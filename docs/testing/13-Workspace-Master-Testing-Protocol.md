# Workspace Master Strategy & Diagnostic Protocol

Protokol ini menghubungkan seluruh bagian sistem: **Backend (API)**, **Admin**, **Mobile**, dan **Petugas** untuk memastikan ketahanan sistem secara menyeluruh.

---

## 1. Arsitektur Konektivitas
Semua aplikasi frontend/mobile bergantung pada satu sumber kebenaran: **Mpad API**.

| Repository | Fungsi | Koneksi Utama | Port Default |
|:---|:---|:---|:---|
| `retribusi-api` | Core Logic & DB | Laravel (PHP 8.3) | `8000` |
| `retribusi-admin` | Dashboard Management | `apiFetch` → API Production | `3001` |
| `retribusi-mobile` | Portal Wajib Pajak | `apiFetch` (with prefix) | `3002` |
| `retribusi-petugas` | Alat Petugas Lapangan | `apiFetch` → API Production | `3003` |

---

## 2. Deteksi Error Lintas-Platform (Diagnostic Guide)

Jika muncul error, ikuti alur deteksi ini untuk mengetahui di mana masalahnya:

### A. Deteksi 401 (Unauthorized)
**Gejala**: Tiba-tiba logout atau gagal login.
*   **Penyebab 1**: Kredensial di frontend (quick login) tidak sama dengan Database.
*   **Penyebab 2**: Token expired di `localStorage`.
*   **Mitigasi**: Jalankan `php artisan tinker` untuk cek email & password di DB. Hapus `localStorage` jika perlu.

### B. Deteksi 500 (Internal Server Error)
**Gejala**: Muncul popup "Gagal" atau "Internal Server Error".
*   **Langkah 1**: Buka **Inspect Element > Network Tab**.
*   **Langkah 2**: Klik request yang merah, lihat tab **Response**.
*   **Langkah 3**: Cari key `error_detail` dan `trace` (Hasil "Hardening" yang sudah kita buat).
*   **Diagnosa Cepat**:
    *   `Undefined variable $request` → Bug kode (tambah param Request).
    *   `Column not found` → Masalah Database (jalankan migrasi).
    *   `Trying to access property of non-object` → Relasi null (tambah check `if($obj)`).

### C. Deteksi 422 (Validation Error)
**Gejala**: Input ditolak meskipun data dirasa benar.
*   **Penyebab**: Perbedaan limit karakter (misal: kode zona max 10 di validator tapi di input 20).
*   **Mitigasi**: Relax-kan aturan di Controller (misal: `max:255`).

---

## 3. Protokol "Anti-Gagal" Backend (Universal)

Terapkan pola ini di seluruh `Retribusi-API` untuk perlindungan 100%:

```php
try {
    // 1. Sanitasi string kosong ke null
    // 2. Inference opd_id (otomatis cari ID dari relasi)
    // 3. Simpan data
} catch (\Throwable $e) {
    // LOG LENGKAP ke server
    \Log::error($e->getMessage(), ['trace' => $e->getTraceAsString()]);
    
    // KIRIM DETAIL ke frontend untuk debug cepat
    return response()->json([
        'message' => 'Gagal: ' . $e->getMessage(),
        'error_detail' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}
```

---

## 4. Sinkronisasi Environment (Cross-Repo)

Pastikan file `.env` di seluruh workspace selaras:

| Variabel | Admin | Mobile | Petugas | API |
|:---|:---|:---|:---|:---|
| **API_URL** | `VITE_API_URL` | `VITE_API_URL` | `VITE_API_URL` | `APP_URL` |
| **TOKEN_KEY** | `token` | `retribusi_auth_token` | `token` | - |
| **USER_KEY** | `user` | `retribusi_auth_user` | `user` | - |

> [!IMPORTANT]
> Jika URL API di Produksi berubah, **KETIGA** frontend di atas wajib di-build ulang dengan `.env.production` yang baru.

---

## 5. Checklist Verifikasi Global (Setiap Fitur Baru)

Sebelum fitur dianggap "Selesai", wajib lolos test ini:

1.  **Backend Feature Test**: `php artisan test --filter NamaFiturTest`.
2.  **Super Admin Test**: Coba simpan data tanpa pilih OPD (pastikan auto-infer jalan).
3.  **OPD Admin Test**: Coba simpan data (pastikan `opd_id` tersaring benar).
4.  **Deployment Trace**: Setelah deploy ke VPS, jalankan `curl` test ke API Produksi untuk memastikan kode benar-benar sudah ter-update di server.
5.  **Audit `destroy()`**: Pastikan setiap method `destroy` di Controller menerima parameter `Request $request`.
6.  **PWA Accessibility**: Pastikan rute `/unduh` tersedia di Mobile & Petugas dengan tombol CTA yang responsif.

### Audit Keamanan & Hardening
Semua controller berikut telah di-harden dengan `try-catch (\Throwable)` dan diagnostik detail:
- Master Data: `Zone`, `Type`, `Classification`, `Rate`.
- Operasional: `Verification`, `Payment`, `MonthlyReport`, `PenaltyWaiver`.

---

## 6. Lokasi File Dokumentasi Terkait
👉 **[error_history_and_mitigation_registry.md](file:///Users/pondokit/.gemini/antigravity/brain/0d142205-22d1-41cf-8ae4-e0cbb929e29d/error_history_and_mitigation_registry.md)**
-   **Analisis Master Data**: `[master_data_analysis.md](file:///Users/pondokit/.gemini/antigravity/brain/0d142205-22d1-41cf-8ae4-e0cbb929e29d/master_data_analysis.md)`
-   **Skema Testing Master Data**: `[master_data_testing_schema.md](file:///Users/pondokit/.gemini/antigravity/brain/0d142205-22d1-41cf-8ae4-e0cbb929e29d/master_data_testing_schema.md)`
