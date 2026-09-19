# AM-BASE-03K — Audit trail rekonsiliasi gateway

## Tujuan

Report rekonsiliasi dari admin harus dapat ditelusuri setelah request selesai. Sebelumnya endpoint POST /api/bank-h2h/reconcile mengembalikan matched, mismatch, dan detail mismatch hanya pada response sesi, tanpa menyimpan report tersebut pada audit gateway.

## Perubahan

- Controller H2H mencatat request dan response rekonsiliasi melalui PaymentAuditService ke tabel payment_gateway_logs.
- Payload masuk tetap melalui masker payment existing; driver, transaksi report, nominal, dan referensi mismatch dapat ditelusuri untuk audit.
- Payload keluar menyimpan ringkasan matched/mismatch dan alasan detail, termasuk Tidak Ditemukan, Status pembayaran bukan success, Bill belum settled, dan Nominal Berbeda.
- Kegagalan driver juga dicatat sebagai event audit dengan status HTTP 500.
- Proses tetap report-only. Pencatatan ini tidak membuat Payment, tidak mengubah Bill, tidak menjalankan settlement, dan tidak melakukan reversal.
- Bentuk response endpoint tidak berubah, sehingga consumer admin H2HLogs tetap menggunakan kontrak existing. Mobile dan petugas tidak memakai endpoint ini.

## Verifikasi

tests/Feature/Api/V1/Bank/BankH2HTest.php memverifikasi response mismatch dan baris payment_gateway_logs untuk endpoint rekonsiliasi. Validasi tambahan yang wajib dicatat pada checkpoint adalah targeted test, lint PHP, dan git diff --check.

Audit ini belum menjadi antrean retry/review atau reverse inquiry provider. Status tersebut tetap menunggu kontrak endpoint bank dan keputusan operasional pada parent AM-BASE-03/AM-PAY-09.
