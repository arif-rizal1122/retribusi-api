# Kumpulan Script Deployment (Local Only)

Folder ini berisi banyak file berektensi `.exp` yang merupakan skrip **Expect**. Skrip ini digunakan untuk mengotomatisasi perintah SSH dan SCP ke VPS tanpa harus berulang kali mengetikkan password.

**⚠️ PENTING: File-file ini beserta `vps.txt` sudah dimasukkan ke `.gitignore` sehingga tidak akan di-push ke GitHub untuk menjaga keamanan password server Anda.**

## Daftar Skrip Utama & Fungsinya

### 1. Script Frontend / Nginx
- `vps_deploy_frontend_scp.exp`: Mengirim dan mengaktifkan file konfigurasi Nginx terbaru untuk PWA/Frontend dari lokal ke VPS.
- `vps_setup_mpad_nginx.exp` & `vps_setup_mpad_ssl.exp`: Setup awal konfigurasi Nginx dan SSL otomatis untuk domain produksi.
- `vps_grep_nginx.exp` & `vps_list_nginx.exp`: Mengecek daftar dan isi konfigurasi situs yang aktif di Nginx server.
- `vps_check_nginx_error.exp`: Membaca log error Nginx.

### 2. Script Backend API & Laravel
- `vps_refresh_api.exp`: Membersihkan semua cache Laravel (config, route, view) dan me-restart queue worker di server.
- `vps_deploy_all.exp`: Menjalankan git pull, composer install, dan migrasi secara berurutan.
- `vps_migrate.exp`: Menjalankan `php artisan migrate --force` di production.
- `vps_fix_laravel_perms.exp` & `vps_fix_perms_final.exp`: Memperbaiki izin folder (permissions) `storage` dan `bootstrap/cache` agat dapat ditulis oleh user `www-data`.
- `vps_read_laravel_logs.exp`: Menampilkan error terbaru dari file `storage/logs/laravel.log`.

### 3. Script Utilities VPS
- `vps_check_status.exp`: Mengecek kondisi storage, RAM, dan status service Nginx & PHP-FPM di server.
- `vps_check_time_logs.exp`: Menyelaraskan informasi zona waktu dan output log terbaru.
- `vps_run_certbot.exp`: Memperbarui atau menerbitkan sertifikat SSL dari Let's Encrypt secara otomatis.
- `change_vps_pass.exp` (di /tmp/): Skrip sementara yang digunakan pada 5 Maret 2026 untuk mengamankan dan merotasi password yang bocor di history Git lama.

---

## ⚠️ Aturan Deployment Frontend (KRITIKAL)

> **Semua proses `npm run build` untuk aplikasi frontend (`retribusi-admin`, `retribusi-mobile`, `retribusi-petugas`) wajib dilakukan langsung di server VPS, BUKAN di komputer lokal.**

Alur deployment frontend yang benar:
1. Push kode ke `main` di GitHub dari lokal.
2. SSH ke VPS → masuk ke direktori frontend.
3. `git pull origin main` untuk mendapatkan kode terbaru.
4. `npm install && npm run build` dilakukan di VPS.
5. Nginx otomatis melayani file dari folder `dist/`.

**Tidak diperkenankan** menjalankan `npm run build` lokal dan mengirim `dist/` via SCP.

---
Semua file ini (dan kredensial di `vps.txt`) sepenuhnya aman berada di komputer lokal Anda, namun telah diblokir dari version control. Gunakan `./<nama_file.exp>` untuk mengeksekusinya.
