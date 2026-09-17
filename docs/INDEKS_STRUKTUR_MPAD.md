# INDEKS MASTER STRUKTUR DOKUMEN M-PAD (GOOGLE DRIVE)
*Pembaruan Terakhir: September 2026 | Tim Ahli Ekosistem Digital M-PAD Bapenda Kota Baubau*

Dokumen ini merupakan peta navigasi dan indeks menyeluruh untuk seluruh arsip, berkas kebijakan, integrasi perbankan, pengadaan, data perpajakan, serta rekaman pengujian sistem M-PAD yang tersimpan di Google Drive (`gdrive:M-PAD/`).

---

## 🏛️ 9 KLASTER UTAMA REPOSITORI M-PAD

```
gdrive:M-PAD/
├── 00_KEBIJAKAN_DAN_CETAK_BIRU/
├── 01_REGULASI_DAN_DASAR_HUKUM/
├── 02_INTEGRASI_BANK_DAN_API/
├── 03_PENGADAAN_PERSURATAN_DAN_ADMINISTRASI/
├── 04_DATA_PAJAK_DAN_OBJEK/
├── 05_DOKUMEN_SISTEM_DAN_KNOWLEDGE_BASE/
├── 06_ARSIP_QA_DAN_TESTING_E2E/
├── 07_ARSIP_RAPAT_DAN_BRIEFING/
└── 08_ARSIP_HISTORIS_DAN_BACKUP/
```

---

### 📂 KLASTER 00: KEBIJAKAN DAN CETAK BIRU (`00_KEBIJAKAN_DAN_CETAK_BIRU/`)
Fokus pada cetak biru makro transformasi digital Kota Baubau dan perancangan strategis sistem perpajakan daerah:
- **`00_BLUEPRINT_DAN_USULAN_RESTRUKTURISASI_FOLDER_MPAD.docx`**: Proposal restrukturisasi dokumen dan repositori ekosistem M-PAD.
- **`Arsitektur Teknis Integrasi TTE BSrE dan MITRA M-PAD Baubau.docx`**: Spesifikasi integrasi Tanda Tangan Elektronik tersertifikasi BSrE BSSN.
- **`Blueprint_Digitalisasi_PAD/`**: Masterplan dan strategi optimalisasi Pendapatan Asli Daerah.
- **`DOKUMEN_KONSOLIDASI_MPAD/`**: Berkas konsolidasi lintas platform (API, Web Admin, Mobile Citizen, Petugas).
- **`MASUKAN_PAK_MASSAD/`**: Catatan telaah dan arahan kebijakan pimpinan Bapenda.

---

### 📂 KLASTER 01: REGULASI DAN DASAR HUKUM (`01_REGULASI_DAN_DASAR_HUKUM/`)
Memuat seluruh instrumen hukum yang menjadi landasan operasional pemungutan pajak dan retribusi:
- **`REGULASI & KEBIJAKAN/`**:
  - UU No. 1 Tahun 2022 (HKPD).
  - Perda Kota Baubau No. 1 Tahun 2024 tentang Pajak Daerah dan Retribusi Daerah (PDRD).
  - Peraturan Walikota (Perwali):
    - Perwali No. 58 Tahun 2024 (Ketentuan Umum Pajak Daerah).
    - Perwali No. 63 Tahun 2024 (Tata Cara Pemungutan PBJT).
    - Perwali No. 8 Tahun 2025 (SOTK Bapenda).
    - Perwali No. 11 Tahun 2025 (Tata Cara Pajak Reklame).
    - Perwali No. 41 Tahun 2025 (Tata Cara Retribusi Pelayanan Kebersihan/Persampahan).
    - Perwali No. 42 Tahun 2025 (Tata Cara Retribusi Pelayanan Pasar).
    - Perwali No. 43 Tahun 2025 (Tata Cara Retribusi Pemanfaatan Barang Milik Daerah).
    - Perwali No. 55 Tahun 2025 (Petunjuk Teknis Pembayaran Non-Tunai dan Elektronifikasi Transaksi Pemda).
  - Keputusan Walikota dan Surat Edaran Bapenda terkait tarif dan zona NJOP.

---

### 📂 KLASTER 02: INTEGRASI BANK DAN API (`02_INTEGRASI_BANK_DAN_API/`)
Arsitektur terpadu Host-to-Host (H2H), payment gateway, dan interkoneksi eksternal:
- **`00_SKEMA_DAN_PANDUAN_H2H/`**:
  - `M-PAD H2H Integration Blueprint.pdf`
  - `mPaD H2H API Specification.md`
  - `H2H-Payment-Gateway-Panduan-Lengkap.pdf`
