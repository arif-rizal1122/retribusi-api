Viewed SKILL.md:1-37
Ran command: `find docs -maxdepth 2 -type f`
Viewed 07-status-dokumen-resmi.md:1-64
Viewed surveillance_tte.md:1-54
Created PEDOMAN_INTEGRASI_TTE_BSRE_DAN_BUKTI_LUNAS_MPAD.md
Created KOMPILASI_DOKUMEN_BUKTI_LUNAS_DAN_ASET_BER_TTE.md
Created IMPLEMENTASI_PLAN_TTE_BSRE_QUEUE_AIMAN_HERMES.md
Ran command: `ls -lh docs/01_regulasi_baubau/PEDOMAN_INTEGRASI_TTE_BSRE_DAN_BUKTI_LUNAS_MPAD.md docs/01_regulasi_baubau/KOMPILASI_DOKUMEN_BUKTI_LUNAS_DAN_ASET_BER_TTE.md docs/04_infrastruktur_api/IMPLEMENTASI_PLAN_TTE_BSRE_QUEUE_AIMAN_HERMES.md`

Seluruh kajian teknis, inventarisasi dokumen bukti lunas per OPD, templat naskah terhitung dengan formula Perda No. 1/2024, solusi transisi penandatanganan aset, arsitektur antrean (*queue*), serta tinjauan regulasi tata kelola pemerintahan telah dirangkum dan disimpan.

