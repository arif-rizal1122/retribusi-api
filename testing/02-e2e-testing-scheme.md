# Skema Pengujian E2E (End-to-End) Petugas & Admin Sipanda

Skenario pengujian ini dirancang untuk memastikan bahwa integrasi fitur Pendaftaran Wajib Pajak, Pembuatan Tagihan, Pencatatan Pembayaran oleh Petugas, dan proses Verifikasi Admin berjalan tanpa error (termasuk *CORS*, *Data Not Found*, atau *Foreign Key Constraints*).

## Prasyarat Lingkungan Pendukung (Prerequisites)
1. **Server API aktif** (Lokal `localhost:8000` atau VPS Production `api.sipanda.online`).
2. **Akun Super Admin** BAPENDA.
3. **Akun Petugas** BAPENDA (contoh: `petugas@bapenda.go.id`).
4. Komando script E2E khusus: `php artisan test:production-e2e`.

---

## Skenario Pengujian Otomatis

Proses pengujian utama dilakukan melalui *command-line interface* (CLI) artisan, yang menjalankan skrip simulasi pemanggilan HTTP API murni. Skrip tersebut mengeksekusi urutan langkah berikut:

### 1. Uji Otentikasi (Authentication Test)
- **Aksi:** Memanggil endpoint `POST /api/login` sebagai Admin dan Petugas secara berurutan.
- **Ekspektasi:** Endpoint mengembalikan respon HTTP 200 beserta bearer token otentikasi.
- **Kasus Error jika Gagal:** Konfigurasi `.env` `APP_URL` atau sandi (`Sipanda123#` vs `password`) tidak sesuai. 

### 2. Uji Referensi Klasifikasi (Classification Reference Test)
- **Aksi:** Memanggil endpoint `GET /api/retribution-types` menggunakan *token* Petugas.
- **Ekspektasi:** Endpoint sukses mengembalikan minimal 1 objek `id` berjenis "Testing". Menyimpan `id` klasifikasi tersebut.

### 3. Pendaftaran Wajib Pajak oleh Petugas (Taxpayer Registration Test)
- **Aksi:** Memanggil endpoint `POST /api/taxpayers` dengan payload nama *dummy*, NIK valid (16 digit angka acak), serta menyematkan *array* `retribution_type_ids` dan `retribution_classification_ids`.
- **Ekspektasi:** Endpoint merespons dengan HTTP 201 (Created). Respons ini mengindikasikan bahwa Wajib Pajak baru sukses tersimpan dan hanya akan direlasikan ke Petugas tersebut (sesuai mitigasi filter).

### 4. Perolehan Objek Pajak (Tax Object Verification Test)
- **Aksi:** Memanggil API manual `POST /api/tax-objects` menggunakan valid `taxpayer_id` dari langkah ke-3. 
- **Ekspektasi:** API merespons objek baru dengan HTTP 201, bukan error validasi atau CORS.

### 5. Pembuatan Tagihan Baru (Billing Creation Test)
- **Aksi:** Memanggil endpoint `POST /api/bills` dengan referensi *Tax Object*. Payload membutuhkan `period` (format `Y-m`) dan jumlah nominal `total_amount`.
- **Ekspektasi:** Tagihan sukses diciptakan (`id` status `active`).

### 6. Pembayaran Tagihan oleh Petugas (Payment Processing Test)
- **Aksi:** Petugas menerima uang dan menyetorkan catatan pembayaran melalui endpoint `POST /api/payments`. Wajib menggunakan `amount`, `payment_method: cash`, `billing_period`.
- **Ekspektasi:** API merekam pembayaran. Logika auto-verifikasi Petugas mengubah status transaksi asli sebagai "Sukses" (`lunas`). 

### 7. Pengecekan Verifikasi Admin (Admin Verification Checklist)
- **Aksi:** Admin mengakses menu daftar `GET /api/verifications`.
- **Ekspektasi:** Tidak ada status API yang *crash* (500). Verifikasi pembayaran Petugas akan disetujui secara tidak langsung, daftar ini dipastikan tidak melontarkan error.

---

## Pengujian Manual: Retribution Type Deletion Cascading List
Selain skrip *Command-Line* E2E (*automated*), pengujian manual ini diperlukan untuk kasus Error Master Data Penghapusan (*Cascading Delete Error*).

**Langkah:**
1. Masuk ke halaman `http://localhost:3000/master-data/retribution-types` sebagai Admin menggunakan browser.
2. Buat Jenis Retribusi baru bertajuk "Test Cascading".
3. Tambahkan 1 Wajib Pajak *dummy*, pasangkan dengan "Test Cascading" (agar pivot table DB terisi).
4. Klik *Delete* (Hapus) pada *row* "Test Cascading".
5. **Ekspektasi Hasil:** Muncul pop-up atau respon `400 Bad Request` bertulis: "Gagal menghapus jenis retribusi karena masih memiliki objek pajak atau data terkait lainnya" di layar / Network tab Chrome. API tidak boleh crash dengan Status 500 (Internal Server Error) dan tidak boleh melontarkan peringatan CORS Policy.

### 8. Verifikasi Ketersediaan Dokumen Resmi (Official Document Verification)
- **Aksi:** Menjalankan script `php testing/stg4_document_availability.php` untuk memvalidasi seluruh endpoint dokumen.
- **Ekspektasi:** Endpoint merespons dengan HTTP 200/201 (Valid JSON atau PDF Stream).

---

## Eksekusi Rutin Pengujian (How to Run)
Gunakan *shell script* kompilasi berikut untuk menguji dan memvalidasi siklus di atas:

```bash
# Menjalankan pengujian E2E integrasi di API Gateway
cd /Users/pondokit/Herd/retribusi-api
php artisan test:production-e2e
```
