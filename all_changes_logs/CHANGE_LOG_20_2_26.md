# Log Perubahan Project Bapeda - 20 Feb 2026

Dokumen ini mencatat sinkronisasi dan perbaikan yang dilakukan untuk memastikan semua service berjalan dengan benar di lingkungan lokal setelah update dari GitHub.

## 1. Sinkronisasi & Update Repository
Semua project telah diupdate ke versi terbaru dari branch `dev`:
- **retribusi-api**: Pulled 19 commit terbaru (termasuk fitur pencarian NIK).
- **retribusi-petugas**: Pulled 4 commit terbaru.
- **Dependency Update**: Menjalankan `composer install` dan `npm install` untuk mendukung package baru (Sentry, dll).
- **Database**: Menjalankan `php artisan migrate` untuk skema terbaru.

## 2. Perbaikan Port Conflict
Terdapat konflik pada port `3001` antara `wa-gateway` dan `retribusi-admin`.
- **Perubahan**: Port `retribusi-admin` diubah dari `3001` ke **`3004`** di file `retribusi-admin/vite.config.ts`.
- **Tujuan**: Memungkinkan kedua service berjalan bersamaan di satu mesin.

## 3. Resolusi Error 500 (Tambah Wajib Pajak)
Ditemukan error `Column not found: 1054 Unknown column 'district'` saat menyimpan data Wajib Pajak.
- **Penyebab**: Skema database lokal belum memiliki kolom baru yang ada di kode controller terbaru.
- **Solusi**: Dibuat migration baru `2026_02_20_025014_add_location_and_coordinates_to_taxpayers_table.php` yang menambahkan kolom:
  - `district`
  - `sub_district`
  - `latitude`
  - `longitude`

## 4. Konfigurasi API Lokal
Aplikasi frontend sebelumnya diarahkan ke `api.sipanda.online` (production).
- **Perubahan**: Membuat file `.env` di semua folder frontend (`admin`, `mobile`, `petugas`) dan mengarahkan `VITE_API_URL` ke **`http://127.0.0.1:8000`**.
- **Tujuan**: Agar fitur baru yang belum di-deploy ke production bisa langsung ditest secara lokal menggunakan backend lokal.

## 5. Ringkasan Akses Lokal
- **Admin Dashboard**: [http://localhost:3004](http://localhost:3004)
- **Aplikasi Mobile**: [http://localhost:3002](http://localhost:3002)
- **Aplikasi Petugas**: [http://localhost:3003](http://localhost:3003)
- **Backend API**: [http://127.0.0.1:8000](http://127.0.0.1:8000)
- **WA Gateway**: [http://localhost:3001](http://localhost:3001)

---
*Dibuat oleh Assistant untuk membantu tracking alur pengembangan.*
