<?php

namespace App\Console\Commands;

use App\Models\ServiceApplication;
use App\Models\User;
use App\Services\BusinessDayCalculator;
use App\Services\ServiceApplicationNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * SLA modul Permohonan Layanan (hari kerja).
 *
 *   1. Pengingat H-4 JAM sebelum deadline  -> WA ke Petugas Lapangan + Admin PTSP
 *   2. Deadline lewat (H+0)                -> is_sla_breached=true, badge merah, WA eskalasi Kabid
 *
 * Dijadwalkan tiap 30 menit (timezone Asia/Makassar).
 * Berbeda dari `sla:escalate` (SlaTrackerService, menit kalender, alur PBB) — JANGAN digabung.
 */
class ServiceSlaMonitor extends Command
{
    /** Ambang pengingat: sisa JAM KERJA sebelum deadline. */
    public const REMINDER_WORK_HOURS = 4;

    protected $signature = 'service:sla-monitor
                            {--dry-run : Tampilkan saja tanpa mengirim WA / mengubah data}
                            {--kabid-phone= : Nomor WA Kabid untuk eskalasi}
                            {--admin-phone= : Nomor WA Admin PTSP untuk pengingat}';

    protected $description = 'Pantau SLA permohonan layanan: pengingat H-4 jam & eskalasi saat lewat batas';

    public function handle(ServiceApplicationNotifier $notifier): int
    {
        $dry = (bool) $this->option('dry-run');
        $kabidPhone = $this->option('kabid-phone');
        $adminPhone = $this->option('admin-phone');

        $now = BusinessDayCalculator::nowWita();

        // Hanya pantau yang masih dalam proses
        $openStatuses = [
            'submitted', 'ptsp_review', 'field_assigned',
            'field_checked', 'bapenda_review', 'revision_needed',
        ];

        $base = ServiceApplication::whereIn('status', $openStatuses)
            ->whereNotNull('sla_deadline');

        // ------------------------------------------------------------------
        // 1. PENGINGAT H-4 JAM KERJA SEBELUM DEADLINE
        // ------------------------------------------------------------------
        // PENTING: pakai JAM KERJA tersisa, bukan jam kalender. SLA modul ini
        // berbasis hari kerja — perbandingan kalender akan salah untuk permohonan
        // yang deadline-nya jatuh setelah akhir pekan.
        // Ambil kandidat: yang deadline-nya dalam 16 jam KALENDER ke depan (jendela
        // cukup lebar), lalu saring tepat dengan hitungan jam kerja.
        $candidates = (clone $base)
            ->where('is_sla_breached', false)
            ->where('sla_deadline', '>', $now)
            ->where('sla_deadline', '<=', $now->copy()->addHours(24 * 4))
            ->get();

        $toRemind = $candidates->filter(function ($app) use ($now) {
            $workLeft = BusinessDayCalculator::workHoursBetween($now, Carbon::parse($app->sla_deadline));
            return $workLeft > 0 && $workLeft <= self::REMINDER_WORK_HOURS;
        });

        $reminded = 0;
        foreach ($toRemind as $app) {
            // Jangan kirim ulang bila sudah pernah diingatkan
            $already = $app->logs()
                ->where('to_status', 'sla_reminder_sent')
                ->exists();

            if ($already) {
                continue;
            }

            $this->line("  [H-4] {$app->application_no} deadline {$app->sla_deadline}");

            if (!$dry) {
                $notifier->notifySlaReminder($app, $adminPhone);
                $app->logs()->create([
                    'from_status' => $app->status,
                    'to_status'   => 'sla_reminder_sent',
                    'actor_role'  => 'system',
                    'notes'       => 'Pengingat H-4 jam dikirim',
                    'meta'        => ['deadline' => $app->sla_deadline->toIso8601String()],
                ]);
            }
            $reminded++;
        }

        // ------------------------------------------------------------------
        // 2. ESKALASI SAAT DEADLINE LEWAT (H+0)
        // ------------------------------------------------------------------
        $breached = (clone $base)
            ->where('is_sla_breached', false)
            ->where('sla_deadline', '<', $now)
            ->get();

        $escalated = 0;
        foreach ($breached as $app) {
            $late = BusinessDayCalculator::workHoursBetween(
                Carbon::parse($app->sla_deadline),
                $now
            );

            $this->warn("  [H+0] {$app->application_no} lewat {$late} jam kerja");

            if (!$dry) {
                $app->update([
                    'is_sla_breached' => true,
                    'sla_breached_at' => $now,
                ]);

                $notifier->notifySlaBreached($app, $kabidPhone);

                $app->logs()->create([
                    'from_status' => $app->status,
                    'to_status'   => 'sla_breached',
                    'actor_role'  => 'system',
                    'notes'       => "SLA terlewati — keterlambatan {$late} jam kerja",
                    'meta'        => [
                        'deadline'     => $app->sla_deadline->toIso8601String(),
                        'breached_at'  => $now->toIso8601String(),
                        'late_hours'   => $late,
                    ],
                ]);
            }
            $escalated++;
        }

        $summary = sprintf(
            '[%s] SLA monitor — H-4: %d, eskalasi: %d%s',
            $now->format('Y-m-d H:i'),
            $reminded,
            $escalated,
            $dry ? ' (DRY-RUN)' : ''
        );
        $this->info($summary);
        Log::info($summary);

        return self::SUCCESS;
    }
}
