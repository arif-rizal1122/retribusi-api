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

## 🧠 Aturan Penanganan Masalah Saat Testing
1. **Identifikasi Dini:** Jika hasil `run_command` dari script QA mengembalikan gagal/error tak terduga (contoh: status HTTP 500, exception di CLI), JANGAN langsung menerka. Dump exception ke STDERR untuk membaca detail baris kode.
2. **Lihat Registri Sejarah:** Sebelum memperbaiki bug, rujuklah (view_file) `testing/results/Mitigation_Registry.md` (if any) barangkali error tersebut adalah bug regresi yang solusinya sudah pernah dipetakan sebelumnya.
3. **Penyembuhan (Healing):** Buka file yang menyebabkan *error trace*, gunakan `replace_file_content` untuk membenahi, uji ulang script QA hingga *Passed*.
4. **Dokumentasikan:** Catat perbaikan Anda menggunakan format di atas ke _Registry Log_.

Dengan adanya *Error Registry* ini, pengetahuan siklus _development_ tidak pernah hilang dan pengujian environment Staging/Production di waktu mendatang dapat mengantisipasi *known issues* terlebih dahulu!

---
*Terakhir diperbarui: 2026-03-10 oleh Antigravity (Task Dashboard Expansion)*
