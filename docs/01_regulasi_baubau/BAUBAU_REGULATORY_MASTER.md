# 🏛️ BAUBAU REGULATORY MASTER (Source of Truth 2024–2026)
## Kompendium Regulasi Pajak Daerah dan Retribusi Daerah Kota Baubau

**Status**: Master Reference (Sumber Kebenaran Bisnis M-PAD)  
**Terakhir Diperbarui**: 30 Agustus 2026  
**Dasar Hukum Utama**:
1. **UU No. 1 Tahun 2022** tentang Hubungan Keuangan antara Pemerintah Pusat dan Pemerintahan Daerah (HKPD).
2. **PP No. 35 Tahun 2023** tentang Ketentuan Umum Pajak Daerah dan Retribusi Daerah (KUPDRD).
3. **Peraturan Daerah (Perda) Kota Baubau No. 1 Tahun 2024** tentang Pajak Daerah dan Retribusi Daerah.
4. **Peraturan Wali Kota (Perwali) Kota Baubau**:
   * **Perwali No. 58 Tahun 2024**: Tata Cara Pemungutan Pajak Daerah dan Retribusi Daerah.
   * **Perwali No. 63 Tahun 2024**: Pembebasan Retribusi PBG bagi Masyarakat Berpenghasilan Rendah (MBR).
   * **Perwali No. 8 Tahun 2025**: Pembagian Portofolio Pendapatan Wilayah I dan Wilayah II (RBAC M-PAD).
   * **Perwali No. 11 Tahun 2025**: Tata Cara Perhitungan dan Penyelenggaraan Pajak Reklame (Zonasi Jalan & NSPR).
   * **Perwali No. 41 Tahun 2025**: Insentif Pemungutan Pajak Daerah dan Retribusi Daerah.
   * **Perwali No. 42 Tahun 2025**: Pemungutan Opsen Pajak MBLB dan Sinergi Pemungutan.
   * **Perwali No. 43 Tahun 2025**: Penilaian Pajak Bumi dan Bangunan Perdesaan dan Perkotaan (PBB-P2 & ZNT).
   * **Perwali No. 55 Tahun 2025**: Penyesuaian Detail Objek dan Tarif Retribusi Jasa Umum Pasar.

---

## 1. Pembagian Portofolio & Struktur Hirarki M-PAD (Perwali 8/2025)

Sistem M-PAD membagi seluruh pendapatan daerah ke dalam arsitektur **4-Tingkat Hirarki**:

```
Level 1: Jenis Retribusi/Pajak (Portofolio Wilayah)
  └── Level 2: Klasifikasi Pajak/Retribusi (ID: 186–215)
        └── Level 3: Zona / Lokasi / Kategori Tarif (ZNT, Kelas Jalan, Koefisien Lapak)
              └── Level 4: Tarif & Formula Matematis
```

### Portofolio Wilayah I (Aset, Properti, dan Official Assessment - ID: 16)
Mengelola jenis pajak dan pungutan yang berbasis pada penetapan resmi (*Official Assessment*) dan kepemilikan/penguasaan aset fisik:
1. **PBB-P2** (Pajak Bumi dan Bangunan Perdesaan dan Perkotaan)
2. **BPHTB** (Bea Perolehan Hak atas Tanah dan Bangunan)
3. **Pajak Reklame**
4. **Pajak MBLB** (Mineral Bukan Logam dan Batuan)
5. **Pajak Sarang Burung Walet**
6. **Opsen Pajak Provinsi** (Opsen PKB & Opsen BBNKB - efektif 5 Jan 2025)

### Portofolio Wilayah II (Konsumsi, Self-Assessment, dan Retribusi Layanan - ID: 17)
Mengelola jenis pajak pelaporan mandiri (*Self-Assessment*) berbasis omzet serta retribusi pelayanan dinas teknis:
1. **PBJT Makanan dan/atau Minuman** (Restoran, Rumah Makan, Kafe, Katering)
2. **PBJT Tenaga Listrik** (Konsumsi Umum, Industri/Migas, Pembangkit Sendiri)
3. **PBJT Jasa Perhotelan** (Hotel, Losmen, Penginapan, Kos > 10 Kamar)
4. **PBJT Jasa Parkir** (Gedung/Pelataran Parkir Khusus)
5. **PBJT Jasa Kesenian dan Hiburan** (Bioskop, Rekreasi, Panti Pijat, Diskotik/Karaoke/Klub Malam)
6. **Pajak Air Tanah (PAT)**
7. **Retribusi Jasa Umum** (5 Jenis: Kebersihan, Parkir Tepi Jalan, Pasar, PKB/KIR, Menara Telekomunikasi)
8. **Retribusi Jasa Usaha** (9 Jenis: Pasar, Terminal, RPH, Penyeberangan Air, Rekreasi, Lab, Usaha Daerah, Pemanfaatan Aset)
9. **Retribusi Perizinan Tertentu** (2 Jenis: PBG dan Pengesahan RPTKA TKA)

