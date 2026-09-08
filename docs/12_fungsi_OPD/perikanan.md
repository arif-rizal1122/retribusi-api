# Laporan Arsitektur Teknis & Roadmap Implementasi Digitalisasi Pendapatan TPI M-PAD (Dinas Perikanan Kota Baubau)
**Nomor Dokumen**: BAPENDA-PERIKANAN/MPAD/TECH-SPEC/2026/001  
**Tanggal Rilis**: 8 September 2026 | **Klasifikasi**: Terbatas  
**Entitas Pengampu**: Dinas Perikanan Kota Baubau (OPD ID 9 / 12)  
**Koordinator Fiskal**: Bapenda Kota Baubau  
**Akun Admin**: `admin.dinas_perikanan@baubaukota.go.id`  
**Landasan Hukum**: UU No. 1/2022 (HKPD), Perda Kota Baubau No. 1/2024, PP No. 35/2023  
**Standar Pembayaran**: SNAP BI H2H (Bank Sultra Direct RKUD)  

---

## RINGKASAN EKSEKUTIF
Dokumen ini merumuskan blueprint perancangan teknis dan roadmap implementasi digitalisasi tata kelola pendapatan pada **Dinas Perikanan Kota Baubau** dalam ekosistem terpadu **M-PAD (Mitra Pajak & Retribusi Daerah)**. Transformasi ini didorong oleh pemberlakuan UU No. 1 Tahun 2022 (HKPD) dan Perda Kota Baubau No. 1 Tahun 2024, yang mereposisi peran Dinas Perikanan tidak hanya sebagai pengampu retribusi jasa usaha fasilitas fisik TPI, melainkan juga sebagai **pemasok data intelijen fiskal hulu (upstream intelligence)** untuk menguji silang kepatuhan **Pajak Barang dan Jasa Tertentu (PBJT 10%)** kuliner restoran seafood dan **Pajak Air Tanah (PAT 20%)** pada pabrik es swasta.

---

## BAB I. KEDUDUKAN YURIDIS & PEMETAAN LAYANAN FISKAL

| Kategori Layanan | Dasar Regulasi | Objek Layanan & Batas Kewenangan | Perlakuan Sistem M-PAD |
| :--- | :--- | :--- | :--- |
| **Retribusi Jasa Usaha TPI (Layanan Inti)** | Pasal 84 ayat (1) huruf b & Pasal 87 | Penyediaan fasilitas fisik pelelangan: sewa meja lelang, timbangan tertera, tambat labuh kapal perikanan di dermaga TPI, pasokan es curah/balok, parkir truk pendingin, dan jasa lelang 1% (TPI Wameo & TPI Bima). | Input Smart Form Petugas, penerbitan e-SKRD karcis digital ber-QR NTPD, kalkulasi instan, transaksi hybrid (QRIS/Tunai T+0). |
| **Pemanfaatan Aset Daerah (Recurring Billing)** | Pasal 84 ayat (1) huruf i & Pasal 94 | Sewa aset fisik Pemkot: Kamar pendingin (Cold Storage), pabrik es pemda, lantai jemur ikan asin, dan sewa kios/petak sarana perikanan di kawasan pesisir TPI. | Penerbitan e-SKRD berkala bulanan otomatis (recurring billing) dengan nomor Virtual Account Bank Sultra direct RKUD. |
| **Penjualan Hasil Produksi (Balai Benih Ikan)** | Pasal 84 ayat (1) huruf h & Pasal 93 | Penjualan bibit/benih ikan air tawar dan payau dari Balai Benih Ikan (BBI) milik Pemerintah Kota Baubau. | Billing mandiri QRIS pada aplikasi warga / mobile M-PAD langsung tercatat pada pos pendapatan dinas perikanan. |
| **Intelijen Hulu Pajak Daerah (Sinergi Bapenda)** | Pasal 18 huruf a, 19, & 24 (PBJT) | Pengawasan omzet restoran seafood, rumah makan ikan bakar (Tarif PBJT 10%), serta sumur bor pabrik es swasta (PAT 20%). | **Cross-reconciliation engine**: Data tonase lelang harian TPI menjadi instrumen uji silang terhadap SPTPD bulanan restoran. |
| **PENCABUTAN RETRIBUSI IZIN (Pengawasan Hukum)** | Pasal 122 huruf k (Perda 35/2012 Batal) | **PERHATIAN**: Retribusi Izin Usaha Perikanan (IUP/SIPI daerah) telah dihapus permanen. Perizinan kapal sepenuhnya melalui OSS-RBA non-retribusi. | Sistem memblokir penagihan izin perikanan; fokus dialihkan 100% pada pelayanan fasilitas fisik dan aset daerah. |

