# 🌐 Status Infrastruktur *.sipanda.online
**Dicek**: 2026-04-22 09:54 WITA

## Domain Status

| Domain | Status | Keterangan |
|--------|--------|------------|
| `sipanda.online` | ✅ HTTP 200 | Landing page MPAD |
| `api.sipanda.online` | ✅ HTTP 200 (UP) | Backend Laravel - **Application up, 30ms** |
| `admin.sipanda.online` | ✅ HTTP 200 | Dashboard Admin - Online |
| `petugas.sipanda.online` | ✅ HTTP 200 | PWA Petugas Lapangan - Online |
| `mobile.sipanda.online` | ❌ HTTP 000 | Subdomain tidak terdaftar/belum dikonfigurasi |
| `app.sipanda.online` | ❌ HTTP 000 | Subdomain tidak terdaftar/belum dikonfigurasi |
| `warga.sipanda.online` | ❌ HTTP 000 | Subdomain tidak terdaftar/belum dikonfigurasi |
| `petugas.mpad.online` | ❌ HTTP 500 | **Internal Server Error** - via Cloudflare |

## Analisis Detail

### ✅ Yang Berjalan dengan Baik
- API Staging (`api.sipanda.online`) merespons dalam **30ms** — performa sangat baik
- Admin dan Petugas Staging sudah online

### ⚠️ Masalah yang Ditemukan

#### 1. `petugas.mpad.online` → HTTP 500
- Server melalui Cloudflare, mengindikasikan ada error di level aplikasi/backend
- Kemungkinan: `.env` tidak terkonfigurasi, atau ada dependensi yang tidak terpenuhi di production
- **Tindakan**: Perlu cek error log di server mpad.online

#### 2. Credential Staging Tidak Sesuai Lokal
- User `adminw1@baubaukota.go.id` dengan password `password` **tidak ada** di DB staging
- Perlu menjalankan `StagingUserSeeder` yang sudah dibuat hari ini
- Cara: Dari panel VPS → `php artisan db:seed --class=StagingUserSeeder --force`

#### 3. Subdomain yang Belum Ada
- `mobile.sipanda.online`, `app.sipanda.online`, `warga.sipanda.online` tidak merespons
- Ini bukan error — hanya belum dikonfigurasi di Nginx/DNS

## Langkah Prioritas

1. **[URGENT]** Perbaiki `petugas.mpad.online` HTTP 500 → Butuh akses VPS mpad.online
2. **[PENTING]** Jalankan `StagingUserSeeder` di DB staging agar testing bisa full-pass
3. **[OPSIONAL]** Konfigurasi subdomain `mobile.sipanda.online` jika dibutuhkan
