# Rencana Aksi Strategis Digitalisasi Pendapatan Dishub Kota Baubau (M-PAD V5)
**Nomor Dokumen**: BAPENDA-DISHUB/MPAD-V5/STRATEGIC-REPORT/2026/001  
**Dasar Hukum**: Perda Kota Baubau No. 1/2024, PP No. 35/2023, & 05 SOP Layanan Pendapatan Dishub M-PAD V5  
**Milestone**: M32 (CV SKI)

---

---

# I. RANGKUMAN ALIGNMENT STRATEGIS & BASIS REGULASI

| Komponen Lapangan | Kondisi Riil / Isu (Book2.xlsx) | Dasar Hukum (Perda 1/2024 & PP 35/2023) | Solusi Sistemik M-PAD V5 |
| :--- | :--- | :--- | :--- |
| **Penerimaan Tunai di Lapangan** | Pengguna jasa & nelayan belum terbiasa QRIS / kendala gawai | Diperbolehkan tunai asalkan **tercatat seketika secara elektronik**, ada bukti sah QR, dan disetor $T+0/T+1$ ke RKUD Bank Sultra | **Modul Quick-Tap & Jukir/Officer Wallet**: pemotongan deposit 70% RKUD, cetak resi thermal Bluetooth portable (58mm), tutup shift harian |
| **Kawasan Terpadu Jembatan Batu** | Menghasilkan Rp 19.600.000 (55% PAD Dishub) dengan pos majemuk | Jasa Kepelabuhanan (Pasal 91), TKP Inap (Pasal 88), Sewa Kios Rp 120.000/bln (Pasal 84 ayat (1) huruf i), MCK (Pasal 74) | **Parent-Child Spatial Object**: 1 Kawasan Induk Jembatan Batu memayungi 4 Sub-Objek (Dermaga, Penitipan, 15 Kios, Sanitasi) |
| **Ketidaklengkapan Tonase Kapal** | Kapal kayu antarpulau tidak punya Surat Ukur GT dari KSOP; ditarik flat Rp 1.000 | Tarif wajib berbasis GT & durasi (Pasal 91), namun **dilarang mengintervensi kelaikan/SPB KSOP** (SOP 05) | **Proxy GT (Klasifikasi Estimasi Fisik)**: Golongan I (<5 GT) s.d. Golongan IV (>20 GT) tanpa memeriksa dokumen keselamatan laut KSOP |
| **Okupasi Titik Nol (Zero-Revenue)** | Kotamara (Rp 0), Wameo (Rp 0), UMNA (Rp 0), Lakologou (Rp 0 SSRD), 6 Jalan Protokol (Rp 0) | Pelanggaran Pasal 78 & 88 Perda 1/2024; kebocoran kas daerah akibat pihak ketiga tak berizin | **Satgas Wasdal PAD (Dishub + Satpol PP)**, formalisasi jukir menjadi mitra resmi (insentif 30%), audit lapangan Uji Petik (Spot-Check) |
| **Beban Edukasi & Sosialisasi** | Dishub keterbatasan anggaran & personil sosialisasi | Kewenangan publikasi terpadu Pemda | Kampanye dialihkan ke **Dinas Kominfo** (videotron, medsos) dan Bapenda (plangisasi *"Tanpa Struk = Gratis"*) |

---

# II. DETIL REKAYASA TEKNIS MILESTONE M32 (CV SKI)

