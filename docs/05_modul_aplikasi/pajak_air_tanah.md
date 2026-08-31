# Layanan Pajak: Pajak Air Tanah (PAT)

**Dasar Hukum**: Perda Kota Baubau No. 1 Tahun 2024
**Sistem Pemungutan**: Official Assessment
**Tarif**: 20%
**Rumus**: `(Volume Ekstraksi m3 x Harga Dasar Air) x 20%`

## Matriks Riset Blanko OPD
1. **Warga**: Warga (Pemilik sumur bor/industri) mendaftarkan titik sumur (MapPicker) dan mengunggah foto water meter (SIP).
2. **Petugas**: Petugas datang mencatat angka meteran riil di aplikasi `retribusi-petugas` (LKOK).
3. **API**: Menghitung tarif progresif/harga dasar air berdasarkan volume ekstraksi (m3), lalu menerbitkan tagihan SKPD.