---

## BAB II. ANALISIS KOMPARASI SPASIAL: TPI WAMEO LAMA VS TPI WAMEO BARU

| Parameter Pembeda | TPI Wameo Lama (Pesisir Tradisional) | TPI Wameo Baru (PPI Higienis) |
| :--- | :--- | :--- |
| **Target Subjek & Kapal** | Nelayan perahu kecil/katinting (<5 GT) dan pedagang eceran (papalele lokal). | Kapal Motor besar (10–30+ GT), tengkulak utama, dan distributor antar-pulau. |
| **Karakteristik Transaksi** | Volume kecil (50–200 kg), perputaran uang tunai lelang subuh >85%, tanpa pembukuan. | Volume besar (1–10 ton), nilai transaksi puluhan juta, terstruktur, membutuhkan faktur sah. |
| **Fasilitas Fisik Tersedia** | Pelataran terbuka, timbangan gantung manual, meja kayu, tanpa cold storage/es pipa. | Standar HACCP gedung tertutup, meja stainless, timbangan digital tertera, cold storage, es tonase. |
| **Perlakuan Modul M-PAD** | Mode Kasir Cepat Tunai (Quick-Tap via Officer Wallet), UI menyembunyikan opsi fasilitas modern. | Full-Featured UI, wajib non-tunai (QRIS Dinamis & Virtual Account direct Kas Daerah). |
| **Diferensiasi Tarif & Strategi** | Tarif pelataran/kebersihan minimalis sesuai Pasal 87 ayat 3 agar tidak membebani nelayan mikro. | Tarif fasilitas penuh; insentif es murah dan tambat aman untuk menarik migrasi damai ke TPI Baru. |

> **Implementasi Spasial PostGIS**: Master data memisahkan poligon `TPI-WM-OLD` dan `TPI-WM-NEW`. Aplikasi petugas lapangan mengaktifkan geofencing GPS radius 75 meter guna mengunci form secara otomatis pada kode TPI yang tepat serta menegaskan batas teritorial dengan dermaga penyeberangan umum Dishub guna mencegah penarikan retribusi ganda.

---

## BAB III. SPESIFIKASI TEKNIS SMART FORM PENDATAAN TPI (DOKUMEN 172)

```
[SEKSI 1: HEADER & IDENTITAS TRANSAKSI]
 ├── 1.1 Kode TPI / Lokasi (Dropdown/Geofenced GPS: TPI Wameo Baru, TPI Wameo Lama, TPI Bima)
 ├── 1.2 Waktu & Tanggal (Auto-System Datetime, Server-Side Readonly)
 ├── 1.3 Identitas Nelayan/Kapal (QR Scanner / Input KUSUKA / Nomor Lambung / Auto-Fetch GT)
 └── 1.4 Identitas Bakul/Pembeli (QR Scanner / NIK / NIB Pedagang Terintegrasi Bapenda)

[SEKSI 2: MATRIKS RINCIAN FASILITAS FISIK TPI]
 ├── 2.1 Jasa Tambat Labuh: [ ] GT x Hari x Tarif Perda
 ├── 2.2 Sewa Meja Lelang: [ ] Qty Unit x Rp 15.000
 ├── 2.3 Sewa Timbangan Tertera: [ ] Qty Unit x Rp 10.000
 ├── 2.4 Pasokan Es Curah/Balok: [ ] Qty Ton/Batang x Tarif Es
 └── 2.5 Parkir Truk Pendingin: [ ] Golongan Kendaraan x Durasi

[SEKSI 3: HASIL TRANSAKSI LELANG & PAYMENT]
 ├── 3.1 Hasil Lelang: Tonase Total (Kg) & Nilai Transaksi Total (Rp)
 ├── 3.2 Total Retribusi: Subtotal Fasilitas (2.1–2.5) + (1% x Nilai Transaksi Lelang)
 └── 3.3 Kanal Pembayaran: QRIS Dinamis Bank Sultra / Virtual Account / Tunai Kasir Petugas (Officer Wallet)
```

