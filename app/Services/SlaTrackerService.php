<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * SlaTrackerService — pelacakan SLA penugasan layanan pajak daerah.
 *
 * Task 2319. Matriks SLA mengikuti dokumen Tupoksi Kabid & Kasubid
 * (docs/00_kebijakan_strategis/TUPOKSI_KABID_KASUBID_WILAYAH_I_II.md).
 *
 * Catatan penting soal eskalasi:
 * - Aturan bisnis menyebut "peringatan otomatis saat jam ke-18 (toleransi 24 jam)".
 *   Artinya ambang eskalasi = 18 jam sejak tahapan dimulai, sedangkan batas SLA
 *   final = 24 jam. Jadi eskalasi memberi peringatan SEBELUM pelanggaran SLA,
 *   bukan sesudahnya.
 * - Tahapan ber-SLA pendek (otorisasi 4 jam, e-SSPD 5 detik) memakai ambang
 *   proporsional 75% dari deadline, karena 18 jam tidak masuk akal untuk tahap itu.
 */
class SlaTrackerService
{
    /** Matriks SLA per tahapan: durasi (menit) + PIC peran. */
    public const SLA_MATRIX = [
        'VERIFIKASI_BERKAS' => [
            'label'          => 'Verifikasi Permohonan Baru',
            'minutes'        => 720,   // 12 jam
            'escalate_after' => 540,   // 9 jam (75%)
            'pic'            => 'Kasubid Pendataan Wilayah I & II',
        ],
        'SURVEI_LAPANGAN' => [
            'label'          => 'Survei & Pengukuran Fisik',
            'minutes'        => 1440,  // 24 jam
            'escalate_after' => 1080,  // 18 jam — sesuai aturan bisnis
            'pic'            => 'Petugas Lapangan',
        ],
        // Kasubid Penagihan punya jalur verifikasi sendiri. Tupoksinya
        // "Penagihan, Verifikasi & Pelaporan" — bukan menumpang jalur Pendataan.
        'VERIFIKASI_PENAGIHAN' => [
            'label'          => 'Verifikasi Berkas oleh Kasubid Penagihan',
            'minutes'        => 720,   // 12 jam
            'escalate_after' => 540,   // 9 jam (75%)
            'pic'            => 'Kasubid Penagihan Wilayah I & II',
        ],
        // Jalur Kasubid -> Kabid: setelah L2 selesai, Kabid menugaskan
        // petugas lapangan bila diperlukan survei/peninjauan.
        // Kasubid memeriksa HASIL pekerjaan lapangan (maker-checker).
        // Ini tahap yang memisahkan "sudah dikerjakan petugas" dari
        // "sudah dipastikan selesai oleh Kasubid".
        'VERIFIKASI_HASIL_SURVEI' => [
            'label'          => 'Verifikasi Hasil Survei Lapangan',
            'minutes'        => 720,   // 12 jam
            'escalate_after' => 540,   // 9 jam (75%)
            'pic'            => 'Kasubid Pendataan Wilayah I & II',
        ],
        'VERIFIKASI_HASIL_PENAGIHAN' => [
            'label'          => 'Verifikasi Hasil Penagihan Lapangan',
            'minutes'        => 720,   // 12 jam
            'escalate_after' => 540,
            'pic'            => 'Kasubid Penagihan Wilayah I & II',
        ],
        'PENUGASAN_LAPANGAN' => [
            'label'          => 'Penugasan Petugas Lapangan',
            'minutes'        => 240,   // 4 jam
            'escalate_after' => 180,   // 3 jam (75%)
            'pic'            => 'Kepala Bidang Pajak Daerah I & II',
        ],
        'APPROVAL_KABID' => [
            'label'          => 'Otorisasi Draf SKPD/SKRD',
            'minutes'        => 240,   // 4 jam
            'escalate_after' => 180,   // 3 jam (75%)
            'pic'            => 'Kepala Bidang Pajak Daerah I & II',
        ],
        'PENERBITAN_ESSPD' => [
            'label'          => 'Penerbitan Bukti Lunas e-SSPD',
            'minutes'        => 1,     // dijaga < 5 detik oleh Auto-TTE Hook
            'escalate_after' => 1,
            'pic'            => 'Auto-TTE Hook',
        ],
        'PENERBITAN_STPD' => [
            'label'          => 'Penerbitan STPD Denda',
            'minutes'        => 2880,  // jendela bulanan tgl 16-17
            'escalate_after' => 2160,
            'pic'            => 'Kasubid Penagihan Wilayah II',
        ],
        'CLOSING_REKONSILIASI' => [
            'label'          => 'Closing Rekonsiliasi Kasda',
            'minutes'        => 1440,  // harian sebelum 17.00 WITA
            'escalate_after' => 1080,
            'pic'            => 'Kasubid Penagihan Wilayah I & II',
        ],
    ];

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_ESCALATED = 'ESCALATED';