Dokumen acuan permanen telah dibuat dan dapat langsung diakses di workspace:
1. 📄 [PEDOMAN_INTEGRASI_TTE_BSRE_DAN_BUKTI_LUNAS_MPAD.md](file:///Users/pondokit/Herd/retribusi-api/docs/01_regulasi_baubau/PEDOMAN_INTEGRASI_TTE_BSRE_DAN_BUKTI_LUNAS_MPAD.md)
2. 📄 [KOMPILASI_DOKUMEN_BUKTI_LUNAS_DAN_ASET_BER_TTE.md](file:///Users/pondokit/Herd/retribusi-api/docs/01_regulasi_baubau/KOMPILASI_DOKUMEN_BUKTI_LUNAS_DAN_ASET_BER_TTE.md)
3. 📄 [IMPLEMENTASI_PLAN_TTE_BSRE_QUEUE_AIMAN_HERMES.md](file:///Users/pondokit/Herd/retribusi-api/docs/04_infrastruktur_api/IMPLEMENTASI_PLAN_TTE_BSRE_QUEUE_AIMAN_HERMES.md)

Dan terhubung dengan berkas kerja pada **[Folder Google Drive M-PAD Baubau](https://drive.google.com/drive/folders/1WgvliWvaCYJrHf4GZ4G8ycyM3n05Buan)**.

---

### 1. Keterkaitan TTE BSrE dengan Fase Terakhir User Journey M-PAD

Dalam *Tri-Perspective User Journey* M-PAD (Warga/Wajib Pajak, Petugas Lapangan, dan Admin OPD):
1. **Posisi Siklus**: TTE BSrE berada pada **Fase 5 (Final Settlement & Legal Document Issuance)**, tepat setelah rekonsiliasi pembayaran perbankan (Fase 4: SNAP BI / Bank Sultra / BRIVA / QRIS).
2. **Peran Yuridis Bukti Pelunasan**: 
   - Mengubah status ketetapan/piutang daerah menjadi penerimaan kas daerah sah yang tidak dapat disangkal (*non-repudiation*).
   - Menjadi syarat mutlak (*prerequisite*) bagi layanan lanjutan lintas instansi:
     - **e-SSPD BPHTB**: Syarat wajib pendaftaran hak tanah dan balik nama sertifikat di **Kantor Pertanahan (ATR/BPN)** dan PPAT.
     - **e-SSRD PBG**: Syarat penerbitan Sertifikat Izin Gedung di **DPMPTSP** dan pemutakhiran di portal **SIMBG Kementerian PUPR**.
     - **SIPAB / BAST Aset**: Syarat fisik pengeluaran alat berat dari UPTD Workshop Dinas PUPR ke lokasi proyek warga.
3. **Mekanisme Otomatis (Auto-TTE Hook)**:
   - Begitu webhook bank persepsi mengirim status `PAID`, sistem mengunci baris data (`lockForUpdate`), memverifikasi `idempotency_key`, mencatat **NTPD**, men-generate berkas PDF/A-1b, dan melempar job ke antrean Redis untuk dibubuhi **Segel Elektronik Instansi** dari BSrE dalam waktu `<3 detik` tanpa campur tangan manual.

---

### 2. Dokumen Resmi Bukti Lunas M-PAD per OPD & Contoh Templat

| No | OPD Pengampu | Objek / Layanan | Dokumen Penetapan Awal | Dokumen Resmi Bukti Lunas Ber-TTE | Skema TTE BSrE |
|:---|:---|:---|:---|:---|:---|
| 1 | **Bapenda** | PBB-P2, BPHTB, PBJT (Restoran, Hotel, Hiburan, Parkir, Listrik), Reklame, PAT, MBLB | e-SPPT, Draf e-SSPD BPHTB, e-SKPD, e-SPTPD | **e-SSPD** (Pajak) & **e-STTS** (PBB-P2) | **Segel Elektronik Otomatis** |
| 2 | **Dinas Lingkungan Hidup (DLH)** | Retribusi Sampah & Uji Lab Lingkungan | e-SKRD Sampah Bulanan, Tagihan Uji Sampel | **e-SSRD Persampahan** & **Laporan Hasil Uji (LHU) Sah** | Segel Elektronik (SSRD) & TTE Personal Kepala Lab (LHU) |
| 3 | **Dinas Perhubungan (Dishub)** | Parkir Tepi Jalan & Jasa Kepelabuhanan (Pas Masuk & Tambat Labuh) | e-SKRD Parkir, Billing Tambat Labuh | **e-SSRD Retribusi Dishub** & **Pas/Karcis Digital Ber-QRIS** | Segel Elektronik Otomatis |
| 4 | **Disperindag** | Retribusi Pasar (Kios/Los Wameo & Karya Nugraha) & RPH | e-SKRD Sewa Kios, Kartu Retribusi Harian Pasar | **e-SSRD Pelayanan Pasar** & **Kuitansi Sah Kios/RPH** | Segel Elektronik Otomatis |
| 5 | **Dinas PUPR** | Retribusi PBG & Uji Lab Aspal/Beton | e-SKRD PBG (Indeks SIMBG), Pengantar Uji Lab | **e-SSRD PBG** & **Sertifikat Hasil Pengujian Bahan** | Segel Elektronik (SSRD) & TTE Personal Kadis (Hasil Uji) |
| 6 | **BPKAD (Aset BMD)** | Pemanfaatan Gedung Daerah, Ruko/Kios Daerah, Tanah Pemda | SKP-Aset Daerah, Draf Perjanjian Sewa | **Surat Perjanjian Sewa Resmi BMD** & **SIPA-BMD** | **TTE Personal Kepala BPKAD** *(Fallback: Kode Numerik OPD)* |
| 7 | **Dinas PUPR (Alat Berat)** | Sewa Excavator, Wheel Loader, Tandem Roller, Dump Truck | SKRD Sewa Alat Berat, Draf Kontrak Sewa | **Surat Izin Pemakaian Alat Berat (SIPAB)** & **BAST Unit** | **TTE Personal Kadis PUPR** *(Fallback: Kode Numerik OPD)* |
| 8 | **Dinas Pariwisata** | Retribusi Benteng Wolio, Nirwana, Sewa Gedung Promosi | Tiket Rombongan, Tagihan Sewa Gedung | **e-SSRD Retribusi Pariwisata** & **Bukti Booking Sah** | Segel Elektronik Otomatis |
| 9 | **Dinas Perikanan** | Retribusi Pelelangan Ikan (TPI) & Tambat Labuh Nelayan | e-SKRD Lelang Berbasis Transaksi | **e-SSRD Pelayanan TPI** | Segel Elektronik Otomatis |
| 10 | **DPMPTSP** | Persetujuan Bangunan Gedung (PBG Final) | Validasi KSWP & e-SSRD PUPR | **Sertifikat Izin PBG Definitif Digital** | TTE Personal Kepala DPMPTSP |

---

### 3. Komponen Antarmuka (UI Companion) & Alur Klik Dokumen

Pada aplikasi Citizen ([retribusi-mobile](file:///Users/pondokit/Herd/retribusi-mobile)) dan Dashboard OPD ([retribusi-admin](file:///Users/pondokit/Herd/retribusi-admin)):
1. **Card Status Tagihan**: Saat pembayaran terkonfirmasi, tagihan berubah status menjadi badge hijau `[ LUNAS / TERVERIFIKASI ]` lengkap dengan timestamp pelunasan dan NTPD.
2. **Aksi Tombol Interaktif**:
   - Tombol **[ Unduh Dokumen Resmi Ber-TTE (PDF) ]**: Mengambil berkas PDF/A-1b yang telah disegel secara kriptografis oleh modul BSrE.
   - Tombol **[ Periksa Keabsahan BSrE ]**: Membuka halaman publik verifikasi e-Registry: `https://verify.baubaukota.go.id/doc/{nomor_dokumen}`.
3. **Pemberitahuan Otomatis**: Sistem mengirimkan tautan dokumen PDF resmi ke WhatsApp Wajib Pajak secara otomatis sesaat setelah pembayaran lunas.

---

### 4. Tata Kelola Penandatanganan Aset & Solusi Transisi "Kode Numerik Resmi OPD"

Khusus pengelolaan Barang Milik Daerah (sewa alat berat PUPR, pemanfaatan gedung/ruko BPKAD):
- **Dasar Aturan**: Dokumen perikatan sewa dan surat izin pemakaian memerlukan tanda tangan Kepala OPD selaku Pengguna/Pengelola Barang.
- **Kendala Lapangan**: Sebagian Kepala OPD belum memiliki sertifikat digital TTE BSrE aktif (sedang dalam antrean verifikasi atau permohonan susulan).
- **Mekanisme Transisi Fallback**:
  1. **Kode Registrasi Numerik Unik OPD**: M-PAD men-generate token unik alfanumerik (misal: `PUPR-AST-2026-09-A41F9820` atau `BPKAD-BMD-2026-09-8819CF41`) yang diikat ke tabel `asset_tokens` dan `signed_documents`.
  2. **Segel Elektronik Pemkot**: Berkas PDF tetap disegel di latar belakang dengan **Segel Elektronik Instansi Pemkot Baubau** via API BSrE (*tamper-evident*).
  3. **Klausul Pengesahan Dokumen**:
     > *"Dokumen ini disahkan oleh Kepala Dinas [Nama OPD] melalui Sistem Digital Terintegrasi M-PAD dengan Kode Registrasi Verifikasi OPD: [KODE_NUMERIK_RESMI]. Keabsahan dokumen dilindungi oleh Segel Elektronik Resmi Pemerintah Kota Baubau dan terdaftar pada E-Registry Pemkot Baubau."*
  4. Begitu akun sertifikat BSrE pejabat terbit, sistem otomatis mengaktifkan fitur *Single-Click TTE Personal* tanpa mengubah struktur data.

---

### 5. Implementation Plan di Assignment & Queue (IDE ↔ Aiman VPS ↔ Hermes SRE)

Penyelarasan tiket kerja dengan Leantime PMO (`leantime.sarjanakomputer.id`):
- **[SPARK-026]**: Spesifikasi Layout PDF/A-1b Bukti Lunas Lintas 8 OPD (`DONE`)
- **[SPARK-027]**: SOP Fallback "Kode Numerik Resmi OPD" untuk Aset (`DONE`)
- **[SPARK-028]**: Question Sheet & Agenda Konsultasi Zoom BSrE (`DONE`)
- **[AIMAN-039]**: Integrasi REST API Client BSrE pada `TTEService.php` (Antigravity IDE - PHP 8.4)
- **[AIMAN-040]**: Auto-TTE Hook pada Webhook Settlement (`retribusi-api`)
- **[AIMAN-041]**: Migrasi DB Tabel `signed_documents`, `tte_logs`, `asset_tokens`
- **[AIMAN-042]**: Integrasi Portal Verifikasi Publik E-Registry (`verify.baubaukota.go.id`)
- **[AIMAN-043]**: Update UI Card Transaksi & Tombol Download PDF Ber-TTE (React PWA)
- **[HERMES-010]**: Konfigurasi Supervisor Daemon Worker Queue `tte_pending_jobs` (Redis, 8 threads di VPS 157.10.252.74)
- **[HERMES-011]**: Telemetry Latensi API BSrE & Fallback Circuit Breaker (Timeout 3.000 ms)
- **[HERMES-012]**: Immutable Audit Log Protection & Encrypted PDF Storage

**Arsitektur Queue**:
Pembayaran bank diproses **sinkron (<800 ms)** agar tidak ada penundaan konfirmasi kas daerah. Setelah itu, berkas PDF dioperasikan secara **asinkron via Redis `tte_pending_jobs`** oleh Supervisor worker. Jika server BSrE mengalami *maintenance*, status bayar di kasda tetap **sah dan lunas**, sementara penandatanganan berkas dijadwalkan ulang secara otomatis (*graceful retry 3x*).

---

### 6. Kajian Yuridis: Apakah Ada Dokumen yang Berbeda Antara Pra-TTE vs Pasca-TTE?

> **Jawaban Tegas:** **Secara substansi hukum dan nilai kewajiban, dokumennya adalah dokumen yang SAMA (tetap e-SSPD, e-SSRD, STTS, atau Izin Pemanfaatan Aset). Namun, secara bentuk fisik, media pembuktian, dan mekanisme validasi, dokumennya BERUBAH TOTAL.**

Perubahan ini memang diatur dan diwajibkan secara tegas dalam regulasi tata kelola pemerintahan:

1. **Perubahan Fisik & Tata Kelola**:
   - **Era Pra-TTE (Konvensional)**: Berupa kertas fisik berlembar tindas (*NCR paper* rangkap 3-4: lembar putih pemohon, lembar merah bank, lembar kuning dinas, lembar hijau arsip) dengan tanda tangan basah dan cap stempel basah tinta ungu.
   - **Era Pasca-TTE (Digital M-PAD)**: Menjadi berkas digital tunggal berstandar **PDF/A-1b** (*Single Source of Truth*). Tanda tangan dan stempel basah digantikan oleh **Sertifikat Elektronik Kriptografis** yang tertanam di dalam metadata biner file dan direpresentasikan secara visual dengan **QR Code 2.5 x 2.5 cm** yang memuat tautan verifikasi publik.

2. **Payung Hukum & Regulasi yang Mewajibkan**:
   - **UU No. 11/2008 jo UU No. 1/2024 (UU ITE)**: Pasal 5 ayat (1) menyatakan dokumen elektronik dan hasil cetaknya adalah alat bukti hukum yang sah. Pasal 11 menjamin TTE memiliki akibat hukum yang sah.
   - **PP No. 71/2019 (PP PSTE)**: Pasal 52-54 mewajibkan setiap penyelenggara sistem elektronik di instansi pemerintah menggunakan sertifikat elektronik berinduk pada PSrE Pemerintah (BSrE BSSN).
   - **Perpres No. 95/2018 (Sistem Pemerintahan Berbasis Elektronik / SPBE)**: Menginstruksikan percepatan integrasi administrasi pemerintahan menuju *paperless office* serta pelarangan peredaran dokumen fisik yang tidak terintegrasi secara elektronik.
   - **Peraturan BSSN No. 10/2019**: Mengatur teknis pengesahan naskah dinas elektronik dan memberikan wewenang legal atas penggunaan **Segel Elektronik (Electronic Seal)** untuk layanan otomatis massal.
   - **UU No. 1/2022 (HKPD) & PP No. 35/2023 (KUPDRD)**: Mandat nasional Percepatan dan Perluasan Digitalisasi Daerah (P2DD) agar penetapan dan pembayaran pajak/retribusi daerah diterbitkan melalui sistem terpadu.
   - **Perda Kota Baubau No. 1/2024**: Memberikan mandat yurisdiksi kepada Bapenda dan OPD teknis Pemkot Baubau untuk menerbitkan dokumen penetapan dan bukti pelunasan digital yang sah.

---

### 7. Lembar Poin Rapat Zoom Konsultasi dengan BSrE Pusat (Hari Ini, 09:00 WITA)

- **Meeting ID**: `794 650 2742` | **Passcode**: `baubau01`
- **Tautan**: [Join Zoom Meeting BSrE & Pemkot Baubau](https://us06web.zoom.us/j/7946502742?pwd=ZPR86w6gBNIbb0SYYtE7Lt9EYJwIV4.1&omn=81058679048)

**4 Pertanyaan Kunci untuk Ditanyakan ke Tim Teknis BSrE:**
1. **Penyematan Segel Elektronik Otomatis (Server-to-Server / Seal)**: Mengingat pembayaran pajak dan retribusi berlangsung 24 jam real-time pasca-settlement bank, mohon konfirmasi bahwa akun instansi Pemkot Baubau dapat memanfaatkan API Segel Elektronik tanpa input passphrase manual per transaksi.
2. **Kapasitas Throughput & Kuota Sandbox**: Menjelang periode jatuh tempo massal PBB-P2 dan PBJT (Agustus–September), berapa limit request per detik (rate limit) dan concurrent request yang dialokasikan BSrE?
3. **Format Naskah & Visual Signature**: M-PAD telah menstandarkan visual stamp berupa QR Code ukuran 2.5 x 2.5 cm di pojok kanan bawah PDF/A-1b. Apakah ada ketentuan koordinat khusus dari BSrE atau sepenuhnya diserahkan kepada tata letak blade template daerah?
4. **Jalur Percepatan Sertifikat TTE Kepala OPD**: Mohon asistensi jalur percepatan penerbitan sertifikat TTE perorangan bagi para Kepala OPD teknis Pemkot Baubau (Kadis PUPR, BPKAD, Disperindag, Dishub, Pariwisata, DLH) melalui Diskominfo Kota Baubau untuk keperluan pengesahan naskah sewa aset BMD.