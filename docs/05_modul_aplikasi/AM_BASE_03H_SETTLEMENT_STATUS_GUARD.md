# AM-BASE-03H — Settlement Status Guard pada Gateway

Tanggal checkpoint: 19 September 2026.

Gateway H2H dan driver pembayaran kini memakai satu guard terminal pada Bill::isSettled(). Guard mengenali status storage legacy PAID, paid, lunas, success, dan settled, serta payment record berstatus success.

Perubahan ini mencegah inquiry mengembalikan tagihan yang sudah lunas sebagai payable dan mencegah callback gateway membuat Payment kedua ketika status bill tersimpan dalam variasi legacy. Driver yang memakai guard ini adalah Bank Sultra H2H, BRI legacy, Mandiri QRIS, QRIS, callback BRIVA SNAP, dan callback VA Sultra yang sudah memiliki alur settlement lokal.

Validasi:

    php artisan test tests/Feature/Payment/Core/GenericH2HStillWorksTest.php tests/Feature/Payment/Core/CitizenPaymentClaimStillWorksTest.php tests/Feature/DlhBankSultraPaymentIntegrationTest.php tests/Feature/DlhSpatialComplianceTest.php tests/Feature/Payment/Snap tests/Unit/Payment/Snap --do-not-cache-result

Test baru mengirim lima variasi status terminal melalui endpoint H2H Bank Sultra dan memastikan semuanya ditolak tanpa payment duplikat. Uji tetap memakai database lokal terisolasi; tidak membuktikan UAT bank atau rekonsiliasi provider.

Tidak ada migration, perubahan .env, credential, response citizen, admin, petugas, atau mobile. Parent AM-BASE-03 masih terbuka untuk reverse inquiry/reconciliation eksternal dan UAT gateway.
