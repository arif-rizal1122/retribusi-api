# 11 - Indikator Pengetesan Filter Hak Akses Petugas & Wajib Pajak

Dokumen ini berisi indikator pengujian krusial terkait pembatasan visibilitas data berdasarkan klasifikasi pajak (hak akses/assignments) bagi pengguna ber-role "Petugas".

> **Catatan Penting:** 
> Seluruh proses pengetesan pada indikator ini **TIDAK MENGGUNAKAN SCREENSHOT** maupun rekaman. Verifikasi mutlak dilakukan melalui perbandingan secara langsung di database query atau menggunakan CLI/Terminal via script pengujian. Gunakan script yang tersedia atau langsung cek UI secara teks visual.

---

## A. Pengetesan Filter Dashboard & Peta Lapangan
**Tujuan:** Memastikan Peta dan Statistik hanya menampilkan Objek Pajak pada klasifikasi yg ditugaskan ke petugas terkait.

* **Skenario 1: Verifikasi Titik Peta (Map Potentials)**
  - Masuk sebagai `petugas@bapenda.go.id` 
  - Pastikan titik Objek Pajak pada peta hanya menampilkan kategori (contoh) PBJT Makan dan Minum.
  - *Indikator Keberhasilan:* Response API `GET /api/dashboard/map-potentials` mengembalikan daftar objek pajak yang nilai `retribution_classification_id` di database-nya adalah id spesifik penugasan. Tidak boleh muncul `PBJT Parkir` atau lainnya.

* **Skenario 2: Analitik dan Pendapatan**
  - Pastikan total tagihan pending (count) sinkron dengan hitungan riil yang ada di database pada tingkat klasifikasi.
  - *Indikator Keberhasilan:* Response API `GET /api/dashboard/stats` nilai `pending_bills` tidak membaur dengan klasifikasi lain. 

---

## B. Pengetesan Modul Wajib Pajak & Objek Pajak
**Tujuan:** Mencegah Petugas melihat profil Wajib Pajak / Aset yang sepenuhnya berada di luar kendalinya.

* **Skenario 1: Daftar Wajib Pajak**
  - Petugas mengakses menu Master Wajib Pajak.
  - *Indikator Keberhasilan:* Wajib pajak yang muncul di `GET /api/taxpayers` pastilah mereka yang memiliki setidaknya 1 objek pajak pada klasifikasi yg ditugaskan pada Petugas. Jika seorang Wajib Pajak hanya punya `Reklame` dan Petugas ini hanya handle `Parkir`, WP tersebut tidak boleh muncul di daftar petugas.

* **Skenario 2: Daftar Objek Pajak (Aset)**
  - Petugas mengakses menu Master Objek Pajak.
  - *Indikator Keberhasilan:* Hanya objek dengan `retribution_classification_id` matching yang ditautkan di dalam response `GET /api/tax-objects`. Aset di luar klasifikasi gagal diakses bahkan melalui *direct URL* (akan direject `403/404`).

---

## C. Pengetesan Modul Billing (Tagihan) & Pembayaran
**Tujuan:** Memblokir Petugas dari memungut / menagih di klasifikasi yang tidak diotorisasi.

* **Skenario 1: Daftar Tagihan (Index Bills)**
  - Petugas menavigasi ke halaman Penagihan (Billing).
  - *Indikator Keberhasilan:* `GET /api/bills` **hanya** memberikan array tagihan di mana relasi `retribution_classification_id` sesuai dengan klasifikasi tugas Petugas. Tagihan jenis PBJT lain wajib disembunyikan.

* **Skenario 2: Perekaman Pembayaran Manual**
  - Petugas melakukan bypass *endpoint payload* / mengirim request langsung untuk membayarkan Objek Pajak klasifikasi lain.
  - *Indikator Keberhasilan:* Endpoint `POST /api/payments` harus mengembalikan error `403` dengan message "Anda tidak ditugaskan untuk mengelola klasifikasi objek pajak ini". Petugas hanya boleh mensukseskan (`success`) bill untuk tugasnya sendiri.
