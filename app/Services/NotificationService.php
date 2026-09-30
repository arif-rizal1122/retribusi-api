<?php

namespace App\Services;

use App\Jobs\SendNotificationJob;
use App\Models\NotificationTemplate;
use App\Models\ServiceApplication;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * NotificationService - Master Delivery Class for M-PAD Digital Ecosystem
 * Acuan Dokumen Implementasi Teknis & Katalog Notifikasi Terperinci Kota Baubau (2026)
 */
class NotificationService
{
    public const TZ = 'Asia/Makassar';

    public function __construct(
        protected NotificationDispatcher $dispatcher
    ) {}

    /**
     * Dispatch notification either synchronously or queued via Redis/DB
     */
    public function dispatch(
        string $templateCode,
        array $payload = [],
        ?User $recipientUser = null,
        ?string $overridePhone = null,
        ?string $referenceType = null,
        mixed $referenceId = null,
        ?int $taxpayerId = null,
        bool $async = false
    ): array {
        if ($async) {
            SendNotificationJob::dispatch(
                templateCode: $templateCode,
                payload: $payload,
                recipientUserId: $recipientUser?->id,
                overridePhone: $overridePhone,
                referenceType: $referenceType,
                referenceId: $referenceId,
                taxpayerId: $taxpayerId
            );

            return [
                'success' => true,
                'queued' => true,
                'template' => $templateCode,
            ];
        }

        return $this->dispatcher->dispatch(
            templateCode: $templateCode,
            payload: $payload,
            recipientUser: $recipientUser,
            overridePhone: $overridePhone,
            referenceType: $referenceType,
            referenceId: $referenceId,
            taxpayerId: $taxpayerId
        );
    }

    // =========================================================================
    // KELOMPOK A: NOTIFIKASI LAYANAN WARGA (MASYARAKAT / WAJIB PAJAK & RETRIBUSI)
    // =========================================================================

    /**
     * 1. NOTIF-WRG-001: OTP Verifikasi Login Instan
     */
    public function sendOtp(string $phone, string $otpCode, ?User $user = null): array
    {
        return $this->dispatch(
            templateCode: 'NOTIF-WRG-001',
            payload: [
                'otp_code' => $otpCode,
                'otp' => $otpCode,
            ],
            recipientUser: $user,
            overridePhone: $phone,
            referenceType: 'auth_otp',
            referenceId: null,
            async: false // OTP must always be instant
        );
    }

    /**
     * 2. NOTIF-WRG-002: Konfirmasi Pendaftaran Berkas Masuk Antrean
     */
    public function notifyApplicationSubmitted(ServiceApplication $app, bool $async = true): array
    {
        $user = $app->applicant;
        $phone = $app->contact_phone ?: $user?->phone;
        $submitTime = $app->created_at 
            ? Carbon::parse($app->created_at)->timezone(self::TZ)->format('H:i') 
            : Carbon::now(self::TZ)->format('H:i');

        return $this->dispatch(
            templateCode: 'NOTIF-WRG-002',
            payload: [
                'nama_warga' => $app->contact_name ?: $user?->name ?: 'Wajib Pajak',
                'nama_layanan' => $app->retributionType?->name ?: $app->service_type ?: 'Layanan Daerah',
                'nomor_berkas' => $app->application_no,
                'waktu_submit' => $submitTime,
            ],
            recipientUser: $user,
            overridePhone: $phone,
            referenceType: 'service_applications',
            referenceId: $app->id,
            taxpayerId: $app->user_id,
            async: $async
        );
    }

