# AM-BASE-04F — Otorisasi endpoint citizen service

Tanggal checkpoint: 20 September 2026

## Tujuan

Memisahkan aksi warga dari akun internal pada endpoint katalog, detail, pendaftaran objek, daftar tagihan, dan periode tagihan layanan citizen. Sebelumnya endpoint hanya memastikan ada user terautentikasi; akun internal dapat masuk ke controller citizen dan mencapai alur yang seharusnya hanya dimiliki `Taxpayer`.

## Perubahan API

`CitizenServiceController` kini memakai guard `authenticatedTaxpayer()` pada:

- `GET /api/citizen/services`
- `GET /api/citizen/services/pending-periods`
- `GET /api/citizen/services/{id}`
- `GET /api/citizen/services/{id}/bills`
- `POST /api/citizen/services/{id}/register`
- `GET /api/citizen/tax-objects`

Sesi `Taxpayer` tetap mendapat response dan payload yang sama. Guest tetap ditolak oleh autentikasi; `User` internal mendapat `403` sebelum query klasifikasi, validasi form, upload, atau pembuatan objek dijalankan. Tidak ada migration, perubahan role, perubahan payload warga, atau perubahan dashboard/admin/petugas.

## Dampak consumer

Mobile memakai katalog, detail, dan pendaftaran sebagai warga melalui `Taxpayer`; tidak perlu perubahan source. Audit source admin dan petugas tidak menemukan consumer endpoint `citizen/services`.

## Validasi

- Guard endpoint: **2 test / 11 assertion lulus**.
- Regresi service, citizen billing, dan registration billing: **16 test / 153 assertion lulus**.
- PHP lint, Pint `--test`, dan `git diff --check` lulus.

Checkpoint ini membuktikan otorisasi pada aplikasi lokal. Workflow keputusan internal dan integrasi dashboard tetap berada di luar checkpoint.
