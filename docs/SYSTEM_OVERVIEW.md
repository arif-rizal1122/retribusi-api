# 🏛️ M-PAD: System Overview & Architecture

Dokumen ini menjelaskan arsitektur, proses sinkronisasi, dan cara kerja sistem **M-PAD (Manajemen Integrasi Tax, Retribusi, dan Aset Daerah)**.

## 1. Arsitektur Sistem

Sistem M-PAD terdiri dari 4 komponen utama yang saling terintegrasi:

- **Backend (API):** Menggunakan **Laravel 11** sebagai pusat logika bisnis, manajemen database (MySQL), dan penyedia data melalui RESTful API.
- **Frontend Admin:** Dashboard berbasis **React (Vite)** untuk pengelola (BAPENDA) untuk manajemen data master, zonasi, dan laporan.
- **Frontend Mobile:** Aplikasi PWA berbasis **React (Vite)** untuk masyarakat (Wajib Pajak) melakukan pendaftaran, pengecekan tagihan, dan simulasi pajak.
- **Frontend Petugas:** Aplikasi berbasis **React (Vite)** khusus untuk petugas lapangan untuk melakukan pengawasan dan input data potensi.

## 2. Kategori Pendapatan (V-Tax & PBB Parity)

Sistem ini melayani dua pilar utama pendapatan daerah Kota Baubau:

### A. 9 Jenis Pajak Daerah (PBJT)
Sesuai UU HKPD & Perwali 58/2024:
1. **PBJT Jasa Perhotelan** (Tarif 10%)
2. **PBJT Makan dan Minum** (Tarif 10%)
3. **PBJT Jasa Kesenian dan Hiburan** (Tarif 10% - 40%)
4. **Pajak Reklame**
5. **PBJT Tenaga Listrik**
6. **Pajak MBLB**
7. **PBJT Jasa Parkir** (Tarif 10%)
8. **Pajak Air Tanah** (Tarif 20%)
9. **Pajak Sarang Burung Walet**

### B. Pajak Bumi dan Bangunan (PBB-P2)
M-PAD kini terintegrasi penuh dengan data PBB:
- **Inkuiri NOP**: Pengecekan tagihan PBB secara real-time.
- **E-SPPT**: Unduh dokumen SPPT digital melalui portal Mobile.
- **Riwayat Transaksi**: Pantau status pembayaran PBB tahun berjalan maupun tunggakan.

## 3. Pipeline Verifikasi & Penagihan (Correspondence)

M-PAD mendukung siklus penagihan terintegrasi (End-to-End) dengan dukungan **Tanda Tangan Elektronik (TTE)**:

1. **Pendaftaran (SPTPD/SPOPD)**: WP melakukan perekaman data mandiri atau oleh petugas.
2. **Penetapan (SKPD/SKRD)**: Menggunakan **Formula Parser** dinamis untuk menghitung nominal secara otomatis.
3. **Tanda Tangan Elektronik (TTE)**: Dokumen resmi (SKPD/Sspd) kini menyertakan QR-Code BSrE untuk validasi keaslian (E-Registry).
4. **Surat Teguran I & II**: Otomatis di-generate jika tagihan melewati batas jatuh tempo.
5. **Surat Paksa (SPMP)**: Diterbitkan untuk upaya paksa penagihan.

## 4. Keamanan & Stabilitas
- **Branding M-PAD:** Nama aplikasi telah sepenuhnya diperbarui dengan ikon standar PWA di semua sub-platform.
- **CORS & Preflight:** Penanganan `OPTIONS` dilakukan di level Nginx, sementara otentikasi menggunakan **Laravel Sanctum**.
- **Audit Logs:** Pencatatan setiap aktivitas sensitif (Penetapan & Penghapusan Denda) untuk transparansi BPK.

## 5. Log Keputusan Penting (2026)

| Tanggal | Aktivitas | Detail Teknis |
| :--- | :--- | :--- |
| 26 Feb | **Unified Sync** | Sinkronisasi penuh 8 domain (Production & Dev) dari Local -> GitHub -> VPS. |
| 03 Mar | **Proses Bisnis** | Alur 4 Tahap BAPENDA (Pendaftaran s/d Penagihan) diaktifkan. |
| 15 Mar | **Integrasi PBB** | Modul inkuiri NOP dan E-SPPT dirilis di platform Mobile. |
| 25 Mar | **E-Registry TTE** | Sistem audit dokumen PDF via QR-Code BSrE mulai diuji coba. |
| 01 Apr | **Amnesty & Enforcement** | Peluncuran modul Penghapusan Denda (Penalty Waiver) dan Surat Paksa digital. |

---
*Last modified: April 2026. Source of Truth for M-PAD Project.*
