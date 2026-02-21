# Mitra PAD

Selamat datang di dokumentasi API MITRA (Mitra PAD - Manajemen Integrasi Tax, Retribusi, dan Aset Daerah). Dokumentasi ini mencakup seluruh endpoint yang tersedia untuk integrasi dengan aplikasi Admin, Petugas, dan Mobile.

## Daftar Dokumentasi

1.  **[Public APIs](public_apis.md)**
    *   Endpoint yang dapat diakses tanpa autentikasi atau untuk kebutuhan awal (Login, Simulasi Pajak, PBB Lookup).
2.  **[Core APIs](core_apis.md)**
    *   Fungsi inti aplikasi: Pengelolaan Wajib Pajak, Objek Pajak, Pembuatan Tagihan (Billing), dan Pembayaran.
3.  **[Surveillance & TTE](surveillance_tte.md)**
    *   Modul lanjutan untuk Pengawasan (Audit Log, Anomali, Penindakan) serta E-Registry dan Tanda Tangan Elektronik (TTE).
4.  **[Informasi Tambahan](spopd-form-structure.md)**
    *   Struktur data formulir SPOPD dan pemetaan field database.
5.  **[Project Artifacts](artifacts/task.md)**
    *   Berisi [Task List](artifacts/task.md), [Implementation Plan](artifacts/implementation_plan.md), dan [Walkthrough](artifacts/walkthrough.md) pengerjaan fitur.

## Standar API

- **Base URL**: `https://api.sipanda.online/api`
- **Format Respons**: JSON
- **Autentikasi**: Laravel Sanctum (Bearer Token)
- **Status Codes**: 
    - `200 OK`: Berhasil
    - `201 Created`: Berhasil membuat data
    - `401 Unauthorized`: Token tidak valid atau sesi berakhir
    - `403 Forbidden`: Tidak memiliki izin akses
    - `422 Unprocessable Content`: Validasi input gagal
    - `500 Server Error`: Kesalahan pada server
