# 🛡️ QA Mitigation Registry

### [BUG-005] Dashboard Kosong di Staging (Admin Wilayah) - 2026-03-10
- **Environment**: Staging (`*.mpad.online`)
- **Endpoint/Kasus**: `GET /api/dashboard/stats`
- **Deskripsi Error**: Dashboard menampilkan angka 0 atau data kosong meskipun data di database tersedia.
- **Akar Masalah (Root Cause)**: Sedang dianalisis. Dugaan awal:
    1.  Mismatch `opd_id` pada akun admin (Sudah diperbaiki, tapi hasil masih dikeluhkan kosong).
    2.  Filter tanggal default (Februari vs Maret). Seeded data berada di Februari, sedangkan dashboard default ke Maret.
    3.  Mismatch status kueri (`paid` vs `lunas`) yang menyebabkan agregasi sum 0.
    4.  Frontend gagal memproses format respons (misal: "86500000.00" string vs float).
- **Solusi (Mitigation)**: 
    - [x] Perbaikan `opd_id` pada user `admin.wilayah`.
    - [x] Sinkronisasi status `paid` -> `lunas`.
    - [x] Verifikasi lokal (Port 3001): Konfirmasi bahwa data muncul jika `opd_id`, `retribution_type_id`, dan status sinkron.
    - [/] Analisis integrasi frontend vs backend staging (Ongoing).
- **Update Lokal**: Akun `admin.wilayah` di lokal sudah diset passwordnya menjadi `password123` dan `opd_id` diset ke 4 (Bapenda).

### [CORS-API-MISSING] - 2026-03-10
- **Environment**: Local & Staging
- **Endpoint/Kasus**: Frontend Fetch calls in `retribusi-admin` & `retribusi-petugas`
- **Deskripsi Error**: "Access to fetch at '...' from origin '...' has been blocked by CORS policy: No 'Access-Control-Allow-Origin' header is present on the requested resource."
- **Akar Masalah (Root Cause)**: Pemanggilan API tidak menyertakan prefix `/api`. Backend Laravel hanya mengaktifkan middleware CORS untuk path yang didefinisikan di `config/cors.php` (biasanya `api/*`). Jika hit ke `/users` alih-alih `/api/users`, request dianggap sebagai non-API dan tidak menyertakan header CORS, memicu pemblokiran browser.
- **Solusi (Mitigation)**: Pastikan setiap pemanggilan via library `api` atau `fetch` selalu diawali dengan prefix `/api/` (Contoh: `api.get('/api/users')`).

### [MESSAGE-PORT-CLOSED] - 2026-03-10
- **Environment**: Browser (Chrome/Edge/Arc)
- **Endpoint/Kasus**: UI Interaction in React Apps
- **Deskripsi Error**: "Uncaught (in promise) The message port closed before a response was received."
- **Akar Masalah (Root Cause)**: Biasanya bukan bug kode aplikasi, melainkan gangguan dari Browser Extension (seperti Password Manager, AdBlocker, atau Grammerly) yang mencoba menginterjeksi DOM/Fetch namun koneksi terputus sebelum janji (promise) selesai.
- **Solusi (Mitigation)**: Informasikan user untuk mencoba di Incognito Mode atau matikan Extension yang mencurigakan. Secara kode, pastikan error handling di `apiFetch` tidak mem-blocking UI jika terjadi silent failure pada level browser extension.
