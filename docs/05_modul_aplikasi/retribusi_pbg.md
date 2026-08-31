# Layanan Retribusi: Persetujuan Bangunan Gedung (PBG)

**Dasar Hukum**: Perda No. 1/2024 & Perwali 63/2024
**Kategori**: Retribusi Perizinan Tertentu
**Pengelola**: DPMPTSP & Dinas PUPR

## 1. Skema Perhitungan & Regulasi
- **Rumus**: `Luas Bangunan (m2) x Indeks Lokalitas x Indeks Terintegrasi x SHST (Standar Harga Satuan Tertinggi)`
- **Pembebasan 100%**: Berlaku untuk Masyarakat Berpenghasilan Rendah (MBR).

## 2. Matriks Riset Blanko OPD

### A. Warga (`retribusi-mobile`)
- Warga mengajukan IMB/PBG dengan menginput **Luas Bangunan Kasat Mata** dan mengunggah file desain (Siteplan/Arsitektur) serta surat tanah.
- Warga mencentang "Pengajuan MBR" jika memenuhi syarat (melampirkan Surat Keterangan Tidak Mampu).

### B. Petugas Teknis PUPR (`retribusi-petugas`)
- Tim teknis turun ke lapangan membawa LKOK (Lembar Kerja Objek Khusus) digital.
- Mereka meninjau kondisi lapangan, lalu sistem/petugas menentukan **Indeks Terintegrasi** dan **Indeks Lokalitas** (Field Teknis, disembunyikan dari warga).

### C. Backend API & Admin Bapenda
- API mengkalkulasi keseluruhan indeks dan menerbitkan SKRD PBG.
- Setelah Warga membayar (QRIS/VA), sistem memberi lampu hijau kepada DPMPTSP untuk menerbitkan izin PBG definitif.
