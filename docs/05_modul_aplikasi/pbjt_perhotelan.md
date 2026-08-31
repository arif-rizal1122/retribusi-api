# Layanan Pajak: PBJT Jasa Perhotelan

**Dasar Hukum**: Perda Kota Baubau No. 1 Tahun 2024
**Sistem Pemungutan**: Self-Assessment
**Tarif Utama**: 10% dari Dasar Pengenaan Pajak (DPP)

## 1. Skema Perhitungan & Regulasi
Dikenakan atas penyediaan akomodasi seperti hotel, losmen, motel, villa, guest house, dan indekos (dengan jumlah kamar > 10).
- **DPP**: Omzet / jumlah pembayaran yang diterima penyedia jasa perhotelan.
- **Rumus**: `Pajak Terutang = Omzet Kotor x 10%`

## 2. Matriks Riset Blanko OPD (Digitalisasi M-PAD)

### A. Warga / Pemilik Hotel (`retribusi-mobile`)
- **Pendaftaran (SPOPD)**: WP memilih titik kordinat bangunan hotel (MapPicker), menginput **jumlah kamar** (kasat mata), dan foto bangunan.
- **Pelaporan (SPTPD)**: WP melapor omzet bulanan secara mandiri dan mengunggah rekap sistem reservasi.

### B. Admin & Backend API (`retribusi-api` & `retribusi-admin`)
- API menghitung `Omzet x 10%` dan mencetak SKPD digital.
- Jika WP telat melapor, API otomatis menambahkan denda administratif dan bunga keterlambatan 1% per bulan.

### C. Petugas Lapangan (`retribusi-petugas`)
- **Surveillance**: Petugas memeriksa tingkat okupansi kamar (hunian) secara fisik jika dicurigai ada manipulasi laporan omzet, menggunakan form LKOK digital di lapangan.
