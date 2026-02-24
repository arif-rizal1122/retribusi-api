# Tahap 9: Pengujian Keamanan Hak Akses (RBAC & Authorization)

Sistem harus mematuhi prinsip *Least Privilege*. Seorang Wajib Pajak tidak boleh mengakses data Admin, dan Petugas tidak boleh menghapus data.

## Skenario Pengujian Lintas Batas (Bypass Attempts)

- [ ] **Skenario 1: WP Mengakses Dashboard Admin**
  - **Aksi**: Login sebagai Wajib Pajak di Aplikasi Mobile, ambil token (Bearer Auth), lalu tembak rute `/api/admin/dashboard` menggunakan Postman/Curl.
  - **Hasil Diharapkan**: Sistem menolak mentah-mentah dengan HTTP Status `403 Forbidden` atau `401 Unauthorized`.
- [ ] **Skenario 2: Petugas Lapangan Menghapus Objek Pajak**
  - **Aksi**: Login sebagai Petugas, lalu mencoba mengirim *REST API DELETE* ke `/api/tax-objects/{id}`.
  - **Hasil Diharapkan**: Ditolak (HTTP `403 Forbidden`). Petugas hanya punya hak baca dan catat pembayaran.
- [ ] **Skenario 3: Wajib Pajak A Melihat SKPD Wajib Pajak B**
  - **Aksi**: WP "Budi" login, mencoba mengakses `/api/bills/{id_milik_anton}`.
  - **Hasil Diharapkan**: Sistem mengembalikan Error `403 Tidak Memiliki Akses` atau `404 Not Found` (Pemisahan Tenancy Wajib Pajak).
- [ ] **Skenario 4: Akses API Tanpa Token (Public Annonymous)**
  - **Aksi**: Tamu yang tidak login menembak `/api/user/profile`.
  - **Hasil Diharapkan**: HTTP Status `401 Unauthenticated`. Dilarang masuk.
