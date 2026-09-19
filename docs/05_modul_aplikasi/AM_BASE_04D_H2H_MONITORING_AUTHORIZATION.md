# AM-BASE-04D — Otorisasi monitoring H2H

## Tujuan

Log gateway dan rekonsiliasi berisi payload bank serta detail mismatch. Data ini adalah fungsi monitoring internal, bukan endpoint citizen atau endpoint operasional OPD umum. Sebelumnya route hanya berada di balik autentikasi Sanctum dan belum memiliki role gate yang sama dengan halaman admin H2H Logs.

## Perubahan

- Route GET /api/bank-h2h/logs dan POST /api/bank-h2h/reconcile sekarang memakai middleware role:super_admin.
- Akun Taxpayer, guest, dan User internal dengan role selain super_admin mendapat 403 sebelum controller membaca log atau menjalankan driver.
- Super admin tetap memakai response dan alur existing. Rekonsiliasi tetap report-only dan audit trail AM-BASE-03K tetap berjalan.
- Tidak ada perubahan response, migration, credential, callback bank, atau frontend. Route H2H bank inbound /api/v1/bank/* tidak ikut terkena guard ini karena berada pada group route yang berbeda.

## Consumer dan verifikasi

Admin H2HLogs memang memakai kedua endpoint dan route UI-nya sudah dibatasi ke super_admin. Pencarian source mobile dan petugas tidak menemukan consumer endpoint monitoring.

Feature test BankH2HTest mencakup akses super admin, penolakan Taxpayer, dan penolakan User role admin. Validasi memakai database test terisolasi; ini bukan perubahan izin akses database produksi.
