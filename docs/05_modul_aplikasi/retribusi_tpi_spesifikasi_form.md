# 🐟 MATRIKS SPESIFIKASI FORM PENDATAAN TPI / PELELANGAN IKAN
## Dinas Perikanan & Kelautan Kota Baubau — Sistem M-PAD
*Rujukan: Analisis Kebutuhan Operasional Lapangan TPI Wameo & TPI Bima*

---

## 1. Catatan Pengembang (Developer Notes)
- **Validasi Frontend**: Pastikan checkbox fasilitas mengunci (*disable*) input angka terkait jika dalam keadaan tidak dicentang (*unchecked*).
- **Integrasi Service**: Komponen *Metode Pembayaran* langsung terhubung dengan modul API *Payment Gateway* M-PAD untuk menerbitkan kode pembayaran secara *real-time*.

---

## 2. Matriks Spesifikasi Field UI Form

| No | Nama Field (UI Label) | Tipe Komponen UI | Tipe Data & Format | Sifat | Logika Bisnis & Validation Rules |
|:---|:---|:---|:---|:---|:---|
| **1** | **Header Transaksi** | | | | |
| 1.1 | Kode TPI / Pelabuhan | Dropdown | String (Code) | Wajib | Select dari Master Data TPI (misal: TPI Bima, TPI Wameo). Default berdasarkan lokasi user login. |
| 1.2 | Tanggal & Waktu | Auto-system (Read-only) | Datetime (YYYY-MM-DD HH:mm:ss) | Auto | Generated otomatis oleh server saat draft form dibuat. |
| 1.3 | ID Nelayan / Kapal | QR Scanner / Text Input | String | Wajib | Auto-populate data Kapal (Nama & GT) jika scan QR berhasil. Manual input sebagai fallback. |
| 1.4 | ID Bakul / Pembeli | QR Scanner / Text Input | String | Wajib | Auto-populate data Pembeli (Nama & NIK/NIB) jika scan QR. Manual input sebagai fallback. |
| **2** | **Fasilitas Terpakai** | | | | |
| 2.1 | Jasa Tambat Labuh | Checkbox + 2 Field Numeric | Decimal (GT) & Integer (Hari) | Opsional | Aktif jika dicentang. Subtotal = GT × Hari × Tarif Perda. |
| 2.2 | Sewa Meja Lelang | Checkbox + Field Numeric | Integer (Qty) | Opsional | Aktif jika dicentang. Subtotal = Qty Meja × Tarif Meja. |
| 2.3 | Sewa Timbangan | Checkbox + Field Numeric | Integer (Qty) | Opsional | Aktif jika dicentang. Subtotal = Qty Timbangan × Tarif Timbangan. |
| 2.4 | Jasa Pasokan Es | Checkbox + Field Numeric + Dropdown Unit | Decimal (Qty) & Enum (Batang, Ton) | Opsional | Aktif jika dicentang. Subtotal = Qty × Tarif Satuan Es. |
| 2.5 | Parkir Truk Pendingin | Checkbox + Dropdown Jenis + Field Numeric | Enum (Golongan) & Integer (Durasi) | Opsional | Aktif jika dicentang. Subtotal = Golongan × Durasi × Tarif. |
| **3** | **Output Otomatis & Pembayaran** | | | | |
| 3.1 | Total Volume Lelang | Input Numeric | Decimal (Kg) | Wajib | Berat total hasil lelang. Digunakan untuk statistik & pelaporan. |
| 3.2 | Nilai Transaksi Lelang | Currency Input | Decimal (Rp) | Wajib | Nilai rupiah transaksi lelang. Digunakan sebagai basis persentase retribusi lelang (1%). |
| 3.3 | Total Retribusi TPI | Currency Display (Read-only) | Decimal (Rp) | Auto | Calculation trigger: Jumlah seluruh subtotal fasilitas + retribusi lelang. |
| 3.4 | Metode Pembayaran | Radio Button / Dropdown | Enum (QRIS, VA_BANK, CASHLESS) | Wajib | Memicu pembuatan kuitansi digital / kode billing pembayaran (Host-to-Host). |

---

## 3. Skenario Operasional di Lapangan

### Skenario 1: Transaksi Skala Besar (Kapal Motor & Tengkulak Utama)
- **Objek**: Kapal Motor KM Sinar Laut (15 GT) bersandar di TPI. Pembeli (Bakul) H. Aris (PT Laut Murni).
- **Fasilitas**: Tambat Labuh (15 GT x 2 Hari), Sewa Meja (4 Unit), Sewa Timbangan (2 Unit), Pasokan Es (1,5 Ton), Parkir Truk Pendingin (1 Unit Truk Sedang).
- **Hasil Lelang**: Volume 2.500 Kg, Nilai Transaksi Rp 50.000.000.
- **Kalkulasi**: Retribusi Fasilitas Rp 205.000 + Retribusi Lelang 1% (Rp 500.000) = **Total Retribusi Rp 705.000**.
- **Kanal Pembayaran**: QRIS Dinamis Bank Sultra / SNAP Multi-Bank langsung terverifikasi lunas seketika.

### Skenario 2: Transaksi Skala Kecil (Nelayan Tradisional / Katinting)
- **Objek**: Nelayan Katinting Pak La Ode (1 GT), pembeli pedagang eceran Mama Wa Ode.
- **Fasilitas**: Sewa Meja (1 Unit), Sewa Timbangan (1 Unit).
- **Hasil Lelang**: Volume 80 Kg, Nilai Transaksi Rp 1.600.000.
- **Kalkulasi**: Total Terutang **Rp 41.000**.
- **Kanal Pembayaran**: Scan QRIS ponsel di layar tablet petugas M-PAD (< 1 menit).