    /**
     * 3. NOTIF-WRG-003: Instruksi Perbaikan / Revisi Berkas Permohonan
     */
    public function notifyApplicationRevision(
        ServiceApplication $app,
        string $catatanVerifikator,
        ?string $catatanSingkat = null,
        bool $async = false
    ): array {
        $user = $app->applicant;
        $phone = $app->contact_phone ?: $user?->phone;

        return $this->dispatch(
            templateCode: 'NOTIF-WRG-003',
            payload: [
                'nama_warga' => $app->contact_name ?: $user?->name ?: 'Wajib Pajak',
                'nama_layanan' => $app->retributionType?->name ?: $app->service_type ?: 'Layanan Daerah',
                'nomor_berkas' => $app->application_no,
                'catatan_verifikator' => $catatanVerifikator,
                'catatan_singkat' => $catatanSingkat ?: mb_substr($catatanVerifikator, 0, 50),
            ],
            recipientUser: $user,
            overridePhone: $phone,
            referenceType: 'service_applications',
            referenceId: $app->id,
            taxpayerId: $app->user_id,
            async: $async
        );
    }

    /**
     * 4. NOTIF-WRG-004: Penerbitan Ketetapan SKRD/SKPD & Kode Billing Bayar
     */
    public function notifyBillingIssued(
        string $phone,
        string $namaWarga,
        string $nomorSkrd,
        string $namaLayanan,
        int|float|string $nominal,
        string $tanggalJatuhTempo,
        ?string $kodeSnap = null,
        ?string $kodeBriva = null,
        ?User $user = null,
        mixed $refId = null,
        bool $async = false
    ): array {
        $nominalRupiah = is_numeric($nominal) ? number_format((float)$nominal, 0, ',', '.') : (string)$nominal;

        return $this->dispatch(
            templateCode: 'NOTIF-WRG-004',
            payload: [
                'nama_warga' => $namaWarga,
                'nomor_skrd' => $nomorSkrd,
                'nama_layanan' => $namaLayanan,
                'nominal_rupiah' => $nominalRupiah,
                'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
                'kode_billing_snap' => $kodeSnap ?: '-',
                'kode_briva_16_digit' => $kodeBriva ?: '-',
            ],
            recipientUser: $user,
            overridePhone: $phone,
            referenceType: 'billing',
            referenceId: $refId,
            async: $async
        );
    }

    /**
     * 5. NOTIF-WRG-005: Pengingat Jatuh Tempo Berkala (H-7, H-3, dan Hari-H)
     */
    public function notifyDueDateReminder(
        string $phone,
        string $namaWarga,
        string $nomorSkrd,
        string $namaLayanan,
        int|float|string $nominal,
        string $tanggalJatuhTempo,
        int $sisaHari,
        ?string $kodeSnap = null,
        ?string $kodeBriva = null,
        ?User $user = null,
        mixed $refId = null
    ): array {
        $nominalRupiah = is_numeric($nominal) ? number_format((float)$nominal, 0, ',', '.') : (string)$nominal;

        return $this->dispatch(
            templateCode: 'NOTIF-WRG-005',
            payload: [
                'nama_warga' => $namaWarga,
                'nomor_skrd' => $nomorSkrd,
                'nama_layanan' => $namaLayanan,
                'nominal_rupiah' => $nominalRupiah,
                'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
                'sisa_hari_lagi' => (string)$sisaHari,
                'kode_briva' => $kodeBriva ?: '-',
                'kode_snap' => $kodeSnap ?: '-',
            ],
            recipientUser: $user,
            overridePhone: $phone,
            referenceType: 'billing_reminder',
            referenceId: $refId,
            async: true // Bulk low priority
        );
    }

