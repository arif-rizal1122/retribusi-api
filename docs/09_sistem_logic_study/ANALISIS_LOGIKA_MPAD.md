# Analisis Logika Inti Sistem M-PAD
> Panduan teknis untuk memahami mekanisme operasional hulu-ke-hilir sistem perpajakan modern Kota Baubau.
> Tanggal Studi: 2026-04-12

## 1. Mekanisme Invoice & Billing (Tagihan)
Sistem M-PAD menggunakan pendekatan hibrida untuk memastikan optimalisasi PAD tanpa celah.

### A. Virtual Billing (Otomatis)
Sistem secara cerdas menghitung potensi tagihan tanpa harus menunggu input manual dari petugas:
- **Metode**: Melakukan komparasi antara tanggal pendaftaran Objek Pajak dengan tabel `payments`.
- **Logika**: Jika periode tertentu belum memiliki record pembayaran sukses, sistem menganggapnya sebagai tunggakan virtual.
- **Dinamis**: Nilai tagihan dihitung secara *real-time* berdasarkan siklus (Harian/Mingguan/Bulanan/Tahunan) yang diatur di Master Data.

### B. Input Manual Invoice
Admin memiliki fleksibilitas untuk penyesuaian data:
- **Fitur**: Administrator (Level OPD/Super Admin) dapat menambahkan tagihan secara manual melalui fitur `store` pada modul Bill.
- **Kebutuhan**: Biasanya digunakan untuk penetapan hasil audit (SKPDKB) atau penyesuaian khusus yang tidak tercakup dalam siklus rutin.

> [!IMPORTANT]
> Setiap tagihan resmi (SKRD) dapat divalidasi dengan **Tanda Tangan Elektronik (TTE)** oleh pejabat berwenang sebelum diterbitkan ke Wajib Pajak.

---

## 2. Mekanisme Penugasan Petugas (/tasks)
Sistem kontrol lapangan terintegrasi untuk memantau produktivitas petugas secara objektif.

- **Pihak Penginput**: Admin OPD atau Pengawas Lapangan melalui Dashboard Admin.
- **Parameter Penugasan**:
  - **Petugas**: User spesifik yang dituju.
  - **Target**: Objek Pajak atau Wajib Pajak tertentu.
  - **Wilayah**: Zona/Kecamatan/Kelurahan tertentu.
  - **Tenggat**: Tanggal jatuh tempo penugasan (`due_date`).
- **Alur Kerja di Aplikasi Petugas**:
  1. Petugas menerima daftar tugas di menu **Tasks**.
  2. Petugas melakukan kunjungan lapangan.
  3. Petugas mengunggah **Foto Bukti** dan mencatat hasil kunjungan.
  4. Sistem mencatat **Koordinat GPS** saat tugas diselesaikan untuk verifikasi lokasi petugas.

---

## 3. Skema Perhitungan Denda (Penalty)
Berdasarkan **Perwali No. 58/2024** dan **Perda No. 1/2024**, denda dikunci pada level sistem di `FormulaParserService`.

| Jenis Tagihan | Persentase Denda | Deskripsi |
| :--- | :--- | :--- |
| **STPD** | 1% / Bulan | Denda keterlambatan pembayaran rutin (Pajak Terhutang). |
| **SKPDKB** | 1.8% / Bulan | Denda hasil pemeriksaan (pemeriksaan umum). |
| **Jabatan** | 2.2% / Bulan | Penetapan secara paksa karena WP tidak melapor/pembukuan. |
| **Angsuran** | 0.6% / Bulan | Bunga untuk WP yang melakukan permohonan cicilan. |
| **Flat Fine** | Rp 100.000 | Sanksi administrasi jika tidak menyampaikan laporan SPTPD. |

> [!TIP]
> Perhitungan denda maksimal dibatasi hingga **24 bulan**. Nilai denda akan otomatis terakumulasi dalam Total Tagihan yang muncul pada QR-Pay.

---

## 4. Algoritma Deteksi Anomali (Surveillance)
Pusat intelijen pengawasan untuk membantu auditor mengidentifikasi potensi kebocoran pajak secara dini.

1. **Anomali Tunggakan (Delinquency)**:
   - Menandai Objek Pajak yang memiliki tunggakan di atas **3 bulan/periode** berturut-turut.
2. **Penyimpangan Omzet (Revenue Mismatch)**:
   - Komparasi antara laporan mandiri (Self-Assessment) dengan estimasi potensi sistem.
   - Ambang batas (*Threshold*): **>20% selisih** akan memicu tanda merah pada dashboard pengawas.
3. **Absensi Pembayaran (Non-Compliance)**:
   - Identifikasi objek aktif yang sama sekali tidak melakukan pembayaran dalam **30 hari terakhir**.
4. **Productivity Variance**:
   - Memantau rute dan koordinat petugas lapangan. Jika tugas dilaporkan selesai tetapi GPS petugas tidak berada di lokasi objek pajak, sistem akan mencatatnya sebagai anomali aktivitas.

---
## 5. Rencana Pengembangan: Tabel Transparansi Objek & WP
Pusat kendali data master untuk memvalidasi pemetaan subjek (WP) dan objek (Aset Pajak) secara massal.

- **Tujuan**: Memudahkan Admin/OPD dalam melakukan audit data dan memastikan tidak ada objek pajak yang "yatim" (tidak memiliki pemilik terdaftar).
- **Struktur Kolom Utama**:
  - **Identitas Objek**: NOP, Nama Objek, dan Alamat Lokasi.
  - **Identitas WP**: Nama Wajib Pajak (Pemilik), NPWPD, dan No. Telepon.
  - **Kategorisasi**: Jenis Retribusi, Klasifikasi, dan Zona Wilayah.
  - **Status Terakhir**: Status Pembayaran Terakhir (Lunas/Menunggak) dan Tanggal Verifikasi.
- **Fitur Interaktif**:
  - **Global Search**: Mencari berdasarkan NOP atau Nama WP secara instan.
  - **Quick Action**: Tombol cepat untuk "Buat Tugas" atau "Lihat Riwayat Tagihan" langsung dari baris tabel.
  - **Export Data**: Kemampuan mengunduh daftar WP per kategori untuk keperluan laporan fisik ke pimpinan.

### Referensi Kode Kunci (Developer Only):
- **Logika Billing**: `app/Services/BillingService.php`
- **Rumus Denda**: `app/Services/FormulaParserService.php`
- **Algoritma Anomali**: `app/Http/Controllers/Pengawas/SurveillanceController.php`
- **Mekanisme Task**: `app/Http/Controllers/PetugasTaskController.php`
