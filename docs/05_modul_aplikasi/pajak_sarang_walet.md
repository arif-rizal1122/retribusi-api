# Layanan Pajak: Sarang Burung Walet

**Dasar Hukum**: Perda Kota Baubau No. 1 Tahun 2024
**Sistem Pemungutan**: Official Assessment
**Tarif**: 10%
**Rumus**: `(Volume Panen Kg x Harga Pasar Standar) x 10%`

## Matriks Riset Blanko OPD
1. **Warga**: Mendaftarkan lokasi gedung walet (MapPicker GIS) dan estimasi kasat mata.
2. **Petugas Lapangan**: Turun ke lapangan pada saat panen untuk melakukan *surveillance* dan menimbang riil volume panen, di-input ke LKOK di aplikasi petugas.
3. **API**: Menghitung 10% dari nilai jual panen tersebut, mencetak SKPD.
