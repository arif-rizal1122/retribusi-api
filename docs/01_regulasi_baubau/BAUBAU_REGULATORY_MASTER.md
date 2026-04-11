# 🏛️ BAUBAU REGULATORY MASTER (Source of Truth)

Dokumen ini adalah acuan tunggal untuk seluruh logika bisnis sistem **MITRA (Manajemen Informasi Terpadu Retribusi dan Aset Daerah)** sesuai dengan **Perda 1/2024** dan **Perwali Baubau Nomor 8 Tahun 2025**.

---

## 1. Jenis Pajak Daerah & Rumus Perhitungannya

### A. Portofolio Wilayah I (Aset & Properti)

#### 1. Pajak Bumi dan Bangunan (PBB-P2)
Dikenakan atas kepemilikan atau pemanfaatan tanah dan bangunan.
*   **Rumus:** `(NJOP - NJOPTKP) x Tarif`
*   **NJOPTKP:** Ditetapkan sebesar **Rp10.000.000**.
*   **Tarif:** Maksimal **0,3%**, khusus lahan produksi pangan/ternak **0,25%**.

#### 2. Bea Perolehan Hak atas Tanah dan Bangunan (BPHTB)
*   **Rumus:** `(NPOP - NPOPTKP) x Tarif 5%`
*   **NPOPTKP:** 
    *   Perolehan Pertama: **Rp80.000.000**.
    *   Waris/Hibah Washiat (Keluarga Sedarah): **Rp300.000.000**.

#### 3. Pajak Reklame
*   **Rumus:** `Nilai Sewa Reklame (NSR) x Tarif 25%`.
*   **Parameter:** NSR dipengaruhi oleh dimensi fisik dan **Zonasi Kelas Jalan**.

#### 4. Pajak MBLB (Mineral Bukan Logam dan Batuan)
*   **Rumus:** `(Volume x Harga Patokan) x Tarif 15%`.

#### 5. Pajak Sarang Burung Walet
*   **Rumus:** `(Volume Panen x Harga Pasar) x Tarif 10%`.

#### 6. Opsen Pajak (Tambahan Pungutan)
*   **Opsen PKB & Opsen BBNKB:** **66%** dari pokok pajak provinsi.

### B. Portofolio Wilayah II (Konsumsi & Self-Assessment)

#### 1. Pajak Barang dan Jasa Tertentu (PBJT)
Berdasarkan total nilai pembayaran (omzet).
*   **Makanan/Minuman (Restoran):** Omzet x **10%**.
*   **Jasa Perhotelan:** Omzet x **10%**.
*   **Jasa Parkir:** Omzet x **10%**.
*   **Jasa Kesenian & Hiburan:** 
    *   Umum: **10%**.
    *   Khusus (Diskotik, Klub Malam, Karaoke): **40%**.
*   **Tenaga Listrik:** 
    *   Konsumsi Umum: **10%**.
    *   Industri/Migas: **3%**.
    *   Dihasilkan Sendiri: **1,5%**.

#### 2. Pajak Air Tanah (PAT)
*   **Rumus:** `(Volume x Harga Dasar Air/HDA) x Tarif 20%`.

#### 3. Retribusi (Sewa Lapak & Jasa Umum)
*   **Sewa Lapak**: Menggunakan sistem Koefisien (1.3 - 1.0) dikalikan tarif dasar.

---

## 2. Struktur Zonasi & Tarif Spasial (Rules Engine)

Sistem **MITRA** menggunakan alur hirarki: **Jenis Retribusi → Klasifikasi → Zona → Tarif**.

### A. Pajak Reklame (Zonasi Kelas Jalan)
Menentukan besaran NSPR (Nilai Strategis Penyelenggaraan Reklame).
*   **Kelas Jalan A (Sangat Strategis):** Jl. RA. Kartini, Jl. Yos Sudarso, Jl. Jend. Sudirman, Pantai Kamali, Kotamara.
*   **Kelas Jalan B (Strategis):** Jl. Teuku Umar, Jl. RE Martadinata, Jl. Gatot Subroto.
*   **Kelas Jalan C (Standar):** Jl. Imam Bonjol, Jl. Seram, dll.

### B. PBB-P2 (Zona Nilai Tanah / ZNT)
*   Setiap wilayah desa/kelurahan dipetakan dalam blok ZNT.
*   **NIR (Nilai Indikasi Rata-rata):** Mencerminkan harga pasar tanah di zona tersebut.

