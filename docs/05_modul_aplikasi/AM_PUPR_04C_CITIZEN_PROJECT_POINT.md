# Titik proyek rental warga

Checkpoint AM-PUPR-04C, 14 September 2026.

POST `/api/citizen/asset/rentals` menerima `koordinat` opsional berupa string `lintang,bujur` dalam derajat desimal WGS84, maksimum 100 karakter. Lintang -90 sampai 90 dan bujur -180 sampai 180. Spasi di sekitar komponen dibersihkan; presisi desimal tidak dibulatkan. Input kosong/null diperlakukan sebagai belum memilih titik. Format eksponen, tiga komponen, array, dan koordinat di luar rentang ditolak dengan HTTP 422 pada field `koordinat`.

Titik disimpan pada `asset_rentals.metadata.koordinat`. Controller membangun metadata sendiri; metadata kiriman client tidak diterima. Migration `2026_09_01_021409_add_geometry_polygon_to_asset_module_tables.php` telah menghapus kolom string `koordinat`; sebelumnya atribut yang diteruskan controller diabaikan model sehingga titik hilang. Perbaikan ini memakai kolom JSON yang sudah tersedia, tanpa migration tambahan. `polygon_geom` tetap untuk batas bidang dan tidak diisi dengan titik.

Create/list/detail citizen mengembalikan `data.koordinat` (atau elemen list) dari metadata sebagai string/null. Record lama tanpa titik tetap null. Titik lama yang sudah hilang tidak dapat dipulihkan tanpa data sumber. Penyimpanan ini belum menyediakan query spasial atau menyatakan titik masuk area layanan resmi.

Consumer mobile `src/services/assetService.ts` dan `src/lib/coordinates.ts` sudah mengirim format tersebut. Route internal `/api/asset/rentals` digunakan admin/petugas dan tidak diubah. Alur inspeksi internal mempertahankan metadata yang sudah ada. Penampilan titik pada dashboard belum termasuk checkpoint ini; integrasi dashboard dan validasi area resmi D04 tetap terbuka. Tidak ada perubahan tarif, kalkulator tronton, SOP, atau gateway.

Validasi aktual (APP_ENV=testing, DB_CONNECTION=sqlite, DB_DATABASE=:memory:, DB_URL kosong):

```powershell
php artisan test tests/Feature/CitizenAssetRentalTest.php --do-not-cache-result
php vendor/bin/pint app/Rules/Wgs84Point.php tests/Feature/CitizenAssetRentalTest.php --test
php -l app/Http/Controllers/CitizenAssetController.php
git diff --check
```

35 test/186 assertion lulus: tanggal tiga zona waktu, guest/internal/ownership, titik valid, persistensi/readback, metadata palsu, batas koordinat, format invalid, data lama dan input opsional. Sebelum perbaikan, enam kasus titik valid dan satu kasus koordinat di luar batas gagal. Pint, syntax dan diff check lulus.

Regresi mobile `npm.cmd run test -- src/pages/Aset.location.test.tsx src/components/MapPicker.test.tsx --pool=threads --maxWorkers=1` lulus 12 test. Pemeriksaan sebelumnya untuk Aset.history dan Aset.location lulus 17 test (ada file yang berulang, bukan 29 test unik). Frontend tidak diubah; typecheck/lint/build frontend tidak diulang. Browser lokal menampilkan gagal memuat karena MySQL 127.0.0.1:3306 menolak koneksi; belum ada konfirmasi visual perbaikan tanggal atau uji pengajuan end-to-end MySQL.
