<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // === WARGA / CITIZEN ===
            [
                'code' => 'NOTIF-WRG-001',
                'group' => 'warga',
                'label' => 'OTP Verifikasi Login',
                'channels' => ['whatsapp'],
                'priority' => 'high',
                'wa_template' => "{Halo|Selamat pagi/siang}! Ini adalah kode OTP M-PAD Kota Baubau Anda: *{otp}*\n\nKode ini bersifat RAHASIA. Jangan berikan kepada siapapun termasuk petugas Bapenda.\nBerlaku selama 5 menit.",
                'push_title' => null,
                'push_body' => null,
                'deep_link_pattern' => null,
                'is_active' => true,
            ],
            [
                'code' => 'NOTIF-WRG-002',
                'group' => 'warga',
                'label' => 'Konfirmasi Berkas Masuk',
                'channels' => ['whatsapp', 'push'],
                'priority' => 'medium',
                'wa_template' => "{Halo|Yth.} {Bapak/Ibu|Wajib Pajak} *{nama_pemohon}*,\n\nPermohonan *{nama_layanan}* Anda telah berhasil didaftarkan di sistem M-PAD Kota Baubau.\n\n📌 Nomor Registrasi: *{nomor_berkas}*\n🏢 OPD Teknis: *{nama_opd}*\n\nBerkas Anda sedang dalam proses verifikasi awal oleh tim pemeriksa. Pantau terus status berkas secara berkala melalui aplikasi M-PAD.\n\n_Bapenda Kota Baubau - Transparan & Akuntabel_",
                'push_title' => 'Berkas Berhasil Didaftarkan',
                'push_body' => 'Permohonan {nomor_berkas} telah masuk tahap verifikasi.',
                'deep_link_pattern' => '/layanan/berkas/{id}',
                'is_active' => true,
            ],
            [
                'code' => 'NOTIF-WRG-003',
                'group' => 'warga',
                'label' => 'Instruksi Perbaikan Berkas',
                'channels' => ['whatsapp', 'push'],
                'priority' => 'high',
                'wa_template' => "⚠️ *PERHATIAN: PERBAIKAN BERKAS M-PAD*\n\n{Halo|Yth.} {Bapak/Ibu} *{nama_pemohon}*,\nBerkas permohonan *{nomor_berkas}* ({nama_layanan}) memerlukan kelengkapan dokumen tambahan:\n\n📝 *Catatan Verifikator:*\n_{catatan_revisi}_\n\nSilakan buka aplikasi M-PAD untuk mengunggah dokumen revisi sebelum batas waktu agar proses penerbitan tidak tertunda.\n\n_Bapenda Kota Baubau_",
                'push_title' => 'Perbaikan Berkas Dibutuhkan',
                'push_body' => 'Catatan revisi untuk {nomor_berkas}: {catatan_revisi}',
                'deep_link_pattern' => '/layanan/berkas/{id}/revisi',
                'is_active' => true,
            ],
            [
                'code' => 'NOTIF-WRG-004',
                'group' => 'warga',
                'label' => 'SKRD Terbit & Kode Billing',
                'channels' => ['whatsapp', 'push'],
                'priority' => 'high',
                'wa_template' => "🔔 *TAGIHAN RETRIBUSI/PAJAK TERBIT*\n\n{Halo|Yth.} {Bapak/Ibu} *{nama_pemohon}*,\nDokumen ketetapan resmi telah terbit dan ditandatangani secara elektronik (TTE):\n\n📄 Nomor Ketetapan: *{nomor_ketetapan}*\n💰 Total Tagihan: *Rp {nominal}*\n💳 Kode Bayar/VA: *{kode_billing}*\n⏰ Jatuh Tempo: *{jatuh_tempo}*\n\nPembayaran dapat dilakukan melalui Bank Sultra, BSI, QRIS, atau kanal perbankan mitra resmi Pemkot Baubau.\n\n_Bapenda Kota Baubau_",
                'push_title' => 'Ketetapan Resmi & Tagihan Terbit',
                'push_body' => 'Ketetapan {nomor_ketetapan} senilai Rp {nominal} telah terbit.',
                'deep_link_pattern' => '/pembayaran/{billing_id}',
                'is_active' => true,
            ],
            [
                'code' => 'NOTIF-WRG-005',
                'group' => 'warga',
                'label' => 'Pengingat Jatuh Tempo',
                'channels' => ['whatsapp', 'push'],
                'priority' => 'low',
                'wa_template' => "⏰ *PENGINGAT JATUH TEMPO M-PAD*\n\n{Halo|Yth.} {Bapak/Ibu} *{nama_pemohon}*,\nTagihan *{nama_objek}* dengan Kode Bayar *{kode_billing}* sebesar *Rp {nominal}* akan jatuh tempo pada *{jatuh_tempo}* ({sisa_hari} hari lagi).\n\nSegera lakukan pembayaran untuk menghindari denda administratif keterlambatan 1% per bulan.\n\n_Bapenda Kota Baubau_",
                'push_title' => 'Pengingat Jatuh Tempo Tagihan',
                'push_body' => 'Tagihan {kode_billing} akan jatuh tempo dalam {sisa_hari} hari.',
                'deep_link_pattern' => '/pembayaran/{billing_id}',
                'is_active' => true,
            ],
            [
                'code' => 'NOTIF-WRG-006',
                'group' => 'warga',
                'label' => 'Konfirmasi Lunas e-SSPD',
                'channels' => ['whatsapp', 'push'],
                'priority' => 'high',
                'wa_template' => "✅ *PEMBAYARAN DITERIMA & LUNAS*\n\n{Terima kasih|Alhamdulillah} {Bapak/Ibu} *{nama_pemohon}*,\nPembayaran retribusi/pajak daerah telah berhasil dikonfirmasi ke Kas Daerah:\n\n🧾 NTPD: *{ntpd}*\n💰 Nominal: *Rp {nominal}*\n🏦 Kanal: *{metode_bayar}*\n🕒 Waktu: *{waktu_bayar} WITA*\n\nBukti setor sah (e-SSPD) ber-QR Code TTE telah diterbitkan dan dapat diunduh di aplikasi M-PAD.\n\n_Bapenda Kota Baubau_",
                'push_title' => 'Pembayaran Berhasil Dikonfirmasi',
                'push_body' => 'Tagihan {kode_billing} telah lunas (NTPD: {ntpd}).',
                'deep_link_pattern' => '/riwayat/{payment_id}',
                'is_active' => true,
            ],

            // === PETUGAS LAPANGAN ===
            [
                'code' => 'NOTIF-PTG-001',
                'group' => 'petugas',
                'label' => 'Dispatch Tugas Survei',
                'channels' => ['whatsapp', 'push'],
                'priority' => 'high',
                'wa_template' => "📋 *PENUGASAN SURVEI LAPANGAN BARU*\n\nHalo Rekan Petugas *{nama_petugas}*,\nAnda menerima tugas verifikasi lapangan dari Koordinator/Kasubid:\n\n📍 Objek: *{nama_objek}*\n🏢 Alamat/Zona: *{lokasi_survei}*\n⏱️ Batas SLA: *{batas_sla_wita} WITA*\n\nBuka aplikasi Petugas M-PAD untuk navigasi lokasi, checklist blanko, dan unggah foto geotagging.",
                'push_title' => 'Penugasan Survei Lapangan Baru',
                'push_body' => 'Tugas verifikasi lapangan: {nama_objek} di {lokasi_survei}.',
                'deep_link_pattern' => '/penugasan/{task_id}',
                'is_active' => true,
            ],
            [
                'code' => 'NOTIF-PTG-002',
                'group' => 'petugas',
                'label' => 'Pelanggaran Geofence',
                'channels' => ['push'],
                'priority' => 'high',
                'wa_template' => null,
                'push_title' => 'Peringatan Radius Lokasi (Geofence)',
                'push_body' => 'Posisi Anda berjarak {jarak_meter}m di luar radius valid objek {nama_objek}. Pastikan mendekat ke titik lokasi.',
                'deep_link_pattern' => '/lokasi',
                'is_active' => true,
            ],
            [
                'code' => 'NOTIF-PTG-003',
                'group' => 'petugas',
                'label' => 'Plafon Kas Tunai',
                'channels' => ['whatsapp', 'push'],
                'priority' => 'high',
                'wa_template' => "⚠️ *PERINGATAN PLAFON KAS TUNAI*\n\nPetugas *{nama_petugas}*,\nAkumulasi penerimaan tunai harian Anda telah mencapai *Rp {total_kas_tunai}* (Batas: Rp {plafon}).\n\nSesuai SOP Keuangan Daerah, segera lakukan penyetoran titipan kas ke Bendahara Penerimaan / Kasda untuk membuka kembali limit transaksi tunai.",
                'push_title' => 'Plafon Kas Tunai Mendekati Batas',
                'push_body' => 'Kas tunai terkumpul Rp {total_kas_tunai}. Segera setorkan ke Kasda.',
                'deep_link_pattern' => '/setor-sts',
                'is_active' => true,
            ],
            [
                'code' => 'NOTIF-PTG-004',
                'group' => 'petugas',
                'label' => 'Tutup Shift Harian',
                'channels' => ['whatsapp', 'push'],
                'priority' => 'medium',
                'wa_template' => "🏁 *REMINDER TUTUP SHIFT HARIAN*\n\nHalo *{nama_petugas}*,\nJam operasional shift pemungutan telah berakhir. Pastikan Anda telah melakukan:\n1. Cetak / generate Rekap Harian Transaksi\n2. Rekonsiliasi fisik karcis vs nominal kas\n3. Pengajuan Draft STS ke Bendahara\n\nTerima kasih atas dedikasi Anda.",
                'push_title' => 'Pengingat Tutup Shift & Rekap STS',
                'push_body' => 'Selesaikan rekapitulasi dan pengajuan STS harian Anda.',
                'deep_link_pattern' => '/shift-rekap',
                'is_active' => true,
            ],

            // === ADMIN & PEJABAT STRATEGIS ===
            [
                'code' => 'NOTIF-ADM-001',
                'group' => 'admin',
                'label' => 'SLA Warning Jam ke-18',
                'channels' => ['whatsapp', 'in_app'],
                'priority' => 'high',
                'wa_template' => "⏳ *PERINGATAN AMBANG SLA (18 JAM)*\n\nYth. Kasubid/Verifikator *{nama_verifikator}*,\nBerkas permohonan berikut telah memasuki jam ke-18 tanpa tindak lanjut (Toleransi SLA: 24 jam):\n\n📌 Nomor Berkas: *{nomor_berkas}*\n👤 Pemohon: *{nama_pemohon}*\n📑 Layanan: *{nama_layanan}*\n\nMohon segera lakukan peninjauan atau disposisi survei untuk mencegah pelanggaran SLA pelayanan publik.",
                'push_title' => 'Peringatan SLA: Berkas 18 Jam',
                'push_body' => 'Berkas {nomor_berkas} mendekati batas toleransi 24 jam.',
                'deep_link_pattern' => '/verifikasi/{app_id}',
                'is_active' => true,
            ],
            [
                'code' => 'NOTIF-ADM-002',
                'group' => 'admin',
                'label' => 'SLA Critical Jam ke-23',
                'channels' => ['whatsapp', 'in_app'],
                'priority' => 'high',
                'wa_template' => "🚨 *ESKALASI KRITIS SLA (23 JAM) - PEMKOT BAUBAU*\n\nYth. Kabid Pendataan/Penagihan,\nBerkas permohonan *{nomor_berkas}* ({nama_layanan} - {nama_pemohon}) telah melewati 23 jam tanpa penyelesaian.\n\n⚠️ Sisa waktu toleransi: < 1 jam sebelum berstatus *BREACHED* pada Dashboard Eksekutif Walikota.\nHarap segera lakukan intervensi struktural.",
                'push_title' => 'ESKALASI KRITIS: Pelanggaran SLA < 1 Jam',
                'push_body' => 'Berkas {nomor_berkas} terancam breached dalam 1 jam.',
                'deep_link_pattern' => '/eskalasi/{app_id}',
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
