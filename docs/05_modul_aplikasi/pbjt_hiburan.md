# Layanan Pajak: PBJT Jasa Kesenian dan Hiburan

**Dasar Hukum**: Perda Kota Baubau No. 1 Tahun 2024
**Sistem Pemungutan**: Self-Assessment
**Tarif Utama**: 
- Hiburan Standar (Bioskop, Konser, Bowling, Panti Pijat): **10%**
- Hiburan Khusus (Diskotik, Karaoke, Klub Malam, Spa): **40%**

## 1. Skema Perhitungan & Regulasi
Pajak dikenakan berdasarkan jenis hiburan yang disediakan.
- **Rumus**: `Pajak Terutang = Omzet Kotor x Tarif (10% atau 40%)`

## 2. Matriks Riset Blanko OPD (Digitalisasi M-PAD)

### A. Warga / Pengusaha Hiburan (`retribusi-mobile`)
- **SPOPD**: WP mendaftar lokasi dan jenis hiburan. Sistem akan menyembunyikan penentuan tarif (10% atau 40%) dari WP.
- **SPTPD**: Lapor omzet bulanan, unggah bukti tiket terjual/rekap billing.

### B. Admin & Backend API (`retribusi-api` & `retribusi-admin`)
- Berdasarkan kategori yang diverifikasi Admin, API akan menetapkan pengali (10% atau 40%) pada omzet yang dilaporkan.

### C. Petugas Lapangan (`retribusi-petugas`)
- **Inspeksi Malam (Patroli)**: Petugas Satpol PP/Bapenda mendatangi tempat hiburan, mencatat kapasitas ruangan, jumlah pengunjung *real-time*, dan membandingkannya dengan pelaporan SPTPD di sistem menggunakan form Uji Petik.
