---
name: QA Error Registry & Mitigation Tactics
description: Skill ini wajib digunakan untuk membaca, mencatat, dan menyusun pola error yang terjadi selama proses Omni Workspace Testing agar mempermudah pembenahan dan menghindari error yang sama berulang pada masa mendatang.
---

# 🛡️ QA Error Registry & Mitigation Tactics

Skill ini berfungsi sebagai "Buku Pintar" bagi Anda (Agen) untuk mencatat semua isu *bug/exception* yang ditemukan saat menjalankan proses Testing QA, menyimpan akar penyebabnya, serta formulasi perbaikannya. 

Setiap kali Anda menemukan pesan kesalahan (Error 500, Parse Error, Syntax Error, dll) dari hasil test script, Anda wajib memperbarui direktori pencatatan mitigasi ini.

## 📂 Lokasi Penyimpanan Log Error
Seluruh daftar masalah dan penyelesaiannya harus dicatat di dalam repositori API pada folder berikut:
`testing/results/Mitigation_Registry.md`

Jika file tersebut belum ada, silakan buat.

## 📝 Format Pencatatan Skema Pembenahan
Ketika mencatatkan error baru ke `Mitigation_Registry.md`, gunakan format standar berikut:

```markdown
### [Bug ID / Nama Kasus] - [Tanggal]
- **Environment**: [Local / Staging / Production]
- **Endpoint/Kasus**: [Contoh: POST /api/petugas-tasks]
- **Deskripsi Error**: [Pesan error, contoh: "Attempt to read property retribution_type_id on null"]
- **Akar Masalah (Root Cause)**: [Penjelasan teknis kenapa ini terjadi, contoh: "Eloquent Global Scope memfilter Kueri sehingga Objek bernilai Null ketika dipanggil oleh Admin beda wilayah"]
- **Solusi (Mitigation)**: [Kode/langkah perbaikan, contoh: "Tambahkan pengecekan if(!$targetUser) atau gunakan withoutGlobalScope()"]
```

## 🧠 Pola Error Umum & Indikator Testing

### 1. CORS Policy Block
- **Indikator**: `net::ERR_FAILED` (CORS) di console browser.
- **Penyebab Utama**: Lupa menyertakan prefix `/api` pada endpoint.
- **Mitigasi**: Selalu cek prefix `/api` dan pastikan origin terdaftar di `config/cors.php`.

### 3. PBB API Authentication (401 Error)
- **Indikator**: `401 Unauthorized` saat melakukan Inquiry/Pay PBB padahal sudah login.
- **Penyebab Utama**: Token Bapenda kadaluarsa atau interceptor Axios diblokir oleh middleware.
- **Mitigasi**: Implementasikan `401 Interceptor` di Mobile/Petugas untuk auto-refresh session atau logout paksa jika token pusat (Bapenda) invalid.

### 4. Sync-All Timeout
- **Indikator**: Request hang lebih dari 60 detik saat Sync PBB massal.
- **Penyebab Utama**: Pemrosesan sekuensial ribuan data tanpa batching.
- **Mitigasi**: Gunakan Laravel Queue atau Chunking (50-100 data per batch) seperti dijelaskan di skill **Backend Expert**.

### 5. Seeder Schema Mismatch (QueryException)
- **Indikator**: `SQLSTATE[42S22]: Column not found` saat menjalankan `db:seed`.
- **Penyebab Utama**: Model/Seeder menembak kolom yang sudah dihapus atau tidak ada di migrasi terbaru (misal: kolom `status` pada `taxpayers`).
- **Mitigasi**: Selalu gunakan `DESCRIBE table_name` via tinker sebelum membuat seeder masif untuk memastikan integritas kolom. Gunakan `updateOrCreate` untuk idempotensi.

### 6. Infinite Recursion / Circular Auth Guard
- **Indikator**: Response 500 setelah penundaan lama (~9-10 detik), body kosong (atau JSON jika handler aktif), dan stack trace menunjukkan ribuan frame di `Sanctum\Guard` atau `RequestGuard`.
- **Penyebab Utama**: 
    1. Konfigurasi `sanctum.guard` menyertakan guard yang sendiri menggunakan driver `sanctum` (Circular Reference).
    2. Global Scope memanggil `Auth::user()` sebelum guard selesai meresolusi user, memicu loop.
- **Mitigasi**: 
    1. Pastikan `sanctum.guard` hanya berisi stateful guards (seperti `web`).
    2. Jangan panggil `Auth` facade di dalam Global Scope. Gunakan pola middleware (`SetScopeUser`) untuk menyuntikkan user ke Scope secara pasif SETELAH autentikasi selesai.

## 🧠 Aturan Penanganan Masalah Saat Testing
1. **Identifikasi Dini:** Jika hasil `run_command` dari script QA mengembalikan gagal/error tak terduga (contoh: status HTTP 500, exception di CLI), JANGAN langsung menerka. Dump exception ke STDERR untuk membaca detail baris kode.
2. **Lihat Registri Sejarah:** Sebelum memperbaiki bug, rujuklah (view_file) `testing/results/Mitigation_Registry.md` (if any) barangkali error tersebut adalah bug regresi yang solusinya sudah pernah dipetakan sebelumnya.
3. **Penyembuhan (Healing):** Buka file yang menyebabkan *error trace*, gunakan `replace_file_content` untuk membenahi, uji ulang script QA hingga *Passed*.
4. **Dokumentasikan:** Catat perbaikan Anda menggunakan format di atas ke _Registry Log_.
