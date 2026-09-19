# AM-BASE-03F — Status bill pada payment request citizen

Tanggal checkpoint: 19 September 2026

Detail data.bills[] pada endpoint GET/POST /api/citizen/payment-requests
sekarang memakai kontrak status citizen yang sama dengan
GET /api/citizen/bills dan GET /api/citizen/payments/history.

## Kontrak additive

Setiap item data.bills[] tetap mengembalikan field lama dan kini memiliki:

- status: pending, overdue, paid, pending_verification, cancelled, expired,
  disputed, atau unknown;
- status_label: label Bahasa Indonesia dari status kanonik;
- can_pay: izin bayar hasil server, bukan keputusan frontend.

Status raw bill tidak ditulis ulang. Bill dengan payment request aktif diberi
pending_verification dan can_pay=false. Request yang sudah dibatalkan atau
kedaluwarsa tidak mengunci bill; bill pending/overdue kembali menunjukkan
status yang dapat dibayar. Bill lunas tetap paid walaupun due date lewat.

Payload payment request, lifecycle BRIVA/Sultra, nominal snapshot, VA,
callback, ownership, dan route tidak diubah. Tidak ada migration, credential,
driver bank, atau aktivasi gateway pada checkpoint ini.

## Bukti validasi

- php artisan test tests/Feature/DlhSpatialComplianceTest.php tests/Feature/DlhBankSultraPaymentIntegrationTest.php tests/Feature/DlhTrashBillingTest.php tests/Feature/DlhLabServiceTest.php tests/Feature/CitizenDlhLabOwnershipTest.php tests/Feature/CitizenBillingReadTest.php tests/Feature/Payment/CitizenPaymentRequestTest.php tests/Feature/Payment/Core/CitizenPaymentClaimStillWorksTest.php tests/Feature/Payment/Snap/SnapMultiBillPaymentTest.php --do-not-cache-result — 49 test / 346 assertion lulus.
- vendor/bin/pint --test app/Http/Controllers/Api/V1/Payment/CitizenPaymentRequestController.php tests/Feature/Payment/CitizenPaymentRequestTest.php, PHP lint, dan git diff --check — lulus.

Mobile paymentRequestService.ts membawa status nested ke tipe
PaymentRequestBillDetail dan menormalisasi alias lama secara defensif.
npm run typecheck, npm run build, dan ESLint terarah pada service/test
lulus. Vitest terarah belum dapat memuat vitest.config.ts karena spawn
EPERM pada environment Windows.

Audit consumer tiga frontend selesai: mobile hanya menampilkan detail bill
yang sudah ada dan tidak membuat keputusan nominal/status; admin dan petugas
tidak memakai endpoint citizen payment request. Perubahan response bersifat
additive dan tidak membutuhkan perubahan UI internal.

Test memakai SQLite/H2H lokal terisolasi. Ini bukan bukti UAT bank,
registrasi VA Sultra, atau settlement produksi.
