<?php

namespace App\Services;

use App\Models\ServiceApplication;
use App\Models\NotificationLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Notifikasi WhatsApp untuk modul Permohonan Layanan.
 *
 * Memakai WaGatewayService yang SUDAH ADA di produksi (pola sama dengan
 * NotarisApprovalController & DlhTrashBillingController).
 *
 * Setiap pengiriman WAJIB dicatat di notification_logs.
 *
 * ⚠️ Skema notification_logs (terverifikasi 27 Sep 2026):
 *    id, taxpayer_id, phone, channel, type, reference_type, reference_id,
 *    message_preview, status, error_message, sent_at, created_at, updated_at
 */
class ServiceApplicationNotifier
{
    public const TZ = 'Asia/Makassar';

    /** Prefix reference_type agar mudah difilter di log. */
    public const REF_TYPE = 'service_applications';

    public function __construct(protected WaGatewayService $wa) {}

    /**
     * Normalisasi nomor HP ke format 628xxx.
     * Menangani: 08xxx, +628xxx, 628xxx, 8xxx, spasi/tanda hubung/titik.
     */
    public static function normalizePhone(?string $raw): ?string
    {
        if (blank($raw)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);

        if (blank($digits)) {
            return null;
        }

        if (str_starts_with($digits, '62')) {
            $normalized = $digits;
        } elseif (str_starts_with($digits, '0')) {
            $normalized = '62' . ltrim($digits, '0');
        } elseif (str_starts_with($digits, '8')) {
            $normalized = '62' . $digits;
        } else {
            return null;
        }

        if (!preg_match('/^62\d{9,13}$/', $normalized)) {
            return null;
        }

        return $normalized;
    }

    /**
     * Nomor tujuan WA: prioritas contact_phone pemohon, fallback users.phone.
     */
    public function resolveRecipient(ServiceApplication $app): ?string
    {
        foreach ([$app->contact_phone, optional($app->applicant)->phone] as $c) {
            $n = self::normalizePhone($c);
            if ($n) {
                return $n;
            }
        }
        return null;
    }

