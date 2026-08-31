# Layanan Pajak: PBJT Makanan dan Minuman

**Dasar Hukum**: Perda Kota Baubau No. 1 Tahun 2024
**Sistem Pemungutan**: Self-Assessment
**Tarif Utama**: 10% dari Dasar Pengenaan Pajak (DPP)
**Threshold (PTKP)**: Omzet minimal Rp 3.000.000 / bulan

## 1. Skema Perhitungan & Regulasi
PBJT Makanan dan Minuman dikenakan atas penyediaan makanan/minuman oleh restoran, rumah makan, kafe, dan katering.
- **DPP**: Jumlah pembayaran yang diterima oleh penyedia (Omzet Kotor).
- **Rumus**: `Pajak Terutang = Omzet Kotor x 10%`

## 2. Matriks Riset Blanko OPD (Digitalisasi M-PAD)
Pendekatan *Citizen-Centric Design* diterapkan untuk memisahkan kompleksitas dari Wajib Pajak:

### A. Warga / Pemilik Usaha (`retribusi-mobile`)
- **Registrasi (SPOPD)**: Wajib Pajak mendaftar dengan memilih lokasi restoran di peta (MapPicker GIS) dan mengunggah foto tempat usaha. IKD (Identitas Kependudukan Digital) terisi otomatis.
- **Pelaporan (SPTPD)**: Setiap bulan, WP hanya menginput **Total Omzet Kotor** dan mengunggah rekap kasir/buku kas. WP **tidak perlu** memikirkan tarif 10% atau denda.

### B. Admin & Backend API (`retribusi-api` & `retribusi-admin`)
- API otomatis mengkalkulasi `Omzet x 10%` dan menerbitkan SKPD.
- Admin memvalidasi laporan SPTPD secara digital di dashboard.

### C. Petugas Lapangan (`retribusi-petugas`)
- **Spot Check**: Petugas datang ke restoran membawa aplikasi untuk melakukan uji petik (Tally Counter jumlah pengunjung atau cek *Tapping Box*).
- Jika ada anomali (under-reporting), petugas mengisi form audit lapangan (LSPOP teknis) yang akan otomatis men-trigger Admin menerbitkan **SKPDKB** (Kurang Bayar).
