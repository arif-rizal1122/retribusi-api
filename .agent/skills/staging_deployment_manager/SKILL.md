---
name: Staging Deployment Manager
description: Skill khusus untuk mengelola, memverifikasi, dan memperbaiki alur deployment CI/CD serta kesehatan (health status) seluruh domain staging *.mpad.online.
---

# 🚀 Staging Deployment Manager

Skill ini dirancang untuk memastikan bahwa setiap perubahan yang dikirim ke branch `staging` ter-deploy dengan sempurna dan layanan tetap berjalan optimal.

## 📋 Daftar Domain & Target
| Service | Domain | Target Folder VPS |
| :--- | :--- | :--- |
| **Backend API** | `https://api.mpad.online` | `/home/sipanda/retribusi-api-staging` |
| **Citizen App** | `https://mpad.online` | `/home/sipanda/retribusi-mobile-staging` |
| **Officer App** | `https://petugas.mpad.online` | `/home/sipanda/retribusi-petugas-staging` |
| **Admin Panel** | `https://admin.mpad.online` | `/home/sipanda/retribusi-admin-staging` |

## 🛠️ Protokol Pengecekan Deployment (Health Check)

Gunakan perintah `curl` untuk mengecek status hidup setiap domain:

1.  **API Health Check**: 
    - Endpoint: `/up` atau `/api/health`
    - Perintah: `curl -I https://api.mpad.online/up`
    - Ekspektasi: `200 OK`

2.  **Frontend Uptime Check**:
    - Perintah: `curl -L -s -o /dev/null -w "%{http_code}" https://mpad.online`
    - Ekspektasi: `200`

3.  **Sync Verification**:
    - Periksa apakah `.env.staging` di frontend sudah mengarah ke `api.mpad.online`.
    - Periksa apakah versi build terbaru sudah naik dengan mengecek `Last-Modified` header atau meta tag jika tersedia.

## ⚠️ Penanganan Gagal Deploy (Emergency Protocol)

Jika CI/CD gagal atau domain tidak merespon:

### 1. Masalah Koneksi SSH/Authentication
Jika deployment gagal karena `Permission denied` atau `Host key verification failed`:
- Cek apakah kredensial di GitHub Secrets masih valid.
- Gunakan taktik "Hard Sync" yang ada di skill **Staging Domain Testing**.

### 2. Service Down (500 Error / 502 Bad Gateway)
- **Backend**: Masuk ke VPS, jalankan `tail -f storage/logs/laravel.log`.
- **Frontend**: Cek apakah build di `dist/` sudah lengkap dan permission folder set ke `755`.

### 3. Database Migration Failure
Jika API mengembalikan 500 setelah deploy, jalankan secara manual di VPS:
```bash
php artisan migrate --force
```

## 🔄 Prosedur Sinkronisasi Lintas Repo
Selalu pastikan perubahan di `retribusi-api` (misal: penambahan kolom baru) telah di-deploy **sebelum** melakukan deployment pada repositori frontend yang bergantung pada data tersebut.
