---
name: Staging Deployment Manager
description: Skill khusus untuk mengelola, memverifikasi, dan memperbaiki alur deployment CI/CD serta kesehatan (health status) seluruh domain staging *.sipanda.online.
---

# 🚀 Staging Deployment Manager

Skill ini dirancang untuk memastikan bahwa setiap perubahan yang dikirim ke branch `staging` ter-deploy dengan sempurna dan layanan tetap berjalan optimal.

## 📋 Daftar Domain & Target
| Service | Domain | Target Folder VPS |
| :--- | :--- | :--- |
| **Backend API** | `https://api.sipanda.online` | `/home/sipanda/retribusi-api-staging` |
| **Citizen App** | `https://sipanda.online` | `/home/sipanda/retribusi-mobile-staging` |
| **Officer App** | `https://petugas.sipanda.online` | `/home/sipanda/retribusi-petugas-staging` |
| **Admin Panel** | `https://admin.sipanda.online` | `/home/sipanda/retribusi-admin-staging` |

## 🛠️ Protokol Pengecekan Deployment (Health Check)

Gunakan perintah `curl` untuk mengecek status hidup setiap domain:

1.  **API Health Check**: 
    - Endpoint: `/up` atau `/api/health`
    - Perintah: `curl -I https://api.sipanda.online/up`
    - Ekspektasi: `200 OK`

2.  **Frontend Uptime Check**:
    - Perintah: `curl -L -s -o /dev/null -w "%{http_code}" https://sipanda.online`
    - Ekspektasi: `200`

3.  **Sync Verification**:
    - Periksa apakah `.env.staging` di frontend sudah mengarah ke `api.sipanda.online`.
    - Periksa apakah versi build terbaru sudah naik dengan mengecek `Last-Modified` header atau meta tag jika tersedia.

## ⚠️ Penanganan Gagal Deploy (Emergency Protocol)

Jika CI/CD gagal atau domain tidak merespon:

### 1. Masalah Koneksi SSH/Authentication
Jika deployment gagal karena `Permission denied`, `Host key verification failed`, atau `can't connect without a private SSH key or password`:
- **Pin Version**: Selalu gunakan versi spesifik di workflow, contoh: `appleboy/ssh-action@v1.2.0` dan `appleboy/scp-action@v0.1.7`. Hindari penggunaan `@master`.
- **Port Explicit**: Pastikan parameter `port: ${{ secrets.VPS_PORT }}` (biasanya 22) disertakan secara eksplisit.
- **Sync Secrets**: Jika password VPS berubah, update rahasia `VPS_PASSWORD` di SELURUH repositori terkait menggunakan GitHub CLI (`gh secret set --repo [REPO]`).
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