- **`01_BRI/`**: Dokumentasi SNAP BI BRI, form registrasi partner perbankan, dan petunjuk teknis sandbox.
- **`02_BNI/`**: Spesifikasi integrasi VA BNI, API transfer, dan rekonsiliasi.
- **`03_BTN/`**: Blueprint H2H dan integrasi e-PBB BTN.
- **`04_MANDIRI/`**: `M-PAD Mandiri H2H Blueprint.pdf` dan spesifikasi Mandiri Bill Collection (MCM).
- **`05_BANK_SULTRA/`**: Spesifikasi teknis H2H Kas Daerah dan e-Retribusi Bank Sultra (RKUD).
- **`06_KREDENSIAL_DAN_SECURITY/`**:
  - **Terisolasi & Terlindungi**: Sertifikat private key (`.pem`), public certificate, token client credentials sandbox/production untuk SNAP BI.
- **`07_KOMDIGI_DAN_BPN/`**: Integrasi H2H BPHTB - Kantah ATR/BPN dan integrasi Web Service Komdigi/Inaproc.
- **`08_PKS_DAN_KERJASAMA/`**: Draft & final Perjanjian Kerjasama (PKS) teknis perbankan.
- **`09_BSRE/`**: Dokumentasi integrasi TTE BSrE BSSN, arsitektur server Esign Client lokal (Ubuntu 24), dan pemenuhan 11 standar kesiapan sistem.

---

### 📂 KLASTER 03: PENGADAAN, PERSURATAN DAN ADMINISTRASI (`03_PENGADAAN_PERSURATAN_DAN_ADMINISTRASI/`)
Berkas legalitas, persuratan resmi dinas, dan pengadaan tenaga ahli:
- **`PENGADAAN & ADMINISTRASI/`**:
  - `Dokumen_KAK_dan_SSH_Bapenda/`: Dokumen pengadaan format resmi Bapenda (Identifikasi Kebutuhan, Perencanaan Pengadaan, KAK, Spesifikasi Teknis, HPS, Referensi Harga).
  - `DOKUMEN_ANALISIS_ADMINISTRASI_MPAD/`: Analisis komprehensif dokumen pengadaan M-PAD vs RUP INAPROC.
  - RAB dan KAK infrastruktur server, video trone, dan pendampingan ekosistem digital.
- **`Persuratan/`**:
  - Rangkaian Surat Tugas dan Berita Acara resmi Pemkot Baubau:
    - `BAPENDA_001_SURAT_TUGAS.pdf` s/d `BAPENDA_007_BA_SERAH_TERIMA_AKHIR.pdf`
    - `SURAT_001_PENGANTAR_PROPOSAL` s/d `SURAT_009_INVOICE.pdf`
  - Kontrak & SPK Tenaga Ahli, Nota Kesepahaman (MoU CV Sarjana Komputer & Bapenda).
  - Timeline dan log aktivitas persuratan per Agustus-September 2026.

---

### 📂 KLASTER 04: DATA PAJAK DAN OBJEK (`04_DATA_PAJAK_DAN_OBJEK/`)
Master data objek dan subjek pajak/retribusi daerah:
- **`DATA PAJAK/`**:
  - Rekapitulasi target & realisasi PAD tahun 2025-2026.
  - Data NOP PBB-P2, DHKP, dan NJOP per kelurahan/kecamatan se-Kota Baubau.
  - Master data Wajib Pajak PBJT (Hotel, Restoran, Hiburan, Parkir, Tenaga Listrik).
  - Database titik reklame dan zonasi protokol.
- **`arsip_berkas_pemohon/`**:
  - Berkas digital permohonan SKRD/sertifikat retribusi dan bukti pendukung wajib pajak.

---

### 📂 KLASTER 05: DOKUMEN SISTEM DAN KNOWLEDGE BASE (`05_DOKUMEN_SISTEM_DAN_KNOWLEDGE_BASE/`)
Pusat dokumentasi teknis sistem, arsitektur perangkat lunak, dan repositori pengetahuan:
- **`INDEKS_STRUKTUR_MPAD.md`**: Peta indeks repositori master ini.
- **`PENGETAHUAN_APLIKASI_MPAD.md`**: Buku manual sistem M-PAD komprehensif.
- **`STANDAR_GRID_SPOT_MPAD.md`**: Standarisasi penentuan titik koordinat GIS dan spot reklame/retribusi.
- **`Laporan Lengkap M-PAD Kota Baubau.pdf`**: Laporan teknis final implementasi ekosistem M-PAD.
- **`Dokumen_Terverifikasi/`**: Kumpulan dokumen spesifikasi teknis yang telah lolos validasi Bapenda.
- **`KNOWLEDGE_BASE/`**: Modul referensi dan pedoman pengembang/operator.
- **`docs/`**: Salinan dokumentasi arsitektur dari repositori `retribusi-api/docs/`.

