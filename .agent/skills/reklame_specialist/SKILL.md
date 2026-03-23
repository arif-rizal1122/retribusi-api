---
name: Advertisement Tax Specialist (Pajak Reklame)
description: Panduan mendalam mengenai perhitungan Pajak Reklame Kota Baubau berdasarkan Perwali 11/2025 dan Perda 1/2024.
---

# Panduan Perhitungan Pajak Reklame Kota Baubau

Skill ini memberikan panduan teknis bagi AI untuk memahami dan menghitung Pajak Reklame sesuai regulasi terbaru.

## 1. Komponen Utama Perhitungan
Dasar Pengenaan Pajak (DPP) adalah **Nilai Sewa Reklame (NSR)**.
`NSR = NJOPR (Nilai Fisik) + NSPR (Nilai Lokasi/Strategis)`

### NJOPR (Nilai Jual Objek Pajak Reklame)
- Dihitung berdasarkan luas (m2) x tarif jenis reklame.
- **Aturan Pembulatan**: Jika luas < 1 m2, wajib dibulatkan ke atas menjadi 1 m2.
- **Satuan Waktu**: 
    - Reklame Permanen: Per Tahun.
    - Reklame Insidentil: Per Bulan.

### NSPR (Nilai Strategis Penyelenggaraan Reklame)
- Didasarkan pada:
    - Kelas Jalan (Zonasi A, B, C).
    - Sudut Pandang (Jumlah Sisi).
    - Ukuran Luas.

## 2. Faktor Pengali Khusus (Multipliers)
Beberapa kategori memiliki faktor pengali terhadap NSR sebelum dikalikan tarif pajak:
- **Produk Rokok / Minuman Beralkohol**: Pengali **1.1 (110%)**.
- **Lokasi Indoor / Mall**: Pengali **0.5 (50%)**.
- **Umum**: Pengali **1.0**.

## 3. Rumus Utama
`Pajak Terutang = Nilai Sewa Reklame (NSR) x 25%`

**Rumus Teknis (System Logic):**
`Total = ((NJOPR + NSPR) * LuasRounded * Sisi) * FaktorPengali * 25% * Unit * Durasi`

## 4. Ketentuan Waktu
- **Permanen**: Videotron, Billboard, Papan (Satuan: Tahun).
- **Insidentil**: Spanduk, Baliho (Satuan: Bulan).
- **Brosur**: Satuan: Minggu (per 1,000 lembar atau per lembar).
- **Kendaraan**: Satuan: Buah / Tahun.
- **Suara**: Satuan: Hari.
- **Film**: Satuan: Menit / Bulan.

## 5. Metadata Mapping (System Key)
- `sifat_pemasangan`: [permanen, insidentil]
- `jenis_reklame`: [videotron, billboard, papan, spanduk, selebaran, kendaraan, suara, film]
- `produk_khusus`: [umum, rokok_alkohol, mall_indoor]
- `kelas_jalan`: [kelas_a, kelas_b, kelas_c]
- `njopr_satuan`: Input manual per m2.
- `nspr_satuan`: Input manual berdasarkan zonasi.
