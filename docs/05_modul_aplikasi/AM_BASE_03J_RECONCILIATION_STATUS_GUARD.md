# AM-BASE-03J — Rekonsiliasi memakai status settlement yang benar

Tanggal checkpoint: 19 September 2026.

Rekonsiliasi Bank Sultra H2H dan QRIS sekarang hanya menghitung transaksi `matched` jika semua bukti lokal cocok:

- referensi bank ditemukan melalui alias yang sesuai (`ntb`, `rrn`, `reference_number`, atau `transaction_id`);
- nominal laporan sama dengan payment lokal;
- payment lokal berstatus `success`; dan
- bill terkait dianggap terminal oleh `Bill::isSettled()`.

Transaksi gagal, bill belum settled, nominal berbeda, dan referensi yang tidak ditemukan masuk `mismatch` dengan alasan yang dapat ditindaklanjuti. Response lama `matched`, `mismatch`, `details`, `ntb`, `bank_amount`, dan `system_amount` dipertahankan. Detail QRIS juga menerbitkan `ntb` secara additive agar modal rekonsiliasi admin dapat menampilkan referensi yang sama; tidak ada perubahan mobile atau petugas.

Rekonsiliasi tetap report-only. Perubahan ini tidak otomatis melunasi, membatalkan, atau menerbitkan Payment/receipt, sehingga kasus ambigu tetap memerlukan review sesuai kontrak bank.

Validasi lokal:

    php artisan test tests/Feature/Payment/Core/GenericH2HStillWorksTest.php --do-not-cache-result

Suite terarah lulus 4 test/38 assertion. Regression lintas core, DLH, citizen billing dan SNAP lulus 104 test/504 assertion; PHP lint dan `git diff --check` lulus. Admin `H2HLogs` tetap memakai endpoint rekonsiliasi existing. Uji memakai database test terisolasi dan bukan bukti rekonsiliasi bank produksi.

Parent AM-BASE-03 tetap terbuka untuk reverse inquiry provider, antrean retry/review dan UAT gateway eksternal.
