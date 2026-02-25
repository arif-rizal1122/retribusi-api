# 11 - Indikator Pengetesan Filter Hak Akses Petugas & Wajib Pajak

Dokumen ini berisi indikator pengujian krusial terkait pembatasan visibilitas data berdasarkan klasifikasi pajak (hak akses/assignments) bagi pengguna ber-role "Petugas". Dokumen ini diseragamkan untuk mencakup seluruh lapisan pengujian perangkat lunak agar fungsi filter dievaluasi secara menyeluruh.

> **CATATAN KRITIKAL (/noss):** 
> Seluruh proses pengetesan pada semua lapisan pengujian di bawah ini **TIDAK MENGGUNAKAN SCREENSHOT** ataupun rekaman pengetesan. Validasi mutlak dilakukan melalui verifikasi CLI/Terminal, pengecekan *assertion* kode, perbandingan respons JSON, atau pengecekan baris database secara langsung.

---

## 1. Unit Testing & Feature Testing
**Tujuan:** Menguji fungsi individual dan logika relasi model database terkait penugasan Petugas tanpa memanggil seluruh siklus HTTP.
* **Skenario:**
  - Mocking objek `User` dengan `role="petugas"` dan memberikan 1 `retribution_classification_id` spesifik.
  - Memanggil model/service penghitung *revenue* dan *pending counts* secara langsung di level kode (`DashboardController->getRevenue`).
* **Indikator Keberhasilan:** 
  - *Assertion* bernilai *True* bahwa fungsi menghitung total data dengan akurat sesuai klasifikasi.
  - Query builder *toSql()* memuat klausa `where retribution_classification_id = ?`.

## 2. API Testing
**Tujuan:** Menguji endpoint dari sisi *request* dan *response* payload, mensimulasikan komunikasi antara *frontend* dan *backend*.
* **Skenario (GET /api/dashboard/map-potentials):**
  - Mengirimkan *Bearer Token* Petugas yang ditugaskan di "PBJT - Makan dan Minum".
* **Indikator Keberhasilan:** 
  - Status Code `200 OK`.
  - JSON Schema merespons dengan array *tax objects* di mana properti `classification_name` murni "PBJT - Makan dan Minum". Aset di luar klasifikasi tersebut sama sekali tidak ter-*serialize*.

## 3. CRUD Testing
**Tujuan:** Menguji kelayakan Create, Read, Update, Delete untuk Objek Pajak, Wajib Pajak, dan Tagihan pada yurisdiksi sang Petugas.
* **Skenario Create (POST /api/payments):**
  - Petugas mencoba *Submit* / mencatat pembayaran untuk `tax_object_id` dengan klasifikasi "PBJT - Parkir".
* **Indikator Keberhasilan:**
  - Transaksi *Insert* digagalkan.
  - Database `payments` tidak bertambah.
  - Mengembalikan API Error Code `403 Forbidden` dengan pesan validasi "Anda tidak ditugaskan untuk mengelola klasifikasi objek pajak ini".
* **Skenario Read (GET /api/tax-objects/{id}):**
  - Membaca detail Objek Pajak khusus yang di luar klasifikasinya.
  - Mengembalikan `403 Forbidden` atau `404 Not Found`.

## 4. UI Testing (E2E)
**Tujuan:** Mensimulasikan klik pengguna tanpa menggunakan validasi tangkapan layar, melainkan inspeksi state DOM dan assertion teks terminal.
* **Skenario:**
  - Mengeksekusi script *Headless Testing* (bisa menggunakan Cypress, Selenium, atau Dusk) yang *login* sebagai petugas.
  - Navigasi ke halaman Wajib Pajak (`/taxpayers`).
* **Indikator Keberhasilan:**
  - *DOM Element* pada tabel *List* tidak merender nama Wajib Pajak yang tidak terafiliasi.
  - Logika assertions murni dari pembacaan *Count of Table Rows*, elemen DOM teks, tanpa pengambilan *.png/screenshot*. Evaluasi di-output dalam Log Terminal.
