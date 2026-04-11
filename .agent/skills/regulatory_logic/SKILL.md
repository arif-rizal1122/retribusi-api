---
name: Regulatory Compliance & Tax Logic
description: Skill khusus untuk memastikan seluruh logika bisnis, kalkulasi pajak, dan tenggat waktu sesuai dengan Perda 1/2024 dan Perwali Kota Baubau (Perwali 58/2024).
---

# Instruksi Kepatuhan Regulasi (Source of Truth)
Gunakan skill ini sebagai acuan hukum utama setiap kali memodifikasi logika perhitungan pajak, denda, atau siklus pelaporan di sistem MPAD.

## 🏛️ Hirarki Sistem (Portfolio-Based RBAC - Perwali 8/2025)
Sistem M-PAD Baubau menggunakan pembagian portofolio pendapatan untuk Wilayah I dan II:
1.  **Level 1 (Wilayah I)**: **ID: 16**. Mengelola PBB-P2, BPHTB, Reklame, MBLB, Sarang Burung Walet, dan Opsen.
2.  **Level 1 (Wilayah II)**: **ID: 17**. Mengelola seluruh jenis PBJT, Pajak Air Tanah (PAT), dan Retribusi.
3.  **Level 2 (Klasifikasi)**: **ID Master 186-200**. Kategori Pajak di bawah portofolio masing-masing. Seluruh logika perhitungan dan ikon reside di level ini. 
    > [!NOTE]
    > Dalam konteks migrasi dari **9pajak (Legacy)**, satu baris di sistem lama (seperti 'Hotel' atau 'Restoran') setara dengan satu **Klasifikasi (Level 2)** di M-PAD. 

## 🏛️ Dasar Hukum Utama
1.  **Perda Kota Baubau No. 1 Tahun 2024**: Pajak Daerah dan Retribusi Daerah.
2.  **Perwali Baubau No. 58 Tahun 2024**: Tata Cara Pemungutan.
3.  **`docs/BAUBAU_REGULATORY_MASTER.md`**: Referensi teknis internal M-PAD.

## 📊 Standar Tarif & Threshold (Update 2024)
- **PBJT (Makan/Minum, Hotel, Parkir):** 10%.
- **PBJT Hiburan Malam:** 40% (Diskotik, Karaoke, Klub Malam).
- **PBJT Tenaga Listrik:** 10% (Konsumsi Umum) / 3% (Industri) / 1.5% (Sendiri).
- **Pajak Reklame:** 25% dari NSR (Nilai Sewa Reklame).
- **Pajak Air Tanah:** 20% dari (Volume x HDA).
- **Pajak MBLB:** 15% dari (Volume x Harga).
- **PBB-P2:** (NJOP - NJOPTKP Rp10jt) x 0.3%. Lahan Pangan 0.25%.
- **BPHTB:** (NPOP - NPOPTKP 80jt) x 5%. Waris 300jt NPOPTKP.
- **Opsen PKB/BBNKB:** 66% dari pokok pajak Provinsi.

## ⏳ Kebijakan Waktu & Dokumen (Nomenklatur Bapenda)
Sistem **MITRA** mengikuti siklus hulu-ke-hilir sebagai berikut:

### 1. Tahap Pendaftaran dan Pendataan (Registration)
-   **SPOPD**: Pendaftaran usaha (PBJT, Reklame, PAT, MBLB, Walet).
-   **SPOP & LSPOP**: Pendaftaran khusus PBB-P2.
-   **SKT**: Bukti WP telah resmi terdaftar.
-   **LKOK**: Lembar Kerja Objek Khusus untuk operasional petugas.

### 2. Tahap Pelaporan dan Penetapan (Assessment)
-   **SPTPD**: Laporan omzet mandiri (Self Assessment) bulanan.
-   **SKPD / SKRD**: Tagihan resmi Pokok Pajak / Retribusi (Official).
-   **SPPT**: Tagihan resmi tahunan PBB-P2.
-   **SKPDKB / SKPDKBT**: Tagihan Kurang Bayar (setelah pemeriksaan/pemeriksaan tambahan).
-   **SKPDLB / SKPDN**: Penetapan Lebih Bayar atau Nihil.

### 3. Tahap Pembayaran dan Penagihan (Collection & Enforcement)
-   **SSPD / SSRD**: Bukti sah penyetoran ke Kas Daerah.
-   **STPD**: Tagihan denda/sanksi bunga (Penalty).
-   **Surat Teguran (1, 2, 3)**: Peringatan tunggakan jatuh tempo.
-   **SPMP (Surat Paksa)**: Dasar hukum penyegelan/penyitaan (Enforcement).

## 🛡️ Aturan Modifikasi Kode
1. **Dilarang keras** menggunakan kolom `tariff_percent` atau `base_amount` di tabel `retribution_types` (Level 1). Kolom tersebut sengaja dikosongkan.
2. Selalu gunakan penanda `retribution_classification_id` untuk mengambil parameter tarif dan rumus perhitungan.