    // ── Status alur SERAH-TERIMA pekerjaan lapangan (Maker-Checker) ──
    // Petugas TIDAK pernah mengklaim 'SELESAI'. Ia hanya menyatakan
    // "sudah saya kerjakan" (MENUNGGU_VERIFIKASI). Yang memastikan selesai
    // adalah Kasubid lewat TERVERIFIKASI. Bila hasil kurang, REVISI.
    public const STATUS_MENUNGGU_VERIFIKASI = 'MENUNGGU_VERIFIKASI';
    public const STATUS_TERVERIFIKASI       = 'TERVERIFIKASI';
    public const STATUS_REVISI              = 'REVISI';

    public static function isKnownStage(string $stage): bool
    {
        return isset(self::SLA_MATRIX[$stage]);
    }

    /**
     * Buat penugasan baru beserta deadline SLA & jadwal eskalasinya.
     *
     * @param  array{reference_type?:string,reference_id?:int,tax_domain?:string,tax_sector_code?:string,assigned_to_user_id?:int,assigned_by_user_id?:int,notes?:string}  $attrs
     */
    public static function assign(string $stage, array $attrs = []): int
    {
        $cfg = self::SLA_MATRIX[$stage] ?? null;
        $now = Carbon::now();

        return DB::table('service_task_assignments')->insertGetId([
            'reference_type'       => $attrs['reference_type'] ?? null,
            'reference_id'         => $attrs['reference_id'] ?? null,
            'stage'                => $stage,
            'tax_domain'           => $attrs['tax_domain'] ?? null,
            'tax_sector_code'      => $attrs['tax_sector_code'] ?? null,
            'assigned_to_user_id'  => $attrs['assigned_to_user_id'] ?? null,
            'assigned_by_user_id'  => $attrs['assigned_by_user_id'] ?? null,
            'status'               => self::STATUS_PENDING,
            'started_at'           => $now,
            'sla_deadline'         => $cfg ? $now->copy()->addMinutes($cfg['minutes']) : null,
            'escalation_alert_at'  => $cfg ? $now->copy()->addMinutes($cfg['escalate_after']) : null,
            'notes'                => $attrs['notes'] ?? null,
            'created_at'           => $now,
            'updated_at'           => $now,
        ]);
    }

