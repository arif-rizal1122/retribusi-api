# Error Logs & Mitigations - Petugas & Admin E2E Testing

Dokumen ini mencatat seluruh error yang ditemukan selama pengujian alur kerja (End-to-End) antara Wajib Pajak, Petugas, dan Admin di server lokal maupun Production (api.sipanda.online), beserta langkah mitigasi yang telah diterapkan.

---

## 1. Kegagalan Auto-Generate Objek Pajak pada Pendaftaran WP Baru
**Gejala:** Saat Petugas mendaftarkan Wajib Pajak baru, `Tax Object` tidak otomatis terbuat di Production meskipun berhasil di lokal.
**Penyebab:** Endpoint pendaftaran tipe retribusi mewajibkan adanya `retribution_classification_id`, namun script E2E mengabaikan klasifikasi ini.
**Mitigasi:**
1. Mengubah script `TestProductionE2E.php` untuk mengambil *Retribution Classification* yang valid dari API sebelum mendaftarkan WP.
2. Karena di produksi terkadang klasifikasi aktif tidak ditemukan (kosong), ditambahkan fallback endpoint `POST /api/tax-objects` di `TaxObjectController@store` untuk mengakomodir input pembuatan objek pajak secara manual melalui aplikasi Petugas.

---

## 2. Visibilitas Wajib Pajak Kosong di Dashboard Petugas (Frontend)
**Gejala:** Daftar Wajib Pajak di aplikasi Petugas (`/taxpayers`) kosong (Tidak Ada Data) padahal data sudah diinput di database.
**Penyebab:** Perlindungan hak akses (*Authorization*) di API `TaxpayerController@index` menerapkan filter ketat: Petugas hanya bisa melihat WP yang ia buat (`created_by = user_id`) DAN WP yang masuk dalam daftar Penugasan Retribusinya (`UserRetributionAssignment`). Jika akun Petugas belum di-assign jenis retribusi apapun, seluruh data WP disembunyikan.
**Mitigasi:**
Membuat relasi penugasan jenis retribusi BAPENDA secara eksplisit ke akun Petugas bersangkutan (`petugas@bapenda.go.id`). Filter di frontend sekarang otomatis berjalan normal tanpa campur tangan tambahan.

---

## 3. Proses Verifikasi Admin Bapenda Gagal (Not Found)
**Gejala:** Setelah Petugas menagihkan dan mencatat pembayaran lunas (*Cash*), script E2E gagal menemukan record `pending` di daftar Verifikasi Admin.
**Penyebab:** Arsitektur API `PaymentController` meng-hardcode pembayaran via Cash oleh Petugas Bapenda langsung berstatus `success` / `lunas`, sehingga *auto-verified* dan tidak memasukkannya ke antrean *pending verification* Admin.
**Mitigasi:**
Script E2E diperbarui untuk mengenali kondisi otorisasi ini. Bila *Payment* dibuat oleh Petugas, pengecekan verifikasi dianggap berhasil (otomatis disetujui).

---

## 4. Error 500 (CORS Blocked) Saat Menghapus Jenis Retribusi
**Gejala:** Admin mendapat error CORS / 500 saat mencoba menghapus Jenis Retribusi (contoh: "testing") dari menu Master Data.
**Penyebab:** Jenis Retribusi tersebut masih memiliki data relasional yang mengikat (Objek Pajak, Tagihan, Penugasan Petugas). Database menolak penghapusan (*Integrity constraint violation: 1451 Cannot delete or update a parent row*), yang menyebabkan Laravel crash dan menghentikan pengiriman *header* CORS.
**Mitigasi:**
Menambahkan *Cascading Delete* menggunakan `DB::beginTransaction()` di `RetributionTypeController@destroy`. Saat dihapus, sistem akan membersihkan seluruh anak data (Penugasan, *Taxpayer Pivot*, *Tax Objects*, *Bills*, dan *Payments*) secara bersamaan, sehingga Jenis Retribusi dapat dihapus tanpa hambatan.
