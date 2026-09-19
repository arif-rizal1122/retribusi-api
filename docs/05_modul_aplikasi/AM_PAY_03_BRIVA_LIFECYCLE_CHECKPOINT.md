# AM-PAY-03 — Checkpoint Lifecycle BRIVA Lokal

Tanggal checkpoint: 19 September 2026.

Checkpoint ini menutup bukti aplikasi yang dapat diuji tanpa akses bank: callback BRIVA yang tiba setelah batas waktu payment request tidak boleh melunasi tagihan.

Perilaku yang dibuktikan:

- \`SnapBrivaService::resolveOpenBill()\` menolak \`PaymentRequest\` yang \`expires_at\` sudah lewat dengan response SNAP \`4032500\` / \`Transaction Expired\`.
- Bill tetap \`pending\`, payment request tetap menjadi bukti request yang kedaluwarsa secara waktu, dan tidak ada \`Payment\` sukses baru.
- Skenario cancel, paid bill, amount mismatch, multi-bill, idempotensi \`X-EXTERNAL-ID\`, inquiry, receipt per bill, signature, token, dan expiry timestamp tetap diregresikan pada suite SNAP.

Validasi:

\`\`\`text
php artisan test tests/Feature/Payment/Snap tests/Unit/Payment/Snap --do-not-cache-result
58 test / 197 assertion lulus
\`\`\`

Batas:

- Test memakai database terisolasi dan callback HTTP lokal; tidak menguji koneksi, finality, settlement, reversal/refund, atau status inquiry dari BRI.
- Status request yang sudah lewat waktu ditandai \`expired\` saat citizen membaca/refresh atau saat request baru dibuat; callback terlambat sendiri hanya ditolak dan tidak mengubah ledger.
- AM-PAY-03 parent tetap terbuka untuk UAT bank, rekonsiliasi, dan konfirmasi lifecycle/status produk BRIVA.
