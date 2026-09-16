# 🏛️ PEDOMAN INTEGRASI TTE BSRE & PENERBITAN BUKTI LUNAS RESMI M-PAD KOTA BAUBAU

Dokumen ini merupakan pedoman arsitektur hukum, proses bisnis, dan integrasi teknis antara **Sistem M-PAD (Pemerintah Kota Baubau)** dengan **Balai Sertifikasi Elektronik (BSrE - BSSN)** sebagai bahan resmi konsultasi pra-integrasi tanggal 17 September 2026.

---

## 1. Posisi Yuridis TTE dalam User Journey M-PAD (Fase Terakhir / Pelunasan)

### A. Siklus Hulu-ke-Hilir Transaksi Daerah
Dalam arsitektur *Tri-Perspective User Journey* M-PAD (Warga/Wajib Pajak, Petugas Lapangan, dan Admin Pengawas OPD), integrasi TTE BSrE berada pada **Fase 5 (Final Settlement & Legal Document Issuance)**:
1. **Fase 1: Pendaftaran & Pendataan (Registration & Assessment)**
   - Warga/Petugas menginput data objek dan subjek pajak/retribusi melalui Smart Form / Auto-Fill GIS.
2. **Fase 2: Penetapan & Penerbitan Tagihan (Billing / Assessment)**
   - Terbit dokumen penetapan awal: **e-SPPT** (PBB-P2), **e-SKPD** (Pajak Daerah), atau **e-SKRD** (Retribusi Daerah).
3. **Fase 3: Transaksi Pembayaran (Payment Ingestion)**
   - Warga membayar melalui kanal perbankan: SNAP BI Direct Debit, VA Bank Sultra, BRIVA, QRIS Dinamis, atau Loket Resmi.
4. **Fase 4: Settlement & Idempotency Lock (Financial Reconciliation)**
   - Webhook perbankan mengirim sinyal `PAID`.
   - Core API mengeksekusi `lockForUpdate()`, memverifikasi `idempotency_key`, mencatat Nomor Transaksi Penerimaan Daerah (**NTPD**), dan mengubah status menjadi **LUNAS**.
5. **Fase 5: Penerbitan Naskah Resmi Bukti Lunas Ber-TTE (Legal Finality)**
   - **Auto-TTE Hook** memicu pembentukan dokumen PDF/A-1b dan memanggil modul E-Sign BSrE.
   - Dokumen disahkan dengan **Segel Elektronik Instansi (Electronic Seal)** atau **TTE Personal Kepala OPD**.
   - Dokumen masuk ke **E-Registry** (`signed_documents`) dan langsung tersedia untuk diunduh warga serta divalidasi publik melalui QR Code.

### B. Nilai Hukum & Keterkaitan Lintas Sektor
Dokumen bukti lunas ber-TTE ini bukan sekadar kuitansi pembayaran, melainkan:
- **Alat Bukti Hukum yang Sah (Non-Repudiation)** sesuai Pasal 5 ayat (1) & Pasal 11 UU ITE.
- **Prasyarat Layanan Lanjutan (Cross-Agency Interlock)**:
  - **e-SSPD BPHTB**: Syarat mutlak pendaftaran hak atas tanah, balik nama sertifikat di Kantor Pertanahan (ATR/BPN), dan pelaporan PPAT/Notaris.
  - **e-SSRD PBG**: Syarat penerbitan Sertifikat Izin Bangunan Gedung di DPMPTSP dan SIMBG Kementerian PUPR.
  - **SIPAB / BAST Aset PUPR**: Syarat pelepasan unit alat berat dari Workshop UPTD ke lokasi proyek pemohon.
  - **SIPA Aset BPKAD**: Syarat serah terima kunci pemakaian gedung pertemuan / ruko daerah.

---

## 2. Inventarisasi Dokumen Bukti Lunas Ber-TTE per OPD

