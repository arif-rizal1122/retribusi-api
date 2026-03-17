# 🏛️ M-PAD: System Overview & Architecture

Dokumen ini menjelaskan arsitektur, proses sinkronisasi, dan cara kerja sistem **M-PAD (Manajemen Integrasi Tax, Retribusi, dan Aset Daerah)**.

## 1. Arsitektur Sistem

Sistem M-PAD terdiri dari 4 komponen utama yang saling terintegrasi:

- **Backend (API):** Menggunakan **Laravel 11** sebagai pusat logika bisnis, manajemen database (MySQL), dan penyedia data melalui RESTful API.
- **Frontend Admin:** Dashboard berbasis **React (Vite)** untuk pengelola (BAPENDA) untuk manajemen data master, zonasi, dan laporan.
- **Frontend Mobile:** Aplikasi PWA berbasis **React (Vite)** untuk masyarakat (Wajib Pajak) melakukan pendaftaran, pengecekan tagihan, dan simulasi pajak.
- **Frontend Petugas:** Aplikasi berbasis **React (Vite)** khusus untuk petugas lapangan untuk melakukan pengawasan dan input data potensi.

## 4. Kategori Pendapatan & Pajak (BAPENDA BAUBAU)

Sistem ini melayani kategori pendapatan daerah sebagai berikut:

### A. Pajak Barang dan Jasa Tertentu (PBJT)
| Kode | Jenis PBJT | Deskripsi |
|------|-----------|-----------|
| `PBJT-LIS` | Tenaga Listrik | Pajak atas konsumsi tenaga listrik. |
| `PBJT-MNM` | Makan & Minum | Pajak restoran dan katering (10%). |
| `PBJT-HTL` | Perhotelan | Pajak jasa penginapan (10%). |
| `PBJT-HBR` | Kesenian & Hiburan| Pajak hiburan (Umum: 10%, Hiburan Malam: 40%). |
| `PBJT-PRK` | Jasa Parkir | Pajak area parkir swasta (LIPO, Bandara, dll). |

### B. Pajak Spesifik lainnya
- **Pajak Reklame**: Papan reklame, neon box, billboard.
- **Pajak MBLB**: Mineral Bukan Logam dan Batuan.
- **Pajak Air Tanah (PAT)**: Pengambilan/pemanfaatan air tanah.
- **BPHTB & PBB-P2**: Bea Perolehan Hak Tanah dan Pajak Bumi Bangunan.

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