---

## BAB IV. SPESIFIKASI STRUK THERMAL 58/80MM & AUDIT TRAIL (DOKUMEN 173)

- **Header Resmi**:
  ```text
  PEMERINTAH KOTA BAUBAU
  BADAN PENDAPATAN DAERAH
  DINAS PERIKANAN
  BUKTI PEMBAYARAN RETRIBUSI TPI (M-PAD)
  ```
- **Nomor NTPD & Transaksi**: Dicetak tebal (`No. Transaksi : TPI-WM/YYYYMMDD/XXXX`, `No. NTPD : 88202609...`).
- **Data Rincian**: Nama Kapal, GT, Nama Bakul, Volume (Kg), Nilai Lelang (Rp), rincian sewa meja, timbangan, es, tambat, jasa lelang 1%, Total Retribusi LUNAS.
- **QR Code Validasi BSrE**: `https://validasi.mpad.baubaukota.go.id/tpi/{ntpd}`
- **Standar Kertas**: Kertas Thermal Sintetis Tahan Air & Minyak (*Water & Oil Resistant Thermal Paper*) agar tulisan tidak luntur di lingkungan pesisir berair garam.

---

## BAB V. SINERGI INTELIJEN FISKAL HULU: TPI & PAJAK DAERAH BAPENDA

1. **Rekonsiliasi Rantai Pasok Ikan vs PBJT Restoran Seafood (10% - Pasal 18 huruf a)**:
   - Pembelian ikan oleh bakul/supplier restoran seafood tercatat di TPI (kg & jenis ikan).
   - Algoritma M-PAD mengonversi volume bahan mentah menjadi estimasi porsi makanan matang yang terjual.
   - Jika deviasi omzet riil vs laporan SPTPD bulanan restoran **> 30%**, sistem otomatis memicu **Red Channel Alert** untuk pemeriksaan kepatuhan pajak oleh pemeriksa Bapenda.
2. **Pengawasan Pajak Air Tanah (PAT 20%) Pabrik Es Swasta**:
   - Konsumsi es puluhan ton per hari di TPI dihubungkan dengan debit ekstraksi sumur bor pabrik es (`Tarif 20% x Volume m³ x HDA`) untuk memastikan kepatuhan PAT.

---

## BAB VI. 5 AREA INVESTIGASI LANJUTAN

1. **Legalitas Klausul Retribusi Lelang 1%**: Wajib disahkan melalui Peraturan Wali Kota (Perwali) tentang Juklak Retribusi TPI agar sah dan tidak menjadi temuan BPK RI.
2. **Logistik Ikan Antar-Pulau (Reefer Container di Pelabuhan Murhum)**: Interkoneksi data manifest muatan bersama KSOP/Pelindo; wajibkan Surat Keterangan Asal Ikan (SKAI) ber-QR M-PAD sebelum berlayar.
3. **Relasi Finansial Punggawa–Sawi**: Fitur *Contract Landing / Bongkar Muat Terikat* pada form M-PAD agar tonase tetap tercatat sah tanpa mengacaukan struktur modal lokal.
4. **Pendaratan Liar (Shadow Landings di Katobengke, Kolagana, Sulaa, Palabusa)**: Pos Pencatatan Bergerak (*Mobile Officer*) berbasis aplikasi offline-first PWA ber-GPS.
5. **Optimalisasi Balai Benih Ikan (BBI)**: Modul pemesanan & e-SKRD benih ikan air tawar/payau via M-PAD Warga.

