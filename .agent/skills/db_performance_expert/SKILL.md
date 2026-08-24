---
name: DB Performance & Optimization Expert
description: Panduan komprehensif untuk audit, optimasi kueri (N+1), dan indeksasi database guna menjamin kecepatan akses aplikasi mPaD skala besar.
---

# 🚀 DB Performance & Optimization Expert

Gunakan skill ini sebagai acuan utama (**Source of Truth**) saat melakukan audit performa, menangani kelambatan aplikasi, atau merancang fitur baru yang melibatkan pemrosesan data besar pada ekosistem mPaD.

## 📖 Kamus Terminologi (Diagnosis)

Saat melakukan audit, gunakan istilah-istilah berikut untuk mendeskripsikan kendala:
- **N+1 Query Problem**: Pemanggilan database berulang kali di dalam loop (misal: mengambil data Bill di dalam loop Taxpayer).
- **Database Bottleneck**: Titik hambatan kinerja yang disebabkan oleh CPU, RAM, atau I/O disk yang mencapai batas.
- **Latency (Waktu Tunda)**: Durasi antara permintaan dikirim hingga respon diterima.
- **Missing Indexes**: Tidak adanya indeks pada kolom yang digunakan di `WHERE`, `JOIN`, atau `ORDER BY`.
- **IOPS Saturation**: Kejenuhan operasi baca/tulis pada media penyimpanan server.

---

## 🛠️ Standar Kodingan Cepat (Script Optimization)

Setiap script yang berinteraksi dengan database **WAJIB** mengikuti pola berikut:

### 1. Paksa Eager Loading (`with`)
**DILARANG** mengakses relasi di dalam loop tanpa memuatnya terlebih dahulu.
```php
// SALAH (Ciri-ciri N+1)
$users = User::all();
foreach($users as $user) { echo $user->opd->name; } // Memicu kueri baru per user

// BENAR (Eager Loading)
$users = User::with('opd')->get();
```

### 2. Gunakan Batch Operations untuk Bulk Data
Untuk operasi massal (seperti `bulkStore` tagihan), jangan gunakan `foreach` + `create()`.
- Gunakan `insert([])` dengan array data untuk pengiriman satu kali (Single Transaction).
- Untuk update massal, gunakan `upsert()` atau `update()` kolektif.

### 3. Pre-fetching & Memory Caching
Ambil data referensi (seperti Tabel Tarif atau Klasifikasi) satu kali di awal proses, masukkan ke dalam Laravel Collection, dan cari di memori aplikasi selama proses berlangsung.

---

## 📊 Metode Audit & Verifikasi

### 1. Gunakan `EXPLAIN`
Selalu jalankan `EXPLAIN` pada kueri SQL untuk memastikan:
- **`type`**: Harus `range`, `ref`, atau `const`. Hindari `ALL` (Full Table Scan).
- **`key`**: Memastikan indeks yang tepat terpilih.

### 2. Identifikasi Slow Queries
- Threshold standar: **1.0 detik**.
- Kueri yang melebihi 1 detik harus segera dioptimasi (tambahkan indeks komposit atau refactor join).

---

## 🏗️ Aturan Indeksasi (Database Level)

Hanya tambahkan indeks pada kolom-kolom berikut:
- **Foreign Keys**: Selalu beri indeks pada kolom `_id`.
- **Filtering Columns**: Kolom status (misal: `status`, `is_active`) dan tanggal (misal: `paid_at`, `created_at`).
- **Composite Index**: Jika kueri sering memfilter berdasarkan dua kolom sekaligus (misal: `opd_id` DAN `status`), buatlah indeks gabungan.

---

## 🛡️ Rencana Mitigasi (Troubleshooting)

Jika aplikasi terasa lambat secara tiba-tiba:
1.  **Cek Proses Berjalan**: Pastikan tidak ada script background (seperti `./run_all.sh`) yang memakan IOPS terlalu tinggi.
2.  **Cek N+1**: Gunakan Laravel Debugbar (di lokal) untuk melihat jumlah kueri per request. Jika > 50 kueri untuk satu tabel, pasti ada kebocoran loop.
3.  **Cek Cold Start**: Pastikan database server memiliki `innodb_buffer_pool_size` yang cukup (minimal 50-70% total RAM).

---
> [!TIP]
> **Performance First**: Lebih baik menulis sedikit lebih banyak baris kodingan demi mendapatkan **Eager Loading** daripada kodingan pendek yang membunuh database dengan **N+1**.
