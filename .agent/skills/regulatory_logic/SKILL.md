---
name: Regulatory Compliance & Tax Logic
description: Skill khusus untuk memastikan seluruh logika bisnis, kalkulasi pajak, dan tenggat waktu sesuai dengan Perda 1/2024, Perwali Kota Baubau (Perwali 58/2024, 63/2024, 8/2025, 11/2025, 41/2025, 42/2025, 43/2025, 55/2025), dan UU HKPD 1/2022.
---

# Instruksi Kepatuhan Regulasi (Source of Truth)
Gunakan skill ini sebagai acuan hukum utama setiap kali memodifikasi logika perhitungan pajak, denda, atau siklus pelaporan di sistem M-PAD.

## 🏛️ Hirarki Sistem (Portfolio-Based RBAC - Perwali 8/2025)
Sistem M-PAD Baubau menggunakan pembagian portofolio pendapatan untuk Wilayah I dan II:
1.  **Level 1 (Wilayah I)**: **ID: 16**. Mengelola PBB-P2, BPHTB, Reklame, MBLB, Sarang Burung Walet, dan Opsen Pajak (PKB/BBNKB).
2.  **Level 1 (Wilayah II)**: **ID: 17**. Mengelola seluruh jenis PBJT (Restoran, Hotel, Parkir, Hiburan, Listrik), Pajak Air Tanah (PAT), dan Retribusi Layanan OPD.
3.  **Level 2 (Klasifikasi)**: **ID Master 186-215**. Kategori Pajak di bawah portofolio masing-masing. Seluruh logika perhitungan dan ikon reside di level ini. 
    > [!NOTE]
    > Dalam konteks migrasi dari **9pajak (Legacy)**, satu baris di sistem lama (seperti 'Hotel' atau 'Restoran') setara dengan satu **Klasifikasi (Level 2)** di M-PAD. 

## 🏛️ Dasar Hukum Utama
1.  **UU No. 1 Tahun 2022**: Hubungan Keuangan antara Pemerintah Pusat dan Pemerintahan Daerah (HKPD).
2.  **PP No. 35 Tahun 2023**: Ketentuan Umum Pajak Daerah dan Retribusi Daerah (KUPDRD).
3.  **Perda Kota Baubau No. 1 Tahun 2024**: Pajak Daerah dan Retribusi Daerah.
4.  **Perwali Kota Baubau Terkait**:
    - **Perwali 58/2024**: Tata Cara Pemungutan PDRD.
    - **Perwali 63/2024**: Pembebasan Retribusi PBG bagi Masyarakat Berpenghasilan Rendah (MBR).
    - **Perwali 8/2025**: Pembagian Portofolio Pendapatan Wilayah I & II.
    - **Perwali 11/2025**: Pajak Reklame (Zonasi Jalan A, B, C & NSPR).
    - **Perwali 41/2025**: Insentif Pemungutan PDRD.
    - **Perwali 42/2025**: Pemungutan Opsen MBLB.
    - **Perwali 43/2025**: Penilaian PBB-P2 & Penyesuaian NJOP.
    - **Perwali 55/2025**: Penyesuaian Tarif Retribusi Pasar (Koefisien Zonasi).
5.  **`docs/01_regulasi_baubau/BAUBAU_REGULATORY_MASTER.md`**: Referensi teknis internal M-PAD.

## 📊 Standar Tarif & Threshold Resmi
- **PBJT (Makan/Minum, Hotel, Parkir, Hiburan Standar):** 10%.
- **PBJT Hiburan Khusus:** 40% (Diskotik, Karaoke, Klub Malam, Bar, Mandi Uap/Spa).
- **PBJT Tenaga Listrik:** 10% (Konsumsi Umum) / 3% (Industri/Migas) / 1.5% (Dihasilkan Sendiri).
- **Pajak Reklame:** 25% dari NSR (NSPR + NPR) berdasarkan Zonasi Jalan A, B, C.
- **Pajak Air Tanah:** 20% dari (Volume Ekstraksi x HDA).
- **Pajak MBLB:** 15% dari (Volume Tonase x Harga Patokan).
- **PBB-P2:** (NJOP Total - NJOPTKP Rp10.000.000) x Tarif Progresif (0.15% - 0.30%). Lahan Pangan 0.25%.
- **BPHTB:** (NPOP - NPOPTKP) x 5%. NPOPTKP Pertama Rp 80jt, Hibah/Waris Rp 300jt.
- **Opsen PKB/BBNKB:** 66% dari pokok pajak Provinsi (berlaku mulai 5 Januari 2025).
- **Sanksi Keterlambatan (STPD/STRD):** Bunga 1% per bulan (maksimal 24 bulan per PP 35/2023).

## ⏳ Siklus Dokumen Bapenda (21 Dokumen Terstandarisasi)
1. **Pendaftaran & Pendataan**: `SPOP`, `LSPOP`, `SPOPD`, `LKOK`, `SKT`.
2. **Pelaporan & Penetapan**: `SPTPD`, `SKPD`, `SKRD`, `SPPT`, `SKPDKB`, `SKPDKBT`, `SKPDLB`, `SKPDN`.
3. **Pembayaran & Penagihan**: `SSPD`, `SSRD`, `STPD`, `STRD`, `Surat Teguran (1, 2, 3)`, `Surat Paksa (SPMP)`.

## 🛡️ Aturan Modifikasi Kode
1. **Dilarang keras** menggunakan kolom `tariff_percent` atau `base_amount` di tabel `retribution_types` (Level 1). Kolom tersebut sengaja dikosongkan.
2. Selalu gunakan relasi `retribution_classification_id` (Level 2) untuk mengambil parameter tarif dan rumus perhitungan.
