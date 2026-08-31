# Layanan Pajak: PBB-P2 (Pajak Bumi dan Bangunan)

**Dasar Hukum**: Perda No. 1/2024 & Perwali 43/2025
**Sistem Pemungutan**: Official Assessment
**Tarif Progresif**: 0.15% - 0.30% (Tergantung NJOP Total)
**NJOPTKP**: Rp 10.000.000

## 1. Skema Perhitungan & Regulasi
PBB-P2 menggunakan tarif progresif berdasarkan nilai aset:
- Lahan Pangan/Ternak: Flat 0.25%.
- NJOP ≤ Rp 1M: 0.15%
- NJOP > Rp 1M - Rp 2M: 0.20%
- NJOP > Rp 2M - Rp 5M: 0.25%
- NJOP > Rp 5M: 0.30%
**Rumus**: `(NJOP Bumi + NJOP Bangunan - NJOPTKP) x Tarif Progresif`

## 2. Matriks Riset Blanko OPD (Cross-Repo Interlock)

### A. Warga (`retribusi-mobile`)
- Warga mengajukan pendaftaran/pemecahan NOP baru (SPOP).
- **Desain Citizen-Centric**: Warga HANYA menggambar poligon tanah di MapPicker, memperkirakan luas bangunan, mengunggah SHM, dan mencentang Pakta Integritas. Warga **tidak tahu dan tidak mengisi** ZNT atau NJOP.

### B. Petugas Lapangan (`retribusi-petugas`)
- Petugas verifikasi mendatangi lokasi.
- **Form Teknis (LSPOP)**: Petugas mengisi spesifikasi teknis (jenis atap, dinding, lantai, daya listrik, keberadaan kolam renang). Sistem API akan mengonversi ini menjadi *Nilai Bangunan*.

### C. Backend API (`retribusi-api`)
- Mengambil titik koordinat/poligon tanah warga, memadukannya dengan layer peta ZNT Bapenda (Spatial Query).
- Mengkalkulasi NJOP Bumi (Luas x ZNT) dan NJOP Bangunan (dari LSPOP Petugas).
- Menerapkan NJOPTKP Rp 10 Juta dan menerbitkan tagihan **SPPT**.
