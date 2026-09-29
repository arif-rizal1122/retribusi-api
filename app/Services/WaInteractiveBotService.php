<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\ServiceApplication;
use App\Models\Taxpayer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * WaInteractiveBotService - Two-Way Interactive WhatsApp Service for M-PAD Kota Baubau
 * Menangani Cek Tagihan, Status Berkas, Informasi Tarif Perda 1/2024, & Panduan Pembayaran
 */
class WaInteractiveBotService
{
    public const TZ = 'Asia/Makassar';

    public function __construct(
        protected WaGatewayService $waGateway
    ) {}

    /**
     * Process incoming WhatsApp message and send automatic reply
     */
    public function handleIncoming(string $rawPhone, string $message): array
    {
        $phone = $this->normalizePhone($rawPhone);
        $cleanMsg = trim(mb_strtolower($message));

        Log::info("WaInteractiveBot: Received message from +{$phone}: {$message}");

        $reply = $this->generateReply($phone, $message, $cleanMsg);

        if (!blank($reply) && !blank($phone)) {
            $sent = $this->waGateway->sendMessage($phone, $reply, 'high');
            return [
                'success' => $sent,
                'phone' => $phone,
                'reply' => $reply,
            ];
        }

        return [
            'success' => false,
            'phone' => $phone,
            'reply' => null,
        ];
    }

    /**
     * Generate response according to intent
     */
    public function generateReply(string $phone, string $rawMessage, string $cleanMsg): string
    {
        // 1. Detect 16-digit NIK
        if (preg_match('/\b\d{16}\b/', $rawMessage, $nikMatches)) {
            return $this->handleBillingInquiryByNik($nikMatches[0], $phone);
        }

        // 2. Billing / Tagihan Inquiry
        if (str_contains($cleanMsg, 'tagihan') 
            || str_contains($cleanMsg, 'cek tagihan') 
            || str_contains($cleanMsg, 'tunggakan') 
            || str_contains($cleanMsg, 'bayar tagihan')
            || str_contains($cleanMsg, '1') && strlen($cleanMsg) === 1
        ) {
            return $this->handleBillingInquiryByPhone($phone);
        }

        // 3. Application / Berkas Status Inquiry
        if (str_contains($cleanMsg, 'status') 
            || str_contains($cleanMsg, 'berkas') 
            || str_contains($cleanMsg, 'permohonan') 
            || str_contains($cleanMsg, 'lacak')
            || str_contains($cleanMsg, '2') && strlen($cleanMsg) === 1
        ) {
            return $this->handleApplicationStatusInquiry($phone, $rawMessage);
        }

        // 4. Tariff & Regulation Perda 1/2024
        if (str_contains($cleanMsg, 'tarif') 
            || str_contains($cleanMsg, 'perda') 
            || str_contains($cleanMsg, 'aturan') 
            || str_contains($cleanMsg, 'besaran')
            || str_contains($cleanMsg, 'pajak apa')
            || str_contains($cleanMsg, '3') && strlen($cleanMsg) === 1
        ) {
            return $this->handleTariffAndRegulationInfo();
        }

        // 5. Payment Channel Guide
        if (str_contains($cleanMsg, 'cara bayar') 
            || str_contains($cleanMsg, 'metode') 
            || str_contains($cleanMsg, 'qris') 
            || str_contains($cleanMsg, 'briva') 
            || str_contains($cleanMsg, 'bank sultra')
            || str_contains($cleanMsg, 'bri')
            || str_contains($cleanMsg, '4') && strlen($cleanMsg) === 1
        ) {
            return $this->handlePaymentGuide();
        }

        // Default: Interactive Welcome & Service Menu
        return $this->getWelcomeMenu();
    }

