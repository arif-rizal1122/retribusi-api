# [DONE] GIS Pemetaan Aktivitas Karcis & Ekosistem Notifikasi Terpadu (Tahap 1)

> **STATUS: SELESAI / IMPLEMENTED**  
> **Tanggal**: 28 September 2026  
> **Repositori Terdampak**: `retribusi-admin`, `retribusi-api`

---

## 1. Bagian A: GIS Pemetaan Aktivitas Karcis Harian (`retribusi-admin`)

### Fitur yang Diimplementasikan:
- **Dedicated Sub-View**: Menambahkan tab tampilan baru `Peta Aktivitas GIS` (`ticketViewMode === 'map-activity'`) pada modul Karcis Harian Disperindag.
- **Peta Interaktif Leaflet dengan 3 Mode**:
  - **Mode Titik Marker**: Render seluruh transaksi karcis harian yang memiliki titik koordinat GPS (`latitude`, `longitude`) dengan marker `pelataranTicketIcon`, popup detail pedagang, luas ($m^2$), juru pungut, nominal, jam, dan tombol modal audit.
  - **Mode Cluster Grup**: Mengelompokkan titik transaksi terdekat dengan lingkaran visual bertuliskan total karcis, nominal agregat, dan daftar pedagang di titik tersebut.
  - **Mode Heatmap Densitas**: Visualisasi gradien panas berbasis nilai transaksi/kepadatan (Hijau -> Amber -> Merah) menggunakan `CircleMarker` berskala dinamis.
- **Interactive Timeline & Playback**:
  - **Autoplay Loop (▶ Putar Animasi / ⏸ Jeda)**: Menganimasikan kronologi transaksi dari pagi (pukul 05:00) hingga sore dengan interval 1,5 detik per jam.
  - **Klik Bar Jam**: Setiap bar jam pada timeline dapat diklik langsung untuk memfilter peta ke jam tertentu.
  - **Shortcut Jam**: Tombol jam instan (05:00 s/d 18:00) di toolbar.
- **Toolbar Filter**:
  - Filter petugas / juru pungut spesifik secara real-time.
  - Integrasi dengan filter tanggal harian.
- **Panel Monitoring Spasial (Real-time Dashboard)**:
  - **KPI Cards**: Total Karcis GPS, Total Nominal Terkumpul, Jumlah Petugas Aktif, dan Jumlah Zona Terlayani.
  - **Timeline Aktivitas Per Jam**: Visualisasi grafik progresif penarikan retribusi dari subuh hingga sore hari.
  - **Breakdown Per Petugas**: Akuntabilitas kontribusi per kolektor beserta total lembar karcis dan nominal kas.
  - **Breakdown Per Zona/Sektor**: Sebaran intensitas pemungutan per blok pasar.
- **Verifikasi & Deployment Production**:
  - `npm run typecheck` lolos (exit code 0).
  - `npm run build` sukses (exit code 0).
  - Dideploy ke server production: `https://adminmpad.baubaukota.go.id/` (HTTP 200 OK).

---

## 2. Bagian B: Fondasi Pipeline & Ekosistem Notifikasi Terpadu M-PAD (Tahap 1)

### Komponen Backend (`retribusi-api`):
1. **Database Schema & Migrasi**:
   - `2026_09_28_000003_create_notification_infrastructure_tables.php`:
     - Tabel `notification_templates`: template katalog, grup pengguna (`warga`, `petugas`, `admin`), channel (`whatsapp`, `push`, `in_app`), prioritas, dan pola deep link.
     - Tabel `user_device_tokens`: registrasi token FCM / VAPID web push per platform (`web`, `android`, `ios`).
     - Upgrade tabel `notification_logs`: penambahan `template_code`, `user_id`, `idempotency_key`, `gateway_message_id`, `delivered_at`, `read_at`.
2. **Model Eloquent**:
   - `App\Models\NotificationTemplate`
   - `App\Models\UserDeviceToken`
   - Pembaruan `App\Models\NotificationLog` dengan relasi ke `User` dan `NotificationTemplate`.
3. **Engine Anti-Ban & Rotasi Pesan**:
   - `App\Services\SpintaxService`: generator variasi pesan otomatis format `{Halo|Selamat pagi|Yth.}` untuk menghindari deteksi spam WhatsApp dan substitusi token `{placeholder}`.
   - Unit test `Tests\Unit\SpintaxServiceTest`: 3 skenario pengujian 100% lulus.
4. **Master Dispatcher**:
   - `App\Services\NotificationDispatcher`: router multi-kanal (WhatsApp, Push, In-App), generator idempotency key SHA-256 (mencegah duplikasi kirim), integrasi `WaGatewayService`, dan auto-audit ke `notification_logs`.
5. **Konfigurasi Antrean Redis 3-Pool**:
   - `config/queue.php`: isolasi antrean `redis-notif-high` (OTP, lunas, eskalasi), `redis-notif-medium` (dispatch, tugas), dan `redis-notif-low` (blast pengingat jatuh tempo).
6. **Katalog 12 Template Notifikasi**:
   - `database/seeders/NotificationTemplateSeeder.php`:
     - **Warga**: `NOTIF-WRG-001` s/d `NOTIF-WRG-006` (OTP, Berkas Masuk, Revisi, SKRD Terbit, Pengingat H-7/H-3, Konfirmasi Lunas).
     - **Petugas**: `NOTIF-PTG-001` s/d `NOTIF-PTG-004` (Tugas Survei, Pelanggaran Geofence, Plafon Kas Tunai, Tutup Shift).
     - **Admin**: `NOTIF-ADM-001` s/d `NOTIF-ADM-002` (SLA Warning Jam 18, SLA Critical Jam 23).

---

## 3. Instruksi Eksekusi Lanjutan
Sesuai aturan `AGENTS.md`, migration dan seeder database tidak dijalankan otomatis dan memerlukan instruksi pengguna:

```bash
# Menjalankan migrasi infrastruktur notifikasi
php artisan migrate --path=database/migrations/2026_09_28_000003_create_notification_infrastructure_tables.php

# Menjalankan seeder katalog template notifikasi
php artisan db:seed --class=NotificationTemplateSeeder
```