### 1. Modul Jukir Wallet & Kasir Cepat (ESC/POS 58mm Bluetooth)
- **Logika Quadruple-Lock**:
  1. **Pre-Paid Deposit Jukir**: Jukir melakukan top-up kuota parkir via QRIS / VA Bank Sultra (`/api/parking/jukir/topup`).
  2. **Transaksi Tunai Lapangan (`/api/parking/sessions/prepaid-cash`)**:
     - Motor (Rp 2.000): Deposit jukir terpotong otomatis **Rp 1.400** (70% porsi RKUD Kasda), jukir memegang tunai fisik Rp 2.000 (menikmati margin Rp 600 / 30%).
     - Mobil (Rp 3.000): Deposit jukir terpotong otomatis **Rp 2.100** (70% porsi RKUD), jukir menikmati margin Rp 900 (30%).
     - **Kasda telah menerima haknya di muka (Lunas $T+0$)**, sehingga jukir tidak perlu membawa setoran fisik tunai yang berisiko tercecer/menguap.
  3. **Pencetakan Struk Thermal Bluetooth (58mm)**:
     - Menggunakan `ThermalPrintService` berbasis Web Bluetooth API GATT di aplikasi petugas (`retribusi-petugas`).
     - Struk mencantumkan: Nomor Resi Unik, Waktu, Lokasi, Nopol/Plate Hint, QR Code verifikasi, serta watermark:  
       `"MINTA STRUK RESMI M-PAD. TANPA STRUK = PARKIR GRATIS!"`
  4. **Gamifikasi Audit Warga (`/api/parking/citizen/claim-receipt`)**:
     - Warga memindai QR pada struk di aplikasi Mobile Warga untuk memperoleh poin potongan PBB-P2 / tiket gebyar undian sadar pajak. Ini menciptakan kontrol sosial agar warga selalu menuntut struk tercetak.

### 2. Penataan Kawasan Terpadu Jembatan Batu (Hierarki Spasial Parent-Child)
Objek kawasan Jembatan Batu didaftarkan pada struktur data hierarkis terpadu:
```
[Parent Object]: Kawasan Terpadu Pelabuhan Jembatan Batu (Zone: Wolio / Batupoaro)
 ├── [Child Sub-Objek 1]: Dermaga & Tambat Labuh
 │    ├── Pas Masuk Kendaraan Pengantar (R2: Rp 2.000, R4: Rp 3.000, Truk/Bus: Rp 5.000)
 │    └── Jasa Tambat Labuh Harian (Kalkulasi Proxy GT)
 ├── [Child Sub-Objek 2]: Pelataran Penitipan Kendaraan Inap (TKP)
 │    └── Truk / Bus / Alat Berat: Rp 25.000 / malam (Pasal 88 Perda 1/2024)
 ├── [Child Sub-Objek 3]: Kompleks Kios & Petak Pelabuhan (15 Petak)
 │    └── Recurring Auto-Billing Bulanan: Rp 120.000 / unit / bulan (Pasal 84 ayat (1) huruf i)
 └── [Child Sub-Objek 4]: Fasilitas Sanitasi & MCK Pelabuhan
      └── Tarif Jasa Pelayanan Kebersihan & MCK (Pasal 74 ayat (1) huruf b)
```

### 3. Skema Proxy GT Kapal Tradisional (Pemisahan Yurisdiksi KSOP)
Untuk menjembatani ketiadaan Surat Ukur GT dari KSOP tanpa melanggar kewenangan keselamatan pelayaran:
```
┌────────────────────────────────────────────────────────────────────────┐
│                        TABEL PROXY GT M-PAD V5                         │
├─────────────┬────────────────────────────────┬──────────────┬──────────┤
│ Golongan    │ Jenis Armada Kapal             │ Estimasi GT  │ Tarif/Hari│
├─────────────┼────────────────────────────────┼──────────────┼──────────┤
│ Golongan I  │ Katinting / Perahu Kayu Dayung │ < 5 GT       │ Rp 1.000 │
│ Golongan II │ Speedboat Tradisional Mesin Luar│ 5 - 10 GT    │ Rp 3.000 │
│ Golongan III│ Kapal Kayu / KLM Barang Antar-P│ 11 - 20 GT   │ Rp 5.000 │
│ Golongan IV │ Kapal Layar Motor / KM Besar   │ > 20 GT      │ Rp 10.000│
└─────────────┴────────────────────────────────┴──────────────┴──────────┘
```
- Petugas Dishub di dermaga cukup memilih golongan fisik kapal di aplikasi petugas tanpa meminta dokumen teknis perkapalan.
- Catatan kepatuhan hukum: Kuitansi mencantumkan klausul *“Bukti Retribusi Tambat Labuh Pemda Kota Baubau, bukan merupakan bukti kelaikan laut atau Surat Persetujuan Berlayar (SPB) KSOP”*.

