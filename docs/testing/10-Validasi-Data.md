# Tahap 10: Pengujian Integritas & Validasi Data Ekstrem (Negative Testing)

Menguji seberapa tangguh sistem saat diserang dengan input data yang salah, tidak masuk akal, atau manipulasi pembayaran.

## Skenario Input Negatif & Cegah Rusak Data

- [ ] **Skenario 1: Manipulasi Pembayaran Minus (Negative Amount)**
  - **Aksi**: Petugas mencoba mensubmit nominal pembayaran sebesar `-500000` (Minus 500 Ribu) ke API Pembayaran.
  - **Hasil Diharapkan**: Laravel Form Request memantulkan *Error Validation*: `422 Unprocessable Entity - Nominal harus lebih besar dari 0`.
- [ ] **Skenario 2: Pembayaran Ganda (Double Payment) pada SKPD yang Lunas**
  - **Aksi**: Tagihan WP Budi sudah `paid`. Lalu Petugas tak sengaja mencoba memproses ulang pembayaran ke Bill ID yang sama.
  - **Hasil Diharapkan**: Ditolak dengan pesan: `400 Bad Request - Tagihan ini sudah berstatus lunas`. Tidak boleh ada uang masuk dobel tak tercatat.
- [ ] **Skenario 3: Payload SKPD Kosong (Empty Required Fields)**
  - **Aksi**: Admin merilis Tagihan Baru tanpa mencantumkan "amount" (Nominal Tagihan = null) atau tanpa "due_date".
  - **Hasil Diharapkan**: Ditolak langsung di pintu depan (HTTP 422 Validasi Required).
- [ ] **Skenario 4: Batas Karakter String Bypass (SQL Truncation Guard)**
  - **Aksi**: Nama WP diset 300 Karakter panjang (Melebihi limit varchar(255) Database).
  - **Hasil Diharapkan**: HTTP 422 - `String terlalu panjang, maksimal 255 karakter`, bukannya meledakkan aplikasi dengan error SQL `500 Internal Server Error`.