---

### 📂 KLASTER 06: ARSIP QA DAN TESTING E2E (`06_ARSIP_QA_DAN_TESTING_E2E/`)
Dokumentasi pengujian menyeluruh (UAT, SIT, Penetration Testing, E2E):
- **`01_WALKTHROUGH_PENGEMBANGAN/`**: Catatan tahapan verifikasi sprint (02, 06, 07, 09, 27 Agustus 2026).
- **`02_TESTING_FITUR_20260828/`**: Evaluasi UX, validasi PBB & BPHTB, form multi-layanan, dan profil bisnis.
- **`03_TESTING_SISTEM_20260831/`**: Uji coba sistem terintegrasi skala penuh (Admin OPD, Petugas Lapangan, Warga, Multi-Bank).
- **Dokumen Master QA**:
  - `Laporan_Penetration_Test_MPAD.pdf`: Hasil uji penetrasi dan audit keamanan sistem.
  - `SIT_MPAD.xlsx`, `SIT_MPAD_ISIAN.xlsx`, `SIT_MPAD_ISIAN_v2.xlsx`: Lembar kerja System Integration Test.
  - `SKEMA_TESTING_MPAD_20260807.md`: Matriks skenario uji regresi dan fungsional.
  - `mpad_testing_report.pdf` & `skema_pengujian_mpad.pdf`.

---

### 📂 KLASTER 07: ARSIP RAPAT DAN BRIEFING (`07_ARSIP_RAPAT_DAN_BRIEFING/`)
Notulensi, paparan presentasi, dan materi audiensi:
- **`BRIEFING_IKD_20260828/`**: Materi briefing integrasi Identitas Kependudukan Digital (IKD) bersama Ditjen Dukcapil & Disdukcapil.
- **`Bahan_Rencana_Rapat_dan_Materi_Admin_OPD_MPAD/`**: Slide presentasi sosialisasi, panduan bimtek untuk admin OPD teknis pemungut retribusi.

---

### 📂 KLASTER 08: ARSIP HISTORIS DAN BACKUP (`08_ARSIP_HISTORIS_DAN_BACKUP/`)
Penyimpanan jangka panjang berkas historis, cadangan basis data, dan arsip warisan proyek:
- **`Backup_Database_dan_Codebase/`**:
  - Snapshot basis data PostgreSQL/MySQL (`mpad_retribusi_20260901_2300.sql.gz`).
  - Bundel kode sumber lintas repositori (`mpad_repomix_20260901.xml`).
- **`SARJANA_baubau4.0/`**: Arsip digital komprehensif inisiatif transformasi digital Kota Baubau 4.0:
  - `Masterplan_dan_Kebijakan/`: Dokumen masterplan smart city dan telaah regulasi.
  - `Sipanda_dan_Retribusi/`: Dokumen sistem warisan SIPANDA, SIMPBB 2025, dan naskah awal retribusi.
  - `Infrastruktur_dan_Web_Baubau/`: Data jaringan, CCTV, dan rancangan portal Pemkot.
  - `KAK_dan_Anggaran_Baubau/`: Arsip proposal dan telaah staf PBJ Kominfo/Bapenda.
- **`SARJANA_M-PAD/` & `SARJANA_mPAD-Retribusi-Baubau/`**: Repositori kode dan desain prototipe generasi awal.
- **`arsip_dist_bak/` & `arsip_docs/`**: Arsip artefak rilis sebelumnya.

---

## 🔒 KEBIJAKAN KEAMANAN & AKSES KREDENSIAL
1. **Pemisahan Kredensial**: Seluruh kunci privat (`*.pem`), sertifikat TLS, dan rahasia klien disimpan eksklusif pada folder terenkripsi/terisolasi di `02_INTEGRASI_BANK_DAN_API/06_KREDENSIAL_DAN_SECURITY/`.
2. **Integritas Arsip**: Tidak ada berkas yang ditimpa secara diam-diam. Seluruh perubahan arsitektur dicatat dalam log dan indeks terpusat ini.
