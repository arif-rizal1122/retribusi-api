# Retribusi API Instructions

`retribusi-api` adalah sumber kebenaran untuk database, validasi, otorisasi, billing, pembayaran, dan integrasi eksternal.

## Mulai dari Ini

1. Baca `../AGENTS.md`, `../WORKBOARD.md`, dan `TODO.md`.
2. Cek `git status --short`, `git diff`, route terkait di `routes/api.php`, controller, model, migration, serta test yang ada.
3. Untuk pembayaran bank, baca dokumentasi SNAP/H2H lokal yang relevan sebelum mengubah implementasi.

## Batasan

- Jangan membuat frontend menjadi sumber kebenaran untuk nominal, status, atau otorisasi pembayaran.
- Jangan mengubah format response endpoint yang dipakai client tanpa mencari consumer di tiga frontend dan mencatat handoff.
- Setiap perubahan skema harus memiliki migration reversible, model relation/cast yang sesuai, serta validasi ownership/role.
- Jangan commit `.env`, credential bank, private key, dump database, atau log.
- Jangan menjalankan migration selain atas instruksi pengguna; sebutkan migration yang perlu dijalankan dalam laporan.

## Pembayaran dan SNAP

- Rekonsiliasi pembayaran harus idempotent, memakai nominal snapshot, dan mengunci record yang akan dilunasi.
- Citizen payment request hanya boleh mengakses data taxpayer pemilik request.
- Pembayaran multi-tagihan harus membuat audit/payment record yang konsisten untuk setiap tagihan.
- Bank integration tetap berada di API. Mobile/admin/petugas hanya memakai kontrak endpoint.

## Verifikasi

- Jalankan feature test yang paling dekat dengan perubahan, lalu `php artisan test` bila lingkungan mendukung.
- Untuk file PHP yang tersentuh, gunakan validasi yang sesuai dan laporkan test yang gagal karena environment secara terpisah dari kegagalan kode.
