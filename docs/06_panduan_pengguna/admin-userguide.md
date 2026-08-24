# 📄 Panduan Pengguna Lengkap (Admin) - M-PAD Kota Baubau
## Versi Terintegrasi (Update April 2026)

Selamat datang di Panduan Pengguna **M-PAD (Mitra PAD)**. Dashboard ini adalah pusat kendali untuk manajemen pendapatan daerah, mencakup PBJT, PBB, dan Retribusi Daerah.

---

## 🟢 1. Dashboard & Monitoring (Command Center)
Dashboard utama memberikan pandangan 360 derajat terhadap kesehatan keuangan daerah.

- **Metric Cards**: Pantau Realisasi vs Target secara real-time.
- **Live Maps**: Marker hijau menunjukkan WP patuh, merah menunjukkan tunggakan. Marker biru berdenyut menunjukkan posisi petugas lapangan saat ini.
- **Heatmap**: Identifikasi wilayah dengan potensi pendapatan tertinggi.

---

## 🔵 2. Manajemen Wajib Pajak & Objek Pajak
Admin bertanggung jawab atas validitas data master WP.

1.  **Verifikasi Pendaftaran**: Buka menu **Verifikasi** untuk meninjau data yang diinput petugas atau WP mandiri. Periksa foto lokasi dan koordinat map sebelum menyetujui.
2.  **Klasifikasi**: Pastikan setiap Objek Pajak (Tax Object) memiliki klasifikasi yang benar untuk menentukan rumus perhitungan otomatis.
3.  **Audit Visual**: Gunakan modul **Billboard Audit** untuk memverifikasi fisik reklame vs data administratif.

---

## 🟡 3. Billing, TTE & E-Registry
Transformasi digital dokumen resmi melalui Tanda Tangan Elektronik (TTE).

1.  **Penetapan (Generate Bill)**: Sistem menghitung otomatis berdasarkan `Formula Parser`. Admin cukup meninjau nominal.
2.  **Tanda Tangan Elektronik (TTE)**:
    - Pilih dokumen (SKPD/SKRD) yang siap ditandatangani.
    - Klik **Sign TTE** (BSrE Integrated).
    - Dokumen akan memiliki QR-Code unik yang terhubung ke sistem **E-Registry** (`verify.baubaukota.go.id`).
3.  **Distribusi**: Dokumen digital otomatis tersedia di aplikasi Mobile Wajib Pajak setelah ditandatangani.

---

## 🔴 4. Modul Penegakan & Amnesty (Baru)

### A. Penghapusan Denda (Penalty Waiver / Amnesty)
Fitur untuk mendukung kebijakan relaksasi pajak.
- **Permohonan**: Lihat daftar permohonan keringanan denda dari WP.
- **Persetujuan**: Admin dapat memberikan potongan denda (persentase atau nominal tetap) berdasarkan pertimbangan pimpinan.
- **Otomasi**: Setelah disetujui, tagihan WP akan terupdate otomatis dengan nilai denda yang baru.

### B. Penindakan (Enforcement Notice)
Langkah tegas untuk WP yang mengabaikan kewajiban.
- **Surat Teguran I & II**: Diterbitkan otomatis oleh sistem jika melewati jatuh tempo.
- **Surat Paksa (SPMP)**: Admin dapat menugaskan petugas lapangan untuk mengeksekusi penempelan stiker/segel dan mengunggah bukti foto koordinat.
- **Monitoring**: Pantau status penindakan dari 'Pending' hingga 'Resolved' (WP Melunasi).

---

## 🏛️ 5. Integrasi PBB-P2
Manajemen khusus untuk Pajak Bumi dan Bangunan.
- **Inkuiri Global**: Cari NOP untuk melihat riwayat pembayaran dan tunggakan PBB.
- **Rekonsiliasi**: Sinkronisasi data pembayaran antara bank persepsi dan sistem Bapenda.
- **E-SPPT Management**: Memastikan ketersediaan dokumen SPPT digital untuk diunduh warga.

---
*Tips Keamanan: Gunakan fitur Audit Log untuk melacak siapa yang melakukan penetapan atau perubahan status tagihan sensitif.*