| No | OPD Pengampu | Objek / Layanan | Dokumen Penetapan Awal | Dokumen Resmi Bukti Lunas Ber-TTE | Skema TTE BSrE |
|:---|:---|:---|:---|:---|:---|
| 1 | **Bapenda** | PBB-P2, BPHTB, PBJT (Hotel, Restoran, Hiburan, Parkir, Listrik), Reklame, PAT, MBLB, Sarang Walet | e-SPPT, Draf e-SSPD BPHTB, e-SKPD, e-SPTPD | **e-SSPD** (Surat Setoran Pajak Daerah) & **STTS** (Surat Tanda Terima Setoran) | **Segel Elektronik Otomatis (Auto-Seal)** |
| 2 | **Dinas Lingkungan Hidup (DLH)** | Retribusi Sampah (Rumah Tangga, Niaga, Faskes) & Uji Lab Lingkungan | e-SKRD Sampah Bulanan, Tagihan Uji Sampel | **e-SSRD Persampahan** & **Laporan Hasil Uji (LHU) Lingkungan Sah** | Segel Elektronik (SSRD) & TTE Personal Kepala Lab (LHU) |
| 3 | **Dinas Perhubungan (Dishub)** | Parkir Tepi Jalan, Tempat Khusus Parkir, Jasa Kepelabuhanan (Pas Masuk & Tambat Labuh) | e-SKRD Parkir / Pas Dermaga, Billing Tambat Labuh | **e-SSRD Retribusi Dishub** & **Karcis / Pas Digital Ber-QRIS** | Segel Elektronik Otomatis |
| 4 | **Disperindag** | Retribusi Pelayanan Pasar (Kios/Los Pasar Wameo, Karya Nugraha, dll) & RPH | e-SKRD Sewa Kios Bulanan, Kartu Retribusi Harian Pasar, Karcis RPH | **e-SSRD Pelayanan Pasar** & **Kuitansi Sah Pemakaian Kios/RPH** | Segel Elektronik Otomatis |
| 5 | **Dinas PUPR** | Retribusi PBG & Pengujian Bahan Konstruksi/Lab Aspal Beton | e-SKRD PBG (Indeks SIMBG), Pengantar Uji Lab | **e-SSRD PBG** & **Sertifikat Hasil Pengujian Bahan Konstruksi** | Segel Elektronik (SSRD) & TTE Personal Kadis (Hasil Uji) |
| 6 | **BPKAD (Aset BMD)** | Pemanfaatan Gedung Daerah, Ruko/Kios Daerah, Tanah/Lahan Pemda | SKP-Aset Daerah, Draf Perjanjian Sewa | **Surat Perjanjian Sewa Resmi BMD** & **Surat Izin Pemanfaatan Aset (SIPA)** | **TTE Personal Kepala BPKAD** *(Fallback: Kode Numerik OPD)* |
| 7 | **Dinas PUPR (Alat Berat)** | Sewa Alat Berat (Excavator, Wheel Loader, Tandem Roller, Dump Truck) | SKRD Sewa Alat Berat, Draf Kontrak Sewa Alat | **Surat Izin Pemakaian Alat Berat (SIPAB)** & **BAST Unit Resmi** | **TTE Personal Kadis PUPR** *(Fallback: Kode Numerik OPD)* |
| 8 | **Dinas Pariwisata** | Retribusi Wisata Budaya (Benteng Wolio, Nirwana), Sewa Gedung Promosi | Tiket Rombongan / Tagihan Sewa Gedung | **e-SSRD Retribusi Pariwisata** & **Bukti Reservasi Sah** | Segel Elektronik Otomatis |
| 9 | **Dinas Perikanan** | Retribusi Pelelangan Ikan (TPI) & Tambat Labuh Kapal Nelayan | e-SKRD Lelang Berbasis Transaksi | **e-SSRD Pelayanan TPI** / Kuitansi Sah Retribusi Perikanan | Segel Elektronik Otomatis |
| 10 | **DPMPTSP** | Izin Persetujuan Bangunan Gedung (PBG Final) | Validasi KSWP & e-SSRD PUPR | **Sertifikat Izin PBG Definitif Digital** | TTE Personal Kepala DPMPTSP |

---

## 3. Tata Kelola Penandatanganan Aset & Solusi Transisi "Kode Numerik Resmi OPD"

### A. Latar Belakang Masalah Lapangan
Khusus pemanfaatan Barang Milik Daerah (BMD) seperti sewa ruko/kios, gedung pertemuan BPKAD, dan sewa alat berat PUPR:
1. **Aspek Legalitas**: Dokumen perikatan sewa dan surat izin pemakaian memerlukan tanda tangan pejabat penanggung jawab barang (Kepala OPD).
2. **Kondisi Riil**: Sebagian Kepala OPD belum memiliki akun/sertifikat TTE BSrE aktif (belum sempat diajukan atau sedang dalam proses antrean verifikasi di Diskominfo/BSrE).
3. **Risiko Pelayanan**: Pelayanan masyarakat tidak boleh terhenti hanya karena sertifikat digital perorangan pejabat belum terbit.

