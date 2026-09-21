---
name: Advertisement Tax Specialist (Pajak Reklame)
description: Panduan mendalam mengenai perhitungan Pajak Reklame Kota Baubau berdasarkan Perwali 11/2025 dan Perda 1/2024.
---

# Panduan Perhitungan Pajak Reklame Kota Baubau

Skill ini memberikan panduan teknis bagi AI untuk memahami dan menghitung Pajak Reklame sesuai regulasi terbaru.

## 1. Komponen Utama Perhitungan
- **ID Master Classification**: **188** (Pajak Reklame).
- **Dasar Pengenaan Pajak (DPP)**: **Nilai Sewa Reklame (NSR)**.
- **Formula Utama**: `((njopr + nspr) * luas * sisi) * 0.25`

### NJOPR (Nilai Jual Objek Pajak Reklame)
- Dihitung berdasarkan luas (m2) x tarif jenis reklame.
- **Aturan Pembulatan**: Jika luas < 1 m2, wajib dibulatkan ke atas menjadi 1 m2.
- **Satuan Waktu**: 
    - Reklame Permanen: Per Tahun.
    - Reklame Insidentil: Per Bulan.

### NSPR (Nilai Strategis Penyelenggaraan Reklame)
- Didasarkan pada:
    - **Zonasi Kelas Jalan**:
        - **Kelas Jalan A (Sangat Strategis)**: Jl. RA. Kartini, Jl. Yos Sudarso, Jl. Jend. Sudirman, Pantai Kamali, Kotamara.
        - **Kelas Jalan B (Strategis)**: Jl. Teuku Umar, Jl. RE Martadinata, Jl. Gatot Subroto.
        - **Kelas Jalan C (Standar)**: Jl. Imam Bonjol, Jl. Seram, dll.
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
- `kelas_jalan`: [`RD-CL-A`, `RD-CL-B`, `RD-CL-C`]
- `njop_reklame_id`: 188

## 6. Standarisasi Formulir Warga (Citizen-Centric Standard)

### A. Field Input Kasat Mata & Otomasi
1. `kategori_reklame`: Dropdown (Videotron, Billboard, Neon Box, Baliho, Spanduk, Reklame Kendaraan).
2. `judul_reklame`: Teks tema/naskah utama iklan.
3. `panjang` & `lebar`: Dimensi media (Meter).
4. `sisi`: Dropdown pilihan muka/sisi (1 Sisi, 2 Sisi Bolak-Balik, 3 Sisi Segitiga).
5. `luas`: **Read-Only** otomatis terhitung (`Panjang × Lebar × Sisi`).
6. `masa_tayang_mulai` & `masa_tayang_selesai`:
   - Tombol **Quick Preset**: 1 Bulan, 3 Bulan, 6 Bulan, 1 Tahun.
   - `masa_tayang_selesai` otomatis terkunci **Read-Only** saat preset aktif, dengan tombol **"Ubah manual"** jika membutuhkan tanggal spesifik.

### B. Berkas Persyaratan Terpangkas
1. `materi_reklame`: Desain / Materi Reklame (`required: true`).
2. `foto_titik`: Foto Lokasi / Lingkungan Tempat Pasang Reklame (`required: true`, helper: *"Foto sudut pandang dari arah jalan tempat reklame akan dipasang"*).
*(KTP otomatis menggunakan profil IKD/akun warga terverifikasi)*.

### C. Pakta Integritas & Yuridis
- Checkbox persetujuan wajib bernilai boolean eksplisit (`true`/`false`).
- Sistem mencatat `legal_consent_at`, `legal_consent_ip`, dan `legal_consent_user_agent` di backend.

