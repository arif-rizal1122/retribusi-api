# AM-BASE-03D — Normalisasi settlement dan rekap DLH

Tanggal checkpoint: 19 September 2026

Checkpoint ini menutup celah pembacaan status pada batas settlement dan laporan DLH. Callback H2H Bank Sultra menyimpan status bill persampahan sebagai `lunas`, sedangkan beberapa bill lama memakai `PAID`, `paid`, `success`, atau `settled`. Peta kepatuhan dan rekap tidak boleh memperlakukan variasi itu sebagai tunggakan.

## Perubahan

- `DlhReportController` memakai `CitizenBillStatus` pada status mentah bill, sehingga `lunas`/`PAID` masuk realisasi dan `UNPAID`/`pending`/`overdue` masuk piutang.
- Bill yang terhubung ke `dlh_lab_orders` dikeluarkan dari total persampahan. Nilai lab kemudian dihitung satu kali dari order yang sudah `PAID`, sedang `SAMPLING`/`ANALYZING`/`COMPLETED`, atau memiliki bill tertaut yang sudah lunas.
- Filter lab diberi nama kolom penuh ketika join bill agar SQLite dan MySQL tidak mengalami kolom `created_at` ambigu.
- `DlhSpatialMonitoringController` memakai status kanonik yang sama, termasuk untuk status callback H2H `lunas`.
- Bentuk response laporan dan GeoJSON tetap sama; hanya angka/status hasil baca yang dikoreksi. Tidak ada migration, callback bank, credential, atau perubahan UI internal.

## Bukti validasi

- `php artisan test tests/Feature/DlhSpatialComplianceTest.php tests/Feature/DlhBankSultraPaymentIntegrationTest.php --do-not-cache-result` — **5 test / 42 assertion lulus**. Mencakup settlement persampahan dan lab melalui H2H lokal, idempotensi callback, rekap, serta peta status `lunas`.
- `php artisan test tests/Feature/DlhTrashBillingTest.php tests/Feature/DlhLabServiceTest.php tests/Feature/CitizenDlhLabOwnershipTest.php tests/Feature/CitizenBillingReadTest.php --do-not-cache-result` — **27 test / 183 assertion lulus**.
- `vendor/bin/pint --test app/Http/Controllers/Api/V1/Dlh/DlhReportController.php app/Http/Controllers/Api/V1/Dlh/DlhSpatialMonitoringController.php tests/Feature/DlhBankSultraPaymentIntegrationTest.php tests/Feature/DlhSpatialComplianceTest.php` — lulus setelah formatter diterapkan.
- `git diff --check` dan PHP lint pada controller/test — lulus.

Test ini memakai database SQLite terisolasi dan driver H2H lokal; bukan bukti UAT bank, produksi, atau rekonsiliasi bank eksternal. Kontrak tarif resmi, scheduler, ekspor audit, dan produk bank citizen tetap berada pada task/dependensi masing-masing.

