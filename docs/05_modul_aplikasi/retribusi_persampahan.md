# Layanan Retribusi: Pelayanan Persampahan/Kebersihan

**Dasar Hukum**: Perda Kota Baubau No. 1 Tahun 2024
**Kategori**: Retribusi Jasa Umum
**Pengelola**: Dinas Lingkungan Hidup (DLH)

## 1. Skema Perhitungan & Regulasi
Tarif ditetapkan secara flat per bulan berdasarkan klasifikasi golongan sumber sampah (Rumah Tangga, Niaga, Industri, dll).

## 2. Matriks Riset Blanko OPD

### A. Warga (`retribusi-mobile`)
- Warga mendaftar sebagai pelanggan kebersihan.
- **Form Minimalis**: Hanya mengisi titik lokasi rumah/toko (MapPicker GIS) dan nama lengkap. 
- Tarif golongan disembunyikan dan akan ditentukan oleh petugas DLH.

### B. Petugas DLH (`retribusi-petugas`)
- Petugas verifikasi mendatangi lokasi.
- Menentukan klasifikasi sumber sampah (contoh: apakah ini rumah tangga kecil atau ruko niaga besar).

### C. Admin & API (`retribusi-api`)
- API menerbitkan tagihan bulanan (SKRD) secara massal.
- Warga bisa membayar via aplikasi E-Retribusi M-PAD (QRIS/VA) tanpa harus didatangi petugas penagih ke rumah.
