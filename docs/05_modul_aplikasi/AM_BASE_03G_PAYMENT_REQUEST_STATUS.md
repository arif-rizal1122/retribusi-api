# AM-BASE-03G — Status payment request pada batas citizen

Tanggal checkpoint: 19 September 2026

Response citizen untuk payment request dan active payment request pada
citizen bills sekarang memakai status kanonik yang sama. Status database
tidak ditulis ulang.

## Kontrak

Status top-level payment request dinormalisasi menjadi:

- draft
- pending
- processing_receipt
- paid
- expired
- failed
- cancelled
- unknown

Alias seperti success, settled, completed, processing, in_progress,
pending_confirmation, void, dan status yang tidak dikenal tidak lagi
dikirim mentah ke mobile. Label juga diterbitkan dari mapper yang sama.
Status pending tetap memakai label channel BRI atau Bank Sultra sesuai
payment_channel.

Field can_refresh dan can_cancel tetap hanya true untuk record storage
pending karena endpoint lifecycle saat ini hanya menerima transisi pending.
Normalisasi response tidak mengaktifkan driver, mengubah callback, atau
mengubah status database.

Active payment request di GET /api/citizen/bills memakai mapper yang sama.
Detail data.bills[] pada payment request tetap memakai CitizenBillStatus,
sehingga status payment request dan status bill tidak tercampur.

## Bukti validasi

- Suite lintas DLH, lab, citizen billing, payment claim, dan SNAP multi-bill:
  50 test / 360 assertion lulus.
- Pint test pada support class, dua controller, dan test: lulus.
- PHP lint dan git diff --check: lulus.
- Consumer audit tiga frontend: mobile sudah memiliki union
  PaymentRequestStatus yang mencakup status canonical dan hanya memakai
  endpoint citizen; admin dan petugas tidak memakai endpoint tersebut.

Test memakai SQLite terisolasi. Ini bukan bukti UAT bank, callback bank
eksternal, atau settlement produksi.