---

## BAB VII. ARSITEKTUR DATABASE & BACKEND LARAVEL 11

```sql
-- Skema DDL Spesifik Modul Pelelangan Ikan (PostgreSQL / Core M-PAD)
CREATE TABLE tpi_transaksi_lelang (
    id VARCHAR(36) PRIMARY KEY,
    kode_transaksi VARCHAR(50) UNIQUE NOT NULL, -- TPI-WM/YYYYMMDD/XXXX
    kode_tpi VARCHAR(20) NOT NULL, -- TPI-WM-NEW, TPI-WM-OLD, TPI-BIMA
    waktu_transaksi TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    kapal_id VARCHAR(36),
    kapal_nama VARCHAR(150),
    kapal_gt DECIMAL(6,2),
    nomor_kusuka VARCHAR(50),
    bakul_id VARCHAR(36),
    bakul_nama VARCHAR(150),
    bakul_nik_nib VARCHAR(30),
    volume_lelang_kg DECIMAL(10,2) NOT NULL,
    nilai_transaksi_lelang DECIMAL(16,2) NOT NULL,
    biaya_fasilitas DECIMAL(16,2) NOT NULL DEFAULT 0.00,
    retribusi_lelang_persen DECIMAL(16,2) NOT NULL DEFAULT 0.00,
    total_retribusi DECIMAL(16,2) NOT NULL,
    status_bayar VARCHAR(20) NOT NULL CHECK (status_bayar IN ('LUNAS','TERUTANG')),
    metode_bayar VARCHAR(20) NOT NULL CHECK (metode_bayar IN ('QRIS','VA_BANK','TUNAI_OFFICER')),
    ntpd VARCHAR(50) UNIQUE,
    bank_ref VARCHAR(64),
    officer_user_id VARCHAR(36) NOT NULL,
    cash_settlement_batch_id VARCHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tpi_rincian_fasilitas (
    id VARCHAR(36) PRIMARY KEY,
    transaksi_lelang_id VARCHAR(36) NOT NULL REFERENCES tpi_transaksi_lelang(id),
    jenis_fasilitas VARCHAR(30) NOT NULL, -- TAMBAT_LABUH, SEWA_MEJA, SEWA_TIMBANGAN, ES_CURAH, PARKIR_TRUK
    qty DECIMAL(8,2) NOT NULL,
    satuan VARCHAR(20) NOT NULL,
    tarif_satuan DECIMAL(16,2) NOT NULL,
    subtotal DECIMAL(16,2) NOT NULL
);
```

- **Controller & Jobs Backend**:
  * `TpiTransactionController.php`: Endpoint transaksi mobile petugas & penerbitan NTPD.
  * `ReconcileTpiCashJob.php`: Validasi setoran tunai petugas lapangan (Officer Wallet) tutup shift T+0.
  * `CrossReconciliationPbjtJob.php`: Background job analitik membandingkan manifest pasokan ikan TPI dengan laporan SPTPD restoran seafood.

---

## BAB VIII & IX. FRAMEWORK 7-LANGKAH & PROTOKOL ANTI-FRAUD 3-LAPIS

- **Lapis 1 (Perbankan)**: Dual PIC notifikasi mutasi abnormal di luar jam kerja (23.00–04.00 WITA) & direct RKUD Bank Sultra.
- **Lapis 2 (Backend Core)**: Audit log tamper-evident (IP, user, microsecond, before-after), idempotency key, rate limit 30 req/menit.
- **Lapis 3 (Lapangan)**: Red Channel Trigger otomatis menerbitkan surat tugas pemeriksaan fisik jika volume lelang/omzet anjlok > 30%.
