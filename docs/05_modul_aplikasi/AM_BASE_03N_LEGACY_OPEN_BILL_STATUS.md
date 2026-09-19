# AM-BASE-03N — Normalisasi status tagihan terbuka legacy

Tanggal checkpoint: 20 September 2026

## Tujuan

Menjaga jalur pembayaran warga dan penerbitan tagihan tetap idempotent ketika data impor atau data lama menyimpan status terbuka sebagai `UNPAID` uppercase. Sebelumnya beberapa query memakai perbandingan case-sensitive sehingga tagihan yang sudah tampil sebagai dapat dibayar pada batas citizen dapat ditolak saat membuat BRIVA atau klaim transfer, atau dibuatkan tagihan periode yang sama untuk kedua kalinya.

## Perubahan API

- `Bill::openForPayment()` menjadi scope bersama yang mencocokkan `pending`, `overdue`, dan `unpaid` secara case-insensitive pada storage.
- `CitizenPaymentRequestController` dan `PaymentController` memakai scope tersebut saat memvalidasi bill untuk payment request BRIVA/transfer manual.
- `BillCreationService::findExistingBill()` mencocokkan status terbuka maupun terminal secara case-insensitive sehingga `UNPAID`, `PAID`, dan alias lama tetap dihormati ketika mencari bill periode yang sama.
- Nilai status di database tidak ditulis ulang. Payload response, route, otorisasi, gateway, migration, credential, dan kontrak frontend tidak berubah.

## Dampak consumer

Tidak ada perubahan payload atau endpoint. Mobile tetap menggunakan `/api/citizen/payment-requests` dan `/api/citizen/payments`; audit source pada mobile, admin, dan petugas tidak menemukan consumer lain yang perlu disesuaikan.

## Validasi

- Targeted payment dan billing: **23 test / 140 assertion lulus**.
- Regression bank, payment core, DLH, lab, citizen billing, SNAP, dan dokumen: **114 test / 560 assertion lulus**.
- PHP lint, Pint `--test`, dan `git diff --check` lulus.

Checkpoint ini hanya membuktikan perilaku aplikasi lokal. Provisioning, callback, dan UAT gateway bank tetap mengikuti task integrasi eksternal yang masih terbuka.
