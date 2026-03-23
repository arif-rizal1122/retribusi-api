# Skema Pengujian E2E (End-to-End) Petugas & Admin Mpad

Skenario pengujian ini dirancang untuk memastikan bahwa integrasi fitur Pendaftaran Wajib Pajak, Pembuatan Tagihan, Pencatatan Pembayaran oleh Petugas, dan proses Verifikasi Admin berjalan tanpa error (termasuk *CORS*, *Data Not Found*, atau *Foreign Key Constraints*).

## Prasyarat Lingkungan Pendukung (Prerequisites)
1. **Server API aktif** (Lokal `localhost:8000` atau VPS Production `api.sipanda.online`).
2. **Setup Subdomain Frontend** (Mobile: `mpad.baubaukota.go.id`, Admin: `adminmpad.baubaukota.go.id`, Petugas: `petugasmpad.baubaukota.go.id`) dengan HTTPS dan CORS yang dizinkan.
3. **Akun Super Admin** BAPENDA (admin yang sudah didaftarkan: `admin@bapenda.go.id` dsb).
4. **Akun Petugas** BAPENDA (petugas yang sudah didaftarkan: `petugas@bapenda.go.id`).
5. Komando script E2E khusus: `php artisan test:production-e2e`.

---

## Skenario Pengujian Otomatis

Proses pengujian utama dilakukan melalui *command-line interface* (CLI) artisan, yang menjalankan skrip simulasi pemanggilan HTTP API murni. Skrip tersebut mengeksekusi urutan langkah berikut:

### 1. Uji Otentikasi (Authentication Test)
- **Aksi:** Memanggil endpoint `POST /api/login` sebagai Admin dan Petugas secara berurutan.
- **Ekspektasi:** Endpoint mengembalikan respon HTTP 200 beserta bearer token otentikasi.
- **Kasus Error jika Gagal:** Konfigurasi `.env` `APP_URL` atau sandi (`Mpad123#` vs `password`) tidak sesuai. 

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

### 8. Uji Aksesibilitas & Distribusi (Public Aliases & PWA)
- **Aksi:** Memasuki rute `/unduh` pada aplikasi *Mobile* dan *Petugas*.
- **Ekspektasi:** Laman merespons dengan HTTP 200. Tombol "Pasang Aplikasi Sekarang" atau "Lihat Panduan Pasang" muncul dengan bayangan premium (`shadow-blue-900/40`) dan label dinamis sesuai state `isInstallable`.
- **Validasi No-Screenshot:** Pastikan rute tidak melempar `404 Not Found` dan elemen `id="install-guide"` terdeteksi di DOM.

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

### 9. Uji Keamanan RBAC (Role-Based Access Control Isolation Test)
- **Aksi:** Memanggil endpoint khusus staf internal (cth: `GET /api/users`) menggunakan Bearer Token milik Wajib Pajak (Citizen).
- **Ekspektasi:** Endpoint wajib merespons dengan HTTP 403 (Forbidden).
- **Mitigasi Kondisi:** Jika merespons 200 OK, injeksi middleware (seperti `EnsureAdmin`) kemungkinan cacat. Pastikan middleware tidak meloloskan *instance* model `App\Models\Taxpayer` pada rute yang mensyaratkan `App\Models\User`.

### 10. Pengujian Konsistensi Environment (Stale Cache Mitigation Test)
- **Aksi:** Verifikasi respon aplikasi API setelah pembaruan parameter sensitif (kredensial database) pada file `.env` VPS yang disuntikkan oleh bot CI/CD.
- **Ekspektasi:** API segera merespons 200 OK tanpa error Database Connection (contoh 1045 Access Denied) pada rute yang dilindungi.
- **Mitigasi Kondisi (500 Server Error):** Jika respon gagal setelah *deploy* konfigurasi, kemungkinan memori servis *process manager* menahan *environment variables* usang (Stale Cache). Coba instruksi *reload* layanan PHP-FPM di VPS (contoh: `sudo systemctl reload php8.3-fpm` dan `php8.4-fpm`) untuk memaksa pemuatan ulang konfigurasi.

---

## Eksekusi Rutin Pengujian (How to Run)
Gunakan *shell script* kompilasi berikut untuk menguji dan memvalidasi siklus di atas:

```bash
# Menjalankan pengujian E2E integrasi di API Gateway
cd /Users/pondokit/Herd/retribusi-api
php artisan test:production-e2e
```
