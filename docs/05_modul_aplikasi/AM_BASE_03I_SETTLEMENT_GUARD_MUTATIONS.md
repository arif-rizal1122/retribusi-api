# AM-BASE-03I — Settlement guard pada jalur mutasi pembayaran

Tanggal checkpoint: 19 September 2026.

Jalur mutasi API yang masih memakai pemeriksaan status lama kini memakai `Bill::isSettled()`:

- approval klaim manual pada `PaymentController` mengenali alias storage case-insensitive dan tetap mengecualikan payment yang sedang diverifikasi saat memeriksa payment sukses lain;
- `BankSultraDriver::createVA()` menolak pembuatan VA untuk bill terminal sebelum konfigurasi atau request eksternal diproses;
- command `app:pay-bill` dan void koreksi tidak lagi hanya mengenali status `lunas` dari accessor.

Regression menambahkan status `PAID` uppercase pada approval klaim dan create VA Sultra. Tidak ada perubahan route, envelope response, nominal, migration, `.env`, credential, atau UI. Consumer admin `PaymentVerification` tetap memakai endpoint dan response yang sama; mobile dan petugas tidak memakai endpoint approval tersebut.

Validasi lokal:

    php artisan test tests/Feature/Payment/Core/GenericH2HStillWorksTest.php tests/Feature/Payment/Core/CitizenPaymentClaimStillWorksTest.php --do-not-cache-result

Suite terarah lulus 9 test/55 assertion. Regression lintas core, DLH, citizen billing dan SNAP lulus 103 test/497 assertion. PHP lint seluruh file berubah dan `git diff --check` lulus. Uji ini memakai database test terisolasi dan bukan bukti UAT bank atau rekonsiliasi provider.

Parent AM-BASE-03 tetap terbuka untuk reverse inquiry, rekonsiliasi provider dan UAT gateway eksternal.
