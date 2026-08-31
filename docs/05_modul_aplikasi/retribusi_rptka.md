# Layanan Retribusi: Pengesahan RPTKA TKA

**Dasar Hukum**: Perda Kota Baubau No. 1 Tahun 2024
**Kategori**: Retribusi Perizinan Tertentu
**Pengelola**: Dinas Tenaga Kerja / DPMPTSP

## 1. Skema Perhitungan & Regulasi
Dikenakan atas pengesahan Rencana Penggunaan Tenaga Kerja Asing (TKA) perpanjangan. Tarif kompensasi dihitung per bulan/per TKA dalam US Dollar, lalu dikonversi ke Rupiah menggunakan kurs Bank Indonesia saat hari pembayaran.

## 2. Matriks Riset Blanko OPD
1. **Warga/Perusahaan (`retribusi-mobile`)**: Perusahaan menginput jumlah TKA dan durasi kerja. Mengunggah paspor/dokumen pendukung.
2. **Admin**: Verifikasi berkas.
3. **API (`retribusi-api`)**: Otomatis menarik data Kurs BI *real-time* via API Eksternal untuk mengonversi tarif dasar ke Rupiah, lalu menerbitkan tagihan SKRD.