    /**
     * Handler: Inquiry billing by phone
     */
    protected function handleBillingInquiryByPhone(string $phone): string
    {
        $localPhone = str_starts_with($phone, '62') ? '0' . substr($phone, 2) : $phone;

        $taxpayer = Taxpayer::where('phone', $phone)
            ->orWhere('phone', $localPhone)
            ->first();

        if (!$taxpayer) {
            return "*[M-PAD KOTA BAUBAU - CEK TAGIHAN]*\n\n"
                 . "Nomor WhatsApp Anda (+{$phone}) belum tertaut dengan akun Wajib Pajak di database M-PAD.\n\n"
                 . "💡 *Cara Mudah Cek Tagihan:*\n"
                 . "Balas pesan ini dengan mengetikkan *16 digit NIK* (KTP) Anda.\n"
                 . "Contoh: `7472012345670001`\n\n"
                 . "Atau masuk ke aplikasi resmi warga:\n"
                 . "🔗 https://mpad.baubaukota.go.id/login";
        }

        return $this->buildBillingSummary($taxpayer);
    }

    /**
     * Handler: Inquiry billing by NIK
     */
    protected function handleBillingInquiryByNik(string $nik, string $phone): string
    {
        $taxpayer = Taxpayer::where('nik', $nik)->first();

        if (!$taxpayer) {
            return "*[M-PAD KOTA BAUBAU - CEK TAGIHAN]*\n\n"
                 . "Data Wajib Pajak dengan NIK *{$nik}* belum terdaftar dalam sistem penerimaan daerah M-PAD.\n\n"
                 . "Silakan lakukan pendaftaran identitas mandiri melalui aplikasi:\n"
                 . "🔗 https://mpad.baubaukota.go.id/register";
        }

        return $this->buildBillingSummary($taxpayer);
    }

    /**
     * Build billing summary string
     */
    protected function buildBillingSummary(Taxpayer $taxpayer): string
    {
        $pendingBills = Bill::where('taxpayer_id', $taxpayer->id)
            ->where('status', 'pending')
            ->with(['retributionType', 'taxObject'])
            ->get();

        if ($pendingBills->isEmpty()) {
            return "*[M-PAD KOTA BAUBAU - STATUS TAGIHAN]*\n\n"
                 . "Halo Bapak/Ibu *{$taxpayer->name}*,\n\n"
                 . "✅ *STATUS: TIDAK ADA TUNGGAKAN*\n"
                 . "Alhamdulillah, seluruh kewajiban retribusi dan pajak daerah Anda saat ini dalam status *LUNAS*.\n\n"
                 . "Terima kasih atas kepatuhan Anda dalam membangun fasilitas publik Kota Baubau.\n\n"
                 . "Unduh bukti lunas resmi (e-SSPD) ber-Segel Elektronik BSrE di:\n"
                 . "🔗 https://mpad.baubaukota.go.id/riwayat";
        }

        $totalNominal = $pendingBills->sum('total_amount');
        $totalRupiah = number_format($totalNominal, 0, ',', '.');
        $count = $pendingBills->count();

        $output = "*[M-PAD KOTA BAUBAU - INFORMASI TAGIHAN AKTIF]*\n\n"
                . "Halo Bapak/Ibu *{$taxpayer->name}*,\n"
                . "Ditemukan *{$count} tagihan aktif* yang belum dilunasi:\n\n";

        foreach ($pendingBills->take(3) as $idx => $b) {
            $num = $idx + 1;
            $namaLayanan = $b->retributionType?->name ?: ($b->taxObject?->name ?: 'Retribusi Daerah');
            $nomRupiah = number_format($b->total_amount, 0, ',', '.');
            $jatuhTempo = $b->due_date ? Carbon::parse($b->due_date)->format('d/m/Y') : '-';
            $briva = $b->briva_number ?? ($b->va_number ?? '-');

            $output .= "{$num}. *{$namaLayanan}*\n"
                    . "   📄 No: {$b->bill_number}\n"
                    . "   💰 Nominal: *Rp {$nomRupiah}*\n"
                    . "   ⏳ Jatuh Tempo: {$jatuhTempo}\n"
                    . "   💳 BRIVA: `{$briva}`\n\n";
        }

        if ($count > 3) {
            $sisa = $count - 3;
            $output .= "_...dan {$sisa} tagihan lainnya._\n\n";
        }

        $output .= "💰 *TOTAL KEWAJIBAN:* *Rp {$totalRupiah}*\n\n"
                . "⚡ *BAYAR INSTAN TANPA BIAYA ADMIN (QRIS / BRIVA):*\n"
                . "🔗 https://mpad.baubaukota.go.id/bayar\n\n"
                . "Hindari denda keterlambatan 1% per bulan sesuai Perda No. 1/2024.";

        return $output;
    }

