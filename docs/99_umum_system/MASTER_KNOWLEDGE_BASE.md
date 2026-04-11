# 🧠 MASTER KNOWLEDGE BASE: M-PAD Documentation
## Ensiklopedia Maksud & Tujuan Seluruh Dokumen (Update April 2026)

Dokumen ini adalah **Sumber dari Segala Sumber** yang merangkum maksud, tujuan, dan inti sari dari seluruh file dokumentasi yang ada di ekosistem M-PAD (Pajak & Retribusi Daerah).

---

## 🏛️ 1. Core Architecture (Root `docs/`)
Dokumen yang mendefinisikan pondasi sistem secara keseluruhan.

| File | Maksud & Tujuan (Intent) |
| :--- | :--- |
| **[README.md](README.md)** | Gerbang utama navigasi. Memetakan seluruh kategori dokumen agar pembaca tahu ke mana harus mencari informasi. |
| **[BAUBAU_REGULATORY_MASTER.md](01_regulasi_baubau/BAUBAU_REGULATORY_MASTER.md)** | **Technical Source of Truth**. Definisi Rumus eksak, Zonasi, dan Mapping ID Database (186-200). |
| **[SYSTEM_OVERVIEW.md](SYSTEM_OVERVIEW.md)** | Definisi arsitektur MPAD. Menjelaskan pilar PBJT vs PBB, alur TTE, dan sejarah keputusan teknis terpenting. |
| **[04-database-schema.md](04-database-schema.md)** | Blueprint data. Berisi ERD dan spesifikasi tabel (termasuk modul baru: Enforcement, Amnesty, dan TTE). |
| **[DOMAIN_SCHEMA.md](DOMAIN_SCHEMA.md)** | Mapping infrastruktur. Menjelaskan URL Production vs Staging serta alur komunikasi data antar domain. |
| **[routes-and-components.md](routes-and-components.md)** | Kamus teknis frontend-backend. Memetakan setiap rute API ke komponen UI yang sesuai. |
| **[STABLE_BASELINE.md](STABLE_BASELINE.md)** | Titik acuan sinkronisasi. Mencatat ID Commit terakhir yang dianggap stabil untuk deployment massal. |
| **[todo-list.md](todo-list.md)** | Daftar tugas berjalan. Mencatat fitur yang sedang dikembangkan, diperbaiki, atau ditunda. |
| **[system-knowledge.md](system-knowledge.md)** | Pengetahuan sistematis untuk Agen AI agar memahami konteks proyek secara mendalam dengan cepat. |

---

## 🔧 2. Implementations (`docs/implementations/`)
Catatan teknis mengenai fitur-fitur spesifik yang telah diimplementasikan.

| File | Maksud & Tujuan (Intent) |
| :--- | :--- |
| **[01-modul-uji-petik.md](implementations/01-modul-uji-petik.md)** | Menjelaskan cara kerja Uji Petik (Spot Check) untuk validasi omzet WP secara riil di lapangan. |
| **[02-role-based-subadmin.md](implementations/02-role-based-subadmin.md)** | Dokumentasi pembagian hak akses (RBAC) antara Admin Utama, Kabid, dan Petugas Teknis. |
| **[03-petugas-tasks-todolist.md](implementations/03-petugas-tasks-todolist.md)** | Alur pembagian tugas harian dari Admin ke Petugas lapangan melalui modul Task List. |

---

## 📚 3. Technical Reference (`docs/reference_technical/`)
Dokumentasi mendalam mengenai logika bisnis dan standar API.

| File | Maksud & Tujuan (Intent) |
| :--- | :--- |
| **[bapenda_process_stages.md](reference_technical/bapenda_process_stages.md)** | Menjelaskan kaitan fitur M-PAD dengan 4 siklus Bapenda: Pendaftaran, Pendataan, Penetapan, Penagihan. |
| **[billing_system_documentation.md](reference_technical/billing_system_documentation.md)** | Logika pembentukan Kode Billing, perhitungan denda, dan integrasi dengan Payment Gateway Bank. |
| **[spopd-form-structure.md](reference_technical/spopd-form-structure.md)** | Detail skema JSON untuk formulir dinamis pendaftaran objek pajak (PBJT). |
| **[penalty_scheme.md](reference_technical/penalty_scheme.md)** | Aturan perhitungan denda keterlambatan (1%-2%) sesuai regulasi pemerintah. |
| **[core_apis.md](reference_technical/core_apis.md)** | Dokumentasi endpoint internal untuk manajemen Auth dan User. |
| **[public_apis.md](reference_technical/public_apis.md)** | Dokumentasi endpoint publik untuk pengecekan tagihan oleh Warga/WP tanpa login. |

---

## ⚖️ 4. Regulations & Strategy (`docs/regulasi/`)
Dokumen penyelarasan sistem dengan aturan hukum dan strategi migrasi.

| File | Maksud & Tujuan (Intent) |
| :--- | :--- |
| **[03-perwali-pdrd-summary.md](regulasi/03-perwali-pdrd-summary.md)** | Ringkasan regulasi daerah yang menjadi landasan tarif dan denda di sistem. |
| **[API_PBB_BAUBAU_2026.md](regulasi/API_PBB_BAUBAU_2026.md)** | Panduan teknis khusus untuk integrasi data PBB-P2 dari sistem SISMIOP. |
| **[migration_strategy.md](migration_strategy.md)** | Strategi memindahkan data dari sistem legacy (9pajak) ke M-PAD tanpa kehilangan integritas. |
| **[07-status-dokumen-resmi.md](regulasi/07-status-dokumen-resmi.md)** | Daftar 21 jenis dokumen resmi (SKPD, SSPD, dll) dan status kesiapannya untuk TTE. |
| **[02-master-data-objek-pajak.md](regulasi/02-master-data-objek-pajak.md)** | Standarisasi penamaan dan kodefikasi objek pajak daerah. |

