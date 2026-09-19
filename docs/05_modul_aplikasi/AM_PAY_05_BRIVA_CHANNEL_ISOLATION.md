# AM-PAY-05 — Isolasi Resolver BRIVA Antar-Channel

Tanggal checkpoint: 19 September 2026.

Callback/inquiry BRIVA hanya boleh menyelesaikan \`PaymentRequest\` yang memang dibuat untuk channel \`BRI\` dan method \`VA\`. Resolver sebelumnya mencari \`va_number\` atau \`external_id\` tanpa batas channel; nomor yang kebetulan sama dari produk lain dapat terbaca sebagai BRIVA.

Perubahan:

- \`SnapBrivaService::resolveBill()\` memakai query bersama dengan \`payment_channel = BRI\` dan \`method = VA\` untuk lookup VA, \`customerNo\` sebagai external ID, dan fallback VA.
- PaymentRequest channel lain tetap tidak ditemukan oleh endpoint \`/api/snap/v1.0/transfer-va/inquiry\` maupun \`/payment\`.
- Tidak ada perubahan schema, migration, credential, callback Bank Sultra, atau UI petugas.

Bukti lokal:

- \`tests/Feature/Payment/Snap/SnapBrivaInquiryTest.php\` menguji PaymentRequest \`Bank Sultra\` dengan nomor VA sama ditolak sebagai BRIVA.
- \`php artisan test tests/Feature/Payment/Snap/SnapBrivaInquiryTest.php --do-not-cache-result\` lulus 4 test / 17 assertion.
- Uji lint/Pint dan suite SNAP yang dicatat pada checkpoint tetap diperlukan sebelum merge ke branch remote.

Batas: ini adalah isolasi resolver di aplikasi lokal. UAT bank, konfigurasi production, dan rekonsiliasi provider tetap berada pada dependensi PAY-01/PAY-03/PAY-04.