---

## 2. Rincian Tarif, Threshold, & Rumus Perhitungan Resmi

### A. Portofolio Wilayah I (Pajak Aset & Properti)

#### 1. Pajak Bumi dan Bangunan (PBB-P2) — Perda 1/2024 & Perwali 43/2025
* **Rumus Pokok**:
  $$\text{Ketetapan PBB} = (\text{NJOP Total} - \text{NJOPTKP}) \times \text{Tarif}$$
* **NJOPTKP**: Ditetapkan sebesar **Rp 10.000.000** per Wajib Pajak (untuk 1 objek dengan NJOP terbesar).
* **Tarif Progresif PBB-P2**:
  * NJOP s.d. Rp 1 Miliar: **0,15%**
  * NJOP > Rp 1 Miliar s.d. Rp 2 Miliar: **0,20%**
  * NJOP > Rp 2 Miliar s.d. Rp 5 Miliar: **0,25%**
  * NJOP > Rp 5 Miliar: **0,30%**
  * Lahan Produksi Pangan dan Ternak: **0,25%** (Flat).

#### 2. Bea Perolehan Hak atas Tanah dan Bangunan (BPHTB) — Perda 1/2024
* **Rumus Pokok**:
  $$\text{BPHTB Terutang} = (\text{NPOP} - \text{NPOPTKP}) \times 5\%$$
  *Catatan: Jika NPOP lebih kecil dari NJOP PBB, maka dasar pengenaan menggunakan NJOP PBB.*
* **NPOPTKP (Nilai Perolehan Objek Pajak Tidak Kena Pajak)**:
  * Perolehan Hak Pertama / Jual Beli / Tukar Menukar: **Rp 80.000.000**
  * Hibah Wasiat / Waris (Keluarga Sedarah Garis Lurus Satu Derajat): **Rp 300.000.000**

#### 3. Pajak Reklame — Perda 1/2024 & Perwali 11/2025
* **Rumus Pokok**:
  $$\text{Pajak Reklame} = \text{NSR (Nilai Sewa Reklame)} \times 25\%$$
* **Komponen NSR**:
  $$\text{NSR} = \text{NSPR (Nilai Strategis)} + \text{NPR (Nilai Pembuatan/Dimensi)}$$
* **Zonasi Kelas Jalan (NSPR)**:
  * **Kelas Jalan A (Sangat Strategis)**: Jl. RA Kartini, Jl. Yos Sudarso, Jl. Jend. Sudirman, Kawasan Pantai Kamali, Kawasan Kotamara.
  * **Kelas Jalan B (Strategis)**: Jl. Teuku Umar, Jl. RE Martadinata, Jl. Gatot Subroto, Jl. Betoambari.
  * **Kelas Jalan C (Standar)**: Jl. Imam Bonjol, Jl. Seram, Jl. Pahlawan, Jl. Sultan Murhum.

#### 4. Pajak MBLB (Mineral Bukan Logam dan Batuan) — Perda 1/2024 & Perwali 42/2025
* **Rumus**:
  $$\text{Pajak MBLB} = (\text{Volume Tonase / } \text{m}^3 \times \text{Harga Patokan Daerah}) \times 15\%$$

#### 5. Pajak Sarang Burung Walet — Perda 1/2024
* **Rumus**:
  $$\text{Pajak Walet} = (\text{Volume Panen (Kg)} \times \text{Harga Pasar Standar}) \times 10\%$$

#### 6. Opsen Pajak Provinsi (UU 1/2022 HKPD — Berlaku Mulai 5 Januari 2025)
* **Opsen PKB (Pajak Kendaraan Bermotor)**: **66%** dari pokok PKB terutang provinsi.
* **Opsen BBNKB (Bea Balik Nama Kendaraan Bermotor)**: **66%** dari pokok BBNKB terutang provinsi.

---

### B. Portofolio Wilayah II (Pajak Konsumsi / PBJT & Retribusi)

#### 1. Pajak Barang dan Jasa Tertentu (PBJT) — Perda 1/2024
* **Dasar Pengenaan**: Jumlah pembayaran yang diterima oleh penyedia barang dan jasa (Omzet).
* **Rincian Tarif PBJT**:
  * **Makanan dan/atau Minuman**: **10%** *(Threshold omzet wajib pajak: > Rp 3.000.000/bulan)*.
  * **Jasa Perhotelan**: **10%** *(Termasuk losmen, motel, villa, guest house, dan indekos > 10 kamar)*.
  * **Jasa Parkir**: **10%** *(Penyelenggara tempat parkir khusus di luar badan jalan)*.
  * **Jasa Kesenian dan Hiburan (Standar)**: **10%** *(Bioskop, konser, bowling, fitness, panti pijat)*.
  * **Jasa Kesenian dan Hiburan (Khusus)**: **40%** *(Diskotik, karaoke dewasa/keluarga, klub malam, bar, dan mandi uap/spa)*.
  * **Tenaga Listrik**:
    * Konsumsi Listrik Umum / Rumah Tangga / Bisnis: **10%**
    * Konsumsi Listrik Industri, Pertambangan, Migas: **3%**
    * Tenaga Listrik yang Dihasilkan Sendiri: **1,5%**