---

## 🧪 5. Quality Assurance (`docs/testing/`)
Protokol dan panduan pengujian untuk menjamin stabilitas sistem.

| File | Maksud & Tujuan (Intent) |
| :--- | :--- |
| **[00-Panduan-Standar-Pengujian.md](testing/00-Panduan-Standar-Pengujian.md)** | Kitab suci pengujian. Menjelaskan cara menjalankan tes manual dan otomatis (NOSS). |
| **[12-Error-History-Mitigation.md](testing/12-Error-History-Mitigation-Registry.md)** | Log sejarah error yang pernah terjadi beserta solusi permanennya (Mitigasi). |
| **[13-Workspace-Master-Testing.md](testing/13-Workspace-Master-Testing-Protocol.md)** | Prosedur pengetesan lintas repositori (Omni Testing) sebelum rilis production. |
| **[02-e2e-testing-scheme.md](testing/02-e2e-testing-scheme.md)** | Skenario pengujian ujung-ke-ujung dari pendaftaran hingga bayar dan cetak bukti. |

---

## 📈 6. Platform Guides & Results (`docs/results/` & Guides)
Laporan hasil uji coba dan panduan operasional aktor.

| File Category | Maksud & Tujuan (Intent) |
| :--- | :--- |
| **[admin-userguide.md](admin-userguide.md)** | Panduan operasional lengkap untuk staf Bapenda (Dashboard & Admin). |
| **[petugas-userguide.md](petugas-userguide.md)** | Panduan operasional aplikasi mobile untuk petugas lapangan. |
| **[mobile-e-retribusi.md](mobile-e-retribusi.md)** | Panduan penggunaan portal layanan mandiri bagi Warga Kota Baubau. |
| **[docs/results/*.md](results/)** | Kumpulan log eksekusi tes penetrasi (keamanan), CRUD API, dan kalkulator pajak. |

---

## 🏛️ 7. Akronim dan Istilah Berkaitan dengan Pajak
-   **PAD**: Pendapatan Asli Daerah.
-   **PDRD**: Pajak Daerah dan Retribusi Daerah.
-   **WP & WR**: Wajib Pajak dan Wajib Retribusi.
-   **NPWPD**: Nomor Pokok Wajib Pajak Daerah.
-   **PBJT**: Pajak Barang dan Jasa Tertentu.
-   **NJOP**: Nilai Jual Objek Pajak (Dasar PBB-P2 & Reklame).
-   **NJOPTKP**: Nilai Jual Objek Pajak Tidak Kena Pajak.
-   **NPOP & NPOPTKP**: Nilai Perolehan Objek Pajak (Dasar BPHTB).
-   **NSR**: Nilai Sewa Reklame.
-   **ZNT & NIR**: Zona Nilai Tanah dan Nilai Indikasi Rata-rata.
-   **Self Assessment**: Pajak dihitung mandiri oleh WP (Resto/Hotel).
-   **Official Assessment**: Pajak ditetapkan oleh Pemda (PBB/Reklame).

---

## 📄 8. Kamus Dokumen & Siklus Administrasi Perpajakan

### A. Pendaftaran & Pendataan
-   **SPOPD**: Formulir daftar/lapor usaha (PBJT, Reklame, Air Tanah, dsb).
-   **SPOP & LSPOP**: Formulir pendaftaran spesifik PBB-P2.
-   **SKT (Surat Keterangan Terdaftar)**: Bukti WP telah masuk sistem.
-   **LKOK**: Lembar kerja pemeriksaan lapangan petugas.

### B. Penetapan & Tagihan
-   **SPTPD**: Dokumen lapor omzet bulanan dari WP.
-   **SKPD / SKRD**: Tagihan resmi Pokok Pajak / Retribusi.
-   **SPPT**: Tagihan resmi tahunan PBB-P2.
-   **SKPDKB / SKPDKBT**: Tagihan Kurang Bayar (hasil pemeriksaan).
-   **SKPDLB / SKPDN**: Penetapan Lebih Bayar atau Nihil.

### C. Pembayaran & Penegakan Hukum
-   **SSPD / SSRD**: Bukti sah penyetoran ke Kas Daerah.
-   **STPD**: Tagihan denda/sanksi bunga keterlambatan.
-   **Surat Teguran (1, 2, 3)**: Peringatan tunggakan.
-   **SPMP (Surat Paksa)**: Dasar hukum melakukan penyitaan/penyegelan.

---
## 🏛️ 9. Visual & Logic Baseline (April 2026)
-   **Database Alignment**: Klasifikasi ID 186-200 adalah klasifikasi master yang telah disinkronkan dengan rumus aktif Perda 1/2024.
-   **Icon Standard**: Sistem menggunakan URL Cloudinary unik untuk setiap klasifikasi guna menjamin variasi visual (Unique Visual Alignment).
-   **Zonasi Codes**: Menggunakan kode standar `RD-CL-A` (Jalan Strategis) dan `PKD-PREM` (Lapak Premium).

---
*Catatan: Dokumen ini wajib diperbarui setiap kali ada folder atau file dokumentasi baru yang ditambahkan ke dalam sistem.*
