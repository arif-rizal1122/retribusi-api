# 🔑 Panduan Manajemen Kredensial

Dokumen ini menjelaskan **lokasi penyimpanan** kredensial tanpa mencantumkan nilai rahasianya (secrets) demi keamanan.

## 1. Kredensial VPS (Server)
- **Hostname:** `157.10.252.74`
- **Username:** `sipanda`
- **Penyimpanan:** 
  - **GitHub Secrets:** `VPS_HOST`, `VPS_USERNAME`, `VPS_PASSWORD`.
  - **Environment Lokasi:** Disimpan dalam password manager pribadi.
- **Akses Database:** Dikelola via file `.env` di VPS.

## 2. Kredensial API & Layanan Ketiga
- **Cloudinary (Media):** 
  - `CLOUDINARY_URL` disimpan di `.env`.
  - API Key & Secret tersedia di dashboard Cloudinary.
- **Sentry (Error Logs):** 
  - DSN String diatur di `.env` (Laravel) dan `vite.config.ts` (Mobile).
- **GitHub Token:** Dikelola via SSH Key (`~/.ssh/`) di komputer pengembang dan Action Token di GitHub.

## 3. File `.env` (Source of Truth)
Setiap lingkungan memiliki file `.env` yang **tidak masuk Git**:
- **Production:** `/home/sipanda/retribusi-api/.env`
- **Development:** `/home/sipanda/retribusi-api-dev/.env`
- **Local:** Terletak di root masing-masing folder repo.

## 4. Cara Rotasi Kredensial
Jika terjadi kebocoran keamanan:
1. Update password user `sipanda` di VPS.
2. Update **Actions Secrets** di Settings GitHub Repositori.
3. Update variabel terkait di file `.env` server.
4. Jalankan `php artisan config:cache` (Production) untuk memuat nilai baru.

---
> [!CAUTION]
> Jangan pernah meng-hardcode kredensial langsung ke dalam kode sumber atau dokumentasi Markdown yang di-push ke GitHub.
