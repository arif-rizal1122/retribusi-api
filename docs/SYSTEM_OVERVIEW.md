# 🏛️ M-PAD: System Overview & Architecture

Dokumen ini menjelaskan arsitektur, proses sinkronisasi, dan cara kerja sistem **M-PAD (Manajemen Integrasi Tax, Retribusi, dan Aset Daerah)**.

## 1. Arsitektur Sistem

Sistem M-PAD terdiri dari 4 komponen utama yang saling terintegrasi:

- **Backend (API):** Menggunakan **Laravel 11** sebagai pusat logika bisnis, manajemen database (MySQL), dan penyedia data melalui RESTful API.
- **Frontend Admin:** Dashboard berbasis **React (Vite)** untuk pengelola (BAPENDA) untuk manajemen data master, zonasi, dan laporan.
- **Frontend Mobile:** Aplikasi PWA berbasis **React (Vite)** untuk masyarakat (Wajib Pajak) melakukan pendaftaran, pengecekan tagihan, dan simulasi pajak.
- **Frontend Petugas:** Aplikasi berbasis **React (Vite)** khusus untuk petugas lapangan untuk melakukan pengawasan dan input data potensi.

## 4. Siklus & Kategori Pendapatan (V-Tax Parity)

Sistem ini disesuaikan sepenuhnya dengan regulasi **9 Jenis Pajak Daerah** (UU HKPD & Perwali 58/2024), yaitu:
1. **PBJT Jasa Perhotelan** (Tarif 10%)
2. **PBJT Makan dan Minum** (Tarif 10%)
3. **PBJT Jasa Kesenian dan Hiburan** (Tarif 10% - 40%)
4. **Pajak Reklame**
5. **PBJT Tenaga Listrik**
6. **Pajak MBLB**
7. **PBJT Jasa Parkir** (Tarif 30%)
8. **Pajak Air Tanah** (Tarif 20%)
9. **Pajak Sarang Burung Walet**

### Pipeline Verifikasi & Siklus Penagihan (Correspondence)
M-PAD mendukung siklus penagihan terintegrasi (End-to-End) layaknya sistem V-Tax:
1. **Pendaftaran (SPTPD/SPOPD)**: WP melakukan perekaman data mandiri atau oleh petugas.
2. **Surat Teguran I & II**: Otomatis di-generate oleh sistem (Cron Job) jika tagihan (SKPD/STPD) melewati batas jatuh tempo.
3. **Surat Paksa (SPMP)**: Diterbitkan untuk upaya paksa jika denda awal diabaikan.

Semua status alur dokumen diseragamkan: `draft` -> `proses` (verifikasi) -> `disetujui` (terbit SKPD) -> `ditolak`.

## 5. Keamanan & Stabilitas
- **CORS:** Diatur ketat di sisi Nginx dan Laravel agar hanya domain resmi (`*.sipanda.online`) yang bisa mengakses API. Penanganan `OPTIONS` preflight dilakukan di level Nginx untuk mencegah "Duplicate Header".
- **Branding PWA:** Nama aplikasi telah diperbarui menjadi **M-PAD** (Mitra PAD) dengan ikon yang dioptimalkan sesuai standar PWA (192, 512, apple-touch) dan latar belakang putih.
- **Monitoring:** Terintegrasi dengan **Sentry** untuk memantau error secara real-time.
- **Verifikasi Sistem**: Dilengkapi dengan suite pengujian otomatis (`testing/`) yang mencakup 69 skenario CRUD dan 27 audit keamanan infrastruktur.
- **Proses Bisnis**: Penyelarasan dengan 4 tahapan BAPENDA (Pendaftaran, Pendataan, Penetapan, Penagihan). Lihat [bapenda_process_stages.md](file:///Users/pondokit/Herd/retribusi-api/docs/reference_technical/bapenda_process_stages.md).

## 5. Log Keputusan Penting (Februari 2026)

| Tanggal | Aktivitas | Detail Teknis |
| :--- | :--- | :--- |
| 25 Feb | **Stabilisasi API** | Fix error 500 di `bootstrap/app.php` (PHP syntax compatibility) dan perbaikan permission folder `storage` & `bootstrap/cache`. |
| 25 Feb | **Optimalisasi CORS** | Pemisahan header CORS: Nginx menangani `OPTIONS`, Laravel menangani request utama. |
| 26 Feb | **Unified Sync** | Sinkronisasi penuh 8 domain (Production & Dev) dari Local -> GitHub -> VPS. Semua environment kini menggunakan owner `www-data`. |
| 26 Feb | **Audit Keamanan** | Menjalankan `test_penetration.sh`, mengamankan akses file `.env`, dan memverifikasi proteksi IDOR. |
| 02 Mar | **Stable Baseline** | Penetapan ID Commit stabil untuk semua repo (`retribusi-api`: `7b7388c`). Lihat [STABLE_BASELINE.md](file:///Users/pondokit/Herd/retribusi-api/docs/STABLE_BASELINE.md). |
| 03 Mar | **Proses Bisnis BAPENDA** | Alur 4 Tahap (Pendaftaran s/d Penagihan). Lihat [bapenda_process_stages.md](file:///Users/pondokit/Herd/retribusi-api/docs/reference_technical/bapenda_process_stages.md). |

---
*Dokumen ini adalah sumber kebenaran (Source of Truth) untuk konteks proyek M-PAD. Terakhir diperbarui: 26 Februari 2026.*