### B. Solusi Transisi Terpadu (Dual-Layer Verification)
1. **Lapisan 1: Secure Numeric Verification Token (Kode Registrasi Unik OPD)**
   - Sistem M-PAD men-generate kode unik alfanumerik 16-karakter dengan format:  
     `[KODE_OPD]-AST-[TAHUN]-[SEKUENS]-[HASH]`  
     *Contoh*: `PUPR-AST-2026-09-A41F9820` atau `BPKAD-BMD-2026-09-8819CF41`.
   - Kode ini diikat secara kriptografis dengan ID perikatan di tabel `asset_tokens` dan `signed_documents`.
2. **Lapisan 2: Segel Elektronik Instansi (Institutional Electronic Seal Fallback)**
   - File PDF tetap disegel di latar belakang oleh sistem menggunakan **Segel Elektronik Pemerintah Kota Baubau** via API BSrE. Dokumen berstatus *tamper-evident* (tidak bisa dimanipulasi).
3. **Klausul Naskah Transisi**:
   ```
   "Dokumen ini disahkan oleh Kepala Dinas [Nama OPD] Kota Baubau 
   melalui Sistem Digital Terintegrasi M-PAD dengan Kode Registrasi Verifikasi OPD: 
   [KODE_NUMERIK_RESMI]. 
   Keabsahan dokumen dilindungi oleh Segel Elektronik Resmi Pemerintah Kota Baubau 
   dan terdaftar secara sah pada E-Registry Pemkot Baubau."
   ```
4. **Migrasi Mulus**: Begitu sertifikat Kepala OPD terbit di BSrE, sistem otomatis beralih ke Single-Click TTE Personal pejabat tanpa merombak arsitektur basis data.

---

## 4. Tinjauan Regulasi: Dokumen Pra-TTE vs Pasca-TTE

### A. Perbandingan Dimensi Dokumen
| Dimensi | Sebelum Ada TTE (Konvensional) | Setelah Terintegrasi TTE BSrE (M-PAD) |
|:---|:---|:---|
| **Media Berkas** | Kertas berlembar tindas (NCR paper 3-4 rangkap). | Digital tunggal berstandar ISO 19005-1 (**PDF/A-1b**). |
| **Pengesahan** | Tanda tangan basah dan stempel tinta ungu manual. | Sertifikat Elektronik tersertifikasi BSSN tertanam dalam metadata PDF. |
| **Validasi Publik** | Cek manual buku register fisik di loket dinas. | Scan QR Code 2.5 x 2.5 cm langsung ke `verify.baubaukota.go.id`. |
| **Integritas Berkas** | Rentan dipalsukan (tipex, cetak ulang palsu). | Berkas terkunci SHA-256 + RSA-2048; perubahan 1 karakter merusak segel. |
| **Waktu Terbit** | 1 s.d. 3 hari kerja menunggu tanda tangan pejabat. | **Instan (<3 detik)** pasca settlement webhook perbankan. |

### B. Landasan Yuridis Nasional & Daerah
Penerbitan dokumen elektronik ber-TTE diatur dan dilindungi oleh:
1. **UU No. 11/2008 jo UU No. 1/2024 (UU ITE)**: Pasal 5 (alat bukti hukum sah) & Pasal 11 (kekuatan hukum TTE).
2. **PP No. 71/2019 (PP PSTE)**: Kewajiban instansi pemerintah menggunakan Sertifikat Elektronik PSrE Pemerintah (BSrE BSSN).
3. **Perpres No. 95/2018 (SPBE)**: Integrasi layanan administrasi pemerintahan dan penghapusan peredaran dokumen kertas.
4. **Peraturan BSSN No. 10/2019**: Standar pelaksanaan pengesahan dokumen dan pemanfaatan Segel Elektronik untuk layanan publik massal.
5. **UU No. 1/2022 (HKPD) & PP No. 35/2023 (KUPDRD)**: Percepatan digitalisasi penerbitan ketetapan dan bukti pembayaran pajak/retribusi daerah.
6. **Perda Kota Baubau No. 1/2024**: Penegasan wewenang pemungutan, penetapan, dan penerbitan bukti lunas elektronik terpadu di Kota Baubau.
