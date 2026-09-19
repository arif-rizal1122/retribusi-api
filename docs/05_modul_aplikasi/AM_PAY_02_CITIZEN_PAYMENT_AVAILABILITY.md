# AM-PAY-02 — Kesiapan metode pembayaran citizen

Tanggal checkpoint: 19 September 2026

API hanya mengiklankan metode pembayaran yang memiliki readiness gate yang
sesuai dengan produk. Flag tersedia pada setiap bill tetap menjadi sumber
keputusan mobile; mobile tidak menebak kesiapan dari nama bank atau nomor VA.

## Kontrak yang diterapkan

- Transfer manual tersedia bila bill boleh dibayar dan klasifikasi memiliki
  rekening tujuan yang valid.
- BRIVA tersedia bila SnapBrivaReadiness lulus. Gate memeriksa enabled flag,
  partner/client key, material signature, format VA, dan whitelist sesuai
  lingkungan.
- Virtual Account Bank Sultra citizen default tidak tersedia. Nomor yang
  sebelumnya dibentuk dari PaymentRequest ID belum membuktikan registrasi VA
  atau provisioning bank.
- Sultra citizen hanya boleh diiklankan dan dibuat bila
  BANK_SULTRA_CITIZEN_VA_ENABLED dan
  BANK_SULTRA_CITIZEN_VA_CONTRACT_CONFIRMED bernilai true. Flag kedua
  memerlukan bukti kontrak/provisioning Bank Sultra; mengubahnya tanpa bukti
  tidak mengaktifkan integrasi bank.
- Callback H2H generik Bank Sultra, callback VA SNAP, dan AFT tetap produk
  terpisah. Readiness Sultra citizen tidak menyatakan settlement atau UAT
  bank berhasil.

Saat Sultra belum siap, GET /api/citizen/bills mengembalikan
payment_options.sultra_va.available=false dengan pesan aman, dan POST
/api/citizen/payment-requests dengan method sultra_va ditolak HTTP 422 tanpa
membuat PaymentRequest.

## Validasi

- CitizenBillingReadTest dan CitizenPaymentRequestTest: 23 test / 206
  assertion lulus.
- Regresi DLH, lab, citizen billing, payment claim, SNAP multi-bill dan
  rekening manual: 54 test / 387 assertion lulus.
- PHP lint, Pint test, dan git diff check lulus.
- Audit consumer: mobile membaca flag sultra_va.available melalui
  citizenBillingService; PaymentMethodSelector/Pembayaran tidak membuat
  nominal atau status sendiri. Admin dan petugas tidak memakai endpoint
  citizen payment options.

Tidak ada migration, perubahan database, credential, callback, atau outbound
request bank pada checkpoint ini. Flag produksi tidak boleh dinyalakan sebelum
kontrak/provisioning dan UAT produk tersedia.
