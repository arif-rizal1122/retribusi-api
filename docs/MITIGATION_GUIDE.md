# 🛡️ Panduan Mitigasi & Troubleshooting (Legacy Errors)

Dokumen ini mencatat masalah teknis yang pernah terjadi dalam pengembangan M-PAD dan solusi yang telah diterapkan untuk mencegah terulangnya kesalahan serupa.

## 1. Masalah Terverifikasi & Solusi

### 🔴 Error 500: PHP Fatal Error (Positional Arguments)
- **Gejala:** Server mengembalikan 500 Internal Server Error mendadak setelah deployment.
- **Penyebab:** Penggunaan named parameter (misal: `guest: function()`) pada Laravel middleware di environment VPS yang menggunakan PHP dengan versi/konfigurasi tertentu yang tidak mendukung sintaks tersebut.
- **Solusi:** Gunakan positional arguments. Ubah named arguments menjadi argumen berurutan di `bootstrap/app.php`.
- **Pelajaran:** Selalu tes sintaks baru di environment Dev sebelum masuk Production.

### 🔴 Error 500: Permission Denied (Storage/Cache)
- **Gejala:** Aplikasi tidak bisa menulis log atau session, menyebabkan crash total.
- **Penyebab:** Owner folder `storage` atau `bootstrap/cache` berubah menjadi `sipanda` atau `root` (biasanya setelah `git pull` manual atau `composer install` oleh user SSH).
- **Solusi:** Reset owner ke `www-data` dan set permission ke `775`.
- **Perintah:** `sudo chown -R www-data:www-data storage bootstrap/cache && sudo chmod -R 775 storage bootstrap/cache`.

### 🔴 Masalah CORS: Duplicate Headers
- **Gejala:** Browser memblokir request dengan pesan "Multiple Access-Control-Allow-Origin headers found".
- **Penyebab:** Konfigurasi CORS aktif di dua tempat sekaligus (Nginx `add_header` dan Laravel Middleware `fruitcake/laravel-cors` atau native Laravel 11).
- **Solusi:** 
  1. Biarkan **Nginx** hanya menangani request `OPTIONS` (Preflight).
  2. Biarkan **Laravel** menangani request utama (GET/POST/dll).
  3. Pastikan tidak ada `add_header` CORS di level server Nginx untuk request non-OPTIONS.

## 2. Aturan Emas (Do's & Don'ts)

| ✅ LAKUKAN (Do) | ❌ JANGAN LAKUKAN (Don't) |
| :--- | :--- |
| Jalankan `php artisan optimize` sebagai user `www-data`. | Jalankan `optimize` sebagai user `sipanda` (akan merusak permission log). |
| Selalu gunakan HTTPS untuk API Call di Production. | Mencampur HTTP dan HTTPS (menyebabkan mixed content & CORS block). |
| Update `.env` di VPS secara manual (jangan lewat Git). | Memasukkan kredensial asli ke dalam file `.env.example`. |
| Gunakan `test_production_ready.sh` setelah deploy. | Deployment tanpa verifikasi endpoint kesehatan (`/up`). |

## 3. Opsi Mitigasi Darurat
Jika sistem down setelah update:
1. **Rollback Cepat:** Jika menggunakan Git, kembali ke commit sebelumnya: `git reset --hard HEAD~1` lalu `php artisan optimize:clear`.
2. **Maintenance Mode:** Aktifkan halaman maintenance: `php artisan down`.
3. **Cek Log Real-time:** Selalu pantau log jika ada error misterius: `tail -f storage/logs/laravel.log`.

---
*Dokumen historis. Terakhir diperbarui: 26 Februari 2026.*
