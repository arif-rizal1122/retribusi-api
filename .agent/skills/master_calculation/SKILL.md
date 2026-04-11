---
name: Master Calculation & Hierarchy Specialist
description: Panduan komprehensif mengenai 4 tingkatan hirarki sistem M-PAD, tujuan masing-masing level, serta seluruh jenis pajak/retribusi daerah Kota Baubau beserta rumusnya (Perda 1/2024).
---

# Master Calculation & Hierarchy Specialist

Skill ini adalah sumber kebenaran tunggal untuk memahamami struktur data, alur bisnis, dan logika perhitungan di ekosistem M-PAD (Pajak dan Retribusi Daerah Kota Baubau).

## 1. Empat (4) Hirarki Data M-PAD
Sistem ini dibangun di atas 4 pilar hirarki yang saling terhubung:

| Level | Model | Deskripsi & Tujuan (Perwali 8/2025) |
| :--- | :--- | :--- |
| **1. Jenis** | `RetributionType` | **Portofolio Bidang Pendapatan**. Wilayah I (ID: 16) & Wilayah II (ID: 17). |
| **2. Klasifikasi** | `RetributionClassification` | **Kategori Pajak**. ID Master: **186 - 200**. Berisi `calculation_formula`, `form_schema`, dan Ikon Cloudinary. |
| **3. Objek** | `TaxObject` | Unit fisik yang dikenakan beban dengan NOP/NOPD unik. |
| **4. Penagihan** | `Bill` | Produk akhir (Laporan SPTPD/SKPD). |

---

## 2. Katalog Pajak (Logic & Formula - Perda 1/2024)

### A. Pajak Barang dan Jasa Tertentu (PBJT) - Tarif 10%
- **PBJT Makanan dan Minuman**: `omzet * 0.10`
- **PBJT Jasa Perhotelan**: `omzet * 0.10`
- **PBJT Jasa Parkir**: `omzet * 0.10`
- **PBJT Jasa Kesenian dan Hiburan**: Umum 10% / Khusus (Diskotik/Karaoke) **40%**.
- **PBJT Tenaga Listrik**: Umum 10% / Industri 3% / Sendiri 1.5%.

### B. Pajak Spesifik
- **Pajak Reklame (25%)**: `NSR x 0.25` (NSR dipengaruhi Kelas Jalan A/B/C).
- **PBB-P2 (0.3%)**: `(NJOP - NJOPTKP Rp10.000.000) x 0.003` (Lahan Pangan/Ternak 0.25%).
- **BPHTB (5%)**: `(NPOP - NPOPTKP) x 0.05`.
    - NPOPTKP Umum: **Rp 80.000.000**.
    - NPOPTKP Waris/Hibah Sedarah: **Rp 300.000.000**.
- **Pajak MBLB (15%)**: `(Volume x Harga Patokan) x 0.15`.
- **Pajak Air Tanah (20%)**: `(Volume x Harga Dasar Air) x 0.20`.
- **Pajak Sarang Burung Walet (10%)**: `(Vol x Harga Pasar) x 0.10`.

### C. Opsen Pajak (66%)
- **Opsen PKB & Opsen BBNKB**: `Pokok Pajak Prov * 0.66`.

---

## 3. Logika Zonasi & Tarif Spasial (Baubau 2024)

Sistem menggunakan alur: **Jenis -> Klasifikasi -> Zona -> Tarif**.

### A. Pajak Reklame (Zonasi Kelas Jalan)
- **Kelas Jalan A (Sangat Strategis)**: Jl. RA. Kartini, Jl. Yos Sudarso, Jl. Jend. Sudirman, Pantai Kamali, Kotamara.
- **Kelas Jalan B (Strategis)**: Jl. Teuku Umar, Jl. RE Martadinata, Jl. Gatot Subroto.
- **Kelas Jalan C (Standar)**: Jl. Imam Bonjol, Jl. Seram, dll.

### B. PBB-P2 (ZNT & NIR)
- **Sistem**: Berdasarkan **ZNT (Zona Nilai Tanah)** dengan **NIR (Nilai Indikasi Rata-Rata)** per blok kelurahan.
- **NJOPTKP**: Rp 10.000.000.

### C. Retribusi Sewa Lapak (Sistem Koefisien)
- **Zona Premium (1.3)**: Kamali/Kotamara (Kawasan Wisata).
- **Zona Strategis (1.2)**: Pusat Kota/Perdagangan (Pelabuhan).
- **Zona Ekonomi (1.1)**: Kawasan Pasar (Wameo).
- **Zona Umum (1.0)**: Kawasan Permukiman.

### D. Retribusi Parkir Tepi Jalan & Sedot Kakus
- **Zonasi Parkir**: Premium (3k/2k), Strategis (2k/1.5k), Ekonomi (1.5k/1k), Umum (1k).
- **Zonasi Kakus**: 
    - **Zona I**: Wolio, Murhum, Betoambari, Kokalukuna (Rp 150k).
    - **Zona II/III**: Bungi, Lea-Lea, Sorawolio (Rp 200k - 250k).

## 4. Siklus Dokumen & Administrasi
1.  **Pendataan**: SPOPD (Lapor Objek), SPOP (PBB), SKT (Bukti Daftar), LKOK (Hasil Lapangan Petugas).
2.  **Pelaporan**: SPTPD (Lapor Omzet), SKPD/SKRD (Tagihan), SPPT (PBB), SKPDKB (Kurang Bayar).
3.  **Penagihan Akhir**: SSPD (Bukti Bayar), STPD (Denda Bunga), Surat Teguran (1, 2, 3), SPMP (Sita/Penyegelan).

## 5. Metadata & Model Mapping (April 2026)
- **Database Baseline**: Referensi utama ID Klasifikasi di sistem MITRA adalah **186 - 200**.
- **Visual Asset**: Ikon menggunakan URL Cloudinary unik untuk visualisasi yang berbeda per klasifikasi.
- **Formula Parser**: Variabel (NJOP, Omzet, Volume, Koefisien Zona) diambil dari `tax_objects.metadata`. Hasil akhir dibulatkan ke bawah ke satuan Rupiah penuh.

> [!IMPORTANT]
> Selalu rujuk [BAUBAU_REGULATORY_MASTER.md](file:///Users/pondokit/Herd/retribusi-api/docs/01_regulasi_baubau/BAUBAU_REGULATORY_MASTER.md) sebagai "Technical Source of Truth" untuk pemulihan atau migrasi data lebih lanjut.
