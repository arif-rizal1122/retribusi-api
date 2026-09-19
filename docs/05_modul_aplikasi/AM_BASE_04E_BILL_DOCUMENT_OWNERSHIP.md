# AM-BASE-04E — Ownership Dokumen Tagihan Citizen

Tanggal checkpoint: 19 September 2026

## Perubahan

- `GET /api/bills/{bill}/skrd`, `GET /api/bills/{bill}/sspd`, dan `GET /api/bills/{bill}/sppt` memakai pemeriksaan izin yang sama.
- `Taxpayer` hanya dapat mengunduh dokumen dari bill dengan `taxpayer_id` miliknya sendiri.
- User internal dengan role berlingkup OPD (misalnya `opd`, `petugas`, `admin_ptsp`, atau `notaris`) hanya dapat membaca dokumen bill dengan `opd_id` yang sama.
- Role pengawasan global (`super_admin`, `admin`, `walikota`, `kepala_bapenda`, `pengawas`, `kabid_pengawas`, `kasubid_pengawas`, dan `viewer`) tetap dapat membaca dokumen lintas OPD untuk kebutuhan pengawasan.
- Guest, principal autentikasi yang tidak dikenali, dan user internal tanpa OPD yang bukan role global mendapat `403` sebelum service dokumen dipanggil.

## Batas route

- Route `/api/documents/*` tetap berada di balik middleware `admin` dan tidak diubah pada checkpoint ini; route tersebut adalah jalur dokumen operasional internal.
- Route `/api/public/pdf/*` tetap publik karena masih dipakai oleh layar legacy admin untuk verifikasi/unduhan public PDF. Route ini tidak menjadi jalur receipt citizen mobile.
- Tidak ada perubahan payload, template PDF, status bill, migration, credential, atau integrasi bank.

## Consumer

Mobile memakai path receipt additive dari payment request, termasuk `/api/bills/{bill}/sspd`. Pencarian source menemukan petugas memakai jalur internal `/api/documents/skrd/{billId}`; admin memakai public PDF legacy. Tidak ada consumer mobile/admin/petugas yang memakai tiga route `/api/bills/{bill}/skrd|sspd|sppt` selain kontrak receipt mobile yang sudah ada, sehingga response/template tetap dipertahankan.

## Validasi

- `php artisan test tests/Feature/BillDocumentOwnershipTest.php tests/Feature/CitizenBillingReadTest.php --do-not-cache-result`: 14 test, 133 assertion lulus.
- PHP lint untuk controller dan test serta `git diff --check` lulus.
- Audit source tiga frontend selesai; tidak ada perubahan frontend.

Checkpoint ini menutup ownership pada route dokumen bill yang dibagi dengan citizen. Parent AM-BASE-04 masih terbuka untuk scope route dokumen internal lainnya, hasil layanan dan setoran.