    public static function start(int $id): bool
    {
        return DB::table('service_task_assignments')
            ->where('id', $id)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_ESCALATED])
            ->update([
                'status'     => self::STATUS_IN_PROGRESS,
                'started_at' => DB::raw('IFNULL(started_at, NOW())'),
                'updated_at' => now(),
            ]) > 0;
    }

    /**
     * Selesaikan penugasan. Menghitung apakah SLA terpenuhi.
     */
    public static function complete(int $id, string $status = self::STATUS_APPROVED, ?string $rejectionReason = null): bool
    {
        $row = DB::table('service_task_assignments')->where('id', $id)->first();
        if (! $row) {
            return false;
        }

        $now = Carbon::now();
        $deadline = $row->sla_deadline ? Carbon::parse($row->sla_deadline) : null;
        $started = $row->started_at ? Carbon::parse($row->started_at) : $now;

        return DB::table('service_task_assignments')->where('id', $id)->update([
            'status'           => $status,
            'rejection_reason' => $rejectionReason,
            'completed_at'     => $now,
            'sla_met'          => $deadline ? $now->lessThanOrEqualTo($deadline) : null,
            'duration_minutes' => $started->diffInMinutes($now),
            'updated_at'       => $now,
        ]) > 0;
    }

    /**
     * Daftar penugasan yang sudah melewati ambang eskalasi tapi belum selesai.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public static function overdueForEscalation()
    {
        return DB::table('service_task_assignments')
            ->whereNull('completed_at')
            ->whereNotNull('escalation_alert_at')
            ->where('escalation_alert_at', '<=', now())
            ->whereNull('escalated_at')
            ->orderBy('escalation_alert_at')
            ->get();
    }

    /**
     * Tandai penugasan sebagai ter-eskalasi (dipanggil command terjadwal).
     *
     * @return array<int, array{id:int,stage:string,label:string,pic:?string,due:?string}>
     */
    public static function escalateDue(): array
    {
        $rows = self::overdueForEscalation();
        $result = [];

        foreach ($rows as $row) {
            DB::table('service_task_assignments')->where('id', $row->id)->update([
                'status'       => self::STATUS_ESCALATED,
                'escalated_at' => now(),
                'updated_at'   => now(),
            ]);

            // Lengkapi PIC & label agar pesan notifikasi siap pakai.
            $picUser = $row->assigned_to_user_id
                ? User::find($row->assigned_to_user_id)
                : null;

            $result[] = [
                'id'    => (int) $row->id,
                'stage' => (string) $row->stage,
                'label' => self::SLA_MATRIX[$row->stage]['label'] ?? $row->stage,
                'pic'   => $picUser?->name ?? (self::SLA_MATRIX[$row->stage]['pic'] ?? null),
                'due'   => $row->sla_deadline,
            ];
        }

        return $result;
    }

    /**
     * Ringkasan kepatuhan SLA (untuk dashboard / laporan).
     *
     * @return array{total:int,pending:int,in_progress:int,completed:int,sla_met:int,sla_breached:int,compliance_rate:float}
     */
    public static function summary(?string $taxDomain = null): array
    {
        $q = DB::table('service_task_assignments');
        if ($taxDomain) {
            $q->where('tax_domain', $taxDomain);
        }
        $rows = $q->get();
        $now = Carbon::now();

        $completed = $rows->whereNotNull('completed_at');

        // Pelanggaran SLA dihitung dari DUA sumber, bukan dari kolom sla_met saja:
        //   1. penugasan selesai dengan sla_met = false
        //   2. penugasan BELUM selesai yang deadline-nya sudah lewat
        // Tanpa poin 2, penugasan telantar tidak terhitung sebagai pelanggaran —
        // padahal justru itu yang paling perlu terlihat.
        $breachedCompleted = $completed->where('sla_met', false)->count();
        $breachedOngoing = $rows->filter(function ($r) use ($now) {
            return is_null($r->completed_at)
                && $r->sla_deadline !== null
                && Carbon::parse($r->sla_deadline)->lessThan($now);
        })->count();

        $slaMet = $completed->where('sla_met', true)->count();

        return [
            'total'           => $rows->count(),
            'pending'         => $rows->where('status', self::STATUS_PENDING)->count(),
            'in_progress'     => $rows->where('status', self::STATUS_IN_PROGRESS)->count(),
            'escalated'       => $rows->where('status', self::STATUS_ESCALATED)->count(),
            'completed'       => $completed->count(),
            'sla_met'         => $slaMet,
            'sla_breached'    => $breachedCompleted + $breachedOngoing,
            'breached_ongoing' => $breachedOngoing,
            'compliance_rate' => $completed->count() > 0 ? round($slaMet / $completed->count() * 100, 1) : 0.0,
        ];
    }
}
