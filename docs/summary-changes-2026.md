# Penjelasan Perubahan Sistem (Update 2026-03-05)

## 1. Implementasi Dokumen Resmi BAPENDA (Backend)
Kami telah melengkapi sistem generator dokumen resmi untuk memenuhi standar BAPENDA. Total terdapat 11 jenis dokumen baru yang sekarang didukung melalui API:

- **Pendaftaran**: 
  - `SKT` (Surat Keterangan Terdaftar) - [NEW]
- **Pendataan**: 
  - `LKOK` (Lembar Kerja Objek Khusus) - [NEW]
- **Penetapan**:
  - `SKRD` (Surat Ketetapan Retribusi Daerah)
  - `SPPT` (Surat Pemberitahuan Pajak Terhutang)
  - `SKPDKBT` (Surat Ketetapan Pajak Daerah Kurang Bayar Tambahan) - [NEW]
  - `SKPDN` (Surat Ketetapan Pajak Daerah Nihil) - [NEW]
- **Penagihan & Pembayaran**:
  - `SSPD` (Surat Setoran Pajak Daerah)
  - `SSRD` (Surat Setoran Retribusi Daerah) - [NEW]
  - `STRD` (Surat Tagihan Retribusi Daerah) - [NEW]
  - `SPP` (Surat Paksa)
  - `SPMP` (Surat Perintah Melaksanakan Penyitaan) - [NEW]

**Cara Kerja**: 
Admin atau Petugas dapat memanggil endpoint `/api/documents/{type}/{id}` untuk mengunduh PDF dokumen tersebut berdasarkan ID tagihan, objek, atau wajib pajak masing-masing.

## 2. Pembersihan Data Produksi (Cleanup Script)
Kami menambahkan script khusus untuk membersihkan data uji coba yang masuk ke database produksi agar laporan keuangan tetap akurat.

- **File**: `scripts/cleanup_production_final.php`
- **Cara Kerja**: Script ini mendeteksi pola data tes seperti `[TEST]`, NIK `9999...`, alamat email `test@...`, dan nama wajib pajak `uji coba`. Script ini berjalan di level aplikasi (Laravel Tinker) untuk menghindari *rate limiting* API.

## 3. Peningkatan Dashboard Pengawas (Admin UI)
Dashboard untuk peran Pengawas/Supervisor ditingkatkan untuk memberikan visibilitas lebih luas terhadap kinerja lapangan.

- **Peta Petugas**: Menampilkan lokasi *real-time* petugas di lapangan (jika fitur lokasi aktif).
- **Deteksi Anomali**: Algoritma baru untuk mendeteksi potensi kecurangan atau keterlambatan pembayaran secara otomatis.
- **Statistik Kepatuhan**: Grafik visual untuk memantau rasio kepatuhan wajib pajak per wilayah (Zona).

## 4. Evaluasi Skema Pembayaran (Lintas Repo)
Kami melakukan audit mendalam terhadap alur pembayaran di 4 repository:

- **Admin**: Sudah mendukung pencatatan manual (Cash/Transfer) dan TTE (Tanda-Tangan Elektronik).
- **Petugas**: Mendukung validasi tagihan di lapangan melalui scan QR dan input manual.
- **Mobile**: Warga bisa melihat tagihan, membayar lewat VA/QRIS, dan memberikan rating pelayanan.
- **API**: Mengelola sinkronisasi status antara tagihan (Bills) dan bukti bayar (Payments).

---
*Semua perubahan telah di-push ke branch **dev**.*