    /**
     * Kirim WA + catat ke notification_logs. TIDAK pernah throw — gagal hanya dicatat.
     */
    public function send(
        string $phone,
        string $message,
        ServiceApplication $app,
        string $event,
        ?int $taxpayerId = null
    ): bool {
        $sent = false;
        $error = null;

        try {
            $sent = (bool) $this->wa->sendMessage($phone, $message);
            if (!$sent) {
                $error = 'gateway mengembalikan false';
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            Log::warning('ServiceApplicationNotifier: WA gagal', [
                'application' => $app->application_no,
                'event'       => $event,
                'error'       => $error,
            ]);
        }

        try {
            NotificationLog::create([
                'taxpayer_id'     => $taxpayerId ?? $app->user_id,
                'phone'           => $phone,
                'channel'         => 'whatsapp',
                'type'            => 'service_application',
                'reference_type'  => self::REF_TYPE,
                'reference_id'    => $app->id,
                'message_preview' => mb_substr($message, 0, 500),
                'status'          => $sent ? 'sent' : 'failed',
                'error_message'   => $error ? mb_substr($error, 0, 255) : null,
                'sent_at'         => $sent ? now() : null,
            ]);
        } catch (\Throwable $e) {
            // Log gagal bukan alasan memblokir alur bisnis
            Log::warning('ServiceApplicationNotifier: log gagal', [
                'application' => $app->application_no,
                'error'       => $e->getMessage(),
            ]);
        }

        return $sent;
    }

    // ---------------------------------------------------------------------
    // Template pesan (copywriting standar dari keputusan arsitektur)
    // ---------------------------------------------------------------------

    public function notifySubmitted(ServiceApplication $app): bool
    {
        $phone = $this->resolveRecipient($app);
        if (!$phone) {
            Log::info('ServiceApplicationNotifier: nomor pemohon kosong', ['app' => $app->application_no]);
            return false;
        }

        $nama = $app->contact_name ?: optional($app->applicant)->name ?: 'Pemohon';
        $msg = "Halo {$nama}, permohonan reklame Anda (#{$app->application_no}) telah kami terima "
             . "dan sedang dalam proses verifikasi teknis oleh tim DPMPTSP Kota Baubau. "
             . "Estimasi verifikasi maksimal 2 hari kerja. Pantau status berkala di M-PAD.";

        return $this->send($phone, $msg, $app, 'submitted');
    }

    public function notifyFieldAssignment(ServiceApplication $app): bool
    {
        $officer = $app->officer;
        $phone = self::normalizePhone(optional($officer)->phone);
        if (!$phone) {
            Log::info('ServiceApplicationNotifier: nomor petugas kosong', ['app' => $app->application_no]);
            return false;
        }

        $alamat = data_get($app->payload, 'alamat_singkat')
            ?: data_get($app->payload, 'alamat')
            ?: '-';
        $deadline = $app->sla_deadline
            ? BusinessDayCalculator::formatId(Carbon::parse($app->sla_deadline))
            : '-';

        $msg = "Tugas Baru: Anda ditugaskan melakukan verifikasi fisik ruang reklame "
             . "#{$app->application_no} di {$alamat}. Batas waktu verifikasi lapangan: {$deadline}. "
             . "Buka aplikasi M-PAD Petugas untuk melihat titik lokasi peta.";

        return $this->send($phone, $msg, $app, 'field_assigned', $officer?->id);
    }

    public function notifyBillingIssued(ServiceApplication $app, ?string $dueDate = null): bool
    {
        $phone = $this->resolveRecipient($app);
        if (!$phone) {
            return false;
        }

        $due = $dueDate ?: '-';
        $msg = "Kabar baik! Permohonan reklame Anda (#{$app->application_no}) telah disetujui. "
             . "SKPD dan kode bayar telah diterbitkan. Silakan buka aplikasi M-PAD dan lakukan "
             . "pembayaran sebelum {$due} agar reklame dapat ditayangkan secara legal.";

        return $this->send($phone, $msg, $app, 'billing_issued');
    }

    public function notifyStatusChange(ServiceApplication $app, string $toStatus, ?string $extra = null): bool
    {
        $phone = $this->resolveRecipient($app);
        if (!$phone) {
            return false;
        }

        $msg = "Permohonan reklame Anda (#{$app->application_no}) telah diperbarui: {$app->statusLabel()}.";
        if ($extra) {
            $msg .= " {$extra}";
        }
        $msg .= " Pantau status berkala di M-PAD.";

        return $this->send($phone, $msg, $app, "status:{$toStatus}");
    }

    // ---------------------------------------------------------------------
    // Notifikasi ke pihak internal
    // ---------------------------------------------------------------------

    public function notifyPtspNewApplication(ServiceApplication $app, ?string $ptspPhone = null): bool
    {
        $phone = self::normalizePhone($ptspPhone);
        if (!$phone) {
            return false;
        }

        $deadline = $app->sla_deadline
            ? BusinessDayCalculator::formatId(Carbon::parse($app->sla_deadline))
            : '-';

        $msg = "Pengajuan baru #{$app->application_no} jenis reklame menunggu verifikasi Anda. "
             . "Wajib ditindaklanjuti sebelum {$deadline}. Buka dashboard M-PAD PTSP.";

        return $this->send($phone, $msg, $app, 'ptsp_new');
    }

    public function notifySlaReminder(ServiceApplication $app, ?string $phone = null): bool
    {
        $target = self::normalizePhone($phone)
            ?: self::normalizePhone(optional($app->officer)->phone);
        if (!$target) {
            return false;
        }

        $msg = "Pengingat: Permohonan Reklame #{$app->application_no} tersisa 4 jam sebelum "
             . "batas SLA. Segera selesaikan verifikasi.";

        return $this->send($target, $msg, $app, 'sla_reminder');
    }

    public function notifySlaBreached(ServiceApplication $app, ?string $kabidPhone = null): bool
    {
        $target = self::normalizePhone($kabidPhone);
        if (!$target) {
            return false;
        }

        $msg = "Peringatan: Permohonan Reklame #{$app->application_no} telah melewati batas "
             . "waktu SLA. Mohon disposisi segera.";

        return $this->send($target, $msg, $app, 'sla_breached');
    }
}
