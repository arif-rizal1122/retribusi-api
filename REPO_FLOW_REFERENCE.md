# Retribusi API - Flow dan Breakdown Repo

Dokumen ini adalah referensi konsolidasi untuk repo `retribusi-api`. Sumber analisa berasal dari file Markdown yang sudah ada, route Laravel, controller, model, service, migration, seeder, test, dan konfigurasi aplikasi.

Scope yang sengaja tidak dirinci per file: `vendor/`, cache, log lokal, binary, hasil build, dan file dependency manager yang dihasilkan otomatis. File source, konfigurasi, dokumentasi, migrasi, seeder, test, dan script operasional dirangkum di bawah.

## Peran Repo

`retribusi-api` adalah backend utama sistem retribusi. Repo ini menyediakan:

- REST API untuk admin web, aplikasi warga, dan aplikasi petugas.
- Autentikasi token berbasis Laravel Sanctum.
- Domain wajib pajak, objek pajak/retribusi, tagihan, pembayaran, verifikasi, laporan, pengawasan, PBB Bapenda, dokumen resmi, TTE, dan bank host-to-host.
- Generator PDF untuk dokumen SKT, SKRD, SKPD, SSPD, SPPT, LKOK, surat teguran, dan dokumen publik.
- Job/scheduler untuk penalty, sync aset, dan perawatan status tagihan.

## Environment dan Cara Jalan

File `.env` dibutuhkan. Repo Laravel membaca konfigurasi dari `.env`, sedangkan `.env.example` menjadi template.

Perintah umum:

```bash
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Tambahan yang umum dipakai:

```bash
php artisan test
php artisan queue:work
php artisan schedule:work
cd wa-gateway && npm install && npm start
```

Variabel penting:

| Variabel | Fungsi |
| --- | --- |
| `APP_URL` | Base URL API dan generator URL publik. |
| `DB_*` | Koneksi database utama. |
| `SANCTUM_STATEFUL_DOMAINS` | Domain frontend yang dianggap stateful bila dipakai. |
| `CORS_ALLOWED_ORIGINS` | Origin admin/mobile/petugas yang boleh akses API. |
| `FILESYSTEM_DISK` | Disk upload dokumen/bukti bayar. |
| `WA_GATEWAY_URL` | Integrasi gateway WhatsApp opsional. |
| `BANK_H2H_*` | Konfigurasi partner bank, signature, dan callback host-to-host. |
| `TTE_*` | Konfigurasi layanan tanda tangan elektronik. |

## Alur Data Utama

1. Pengguna login lewat `/api/login` atau warga lewat `/api/citizen/login`.
2. `AuthController` memvalidasi kredensial, status user, approval OPD, lalu membuat token Sanctum.
3. Frontend mengirim `Authorization: Bearer <token>` ke endpoint protected.
4. Data master dibuat oleh admin/OPD: jenis retribusi, klasifikasi, zona, tarif, OPD, wajib pajak, dan objek pajak.
5. Tagihan dibuat dari objek pajak, periode, tarif, simulasi formula, atau integrasi PBB.
6. Pembayaran dibuat oleh warga/petugas/admin, bukti bayar dapat diupload, lalu status diverifikasi.
7. Sistem membuat dokumen resmi lewat service PDF dan dapat menandatangani dokumen via TTE.
8. Dashboard, laporan, audit, dan peta menarik data agregasi dari tagihan, pembayaran, lokasi petugas, objek pajak, serta pengawasan lapangan.

## Alur Autentikasi dan Otorisasi

- Public login: `/api/login`, `/api/citizen/login`, `/api/citizen/register`, `/api/opd/register`.
- Token: Sanctum personal access token.
- Middleware utama:
  - `auth:sanctum` untuk endpoint protected.
  - `scope_user` untuk membatasi akses berdasarkan scope/role token.
  - `bank_h2h` untuk endpoint host-to-host bank.
  - `throttle` untuk login/register.
- Role yang terlihat dipakai lintas frontend: `super_admin`, `admin`, `opd`, `petugas`, `verifikator`, `pengawas`, `viewer`, dan user warga/citizen.
- Logout menghapus token aktif.
- Perubahan password memverifikasi password lama terlebih dahulu.
- Update lokasi petugas disimpan lewat endpoint lokasi user.

## Keamanan

- Password diverifikasi dengan `Hash::check`.
- Password disimpan hash Laravel.
- Login menolak user nonaktif dan OPD yang belum approved.
- Endpoint bank H2H menggunakan middleware signature khusus.
- Endpoint upload terpusat di `UploadController`, perlu validasi tipe/ukuran sesuai konfigurasi Laravel.
- Endpoint protected menggunakan bearer token.
- CORS harus membatasi origin admin/mobile/petugas.
- PDF publik hanya boleh mengandalkan nomor dokumen/token verifikasi, bukan ID internal sensitif.
- Test keamanan berada di `tests/Feature/SecurityValidationTest.php`.

## Endpoint Utama

Daftar ini berasal dari `routes/api.php`.

### Public

| Method | Path | Fungsi |
| --- | --- | --- |
| `POST` | `/api/opd/register` | Registrasi OPD. |
| `POST` | `/api/login` | Login admin/OPD/petugas/verifikator. |
| `POST` | `/api/citizen/login` | Login warga. |
| `POST` | `/api/citizen/register` | Registrasi warga. |
| `GET` | `/api/opds` | Daftar OPD publik. |
| `GET` | `/api/citizen/bills` | Cek tagihan warga dari parameter publik. |
| `GET` | `/api/verify/bill/{number}` | Verifikasi nomor tagihan. |
| `GET` | `/api/verify/payment/{number}` | Verifikasi nomor pembayaran. |
| `GET` | `/api/public/pdf/skrd/{number}` | PDF SKRD publik. |
| `GET` | `/api/public/pdf/skpd/{number}` | PDF SKPD publik. |
| `GET` | `/api/public/pdf/sspd/{number}` | PDF SSPD publik. |
| `GET` | `/api/simulate-tax` | Simulasi pajak/retribusi. |
| `GET` | `/api/tax-formulas` | Daftar formula tarif. |
| `GET` | `/api/pbb/classifications` | Klasifikasi PBB. |
| `GET` | `/api/pbb/lookup-class` | Lookup kelas PBB. |
| `GET` | `/api/pbb/calculate` | Kalkulasi PBB. |
| `POST` | `/api/pbb/bapenda/inquiry` | Inquiry PBB Bapenda. |

### Protected User

| Method | Path | Fungsi |
| --- | --- | --- |
| `POST` | `/api/logout` | Logout token aktif. |
| `GET` | `/api/user` | Profil user login. |
| `PUT` | `/api/user/profile` | Update profil user. |
| `PUT` | `/api/user/password` | Ganti password. |
| `POST` | `/api/user/location` | Update lokasi petugas. |
| `GET` | `/api/me` | Profil ringkas untuk frontend. |
| `POST` | `/api/upload` | Upload file/bukti/dokumen. |
| `GET/POST` | `/api/citizen/services` | Layanan warga dan registrasi layanan. |
| `GET/POST` | `/api/citizen/reports` | Pelaporan SPTPD/warga. |
| `GET/POST` | `/api/citizen/complaints` | Pengaduan warga. |
| `POST` | `/api/pbb/bapenda/link-nop` | Kaitkan NOP PBB ke akun. |
| `GET` | `/api/pbb/bapenda/my-objects` | Objek PBB milik user. |
| `POST` | `/api/pbb/bapenda/pay` | Pembayaran PBB. |
| `GET` | `/api/pbb/bapenda/my-transactions` | Transaksi PBB user. |
| `GET` | `/api/dashboard/*` | Statistik dashboard, tren, peta. |
| `apiResource` | `/api/users` | CRUD user. |
| `apiResource` | `/api/opds` | CRUD OPD. |
| `apiResource` | `/api/taxpayers` | CRUD wajib pajak. |
| `apiResource` | `/api/tax-objects` | CRUD objek pajak/retribusi. |
| `apiResource` | `/api/bills` | CRUD tagihan. |
| `apiResource` | `/api/payments` | CRUD pembayaran. |
| `apiResource` | `/api/retribution-types` | CRUD jenis retribusi. |
| `apiResource` | `/api/retribution-classifications` | CRUD klasifikasi retribusi. |
| `apiResource` | `/api/retribution-rates` | CRUD tarif retribusi. |
| `apiResource` | `/api/zones` | CRUD zona. |
| `apiResource` | `/api/petugas-tasks` | Tugas petugas. |
| `apiResource` | `/api/spot-checks` | Pemeriksaan lapangan. |
| `apiResource` | `/api/complaints` | Kelola pengaduan. |
| `apiResource` | `/api/billboards` | Pendataan reklame. |
| `apiResource` | `/api/documents` | Dokumen resmi. |
| `apiResource` | `/api/tax-educations` | Edukasi pajak/retribusi. |

### Bank Host-to-Host

| Method | Path | Fungsi |
| --- | --- | --- |
| `POST` | `/api/v1/bank/inquiry` | Inquiry tagihan oleh bank. |
| `POST` | `/api/v1/bank/payment` | Notifikasi pembayaran bank. |
| `POST` | `/api/v1/bank/reversal` | Reversal pembayaran bank. |

## Model Domain

| Model | Fungsi |
| --- | --- |
| `User` | Akun admin, OPD, petugas, verifikator, pengawas, warga. |
| `Opd` | Organisasi perangkat daerah pengelola layanan. |
| `Taxpayer` | Data wajib pajak/retribusi. |
| `TaxObject` | Objek pajak/retribusi yang menjadi dasar tagihan. |
| `Bill` | Tagihan per objek/periode. |
| `Payment` | Pembayaran dan status verifikasi. |
| `RetributionType` | Jenis retribusi. |
| `RetributionClassification` | Klasifikasi layanan/tarif. |
| `RetributionRate` | Tarif berdasarkan klasifikasi, zona, atau formula. |
| `Zone` | Zona tarif/lokasi. |
| `PbbObject` | Objek PBB terhubung ke data Bapenda. |
| `PbbPayment` | Transaksi pembayaran PBB. |
| `PbbSppt` | Data SPPT PBB. |
| `PbbZnt` | Zona nilai tanah PBB. |
| `PenaltyWaiver` | Pengajuan/persetujuan penghapusan denda. |
| `SpotCheck` | Pemeriksaan lapangan. |
| `PetugasTask` | Penugasan petugas. |
| `Complaint` | Pengaduan warga. |
| `OfficialDocument` | Metadata dokumen resmi. |
| `DocumentSequence` | Nomor urut dokumen. |
| `BankH2HLog` | Audit transaksi bank H2H. |
| `AuditLog` | Audit perubahan sistem. |
| `EnforcementCase` | Kasus penertiban/pengawasan. |
| `FieldTeam` | Tim lapangan. |
| `FieldOfficerLocation` | Posisi petugas. |
| `Billboard` | Data objek reklame. |
| `TaxEducation` | Konten edukasi. |
| `TaxReport` | Laporan/SPTPD warga. |

## Breakdown File Source

### Root dan Konfigurasi

| File | Fungsi |
| --- | --- |
| `README.md` | Dokumentasi ringkas proyek/API. |
| `.env.example` | Template environment. |
| `composer.json` | Dependency PHP, script Composer, autoload. |
| `composer.lock` | Lock dependency PHP. |
| `artisan` | Entry CLI Laravel. |
| `phpunit.xml` | Konfigurasi test PHPUnit. |
| `package.json` | Dependency Node untuk asset/tooling jika dipakai Laravel. |
| `vite.config.js` | Konfigurasi build asset Vite. |
| `postcss.config.js` | Konfigurasi PostCSS. |
| `tailwind.config.js` | Konfigurasi Tailwind untuk asset Laravel. |
| `deploy.sh`, `deploy-webhook.php` | Script deployment dan webhook deploy. |
| `deploy-ssh-key.txt`, `id_rsa*` | Material SSH lokal; jangan commit rahasia baru. |
| `production-api.txt` | Catatan host/produksi. |

### `routes/`

| File | Fungsi |
| --- | --- |
| `routes/api.php` | Semua endpoint REST utama. |
| `routes/web.php` | Route web Laravel, health/public view bila ada. |
| `routes/console.php` | Registrasi command closure/scheduler console. |

### `app/Http/Controllers/Api/`

| File | Fungsi |
| --- | --- |
| `AuthController.php` | Login/logout, profil, password, lokasi, auth warga. |
| `UserController.php` | CRUD user dan role. |
| `OpdController.php` | CRUD OPD dan approval/registrasi OPD. |
| `TaxpayerController.php` | CRUD wajib pajak dan pencarian NIK/NPWPD. |
| `TaxObjectController.php` | CRUD objek pajak, pending period, tagging lokasi. |
| `BillController.php` | CRUD tagihan, pembayaran tagihan, PDF tagihan. |
| `PaymentController.php` | CRUD pembayaran, upload bukti, update status. |
| `RetributionTypeController.php` | CRUD jenis retribusi. |
| `RetributionClassificationController.php` | CRUD klasifikasi retribusi. |
| `RetributionRateController.php` | CRUD tarif dan formula. |
| `ZoneController.php` | CRUD zona. |
| `DashboardController.php` | Statistik, tren pendapatan, peta potensi. |
| `AnalyticsController.php` | Analitik realisasi dan agregasi. |
| `ReportController.php` | Laporan summary, recent, performa, BPK. |
| `CitizenServiceController.php` | Katalog layanan dan registrasi layanan warga. |
| `TaxReportController.php` | Laporan/SPTPD warga. |
| `ComplaintController.php` | Pengaduan warga/admin. |
| `PetugasTaskController.php` | Penugasan dan update status tugas petugas. |
| `SpotCheckController.php` | Spot check lapangan. |
| `PengawasController.php` | Dashboard pengawas, audit, peta, enforcement. |
| `EnforcementController.php` | Kasus penertiban dan approval tindakan. |
| `PenaltyWaiverController.php` | Pengajuan dan approval penghapusan denda. |
| `DocumentController.php` | Dokumen resmi dan download. |
| `PdfController.php` | Endpoint PDF internal. |
| `PublicVerificationController.php` | Verifikasi nomor dokumen/tagihan/pembayaran publik. |
| `OfficialDocumentController.php` | Generate dan kelola dokumen resmi. |
| `TteController.php` | Tanda tangan elektronik dokumen. |
| `BankH2HController.php` | Inquiry, payment, reversal dari bank. |
| `BankH2HLogController.php` | Audit log transaksi bank. |
| `PbbController.php` | Kalkulasi dan klasifikasi PBB. |
| `PbbBapendaController.php` | Inquiry, link NOP, pembayaran, transaksi PBB. |
| `TaxFormulaController.php` | Formula dan simulasi tarif. |
| `UploadController.php` | Upload file. |
| `BillboardController.php` | Data reklame. |
| `TaxEducationController.php` | Konten edukasi. |
| `SkpdController.php` | Pembuatan SKPD. |
| `DeploymentController.php` | Endpoint deploy hook admin. |

### Middleware dan Request

| File | Fungsi |
| --- | --- |
| `app/Http/Middleware/BankH2HSignature.php` | Validasi signature request bank. |
| `app/Http/Middleware/ScopeUser.php` | Pembatasan akses user/token. |
| `app/Http/Requests/*` | Validasi request terstruktur bila dipakai controller. |

### Service

| File | Fungsi |
| --- | --- |
| `app/Services/BillingService.php` | Hitung periode tertunggak, label periode, total, settlement tagihan. |
| `app/Services/PaymentGatewayInterface.php` | Kontrak gateway pembayaran. |
| `app/Services/BankH2HPaymentGateway.php` | Implementasi payment gateway bank host-to-host. |
| `app/Services/PaymentGatewayManager.php` | Resolver gateway pembayaran. |
| `app/Services/OfficialDocumentService.php` | Render, simpan, dan signing dokumen resmi. |
| `app/Services/TteService.php` | Integrasi tanda tangan elektronik. |
| `app/Services/PbbCalculationService.php` | Kalkulasi NJOP/PBB. |
| `app/Services/PbbBapendaService.php` | Integrasi dan normalisasi data Bapenda PBB. |
| `app/Services/FinancialReportExportService.php` | Export laporan keuangan. |
| `app/Services/AuditLogService.php` | Catatan audit perubahan penting. |
| `app/Services/WhatsAppService.php` | Kirim notifikasi WhatsApp opsional. |
| `app/Services/DocumentNumberService.php` | Penomoran dokumen resmi. |

### Console, Command, dan Job

| File | Fungsi |
| --- | --- |
| `app/Console/Commands/ApplyLatePenalties.php` | Menghitung/menerapkan denda keterlambatan. |
| `app/Console/Commands/ExpireOverdueBills.php` | Menandai tagihan lewat jatuh tempo. |
| `app/Console/Commands/RecalculateBillPenalties.php` | Rehitung denda tagihan. |
| `app/Console/Commands/ScheduleWaGatewayCleanup.php` | Perawatan gateway WhatsApp. |
| `app/Jobs/*` | Job antrian untuk proses async seperti notifikasi/sync. |

### Database

| Folder/File | Fungsi |
| --- | --- |
| `database/migrations/*create_users_table*` | Struktur user/token dasar. |
| `database/migrations/*create_opds_table*` | Struktur OPD. |
| `database/migrations/*create_taxpayers_table*` | Struktur wajib pajak. |
| `database/migrations/*create_tax_objects_table*` | Struktur objek pajak/retribusi. |
| `database/migrations/*create_bills_table*` | Struktur tagihan. |
| `database/migrations/*create_payments_table*` | Struktur pembayaran. |
| `database/migrations/*create_retribution_*` | Struktur jenis, klasifikasi, tarif retribusi. |
| `database/migrations/*create_zones_table*` | Struktur zona. |
| `database/migrations/*pbb*` | Struktur objek, SPPT, ZNT, pembayaran PBB. |
| `database/migrations/*document*` | Struktur dokumen resmi dan nomor urut. |
| `database/migrations/*bank_h2h*` | Struktur log host-to-host bank. |
| `database/migrations/*complaint*` | Struktur pengaduan. |
| `database/migrations/*spot_check*`, `*field*`, `*enforcement*` | Struktur pengawasan lapangan. |
| `database/migrations/*audit*` | Struktur audit log. |
| `database/seeders/DatabaseSeeder.php` | Seeder utama yang memanggil seeder lain. |
| `database/seeders/*Seeder.php` | Data awal role, OPD, master retribusi, user demo, PBB, dan contoh transaksi. |
| `database/factories/*Factory.php` | Factory data untuk test/seeding. |

### Resource dan View

| File/Folder | Fungsi |
| --- | --- |
| `resources/views/pdf/*` | Template Blade PDF SKT/SKRD/SKPD/SSPD/SPPT/laporan. |
| `resources/views/emails/*` | Template email notifikasi bila dipakai. |
| `resources/js/*`, `resources/css/*` | Asset Laravel bawaan/tooling Vite. |
| `public/` | Entry web publik, storage symlink, asset publik. |

### Test, Script, dan Dokumentasi

| File/Folder | Fungsi |
| --- | --- |
| `tests/Feature/*` | Test endpoint, auth, security, billing, PBB, dokumen, H2H. |
| `tests/Unit/*` | Test unit service/perhitungan. |
| `scripts/*` | Script operasional atau helper deployment/testing. |
| `testing/*` | Artefak test manual, koleksi request, atau catatan pengujian. |
| `docs/99_umum_system/system-knowledge.md` | Pengetahuan sistem umum yang sudah ada. |
| `docs/04_infrastruktur_api/routes-and-components.md` | Dokumentasi route dan komponen API yang sudah ada. |
| `docs/**` | Dokumentasi domain, endpoint, deployment, dan catatan teknis lain. |

## Integrasi dengan Frontend

| Frontend | Cara akses API |
| --- | --- |
| `retribusi-admin` | `VITE_API_URL`, bearer token dari `localStorage.token`. |
| `retribusi-mobile` | `VITE_API_URL`, token warga dari `localStorage.retribusi_auth_token`. |
| `retribusi-petugas` | `VITE_API_URL`, bearer token dari `localStorage.token`. |

## Risiko dan Catatan Operasional

- Repo ini membutuhkan `.env`; jangan mengandalkan default untuk koneksi database produksi.
- Seeder membantu menyiapkan data demo, tetapi data produksi harus dimigrasi secara terkontrol.
- WA gateway adalah service Node terpisah di `wa-gateway/`; jalankan hanya bila notifikasi WhatsApp diperlukan.
- Endpoint bank H2H dan TTE perlu credential produksi yang valid.
- PDF dan upload membutuhkan permission storage Laravel yang benar.
- Jika frontend berjalan di port berbeda, update `CORS_ALLOWED_ORIGINS` dan `SANCTUM_STATEFUL_DOMAINS`.