### C. Retribusi Sewa Lapak (Sistem Koefisien)
*   **Zona Premium (1.3):** Kawasan wisata utama (Pantai Kamali/Kotamara).
*   **Zona Strategis (1.2):** Pusat kota/perdagangan (Pelabuhan Murhum).
*   **Zona Ekonomi (1.1):** Kawasan pasar (Wameo/Pujasera).
*   **Zona Umum (1.0):** Kawasan permukiman.

### D. Retribusi Parkir Tepi Jalan Umum
*   **Zona Premium**: Mobil Rp 3.000, Motor Rp 2.000.
*   **Zona Strategis**: Mobil Rp 2.000, Motor Rp 1.500.
*   **Zona Ekonomi**: Mobil Rp 1.500, Motor Rp 1.000.
*   **Zona Umum**: Mobil Rp 1.000, Motor Rp 1.000.

---

## 3. Siklus Dokumen & Administrasi

### A. Tahap Pendaftaran dan Pendataan
*   **SPOPD:** Pendaftaran usaha PBJT, Reklame, PAT, MBLB, Walet.
*   **SPOP & LSPOP:** Pendaftaran khusus PBB-P2.
*   **SKT:** Bukti legalitas pendaftaran WP.
*   **LKOK:** Kertas kerja pemutakhiran data lapangan.

### B. Tahap Pelaporan dan Penetapan
*   **SPTPD:** Laporan mandiri (Self Assessment) omzet bulanan.
*   **SKPD / SKRD:** Tagihan/ketetapan resmi dari pemerintah daerah.
*   **SPPT:** Ketetapan tagihan PBB-P2.
*   **SKPDKB / SKPDKBT:** Ketetapan Kurang Bayar (setelah pemeriksaan).
*   **SKPDLB / SKPDN:** Ketetapan Lebih Bayar atau Nihil.

### C. Tahap Pembayaran dan Penagihan
*   **SSPD / SSRD:** Bukti legal penyetoran pajak/retribusi.
*   **STPD:** Tagihan sanksi administrasi/denda bunga.
*   **Surat Teguran (1, 2, 3):** Peringatan keterlambatan pembayaran.
*   **SPMP & Surat Paksa:** Penindakan akhir (Penyitaan/Penyegelan).

---

*Dokumen ini merupakan hasil harmonisasi Perda 1/2024 dan Perwali 8/2025. Perbarui setiap terjadi perubahan aturan zonasi atau tarif baru.*

---

## 4. Lampiran: Pemetaan ID Teknis (Database Source of Truth)

Untuk keperluan integrasi sistem **MITRA**, berikut adalah pemetaan ID klasifikasi aktif pada tabel `retribution_classifications`:

| Nama Klasifikasi | ID Database | Kode Unik | Rumus Aktif |
| :--- | :--- | :--- | :--- |
| **PBB-P2** | 186 | `PBB-P2` | `(njop - 10000000) * 0.003` |
| **BPHTB** | 187 | `BPHTB` | `(npop - 80000000) * 0.05` |
| **Pajak Reklame** | 188 | `REKLAME` | `((njopr + nspr) * luas * sisi) * 0.25` |
| **Pajak MBLB** | 189 | `MBLB` | `(volume * harga) * 0.15` |
| **Pajak Walet** | 190 | `WALET` | `(volume * harga) * 0.10` |
| **Opsen Pajak** | 191 | `OPSEN` | `pokok_provinsi * 0.66` |
| **PBJT Makan/Minum**| 192 | `PBJT-FOOD` | `omzet * 0.1` |
| **PBJT Perhotelan** | 193 | `PBJT-HTL` | `omzet * 0.1` |
| **PBJT Hiburan** | 194 | `PBJT-HBR` | `omzet * 0.1` |
| **Hiburan Malam** | 195 | `PBJT-HBR-SP`| `omzet * 0.4` |
| **Tenaga Listrik** | 196 | `PBJT-PLN` | `tagihan * 0.1` |
| **Pajak Air Tanah** | 197 | `AIR-TANAH` | `(volume * hda) * 0.2` |
| **Persampahan** | 198 | `RET-SMP` | `tarif_flat` |
| **Parkir Tepi Jalan**| 199 | `RET-PRK` | `tarif_flat` |
| **Sewa PKD (Lapak)** | 200 | `RET-PKD` | `tarif_dasar * koefisien` |
| **Umum Lainnya** | 156 | `W1-OTH` | `tarif_flat` |
