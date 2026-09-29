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

    protected ?NotificationService $notif = null;

    public function __construct(
        protected WaGatewayService $wa,
        ?NotificationService $notif = null
    ) {
        $this->notif = $notif ?: app(NotificationService::class);
    }

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
            Log::warning('ServiceApplicationNotifier: log gagal', [
                'application' => $app->application_no,
                'error'       => $e->getMessage(),
            ]);
        }

        return $sent;
    }

    // ---------------------------------------------------------------------
    // Standarisasi Notifikasi Sesuai Katalog Resmi M-PAD (2026)
    // ---------------------------------------------------------------------

    /**
     * NOTIF-WRG-002: Konfirmasi Pendaftaran Berkas Masuk Antrean
     */
    public function notifySubmitted(ServiceApplication $app): bool
    {
        try {
            $res = $this->notif->notifyApplicationSubmitted($app, async: false);
            return (bool)($res['success'] ?? false);
        } catch (\Throwable $e) {
            Log::warning('ServiceApplicationNotifier: notifySubmitted fallback', ['error' => $e->getMessage()]);
            $phone = $this->resolveRecipient($app);
            if (!$phone) {
                return false;
            }
            $nama = $app->contact_name ?: optional($app->applicant)->name ?: 'Pemohon';
            $msg = "Halo {$nama}, pengajuan Anda (#{$app->application_no}) telah kami terima dan dalam antrean verifikasi OPD.";
            return $this->send($phone, $msg, $app, 'submitted');
        }
    }

    /**
     * NOTIF-PTG-001: Alert Dispatch Penugasan Survei Lapangan Baru
     */
    public function notifyFieldAssignment(ServiceApplication $app): bool
    {
        $officer = $app->officer;
        if ($officer) {
            try {
                $payload = $app->payload ?: [];
                $alamat = data_get($payload, 'alamat_singkat') ?: data_get($payload, 'alamat') ?: '-';
                $lat = data_get($payload, 'latitude') ?: data_get($payload, 'lat') ?: '-5.4632';
                $lng = data_get($payload, 'longitude') ?: data_get($payload, 'lng') ?: '122.6074';
                $deadline = $app->sla_deadline
                    ? Carbon::parse($app->sla_deadline)->timezone(self::TZ)->format('d/m/Y H:i')
                    : Carbon::now(self::TZ)->addHours(24)->format('d/m/Y H:i');

                $res = $this->notif->notifyFieldDispatch(
                    petugasUser: $officer,
                    namaOpd: 'Bapenda / OPD Pengampu',
                    nomorBerkas: $app->application_no,
                    namaPemohon: $app->contact_name ?: optional($app->applicant)->name ?: 'Wajib Pajak',
                    alamatLengkap: $alamat,
                    latitude: $lat,
                    longitude: $lng,
                    tenggatWaktuWita: $deadline,
                    taskId: $app->id,
                    async: false
                );
                return (bool)($res['success'] ?? false);
            } catch (\Throwable $e) {
                Log::warning('ServiceApplicationNotifier: notifyFieldAssignment fallback', ['error' => $e->getMessage()]);
            }
        }

        $phone = self::normalizePhone(optional($officer)->phone);
        if (!$phone) {
            return false;
        }

        $alamat = data_get($app->payload, 'alamat_singkat') ?: data_get($app->payload, 'alamat') ?: '-';
        $deadline = $app->sla_deadline ? BusinessDayCalculator::formatId(Carbon::parse($app->sla_deadline)) : '-';
        $msg = "Tugas Baru: Anda ditugaskan melakukan verifikasi lapangan #{$app->application_no} di {$alamat}. Batas waktu: {$deadline}.";

        return $this->send($phone, $msg, $app, 'field_assigned', $officer?->id);
    }

    /**
     * NOTIF-WRG-004: Penerbitan Ketetapan SKRD/SKPD & Kode Billing Bayar
     */
    public function notifyBillingIssued(ServiceApplication $app, ?string $dueDate = null): bool
    {
        $phone = $this->resolveRecipient($app);
        if (!$phone) {
            return false;
        }

        $due = $dueDate ?: Carbon::now(self::TZ)->addDays(30)->format('d/m/Y');
        $nama = $app->contact_name ?: optional($app->applicant)->name ?: 'Wajib Pajak';
        $nominal = data_get($app->payload, 'nominal') ?: data_get($app->payload, 'total_retribusi') ?: 0;

        try {
            $res = $this->notif->notifyBillingIssued(
                phone: $phone,
                namaWarga: $nama,
                nomorSkrd: $app->application_no,
                namaLayanan: $app->retributionType?->name ?: $app->service_type ?: 'Layanan M-PAD',
                nominal: $nominal,
                tanggalJatuhTempo: $due,
                user: $app->applicant,
                refId: $app->id,
                async: false
            );
            return (bool)($res['success'] ?? false);
        } catch (\Throwable $e) {
            Log::warning('ServiceApplicationNotifier: notifyBillingIssued fallback', ['error' => $e->getMessage()]);
        }

        $msg = "Tagihan Anda (#{$app->application_no}) telah disetujui. SKRD dan kode bayar telah terbit. Silakan lakukan pembayaran sebelum {$due}.";
        return $this->send($phone, $msg, $app, 'billing_issued');
    }

    /**
     * NOTIF-WRG-003: Instruksi Perbaikan / Revisi Berkas Permohonan
     */
    public function notifyRevision(ServiceApplication $app, string $catatanVerifikator, ?string $catatanSingkat = null): bool
    {
        try {
            $res = $this->notif->notifyApplicationRevision($app, $catatanVerifikator, $catatanSingkat, async: false);
            return (bool)($res['success'] ?? false);
        } catch (\Throwable $e) {
            Log::warning('ServiceApplicationNotifier: notifyRevision fallback', ['error' => $e->getMessage()]);
            $phone = $this->resolveRecipient($app);
            if (!$phone) {
                return false;
            }
            $nama = $app->contact_name ?: optional($app->applicant)->name ?: 'Pemohon';
            $msg = "Permohonan #{$app->application_no} memerlukan perbaikan: {$catatanVerifikator}. Segera unggah revisi di aplikasi M-PAD.";
            return $this->send($phone, $msg, $app, 'revision_needed');
        }
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
    // Notifikasi ke pihak internal & Pejabat OPD
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

    /**
     * NOTIF-ADM-001: Peringatan Dini Batas Waktu Verifikasi Berkas (SLA Jam ke-18)
     */
    public function notifySlaReminder(ServiceApplication $app, ?string $phone = null): bool
    {
        $target = self::normalizePhone($phone)
            ?: self::normalizePhone(optional($app->officer)->phone);
        if (!$target) {
            return false;
        }

        try {
            $res = $this->notif->notifySlaWarning18h(
                opdPhone: $target,
                namaOpd: 'OPD Pengampu Teknis',
                nomorBerkas: $app->application_no,
                namaPemohon: $app->contact_name ?: optional($app->applicant)->name ?: 'Wajib Pajak',
                namaLayanan: $app->retributionType?->name ?: $app->service_type ?: 'Layanan M-PAD',
                verifikatorUser: $app->officer,
                appId: $app->id,
                async: false
            );
            return (bool)($res['success'] ?? false);
        } catch (\Throwable $e) {
            Log::warning('ServiceApplicationNotifier: notifySlaReminder fallback', ['error' => $e->getMessage()]);
        }

        $msg = "Pengingat: Permohonan Reklame #{$app->application_no} mendekati batas SLA. Segera selesaikan verifikasi.";
        return $this->send($target, $msg, $app, 'sla_reminder');
    }

    /**
     * NOTIF-ADM-002: Eskalasi Kritis Pelanggaran SLA (Jam ke-23)
     */
    public function notifySlaBreached(ServiceApplication $app, ?string $kabidPhone = null): bool
    {
        $target = self::normalizePhone($kabidPhone);
        if (!$target) {
            return false;
        }

        try {
            $res = $this->notif->notifySlaCritical23h(
                leaderPhone: $target,
                namaOpd: 'OPD Pengampu / Bapenda',
                nomorBerkas: $app->application_no,
                namaPemohon: $app->contact_name ?: optional($app->applicant)->name ?: 'Wajib Pajak',
                leaderUser: null,
                appId: $app->id,
                async: false
            );
            return (bool)($res['success'] ?? false);
        } catch (\Throwable $e) {
            Log::warning('ServiceApplicationNotifier: notifySlaBreached fallback', ['error' => $e->getMessage()]);
        }

        $msg = "Peringatan: Permohonan Reklame #{$app->application_no} telah melewati batas waktu SLA. Mohon disposisi segera.";
        return $this->send($target, $msg, $app, 'sla_breached');
    }
}