    /**
     * Handler: Application status inquiry
     */
    protected function handleApplicationStatusInquiry(string $phone, string $rawMessage): string
    {
        // Try to find application number in text (e.g. REG-123 or SRV-123)
        if (preg_match('/\b[A-Za-z0-9\/-]{6,25}\b/', $rawMessage, $matches)) {
            $app = ServiceApplication::where('application_no', $matches[0])->first();
            if ($app) {
                return $this->buildApplicationStatusMessage($app);
            }
        }

        // Fallback: look up by applicant phone
        $localPhone = str_starts_with($phone, '62') ? '0' . substr($phone, 2) : $phone;

        $apps = ServiceApplication::where(function ($q) use ($phone, $localPhone) {
            $q->where('contact_phone', $phone)
              ->orWhere('contact_phone', $localPhone);
        })
        ->latest()
        ->get();

        if ($apps->isEmpty()) {
            return "*[M-PAD KOTA BAUBAU - STATUS PERMOHONAN]*\n\n"
                 . "Tidak ditemukan permohonan layanan publik aktif yang terhubung dengan nomor HP ini.\n\n"
                 . "💡 Jika Anda memiliki *Nomor Registrasi Berkas*, ketikkan nomor tersebut pada chat ini (contoh: `SRV-2026-0001`).\n\n"
                 . "Atau buka riwayat layanan di aplikasi Mobile Warga:\n"
                 . "🔗 https://mpad.baubaukota.go.id/layanan";
        }

        $latest = $apps->first();
        return $this->buildApplicationStatusMessage($latest);
    }

    /**
     * Build application status message
     */
    protected function buildApplicationStatusMessage(ServiceApplication $app): string
    {
        $namaLayanan = $app->retributionType?->name ?: $app->service_type ?: 'Layanan M-PAD';
        $statusLabel = $app->statusLabel();
        $tglDaftar = $app->created_at ? Carbon::parse($app->created_at)->timezone(self::TZ)->format('d/m/Y H:i') : '-';

        return "*[M-PAD KOTA BAUBAU - STATUS BERKAS PERMOHONAN]*\n\n"
             . "📋 *Nomor Registrasi:* #{$app->application_no}\n"
             . "📌 *Layanan:* {$namaLayanan}\n"
             . "🕒 *Diajukan:* {$tglDaftar} WITA\n"
             . "📊 *Status Terkini:* *{$statusLabel}*\n\n"
             . "⏱️ *Standar Waktu Verifikasi:* Maks. 24 Jam Kerja (SLA Resmi)\n\n"
             . "Pantau detail catatan petugas atau dokumen revisi:\n"
             . "🔗 https://mpad.baubaukota.go.id/layanan/status/{$app->application_no}\n\n"
             . "Sistem Informasi Terpadu Pendapatan Asli Daerah Kota Baubau.";
    }