    /**
     * 6. NOTIF-WRG-006: Konfirmasi Pembayaran Berhasil & Bukti Lunas e-SSPD Sah
     */
    public function notifyPaymentSuccess(
        string $phone,
        string $namaWarga,
        string $ntpd,
        string $nomorSkrd,
        string $namaLayanan,
        int|float|string $nominal,
        string $waktuBayar,
        string $namaChannelBank,
        ?User $user = null,
        mixed $paymentId = null,
        bool $async = false
    ): array {
        $nominalRupiah = is_numeric($nominal) ? number_format((float)$nominal, 0, ',', '.') : (string)$nominal;

        return $this->dispatch(
            templateCode: 'NOTIF-WRG-006',
            payload: [
                'nama_warga' => $namaWarga,
                'nomor_ntpd' => $ntpd,
                'nomor_skrd' => $nomorSkrd,
                'nama_layanan' => $namaLayanan,
                'nominal_rupiah' => $nominalRupiah,
                'waktu_bayar' => $waktuBayar,
                'nama_channel_bank' => $namaChannelBank,
            ],
            recipientUser: $user,
            overridePhone: $phone,
            referenceType: 'payments',
            referenceId: $paymentId,
            async: $async
        );
    }

    // =========================================================================
    // KELOMPOK B: NOTIFIKASI PETUGAS LAPANGAN (SURVEYOR, JUKIR, KOLEKTOR, POS RETRIBUSI)
    // =========================================================================

    /**
     * 7. NOTIF-PTG-001: Alert Dispatch Penugasan Survei Lapangan Baru
     */
    public function notifyFieldDispatch(
        User $petugasUser,
        string $namaOpd,
        string $nomorBerkas,
        string $namaPemohon,
        string $alamatLengkap,
        float|string $latitude,
        float|string $longitude,
        string $tenggatWaktuWita,
        ?string $kelurahanKecamatan = null,
        mixed $taskId = null,
        bool $async = false
    ): array {
        return $this->dispatch(
            templateCode: 'NOTIF-PTG-001',
            payload: [
                'nama_petugas' => $petugasUser->name,
                'id_petugas' => (string)$petugasUser->id,
                'nama_opd' => $namaOpd,
                'nomor_berkas' => $nomorBerkas,
                'nama_pemohon' => $namaPemohon,
                'alamat_lengkap' => $alamatLengkap,
                'latitude' => (string)$latitude,
                'longitude' => (string)$longitude,
                'tenggat_waktu' => $tenggatWaktuWita,
                'kelurahan_kecamatan' => $kelurahanKecamatan ?: $alamatLengkap,
            ],
            recipientUser: $petugasUser,
            overridePhone: $petugasUser->phone,
            referenceType: 'field_tasks',
            referenceId: $taskId,
            async: $async
        );
    }

    /**
     * 8. NOTIF-PTG-002: Peringatan Pelanggaran Radius Geofencing (> 30 Meter)
     */
    public function notifyGeofenceViolation(
        User $petugasUser,
        int|float $distanceMeters,
        mixed $refId = null
    ): array {
        return $this->dispatch(
            templateCode: 'NOTIF-PTG-002',
            payload: [
                'jarak_meter' => (string)round($distanceMeters),
            ],
            recipientUser: $petugasUser,
            overridePhone: $petugasUser->phone,
            referenceType: 'geofence_violations',
            referenceId: $refId,
            async: false // Immediate alert in-app
        );
    }

    /**
     * 9. NOTIF-PTG-003: Peringatan Plafon Uang Tunai di Tangan (Rp800k warn / Rp1M lock)
     */
    public function notifyCashFloatLimit(
        User $petugasUser,
        int|float $totalKasDiTangan,
        string $nomorVaSetor,
        mixed $refId = null,
        bool $async = false
    ): array {
        $kasRupiah = number_format((float)$totalKasDiTangan, 0, ',', '.');

        return $this->dispatch(
            templateCode: 'NOTIF-PTG-003',
            payload: [
                'nama_petugas' => $petugasUser->name,
                'total_kas_di_tangan' => $kasRupiah,
                'nomor_va_setor' => $nomorVaSetor,
            ],
            recipientUser: $petugasUser,
            overridePhone: $petugasUser->phone,
            referenceType: 'cash_float',
            referenceId: $refId,
            async: $async
        );
    }

