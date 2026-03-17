---
name: Production Deployment Manager
description: Skill khusus untuk manajemen deployment, monitoring, dan emergency recovery pada lingkungan production Pemkot Baubau (*.baubaukota.go.id & sipanda.online).
---

# 🏛️ Production Deployment Manager

Skill ini adalah standar operasional untuk menjaga kestabilan sistem MPAD di lingkungan live (Production).

## 📋 Ekosistem Production
| Service | Domain | Branch | Target Folder VPS |
| :--- | :--- | :--- | :--- |
| **Backend API** | `https://api.sipanda.online` | `main` | `/home/sipanda/retribusi-api` |
| **Citizen App** | `https://mpad.baubaukota.go.id` | `main` | `/home/sipanda/retribusi-mobile` |
| **Admin Panel** | `https://adminmpad.baubaukota.go.id` | `main` | `/home/sipanda/retribusi-admin` |
| **Officer App** | `https://petugasmpad.baubaukota.go.id` | `main` | `/home/sipanda/retribusi-petugas` |

## 🛠️ Protokol Pengecekan Kesehatan (Production Health Check)

Lakukan pengecekan berkala menggunakan `curl` untuk memastikan layanan publik tidak terganggu:

1.  **API Status**: 
    - Perintah: `curl -I https://api.sipanda.online/up`
    - Ekspektasi: `200 OK`
2.  **Frontend Availability**:
    - Periksa ketiga domain `.baubaukota.go.id`.
    - Pastikan SSL Sertifikat valid dan HTTPS aktif.
3.  **CORS Guard (Regression)**:
    - Pastikan domain `baubaukota.go.id` terdaftar di whitelist `config/cors.php` pada server API agar frontend bisa berkomunikasi.

## 🚀 Prosedur Deployment Aman (Safe Deploy)

1.  **Staging First**: Selalu uji coba di domain `*.mpad.online` (Staging) sebelum melakukan merge ke branch `main`.
2.  **Migration Watch**: Jika ada migrasi database, pastikan tidak ada perubahan destruktif (drop column) tanpa koordinasi. CI/CD akan menjalankan `php artisan migrate --force`.
3.  **Assets Optimization**: CI/CD frontend akan menjalankan `npm run build` yang secara otomatis mengarahkan `VITE_API_URL` ke `https://api.sipanda.online`.

## ⚠️ Penanganan Keadaan Darurat (Critical Incident)

Jika terjadi 500 Error atau sistem hang di Production:
1.  **Log Analysis**: SSH ke VPS, cek `/home/sipanda/retribusi-api/storage/logs/laravel.log`.
2.  **Service Restart**: Jika diperlukan, restart layanan web server (Nginx/Apache) atau Queue worker.
3.  **Rollback Strategy**: Jika deployment terbaru menyebabkan kegagalan fatal, segera revert commit di branch `main` atau lakukan `git reset --hard` ke tag versi stabil sebelumnya di VPS.

## 📡 Monitoring Sentry
Pantau error secara real-time melalui Dashboard Sentry yang dikonfigurasi pada file `.env.production`.