    /**
     * Handler: Tariff and regulation info Perda 1/2024
     */
    protected function handleTariffAndRegulationInfo(): string
    {
        return "*[M-PAD KOTA BAUBAU - PANDUAN TARIF PERDA NO. 1/2024]*\n\n"
             . "Berdasarkan Perda Kota Baubau No. 1 Tahun 2024 tentang Pajak Daerah & Retribusi Daerah:\n\n"
             . "🏛️ *PAJAK DAERAH:* \n"
             . "• *PBJT (Hotel, Restoran, Parkir, Hiburan):* 10%\n"
             . "• *PBB-P2:* 0,1% s/d 0,2% dari NJOP (disertai NJOPTKP Rp10.000.000)\n"
             . "• *BPHTB:* 5% dari NPOP (NPOPTKP Rp80.000.000)\n"
             . "• *Pajak Reklame:* Berdasarkan luas m2, tipe media, & zona penempatan\n\n"
             . "🏪 *RETRIBUSI DAERAH:* \n"
             . "• *Pelayanan Pasar:* Sesuai luas kios/los harian atau bulanan\n"
             . "• *Kebersihan/Sampah (DLH):* Terklasifikasi rumah tangga & niaga\n"
             . "• *Parkir Tepi Jalan:* Roda 2 Rp2.000 | Roda 4 Rp3.000\n"
             . "• *Sewa Aset Daerah (PUPR):* Sesuai SK penetapan tarif barang milik daerah\n\n"
             . "Simulasi hitung mandiri dapat diakses di Kalkulator Pajak M-PAD:\n"
             . "🔗 https://mpad.baubaukota.go.id/kalkulator-pajak";
    }

    /**
     * Handler: Payment guide
     */
    protected function handlePaymentGuide(): string
    {
        return "*[M-PAD KOTA BAUBAU - JALUR PEMBAYARAN RESMI]*\n\n"
             . "Pemerintah Kota Baubau menyediakan 3 kanal pembayaran non-tunai bebas antre & *BEBAS BIAYA ADMIN*:\n\n"
             . "1️⃣ *QRIS Dinamis (Instan)*\n"
             . "   Dapat dipindai dari BCA, BRImo, Mandiri Livin, BNI, BSI, Bank Sultra, GoPay, OVO, Dana, ShopeePay.\n\n"
             . "2️⃣ *BRIVA BRI (Bank BRI)*\n"
             . "   Bayar lewat ATM BRI, BRImo, atau *seluruh gerai AgenBRILink* di pelosok Kota Baubau.\n\n"
             . "3️⃣ *SNAP BI Bank Sultra*\n"
             . "   Bayar lewat Bank Sultra Mobile, ATM Bank Sultra, atau Teller Cabang Bank Sultra.\n\n"
             . "Setelah pembayaran berhasil, bukti lunas sah (e-SSPD) ber-Segel Elektronik BSrE langsung terbit otomatis!\n\n"
             . "Akses pembayaran mandiri:\n"
             . "🔗 https://mpad.baubaukota.go.id/bayar";
    }

    /**
     * Interactive welcome menu
     */
    protected function getWelcomeMenu(): string
    {
        return "*[M-PAD KOTA BAUBAU - LAYANAN DIGITAL RESMI]*\n\n"
             . "Halo! Selamat datang di WhatsApp Otomatis Layanan Pajak & Retribusi Kota Baubau.\n\n"
             . "Ketik salah satu kata kunci berikut untuk informasi cepat:\n\n"
             . "👉 Ketik *TAGIHAN* atau *1* untuk Cek Tunggakan / Tagihan Aktif\n"
             . "👉 Ketik *STATUS* atau *2* untuk Cek Progres Permohonan Berkas\n"
             . "👉 Ketik *TARIF* atau *3* untuk Informasi Regulasi Perda 1/2024\n"
             . "👉 Ketik *BAYAR* atau *4* untuk Panduan Pembayaran Non-Tunai\n\n"
             . "💡 Anda juga dapat langsung mengetikkan *16 Digit NIK* untuk cek tagihan terdaftar Anda.\n\n"
             . "Portal Resmi: https://mpad.baubaukota.go.id\n"
             . "_Bapenda Kota Baubau - Transparan, Cepat, & Akuntabel_";
    }

    /**
     * Clean and normalize phone number
     */
    protected function normalizePhone(string $raw): string
    {
        $clean = preg_replace('/[^0-9]/', '', $raw);
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        }
        return $clean;
    }
}
