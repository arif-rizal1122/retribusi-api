# Ownership order laboratorium citizen

Checkpoint AM-BASE-04B, 19 September 2026.

Order laboratorium yang dibuat melalui sesi `Taxpayer` sekarang menyimpan `taxpayer_id` dari model autentikasi tersebut. Nama pemohon, nama instansi, nomor telepon, atau ID lain di body hanya menjadi data kontak layanan dan tidak menentukan pemilik order.

`GET /api/dlh/lab/orders/{id}` dan `POST /api/dlh/lab/orders/{id}/billing` (beserta alias `/api/v1/dlh/...`) membatasi order ketika sesi adalah `Taxpayer`. Order milik warga lain menghasilkan respons 404 yang sama dengan order tidak ditemukan, sehingga endpoint tidak membocorkan keberadaan order. Pengguna internal model `User` mempertahankan akses operasional yang sudah dipakai alur analis/dashboard.

`POST /api/dlh/lab/orders/{id}/results` hanya menerima model `User` internal. Warga tidak dapat mengubah hasil, status, URL LHU, atau status TTE melalui endpoint tersebut. Public QR verification `/api/verify/dlh-lab/{orderNumber}` tetap merupakan endpoint informasi publik dan tidak diubah pada checkpoint ini; aturan dokumen e-LHU final, TTE, dan URL unduh tetap memerlukan sumber penerbit yang sah.

Mobile tetap memakai `/api/dlh/lab/parameters`, `/api/dlh/lab/orders`, `/api/dlh/lab/orders/{id}`, dan `/api/dlh/lab/orders/{id}/billing` melalui `src/services/dlhLabService.ts`. Tidak ada perubahan mobile, admin, petugas, database migration, tarif, gateway, atau upload dokumen. Akses internal tidak digunakan oleh mobile.

Validasi aktual (process env `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `DB_URL` kosong):

```powershell
php artisan test tests/Feature/CitizenDlhLabOwnershipTest.php tests/Feature/DlhLabServiceTest.php --do-not-cache-result
php -l app/Http/Controllers/Api/V1/Dlh/DlhLabServiceController.php
php -l tests/Feature/CitizenDlhLabOwnershipTest.php
php vendor/bin/pint app/Http/Controllers/Api/V1/Dlh/DlhLabServiceController.php tests/Feature/CitizenDlhLabOwnershipTest.php --test
git diff --check
```

Hasil: 7 test/34 assertion lulus. Regresi meliputi binding taxpayer, detail/billing lintas warga, penolakan upload hasil oleh warga, dan akses analis internal. Test mobile tidak dijalankan ulang karena source mobile tidak berubah; consumer route dan envelope diperiksa secara source audit.
