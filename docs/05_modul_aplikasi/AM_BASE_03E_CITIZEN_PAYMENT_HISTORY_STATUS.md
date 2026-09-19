# AM-BASE-03E — Status bill pada riwayat pembayaran citizen

Tanggal checkpoint: 19 September 2026

Endpoint `GET /api/citizen/payments/history` sudah mengembalikan status bill
nested yang sama dengan kontrak citizen bills. Sebelumnya endpoint ini
mengembalikan nilai storage mentah seperti `lunas`, `PAID`, atau `pending`,
sehingga client harus menebak apakah sebuah transaksi sudah lunas.

## Kontrak

Field `data[].bill.status` sekarang dinormalisasi dengan
`CitizenBillStatus::normalize` menjadi salah satu status citizen (`paid`,
`pending`, `overdue`, `pending_verification`, `cancelled`, `expired`,
`disputed`, atau `unknown`). Status mentah tetap berada di storage dan tidak
diubah oleh endpoint read-only.

Field additive `data[].bill.status_label` berisi label Bahasa Indonesia dari
status kanonik. Field bill lain, envelope pagination, status pembayaran
top-level, dan ownership query tidak berubah. Bill tanpa relasi tetap
dikembalikan sebagai `null`.

Status `overdue` hanya dipakai oleh normalisasi ketika bill belum lunas dan
tanggal jatuh temponya sudah lewat. Nilai pembayaran top-level tetap memakai
kontrak `pending`, `success`, atau `failed`.

## Perubahan dan batas

- `PaymentController::history` memakai status raw bill agar accessor model
  tidak mengaburkan alias lama, lalu menerbitkan status dan label kanonik.
- Test citizen mencakup bill berstatus storage `lunas` dengan jatuh tempo
  lewat; response harus menjadi `paid` dengan label `Lunas`.
- Test H2H DLH tidak lagi membuat bill manual. Fixture menerbitkan bill lewat
  `DlhTrashBillingService`, kemudian menjalankan inquiry, payment callback,
  idempotensi, dan rekap dengan bill yang sama. Periode bulan berikutnya
  dipakai supaya penalti waktu berjalan tidak mengubah nominal fixture.
- Perubahan ini tidak mengaktifkan bank, tidak mengubah migration, credential,
  callback, dashboard internal, atau status storage.

## Bukti validasi

- `php artisan test tests/Feature/DlhSpatialComplianceTest.php tests/Feature/DlhBankSultraPaymentIntegrationTest.php tests/Feature/DlhTrashBillingTest.php tests/Feature/DlhLabServiceTest.php tests/Feature/CitizenDlhLabOwnershipTest.php tests/Feature/CitizenBillingReadTest.php --do-not-cache-result` — **33 test / 230 assertion lulus**.
- `vendor/bin/pint --test app/Http/Controllers/PaymentController.php app/Http/Controllers/Api/V1/Dlh/DlhReportController.php app/Http/Controllers/Api/V1/Dlh/DlhSpatialMonitoringController.php tests/Feature/CitizenBillingReadTest.php tests/Feature/DlhBankSultraPaymentIntegrationTest.php tests/Feature/DlhSpatialComplianceTest.php` — lulus.
- PHP lint pada controller dan dua test, serta `git diff --check` — lulus.

Consumer audit pada `retribusi-mobile`, `retribusi-admin`, dan
`retribusi-petugas` selesai. Mobile memakai endpoint history untuk tren dan
aktivitas, tetapi tidak bergantung pada status nested bill; tipe mobile
sekarang mendeklarasikan `CitizenBillStatus` dan `status_label` optional.
Admin dan petugas tidak memiliki consumer endpoint ini. `npm run typecheck`
dan `npm run build` mobile lulus. `npm run lint` masih gagal oleh 104 error
baseline di file lama yang tidak tersentuh; `npm test` belum dapat memuat
config Vitest karena `spawn EPERM` pada environment Windows ini.

Test memakai SQLite/H2H lokal terisolasi. Hasil tersebut bukan bukti UAT bank,
settlement produksi, atau rekonsiliasi rekening eksternal.
