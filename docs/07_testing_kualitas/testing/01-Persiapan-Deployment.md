# Tahap 1: Persiapan Deployment (Deployment Checklist)

Sebelum mulai menguji fitur secara fungsional, pastikan hal teknis fundamental sudah beres di VPS/Server Production.

## Daftar Periksa Server
- [ ] **Kode Terbaru**: Pastikan *branch* `main` dari seluruh repositori terkait (API, Admin, Petugas, Mobile) sudah berhasil di-*pull* dan ter-*deploy* ke mesin Ubuntu Production (`157.10.252.74`).
- [ ] **Environment Variables**: Pastikan `.env` API production sudah tersetting ke `APP_DEBUG=false` dan `APP_ENV=production`.
- [ ] **Migrasi Database**: Jika rilis terbaru ini memuat penambahan atau perubahan skema tabel (migration), pastikan perintah ini sudah dijalankan:
  ```bash
  php artisan migrate --force
  ```

## Optimalisasi Laravel
Jalankan *cache clearing* agar aplikasi berjalan dengan performa maksimal. Eksekusi sekumpulan perintah ini di terminal (SSH) VPS pada root folder `retribusi-api`:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
```

**Tanda Sukses:**
Terminal akan membalas dengan warna hijau berpesan _"Configuration cached successfully!", "Routes cached successfully!"_ dsb., tanpa ada pesan Exception merah.