    /**
     * 10. NOTIF-PTG-004: Peringatan Tutup Shift Harian & Setor Kasda
     */
    public function notifyShiftClose(
        User $petugasUser,
        int|float $totalQris,
        int $jumlahTrxQris,
        int|float $totalTunai,
        int $jumlahTrxTunai,
        mixed $shiftId = null,
        bool $async = false
    ): array {
        return $this->dispatch(
            templateCode: 'NOTIF-PTG-004',
            payload: [
                'nama_petugas' => $petugasUser->name,
                'total_qris' => number_format((float)$totalQris, 0, ',', '.'),
                'jumlah_trx_qris' => (string)$jumlahTrxQris,
                'total_tunai' => number_format((float)$totalTunai, 0, ',', '.'),
                'jumlah_trx_tunai' => (string)$jumlahTrxTunai,
            ],
            recipientUser: $petugasUser,
            overridePhone: $petugasUser->phone,
            referenceType: 'shifts',
            referenceId: $shiftId,
            async: $async
        );
    }

    /**
     * NOTIF-PTG-005: Broadcast Operasi Lapangan / Uji Petik Terpadu
     */
    public function notifyFieldOperationBroadcast(
        User|string $petugasOrPhone,
        string $namaTim,
        string $jadwalKegiatan,
        string $titikLokasi,
        string $targetOperasi,
        string $namaKoordinator,
        mixed $operationId = null,
        bool $async = false
    ): array {
        $user = $petugasOrPhone instanceof User ? $petugasOrPhone : null;
        $phone = $petugasOrPhone instanceof User ? $petugasOrPhone->phone : $petugasOrPhone;

        return $this->dispatch(
            templateCode: 'NOTIF-PTG-005',
            payload: [
                'nama_tim' => $namaTim,
                'jadwal_kegiatan' => $jadwalKegiatan,
                'titik_lokasi' => $titikLokasi,
                'target_operasi' => $targetOperasi,
                'nama_koordinator' => $namaKoordinator,
            ],
            recipientUser: $user,
            overridePhone: $phone,
            referenceType: 'field_operations',
            referenceId: $operationId,
            async: $async
        );
    }

    // =========================================================================
    // KELOMPOK C: NOTIFIKASI ADMIN OPD & PIMPINAN BAPENDA
    // =========================================================================

    /**
     * 11. NOTIF-ADM-001: Peringatan Dini Batas Waktu Verifikasi Berkas (SLA Jam ke-18)
     */
    public function notifySlaWarning18h(
        string $opdPhone,
        string $namaOpd,
        string $nomorBerkas,
        string $namaPemohon,
        string $namaLayanan,
        ?User $verifikatorUser = null,
        mixed $appId = null,
        bool $async = false
    ): array {
        return $this->dispatch(
            templateCode: 'NOTIF-ADM-001',
            payload: [
                'nama_opd' => $namaOpd,
                'nomor_berkas' => $nomorBerkas,
                'nama_pemohon' => $namaPemohon,
                'nama_layanan' => $namaLayanan,
            ],
            recipientUser: $verifikatorUser,
            overridePhone: $opdPhone,
            referenceType: 'sla_monitor_18h',
            referenceId: $appId,
            async: $async
        );
    }

    /**
     * 12. NOTIF-ADM-002: Eskalasi Kritis Pelanggaran SLA (Jam ke-23)
     */
    public function notifySlaCritical23h(
        string $leaderPhone,
        string $namaOpd,
        string $nomorBerkas,
        string $namaPemohon,
        ?User $leaderUser = null,
        mixed $appId = null,
        bool $async = false
    ): array {
        return $this->dispatch(
            templateCode: 'NOTIF-ADM-002',
            payload: [
                'nama_opd' => $namaOpd,
                'nomor_berkas' => $nomorBerkas,
                'nama_pemohon' => $namaPemohon,
            ],
            recipientUser: $leaderUser,
            overridePhone: $leaderPhone,
            referenceType: 'sla_critical_23h',
            referenceId: $appId,
            async: $async
        );
    }
}