#### 2. Pajak Air Tanah (PAT) — Perda 1/2024
* **Rumus**:
  $$\text{PAT} = (\text{Volume Ekstraksi } \text{m}^3 \times \text{Harga Dasar Air/HDA}) \times 20\%$$

#### 3. Retribusi Jasa Umum, Jasa Usaha, dan Perizinan Tertentu
* **Persetujuan Bangunan Gedung (PBG - DPMPTSP / PUPR)**:
  $$\text{Retribusi PBG} = \text{Luas Bangunan } (\text{m}^2) \times \text{Indeks Lokalitas} \times \text{Indeks Terintegrasi} \times \text{SHST}$$
  *Catatan: Pembebasan 100% untuk Masyarakat Berpenghasilan Rendah (MBR) per Perwali 63/2024.*
* **Pengesahan Perpanjangan RPTKA (TKA - DPMPTSP)**:
  Tarif standar kompensasi ketenagakerjaan per orang per bulan sesuai kurs BI.
* **Pelayanan Pasar (Perwali 55/2025)**:
  Menggunakan sistem koefisien zonasi lokasi lapak/kios (Koefisien 1.3 - 1.0 $\times$ Tarif Dasar).
* **Parkir Tepi Jalan Umum (Dishub)**:
  * Roda 4 (Mobil): Rp 2.000 – Rp 3.000
  * Roda 2 (Motor): Rp 1.000 – Rp 2.000

---

## 3. Sanksi Administrasi & Bunga Keterlambatan (KUPDRD PP 35/2023)

* **Sanksi Keterlambatan Pembayaran (STPD / STRD)**:
  Dikenakan sanksi administratif berupa bunga sebesar **1% (satu persen) per bulan** dari pokok pajak/retribusi yang tidak atau kurang dibayar.
* **Masa Maksimal Denda**: Dihitung paling lama **24 (dua puluh empat) bulan** sejak saat terutang.
* **Sanksi Keterlambatan Penyampaian SPTPD**:
  Dikenakan denda administratif tetap sebesar Rp 100.000 per masa pajak untuk orang pribadi dan Rp 500.000 untuk badan.

---

## 4. Siklus Dokumen Resmi Bapenda (21 Dokumen Terstandarisasi)

```
[Tahap 1: Pendaftaran]
SPOP / SPOPD ──► LKOK (Verifikasi Lapangan) ──► SKT (Surat Keterangan Terdaftar)

[Tahap 2: Pelaporan & Penetapan]
SPTPD (Laporan Mandiri) ──► SKPD / SKRD (Ketetapan Pokok) / SPPT (PBB)
                       └──► SKPDKB / SKPDKBT (Kurang Bayar) / SKPDLB (Lebih Bayar) / SKPDN (Nihil)

[Tahap 3: Pembayaran & Penagihan]
Bayar (BRI/QRIS) ──► SSPD / SSRD (Bukti Sah Lunas + QR TTE)
Tunggakan        ──► STPD / STRD (Tagihan Bunga 1%) ──► Surat Teguran 1-3 ──► Surat Paksa (SPMP)
```

---

## 5. Matriks Peran & Hak Akses (RBAC Ekosistem M-PAD)

| Peran (Role) | Akses Portofolio | Wewenang Utama |
| :--- | :--- | :--- |
| **Superadmin (Kadis Bapenda)** | All (Level 1–4) | TTE Elektronik dokumen SKPD/SKRD, persetujuan penghapusan, laporan eksekutif. |
| **Kabid / Kasubid Wilayah I** | Portofolio ID: 16 | Verifikasi PBB, BPHTB, Reklame, penetapan SKPDKB, penerbitan SPPT masal. |
| **Kabid / Kasubid Wilayah II** | Portofolio ID: 17 | Verifikasi SPTPD bulanan PBJT, PAT, Retribusi, penerbitan SKRD. |
| **Admin OPD (e.g., DPMPTSP, Damkar, DLH)** | OPD Terkait | Verifikasi teknis berkas, persetujuan rekomendasi teknis (e.g., Damkar = verifikator proteksi gedung). |
| **Petugas Lapangan (Officer)** | Assigned Territory | Koleksi tagihan tunai/QRIS lapangan, cetak struk thermal BLE 58mm, audit LKOK. |
| **Wajib Pajak (Citizen)** | Data Milik Sendiri | Pendaftaran NOP SPOP mandiri, pembayaran multi-bill SNAP BRIVA/QRIS, unduh SSPD sah. |
