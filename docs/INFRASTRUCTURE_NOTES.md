# 🌐 Catatan Infrastruktur & Maintenance (VPS)

Dokumen ini mencatat detail teknis akses server dan perintah pemeliharaan manual yang sebelumnya digunakan dalam script otomatisasi (`.exp`).

## 1. Detail Koneksi
- **IP Address:** `157.10.252.74`
- **User:** `sipanda`
- **Sistem Operasi:** Ubuntu 22.04 LTS
- **Web Server:** Nginx (v1.18.0+)
- **PHP Version:** PHP 8.2+ (FPM)

## 2. Peta Direktori VPS
Seluruh proyek terletak di bawah home user `/home/sipanda/`:

| Nama Proyek | Direktori (Production) | Direktori (Dev) |
| :--- | :--- | :--- |
| **API** | `/home/sipanda/retribusi-api` | `/home/sipanda/retribusi-api-dev` |
| **Mobile** | `/home/sipanda/retribusi-mobile` | `/home/sipanda/retribusi-mobile-dev` |
| **Admin** | `/home/sipanda/retribusi-admin` | `/home/sipanda/retribusi-admin-dev` |
| **Petugas**| `/home/sipanda/retribusi-petugas`| `/home/sipanda/retribusi-petugas-dev`|

## 3. Perintah Pemeliharaan Manual
Jika sinkronisasi GitHub Actions gagal, perintah berikut dapat dijalankan secara manual di terminal VPS:

### A. Sinkronisasi API
```bash
cd /home/sipanda/retribusi-api
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
sudo -u www-data php artisan optimize
```

### B. Perbaikan Permission (Sangat Penting)
Jika muncul error 500 "Permission Denied", jalankan perintah ini:
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### C. Konfigurasi Nginx
- Lokasi file: `/etc/nginx/sites-available/retribusi-api`
- Cek syntax: `sudo nginx -t`
- Reload: `sudo systemctl reload nginx`

---
> [!IMPORTANT]
> Selalu operasikan perintah `artisan optimize` sebagai user `www-data` agar hak akses log dan cache tetap sinkron dengan web server.
