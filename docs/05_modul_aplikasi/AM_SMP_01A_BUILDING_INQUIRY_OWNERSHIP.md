# AM-SMP-01A/02A — Inquiry ID Bangunan Persampahan

Tanggal checkpoint: 19 September 2026

## Kontrak yang diterapkan

- `GET /api/dlh/trash/inquiry?query={ID_BANGUNAN}` dan alias `/api/v1/dlh/trash/inquiry` berjalan di dalam autentikasi Sanctum.
- `ID_BANGUNAN` memakai nilai `tax_objects.nop` yang sudah menjadi identifier unik pada skema saat ini. Pencarian exact-match tidak menerima nama, alamat, NIK, atau ID database.
- Sesi `Taxpayer` hanya dapat membaca objek persampahan miliknya. User internal tetap dapat melakukan inquiry operasional melalui route yang sama.
- Response hanya mengambil `Bill` dengan `tax_object_id` yang sama. Nominal `total_amount` berasal dari accessor Bill, dan status dikirim uppercase agar konsisten dengan mobile.
- Inquiry dibatasi `throttle:30,1`. Pesan 404 tidak membedakan objek tidak ada dengan objek milik akun lain.

## Batas yang masih terbuka

`nop` adalah identifier yang tersedia saat ini, belum bukti bahwa DLH telah menetapkan format resmi “ID Bangunan”. Backfill/master mapping, kebijakan pembayaran objek milik pihak lain, kategori/tarif resmi dan scheduler billing tetap memerlukan keputusan DLH/Bapenda. Perubahan ini tidak mengubah admin/petugas atau membuat fallback data di mobile.

## Bukti validasi

`DlhTrashBillingTest`, `DlhBankSultraPaymentIntegrationTest`, dan `DlhSpatialComplianceTest` lulus 12 test/65 assertion pada SQLite in-memory. PHP lint, Pint pada controller/test, route list untuk dua alias, dan `git diff --check` lulus. Tidak ada migration atau credential bank yang diubah.