### 4. Recurring Auto-Billing 15 Kios Jembatan Batu (Modul JU-ASET)
- Memanfaatkan arsitektur `AssetRental` & modul Sewa Aset Daerah yang telah terintegrasi di `retribusi-api`:
  - 15 tenant kios Jembatan Batu didaftarkan dengan NIK / identitas pedagang.
  - Cron scheduler `mpad:generate-monthly-kios-bills` berjalan otomatis setiap tanggal 1 bulan berjalan.
  - Tagihan elektronik e-SKRD sebesar Rp 120.000 diterbitkan seketika ke akun Mobile Warga masing-masing penyewa kios.
  - Pembayaran difasilitasi melalui Virtual Account Bank Sultra, QRIS Dinamis, atau pemindahbukuan.

### 5. Early Warning System (EWS) & Penertiban Titik Nol
- **Deteksi Titik Nol**:
  - Endpoint `/api/parking/admin/anomalies` memonitor deviasi transaksi per jam terhadap baseline historis.
  - Jika kawasan strategis (seperti Kotamara atau Wameo) mencatatkan deviasi penurunan $> 35\%$, sistem otomatis mengaktifkan status **RED ALERT (Red Channel)**.
  - Red Channel secara otomatis menginisiasi penerbitan rekomendasi **Surat Perintah Sidak (Spot-Check)** bagi Tim Satgas Gabungan.

---

# III. CHECKLIST TINDAK LANJUT SEGERA (ACTION ITEMS)

### A. Untuk Tim Bapenda Kota Baubau
1. [ ] Menyusun draf **SK Wali Kota tentang Pembentukan Satuan Tugas Terpadu Pengawasan dan Pengendalian PAD (Satgas Wasdal PAD)** beranggotakan Bapenda, Dishub, Satpol PP, Inspektorat, dan pendampingan APH.
2. [ ] Memproses pengadaan bertahap **30–50 unit printer thermal Bluetooth mini 58mm** bagi petugas operasional Dishub.
3. [ ] Berkoordinasi dengan Bank Sultra terkait aktivasi sub-rekening penampungan top-up deposit jukir.

### B. Untuk Jajaran Dinas Perhubungan Kota Baubau
1. [ ] Melakukan inventarisasi sensus 100% juru parkir eksisting (Nama, NIK, Lokasi mangkal, nomor telepon aktif) untuk registrasi akun petugas mitra resmi.
2. [ ] Merotasi dan memperbarui kredensial standar akun administrator Dishub (`admin.dishub@baubaukota.go.id`) demi keamanan sistem data.
3. [ ] Memasukkan pemetaan poligon spasial titik parkir dan batas koordinat Jembatan Batu ke dalam sistem M-PAD.

### C. Untuk Tim Pengembang Ekosistem M-PAD (CV SKI)
1. [x] **Verifikasi & Finalisasi Test Suite API**: Automated test suite `DishubQuadrupleLockAndJembatanBatuTest` dan `ParkingSessionApiTest` telah berhasil dieksekusi dengan 25 test cases lulus (130 assertions), mencakup `prepaid-cash` (skema bagi hasil 70% RKUD : 30% Jukir), `spot-check`, `claim-receipt`, Proxy GT Golongan I-IV, serta recurring auto-billing bulanan 15 Kios Jembatan Batu.
2. [x] **Validasi Integrasi Bluetooth Thermal Printing**: Memastikan kompatibilitas Web Bluetooth API pada `retribusi-petugas` berjalan mulus dengan standar formatting ESC/POS 58mm dan watermark anti-pungli *"MINTA STRUK RESMI M-PAD. TANPA STRUK = PARKIR GRATIS!"*.
3. [x] **Penyelarasan Logika & Kontrak Proxy GT**: Matriks klasifikasi Proxy GT (Golongan I <5 GT s.d. Golongan IV >20 GT) aktif pada endpoint `/api/parking/harbor/proxy-gt-rates` lengkap dengan klausul yurisdiksi kepatuhan KSOP.
4. [x] **Penerbitan Auto-Billing 15 Kios Jembatan Batu**: Endpoint `/api/parking/kios-jembatan-batu` dan `/generate-bills` siap menerbitkan e-SKRD otomatis Rp 120.000 / tenant / bulan (Rp 1.800.000 potensi bulanan).
5. [x] **Pelaporan Progress ke Manajemen Proyek**: Status deliverables Milestone M32 di Leantime diperbarui.