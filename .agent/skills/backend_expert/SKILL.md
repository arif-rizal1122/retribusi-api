---
name: Backend Expert (Tracking & Performance)
description: Panduan arsitektur sistem deteksi lokasi (Live Tracking GPS) pada ekosistem MPAD (Petugas, API, Pengawas) dan dampaknya terhadap performa server.
---

# Panduan Backend Expert: Arsitektur Live Tracking GPS

Gunakan panduan ini sebagai standarisasi (source of truth) saat mengembangkan, memelihara, atau mendebug fitur pelacakan lokasi (GPS) pada aplikasi retribusi (Mobile/Petugas) dan Dashboard Pengawas.

## 📡 Alur Data (Data Flow)

Sistem pelacakan lokasi di MPAD melibatkan 3 komponen utama yang harus dijaga harmoni kinerjanya:
1. **Frontend (Petugas App):** Menggunakan HTML5 Geolocation API (`watchPosition`) untuk mendapatkan lokasi GPS yang presisi tinggi (*high accuracy*).
2. **Backend (retribusi-api):** Menerima Payload koordinat melalui endpoint `PUT /api/user/location` dan menyimpannya di tabel `users`.
3. **Frontend (Dashboard Pengawas):** Secara berkala mem-polling (menarik) data lokasi terbaru dari semua petugas aktif melalui endpoint `GET /api/pengawas/petugas-locations` untuk di-render pada peta (Leaflet).

## 🛡️ Mekanisme Perlindungan Performa (Throttling)

Karena pengiriman lokasi GPS berpotensi memicu ribuan kueri `UPDATE` ke database yang dapat menyebabkan kelebihan beban CPU & I/O (Database Lock), **DILARANG KERAS** mengirimkan titik GPS dari aplikasi klien setiap detiknya.

Sistem MPAD menggunakan algoritma **Throttling Berlapis** yang wajib dipertahankan:

### 1. Throttling Waktu (Time Interval)
- Lokasi **TIDAK BOLEH** dikirimkan ke server lebih cepat dari interval **60 detik (1 menit)** sekali.
- Pergerakan apa pun yang terjadi dalam jeda 1 menit tersebut hanya akan merender *state* lokal (pada layar HP petugas).

### 2. Throttling Jarak (Distance Displacement)
- Meskipun interval 1 menit telah berlalu, aplikasi Petugas **TIDAK BOLEH** mengirim request ke server jika jarak perpindahan (menggunakan formula *Haversine*) kurang dari **50 meter** dari lokasi terakhir yang dikirim.
- Hal ini mencegah spamming jaringan saat petugas sedang diam (istirahat, di kantor polisi, dsb.).

### 3. Throttling Polling (Dashboard Pengawas)
- Saat fitur "LIVE ON" aktif, Dashboard Admin hanya menarik data dari `GET /api/pengawas/petugas-locations` setiap **60 detik**.

## 🛠️ Daftar Endpoint API Relevan

### `PUT /api/user/location`
- **Fungsi:** Meng-_update_ kolom `latitude` dan `longitude` di tabel `users`.
- **Payload:** `{"latitude": -5.4633, "longitude": 122.6012}`
- **Security:** Requires Bearer Token.

### `GET /api/pengawas/petugas-locations`
- **Fungsi:** Mengambil lokasi terbaru dari semua pengguna dengan `role = 'petugas'` dan `status = 'active'` yang koordinatnya tidak *null*.
- **Response:** Array of objects berisi id, nama, lat, lng, opd_id, dan waktu update terakhir (`updated_at` diformat via `diffForHumans()`).

## 💡 Best Practice & Future Scaling
Jika jumlah Petugas Penagih di lapangan berkembang hingga ratusan atau ribuan di masa depan:
- **JANGAN** menggunakan MySQL/PostgreSQL langsung untuk menyimpan _real-time streaming coordinates_.
- **MIGRASI** aliran lokasi ini menggunakan *In-Memory Data Store* seperti **Redis** (dengan *TTL/Expiration* agar data usang terhapus otomatis) atau memanfaatkan *WebSockets* (Pusher/Laravel Reverb) ketimbang *HTTP Polling*.
- Untuk penerapan di Kota Baubau dengan puluhan petugas, implementasi tabel MySQL dengan *Throttling Jarak & Waktu* saat ini sudah **ideal dan sangat aman**.

## 🔄 Manajemen Sinkronisasi Massal (PBB Sync-All)

Fitur `Sync All Data PBB` di Admin memicu pengambilan data (Inquiry) untuk ribuan NOP sekaligus. Hal ini dapat menyebabkan:
1.  **Timeout API Bapenda:** Backend Bapenda mungkin tidak kuat menerima ribuan request sekuensial dalam waktu singkat.
2.  **Long-Running Process:** Request HTTP Admin akan menggantung jika backend memproses sekuensial tanpa antrean.

**Strategi Delegasi & Optimasi:**
- **Batching:** Bagi ribuan NOP menjadi kelompok kecil (misal: 50 NOP per batch) dengan jeda (sleep) 100-200ms antar batch.
- **Background Jobs:** Sebaiknya gunakan Laravel **Queue** (`Queue::push`) untuk memproses sinkronisasi ini di latar belakang, sehingga UI Admin segera mendapatkan response "Sync Started".
- **Rate Limiting:** Hormati batas rate limit yang ditentukan oleh `API_PBB_BAUBAU_2026.md`.
