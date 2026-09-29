<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Catalog Notifikasi Terperinci Ekosistem Digital M-PAD Kota Baubau (2026)
     */
    public function run(): void
    {
        $templates = [
            // =========================================================================
            // KELOMPOK A: NOTIFIKASI LAYANAN WARGA (MASYARAKAT / WAJIB PAJAK & RETRIBUSI)
            // =========================================================================
            [
                'code' => 'NOTIF-WRG-001',
                'group' => 'warga',
                'label' => 'OTP Verifikasi Login Instan',
                'channels' => ['whatsapp'],
                'priority' => 'high',
                'wa_template' => "*[M-PAD KOTA BAUBAU - KODE KEAMANAN]*\n\n{Yth. Warga Kota Baubau|Halo Warga Kota Baubau},\n\nBerikut adalah Kode Keamanan (OTP) Anda untuk masuk ke sistem M-PAD:\n\n👉 *{otp_code}* 👈\n\nKode ini berlaku selama *5 menit*.\n\n⚠️ *PERINGATAN KEAMANAN:*\nJANGAN PERNAH membagikan kode ini kepada siapa pun, termasuk pihak yang mengaku sebagai petugas Bapenda atau Pemkot Baubau. Petugas resmi tidak pernah meminta kode OTP Anda.\n\nSistem M-PAD Badan Pendapatan Daerah Kota Baubau.",
                'push_title' => null,
                'push_body' => null,
                'deep_link_pattern' => '/auth/verify',
                'is_active' => true,
            ],
            [
                'code' => 'NOTIF-WRG-002',
                'group' => 'warga',
                'label' => 'Konfirmasi Pendaftaran Berkas Masuk Antrean',
                'channels' => ['whatsapp', 'push'],
                'priority' => 'medium',
                'wa_template' => "*[M-PAD KOTA BAUBAU - PENGAJUAN DITERIMA]*\n\n{Halo|Yth.} {Bapak/Ibu|Wajib Retribusi} {nama_warga},\n\nPengajuan layanan Anda telah berhasil kami terima dalam sistem:\n\n📋 *Jenis Layanan:* {nama_layanan}\n🔢 *Nomor Registrasi:* #{nomor_berkas}\n🕒 *Waktu Masuk:* {waktu_submit} WITA\n⏱️ *Standar Waktu Verifikasi:* Maksimal 1x24 Jam Kerja (SLA Resmi)\n\nStatus saat ini: *MENUNGGU VERIFIKASI PETUGAS OPD*.\n\nPantau progres pemeriksaan berkas Anda secara berkala melalui tautan resmi:\n🔗 https://mpad.baubaukota.go.id/layanan/status/{nomor_berkas}\n\nButuh bantuan? Balas pesan ini untuk terhubung dengan layanan informasi M-PAD.",
                'push_title' => 'Pengajuan #{nomor_berkas} Berhasil Dikirim!',
                'push_body' => 'Berkas {nama_layanan} Anda sedang dalam antrean verifikasi teknis OPD (Maks. 24 jam). Ketuk untuk lacak status.',
                'deep_link_pattern' => '/layanan/status/{nomor_berkas}',
                'is_active' => true,
            ],
            [
                'code' => 'NOTIF-WRG-003',
                'group' => 'warga',
                'label' => 'Instruksi Perbaikan / Revisi Berkas Permohonan',
                'channels' => ['whatsapp', 'push'],
                'priority' => 'high',
                'wa_template' => "*[M-PAD KOTA BAUBAU - PERMOHONAN BUTUH PERBAIKAN]*\n\n{Yth.|Halo} Bapak/Ibu {nama_warga},\n\nPermohonan layanan Anda dengan nomor registrasi *#{nomor_berkas}* ({nama_layanan}) memerlukan perbaikan data/lampiran sebelum dapat diproses lebih lanjut.\n\n📝 *Catatan Verifikator Dinas:*\n\"{catatan_verifikator}\"\n\n⏳ *Batas Waktu Perbaikan:* 3x24 Jam dari pesan ini diterima.\n\n*LANGKAH PERBAIKAN:*\n1. Klik tautan perbaikan resmi berikut:\n   🔗 https://mpad.baubaukota.go.id/layanan/revisi/{nomor_berkas}\n2. Masuk menggunakan akun Anda.\n3. Perbarui formulir atau unggah ulang dokumen yang diminta sesuai catatan verifikator di atas.\n4. Tekan tombol 'Kirim Ulang Berkas'.\n\nJika perbaikan tidak dikirimkan dalam 3x24 jam, pengajuan akan dibatalkan otomatis oleh sistem.",
                'push_title' => 'Berkas #{nomor_berkas} Butuh Perbaikan!',
                'push_body' => 'Terdapat dokumen yang perlu disesuaikan: "{catatan_singkat}". Ketuk untuk unggah dokumen sekarang.',
                'deep_link_pattern' => '/layanan/revisi/{nomor_berkas}',
                'is_active' => true,
            ],
            [
                'code' => 'NOTIF-WRG-004',
                'group' => 'warga',
                'label' => 'Penerbitan Ketetapan SKRD/SKPD & Kode Billing Bayar',
                'channels' => ['whatsapp', 'push'],
                'priority' => 'high',
                'wa_template' => "*[M-PAD KOTA BAUBAU - TAGIHAN TERBIT & KODE BAYAR]*\n\n{Yth.|Halo} Bapak/Ibu {nama_warga},\n\nSurat Ketetapan Retribusi/Pajak Daerah (SKRD/SKPD) Anda telah resmi terbit. Berikut rincian pembayaran:\n\n📄 *Nomor Ketetapan:* {nomor_skrd}\n📌 *Objek Layanan:* {nama_layanan}\n💰 *Total Tagihan:* *Rp {nominal_rupiah}*\n⏰ *Batas Pembayaran:* {tanggal_jatuh_tempo} (Pukul 23:59 WITA)\n\n*PILIHAN JALUR PEMBAYARAN RESMI (Bebas Biaya Admin):*\n1️⃣ *Kode Billing SNAP BI (Bank Sultra):*\n   👉 *{kode_billing_snap}*\n   (Dapat dibayarkan via Bank Sultra Mobile, ATM Bank Sultra, atau Teller Kantor Cabang Bank Sultra terdekat).\n\n2️⃣ *BRIVA BRI (Bank BRI):*\n   👉 *{kode_briva_16_digit}*\n   (Dapat dibayarkan via aplikasi BRImo, ATM BRI, atau AgenBRILink di seluruh pelosok kota).\n\n3️⃣ *QRIS Dinamis (Semua Bank / Dompet Digital):*\n   Bayar langsung tanpa repot mengetik kode dengan klik tautan berikut:\n   🔗 https://mpad.baubaukota.go.id/bayar/{nomor_skrd}\n\n⚠️ Pembayaran yang melewati tanggal jatuh tempo akan dikenakan denda administratif keterlambatan sebesar 1% per bulan sesuai Perda Kota Baubau No. 1 Tahun 2024.",
                'push_title' => 'Tagihan SKRD Rp {nominal_rupiah} Telah Terbit',
                'push_body' => 'Bayar sebelum {tanggal_jatuh_tempo}. Ketuk untuk lihat Kode Billing & bayar via QRIS instan.',
                'deep_link_pattern' => '/bayar/{nomor_skrd}',
                'is_active' => true,
            ],
            [
                'code' => 'NOTIF-WRG-005',
                'group' => 'warga',
                'label' => 'Pengingat Jatuh Tempo Berkala (H-7, H-3, dan Hari-H)',
                'channels' => ['whatsapp', 'push'],
                'priority' => 'low',
                'wa_template' => "*[M-PAD KOTA BAUBAU - PENGINGAT JATUH TEMPO]*\n\n{Halo|Yth.} Bapak/Ibu {nama_warga},\n\nKami mengingatkan bahwa tagihan retribusi/pajak daerah Anda akan segera jatuh tempo:\n\n📌 *Layanan:* {nama_layanan}\n📄 *Nomor Ketetapan:* {nomor_skrd}\n💰 *Nominal:* *Rp {nominal_rupiah}*\n⏳ *Jatuh Tempo:* *{tanggal_jatuh_tempo}* ({sisa_hari_lagi} hari lagi)\n💳 *Kode Bayar BRIVA:* `{kode_briva}`\n💳 *Kode Billing Bank Sultra:* `{kode_snap}`\n\nHindari denda administratif keterlambatan 1% per bulan dengan melunasi kewajiban sebelum tenggat waktu.\n\nBayar sekarang melalui tautan cepat:\n🔗 https://mpad.baubaukota.go.id/bayar/{nomor_skrd}\n\nAbaikan pesan ini jika Anda telah melakukan pembayaran dalam 1 jam terakhir.",
                'push_title' => 'Pengingat: Tagihan #{nomor_skrd} Jatuh Tempo dalam {sisa_hari_lagi} Hari',
                'push_body' => 'Segera lunasi Rp {nominal_rupiah} untuk menghindari denda keterlambatan 1% per bulan.',
                'deep_link_pattern' => '/bayar/{nomor_skrd}',
                'is_active' => true,
            ],
            [
                'code' => 'NOTIF-WRG-006',
                'group' => 'warga',
                'label' => 'Konfirmasi Pembayaran Berhasil & Bukti Lunas e-SSPD Sah',
                'channels' => ['whatsapp', 'push'],
                'priority' => 'high',
                'wa_template' => "*[M-PAD KOTA BAUBAU - BUKTI PEMBAYARAN LUNAS SAH]*\n\n{Alhamdulillah, terima kasih|Terima kasih} Bapak/Ibu {nama_warga}!\n\nPembayaran pajak/retribusi daerah Anda telah *BERHASIL* dan 100% langsung disetor ke Rekening Kas Umum Daerah (RKUD) Pemerintah Kota Baubau.\n\n🧾 *Rincian Penerimaan Kasda:*\n- *Nomor Transaksi (NTPD):* `{nomor_ntpd}`\n- *Nomor SKRD/SKPD:* {nomor_skrd}\n- *Objek Layanan:* {nama_layanan}\n- *Nominal Disetor:* *Rp {nominal_rupiah}*\n- *Waktu Bayar:* {waktu_bayar} WITA\n- *Kanal Bayar:* {nama_channel_bank}\n\nSurat Setoran Pajak/Retribusi Daerah Elektronik (e-SSPD / e-SSRD) Anda yang telah dibubuhi *Segel Elektronik Resmi BSrE BSSN* dapat diunduh dan diverifikasi keasliannya melalui tautan resmi berikut:\n\n📥 *Unduh Dokumen Lunas Resmi:*\n🔗 https://verify.baubaukota.go.id/verify/{nomor_ntpd}\n\nPajak dan retribusi Anda berkontribusi nyata bagi pembangunan fasilitas umum dan kemajuan Kota Baubau.",
                'push_title' => 'Pembayaran Rp {nominal_rupiah} Berhasil Disetor!',
                'push_body' => 'NTPD: {nomor_ntpd}. Dokumen resmi e-SSPD ber-Segel Elektronik BSrE siap diunduh. Ketuk untuk unduh PDF.',
                'deep_link_pattern' => '/dokumen/lunas/{nomor_ntpd}',
                'is_active' => true,
            ],

            // =========================================================================
            // KELOMPOK B: NOTIFIKASI PETUGAS LAPANGAN (SURVEYOR, JUKIR, KOLEKTOR, POS RETRIBUSI)
            // =========================================================================
            [
                'code' => 'NOTIF-PTG-001',
                'group' => 'petugas',
                'label' => 'Alert Dispatch Penugasan Survei Lapangan Baru',
                'channels' => ['whatsapp', 'push'],
                'priority' => 'high',
                'wa_template' => "*[M-PAD PETUGAS - DISPOSISI TUGAS SURVEI BARU]*\n\nHalo Petugas {nama_petugas} (ID: {id_petugas}),\n\nAnda menerima penugasan verifikasi lapangan baru dari Admin {nama_opd}:\n\n📋 *ID Permohonan:* #{nomor_berkas}\n👤 *Nama Pemohon:* {nama_pemohon}\n📍 *Alamat Objek:* {alamat_lengkap}\n🌐 *Koordinat GPS Target:* {latitude}, {longitude}\n⏱️ *Batas Waktu Peninjauan (SLA):* {tenggat_waktu} WITA\n\n*INSTRUKSI KERJA LAPANGAN:*\n1. Buka aplikasi Petugas Lapangan:\n   🔗 https://petugasmpad.baubaukota.go.id/tugas/{nomor_berkas}\n2. Kunjungi titik lokasi objek sesuai koordinat.\n3. Buka form digital Lembar Kerja Objek Konfirmasi (LKOK).\n4. Ambil minimal 2 foto fisik objek yang menyertakan geotagging GPS otomatis.\n5. Isi hasil verifikasi ukuran/kapasitas dan submit data sebelum batas waktu habis.",
                'push_title' => 'Tugas Baru: Survei Objek #{nomor_berkas}',
                'push_body' => 'Lokasi: {kelurahan_kecamatan}. Selesaikan sebelum {tenggat_waktu}. Ketuk untuk buka rute navigasi peta.',
                'deep_link_pattern' => '/tugas/{nomor_berkas}',
                'is_active' => true,
            ],
            [
                'code' => 'NOTIF-PTG-002',
                'group' => 'petugas',
                'label' => 'Peringatan Pelanggaran Radius Geofencing (> 30 Meter)',
                'channels' => ['push', 'in_app'],
                'priority' => 'high',
                'wa_template' => null,
                'push_title' => '⚠️ Peringatan: Transaksi di Luar Radius Lokasi!',
                'push_body' => 'Jarak Anda {jarak_meter}m dari titik izin resmi (Maksimal toleransi 30m). Fitur penerimaan transaksi tunai dikunci sementara.',
                'deep_link_pattern' => '/lokasi/validasi',
                'is_active' => true,
            ],
            [
                'code' => 'NOTIF-PTG-003',
                'group' => 'petugas',
                'label' => 'Peringatan Plafon Uang Tunai di Tangan (Cash Float Ceiling Limit)',
                'channels' => ['whatsapp', 'push'],
                'priority' => 'high',
                'wa_template' => "*[M-PAD PETUGAS - PERINGATAN PLAFON KAS]*\n\nPerhatian Petugas {nama_petugas},\n\nTotal uang tunai penerimaan retribusi di tangan Anda saat ini telah mencapai:\n💵 *Rp {total_kas_di_tangan}* dari batas maksimal *Rp 1.000.000*.\n\n⚠️ Sesuai SOP Pengendalian Kasda Perwali No. 11/2025:\nOpsi penerimaan tunai pada aplikasi Anda akan *DIKUNCI OTOMATIS* jika telah mencapai Rp1.000.000.\n\n*TINDAKAN SEGERA:*\n1. Setorkan uang tunai terkumpul ke Bendahara Penerimaan OPD, atau\n2. Lakukan setor tunai langsung ke rekening Kas Daerah (RKUD) melalui gerai AgenBRILink terdekat menggunakan nomor rekening virtual setor kasda Anda: `{nomor_va_setor}`.\n3. Setelah setor berhasil, saldo kas di tangan akan ter-reset menjadi Rp 0 dan aplikasi dapat kembali menerima transaksi tunai.",
                'push_title' => '💵 Plafon Kas Tunai Kritis: Rp {total_kas_di_tangan}',
                'push_body' => 'Batas maksimal Rp1.000.000. Segera setor ke Kasda/AgenBRILink agar opsi tunai tidak terkunci.',
                'deep_link_pattern' => '/setor/panduan',
                'is_active' => true,
            ],
            [
                'code' => 'NOTIF-PTG-004',
                'group' => 'petugas',
                'label' => 'Peringatan Tutup Shift Harian & Setor Kasda',
                'channels' => ['whatsapp', 'push'],
                'priority' => 'medium',
                'wa_template' => "*[M-PAD PETUGAS - PERINGATAN TUTUP SHIFT]*\n\nPetugas {nama_petugas},\n\nWaktu operasional shift Anda telah berakhir untuk hari ini.\n\n📊 *Ringkasan Transaksi Shift:*\n- Transaksi Non-Tunai (QRIS Langsung Kasda): Rp {total_qris} ({jumlah_trx_qris} transaksi)\n- Transaksi Tunai: Rp {total_tunai} ({jumlah_trx_tunai} transaksi)\n\nSegera buka aplikasi Petugas Lapangan dan lakukan prosedur *TUTUP SHIFT*:\n🔗 https://petugasmpad.baubaukota.go.id/shift/close\n\nCetak Lembar Rekonsiliasi Harian melalui printer thermal Bluetooth untuk dilampirkan bersama fisik uang setoran ke Bendahara Penerimaan.",
                'push_title' => 'Waktu Tutup Shift: Total Tunai Rp {total_tunai}',
                'push_body' => 'Akhiri shift sekarang dan cetak bukti rekonsiliasi harian.',
                'deep_link_pattern' => '/shift/close',
                'is_active' => true,
            ],

            // =========================================================================
            // KELOMPOK C: NOTIFIKASI ADMIN OPD & PIMPINAN BAPENDA
            // =========================================================================
            [
                'code' => 'NOTIF-ADM-001',
                'group' => 'admin',
                'label' => 'Peringatan Dini Batas Waktu Verifikasi Berkas (SLA Jam ke-18)',
                'channels' => ['whatsapp', 'in_app'],
                'priority' => 'high',
                'wa_template' => "*[M-PAD MONITOR - PERINGATAN SLA JAM KE-18]*\n\nPerhatian Verifikator {nama_opd},\n\nTerdapat berkas layanan warga yang *BELUM DIVERIFIKASI* dan mendekati batas maksimal SLA pelayanan:\n\n📋 *Nomor Berkas:* #{nomor_berkas}\n👤 *Nama Pemohon:* {nama_pemohon}\n📌 *Layanan:* {nama_layanan}\n⏳ *Waktu Tunggu Berjalan:* 18 Jam 05 Menit\n⏱️ *Sisa Waktu SLA:* *5 Jam 55 Menit lagi*\n\nSegera lakukan verifikasi berkas atau delegasikan penugasan survei sekarang melalui panel admin:\n🔗 https://adminmpad.baubaukota.go.id/verifikasi/{nomor_berkas}\n\nPeringatan otomatis eskalasi ke Kepala Bidang/Pimpinan akan terpicu jika berkas mencapai jam ke-23.",
                'push_title' => 'Peringatan SLA: Berkas 18 Jam',
                'push_body' => 'Berkas {nomor_berkas} mendekati batas toleransi 24 jam.',
                'deep_link_pattern' => '/verifikasi/{nomor_berkas}',
                'is_active' => true,
            ],
            [
                'code' => 'NOTIF-ADM-002',
                'group' => 'admin',
                'label' => 'Eskalasi Kritis Pelanggaran SLA (Jam ke-23)',
                'channels' => ['whatsapp', 'in_app'],
                'priority' => 'high',
                'wa_template' => "*[M-PAD ESKALASI DARURAT - POTENSI PELANGGARAN SLA 24 JAM]*\n\nYth. Pimpinan {nama_opd} / Bapenda Kota Baubau,\n\nSistem mendeteksi berkas pelayanan publik berstatus *KRITIS / TERTUNDA* mendekati batas pelanggaran janji layanan (SLA Breach 24 Jam):\n\n🚨 *Nomor Berkas:* #{nomor_berkas}\n👤 *Pemohon:* {nama_pemohon}\n🏢 *OPD Pengampu:* {nama_opd}\n⏳ *Usia Berkas:* *23 Jam 00 Menit*\n⏱️ *Tersisa:* *60 Menit sebelum Pelanggaran Resmi Tercatat di Pusat Komando Walikota*\n\nMohon atensi pimpinan untuk memerintahkan verifikator menyelesaikan berkas melalui tautan intervensi:\n🔗 https://adminmpad.baubaukota.go.id/eskalasi/{nomor_berkas}",
                'push_title' => 'ESKALASI KRITIS: Pelanggaran SLA < 1 Jam',
                'push_body' => 'Berkas {nomor_berkas} terancam breached dalam 1 jam.',
                'deep_link_pattern' => '/eskalasi/{nomor_berkas}',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $t) {
            NotificationTemplate::updateOrCreate(
                ['code' => $t['code']],
                $t
            );
        }
    }
}
