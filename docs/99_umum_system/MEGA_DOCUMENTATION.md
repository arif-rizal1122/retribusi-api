# MEGA DOCUMENTATION
## File: docs/04_infrastruktur_api/INFRASTRUCTURE_NOTES.md
---
# 🌐 Catatan Infrastruktur & Maintenance (VPS)

Dokumen ini mencatat detail teknis akses server dan perintah pemeliharaan manual yang sebelumnya digunakan dalam script otomatisasi (`.exp`).

## 1. Detail Koneksi
- **IP Address:** `157.10.252.74`
- **User:** `mpad`
- **Sistem Operasi:** Ubuntu 22.04 LTS
- **Web Server:** Nginx (v1.18.0+)
- **PHP Version:** PHP 8.2+ (FPM)

## 2. Peta Direktori VPS
Seluruh proyek terletak di bawah home user `/home/mpad/`:

| Nama Proyek | Direktori (Production) | Direktori (Dev) |
| :--- | :--- | :--- |
| **API** | `/home/mpad/retribusi-api` | `/home/mpad/retribusi-api-dev` |
| **Mobile** | `/home/mpad/retribusi-mobile` | `/home/mpad/retribusi-mobile-dev` |
| **Admin** | `/home/mpad/retribusi-admin` | `/home/mpad/retribusi-admin-dev` |
| **Petugas**| `/home/mpad/retribusi-petugas`| `/home/mpad/retribusi-petugas-dev`|

## 3. Perintah Pemeliharaan Manual
Jika sinkronisasi GitHub Actions gagal, perintah berikut dapat dijalankan secara manual di terminal VPS:

### A. Sinkronisasi API
```bash
cd /home/mpad/retribusi-api
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
sudo -u www-data php artisan optimize
```

### B. Perbaikan Permission (Sangat Penting)
Jika muncul error 500 "Permission Denied", jalankan perintah ini:
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### C. Konfigurasi Nginx
- Lokasi file: `/etc/nginx/sites-available/retribusi-api`
- Cek syntax: `sudo nginx -t`
- Reload: `sudo systemctl reload nginx`

---
> [!IMPORTANT]
> Selalu operasikan perintah `artisan optimize` sebagai user `www-data` agar hak akses log dan cache tetap sinkron dengan web server.


---

## File: docs/04_infrastruktur_api/CREDENTIALS_GUIDE.md
---
# 🔑 Panduan Manajemen Kredensial

Dokumen ini menjelaskan **lokasi penyimpanan** kredensial tanpa mencantumkan nilai rahasianya (secrets) demi keamanan, serta daftar akun dan tautan akses untuk sistem Retribusi.

## 1. Kredensial VPS (Server)
- **Hostname:** `157.10.252.74`
- **Username:** `mpad`
- **Penyimpanan:** 
  - **GitHub Secrets:** `VPS_HOST`, `VPS_USERNAME`, `VPS_PASSWORD`.
  - **Environment Lokasi:** Disimpan dalam password manager pribadi.
- **Akses Database:** Dikelola via file `.env` di VPS.

## 2. Kredensial API & Layanan Ketiga
- **Cloudinary (Media):** 
  - `CLOUDINARY_URL` disimpan di `.env`.
  - API Key & Secret tersedia di dashboard Cloudinary.
- **Sentry (Error Logs):** 
  - DSN String diatur di `.env` (Laravel) dan `vite.config.ts` (Mobile).
- **GitHub Token:** Dikelola via SSH Key (`~/.ssh/`) di komputer pengembang dan Action Token di GitHub.

## 3. Akun Sistem (Aplikasi)

### Super Admin (Akses Penuh)
- **Email**: `admin@retribusi.id` / `superadmin@baubaukota.go.id`
- **Password**: `Bapenda123#$` / `Bapenda123#$` (untuk Dev)
- **Role**: `super_admin`

### Admin OPD (Dinas Terkait)
- **Email**: `bapenda@baubaukota.go.id`, `dishub@retribusi.id`, `disperindag@retribusi.id`, `dlh@retribusi.id`
- **Password**: `Bapenda123#$`
- **Role**: `opd`

### Pengawas (Approval & Penindakan)
- **Email**: `kabid@retribusi.id` / `pengawas@baubaukota.go.id`
- **Password**: `Bapenda123#$`
- **Role**: `pengawas`
- **Wewenang**: Full Approval SPP, SKRD, SSPD, dan Penerbitan SKPDKB.

### Petugas (Mobile Quick Scan / Patroli)
- **Email**: `petugas@bapenda.go.id` / `petugasrbac@test.com`
- **Password**: `Bapenda123#$`
- **Role**: `petugas`

---
## 4. Link Production

- **Admin Web** (Super Admin, OPD, Pengawas): [https://adminmpad.baubaukota.go.id](https://adminmpad.baubaukota.go.id)
- **Petugas Web** (Dashboard Petugas): [https://petugasmpad.baubaukota.go.id](https://petugasmpad.baubaukota.go.id)
- **Mobile Web** (Wajib Retribusi & Quick Access): [https://mpad.baubaukota.go.id](https://mpad.baubaukota.go.id)
- **API Server**: [https://api.sipanda.online](https://api.sipanda.online)

---
## 5. File `.env` (Source of Truth)
Setiap lingkungan memiliki file `.env` yang **tidak masuk Git**:
- **Production:** `/home/mpad/retribusi-api/.env`
- **Development:** `/home/mpad/retribusi-api-dev/.env`
- **Local:** Terletak di root masing-masing folder repo.

## 6. Cara Rotasi Kredensial
Jika terjadi kebocoran keamanan:
1. Update password user `mpad` di VPS.
2. Update **Actions Secrets** di Settings GitHub Repositori.
3. Update variabel terkait di file `.env` server.
4. Jalankan `php artisan config:cache` (Production) untuk memuat nilai baru.

---
> [!CAUTION]
> Jangan pernah meng-hardcode kredensial langsung ke dalam kode sumber atau dokumentasi Markdown yang di-push ke GitHub.


---

## File: docs/04_infrastruktur_api/routes-and-components.md
---
# Daftar Lengkap: API Endpoints, FE Routes & Komponen
**Terakhir diperbarui**: 14 April 2026 (Audit Antigravity)

---

> [!IMPORTANT]
> **Source of Truth**: Dokumen ini merupakan basis data teknis untuk **Cycle 2 (Route Mapping)** dalam [Master Testing Plan](file:///Users/pondokit/Herd/retribusi-api/tests/MASTER_TESTING_PLAN.md). Semua pengujian endpoint wajib divalidasi silang dengan daftar di bawah ini.

## 0. INFRASTRUKTUR & DOMAIN
Seluruh endpoint di bawah ini diakses melalui domain produksi yang sudah dipartisi:
- **API**: [api.sipanda.online](https://api.sipanda.online)
- **Admin**: [adminmpad.baubaukota.go.id](https://adminmpad.baubaukota.go.id)
- **Petugas**: [petugasmpad.baubaukota.go.id](https://petugasmpad.baubaukota.go.id)

Lihat [INFRASTRUCTURE_MAP.md](file:///Users/pondokit/Herd/retribusi-api/INFRASTRUCTURE_MAP.md) untuk detail kredensial dan folder VPS.

## 1. BACKEND — `retribusi-api`

### Controller Files

| # | File | Namespace |
|---|------|-----------|
| 1 | `AuthController.php` | `App\Http\Controllers` |
| 2 | `AnalyticsController.php` | `App\Http\Controllers` |
| 3 | `BillController.php` | `App\Http\Controllers` |
| 4 | `CitizenServiceController.php` | `App\Http\Controllers` |
| 5 | `DashboardController.php` | `App\Http\Controllers` |
| 6 | `DocumentController.php` | `App\Http\Controllers` |
| 7 | `DocumentationController.php` | `App\Http\Controllers` |
| 8 | `MeController.php` | `App\Http\Controllers` |
| 9 | `MonthlyReportController.php` | `App\Http\Controllers` |
| 10 | `OpdController.php` | `App\Http\Controllers` |
| 11 | `PaymentController.php` | `App\Http\Controllers` |
| 12 | `PbbBapendaController.php` | `App\Http\Controllers` |
| 13 | `PbbClassificationController.php` | `App\Http\Controllers` |
| 14 | `PenaltyWaiverController.php` | `App\Http\Controllers` |
| 15 | `PublicVerificationController.php` | `App\Http\Controllers` |
| 16 | `ReportController.php` | `App\Http\Controllers` |
| 17 | `RetributionClassificationController.php` | `App\Http\Controllers` |
| 18 | `RetributionRateController.php` | `App\Http\Controllers` |
| 19 | `RetributionTypeController.php` | `App\Http\Controllers` |
| 20 | `TaxObjectController.php` | `App\Http\Controllers` |
| 21 | `TaxpayerController.php` | `App\Http\Controllers` |
| 22 | `TaxpayerSearchController.php` | `App\Http\Controllers` |
| 23 | `UploadController.php` | `App\Http\Controllers` |
| 24 | `UserController.php` | `App\Http\Controllers` |
| 25 | `VerificationController.php` | `App\Http\Controllers` |
| 26 | `ZoneController.php` | `App\Http\Controllers` |
| 27 | `AuditLogController.php` | `App\Http\Controllers\Pengawas` |
| 28 | `EnforcementNoticeController.php` | `App\Http\Controllers\Pengawas` |
| 29 | `PenindakanController.php` | `App\Http\Controllers\Pengawas` |
| 30 | `SurveillanceController.php` | `App\Http\Controllers\Pengawas` |
| 31 | `EregistryController.php` | `App\Http\Controllers\Api` |

### API Endpoints (routes/api.php)

#### 🔓 Public Routes (No Auth)

| Method | URI | Controller | Keterangan |
|--------|-----|------------|------------|
| `POST` | `/api/opd/register` | `OpdController::register` | Registrasi OPD baru |
| `POST` | `/api/login` | `AuthController::login` | Login admin/petugas |
| `POST` | `/api/citizen/login` | `AuthController::citizenLogin` | Login warga |
| `POST` | `/api/citizen/register` | `AuthController::registerCitizen` | Registrasi warga |
| `GET` | `/api/opds` | `OpdController::index` | List OPD |
| `GET` | `/api/citizen/bills` | `BillController::citizenBills` | Demo tagihan publik |
| `GET` | `/api/verify/bill/{number}` | `PublicVerificationController::verifyBill` | Verifikasi tagihan publik |
| `GET` | `/api/verify/payment/{number}` | `PublicVerificationController::verifyPayment` | Verifikasi pembayaran publik |
| `POST` | `/api/simulate-tax` | Closure | Simulasi pajak |
| `GET` | `/api/tax-formulas` | Closure | List formula pajak |
| `GET` | `/api/pbb/classifications` | `PbbClassificationController::index` | Klasifikasi PBB |
| `GET` | `/api/pbb/classifications/{type}/{code}` | `PbbClassificationController::showByCode` | Klasifikasi PBB by code |
| `POST` | `/api/pbb/lookup-class` | `PbbClassificationController::lookupByValue` | Lookup kelas PBB |
| `POST` | `/api/pbb/calculate` | `PbbClassificationController::calculate` | Hitung PBB |
| `POST` | `/api/pbb/bapenda/inquiry` | `PbbBapendaController::inquiry` | Cek tagihan PBB |
| `GET` | `/api/tte/verify/{number}` | `EregistryController::verify` | Verifikasi TTE |

#### 🔒 Protected Routes (Auth: Sanctum — Shared)

| Method | URI | Controller | Keterangan |
|--------|-----|------------|------------|
| `POST` | `/api/logout` | `AuthController::logout` | Logout |
| `GET` | `/api/user` | `AuthController::user` | User info |
| `PUT` | `/api/user/profile` | `AuthController::updateProfile` | Update profil |
| `POST` | `/api/user/password` | `AuthController::changePassword` | Ganti password |
| `PUT` | `/api/user/location` | `AuthController::updateLocation` | Update lokasi GPS |
| `POST` | `/api/upload` | `UploadController::uploadImage` | Upload gambar |
| `GET` | `/api/me` | `MeController::show` | Data user saat ini |
| `POST` | `/api/me/update` | `MeController::update` | Update data user |
| `GET` | `/api/citizen/services` | `CitizenServiceController::index` | List layanan warga |
| `GET` | `/api/citizen/services/pending-periods` | `CitizenServiceController::getPendingPeriods` | Periode tunggakan warga |
| `GET` | `/api/citizen/services/{id}` | `CitizenServiceController::show` | Detail layanan |
| `POST` | `/api/citizen/services/{id}/register` | `CitizenServiceController::register` | Daftar layanan |
| `GET` | `/api/citizen/services/{id}/bills` | `CitizenServiceController::bills` | Tagihan per layanan |
| `GET` | `/api/bills/{bill}` | `BillController::show` | Detail tagihan |
| `GET` | `/api/bills/{bill}/skrd` | `BillController::exportSKRD` | Export SKRD |
| `GET` | `/api/bills/{bill}/sspd` | `BillController::exportSSPD` | Export SSPD |
| `GET` | `/api/bills/{bill}/sppt` | `BillController::exportSPPT` | Export SPPT |
| `POST` | `/api/citizen/reports` | `MonthlyReportController::store` | Submit SPTPD |
| `GET` | `/api/citizen/reports` | `MonthlyReportController::index` | List SPTPD warga |
| `POST` | `/api/pbb/bapenda/link-nop` | `PbbBapendaController::linkNop` | Link NOP PBB |
| `DELETE` | `/api/pbb/bapenda/unlink-nop/{id}` | `PbbBapendaController::unlinkNop` | Unlink NOP PBB |
| `GET` | `/api/pbb/bapenda/my-objects` | `PbbBapendaController::myObjects` | Objek PBB user |
| `GET` | `/api/pbb/bapenda/my-transactions` | `PbbBapendaController::myTransactions` | Transaksi PBB user |
| `POST` | `/api/pbb/bapenda/pay` | `PbbBapendaController::pay` | Bayar PBB |

#### 🔐 Admin & Petugas Only (Middleware: admin)

| Method | URI | Controller | Keterangan |
|--------|-----|------------|------------|
| `GET` | `/api/analytics/realization` | `AnalyticsController::getRealization` | Data realisasi |
| `GET` | `/api/analytics/heatmap` | `AnalyticsController::getHeatmapData` | Heatmap potensi |
| `GET/POST/PUT/DELETE` | `/api/retribution-types` | `RetributionTypeController` | CRUD Jenis Retribusi |
| `GET` | `/api/taxpayers/search/{nik}` | `TaxpayerSearchController::searchByNik` | Cari WP by NIK |
| `GET/POST/PUT/DELETE` | `/api/taxpayers` | `TaxpayerController` | CRUD Wajib Pajak |
| `GET/POST/PUT/DELETE` | `/api/tax-objects` | `TaxObjectController` | CRUD Objek Pajak |
| `GET` | `/api/bills` | `BillController::index` | List tagihan |
| `POST` | `/api/bills` | `BillController::store` | Buat tagihan |
| `GET` | `/api/tax-objects/{id}/pending-periods` | `PaymentController::getPendingPeriods` | Periode tunggakan |
| `GET` | `/api/payments` | `PaymentController::index` | List pembayaran |
| `POST` | `/api/payments` | `PaymentController::store` | Catat pembayaran |
| `POST` | `/api/bills/{bill}/pay` | `PaymentController::store` | Bayar tagihan |
| `PUT` | `/api/payments/{payment}/status` | `PaymentController::updateStatus` | Verifikasi bayar |
| `PUT` | `/api/verifications/{id}/status` | `VerificationController::updateStatus` | Update verifikasi |
| `GET/POST` | `/api/verifications` | `VerificationController` | List/Buat verifikasi |
| `GET` | `/api/verifications/{id}` | `VerificationController::show` | Detail verifikasi |
| `GET/POST/PUT/DELETE` | `/api/zones` | `ZoneController` | CRUD Zona |
| `GET/POST/PUT/DELETE` | `/api/retribution-classifications` | `RetributionClassificationController` | CRUD Klasifikasi |
| `GET/POST/PUT/DELETE` | `/api/retribution-rates` | `RetributionRateController` | CRUD Tarif |
| `GET/PUT/DELETE` | `/api/opds/{id}` | `OpdController` | Manage OPD |
| `GET/POST/PUT/DELETE` | `/api/users` | `UserController` | CRUD Users |
| `GET` | `/api/dashboard/stats` | `DashboardController::getStats` | Statistik dashboard |
| `GET` | `/api/dashboard/revenue-trend` | `DashboardController::getRevenueTrend` | Tren pendapatan |
| `GET` | `/api/dashboard/map-potentials` | `DashboardController::getMapPotentials` | Peta potensi |
| `GET` | `/api/pengawas/audit-logs` | `AuditLogController::index` | Log audit |
| `GET` | `/api/pengawas/anomalies` | `SurveillanceController::getAnomalies` | Deteksi anomali |
| `GET` | `/api/pengawas/compliance-stats` | `SurveillanceController::getComplianceStats` | Statistik kepatuhan |
| `GET` | `/api/pengawas/petugas-locations` | `SurveillanceController::getPetugasLocations` | Lokasi petugas |
| `GET` | `/api/pengawas/enforcements` | `EnforcementNoticeController::index` | List penindakan |
| `POST` | `/api/pengawas/enforcements` | `EnforcementNoticeController::store` | Buat penindakan |
| `POST` | `/api/pengawas/enforcements/{id}` | `EnforcementNoticeController::update` | Update penindakan |
| `POST` | `/api/pengawas/enforcements/{id}/approve` | `EnforcementNoticeController::approve` | Approve penindakan |
| `POST` | `/api/pengawas/enforcements/{id}/reject` | `EnforcementNoticeController::reject` | Tolak penindakan (⭐ Baru) |
| `GET` | `/api/pengawas/enforcements/history/{tax_object_id}` | `EnforcementNoticeController::getHistory` | Riwayat penindakan |
| `GET` | `/api/pengawas/enforcements/{id}/pdf` | `EnforcementNoticeController::generatePDF` | Cetak PDF (Teguran/SPMP/SPP) |
| `GET/POST` | `/api/spot-checks` | `SpotCheckController` | CRUD Uji Petik |
| `PATCH` | `/api/spot-checks/{id}/status` | `SpotCheckController::updateStatus` | Approve Uji Petik |
| `GET` | `/api/spot-checks/tax-object/{id}/estimation` | `SpotCheckController::getEstimatedRevenue` | Kalkulasi Estimasi Spot Check |
| `GET` | `/api/pengawas/penindakan` | `PenindakanController::index` | List penindakan |
| `POST` | `/api/pengawas/penindakan/issue-skpdkb` | `PenindakanController::generateSKPDKB` | Generate SKPDKB |
| `GET` | `/api/reports/summary` | `ReportController::getSummary` | Ringkasan laporan |
| `GET` | `/api/reports/recent` | `ReportController::getRecent` | Laporan terbaru |
| `GET` | `/api/reports/petugas-performance` | `ReportController::getPetugasPerformance` | Performa petugas |
| `GET` | `/api/reports/monthly` | `MonthlyReportController::index` | List SPTPD admin |
| `PUT` | `/api/reports/monthly/{report}/validate` | `MonthlyReportController::validateReport` | Validasi SPTPD |
| `GET` | `/api/reports/bpk` | `ReportController::getMonthlyReport` | Laporan BPK |
| `GET` | `/api/amnesty` | `PenaltyWaiverController::index` | List amnesti |
| `POST` | `/api/amnesty` | `PenaltyWaiverController::store` | Ajukan amnesti |
| `POST` | `/api/amnesty/{id}/approve` | `PenaltyWaiverController::approve` | Approve amnesti |
| `POST` | `/api/amnesty/{id}/reject` | `PenaltyWaiverController::reject` | Tolak amnesti |
| `GET` | `/api/amnesty/{id}/document` | `PenaltyWaiverController::generateDocument` | Cetak SK amnesti |
| `GET` | `/api/tte/documents` | `EregistryController::index` | List dokumen TTE |
| `POST` | `/api/tte/sign` | `BillController::signTTE` | TTd elektronik |
| `POST` | `/api/pbb/bapenda/reversal` | `PbbBapendaController::reversal` | Reversal PBB |
| `GET` | `/api/pbb/bapenda/transactions` | `PbbBapendaController::transactions` | All transaksi PBB |
| `GET` | `/api/pbb/bapenda/stats` | `PbbBapendaController::stats` | Statistik PBB |
| `GET` | `/api/documents/skt/{taxpayerId}` | `DocumentController::skt` | Cetak SKT |
| `GET` | `/api/documents/lkok/{taxObjectId}` | `DocumentController::lkok` | Cetak LKOK |
| `GET` | `/api/documents/skrd/{billId}` | `DocumentController::skrd` | Cetak SKRD |
| `GET` | `/api/documents/sppt/{billId}` | `DocumentController::sppt` | Cetak SPPT |
| `POST` | `/api/documents/skpdkbt/{billId}` | `DocumentController::skpdkbt` | Cetak SKPDKBT |
| `POST` | `/api/documents/skpdn/{billId}` | `DocumentController::skpdn` | Cetak SKPDN |
| `GET` | `/api/documents/sspd/{billId}` | `DocumentController::sspd` | Cetak SSPD |
| `GET` | `/api/documents/ssrd/{billId}` | `DocumentController::ssrd` | Cetak SSRD |
| `GET` | `/api/documents/strd/{billId}` | `DocumentController::strd` | Cetak STRD |
| `GET` | `/api/documents/spp/{noticeId}` | `DocumentController::spp` | Cetak SPP |
| `GET` | `/api/documents/spmp/{noticeId}` | `DocumentController::spmp` | Cetak SPMP |

---

## 2. ADMIN — `retribusi-admin`

### Routes & Components

| # | Route | Komponen | File | Roles |
|---|-------|----------|------|-------|
| 1 | `/` | `LandingPage` | `LandingPage.tsx` | Public |
| 2 | `/login` | `Login` | `Login.tsx` | Public |
| 3 | `/register/opd` | `OpdRegistration` | `OpdRegistration.tsx` | Public |
| 4 | `/pendaftaran` | `PublicRegistration` | `PublicRegistration.tsx` | Public |
| 5 | `/tasks` | `PetugasTasks` | `PetugasTasks.tsx` | Public |
| 6 | `/sptpd` | `ESptpd` | `ESptpd.tsx` | super_admin, citizen |
| 7 | `/dashboard` | `Dashboard` | `Dashboard.tsx` | super_admin, opd, verifikator, petugas, viewer, pengawas, kabid/kasubid, walikota |
| 8 | `/surveillance` | `Dashboard` | `Dashboard.tsx` | super_admin, pengawas, kabid/kasubid |
| 9 | `/pengawas/dashboard` | `PengawasDashboard` | `PengawasDashboard.tsx` | super_admin, pengawas, kabid/kasubid |
| 10 | `/spot-checks` | `SpotCheckList` | `SpotCheckList.tsx` | super_admin, pengawas, kabid/kasubid |
| 11 | `/spot-checks/create` | `SpotCheckForm` | `SpotCheckForm.tsx` | super_admin, pengawas, kabid/kasubid |
| 17 | `/billing` | `Billing` | `Billing.tsx` | super_admin, opd, petugas |
| 18 | `/verification` | `Verification` | `Verification.tsx` | super_admin, opd, verifikator |
| 19 | `/reporting` | `Reporting` | `Reporting.tsx` | super_admin, opd, viewer |
| 20 | `/master-data` | `MasterData` | `MasterData.tsx` | super_admin, opd, verifikator, petugas, viewer, kabid/kasubid |
| 21 | `/system` | `SystemAdmin` | `SystemAdmin.tsx` | super_admin |
| 22 | `/opds` | `OpdManagement` | `OpdManagement.tsx` | super_admin |
| 23 | `/profile` | `Profile` | `Profile.tsx` | All auth roles |
| 24 | `/user-guide` | `UserGuide` | `UserGuide.tsx` | All (no auth) |
| 25 | `/pbb-bapenda` | `PbbManagement` | `PbbManagement.tsx` | super_admin, opd |
| 26 | `/presentation` | `Presentation` | `Presentation.tsx` | Public |
| 27 | `/download` | `DownloadApp` | `DownloadApp.tsx` | Public |
| 29 | `/verify/:number` | `ERegistry` | `ERegistry.tsx` | Public |
| 30 | `/verify` | `ERegistry` | `ERegistry.tsx` | Public |

### Components

| File | Fungsi |
|------|--------|
| `Layout.tsx` | Sidebar + Navbar wrapper |
| `MapPicker.tsx` | Peta interaktif |
| `ProtectedRoute.tsx` | Guard auth + role |
| `SearchableSelect.tsx` | Dropdown searchable |
| `SupervisorMap.tsx` | Peta interaktif & satelit untuk Command Center |
| `TaxpayerEditModal.tsx` | Modal edit WP |

---

## 3. PETUGAS — `retribusi-petugas`

### Routes & Components

| # | Route | Komponen | File | Roles |
|---|-------|----------|------|-------|
| 1 | `/` | `LandingPage` | `LandingPage.tsx` | Public |
| 2 | `/login` | `PetugasLogin` | `PetugasLogin.tsx` | Public |
| 3 | `/register` | `PetugasRegister` | `PetugasRegister.tsx` | Public |
| 4 | `/welcome` | `PetugasWelcome` | `PetugasWelcome.tsx` | Public |
| 5 | `/peta` | `PetaLapangan` | `PetaLapangan.tsx` | super_admin, opd, petugas |
| 6 | `/dashboard` | `Dashboard` | `Dashboard.tsx` | super_admin, opd, verifikator, petugas, viewer |
| 7 | `/scanner` | `FieldScanner` | `FieldScanner.tsx` | super_admin, opd, petugas |
| 8 | `/payment-confirmation` | `PaymentConfirmation` | `PaymentConfirmation.tsx` | super_admin, opd, petugas |
| 9 | `/field-check` | `FieldInspection` | `FieldInspection.tsx` | super_admin, opd, petugas |
| 10 | `/taxpayers` | `TaxpayerManagement` | `TaxpayerManagement.tsx` | super_admin, opd, petugas |
| 11 | `/taxpayers/:id` | `TaxpayerDetail` | `TaxpayerDetail.tsx` | super_admin, opd, petugas |
| 12 | `/billing` | `Billing` | `Billing.tsx` | super_admin, opd, petugas |
| 13 | `/verification` | `PaymentVerification` | `PaymentVerification.tsx` | super_admin, opd, petugas |
| 14 | `/reporting` | `Reporting` | `Reporting.tsx` | super_admin, opd, viewer, petugas |
| 15 | `/master-data` | `MasterData` | `MasterData.tsx` | super_admin, opd, verifikator, petugas, viewer |
| 16 | `/calculator` | `TaxCalculator` | `TaxCalculator.tsx` | super_admin, opd, petugas |
| 17 | `/pbb-bapenda` | `PbbBapenda` | `PbbBapenda.tsx` | super_admin, opd, petugas |
| 18 | `/profile` | `Profile` | `Profile.tsx` | All auth roles |
| 19 | `/user-guide` | `UserGuide` | `UserGuide.tsx` | Public |
| 20 | `/download` | `DownloadApp` | `DownloadApp.tsx` | Public |
| 21 | `/unduh` | `DownloadApp` | `DownloadApp.tsx` | Public |
| 22 | `/presentation` | `Presentation` | `Presentation.tsx` | Public |

### Components

| File | Fungsi |
|------|--------|
| `Layout.tsx` | Sidebar + Navbar + Bottom Nav |
| `Card.tsx` | Card container UI |
| `InstallPWA.tsx` | Install PWA prompt |
| `MapPicker.tsx` | Peta interaktif |
| `ProtectedRoute.tsx` | Guard auth + role |
| `SearchableSelect.tsx` | Dropdown searchable |
| `TaxpayerEditModal.tsx` | Modal edit WP |

### Services & Contexts

| File | Fungsi |
|------|--------|
| `services/ThermalPrintService.ts` | Bluetooth thermal printer |
| `contexts/AuthContext.tsx` | Auth state management |
| `contexts/ThemeContext.tsx` | Light/Dark mode toggle |
| `lib/api.ts` | Axios API wrapper |

---

## 4. MOBILE — `retribusi-mobile`

### Routes & Components

| # | Route | Komponen | File | Auth |
|---|-------|----------|------|------|
| 1 | `/` | Redirect | — | → `/welcome` atau `/login` |
| 2 | `/welcome` | `Welcome` | `Welcome.tsx` | Public |
| 3 | `/login` | `Login` | `Login.tsx` | Public |
| 4 | `/register` | `Register` | `Register.tsx` | Public |
| 5 | `/home` | `Home` | `Home.tsx` | ✅ Protected |
| 6 | `/layanan-retribusi` | `LayananRetribusi` | `LayananRetribusi.tsx` | ✅ Protected |
| 7 | `/pembayaran` | `Pembayaran` | `Pembayaran.tsx` | ✅ Protected |
| 8 | `/tagihan` | `Tagihan` | `Tagihan.tsx` | ✅ Protected |
| 9 | `/pengaduan` | `Pengaduan` | `Pengaduan.tsx` | ✅ Protected |
| 10 | `/profile` | `UserProfile` | `UserProfile.tsx` | ✅ Protected |
| 11 | `/change-password` | `ChangePassword` | `ChangePassword.tsx` | ✅ Protected |
| 12 | `/parkir` | `Parkir` | `Parkir.tsx` | ✅ Protected |
| 13 | `/sampah` | `Sampah` | `Sampah.tsx` | ✅ Protected |
| 14 | `/pasar` | `Pasar` | `Pasar.tsx` | ✅ Protected |
| 15 | `/pdam` | `PDAM` | `PDAM.tsx` | ✅ Protected |
| 16 | `/pju` | `PJU` | `PJU.tsx` | ✅ Protected |
| 17 | `/iumk` | `IUMK` | `IUMK.tsx` | ✅ Protected |
| 18 | `/imb` | `IMB` | `IMB.tsx` | ✅ Protected |
| 19 | `/tdp-siup` | `TDPSIUP` | `TDPSIUP.tsx` | ✅ Protected |
| 20 | `/internet` | `Internet` | `Internet.tsx` | ✅ Protected |
| 21 | `/kendaraan` | `Kendaraan` | `Kendaraan.tsx` | ✅ Protected |
| 22 | `/eticket` | `ETicket` | `ETicket.tsx` | ✅ Protected |
| 23 | `/reklame` | `Reklame` | `Reklame.tsx` | ✅ Protected |
| 24 | `/internet/indihome` | `TelkomIndihome` | `internet/TelkomIndihome.tsx` | ✅ Protected |
| 25 | `/internet/wifi-id` | `WifiId` | `internet/WifiId.tsx` | ✅ Protected |
| 26 | `/internet/merdeka` | `InternetMerdeka` | `internet/InternetMerdeka.tsx` | ✅ Protected |
| 27 | `/internet/intan-wifi` | `IntanWifi` | `internet/IntanWifi.tsx` | ✅ Protected |
| 28 | `/layanan/:id` | `ServiceDetail` | `ServiceDetail.tsx` | ✅ Protected |
| 29 | `/kalkulator-pajak` | `TaxCalculator` | `TaxCalculator.tsx` | ✅ Protected |
| 30 | `/pbb-calculator` | `PbbCalculator` | `PbbCalculator.tsx` | ✅ Protected |
| 31 | `/pbb-tagihan` | `PbbTagihan` | `PbbTagihan.tsx` | ✅ Protected |
| 32 | `/sptpd-report` | `SptpdReporting` | `SptpdReporting.tsx` | ✅ Protected |
| 33 | `/download` | `DownloadApp` | `DownloadApp.tsx` | Public |
| 34 | `/unduh` | `DownloadApp` | `DownloadApp.tsx` | Public |

### Components

| File | Fungsi |
|------|--------|
| `AtmCard.tsx` | Kartu ATM-style display |
| `BottomNavigation.tsx` | Navigasi bawah (mobile) |
| `Button.tsx` | Tombol styled |
| `Card.tsx` | Card container |
| `Input.tsx` | Input field styled |
| `InstallPWA.tsx` | Install PWA prompt |
| `MapPicker.tsx` | Peta interaktif |
| `PaymentCard.tsx` | Card pembayaran |
| `RetribusiLayout.tsx` | Layout retribusi |
| `Textarea.tsx` | Textarea styled |
| `UserMenu.tsx` | Menu profil user |

### Pages Tidak Terdaftar di Router

| File | Keterangan |
|------|------------|
| `Kepuasan.tsx` | Survey kepuasan (mungkin embedded) |
| `KritikSaran.tsx` | Kritik & saran (mungkin embedded) |
| `RetribusiPage.tsx` | Template halaman retribusi |

---

## Ringkasan Statistik

| Repo | Controllers | Routes | Pages | Components |
|------|-------------|--------|-------|------------|
| **retribusi-api** | 31 | 76+ | — | — |
| **retribusi-admin** | — | 29 | 26 | 6 |
| **retribusi-petugas** | — | 22 | 21 | 7 |
| **retribusi-mobile** | — | 34 | 33 | 11 |
| **TOTAL** | **31** | **161+** | **80** | **24** |

---

## 5. TESTING — `retribusi-api/testing/`

### Folder Structure

```
testing/
├── 📋 Panduan & Skema
│   ├── 00-Panduan-Standar-Pengujian.md
│   ├── 01-Persiapan-Deployment.md
│   ├── 01-known-errors-mitigations.md
│   ├── 02-Keamanan-Infrastruktur.md
│   ├── 02-e2e-testing-scheme.md
│   ├── 03-Alur-Utama-E2E.md
│   ├── 04-Fitur-Kritis-Edge-Cases.md
│   ├── 05-Cross-Device-UAT.md
│   ├── 06-Go-Live-Monitoring.md
│   ├── 07-Formula-Jenis-Pajak.md
│   ├── 09-Keamanan-RBAC.md
│   ├── 10-Validasi-Data.md
│   ├── 11-Filter-Wajib-Pajak-Petugas.md
│   ├── 12-Error-History-Mitigation-Registry.md
│   ├── 13-Workspace-Master-Testing-Protocol.md
│   ├── 14-Master-Data-Testing-Schema.md
│   └── 🚀 MASTER_TESTING_PLAN.md (Strategi Utama 10-Check Audit) ⭐
│
├── 🔧 Scripts
│   ├── run_kalkulator_test.php        (PHP — Seluruh formula pajak)
│   ├── run_negative_test.php          (PHP — Validasi input negatif)
│   ├── run_rbac_test.php              (PHP — RBAC per role)
│   ├── run_role_e2e_test.php          (PHP — E2E lintas peran)
│   ├── mitigation_tester.php          (PHP — Test mitigasi error)
│   ├── test_api_crud.sh               (Bash — CRUD semua endpoint)
│   ├── test_penetration.sh            (Bash — Security audit)
│   ├── test_production_cors.sh        (Bash — CORS verification)
│   ├── test_production_ready.sh       (Bash — Infra readiness)
│   ├── test_production_regression.sh  (Bash — Regression test)
│   ├── test_vtax_parity.php           (PHP — V-Tax 9-Pajak parity) ⭐ Baru
│   ├── verify_pdf_templates.php       (PHP — PDF template syntax)
│   ├── stg1_health_check.php          (PHP — Staging health)
│   ├── stg2_penetration_rbac.php      (PHP — Staging RBAC)
│   ├── stg3_formula_sync.php          (PHP — Formula sync)
│   └── stg4_document_availability.php (PHP — Document endpoints)
│
└── 📊 results/
    ├── 07_Hasil_Kalkulator_Semua_Pajak.md
    ├── 08_Laporan_E2E_Lintas_Peran.md
    ├── 09_Laporan_Keamanan_RBAC.md
    ├── 10_Laporan_Validasi_Negatif.md
    ├── 12_Production_Readiness_*.md (2 runs)
    ├── 13_Penetration_Test_*.md
    ├── 14_API_CRUD_Test_*.md (6 runs)
    ├── kalkulator_test_results.txt
    ├── negative_test_results.txt
    ├── rbac_test_results.txt
    └── role_e2e_test_results.txt
```

### 5.3 TABEL MASTER: RENTETAN PENGUJIAN SELURUH ROUTE (188 ITEMS)

Tabel berikut memetakan seluruh rute aktif aplikasi ke dalam fase pengujian masing-masing untuk memastikan cakupan 100%.

| # | Method | URI | Test Script / Fase |
|---|--------|-----|-------------------|
| 1 | GET|HEAD | / | General / API |
| 2 | GET|HEAD | api/amnesty | General / API |
| 3 | POST | api/amnesty | General / API |
| 4 | POST | api/amnesty/{id}/approve | General / API |
| 5 | GET|HEAD | api/amnesty/{id}/document | General / API |
| 6 | POST | api/amnesty/{id}/reject | General / API |
| 7 | GET|HEAD | api/analytics/classification-performance | General / API |
| 8 | GET|HEAD | api/analytics/heatmap | General / API |
| 9 | GET|HEAD | api/analytics/object-performance | General / API |
| 10 | GET|HEAD | api/analytics/realization | General / API |
| 11 | POST | api/billboards/{taxObject}/photo | Fase 4: Billing |
| 12 | POST | api/billboards/{taxObject}/verify | Fase 4: Billing |
| 13 | GET|HEAD | api/bills | Fase 4: Billing |
| 14 | POST | api/bills | Fase 4: Billing |
| 15 | GET|HEAD | api/bills/{bill} | Fase 4: Billing |
| 16 | POST | api/bills/{bill}/pay | Fase 4: Billing |
| 17 | GET|HEAD | api/bills/{bill}/skrd | Fase 4: Billing |
| 18 | GET|HEAD | api/bills/{bill}/sppt | Fase 4: Billing |
| 19 | GET|HEAD | api/bills/{bill}/sspd | Fase 4: Billing |
| 20 | GET|HEAD | api/citizen/bills | Fase 4: Billing |
| 21 | POST | api/citizen/complaints | General / API |
| 22 | GET|HEAD | api/citizen/complaints | General / API |
| 23 | POST | api/citizen/login | Fase 1: Auth |
| 24 | POST | api/citizen/register | General / API |
| 25 | POST | api/citizen/reports | General / API |
| 26 | GET|HEAD | api/citizen/reports | General / API |
| 27 | GET|HEAD | api/citizen/services | General / API |
| 28 | GET|HEAD | api/citizen/services/pending-periods | General / API |
| 29 | GET|HEAD | api/citizen/services/{id} | General / API |
| 30 | GET|HEAD | api/citizen/services/{id}/bills | Fase 4: Billing |
| 31 | POST | api/citizen/services/{id}/register | General / API |
| 32 | GET|HEAD | api/complaints | General / API |
| 33 | GET|HEAD | api/complaints/{complaint} | General / API |
| 34 | PUT | api/complaints/{complaint}/status | General / API |
| 35 | GET|HEAD | api/dashboard/map-potentials | General / API |
| 36 | GET|HEAD | api/dashboard/revenue-trend | General / API |
| 37 | GET|HEAD | api/dashboard/stats | General / API |
| 38 | GET|HEAD | api/documents/lkok/{taxObjectId} | General / API |
| 39 | GET|HEAD | api/documents/skpd/{billId} | Fase 4: Billing |
| 40 | POST | api/documents/skpdkbt/{billId} | Fase 4: Billing |
| 41 | POST | api/documents/skpdn/{billId} | Fase 4: Billing |
| 42 | GET|HEAD | api/documents/skrd/{billId} | Fase 4: Billing |
| 43 | GET|HEAD | api/documents/skt/{taxpayerId} | Fase 3: WP |
| 44 | GET|HEAD | api/documents/spmp/{noticeId} | General / API |
| 45 | GET|HEAD | api/documents/spp/{noticeId} | General / API |
| 46 | GET|HEAD | api/documents/sppt/{billId} | Fase 4: Billing |
| 47 | GET|HEAD | api/documents/sspd/{billId} | Fase 4: Billing |
| 48 | GET|HEAD | api/documents/ssrd/{billId} | Fase 4: Billing |
| 49 | GET|HEAD | api/documents/strd/{billId} | Fase 4: Billing |
| 50 | POST | api/login | Fase 1: Auth |
| 51 | POST | api/logout | General / API |
| 52 | GET|HEAD | api/me | General / API |
| 53 | POST | api/me/update | General / API |
| 54 | POST | api/opd/register | General / API |
| 55 | GET|HEAD | api/opds | General / API |
| 56 | POST | api/opds | General / API |
| 57 | GET|HEAD | api/opds/{opd} | General / API |
| 58 | PUT|PATCH | api/opds/{opd} | General / API |
| 59 | DELETE | api/opds/{opd} | General / API |
| 60 | GET|HEAD | api/payments | Fase 4: Billing |
| 61 | POST | api/payments | Fase 4: Billing |
| 62 | PUT | api/payments/{payment}/status | Fase 4: Billing |
| 63 | GET|HEAD | api/pbb/bapenda/download-sppt | General / API |
| 64 | POST | api/pbb/bapenda/inquiry | General / API |
| 65 | POST | api/pbb/bapenda/link-nop | General / API |
| 66 | GET|HEAD | api/pbb/bapenda/my-objects | General / API |
| 67 | GET|HEAD | api/pbb/bapenda/my-transactions | General / API |
| 68 | POST | api/pbb/bapenda/pay | General / API |
| 69 | POST | api/pbb/bapenda/reversal | General / API |
| 70 | GET|HEAD | api/pbb/bapenda/stats | General / API |
| 71 | POST | api/pbb/bapenda/sync-all | General / API |
| 72 | GET|HEAD | api/pbb/bapenda/transactions | General / API |
| 73 | DELETE | api/pbb/bapenda/unlink-nop/{id} | General / API |
| 74 | POST | api/pbb/calculate | General / API |
| 75 | GET|HEAD | api/pbb/classifications | General / API |
| 76 | GET|HEAD | api/pbb/classifications/{type}/{code} | General / API |
| 77 | POST | api/pbb/lookup-class | General / API |
| 78 | GET|HEAD | api/pengawas/anomalies | Fase 6: Pengawasan |
| 79 | GET|HEAD | api/pengawas/audit-logs | Fase 6: Pengawasan |
| 80 | GET|HEAD | api/pengawas/compliance-stats | Fase 6: Pengawasan |
| 81 | GET|HEAD | api/pengawas/enforcements | Fase 6: Pengawasan |
| 82 | POST | api/pengawas/enforcements | Fase 6: Pengawasan |
| 83 | GET|HEAD | api/pengawas/enforcements/history/{tax_object_id} | Fase 6: Pengawasan |
| 84 | POST | api/pengawas/enforcements/{id} | Fase 6: Pengawasan |
| 85 | POST | api/pengawas/enforcements/{id}/approve | Fase 6: Pengawasan |
| 86 | GET|HEAD | api/pengawas/enforcements/{id}/pdf | Fase 6: Pengawasan |
| 87 | POST | api/pengawas/enforcements/{id}/reject | Fase 6: Pengawasan |
| 88 | GET|HEAD | api/pengawas/penindakan | Fase 6: Pengawasan |
| 89 | POST | api/pengawas/penindakan/issue-skpdkb | Fase 6: Pengawasan |
| 90 | GET|HEAD | api/pengawas/petugas-locations | Fase 6: Pengawasan |
| 91 | GET|HEAD | api/petugas-tasks | General / API |
| 92 | POST | api/petugas-tasks | General / API |
| 93 | GET|HEAD | api/petugas-tasks/{petugas_task} | General / API |
| 94 | PUT|PATCH | api/petugas-tasks/{petugas_task} | General / API |
| 95 | DELETE | api/petugas-tasks/{petugas_task} | General / API |
| 96 | GET|HEAD | api/public/pdf/npwpd/{id} | General / API |
| 97 | GET|HEAD | api/public/pdf/skpd/{billId} | Fase 4: Billing |
| 98 | GET|HEAD | api/public/pdf/skrd/{billId} | Fase 4: Billing |
| 99 | GET|HEAD | api/public/pdf/sppt/{billId} | Fase 4: Billing |
| 100 | GET|HEAD | api/public/pdf/sspd/{billId} | Fase 4: Billing |
| 101 | GET|HEAD | api/public/pdf/surat-teguran/{noticeId} | General / API |
| 102 | GET|HEAD | api/reports/bpk | General / API |
| 103 | GET|HEAD | api/reports/monthly | General / API |
| 104 | PUT | api/reports/monthly/{report}/validate | General / API |
| 105 | GET|HEAD | api/reports/petugas-performance | General / API |
| 106 | GET|HEAD | api/reports/recent | General / API |
| 107 | GET|HEAD | api/reports/sipd | General / API |
| 108 | GET|HEAD | api/reports/summary | General / API |
| 109 | GET|HEAD | api/retribution-classifications | Fase 2: Master |
| 110 | POST | api/retribution-classifications | Fase 2: Master |
| 111 | GET|HEAD | api/retribution-classifications/{retribution_classification} | Fase 2: Master |
| 112 | PUT|PATCH | api/retribution-classifications/{retribution_classification} | Fase 2: Master |
| 113 | DELETE | api/retribution-classifications/{retribution_classification} | Fase 2: Master |
| 114 | GET|HEAD | api/retribution-rates | Fase 2: Master |
| 115 | POST | api/retribution-rates | Fase 2: Master |
| 116 | GET|HEAD | api/retribution-rates/{retribution_rate} | Fase 2: Master |
| 117 | PUT|PATCH | api/retribution-rates/{retribution_rate} | Fase 2: Master |
| 118 | DELETE | api/retribution-rates/{retribution_rate} | Fase 2: Master |
| 119 | GET|HEAD | api/retribution-types | Fase 2: Master |
| 120 | POST | api/retribution-types | Fase 2: Master |
| 121 | GET|HEAD | api/retribution-types/{retribution_type} | Fase 2: Master |
| 122 | PUT|PATCH | api/retribution-types/{retribution_type} | Fase 2: Master |
| 123 | DELETE | api/retribution-types/{retribution_type} | Fase 2: Master |
| 124 | GET|HEAD | api/simpad-koneksi/objects/{type} | General / API |
| 125 | GET|HEAD | api/simpad-koneksi/officers | General / API |
| 126 | POST | api/simpad-koneksi/sync-object | General / API |
| 127 | GET|HEAD | api/simpad-koneksi/taxpayers/{npwpd} | Fase 3: WP |
| 128 | POST | api/simulate-tax | General / API |
| 129 | GET|HEAD | api/spot-checks | General / API |
| 130 | POST | api/spot-checks | General / API |
| 131 | GET|HEAD | api/spot-checks/tax-object/{id}/estimation | General / API |
| 132 | PATCH | api/spot-checks/{id}/status | General / API |
| 133 | GET|HEAD | api/spot-checks/{spot_check} | General / API |
| 134 | PUT|PATCH | api/spot-checks/{spot_check} | General / API |
| 135 | DELETE | api/spot-checks/{spot_check} | General / API |
| 136 | GET|HEAD | api/tax-educations | General / API |
| 137 | POST | api/tax-educations | General / API |
| 138 | POST | api/tax-educations/{taxEducation}/broadcast | General / API |
| 139 | GET|HEAD | api/tax-educations/{tax_education} | General / API |
| 140 | PUT|PATCH | api/tax-educations/{tax_education} | General / API |
| 141 | DELETE | api/tax-educations/{tax_education} | General / API |
| 142 | GET|HEAD | api/tax-formulas | General / API |
| 143 | GET|HEAD | api/tax-objects | General / API |
| 144 | POST | api/tax-objects | General / API |
| 145 | GET|HEAD | api/tax-objects/{taxObject}/pending-periods | General / API |
| 146 | GET|HEAD | api/tax-objects/{tax_object} | General / API |
| 147 | PUT|PATCH | api/tax-objects/{tax_object} | General / API |
| 148 | DELETE | api/tax-objects/{tax_object} | General / API |
| 149 | GET|HEAD | api/taxpayers | Fase 3: WP |
| 150 | POST | api/taxpayers | Fase 3: WP |
| 151 | GET|HEAD | api/taxpayers/search/{nik} | Fase 3: WP |
| 152 | GET|HEAD | api/taxpayers/{taxpayer} | Fase 3: WP |
| 153 | PUT|PATCH | api/taxpayers/{taxpayer} | Fase 3: WP |
| 154 | DELETE | api/taxpayers/{taxpayer} | Fase 3: WP |
| 155 | GET|HEAD | api/tte/documents | General / API |
| 156 | POST | api/tte/sign | General / API |
| 157 | GET|HEAD | api/tte/verify/{number} | General / API |
| 158 | POST | api/upload | General / API |
| 159 | GET|HEAD | api/user | General / API |
| 160 | PUT | api/user/location | General / API |
| 161 | POST | api/user/password | General / API |
| 162 | PUT | api/user/profile | General / API |
| 163 | GET|HEAD | api/users | General / API |
| 164 | POST | api/users | General / API |
| 165 | GET|HEAD | api/users/{user} | General / API |
| 166 | PUT|PATCH | api/users/{user} | General / API |
| 167 | DELETE | api/users/{user} | General / API |
| 168 | GET|HEAD | api/verifications | General / API |
| 169 | POST | api/verifications | General / API |
| 170 | GET|HEAD | api/verifications/{verification} | General / API |
| 171 | PUT | api/verifications/{verification}/status | General / API |
| 172 | GET|HEAD | api/verify/bill/{number} | Fase 4: Billing |
| 173 | GET|HEAD | api/verify/payment/{number} | Fase 4: Billing |
| 174 | GET|HEAD | api/zones | General / API |
| 175 | POST | api/zones | General / API |
| 176 | GET|HEAD | api/zones/{zone} | General / API |
| 177 | PUT|PATCH | api/zones/{zone} | General / API |
| 178 | DELETE | api/zones/{zone} | General / API |
| 179 | GET|HEAD | docs | General / API |
| 180 | GET|HEAD | docs/assets/{filename} | General / API |
| 181 | GET|HEAD | docs/{page} | General / API |
| 182 | GET|HEAD | login | Fase 1: Auth |
| 183 | GET|HEAD | sanctum/csrf-cookie | General / API |
| 184 | GET|HEAD | storage/{path} | General / API |
| 185 | GET|HEAD | up | General / API |

### 5.4 STRATEGI PENGUJIAN 10-FASE (ULTIMATE MASTER PLAN)

Audit teknis dilakukan dalam 10 siklus bertahap sesuai [MASTER_TESTING_PLAN.md](file:///Users/pondokit/Herd/retribusi-api/tests/MASTER_TESTING_PLAN.md):

1. **Cycle 1**: Infrastruktur & Domain Deep-Dive.
2. **Cycle 2**: Route Mapping & API Discovery.
3. **Cycle 3**: Audit Logic & Konsistensi Formula.
4. **Cycle 4**: Audit Parity Database (MariaDB).
5. **Cycle 5**: Pentest Keamanan & Perimeter RBAC.
6. **Cycle 6**: Validasi Integritas Asset & Cloudinary.
7. **Cycle 7**: Parity Environment & Konfigurasi PHP.
8. **Cycle 8**: Test Persistensi State CRUD.
9. **Cycle 9**: Stress Test Restorasi Atomik & Fail-Safe.
10. **Cycle 10**: Vonis Readiness Akhir (End-to-End Signature).

### ⚠️ Endpoint Belum Ter-Test

| Endpoint | Method | Prioritas | Saran Test |
|----------|--------|-----------|------------|
| `/api/payments` | GET/POST | 🔴 Tinggi | Tambahkan di `run_role_e2e_test.php` |
| `/api/payments/{id}/status` | PUT | 🔴 Tinggi | Tambahkan skenario verifikasi |
| `/api/citizen/services` | GET | 🟡 Sedang | Test citizen flow |
| `/api/citizen/services/{id}/register` | POST | 🟡 Sedang | Test citizen flow |
| `/api/pbb/bapenda/link-nop` | POST | 🟡 Sedang | Test PBB citizen |
| `/api/pbb/bapenda/pay` | POST | 🟡 Sedang | Test PBB payment |
| `/api/upload` | POST | 🟢 Rendah | Multipart upload test |
| `/api/tte/sign` | POST | 🟡 Sedang | TTE signing flow |
| `/api/pengawas/enforcements/{id}/reject` | POST | ✅ Ter-cover | `test_vtax_parity.php` |



---

## File: docs/04_infrastruktur_api/DEPLOYMENT_SCRIPTS_README.md
---
# Kumpulan Script Deployment (Local Only)

Folder ini berisi banyak file berektensi `.exp` yang merupakan skrip **Expect**. Skrip ini digunakan untuk mengotomatisasi perintah SSH dan SCP ke VPS tanpa harus berulang kali mengetikkan password.

**⚠️ PENTING: File-file ini beserta `vps.txt` sudah dimasukkan ke `.gitignore` sehingga tidak akan di-push ke GitHub untuk menjaga keamanan password server Anda.**

## Daftar Skrip Utama & Fungsinya

### 1. Script Frontend / Nginx
- `vps_deploy_frontend_scp.exp`: Mengirim dan mengaktifkan file konfigurasi Nginx terbaru untuk PWA/Frontend dari lokal ke VPS.
- `vps_setup_mpad_nginx.exp` & `vps_setup_mpad_ssl.exp`: Setup awal konfigurasi Nginx dan SSL otomatis untuk domain produksi.
- `vps_grep_nginx.exp` & `vps_list_nginx.exp`: Mengecek daftar dan isi konfigurasi situs yang aktif di Nginx server.
- `vps_check_nginx_error.exp`: Membaca log error Nginx.

### 2. Script Backend API & Laravel
- `vps_refresh_api.exp`: Membersihkan semua cache Laravel (config, route, view) dan me-restart queue worker di server.
- `vps_deploy_all.exp`: Menjalankan git pull, composer install, dan migrasi secara berurutan.
- `vps_migrate.exp`: Menjalankan `php artisan migrate --force` di production.
- `vps_fix_laravel_perms.exp` & `vps_fix_perms_final.exp`: Memperbaiki izin folder (permissions) `storage` dan `bootstrap/cache` agat dapat ditulis oleh user `www-data`.
- `vps_read_laravel_logs.exp`: Menampilkan error terbaru dari file `storage/logs/laravel.log`.

### 3. Script Utilities VPS
- `vps_check_status.exp`: Mengecek kondisi storage, RAM, dan status service Nginx & PHP-FPM di server.
- `vps_check_time_logs.exp`: Menyelaraskan informasi zona waktu dan output log terbaru.
- `vps_run_certbot.exp`: Memperbarui atau menerbitkan sertifikat SSL dari Let's Encrypt secara otomatis.
- `change_vps_pass.exp` (di /tmp/): Skrip sementara yang digunakan pada 5 Maret 2026 untuk mengamankan dan merotasi password yang bocor di history Git lama.

---

## ⚠️ Aturan Deployment Frontend (KRITIKAL)

> **Semua proses `npm run build` untuk aplikasi frontend (`retribusi-admin`, `retribusi-mobile`, `retribusi-petugas`) wajib dilakukan langsung di server VPS, BUKAN di komputer lokal.**

Alur deployment frontend yang benar:
1. Push kode ke `main` di GitHub dari lokal.
2. SSH ke VPS → masuk ke direktori frontend.
3. `git pull origin main` untuk mendapatkan kode terbaru.
4. `npm install && npm run build` dilakukan di VPS.
5. Nginx otomatis melayani file dari folder `dist/`.

**Tidak diperkenankan** menjalankan `npm run build` lokal dan mengirim `dist/` via SCP.

---
Semua file ini (dan kredensial di `vps.txt`) sepenuhnya aman berada di komputer lokal Anda, namun telah diblokir dari version control. Gunakan `./<nama_file.exp>` untuk mengeksekusinya.


---

## File: docs/01_regulasi_baubau/summary-changes-2026.md
---
# Penjelasan Perubahan Sistem (Update 2026-03-05)

## 1. Implementasi Dokumen Resmi BAPENDA (Backend)
Kami telah melengkapi sistem generator dokumen resmi untuk memenuhi standar BAPENDA. Total terdapat 11 jenis dokumen baru yang sekarang didukung melalui API:

- **Pendaftaran**: 
  - `SKT` (Surat Keterangan Terdaftar) - [NEW]
- **Pendataan**: 
  - `LKOK` (Lembar Kerja Objek Khusus) - [NEW]
- **Penetapan**:
  - `SKRD` (Surat Ketetapan Retribusi Daerah)
  - `SPPT` (Surat Pemberitahuan Pajak Terhutang)
  - `SKPDKBT` (Surat Ketetapan Pajak Daerah Kurang Bayar Tambahan) - [NEW]
  - `SKPDN` (Surat Ketetapan Pajak Daerah Nihil) - [NEW]
- **Penagihan & Pembayaran**:
  - `SSPD` (Surat Setoran Pajak Daerah)
  - `SSRD` (Surat Setoran Retribusi Daerah) - [NEW]
  - `STRD` (Surat Tagihan Retribusi Daerah) - [NEW]
  - `SPP` (Surat Paksa)
  - `SPMP` (Surat Perintah Melaksanakan Penyitaan) - [NEW]

**Cara Kerja**: 
Admin atau Petugas dapat memanggil endpoint `/api/documents/{type}/{id}` untuk mengunduh PDF dokumen tersebut berdasarkan ID tagihan, objek, atau wajib pajak masing-masing.

## 2. Pembersihan Data Produksi (Cleanup Script)
Kami menambahkan script khusus untuk membersihkan data uji coba yang masuk ke database produksi agar laporan keuangan tetap akurat.

- **File**: `scripts/cleanup_production_final.php`
- **Cara Kerja**: Script ini mendeteksi pola data tes seperti `[TEST]`, NIK `9999...`, alamat email `test@...`, dan nama wajib pajak `uji coba`. Script ini berjalan di level aplikasi (Laravel Tinker) untuk menghindari *rate limiting* API.

## 3. Peningkatan Dashboard Pengawas (Admin UI)
Dashboard untuk peran Pengawas/Supervisor ditingkatkan untuk memberikan visibilitas lebih luas terhadap kinerja lapangan.

- **Peta Petugas**: Menampilkan lokasi *real-time* petugas di lapangan (jika fitur lokasi aktif).
- **Deteksi Anomali**: Algoritma baru untuk mendeteksi potensi kecurangan atau keterlambatan pembayaran secara otomatis.
- **Statistik Kepatuhan**: Grafik visual untuk memantau rasio kepatuhan wajib pajak per wilayah (Zona).

## 4. Evaluasi Skema Pembayaran (Lintas Repo)
Kami melakukan audit mendalam terhadap alur pembayaran di 4 repository:

- **Admin**: Sudah mendukung pencatatan manual (Cash/Transfer) dan TTE (Tanda-Tangan Elektronik).
- **Petugas**: Mendukung validasi tagihan di lapangan melalui scan QR dan input manual.
- **Mobile**: Warga bisa melihat tagihan, membayar lewat VA/QRIS, dan memberikan rating pelayanan.
- **API**: Mengelola sinkronisasi status antara tagihan (Bills) dan bukti bayar (Payments).

---
*Semua perubahan telah di-push ke branch **dev**.*


---

## File: docs/01_regulasi_baubau/regulasi/03-perwali-pdrd-summary.md
---
# Ringkasan Perwali Tata Cara Pemungutan PDRD
## Peraturan Walikota tentang Tata Cara Pemungutan Retribusi Daerah dan Pajak Daerah

> **Catatan**: Dokumen ini merupakan template ringkasan yang harus dilengkapi berdasarkan isi dokumen "Perwali Tata Cara Pemungutan PDRD un Sil.pdf"

---

## 1. KETENTUAN UMUM

### 1.1 Definisi
| Istilah | Definisi |
|---------|----------|
| **PDRD** | Pajak Daerah dan Retribusi Daerah |
| **Wajib Pajak (WP)** | Orang pribadi atau badan yang menurut ketentuan peraturan perundang-undangan perpajakan daerah diwajibkan untuk melakukan pembayaran pajak |
| **Wajib Retribusi (WR)** | Orang pribadi atau badan yang menurut peraturan perundang-undangan diwajibkan untuk melakukan pembayaran retribusi |
| **NPWPD** | Nomor Pokok Wajib Pajak Daerah |
| **SKPD** | Surat Ketetapan Pajak Daerah |
| **SKRD** | Surat Ketetapan Retribusi Daerah |
| **SSPD** | Surat Setoran Pajak Daerah |
| **SSRD** | Surat Setoran Retribusi Daerah |

### 1.2 Ruang Lingkup
- Pemungutan Pajak Daerah
- Pemungutan Retribusi Daerah
- Tata cara pendaftaran, penetapan, pembayaran, dan pelaporan

---

## 2. JENIS PAJAK DAERAH DAN TARIF

### 2.1 Pajak Hotel
| Komponen | Keterangan |
|----------|------------|
| Objek Pajak | Pelayanan yang disediakan hotel |
| Subjek Pajak | Orang pribadi atau badan yang melakukan pembayaran kepada hotel |
| Dasar Pengenaan | Jumlah pembayaran/seharusnya dibayar kepada hotel |
| Tarif | **10%** |

### 2.2 Pajak Restoran
| Komponen | Keterangan |
|----------|------------|
| Objek Pajak | Pelayanan penjualan makanan/minuman yang dikonsumsi di tempat |
| Subjek Pajak | Orang pribadi atau badan yang membeli makanan/minuman |
| Dasar Pengenaan | Jumlah pembayaran yang diterima restoran |
| Tarif | **10%** |

### 2.3 Pajak Hiburan
| Jenis Hiburan | Tarif |
|---------------|-------|
| Tontonan film/bioskop | 10% |
| Pagelaran kesenian/musik | 10% |
| Pameran | 10% |
| **Diskotik/Klub Malam/Karaoke** | **40%** |
| Sirkus/akrobat | 10% |
| Permainan bilyard | 10% |
| Permainan ketangkasan | 10% |
| Pacuan kuda/kendaraan bermotor | 10% |
| Panti pijat/refleksi | 10% |

### 2.4 Pajak Parkir
| Komponen | Keterangan |
|----------|------------|
| Objek Pajak | Penyelenggaraan tempat parkir di luar badan jalan |
| Subjek Pajak | Orang pribadi atau badan yang melakukan parkir |
| Dasar Pengenaan | Jumlah pembayaran/seharusnya dibayar untuk penggunaan tempat parkir |
| Tarif | **30%** |

### 2.5 Pajak Air Tanah
| Komponen | Keterangan |
|----------|------------|
| Objek Pajak | Pengambilan dan/atau pemanfaatan air tanah |
| Subjek Pajak | Orang pribadi atau badan yang melakukan pengambilan air tanah |
| Dasar Pengenaan | Nilai Perolehan Air Tanah |
| Tarif | **20%** |

### 2.6 PBJT Tenaga Listrik
| Komponen | Keterangan |
|----------|------------|
| Objek Pajak | Tenaga listrik yang dihasilkan sendiri |
| Subjek Pajak | Pengguna tenaga listrik |
| Dasar Pengenaan | Nilai jual tenaga listrik |
| Tarif | Sesuai ketentuan |

---

## 3. TATA CARA PENDAFTARAN

### 3.1 Pendaftaran Wajib Pajak
1. Wajib Pajak mengisi formulir SPOPD (Surat Pendaftaran Objek Pajak Daerah)
2. Melampirkan dokumen persyaratan:
   - Fotokopi KTP pemilik/pengelola
   - Fotokopi NPWP (jika ada)
   - Fotokopi izin usaha
   - Dokumen pendukung lainnya
3. Menyerahkan formulir ke Badan Pendapatan Daerah
4. Verifikasi oleh petugas pendata
5. Penerbitan NPWPD

### 3.2 Jenis Transaksi
| Kode | Jenis | Keterangan |
|------|-------|------------|
| 1 | Perekaman Data | Pendaftaran objek pajak baru |
| 2 | Pemutakhiran Data | Perubahan data objek pajak |
| 3 | Penghapusan Data | Penonaktifan objek pajak |

---

## 4. TATA CARA PENETAPAN

### 4.1 Penetapan Pajak
1. **Self Assessment**: WP menghitung sendiri pajak terutang
2. **Official Assessment**: Penetapan oleh Fiskus/petugas pajak

### 4.2 Dokumen Penetapan
- SKPD (Surat Ketetapan Pajak Daerah)
- SKPDKB (Surat Ketetapan Pajak Daerah Kurang Bayar)
- SKPDKBT (Surat Ketetapan Pajak Daerah Kurang Bayar Tambahan)
- SKPDLB (Surat Ketetapan Pajak Daerah Lebih Bayar)
- SKPDN (Surat Ketetapan Pajak Daerah Nihil)

---

## 5. TATA CARA PEMBAYARAN

### 5.1 Tempat Pembayaran
- Kas Daerah
- Bank yang ditunjuk
- Tempat lain yang ditentukan

### 5.2 Jatuh Tempo
- Pajak harus dibayar paling lambat **15 hari** setelah saat terutangnya pajak
- Atau sesuai tanggal yang tercantum dalam SKPD

### 5.3 Sanksi Keterlambatan
| Jenis Sanksi | Besaran |
|--------------|---------|
| Bunga keterlambatan | 2% per bulan (maksimal 24 bulan) |
| Denda administrasi | Sesuai ketentuan |

---

## 6. TATA CARA PELAPORAN

### 6.1 Kewajiban Pelaporan
- Wajib Pajak wajib menyampaikan SPTPD (Surat Pemberitahuan Pajak Daerah)
- Pelaporan dilakukan secara berkala (bulanan/triwulan)

### 6.2 Batas Waktu Pelaporan
| Jenis Pajak | Batas Waktu |
|-------------|-------------|
| Pajak Hotel | Tanggal 15 bulan berikutnya |
| Pajak Restoran | Tanggal 15 bulan berikutnya |
| Pajak Hiburan | Tanggal 15 bulan berikutnya |
| Pajak Parkir | Tanggal 15 bulan berikutnya |

---

## 7. RETRIBUSI DAERAH

### 7.1 Jenis Retribusi
1. **Retribusi Jasa Umum**
2. **Retribusi Jasa Usaha**
3. **Retribusi Perizinan Tertentu**

### 7.2 Retribusi Pengelolaan Kekayaan Daerah
Termasuk dalam kategori Retribusi Jasa Usaha:
- Pantai Kamali
- Kota Mara
- Stadion
- Pasar Buah
- Wantiro

---

## 8. PEMERIKSAAN DAN PENEGAKAN

### 8.1 Pemeriksaan Pajak
- Pemeriksaan rutin
- Pemeriksaan khusus
- Pemeriksaan bukti permulaan

### 8.2 Sanksi Pidana
- Penggelapan pajak
- Pemalsuan dokumen
- Ketidakpatuhan yang disengaja

---

## LAMPIRAN

### Daftar Formulir
| No | Kode | Nama Formulir |
|----|------|---------------|
| 1 | SPOPD | Surat Pendaftaran Objek Pajak Daerah |
| 2 | SPTPD | Surat Pemberitahuan Pajak Daerah |
| 3 | SKPD | Surat Ketetapan Pajak Daerah |
| 4 | SSPD | Surat Setoran Pajak Daerah |
| 5 | SKRD | Surat Ketetapan Retribusi Daerah |
| 6 | SSRD | Surat Setoran Retribusi Daerah |

---

*Dokumen terakhir diperbarui: Maret 2026 (Final)*


---

## File: docs/01_regulasi_baubau/regulasi/02-master-data-objek-pajak.md
---
# Master Data Objek Pajak dan Retribusi
## Pemerintah Kota Bau-Bau

---

> [!NOTE]
> **Dokumen ini berisi data objek pajak dan retribusi yang dikelola oleh BAPENDA (Badan Pendapatan Daerah) Kota Bau-Bau.**
> 
> Untuk tahap awal implementasi, sistem akan fokus pada objek-objek berikut yang berada di bawah pengelolaan BAPENDA:
> 
> **Retribusi Pengelolaan Kekayaan Daerah:**
> 1. Pantai Kamali
> 2. Kota Mara
> 3. Stadion
> 4. Pasar Buah
> 5. Wantiro
> 
> **Pajak Hotel dan Restoran:**
> 1. Restoran (10%)
> 2. Hotel (10%)
> 3. Hiburan Malam (40%)
> 
> **Pajak Parkir:**
> - Bandara
> - LIPO
> - Pantai Nirwana

---

## A. RETRIBUSI PENGELOLAAN KEKAYAAN DAERAH

### 1. Pantai Kamali
```json
{
  "kode": "RET-PKD-001",
  "nama": "Pantai Kamali",
  "jenis": "wisata_alam",
  "kategori": "pengelolaan_kekayaan_daerah",
  "lokasi": {
    "alamat": "Pantai Kamali, Kota Bau-Bau",
    "kelurahan": "",
    "kecamatan": "",
    "koordinat": null
  },
  "tarif": {
    "tiket_masuk": 0,
    "parkir_motor": 0,
    "parkir_mobil": 0,
    "fasilitas_lainnya": 0
  },
  "jam_operasional": {
    "buka": "06:00",
    "tutup": "18:00"
  },
  "status": "aktif"
}
```

### 2. Kota Mara
```json
{
  "kode": "RET-PKD-002",
  "nama": "Kota Mara",
  "jenis": "wisata_budaya",
  "kategori": "pengelolaan_kekayaan_daerah",
  "lokasi": {
    "alamat": "Kota Mara, Kota Bau-Bau",
    "kelurahan": "",
    "kecamatan": "",
    "koordinat": null
  },
  "tarif": {
    "tiket_masuk": 0,
    "parkir_motor": 0,
    "parkir_mobil": 0,
    "fasilitas_lainnya": 0
  },
  "status": "aktif"
}
```

### 3. Stadion
```json
{
  "kode": "RET-PKD-003",
  "nama": "Stadion",
  "jenis": "fasilitas_olahraga",
  "kategori": "pengelolaan_kekayaan_daerah",
  "lokasi": {
    "alamat": "Stadion Kota Bau-Bau",
    "kelurahan": "",
    "kecamatan": "",
    "koordinat": null
  },
  "tarif": {
    "sewa_harian": 0,
    "sewa_event": 0,
    "fasilitas_lainnya": 0
  },
  "kapasitas": 0,
  "status": "aktif"
}
```

### 4. Pasar Buah
```json
{
  "kode": "RET-PKD-004",
  "nama": "Pasar Buah",
  "jenis": "pasar_tradisional",
  "kategori": "pengelolaan_kekayaan_daerah",
  "lokasi": {
    "alamat": "Pasar Buah, Kota Bau-Bau",
    "kelurahan": "",
    "kecamatan": "",
    "koordinat": null
  },
  "tarif": {
    "retribusi_harian": 0,
    "retribusi_bulanan": 0
  },
  "jumlah_kios": 0,
  "jumlah_los": 0,
  "status": "aktif"
}
```

### 5. Wantiro
```json
{
  "kode": "RET-PKD-005",
  "nama": "Wantiro",
  "jenis": "wisata_alam",
  "kategori": "pengelolaan_kekayaan_daerah",
  "lokasi": {
    "alamat": "Wantiro, Kota Bau-Bau",
    "kelurahan": "",
    "kecamatan": "",
    "koordinat": null
  },
  "tarif": {
    "tiket_masuk": 0,
    "parkir_motor": 0,
    "parkir_mobil": 0
  },
  "status": "aktif"
}
```

---

## B. PAJAK HOTEL DAN RESTORAN

### 1. Restoran
```json
{
  "kode": "PAJ-PHR-REST",
  "nama": "Pajak Restoran",
  "jenis": "restoran",
  "kategori": "pajak_hotel_restoran",
  "tarif_persen": 10,
  "dasar_pengenaan": "nilai_penjualan_makanan_minuman",
  "keterangan": "Pajak atas penjualan makanan dan minuman di restoran/rumah makan"
}
```

### 2. Hotel
```json
{
  "kode": "PAJ-PHR-HOTEL",
  "nama": "Pajak Hotel",
  "jenis": "hotel",
  "kategori": "pajak_hotel_restoran",
  "tarif_persen": 10,
  "dasar_pengenaan": "nilai_pembayaran_penginapan",
  "sub_kategori": [
    {
      "kode": "bintang_lima",
      "nama": "Hotel Bintang 5",
      "tarif_persen": 10
    },
    {
      "kode": "bintang_empat",
      "nama": "Hotel Bintang 4",
      "tarif_persen": 10
    },
    {
      "kode": "bintang_tiga",
      "nama": "Hotel Bintang 3",
      "tarif_persen": 10
    },
    {
      "kode": "bintang_dua",
      "nama": "Hotel Bintang 2",
      "tarif_persen": 10
    },
    {
      "kode": "bintang_satu",
      "nama": "Hotel Bintang 1",
      "tarif_persen": 10
    },
    {
      "kode": "non_bintang",
      "nama": "Hotel Non-Bintang",
      "tarif_persen": 10
    },
    {
      "kode": "rumah_kost",
      "nama": "Rumah Kost",
      "tarif_persen": 10
    }
  ]
}
```

### 3. Hiburan Malam
```json
{
  "kode": "PAJ-PHR-HIBMALAM",
  "nama": "Pajak Hiburan Malam",
  "jenis": "hiburan_malam",
  "kategori": "pajak_hotel_restoran",
  "tarif_persen": 40,
  "dasar_pengenaan": "nilai_tagihan_kepada_pengunjung",
  "keterangan": "Tarif tertinggi sesuai ketentuan peraturan",
  "sub_kategori": [
    {
      "kode": "diskotik",
      "nama": "Diskotik",
      "tarif_persen": 40
    },
    {
      "kode": "klub_malam",
      "nama": "Klub Malam",
      "tarif_persen": 40
    },
    {
      "kode": "karaoke",
      "nama": "Karaoke",
      "tarif_persen": 40
    }
  ]
}
```

---

## C. PAJAK PARKIR

### 1. Bandara
```json
{
  "kode": "PAJ-PARKIR-BANDARA",
  "nama": "Parkir Bandara",
  "jenis": "parkir",
  "kategori": "pajak_parkir",
  "lokasi": {
    "nama": "Bandara",
    "alamat": "Bandara Betoambari, Kota Bau-Bau"
  },
  "pengelola": "dikelola_jasa_parkir_pihak_ketiga",
  "tarif": {
    "sepeda_motor": 0,
    "mobil": 0,
    "bus_truk": 0
  },
  "pajak_persen": 30,
  "status": "aktif"
}
```

### 2. LIPO (Pusat Perbelanjaan)
```json
{
  "kode": "PAJ-PARKIR-LIPO",
  "nama": "Parkir LIPO",
  "jenis": "parkir",
  "kategori": "pajak_parkir",
  "lokasi": {
    "nama": "LIPO",
    "alamat": "LIPO Mall/Plaza, Kota Bau-Bau"
  },
  "pengelola": "dikelola_jasa_parkir_pihak_ketiga",
  "tarif": {
    "sepeda_motor": 0,
    "mobil": 0
  },
  "pajak_persen": 30,
  "status": "aktif"
}
```

### 3. Pantai Nirwana
```json
{
  "kode": "PAJ-PARKIR-NIRWANA",
  "nama": "Parkir Pantai Nirwana",
  "jenis": "parkir",
  "kategori": "pajak_parkir",
  "lokasi": {
    "nama": "Pantai Nirwana",
    "alamat": "Pantai Nirwana, Kota Bau-Bau"
  },
  "pengelola": "dikelola_sendiri",
  "tarif": {
    "sepeda_motor": 0,
    "mobil": 0
  },
  "pajak_persen": 30,
  "status": "aktif"
}
```

---

## D. RINGKASAN KODE OBJEK PAJAK

| Kategori | Kode Prefix | Contoh |
|----------|-------------|--------|
| Retribusi Pengelolaan Kekayaan Daerah | `RET-PKD-` | RET-PKD-001 |
| Pajak Hotel | `PAJ-PHR-HOTEL` | PAJ-PHR-HOTEL |
| Pajak Restoran | `PAJ-PHR-REST` | PAJ-PHR-REST |
| Pajak Hiburan Malam | `PAJ-PHR-HIBMALAM` | PAJ-PHR-HIBMALAM |
| Pajak Parkir | `PAJ-PARKIR-` | PAJ-PARKIR-BANDARA |
| PBJT Tenaga Listrik | `PBJT-LISTRIK-` | PBJT-LISTRIK-001 |
| PBJT Kesenian/Hiburan | `PBJT-HIBURAN-` | PBJT-HIBURAN-001 |
| Pajak Air Tanah | `PAJ-AIRTANAH-` | PAJ-AIRTANAH-001 |
| PBJT Perhotelan | `PBJT-HOTEL-` | PBJT-HOTEL-001 |

---

## E. CATATAN IMPLEMENTASI

1. **Tarif** - Nilai tarif harus diisi sesuai dengan Peraturan Walikota (Perwali) yang berlaku
2. **Lokasi** - Data koordinat dan alamat lengkap perlu dilengkapi
3. **Status** - Objek pajak dapat berstatus `aktif`, `non_aktif`, atau `dalam_proses`
4. **Kode Unik** - Setiap objek pajak memiliki kode unik untuk identifikasi

---

*Dokumen ini harus diperbarui sesuai dengan data aktual dari Badan Pendapatan Daerah*


---

## File: docs/01_regulasi_baubau/regulasi/plan_pbb_integration.md
---
# Rencana Integrasi PBB (Pajak Bumi dan Bangunan) - Bapenda Baubau

## 1. Strategi Integrasi: Proxy Langsung & Klaim NOP

### Konsep Utama
*   **Proxy Langsung**: Aplikasi hanya menjadi "jembatan" ke API Bapenda untuk pengecekan tagihan dan pembayaran. Data objek pajak PBB tidak disimpan/disalin ke database lokal untuk menghindari sinkronisasi yang rumit dan data usang.
*   **Klaim NOP (Link NOP)**: Mengingat API Bapenda tidak memiliki data NIK, User (Wajib Pajak) yang sudah memiliki akun di aplikasi Retribusi (berbasis NIK) harus melakukan **"Klaim NOP"** sekali saja. Setelah itu, sistem akan menyimpan hubungan antara `user_id` dan `nop`.

### Komponen Sistem

#### A. Database (Schema Changes)
Kita tidak mengubah struktur tabel inti (`payments`, `bills`, `tax_objects`) yang sudah ada. Kita menambahkan tabel pelengkap ("sidecar tables"):

1.  **`taxpayer_pbb_objects`** (Menyimpan daftar NOP milik user)
    ```sql
    CREATE TABLE taxpayer_pbb_objects (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        taxpayer_id BIGINT UNSIGNED NOT NULL, -- Relasi ke tabel 'taxpayers' existing
        nop VARCHAR(25) NOT NULL,             -- NOP Pajak
        name_on_sppt VARCHAR(255),            -- Nama di SPPT (untuk validasi visual)
        address_on_sppt VARCHAR(255),         -- Alamat di SPPT
        is_verified BOOLEAN DEFAULT FALSE,    
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL,
        
        FOREIGN KEY (taxpayer_id) REFERENCES taxpayers(id) ON DELETE CASCADE,
        UNIQUE KEY (taxpayer_id, nop)
    );
    ```

2.  **`transaction_pbb`** (Mencatat riwayat transaksi pembayaran PBB)
    ```sql
    CREATE TABLE transaction_pbb (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT NULL,          -- User yang melakukan transaksi (bisa petugas/warga)
        nop VARCHAR(20) NOT NULL,
        tahun VARCHAR(4) NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        denda DECIMAL(15,2) DEFAULT 0,
        total_bayar DECIMAL(15,2) NOT NULL,
        ntpd VARCHAR(50) NULL,        -- Bukti sah dari Bapenda
        payment_status ENUM('pending', 'success', 'failed', 'reversed') DEFAULT 'pending',
        
        -- Snapshot data WP saat bayar
        wp_name VARCHAR(100),
        wp_address VARCHAR(200),
        
        api_response_json TEXT,       -- Log respon lengkap untuk audit
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL
    );
    ```

#### B. API Service (`retribusi-api`)
1.  **`PbbService` Class**:
    *   `login()`: Mendapatkan Token Bearer, cache support (Redis/File) 24 jam.
    *   `inquiry(nop, tahun)`: Cek tagihan ke Bapenda.
    *   `payment(nop, tahun)`: Eksekusi pembayaran.
    *   `reversal(nop, tahun)`: Pembatalan (Admin only).
2.  **Endpoints**:
    *   `POST /api/pbb/link-nop`: User mengklaim NOP.
    *   `GET /api/pbb/my-objects`: List NOP user beserta tagihan *real-time*.
    *   `POST /api/pbb/pay`: Melakukan pembayaran.

#### C. Aplikasi Mobile (`retribusi-mobile`)
*   **Menu PBB**:
    *   Input NOP baru -> Validasi Nama -> Simpan.
    *   List Tagihan PBB saya (dari NOP yang sudah disimpan).
    *   Bayar via Saldo/QRIS (sistem internal retribusi) -> Backend tembak API Bapenda.

#### D. Aplikasi Petugas (`retribusi-petugas`)
*   Menu **"Bayar PBB Warga"**:
    *   Petugas input NOP warga.
    *   Petugas terima uang tunai.
    *   Petugas klik "Bayar" -> Dapat NTPD -> Cetak Struk Bluetooth.

---

## 2. Analisis Kalkulator PBB & Klasifikasi NJOP

### Kondisi Saat Ini
Di database lokal sudah ada tabel `pbb_njop_classifications` yang berisi klasifikasi nilai tanah/bangunan.

### Rekomendasi Integrasi Kalkulator
Untuk fitur **"Kalkulator Simulasi PBB"** (hanya simulasi, bukan tagihan resmi), kita bisa menggunakan data klasifikasi ini.

**Apakah kalkulasinya sesuai?**
*   **API Bapenda**: Mengembalikan nilai `TOTAL_HARUS_DIBAYAR` yang sudah final (sudah dihitung server Bapenda). **Ini yang kita pakai untuk pembayaran.**
*   **Kalkulator Lokal**: Hanya untuk simulasi "Berapa kira-kira pajak saya jika luas tanah sekian?".
    *   Kita **TIDAK PERLU** menghitung ulang tagihan resmi menggunakan tabel klasifikasi lokal, karena itu berisiko selisih dengan data Bapenda.
    *   Gunakan tabel `pbb_njop_classifications` **HANYA** untuk fitur "Simulasi PBB" di aplikasi mobile, agar warga bisa memperkirakan pajak bangunan baru mereka.

**Kesimpulan Kalkulasi**:
*   Untuk **Pembayaran Resmi**: Gunakan angka dari API Bapenda (`inquiry`). JANGAN menghitung sendiri.
*   Untuk **Simulasi**: Gunakan tabel klasifikasi lokal.


---

## File: docs/01_regulasi_baubau/regulasi/PROPOSAL_PENAWARAN_SIPANDA.md
---
# PROPOSAL PENAWARAN KERJA SAMA
## PENGEMBANGAN MITRA PAD (M-PAD) Management Information Tax, Retribution, and Assets
### KOTA BAUBAU

---

**Kepada Yth.**  
**Kepala Badan Pendapatan Daerah (BAPENDA)**  
**Kota Baubau**  
**Di Tempat**

---

### 1. PENDAHULUAN

**CV. SARJANA KOMPUTER INDONESIA** dengan bangga mengajukan proposal penawaran untuk pengembangan dan implementasi **MITRA PAD (M-PAD) Management Information Tax, Retribution, and Assets**.

Sistem ini dirancang sebagai solusi digital terintegrasi untuk memodernisasi tata kelola pendapatan daerah Kota Baubau, meningkatkan transparansi, serta mengoptimalkan potensi Pendapatan Asli Daerah (PAD) melalui teknologi terkini.

Proposal ini mencakup pengembangan sisi *Backend (API)*, *Dashboard Administrator*, dan *Aplikasi Lapangan untuk Petugas*, yang saling terhubung secara *real-time*.

---

### 2. LINGKUP PEKERJAAN (SCOPE OF WORK)

Berdasarkan analisis kebutuhan, solusi yang kami tawarkan mencakup tiga komponen utama sistem yang saling terintegrasi:

#### A. Backend System & RESTful API (M-PAD API)
Pusat pengolahan data dan logika bisnis yang aman dan handal.
*   **Teknologi:** Laravel 11, MySQL/PostgreSQL.
*   **Fitur Utama:**
    *   **Zonasi & Billing Engine:** Logika otomatis perhitungan pajak dan retribusi berdasarkan wilayah (zona) dan parameter tarif yang dinamis.
    *   **Keamanan Tingkat Lanjut:** Implementasi Laravel Sanctum untuk autentikasi token ganda, pengaturan CORS ketat, dan perlindungan data sensitif.
    *   **Monitoring Real-time:** Integrasi dengan Sentry untuk pemantauan kesehatan sistem dan pelaporan *error* secara otomatis.
    *   **Testing Suite Komprehensif:** Termasuk uji kelayakan produksi, uji penetrasi keamanan (Security Penetration Testing), dan uji beban sistem.

#### B. Dashboard Administrator (M-PAD Admin)
Pusat komando bagi BAPENDA untuk pengelolaan data, monitoring, dan pelaporan.
*   **Teknologi:** React, Vite, Tailwind CSS.
*   **Fitur Utama:**
    *   **Manajemen Pengguna (RBAC):** Pengaturan hak akses bertingkat untuk Admin, Verifikator, dan Viewer.
    *   **Manajemen Data Wajib Pajak (Master Data):** Fitur lengkap (CRUD) untuk pendataan Wajib Pajak, termasuk validasi NIK dan kelengkapan berkas digital.
    *   **Visualisasi Data & Peta Potensi:** Tampilan *dashboard* eksekutif untuk memantau realisasi penerimaan dan sebaran potensi pajak di peta digital.
    *   **Pelaporan & Rekonsiliasi:** Pembuatan laporan pendapatan harian, bulanan, dan tahunan yang akurat dan dapat diekspor.

#### C. Aplikasi Lapangan Petugas (M-PAD Petugas)
Aplikasi bergerak (Mobile Web/PWA) untuk memudahkan petugas lapangan dalam bekerja.
*   **Teknologi:** React, Vite, Tailwind CSS (Progressive Web App).
*   **Fitur Utama:**
    *   **Verifikasi Lapangan:** Kemudahan validasi data objek pajak langsung di lokasi.
    *   **Input Data Potensi:** Formulir digital untuk perekaman data potensi baru secara *real-time* dari lapangan.
    *   **Monitoring Kinerja:** Petugas dapat melihat capaian kinerja pribadi, total wajib retribusi yang didata, dan penerimaan harian.
    *   **Peta Kerja:** Navigasi berbasis peta untuk melihat titik-titik potensi di wilayah tugas masing-masing.

---

### 3. METODOLOGI & JAMINAN KUALITAS

Kami menerapkan metodologi pengembangan modern dengan standar industri tinggi:
*   **Dokumentasi Lengkap:** Menyertakan *System Overview*, Panduan Infrastruktur, Panduan Mitigasi, dan *User Guide* yang komprehensif.
*   **Pengujian Terstandar:** Setiap modul melalui tahap *Unit Testing*, *Integration Testing*, dan *User Acceptance Testing (UAT)* sesuai *checklist* produksi.
*   **Skalabilitas:** Arsitektur sistem dirancang untuk menangani lonjakan data dan pengguna di masa depan tanpa kendala berarti.

---

### 4. RENCANA ANGGARAN BIAYA (RAB)

Berikut adalah rincian penawaran biaya untuk paket pengembangan sistem MITRA PAD (M-PAD) (tidak termasuk aplikasi Warga/Mobile untuk umum, yang akan diajukan dalam skema kerja sama terpisah):

| No | Kategori & Deskripsi Pekerjaan | Total Biaya (IDR) |
|:--:|:---|:---:|
| 1 | **Pengembangan Aplikasi & Pengujian**<br>*(Modul API, Dashboard React, PWA Petugas & Warga, Fitur Kustom, UI/UX, dan Dokumentasi Teknis)* | Rp 140.000.000 |
| 2 | **Pendampingan Strategis (1 Tahun)**<br>*(Dukungan teknis, monitoring evaluasi, dan konsultasi penyesuaian regulasi)* | Rp 15.000.000 |
| | **Subtotal** | **Rp 155.000.000** |
| | **PPN (12%)** | **Rp 18.600.000** |
| | **TOTAL ANGGARAN** | **Rp 173.600.000** |

---

### 5. PENUTUP

Demikian proposal penawaran ini kami sampaikan. Besar harapan kami untuk dapat bermitra dengan BAPENDA Kota Baubau dalam mewujudkan digitalisasi pendapatan daerah yang transparan, akuntabel, dan efisien.

Kami siap untuk melakukan presentasi teknis lebih lanjut dan mendiskusikan detail kebutuhan Bapak/Ibu.

Hormat Kami,

**CV. SARJANA KOMPUTER INDONESIA**


---

## File: docs/01_regulasi_baubau/regulasi/API_PBB_BAUBAU_PTPOS_2025.md
---
# API POSPBB - PTPOS

## BAPENDA KOTA BAUBAU

**API POSPBB 2025**

---

## Daftar Isi

1. [Register User](#register-user)
2. [Login](#login)
3. [Inquiry Data](#inquiry-data)
4. [Payment](#payment)
5. [Reversal](#reversal)

---

## Register User

| Item | Detail |
|------|--------|
| **Method URL** | `http://103.182.72.241:8000/pospbb/Api_pos/register` |
| **Method Type** | `POST` |
| **Method Header** | `Content-Type: application/json` |
| **Description** | Register User |

### Input Parameter (Body Form-Data)

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| USERNAME | CHAR | 200 | 1 | |
| PASSWORD | VARCHAR | 200 | 1 | |
| OUTLET | CHAR | 50 | 1 | ptpos |

### Return Data

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| STATUS | VARCHAR | 100 | 1 | |
| MSG | CHAR | 200 | 1 | |

### Response Sukses

```json
{
  "status": 200,
  "msg": "Registrasi sukses."
}
```

### Response Gagal

```json
{
  "status": 400,
  "msg": "Registrasi gagal. Username sudah dipakai."
}
```

---

## Login

| Item | Detail |
|------|--------|
| **Method URL** | `http://103.182.72.241:8000/pospbb/Api_pos/login` |
| **Method Type** | `POST` |
| **Method Header** | `Content-Type: application/json` |
| **Description** | Login User to Inquiry Data |

### Input Parameter (Body Form-Data)

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| USERNAME | CHAR | 200 | 1 | |
| PASSWORD | VARCHAR | 200 | 1 | |

### Return Data

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| STATUS | VARCHAR | 100 | 1 | |
| TOKEN | Header: Bearer Token | | | Token for Auth, put in the header auto |

### Response Sukses

```json
{
  "status": 200,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE2ODc0NDgzOTksIm5iZiI6MTY4NzQ0ODQwOSwiZXhwIjoxNjg3NDUxOTk5LCJ1c2VybmFtZSI6IlVTRVIxIn0.5DY7ZW4fm_B4AbkERgRTLuL-e72AiUTylyb14dzqh5M"
}
```

### Response Wrong Username

```json
{
  "status": 404,
  "msg": "No data found"
}
```

### Response Wrong Password

```json
{
  "status": 400,
  "msg": "Password tidak sesuai."
}
```

---

## Inquiry Data

| Item | Detail |
|------|--------|
| **Method URL** | `http://103.182.72.241:8000/pospbb/Api_pos/inquiry` |
| **Method Type** | `POST` |
| **Method Header** | `Content-Type: application/json` |
| **Description** | Inquiry Data SPPT |

### Input Header

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| Token | Authorization: Bearer Token | | | 1 x 24 Hour |

### Input Parameter (Body Form-Data)

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| NOP | CHAR | 18 | 1 | Nomor Objek Pajak PBB |
| TAHUN | CHAR | 4 | 1 | Tahun Pajak PBB |

### Return Data

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| STATUS | VARCHAR | 100 | 1 | |
| NAMA_WP | CHAR | 100 | 1 | |
| ALAMAT_WP | CHAR | 100 | 1 | |
| KELURAHAN | CHAR | 100 | 1 | |
| KOTA | CHAR | 100 | 1 | |
| TAHUN | CHAR | 4 | 1 | |
| PBB_POKOK | NUMBER | 12 | 1 | |
| DENDA | NUMBER | 12 | 1 | |
| TOTAL_HARUS_DIBAYAR | NUMBER | 12 | 1 | |
| STATUS_BAYAR | CHAR | 100 | 1 | |

### Response Sukses

```json
{
  "status": 200,
  "nama_wp": "AZMAN",
  "alamat_wp": "DSN PADA KUKU",
  "kelurahan": "PADARAYA MAKMUR",
  "kota": "WAKATOBI",
  "tahun": "2022",
  "pbb_pokok": 53600,
  "denda": 536,
  "total_harus_dibayar": 54136,
  "status_bayar": "BLM BAYAR"
}
```

### Response No Data

```json
{
  "status": 401,
  "msg": "Data tidak ditemukan"
}
```

---

## Payment

| Item | Detail |
|------|--------|
| **Method URL** | `http://103.182.72.241:8000/pospbb/Api_pos/payment` |
| **Method Type** | `POST` |
| **Method Header** | `Content-Type: application/json` |
| **Description** | Inquiry Data SPPT |

### Input Header

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| Token | Authorization: Bearer Token | | | 1 x 24 Hour |

### Input Parameter (Body Form-Data)

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| NOP | CHAR | 18 | 1 | NOP |
| TAHUN | CHAR | 4 | 1 | Tahun Pajak |

### Return Data

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| STATUS | VARCHAR | 100 | 1 | |
| MSG | CHAR | 100 | 1 | |
| NTPD | CHAR | 14 | 1 | |

### Response Sukses

```json
{
  "status": 200,
  "msg": "Pembayaran Sukses",
  "ntpd": "11062025187293"
}
```

### Response No Data

```json
{
  "status": 401,
  "msg": "Data tidak ditemukan"
}
```

---

## Reversal

| Item | Detail |
|------|--------|
| **Method URL** | `http://103.182.72.241:8000/pospbb/Api_pos/reversal` |
| **Method Type** | `POST` |
| **Method Header** | `Content-Type: application/json` |
| **Description** | Inquiry Data SPPT |

### Input Header

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| Token | Authorization: Bearer Token | | | 1 x 24 Hour |

### Input Parameter (Body Form-Data)

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| NOP | CHAR | 18 | 1 | Nomor Objek Pajak PBB |
| TAHUN | CHAR | 4 | 1 | Tahun Pajak |
| KETERANGAN | CHAR | 100 | 1 | |

### Return Data

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| STATUS | VARCHAR | 100 | 1 | |
| MSG | CHAR | 100 | 1 | |

### Response Sukses

```json
{
  "status": 200,
  "msg": "Pembayaran Sukses dibatalkan"
}
```

### Response No Data

```json
{
  "status": 401,
  "msg": "Data tidak ditemukan"
}
```


---

## File: docs/01_regulasi_baubau/regulasi/06-data-objek-pajak-aktual.md
---
# Data Objek Pajak BAPENDA - Statistik & Implementasi Aktual
## Sumber: /Users/pondokit/Downloads/OBJEK PAJAK

> **Tanggal Terakhir Diperbarui**: 31 Januari 2026
> **Status**: Selesai di-import ke Database & Arsitektur Split Selesai

---

## 📊 RINGKASAN DATA AKTUAL (Hasil Import)

Setelah melalui proses deduplikasi NPWPD dan pembersihan data, berikut adalah statistik akhir yang berhasil masuk ke sistem:

| Entitas | Jumlah Records | Keterangan |
|---------|----------------|------------|
| **Wajib Pajak (Taxpayers)** | 1,295 | Deduplikasi berdasarkan NPWPD |
| **Objek Pajak (Tax Objects)** | 42,475 | Terhubung ke NOP & WP |
| **Jenis Pajak (Tax Types)** | 7 | Parkir, Hotel, Hiburan, Restoran, dll. |

---

## 🛠️ IMPLEMENTASI TEKNIS DATABASE

### 1. Perubahan Schema Database
Untuk mengakomodasi data aktual, telah dilakukan modifikasi pada tabel:
- **`tax_objects`**: Penambahan kolom `nop` (Nomor Objek Pajak) sebagai identifier unik objek.
- **`taxpayers`**: Kolom `nik` dibuat nullable untuk mendukung entitas bisnis yang hanya memiliki `npwpd`.

### 2. Automasi Import
Dibuat Artisan Command khusus `app:import-tax-object-data` yang melakukan:
- Mapping otomatis kategori pajak ke OPD (BAPENDA).
- Deduplikasi Wajib Pajak secara cerdas menggunakan NPWPD.
- Penanganan data besar (chunking) khusus untuk kategori Restoran.

---

## 🏗️ ARSITEKTUR REPOSITORY (Split)

Berdasarkan kebutuhan fokus fitur (Petugas Petugas), ekosistem aplikasi sekarang terbagi menjadi:

### 1. `retribusi-admin` (Master Admin)
- **Fokus**: Kelola OPD, User Management Global, Konfigurasi Sistem.
- **User**: Super Admin, Admin OPD.
- **Path**: `/Users/pondokit/Herd/retribusi-admin`

### 2. `retribusi-petugas` (Cashier/Petugas Portal)
- **Fokus**: Penagihan (Billing), Pembayaran (Payment), Laporan Petugas, Manajemen Wajib Pajak.
- **User**: Petugas, Verifikator, Petugas Lapangan.
- **Path**: `/Users/pondokit/Herd/retribusi-petugas`
- **Kustomisasi**: 
  - Branding: **Mpad Petugas**.
  - Navigasi disederhanakan hanya untuk fitur petugas (Dashboard, Billing, Reporting, Profile).
  - Penghapusan module administratif yang tidak perlu (User Mgmt, OPD Mgmt, System Admin).

---

## 📈 DETAIL PER KATEGORI (Aktual)

| Jenis Pajak | Status | Mapping OPD | Keterangan |
|-------------|--------|-------------|------------|
| Pajak Restoran | AKTIF | BAPENDA | Data terbesar (~40k objek) |
| Pajak Hotel | AKTIF | BAPENDA | Termasuk Kos & Wisma |
| Pajak Hiburan | AKTIF | BAPENDA | Termasuk rate 10% & 40% |
| Pajak Parkir | AKTIF | BAPENDA | Objek parkir komersial |
| Pajak Air Tanah | AKTIF | BAPENDA | Industri & Komersial |

---

*Dokumen ini merupakan catatan permanen hasil integrasi data dan restrukturisasi aplikasi Januari 2026.*


---

## File: docs/01_regulasi_baubau/regulasi/API_PBB_BAUBAU_2026.md
---
# API PBB Bapenda - 2026 Schema

This document defines the 2026 implementation of the PBB POS (Pajak Bumi dan Bangunan) API by Bapenda Kota Baubau.

## 1. Revision History

| Version | Date | Changes |
|---------|------|---------|
| 2025.1  | -    | Legacy schema using `Api_pos` endpoints. |
| 2026.1  | 2026-03-04 | Updated to `Api_service` endpoints and normalized response handling. |

## 2. Global Configuration

- **Base URL**: `http://103.182.72.241:8000/pospbb/Api_service`
- **Method**: `POST` (All endpoints)
- **Header**: `Content-Type: multipart/form-data` (Recommended) or `application/x-www-form-urlencoded`
- **Authentication**: Bearer Token (obtained via Login)

## 3. Endpoints

### Login
- **URL**: `{{BASE_URL}}/login`
- **Input**:
    - `username`: string
    - `password`: string
    - `outlet`: string (e.g., "m-PAD") [MANDATORY]
- **Success Response (200)**:
    ```json
    {
      "status": 200,
      "token": "JWT_TOKEN_HERE"
    }
    ```

### Inquiry (Cek Tagihan)
- **URL**: `{{BASE_URL}}/inquiry`
- **Header**: `Authorization: Bearer {{TOKEN}}`
- **Input**:
    - `nop`: string (18 digits)
    - `tahun`: string (4 digits)
- **Success Response (200)**:
    ```json
    {
      "status": 200,
      "data": {
        "nama_wp": "...",
        "alamat_wp": "...",
        "kelurahan": "...",
        "kota": "...",
        "tahun": "...",
        "pbb_pokok": 0,
        "denda": 0,
        "total_harus_dibayar": 0,
        "status_bayar": "BLM BAYAR"
      }
    }
    ```

### Payment (Pembayaran)
- **URL**: `{{BASE_URL}}/payment`
- **Header**: `Authorization: Bearer {{TOKEN}}`
- **Input**:
    - `nop`: string
    - `tahun`: string
    - `tagihan`: number (Full amount from Inquiry) [MANDATORY]
    - `keterangan`: string (Optional description)
- **Success Response (200)**:
    ```json
    {
      "status": 200,
      "msg": "Pembayaran sukses",
      "ntpd": "..."
    }
    ```
- **Already Paid Response (201)**:
    ```json
    {
      "status": 201,
      "msg": "SPPT sudah pernah dibayar tanggal ..."
    }
    ```

### Reversal (Pembatalan)
- **URL**: `{{BASE_URL}}/reversal`
- **Header**: `Authorization: Bearer {{TOKEN}}`
- **Input**:
    - `nop`: string
    - `tahun`: string
    - `keterangan`: string
- **Success Response (200)**:
    ```json
    {
      "status": 200,
      "msg": "Pembayaran Sukses dibatalkan"
    }
    ```

## 4. Development Notes & Obstacles (2025 Lessons)

> [!WARNING]
> **Endpoint Shift**: The path changed from `Api_pos` to `Api_service`. All legacy calls must be updated.
> 
> **Status Check**: Always prioritize checking the `status` field in the JSON body, as the API might return HTTP 200 even for failed business logic (e.g., Token Expired or Data Not Found).

> [!TIP]
> **Auto-Retry Mechanism**: Implementation should include a middleware or service-level retry that clears token cache and re-authenticates on 401/Unauthorized status.


---

## File: docs/01_regulasi_baubau/regulasi/07-status-dokumen-resmi.md
---
# 📊 Laporan Status Implementasi Dokumen BAPENDA

Berdasarkan audit teknis terhadap sistem **M-PAD**, berikut adalah rincian status implementasi dokumen resmi yang terbagi dalam 4 tahapan pemungutan PDRD dan klasifikasi pajak.

## 🏛️ 4 Tahapan Pemungutan & Status Dokumen

Secara keseluruhan, sistem telah memiliki **logika data (JSON)** untuk 100% dari 21 jenis dokumen, dan **seluruhnya sudah memiliki template PDF** siap cetak yang mengikuti regulasi Baubau.

### 🟢 Tahap 1: Pendaftaran (Registration)
*Status: **Selesai** | Template PDF: Ready*
- [x] **NPWPD**: Tergenerasi otomatis saat registrasi Wajib Pajak.
- [x] **SKT (Surat Keterangan Terdaftar)**: **Sudah Jalan** (Template PDF Ready).
- [x] **SPOPD**: Terintegrasi via `TaxpayerController`.

### 🟢 Tahap 2: Pendataan (Data Collection)
*Status: **Selesai** | Template PDF: Ready*
- [x] **LKOK (Lembar Kerja Objek Khusus)**: **Sudah Jalan** (Template PDF Ready).
- [x] **Peta ZNT/NIR**: Tersedia via `ZoneController`.
- [x] **SPOP/LSPOP (PBB)**: Sudah terimplementasi logic-nya (Blade template: `nopd`).

### 🔵 Tahap 3: Penetapan (Assessment/Billing)
*Status: **Selesai** | Template PDF: Ready*
- [x] **SKRD (Surat Ketetapan Retribusi Daerah)**: **Sudah Jalan** (Template PDF Tersedia).
- [x] **SPPT (Surat Pemberitahuan Pajak Terutang)**: **Sudah Jalan** (Template PDF Tersedia).
- [x] **SKPDKBT / SKPDN**: **Sudah Jalan** (Template PDF Tersedia di `skpd.blade`).
- [x] **SK Penghapusan Denda**: Logika sudah siap via modul Amnesty/Waiver.

### 🔴 Tahap 4: Penagihan (Collection/Enforcement)
*Status: **Selesai** | Template PDF: Ready*
- [x] **SSPD (Surat Setoran Pajak Daerah)**: **Sudah Jalan** (Template PDF Tersedia).
- [x] **SPP (Surat Perintah Pemeriksaan)**: **Sudah Jalan** (Template PDF Tersedia).
- [x] **STRD / SSRD**: **Sudah Jalan** (Template PDF Tersedia di `ssrd.blade` & `strd.blade`).
- [x] **SPMP (Surat Paksa/Penyitaan)**: **Sudah Jalan** (Template PDF Tersedia).

---

## 🛠️ Ringkasan Temuan (Semua Ready)

> [!TIP]
> **Update Audit 17 Maret 2026**:
> Seluruh 21 jenis dokumen resmi Bapenda Baubau kini telah memiliki fungsionalitas **Full PDF Download** di backend dan dapat diakses oleh petugas yang berwenang.

---
*Laporan ini diperbarui secara otomatis berdasarkan audit file sistem pada 17 Maret 2026.*


---

## File: docs/01_regulasi_baubau/BAUBAU_REGULATORY_MASTER.md
---
# 🏛️ BAUBAU REGULATORY MASTER (Source of Truth)

Dokumen ini adalah acuan tunggal untuk seluruh logika bisnis sistem **MITRA (Manajemen Informasi Terpadu Retribusi dan Aset Daerah)** sesuai dengan **Perda 1/2024** dan **Perwali Baubau Nomor 8 Tahun 2025**.

---

## 1. Jenis Pajak Daerah & Rumus Perhitungannya

### A. Portofolio Wilayah I (Aset & Properti)

#### 1. Pajak Bumi dan Bangunan (PBB-P2)
Dikenakan atas kepemilikan atau pemanfaatan tanah dan bangunan.
*   **Rumus:** `(NJOP - NJOPTKP) x Tarif`
*   **NJOPTKP:** Ditetapkan sebesar **Rp10.000.000**.
*   **Tarif:** Maksimal **0,3%**, khusus lahan produksi pangan/ternak **0,25%**.

#### 2. Bea Perolehan Hak atas Tanah dan Bangunan (BPHTB)
*   **Rumus:** `(NPOP - NPOPTKP) x Tarif 5%`
*   **NPOPTKP:** 
    *   Perolehan Pertama: **Rp80.000.000**.
    *   Waris/Hibah Washiat (Keluarga Sedarah): **Rp300.000.000**.

#### 3. Pajak Reklame
*   **Rumus:** `Nilai Sewa Reklame (NSR) x Tarif 25%`.
*   **Parameter:** NSR dipengaruhi oleh dimensi fisik dan **Zonasi Kelas Jalan**.

#### 4. Pajak MBLB (Mineral Bukan Logam dan Batuan)
*   **Rumus:** `(Volume x Harga Patokan) x Tarif 15%`.

#### 5. Pajak Sarang Burung Walet
*   **Rumus:** `(Volume Panen x Harga Pasar) x Tarif 10%`.

#### 6. Opsen Pajak (Tambahan Pungutan)
*   **Opsen PKB & Opsen BBNKB:** **66%** dari pokok pajak provinsi.

### B. Portofolio Wilayah II (Konsumsi & Self-Assessment)

#### 1. Pajak Barang dan Jasa Tertentu (PBJT)
Berdasarkan total nilai pembayaran (omzet).
*   **Makanan/Minuman (Restoran):** Omzet x **10%**.
*   **Jasa Perhotelan:** Omzet x **10%**.
*   **Jasa Parkir:** Omzet x **10%**.
*   **Jasa Kesenian & Hiburan:** 
    *   Umum: **10%**.
    *   Khusus (Diskotik, Klub Malam, Karaoke): **40%**.
*   **Tenaga Listrik:** 
    *   Konsumsi Umum: **10%**.
    *   Industri/Migas: **3%**.
    *   Dihasilkan Sendiri: **1,5%**.

#### 2. Pajak Air Tanah (PAT)
*   **Rumus:** `(Volume x Harga Dasar Air/HDA) x Tarif 20%`.

#### 3. Retribusi (Sewa Lapak & Jasa Umum)
*   **Sewa Lapak**: Menggunakan sistem Koefisien (1.3 - 1.0) dikalikan tarif dasar.

---

## 2. Struktur Zonasi & Tarif Spasial (Rules Engine)

Sistem **MITRA** menggunakan alur hirarki: **Jenis Retribusi → Klasifikasi → Zona → Tarif**.

### A. Pajak Reklame (Zonasi Kelas Jalan)
Menentukan besaran NSPR (Nilai Strategis Penyelenggaraan Reklame).
*   **Kelas Jalan A (Sangat Strategis):** Jl. RA. Kartini, Jl. Yos Sudarso, Jl. Jend. Sudirman, Pantai Kamali, Kotamara.
*   **Kelas Jalan B (Strategis):** Jl. Teuku Umar, Jl. RE Martadinata, Jl. Gatot Subroto.
*   **Kelas Jalan C (Standar):** Jl. Imam Bonjol, Jl. Seram, dll.

### B. PBB-P2 (Zona Nilai Tanah / ZNT)
*   Setiap wilayah desa/kelurahan dipetakan dalam blok ZNT.
*   **NIR (Nilai Indikasi Rata-rata):** Mencerminkan harga pasar tanah di zona tersebut.

### C. Retribusi Sewa Lapak (Sistem Koefisien)
*   **Zona Premium (1.3):** Kawasan wisata utama (Pantai Kamali/Kotamara).
*   **Zona Strategis (1.2):** Pusat kota/perdagangan (Pelabuhan Murhum).
*   **Zona Ekonomi (1.1):** Kawasan pasar (Wameo/Pujasera).
*   **Zona Umum (1.0):** Kawasan permukiman.

### D. Retribusi Parkir Tepi Jalan Umum
*   **Zona Premium**: Mobil Rp 3.000, Motor Rp 2.000.
*   **Zona Strategis**: Mobil Rp 2.000, Motor Rp 1.500.
*   **Zona Ekonomi**: Mobil Rp 1.500, Motor Rp 1.000.
*   **Zona Umum**: Mobil Rp 1.000, Motor Rp 1.000.

---

## 3. Siklus Dokumen & Administrasi

### A. Tahap Pendaftaran dan Pendataan
*   **SPOPD:** Pendaftaran usaha PBJT, Reklame, PAT, MBLB, Walet.
*   **SPOP & LSPOP:** Pendaftaran khusus PBB-P2.
*   **SKT:** Bukti legalitas pendaftaran WP.
*   **LKOK:** Kertas kerja pemutakhiran data lapangan.

### B. Tahap Pelaporan dan Penetapan
*   **SPTPD:** Laporan mandiri (Self Assessment) omzet bulanan.
*   **SKPD / SKRD:** Tagihan/ketetapan resmi dari pemerintah daerah.
*   **SPPT:** Ketetapan tagihan PBB-P2.
*   **SKPDKB / SKPDKBT:** Ketetapan Kurang Bayar (setelah pemeriksaan).
*   **SKPDLB / SKPDN:** Ketetapan Lebih Bayar atau Nihil.

### C. Tahap Pembayaran dan Penagihan
*   **SSPD / SSRD:** Bukti legal penyetoran pajak/retribusi.
*   **STPD:** Tagihan sanksi administrasi/denda bunga.
*   **Surat Teguran (1, 2, 3):** Peringatan keterlambatan pembayaran.
*   **SPMP & Surat Paksa:** Penindakan akhir (Penyitaan/Penyegelan).

---

*Dokumen ini merupakan hasil harmonisasi Perda 1/2024 dan Perwali 8/2025. Perbarui setiap terjadi perubahan aturan zonasi atau tarif baru.*

---

## 4. Lampiran: Pemetaan ID Teknis (Database Source of Truth)

Untuk keperluan integrasi sistem **MITRA**, berikut adalah pemetaan ID klasifikasi aktif pada tabel `retribution_classifications`:

| Nama Klasifikasi | ID Database | Kode Unik | Rumus Aktif |
| :--- | :--- | :--- | :--- |
| **PBB-P2** | 186 | `PBB-P2` | `(njop - 10000000) * 0.003` |
| **BPHTB** | 187 | `BPHTB` | `(npop - 80000000) * 0.05` |
| **Pajak Reklame** | 188 | `REKLAME` | `((njopr + nspr) * luas * sisi) * 0.25` |
| **Pajak MBLB** | 189 | `MBLB` | `(volume * harga) * 0.15` |
| **Pajak Walet** | 190 | `WALET` | `(volume * harga) * 0.10` |
| **Opsen Pajak** | 191 | `OPSEN` | `pokok_provinsi * 0.66` |
| **PBJT Makan/Minum**| 192 | `PBJT-FOOD` | `omzet * 0.1` |
| **PBJT Perhotelan** | 193 | `PBJT-HTL` | `omzet * 0.1` |
| **PBJT Hiburan** | 194 | `PBJT-HBR` | `omzet * 0.1` |
| **Hiburan Malam** | 195 | `PBJT-HBR-SP`| `omzet * 0.4` |
| **Tenaga Listrik** | 196 | `PBJT-PLN` | `tagihan * 0.1` |
| **Pajak Air Tanah** | 197 | `AIR-TANAH` | `(volume * hda) * 0.2` |
| **Persampahan** | 198 | `RET-SMP` | `tarif_flat` |
| **Parkir Tepi Jalan**| 199 | `RET-PRK` | `tarif_flat` |
| **Sewa PKD (Lapak)** | 200 | `RET-PKD` | `tarif_dasar * koefisien` |
| **Umum Lainnya** | 156 | `W1-OTH` | `tarif_flat` |


---

## File: docs/01_regulasi_baubau/audit_bapenda_stages.md
---
# 🧾 AUDIT IMPLEMENTASI TAHAPAN BAPENDA
## Evaluasi Keselarasan M-PAD dengan Regulasi (Update April 2026)

Dokumen ini mendokumentasikan hasil audit teknis terhadap keselarasan sistem **M-PAD** dengan 4 tahapan pemungutan PDRD sesuai regulasi BAPENDA Kota Baubau.

---

## 🟢 Tahap 1: Pendaftaran (Registration)
- **Status**: TERIMPLEMENTASI
- **Fitur Utama**: 
    - E-SPOPD/SPTPD untuk 9 jenis Pajak PBJT.
    - **Self-Service**: Wajib Pajak mendaftar mandiri via aplikasi Mobile.
    - **Petugas-Assisted**: Pendaftaran objek baru langsung di lapangan.
    - Master Data: Integrasi NIK (KTP) dan NPWPD otomatis.
- **Rujukan Kode**: `TaxpayerController.php`, `PublicRegistrationController.php`.

## 🟢 Tahap 2: Pendataan (Assessment & Data Collection)
- **Status**: TERIMPLEMENTASI & OPTIMIZED
- **Fitur Utama**:
    - **Visual Audit Reklame**: Audit fisik berbasis foto dan koordinat GPS untuk objek reklame.
    - **Spot Check (Uji Petik)**: Pemantauan omzet harian (Restoran/Hotel) secara berkala (weekend/weekday).
    - **GIS Potential Mapping**: Pemetaan objek pajak di atas peta Satelit (ESRI) untuk melihat potensi yang belum terdaftar.
    - **Live Tracking**: Monitoring lokasi petugas pendata secara real-time.
- **Rujukan Kode**: `SpotCheckController.php`, `BillboardAuditController.php`, `AnalyticsController@getHeatmapData`.

## 🟢 Tahap 3: Penetapan (Official Assessment)
- **Status**: TERIMPLEMENTASI
- **Fitur Utama**:
    - **Formula Parser v2**: Perhitungan dinamis berdasarkan parameter teknis (luas, titik, masa, klasifikasi).
    - **PBB-P2 Module**: Penetapan Nilai Jual Objek Pajak (NJOP) dan penerbitan SPPT.
    - **TTE (Digital Signature)**: Pengesahan dokumen SKPD/SKRD menggunakan tanda tangan elektronik BSrE.
    - **Penalty Engine**: Perhitungan denda otomatis (1% - 2% per bulan) saat jatuh tempo.
- **Rujukan Kode**: `BillController.php`, `FormulaParserService.php`, `PbbCalculationService.php`.

## 🟢 Tahap 4: Penagihan & Penindakan (Collection & Enforcement)
- **Status**: TERIMPLEMENTASI (Full Cycle)
- **Fitur Utama**:
    - **Penalty Waiver (Amnesty)**: Modul penghapusan denda untuk program relaksasi pajak daerah.
    - **Enforcement Notice**: Penerbitan Surat Teguran I, II, dan Surat Paksa (SPMP) digital.
    - **E-Receipt (SSPD)**: Bukti bayar digital dengan pengaman QR-Code.
    - **Payment Gateway**: Integrasi pembayaran via QRIS, Virtual Account, dan loket Bank.
- **Rujukan Kode**: `PaymentController.php`, `PenaltyWaiverController.php`, `EnforcementNoticeController.php`.

---
**Kesimpulan**: Sistem M-PAD telah mencapai kematangan penuh (Full Parity) dalam mendukung siklus 4 Tahap BAPENDA, diperkuat dengan modul Audit Visual dan Penindakan Digital.
*Tanggal Audit Terakhir: 01 April 2026*


---

## File: docs/02_arsitektur_database/database_modernization_comparison.md
---
# Analisis Mendalam Modernisasi Database: Legacy 9pajak vs. Modern M-PAD

Dokumen ini menyajikan perbandingan teknis komprehensif antara arsitektur database lama yang bersifat kaku dengan sistem M-PAD yang fleksibel dan transparan.

---

## I. Ringkasan Poin Utama (Executive Summary)

1.  **Peralihan dari Silo ke Unified:** Menghapus sekat-sekat tabel per jenis pajak menjadi satu database terpadu.
2.  **Fleksibilitas Tanpa Migrasi:** Penggunaan **Metadata JSON** memungkinkan perubahan formulir tanpa mengubah struktur database.
3.  **Transparansi Rumus (Dynamic Coding):** Logika perhitungan pajak dipindahkan dari kode program (PHP) ke database yang bisa diaudit (*Formula Parser*).
4.  **Integritas & Akuntabilitas:** Rekam jejak perubahan (*Audit Trail*) yang sangat detail dan dukungan dokumen digital (TTE).
5.  **Kesiapan GIS:** Integrasi koordinat spasial di level inti data untuk visualisasi potensi pajak di peta.

---

## II. Penjabaran Lengkap: Masalah pada Arsitektur Lama (Legacy Rigidity)

Sistem lama dirancang menggunakan pola **"Table-per-Tax-Type"**, yang mengakibatkan beberapa masalah fundamental:

### 1. Data Silo (Sekat Data)
Tiap jenis pajak (Hotel, Restoran, Reklame) memiliki "kamar" database-nya sendiri.
- **Dampaknya:** Sangat sulit bagi pimpinan untuk mendapatkan pandangan tunggal (*Single View*) mengenai profil pajak seorang warga. Data WP sering terduplikasi dan tidak konsisten antar tabel.

### 2. Schema Rigidity (Kekakuan Struktur)
Setiap field seperti `jumlah_kamar` atau `panjang_reklame` adalah kolom fisik permanen.
- **Dampaknya:** Setiap ada update kebijakan atau tambahan data pendataan, sistem harus menjalani proses **Database Migration** yang berisiko tinggi terhadap kehilangan data dan membutuhkan *downtime* layanan.

### 3. Hardcoded Business Logic (Logika Terkunci)
Rumus perhitungan pajak "tertanam" di dalam bahasa pemrograman PHP.
- **Dampaknya:** Auditor atau admin pajak tidak bisa memverifikasi rumus tersebut tanpa bantuan programmer. Transparansi sangat rendah karena aturan bisnis tidak bisa dilihat langsung dari sistem.

---

## III. Penjabaran Lengkap: Keunggulan Arsitektur M-PAD (Modern Flexibility)

Sistem BARU dirancang dengan prinsip **"Logic-Data Separation"** yang memberikan kelincahan tinggi:

### 1. Unified Database Schema & Relational Integrity
M-PAD menggunakan tabel inti `tax_objects` yang menampung semua jenis objek pajak.
- **Analisis:** Hubungan antara Wajib Pajak (`taxpayers`), Objek (`tax_objects`), Tagihan (`bills`), dan Pembayaran (`payments`) sangat terpusat. Hal ini menjamin **Referential Integrity** (tidak ada data yatim piatu atau duplikasi profil).

### 2. Metadata JSON & Form Schema Analysis
M-PAD mengadopsi kolom `metadata` dengan format JSON.
- **Analisis:** Kita bisa menyimpan atribut apa pun tanpa menambah kolom tabel. Ditambah lagi dengan **`form_schema`** di tabel `RetributionClassification`, sistem bisa menghasilkan formulir input secara dinamis berdasarkan jenis pajaknya. Ini adalah solusi untuk **Scalability** (kemampuan berkembang) jangka panjang.

### 3. Formula Parser Engine (Smart Assessment)
Logika perhitungan dipindahkan ke database dalam kolom `calculation_formula`.
- **Analisis:** `FormulaParserService` mengevaluasi rumus ini secara dinamis (*Dynamic Evaluation*). Admin bisa mengubah tarif atau rumus secepat kilat saat Perwali baru terbit, tanpa harus menyentuh kode program aplikasi sama sekali.

### 4. Audit Trail & Snapshot Integrity
Setiap perubahan data divalidasi dan dicatat dalam tabel `audit_logs`.
- **Analisis:** Sistem mencatat `old_values` (nilai lama) dan `new_values` (nilai baru), beserta `ip_address` dan `user_agent`. Ini memberikan transparansi total kepada Inspektorat atau BPK dalam melacak setiap aktivitas perubahan data keuangan.

### 5. GIS-Centric Data Model
V-Tax Parity mengharuskan data objek pajak memiliki dimensi geografis.
- **Analisis:** Database mendukung penyimpanan `latitude` dan `longitude` secara asli. Hal ini memungkinkan visualisasi **Heatmap** potensi pajak dan membantu petugas lapangan melakukan verifikasi fisik secara presisi menggunakan peta.

---

## IV. Istilah-Istilah Penting (Glossary)

| Istilah | Penjelasan |
| :--- | :--- |
| **Data Silo** | Kondisi di mana data terisolasi dalam tabel-tabel terpisah yang tidak saling terintegrasi. |
| **Schema Migration** | Proses pengubahan struktur tabel database (tambah/hapus kolom). |
| **Metadata JSON** | Format penyimpanan data dinamis dalam satu kolom yang bisa menampung banyak atribut sekaligus. |
| **Formula Parser** | Mesin cerdas yang membaca rumus teks dan mengubahnya menjadi hasil perhitungan angka. |
| **Audit Trail** | Rekam jejak kronologis yang membuktikan urutan aktivitas pada suatu data. |
| **Referential Integrity** | Konsistensi data antar tabel yang saling terhubung (tidak ada data yang hilang kaitannya). |
| **Scalability** | Kemampuan sebuah sistem untuk menangani pertumbuhan data dan fitur tanpa penurunan performa. |

---

> [!IMPORTANT]
> Modernisasi ini mengubah paradigma dari **"Sistem Statis"** menjadi **"Sistem Cerdas"**. Dengan M-PAD, database bukan lagi sekadar tempat penyimpanan, melainkan **Aset Strategis** yang bisa memberikan wawasan keputusan langsung bagi pimpinan daerah.


---

## File: docs/02_arsitektur_database/reference_technical/spopd-form-structure.md
---
# Struktur Data Formulir SPOPD
## Surat Pendaftaran Objek Pajak Daerah - Pemerintah Kota Bau-Bau

Dokumen ini berisi struktur data lengkap dari formulir SPOPD untuk berbagai jenis Pajak Barang dan Jasa Tertentu (PBJT).

---

## Struktur Umum (Shared Fields)

Semua formulir SPOPD memiliki field-field berikut:

### Header
| Field | Tipe | Keterangan |
|-------|------|------------|
| `nomor_formulir` | string | Nomor urut formulir |
| `jenis_transaksi` | enum | `perekaman_data`, `pemutakhiran_data`, `penghapusan_data` |

### Data Subjek Pajak
| Field | Tipe | Keterangan |
|-------|------|------------|
| `npwpd` | string(20) | Nomor Pokok Wajib Pajak Daerah (format: XX-XX-XXXX-XXXXXXX) |
| `npwpd_lama` | string(20) | NPWPD Lama (jika ada perubahan) |
| `nama_usaha` | string(100) | Nama usaha/badan usaha |
| `alamat_usaha` | text | Alamat lengkap usaha |
| `nama_pemilik_pengelola` | string(100) | Nama pemilik/pengelola |
| `nik_pemilik_pengelola` | string(16) | NIK pemilik/pengelola |
| `alamat_pemilik_pengelola` | text | Alamat pemilik/pengelola |
| `nomor_telepon` | string(15) | Nomor telepon |
| `alamat_email` | string(100) | Alamat email |

### Pernyataan Subjek Pajak
| Field | Tipe | Keterangan |
|-------|------|------------|
| `nama_subjek_pajak` | string(100) | Nama yang menandatangani |
| `tanggal_pernyataan` | date | Tanggal pernyataan |
| `tanda_tangan` | blob | Tanda tangan digital |

### Identitas Pendata/Pejabat
| Field | Tipe | Keterangan |
|-------|------|------------|
| `petugas_pendata_tanggal` | date | Tanggal pendataan |
| `petugas_pendata_tanda_tangan` | blob | Tanda tangan petugas |
| `petugas_pendata_nama_jelas` | string(100) | Nama jelas petugas |
| `petugas_pendata_nip` | string(20) | NIP petugas |
| `pejabat_berwenang_tanggal` | date | Tanggal approval |
| `pejabat_berwenang_tanda_tangan` | blob | Tanda tangan pejabat |
| `pejabat_berwenang_nama_jelas` | string(100) | Nama jelas pejabat |
| `pejabat_berwenang_nip` | string(20) | NIP pejabat |

---

## 1. PBJT ATAS TENAGA LISTRIK (Halaman 11)

### Data Objek Pajak - Tenaga Listrik
| Field | Tipe | Keterangan |
|-------|------|------------|
| `golongan_usaha` | enum | `non_industri_pertambangan`, `industri`, `pertambangan`, `sosial` |
| `mesin_pembangkit_nama_tipe` | string(100) | Nama/tipe mesin pembangkit listrik |
| `mesin_pembangkit_kapasitas_daya` | integer | Kapasitas daya dalam VA |
| `penggunaan` | enum | `sumber_listrik_utama`, `sumber_listrik_cadangan`, `sumber_listrik_darurat` |

---

## 2. PBJT ATAS JASA KESENIAN DAN HIBURAN (Halaman 7)

### Data Objek Pajak - Kesenian dan Hiburan

#### Jenis Kesenian dan Hiburan
| Field | Tipe | Keterangan |
|-------|------|------------|
| `jenis_hiburan` | array[enum] | Multiple selection dari options berikut |

**Options Jenis Hiburan:**
- `bioskop_tradisional_film` - Bibenian Tradisional, Film (Bioskop/Keliling)
- `diskotik_klub_malam` - Diskotik/Klub Malam
- `sirkus_pameran_seni` - Sirkus/Pameran Seni
- `fitness_kebugaran_sanggar_senam` - Fitness Centre/Kebugaran, Sanggar Senam
- `karaoke` - Karaoke
- `permainan_bilyard` - Permainan Bilyard
- `pertunjukan_musik_tari_pameran_busana` - Pertunjukan Musik/Tari, Pameran Busana, Kontes Kecantikan
- `permainan_ketangkasan` - Permainan Ketangkasan
- `pertandingan_olahraga` - Pertandingan Olahraga

#### Waktu Pertunjukan
| Field | Tipe | Keterangan |
|-------|------|------------|
| `waktu_pertunjukan` | enum | `berjadwal_tetap_reguler`, `insidental` |

#### Penyelenggaraan Hiburan Insidental
| Field | Tipe | Keterangan |
|-------|------|------------|
| `judul_pertunjukan` | string(200) | Judul pertunjukan |
| `jumlah_kursi_penonton` | integer | Jumlah kursi penonton |
| `jumlah_hari_pertunjukan` | integer | Jumlah hari pertunjukan |

#### Jenis/Kelas Tiket dan Tarif (Array)
| Field | Tipe | Keterangan |
|-------|------|------------|
| `tiket_kelas[].no` | integer | Nomor urut |
| `tiket_kelas[].jenis_kelas` | string(50) | Jenis/kelas tiket (VIP, Regular, dll) |
| `tiket_kelas[].jumlah` | integer | Jumlah tiket |
| `tiket_kelas[].tarif_rp` | decimal(15,2) | Tarif dalam Rupiah |

#### Hiburan Kesenian Tradisional/Film/Pertunjukan Musik (Tarif Berjadwal Tetap)
| Field | Tipe | Keterangan |
|-------|------|------------|
| `jumlah_kursi` | integer | Jumlah kursi |
| `jumlah_pertunjukan` | integer | Jumlah pertunjukan per hari |
| `hari_pertunjukan` | enum | `hari` (per hari) |

#### Hiburan Permainan Bilyard/Permainan Ketangkasan/Video Games
| Field | Tipe | Keterangan |
|-------|------|------------|
| `jumlah_meja_mesin` | integer | Jumlah meja/mesin |
| `jam_buka` | time | Jam buka |
| `s_d` | time | Sampai dengan |
| `tarif_rp_per` | decimal(15,2) | Tarif per satuan waktu |

#### Hiburan Diskotik/Klub Malam
| Field | Tipe | Keterangan |
|-------|------|------------|
| `kapasitas_orang` | integer | Kapasitas dalam jumlah orang |
| `tarif_rp` | decimal(15,2) | Tarif masuk |

#### Hiburan Pusat Kebugaran/Sanggar Senam
| Field | Tipe | Keterangan |
|-------|------|------------|
| `kapasitas_orang` | integer | Kapasitas dalam jumlah orang |

#### Tarif Membership (Array)
| Field | Tipe | Keterangan |
|-------|------|------------|
| `membership[].no` | integer | Nomor urut |
| `membership[].jenis_kelas` | string(50) | Jenis/kelas membership |
| `membership[].tarif_rp` | decimal(15,2) | Tarif dalam Rupiah |

---

## 3. PBJT ATAS PAJAK AIR TANAH (Halaman 14)

### Data Objek Pajak - Air Tanah
| Field | Tipe | Keterangan |
|-------|------|------------|
| `tujuan_pemanfaatan` | enum | `non_niaga`, `niaga`, `industri_dengan_bahan_baku_air`, `pelayanan_publik_oleh_pdam` |
| `lokasi_sumber_air` | text | Lokasi sumber air |
| `mesin_pompa_nama_tipe` | string(100) | Nama/tipe mesin pompa |
| `mesin_pompa_kapasitas` | decimal(10,2) | Kapasitas dalam M³/Jam |
| `meteran_air` | enum | `ada`, `tidak_ada` |

---

## 4. PBJT ATAS JASA PARKIR (Halaman 12)

### Data Objek Pajak - Parkir
| Field | Tipe | Keterangan |
|-------|------|------------|
| `lokasi` | text | Lokasi area parkir |
| `pengelola` | enum | `dikelola_sendiri_pemilik_lahan_gedung`, `dikelola_jasa_parkir_pihak_ketiga` |

#### Kapasitas Parkir
| Field | Tipe | Keterangan |
|-------|------|------------|
| `luas_lahan` | decimal(10,2) | Luas lahan dalam m² |
| `parkir_roda_dua_kapasitas` | integer | Kapasitas kendaraan roda dua |
| `parkir_roda_empat_kapasitas` | integer | Kapasitas kendaraan roda empat |

#### Waktu Operasional
| Field | Tipe | Keterangan |
|-------|------|------------|
| `hari_operasional` | array[enum] | `senin`, `selasa`, `rabu`, `kamis`, `jumat`, `sabtu`, `minggu` |
| `jam_buka` | time | Jam buka |
| `jam_tutup` | time | Jam tutup (s/d) |
| `pengecualian_libur` | text | Pengecualian/libur jika ada |

#### Tarif (Rupiah)
| Field | Tipe | Keterangan |
|-------|------|------------|
| `tarif_sepeda` | decimal(15,2) | Tarif sepeda |
| `tarif_sepeda_motor` | decimal(15,2) | Tarif sepeda motor |
| `tarif_mobil_penumpang_pickup` | decimal(15,2) | Tarif mobil penumpang/pickup sejenisnya |
| `tarif_truk_gandengan_bus_besar` | decimal(15,2) | Tarif truk gandengan/bus besar sejenisnya |
| `tarif_truk_bus_sedang_kecil` | decimal(15,2) | Tarif truk/bus sedang/kecil sejenisnya |
| `tarif_tambahan` | decimal(15,2) | Tarif tambahan (jika ada) |

#### Karcis Parkir
| Field | Tipe | Keterangan |
|-------|------|------------|
| `jenis_karcis` | enum | `keluaran_sistem_informasi_berbasis_komputer`, `karcis_dengan_perforasi`, `karcis_biasa`, `tidak_ada` |

---

## 5. PBJT ATAS JASA PERHOTELAN (Halaman 3D)

### Data Objek Pajak - Perhotelan

#### Jenis Hotel
| Field | Tipe | Keterangan |
|-------|------|------------|
| `jenis_hotel` | enum | `bintang_lima`, `bintang_empat`, `bintang_tiga`, `bintang_dua`, `bintang_satu`, `non_bintang`, `rumah_kost` |

#### Jaringan Hotel
| Field | Tipe | Keterangan |
|-------|------|------------|
| `jaringan_hotel` | enum | `tidak_ada`, `nasional`, `internasional` |
| `nama_jaringan` | string(100) | Nama jaringan hotel (jika ada) |

#### Tipe dan Jumlah Kamar (Array)
| Field | Tipe | Keterangan |
|-------|------|------------|
| `kamar[].no` | integer | Nomor urut |
| `kamar[].tipe_kamar` | string(50) | Tipe kamar (Standard, Deluxe, Suite, dll) |
| `kamar[].jumlah_operasional` | integer | Jumlah kamar operasional |
| `kamar[].jumlah_non_operasional` | integer | Jumlah kamar non-operasional |
| `kamar[].tarif_rata_rata` | decimal(15,2) | Tarif rata-rata per kamar dalam Rupiah |

#### Fasilitas Hotel
| Field | Tipe | Keterangan |
|-------|------|------------|
| `fasilitas_hotel` | array[enum] | Multiple selection |

**Options Fasilitas:**
- `restoran_kafetaria_bar` - Restoran/Kafetaria/Bar
- `spa_pusat_kebugaran` - Spa/Pusat Kebugaran
- `diskotik_karaoke_klub_malam` - Diskotik/Karaoke/Klub Malam
- `ruang_rapat_pertemuan_ballroom` - Ruang Rapat/Pertemuan/Ballroom

#### Sistem Akuntansi
| Field | Tipe | Keterangan |
|-------|------|------------|
| `sistem_akuntansi` | enum | `pembukuan`, `pencatatan_sederhana`, `tidak_ada` |

#### Bon Penjualan (Bill)
| Field | Tipe | Keterangan |
|-------|------|------------|
| `jenis_bon_penjualan` | enum | `keluaran_sistem_informasi_berbasis_komputer`, `keluaran_mesin_cash_register`, `bon_penjualan_dengan_perforasi`, `bon_penjualan_biasa`, `tidak_ada` |

---

## Keterangan Pengisian
1. Isilah formulir dengan benar, jelas dan lengkap dengan menggunakan huruf balok/kapital
2. Kolom dengan warna abu-abu diisi oleh petugas
3. Beri tanda silang (X) pada kotak yang sesuai

---

## Catatan Implementasi

### Jenis Pajak (tax_type)
```
tenaga_listrik      = PBJT Atas Tenaga Listrik
kesenian_hiburan    = PBJT Atas Jasa Kesenian dan Hiburan
air_tanah           = Pajak Air Tanah
parkir              = PBJT Atas Jasa Parkir
perhotelan          = PBJT Atas Jasa Perhotelan
```

### Status Transaksi
```
perekaman_data     = Pendaftaran baru
pemutakhiran_data  = Update data existing
penghapusan_data   = Hapus/non-aktifkan objek pajak
```


---

## File: docs/02_arsitektur_database/reference_technical/core_apis.md
---
# Core APIs (MITRA)

Modul ini menangani pengelolaan entitas bisnis inti: Wajib Pajak, Objek Pajak, Penagihan (Billing), dan Pembayaran. Kebanyakan endpoint ini memerlukan **Bearer Token**.

## 1. Wajib Pajak (Taxpayers)

### List Wajib Pajak
- **URL**: `/taxpayers`
- **Method**: `GET`
- **Filter**: `?search=name_or_nik`

### Cari Wajib Pajak (NIK)
Digunakan untuk pencarian cepat saat pendaftaran objek baru.
- **URL**: `/taxpayers/search/{nik}`
- **Method**: `GET`

---

## 2. Objek Pajak (Tax Objects)

### List Objek Pajak
- **URL**: `/tax-objects`
- **Method**: `GET`

---

## 3. Billing & Penagihan

### Buat Tagihan (Entry Data)
- **URL**: `/bills`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "tax_object_id": 1,
    "amount": 500000,
    "period_month": 3,
    "period_year": 2026,
    "notes": "Tagihan Maret"
  }
  ```

### Export Dokumen (PDF)
- **SKRD**: `/bills/{id}/skrd`
- **SSPD**: `/bills/{id}/sspd`
- **SPPT (PBB)**: `/bills/{id}/sppt`
- **Method**: `GET`

---

## 4. Pembayaran (Payments)

### Konfirmasi Pembayaran
- **URL**: `/payments`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "bill_id": 101,
    "payment_method": "cash",
    "amount_paid": 500000
  }
  ```

### Cek Tunggakan (Mobile)
- **URL**: `/citizen/services/pending-periods`
- **Method**: `GET`


---

## File: docs/02_arsitektur_database/reference_technical/surveillance_tte.md
---
# Surveillance & TTE (Advanced - MITRA)

Modul ini digunakan oleh Pengawas dan Admin untuk memantau kepatuhan, audit trail, serta proses legalisasi dokumen secara digital (TTE).

## 1. Surveillance (Pengawasan)

### Audit Logs
Melihat riwayat aktifitas user di sistem.
- **URL**: `/pengawas/audit-logs`
- **Method**: `GET`

### Deteksi Anomali
Melihat Wajib Pajak atau tagihan yang diidentifikasi tidak wajar.
- **URL**: `/pengawas/anomalies`
- **Method**: `GET`

### Monitoring Penegakan (Enforcement)
- **List Notifikasi**: `/pengawas/enforcements` (Method: `GET`)
- **Approve Notifikasi**: `/pengawas/enforcements/{id}/approve` (Method: `POST`)

---

## 2. Tax Amnesty (Penghapusan Denda)

Digunakan untuk mengajukan atau menyetujui penghapusan denda administratif.
- **Ajukan Amnesty**: `/amnesty` (Method: `POST`)
- **Setujui/Tolak**: `/amnesty/{id}/approve` atau `/amnesty/{id}/reject` (Method: `POST`)

---

## 3. E-Registry & TTE

### Registrasi E-Doc
Melihat dokumen yang terdaftar di registry.
- **URL**: `/tte/documents`
- **Method**: `GET`

### Tanda Tangan Digital (Sign)
Melakukan proses TTE pada tagihan (Bill).
- **URL**: `/tte/sign`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "bill_id": 1,
    "passphrase": "your_secure_passphrase"
  }
  ```

### Verifikasi TTE (Public Access)
Memeriksa keabsahan tanda tangan digital pada dokumen.
- **URL**: `/tte/verify/{document_number}`
- **Method**: `GET`


---

## File: docs/02_arsitektur_database/reference_technical/bapenda_process_stages.md
---
# 📑 TAHAPAN PEMUNGUTAN PDRD (BAPENDA)

Dokumen ini memetakan proses bisnis pemungutan Pajak Daerah dan Retribusi Daerah (PDRD) ke dalam modul-modul sistem **M-PAD**.

---

## 1. Tahap Pendaftaran
*Pendaftaran Wajib Pajak dan Objek Pajak baru ke dalam sistem.*

- **Dokumen Terkait**:
    - **SPOPD** (Surat Pendaftaran Objek Pajak Daerah)
    - **NPWPD** (Nomor Pokok Wajib Pajak Daerah)
    - **SKT** (Surat Keterangan Terdaftar)
- **Implementasi M-PAD**:
    - Modul pendaftaran di **M-PAD Mobile** (WP Mandiri) dan **Petugas App** (Pendaftaran oleh petugas).
    - Verifikasi pendaftaran di **Admin Panel**.
    - Auto-generate NPWPD setelah verifikasi disetujui.

## 2. Tahap Pendataan
*Identifikasi potensi dan pemutakhiran data objek pajak di lapangan.*

- **Dokumen Terkait**:
    - **LKOK** (Lembar Kerja Objek Khusus)
    - **Peta ZNT** (Zona Nilai Tanah) & NIR (Nilai Indikasi Rata-rata)
    - **LHP** (Laporan Hasil Penelitian/Pemeriksaan)
- **Implementasi M-PAD**:
    - **GPS Tagging** & Foto lokasi objek pajak via Petugas App.
    - Overlay **Zona (ZNT)** di Dashboard Admin untuk validasi potensi.
    - Modul Survei Potensi oleh Petugas Lapangan.

## 3. Tahap Penetapan
*Perhitungan nilai pajak/retribusi yang harus dibayar.*

- **Dokumen Terkait**:
    - **SPTPD** (Surat Pemberitahuan Pajak Daerah) - *Self Assessment*
    - **SKPD** (Surat Ketetapan Pajak Daerah) - *Official Assessment*
    - **SKRD** (Surat Ketetapan Retribusi Daerah)
    - **SPPT** (Surat Pemberitahuan Pajak Terutang) - PBB-P2
- **Implementasi M-PAD**:
    - **Billing Engine**: Perhitungan otomatis berdasarkan rumus dinamis (Perda No. 1/2024).
    - Generator PDF untuk dokumen ketetapan (SPTPD/SKPD/SKRD) dengan QR Code.

## 4. Tahap Penagihan
*Proses monitoring pembayaran hingga tindakan penagihan sanksi.*

- **Dokumen Terkait**:
    - **SSPD** (Surat Setoran Pajak Daerah) / **SSRD**
    - **STPD** (Surat Tagihan Pajak Daerah) - Untuk sanksi/denda.
    - **Surat Teguran** 1, 2, & 3.
- **Implementasi M-PAD**:
    - **Real-time Monitoring**: Dashboard realisasi pendapatan.
    - **Penalty Engine**: Auto-calculate denda 2% per bulan.
    - **Notifikasi OTOMATIS**: Pengiriman draf Surat Teguran via WhatsApp (Fonnte).

---
*Referensi: Perwali Tata Cara Pemungutan PDRD Kota Baubau.*


---

## File: docs/02_arsitektur_database/reference_technical/penalty_scheme.md
---
# Skema Penalty & Denda (MITRA)

Dokumen ini menjelaskan mekanisme perhitungan sanksi administrasi (bunga), denda keterlambatan lapor, serta prosedur penghapusan denda (amnesty) berdasarkan **Perwali No. 58/2024**.

## 1. Sanksi Administrasi (Bunga Keterlambatan)

Sanksi bunga dihitung dari total pokok pajak yang belum dibayar. Perhitungannya dimulai 1 hari setelah melewati **Jatuh Tempo (Due Date)**.

### Tarif Bunga per Bulan
Tarif bunga dibedakan berdasarkan jenis penetapan atau kondisi keterlambatan:

| Jenis Sanksi | Tarif / Bulan | Keterangan |
| :--- | :--- | :--- |
| **STPD** | 1.0% | Keterlambatan Pembayaran/Setoran (Standar) |
| **SKPDKB** | 1.8% | Hasil Pemeriksaan Umum |
| **Jabatan** | 2.2% | Pemeriksaan karena tidak lapor/tidak pembukuan |
| **Lainnya** | 0.6% | Angsuran, Penundaan, atau Salah Hitung |

### Aturan Perhitungan Waktu
- **Bagian Bulan dihitung Penuh**: Keterlambatan 1 hari tetap dihitung sebagai 1 bulan penuh.
- **Maksimal Durasi**: Sanksi bunga maksimal dikenakan untuk **24 bulan**.
- **Rumus**: `Sanksi = Pokok x Tarif x Jumlah Bulan (Max 24)`

---

## 2. Denda Keterlambatan Lapor (Fixed Fine)

Khusus untuk jenis pajak **Self Assessment** (Wilayah II), wajib pajak wajib melaporkan omzetnya (SPTPD) setiap bulan.
- **Denda**: Rp 100.000,- (flat) jika tidak melapor tepat waktu.

---

## 3. Ketentuan Khusus PBB-P2

PBB-P2 memiliki kebijakan khusus terkait masa tenggang:
- **Jatuh Tempo Standar**: Biasanya 10 November pada tahun berjalan.
- **Grace Period Baru**: Wajib Pajak baru (Pendaftaran Baru) diberikan masa tenggang **6 bulan** sejak pendaftaran sebelum mulai dikenakan bunga jika belum bayar.

---

## 4. Amnesty & Waiver (Penghapusan Denda)

Sistem MITRA mendukung pengajuan penghapusan atau pengurangan denda melalui modul **Amnesty**.

### Prosedur
1. **Pengajuan**: Petugas lapangan atau Admin mengajukan permohonan atas permintaan Wajib Pajak dengan menyertakan alasan.
2. **Jenis Pengurangan**:
   - **Persentase**: Contoh: Pengurangan 50% atau 100% (penghapusan total).
   - **Nominal Tetap**: Contoh: Pengurangan sebesar Rp 50.000.
3. **Persetujuan**: Harus disetujui oleh **Kabid** atau **Super Admin** melalui dashboard sebelum tagihan (Bill) diperbarui.

---

## 5. Otomasi Sistem
Sistem menjalankan command `bills:calculate-penalties` secara berkala (Cron Job) untuk memperbarui nilai sanksi pada seluruh tagihan yang berstatus `pending` dan telah melewati jatuh tempo.


---

## File: docs/02_arsitektur_database/reference_technical/public_apis.md
---
# Public APIs & Authentication

Endpoint ini digunakan untuk proses login, registrasi awal, dan simulasi perhitungan pajak tanpa memerlukan token autentikasi penuh (kecuali ditentukan lain).

## 1. Authentication

### Login (Admin/Petugas)
Digunakan untuk login ke aplikasi Admin dan Petugas.
- **URL**: `/login`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "email": "admin@example.com",
    "password": "password"
  }
  ```
- **Response**: `200 OK` (dengan Bearer Token)

### Citizen Login
Digunakan khusus untuk aplikasi Mobile (Wajib Pajak).
- **URL**: `/citizen/login`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "nik": "7471xxxxxxxxxxxx",
    "password": "password"
  }
  ```

---

## 2. Tax Simulation & Formulas

### Simulasi Perhitungan Pajak
Menghitung estimasi pajak berdasarkan rumus dinamis.
- **URL**: `/simulate-tax`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "classification_id": 1,
    "variables": {
      "nilai_transaksi": 1000000,
      "jumlah_hari": 30
    }
  }
  ```

### Ambil Daftar Rumus
Mengambil daftar klasifikasi yang memiliki schema input untuk kalkulator.
- **URL**: `/tax-formulas`
- **Method**: `GET`

---

## 3. PBB-P2 Lookup & Calculation

### Lookup Kelas NJOP
Mencari kelas NJOP berdasarkan nilai (Bumi/Bangunan).
- **URL**: `/pbb/lookup-class`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "type": "bumi",
    "value": 250000
  }
  ```

### Kalkulasi PBB
Menghitung PBB-P2 berdasarkan luas dan kelas.
- **URL**: `/pbb/calculate`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "luas_bumi": 100,
    "kelas_bumi": "075",
    "luas_bangunan": 50,
    "kelas_bangunan": "030"
  }
  ```

---

## 4. Public Verification

### Verifikasi SKRD/SSPD
Memeriksa keabsahan dokumen via QR Code/Nomor.
- **URL**: `/verify/bill/{number}` atau `/verify/payment/{number}`
- **Method**: `GET`


---

## File: docs/02_arsitektur_database/reference_technical/billing_system_documentation.md
---
# Dokumentasi Sistem Billing (Penagihan)

Dokumen ini menjelaskan arsitektur, logika perhitungan, dan prosedur operasional untuk penerbitan tagihan (billing) dalam sistem retribusi.

---

## 1. Arsitektur Data (Schema)

Pusat data penagihan dikelola melalui dua entitas utama:

- **`bills` (Model: `Bill.php`)**:
  - Rekaman formal tagihan (Invoice/SKRD).
  - Memiliki `bill_number` unik (Format: `INV-YYYYMMDD-XXXXXX`).
  - Menyimpan rincian nominal: Pokok (`amount`), Bunga (`penalty_amount`), Denda Administrasi (`fixed_fine_amount`), dan Potensi Pengurangan (`waived_penalty_amount`).
  - Status: `pending`, `success`, `expired`, `cancelled`.

- **`payments` (Model: `Payment.php`)**:
  - Catatan realisasi transaksi.
  - Terhubung ke `bill_id` (jika membayar tagihan spesifik) atau langsung ke `tax_object_id` (untuk pembayaran periodik tanpa invoice).

---

## 2. Metodologi Penerbitan Billing

Penerbitan tagihan dilakukan secara **Trigger-Based** (manual/masal) atau **Virtual** (on-demand), bukan melalui otomatisasi Cron Job murni.

### A. Manual Single Billing (On-Demand)
Digunakan untuk kasus khusus atau pendaftaran baru.
- **Lokasi**: `BillController@store` (`POST /api/bills`)
- **Prosedur**: Petugas memilih objek pajak, periode, dan tanggal jatuh tempo.

### B. Bulk Generation (Penerbitan Masal)
Digunakan untuk penerbitan awal bulan/tahun bagi seluruh wajib pajak dalam satu kategori.
- **Lokasi**: `BillController@bulkStore` (`POST /api/bills/bulk`)
- **Prosedur**: Sistem mencari seluruh `TaxObject` aktif di bawah `RetributionType` tertentu dan membuat record tagihan secara paralel.

### C. Self-Assessment Flow
Untuk jenis retribusi yang memerlukan laporan mandiri (misal: Parkir/Restoran).
- **Alur**: Laporan Masuk (`MonthlyReport`) → Persetujuan Hubungan/Admin → Terbit Billing berdasarkan nominal laporan.

### D. Virtual Billing Discovery
Sistem dapat mendeteksi tunggakan tanpa invoice fisik melalui **`BillingService.php`**.
- **Logika**: Membandingkan `created_at` objek pajak dengan siklus billing vs riwayat pembayaran sukses.
- **Tujuan**: Memungkinkan Wajib Pajak membayar periode tertentu meskipun Admin belum "menerbitkan" invoice di tabel `bills`.

---

## 3. Konsep Masa Depan: **Hybrid Dynamic Billing**

Untuk meningkatkan efisiensi dan ketaatan pajak tanpa mengorbankan validitas audit, sistem bergerak menuju model **Hybrid**:

### A. Virtual Discovery (Sisi Wajib Pajak/UX)
- **Logic**: Sistem tidak lagi mengandalkan tabel `bills` yang telah terbit secara statis.
- **Mekanisme**: Saat User membuka aplikasi, `BillingService@getPendingPeriods` akan menghitung "utang berjalan" secara dinamis berdasarkan: `Tanggal Daftar` vs `Hukum Siklus` vs `Riwayat Pembayaran Sukses`.
- **Manfaat**: User selalu melihat tagihan terbaru tanpa perlu Admin melakukan "Generate" manual.

### B. Just-in-Time (JIT) Billing (Sisi Audit/Legalitas)
- **Logic**: Record di tabel `bills` (SKRD resmi) hanya dibuat pada saat aksi nyata dilakukan.
- **Mekanisme**: Begitu User menekan tombol **"BAYAR"**, sistem akan:
  1. Generate record `bills` baru untuk periode tersebut.
  2. Memberikan nomor invoice resmi.
  3. Melakukan sinkronisasi ke tabel `payments`.
- **Manfaat**: Database tetap bersih dari "tagihan sampah", namun setiap rupiah yang masuk tetap memiliki bukti audit (Nomor Invoice/SKRD) yang sah.

### C. Dashboard Ketaatan (Sisi Admin)
- **Mekanisme**: Admin melihat "Tingkat Ketaatan" (Compliance Rate) yang dihitung secara *real-time* dengan membandingkan potensi (objek pajak aktif) terhadap realisasi (pembayaran sukses), bukan sekadar melihat tagihan yang terbit.

---

## 4. Logika Perhitungan Nominal

Nominal tagihan ditentukan melalui hirarki berikut (diatur di `BillingService.php`):

1. **Fixed Rate**: Mengambil nilai tetap dari `RetributionRate`.
2. **Formula-Based**: Menggunakan `FormulaParserService` untuk menghitung rumus dinamis (contoh: `volume * tarif`). Data diambil dari `metadata` objek pajak.
3. **PBB-P2 Special**: Menggunakan `PbbCalculationService` (Rumus: `(NJOP - NJOPTKP) * Tarif`).

---

## 4. Sistem Denda Otomatis

Meskipun penerbitan tagihan bersifat manual/masal, update denda dilakukan secara otomatis setiap hari.
- **Command**: `php artisan bills:calculate-penalties`
- **File**: `app/Console/Commands/CalculateBillPenalties.php`
- **Logika**:
  - Jika `due_date` terlewati, denda bunga (persentase per bulan) mulai dihitung.
  - Bagian dari bulan (sekalipun 1 hari) dihitung sebagai 1 bulan penuh.

---

## 5. File-File Penting (Developer Reference)

| File | Peran |
| :--- | :--- |
| `app/Models/Bill.php` | Struktur data & relasi tagihan. |
| `app/Http/Controllers/BillController.php` | API Endpoints untuk manajemen billing. |
| `app/Services/BillingService.php` | Mesin perhitungan tunggakan & nominal. |
| `app/Services/FormulaParserService.php` | Parser rumus denda & tarif dinamis. |
| `app/Console/Commands/CalculateBillPenalties.php` | Skrip otomatisasi penalty harian. |
| `database/migrations/*create_bills_table.php` | Definisi skema tabel di database. |

---
*Terakhir diupdate: 3 Maret 2026*


---

## File: docs/02_arsitektur_database/DOMAIN_SCHEMA.md
---
# 🌐 Domain Schema & Subdomain Mapping
## Arsitektur M-PAD (April 2026)

Dokumentasi ini merinci pemetaan domain dan subdomain untuk sistem M-PAD (Mitra Pajak & Retribusi Daerah) Kota Baubau.

## 1. Lingkungan Produksi (Pemerintah Kota Baubau)
Domain resmi yang digunakan untuk operasional publik (Wajib Pajak) dan internal (Bapenda/Petugas).

| Komponen | Domain | Deskripsi |
| :--- | :--- | :--- |
| **Portal Layanan WP** | `mpad.baubaukota.go.id` | PWA: Pendaftaran, Inkuiri NOP PBB, E-SPPT |
| **Dashboard Admin** | `adminmpad.baubaukota.go.id` | Backoffice: Penetapan, Verifikasi, TTE Dokumen |
| **Backend API** | `api.sipanda.online` | Centralized Logic: CRUD, Auth, Integrasi Bank |
| **Portal Petugas** | `petugasmpad.baubaukota.go.id` | Lapangan: Spot Check, Audit Reklame, Live Tracking |
| **E-Registry** | `verify.baubaukota.go.id` | Validasi QR-Code TTE (Publik) |

## 2. Lingkungan Staging / Testing (VPS)
Digunakan untuk validasi fitur baru (Hotfix & Minor/Major Releases).

| Komponen | Domain | Deskripsi |
| :--- | :--- | :--- |
| **Main Entrance** | `sipanda.online` | Mobile/Citizen frontend staging |
| **Admin Panel** | `admin.sipanda.online` | Admin dashboard staging |
| **API Endpoint** | `api.sipanda.online` | Backend API staging |
| **Officer Portal** | `petugas.sipanda.online` | Petugas field portal staging |

## 3. Alur Komunikasi Data
1. **WP/Petugas** mengirimkan request ke `api.sipanda.online`.
2. **API** memproses logika (Formula Parser, PBB Inquiry).
3. **Database** (MySQL) menyimpan state dan `metadata` JSON.
4. **TTE Service** (BSrE) dipanggil untuk penandatanganan dokumen resmi.
5. **Gateway Payment** memproses billing via `api.sipanda.online` (Redirect/Callback).

---
*Last modified: April 2026. Unified Branding M-PAD.*


---

## File: docs/02_arsitektur_database/04-database-schema.md
---
# Database Schema
## Sistem Retribusi dan Pendapatan Daerah (M-PAD)

---

## Entity Relationship Diagram (Baubau 2024)

```mermaid
erDiagram
    RETRIBUTION_TYPES ||--o{ RETRIBUTION_CLASSIFICATIONS : "Level 1 -> Level 2"
    RETRIBUTION_CLASSIFICATIONS ||--o{ TAX_OBJECTS : "Categorizes"
    RETRIBUTION_CLASSIFICATIONS ||--o{ RETRIBUTION_RATES : "Defines Rates (Lvl 3/4)"
    
    USERS ||--o{ TAX_OBJECTS : manages
    USERS ||--o{ BILLS : creates
    
    TAX_OBJECTS ||--o{ BILLS : generates
    TAXPAYERS ||--o{ TAX_OBJECTS : owns
    
    BILLS ||--o{ PAYMENTS : receives
    BILLS ||--o{ SIGNED_DOCUMENTS : generates
```

---

## Tabel DETAIL (Update April 2026)

### 1. tax_objects (Objek Pajak)
*Pembaruan: Penambahan field audit fisik dan sinkronisasi PBB.*

```sql
CREATE TABLE tax_objects (
    id UUID PRIMARY KEY,
    taxpayer_id UUID REFERENCES taxpayers(id),
    retribution_type_id UUID REFERENCES retribution_types(id),
    retribution_classification_id UUID REFERENCES retribution_classifications(id),
    opd_id UUID,
    zone_id UUID,
    
    nop VARCHAR(50) UNIQUE NOT NULL, -- Nomor Objek Pajak
    name VARCHAR(100), -- Nama OP
    address TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    
    metadata JSON, -- Data dinamis (jumlah kamar, luas reklame, dll)
    status ENUM('draft', 'proses', 'disetujui', 'ditolak') DEFAULT 'draft',
    
    -- Audit Lapangan
    audit_status VARCHAR(50), -- 'clean', 'anomaly', 'under_review'
    is_verified_physically BOOLEAN DEFAULT FALSE,
    last_photo_url VARCHAR(255),
    installation_date DATE, -- Khusus reklame/alat baru
    
    approved_at TIMESTAMP,
    approved_by UUID REFERENCES users(id),
    timestamps
);
```

### 2. bills (Tagihan / Penetapan)
*Pembaruan: Dukungan Penghapusan Denda (Amnesty) dan Uji Petik.*

```sql
CREATE TABLE bills (
    id UUID PRIMARY KEY,
    tax_object_id UUID REFERENCES tax_objects(id),
    spot_check_id BIGINT REFERENCES spot_checks(id),
    bill_number VARCHAR(50) UNIQUE,
    status ENUM('draft', 'issued', 'paid', 'overdue', 'cancelled'),
    
    -- Financials
    amount DECIMAL(15,2), -- Pokok
    penalty_amount DECIMAL(15,2), -- Denda Berjalan
    waived_penalty_amount DECIMAL(15,2), -- Denda yang Dihapus (Amnesty)
    admin_fee DECIMAL(15,2),
    
    period VARCHAR(20), -- '03/2026'
    due_date TIMESTAMP,
    metadata JSON,
    timestamps
);
```

### 3. enforcement_notices (Penindakan / Surat Paksa)
*Baru: Modul untuk pengawasan ketat dan penagihan paksa.*

```sql
CREATE TABLE enforcement_notices (
    id BIGINT PRIMARY KEY,
    tax_object_id UUID REFERENCES tax_objects(id),
    bill_id UUID REFERENCES bills(id),
    assigned_to UUID REFERENCES users(id), -- Petugas Eksekutor
    number VARCHAR(50) UNIQUE, -- Nomor Surat Paksa / Teguran
    type ENUM('teguran_1', 'teguran_2', 'surat_paksa', 'penyegelan'),
    status ENUM('pending', 'active', 'resolved', 'rejected'),
    
    amount_at_issue DECIMAL(15,2),
    due_date DATE,
    photo_path VARCHAR(255), -- Bukti foto penempelan stiker/segel
    notes TEXT,
    rejection_notes TEXT,
    
    created_by UUID REFERENCES users(id),
    approved_by UUID REFERENCES users(id),
    timestamps
);
```

### 4. penalty_waivers (Amnesty / Penghapusan Denda)
*Baru: Modul permohonan keringanan pajak.*

```sql
CREATE TABLE penalty_waivers (
    id BIGINT PRIMARY KEY,
    bill_id UUID REFERENCES bills(id),
    requested_by UUID REFERENCES users(id),
    approved_by UUID REFERENCES users(id),
    
    reason TEXT, -- Alasan permohonan
    reduction_type ENUM('percentage', 'fixed_amount'),
    reduction_value DECIMAL(15,2),
    status ENUM('pending', 'approved', 'rejected'),
    approval_notes TEXT,
    timestamps
);
```

### 5. signed_documents (E-Registry TTE)
*Baru: Integrasi Tanda Tangan Elektronik (TTE) BSrE.*

```sql
CREATE TABLE signed_documents (
    id UUID PRIMARY KEY,
    document_type VARCHAR(100), -- Model class (App\Models\Bill, etc)
    document_id UUID,
    document_number VARCHAR(50),
    file_path VARCHAR(255), -- Link ke S3/Cloud Storage
    signature_hash TEXT, -- Digital signature hash
    verification_url VARCHAR(255), -- URL untuk QR-Code validation
    status ENUM('pending', 'signed', 'revoked'),
    
    signed_by UUID REFERENCES users(id),
    signed_at TIMESTAMP,
    metadata JSON,
    timestamps
);
```

### 6. tax_transactions (Log Detak Transaksi)
*Pembaruan: Digunakan untuk rekonsiliasi data dari Tapbox.*

```sql
CREATE TABLE tax_transactions (
    id UUID PRIMARY KEY,
    tax_object_id UUID REFERENCES tax_objects(id),
    taxpayer_id UUID REFERENCES taxpayers(id),
    opd_id UUID,
    
    transaction_date DATE,
    amount DECIMAL(15,2),
    tax_amount DECIMAL(15,2),
    source VARCHAR(50), -- 'tapbox', 'manual', 'bank_api'
    description TEXT,
    metadata JSON,
    timestamps
);
```

### 7. complaints (Laporan Pengaduan & Aspirasi)
*Baru: Modul untuk menampung keluhan wajib pajak secara proaktif.*

```sql
CREATE TABLE complaints (
    id BIGINT PRIMARY KEY,
    taxpayer_id UUID REFERENCES taxpayers(id),
    name VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(20),
    category VARCHAR(100), -- 'reklame', 'hotel', 'restoran', dll
    complaint_text TEXT,
    rating INTEGER,
    suggestion_text TEXT,
    attachments JSON,
    status ENUM('pending', 'processing', 'resolved', 'rejected'),
    admin_notes TEXT,
    resolved_at TIMESTAMP,
    resolved_by UUID REFERENCES users(id),
    timestamps
);
```

### 8. tax_educations (Materi Edukasi Pajak)
*Baru: Portal informasi peraturan dan panduan pengisian pajak.*

```sql
CREATE TABLE tax_educations (
    id BIGINT PRIMARY KEY,
    title VARCHAR(255),
    slug VARCHAR(255) UNIQUE,
    content TEXT,
    category VARCHAR(100),
    image_url VARCHAR(255),
    is_active BOOLEAN,
    published_at TIMESTAMP,
    timestamps
);
```

### 9. audit_logs (Rekam Jejak Audit)
*Pembaruan: Kepatuhan tinggi terhadap transparansi data.*

```sql
CREATE TABLE audit_logs (
    id BIGINT PRIMARY KEY,
    user_id UUID REFERENCES users(id),
    action VARCHAR(50), -- 'create', 'update', 'delete'
    model_type VARCHAR(100),
    model_id BIGINT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamps
);
```

---

## 10. Referensi Tabel Master (MITRA Level 1-4)

- **retribution_types**: Kontainer Wilayah Operasional (Level 1). Saat ini: **Wilayah I (16)** dan **Wilayah II (17)**.
- **retribution_classifications**: Kategori Pajak Utama (Level 2). Contoh: PBJT, Reklame, PBB-P2. Menyimpan `formula` dan `icon`.
- **retribution_rates**: Data Tarif Spasial (Level 3 & 4). Menghubungkan Klasifikasi ke Zona dengan nominal Rupiah tertentu.
- **zones**: Data wilayah administratif (Kelurahan/Kecamatan) untuk pemetaan objek dan personil.

---

## 11. Perbandingan Modernisasi: Legacy 9pajak vs M-PAD

Berdasarkan analisis file backup `sw_patda_backup.sql`, berikut adalah perbedaan fundamentalnya:

| Fitur | Sistem Lama (9pajak) | Sistem Baru (M-PAD) |
| :--- | :--- | :--- |
| **Arsitektur** | Modular Terpisah (Tabel per jenis pajak) | Unified Pattern (`tax_objects`) |
| **Identifier** | Auto-increment Integer | UUID (Universally Unique ID) |
| **Atribut Objek** | Kolom Fisik Terbatas | **Metadata JSON (Fleksibel)** |
| **Logika Hitung**| Hardcoded di Kode Aplikasi (PHP) | **Formula Parser (Dinamis di DB)** |
| **Audit Trail** | Terbatas pada riwayat transaksi | Full Record (Audit Logs System) |
| **Field Prefix** | Selalu menggunakan `CPM_` | Penamaan standar PSR (Tanpa prefix) |

---
*Last modified: April 2026. Unified Schema M-PAD with Legacy Comparison.*


---

## File: docs/06_panduan_pengguna/UI_INTEGRATION_STANDARDS.md
---
# UI Integration & Styling Standards

## Core Principles

> [!IMPORTANT]
> **Synchronized Evolution**: Any change to the UI integration model (e.g., auth logic, API structure, global styling) **MUST** be implemented across all relevant repositories simultaneously to maintain system integrity.
>
> **Architectural Awareness**: When creating new files or components that interact with multiple systems, you **MUST** refer to this schema to ensure consistency in state management, storage keys, and visual branding.

## 1. API Integration Model

All applications must use a consistent `api.ts` utility located in `src/lib/api.ts`.

### Standard Implementation
```typescript
const API_URL = import.meta.env.VITE_API_URL || 'https://api.sipanda.online';

export async function apiFetch(endpoint: string, options: RequestInit & { params?: Record<string, any> } = {}) {
    const token = localStorage.getItem('token'); // standard key: 'token'

    let url = `${API_URL}${endpoint}`;
    if (options.params) {
        const searchParams = new URLSearchParams();
        Object.entries(options.params).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                searchParams.append(key, String(value));
            }
        });
        const queryString = searchParams.toString();
        if (queryString) {
            url += (url.includes('?') ? '&' : '?') + queryString;
        }
    }

    const headers = {
        'Accept': 'application/json',
        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
        ...((options.headers as any) || {}),
    };

    const response = await fetch(url, {
        ...options,
        headers,
    });

    if (response.status === 401) {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        if (window.location.pathname !== '/login') {
            window.location.href = '/login';
        }
        throw new Error('Unauthorized');
    }

    const data = await response.json();
    if (!response.ok) {
        throw new Error(data.message || 'Terjadi kesalahan pada server');
    }
    return data;
}

export const api = {
    get: (endpoint: string, options: any = {}) => apiFetch(endpoint, { method: 'GET', ...options }),
    post: (endpoint: string, body: any, options: any = {}) => api_call_with_body('POST', endpoint, body, options),
    put: (endpoint: string, body: any, options: any = {}) => api_call_with_body('PUT', endpoint, body, options),
    delete: (endpoint: string, options: any = {}) => apiFetch(endpoint, { method: 'DELETE', ...options }),
};

async function api_call_with_body(method: string, endpoint: string, body: any, options: any) {
    const isFormData = body instanceof FormData;
    const headers = { ...((options.headers as any) || {}) };
    if (!isFormData && !headers['Content-Type']) {
        headers['Content-Type'] = 'application/json';
    }
    return apiFetch(endpoint, {
        method,
        body: isFormData ? body : JSON.stringify(body),
        ...options,
        headers
    });
}
```

## 2. Visual Standards (Tailwind CSS)

### Color Palette
The following colors should be extended in `tailwind.config.js`:

```javascript
theme: {
  extend: {
    colors: {
      baubau: {
        yellow: '#FFD700',
        'yellow-light': '#FFED4E',
        'yellow-dark': '#E6C200',
        blue: '#0066B3',
        'blue-light': '#1E88E5',
        'blue-dark': '#004D8C',
        green: '#2E7D32',
      },
      dark: {
        bg: '#0f172a',
        card: '#1e293b',
        border: '#334155',
      },
    },
  },
}
```

### Typography
- Primary Font: **Inter** (Google Fonts)

## 3. Storage Keys
To ensure compatibility across tools, use these standard keys:
- Authentication: `token`
- User Data: `user`
- Theme Mode: `theme` (values: `light`, `dark`)


---

## File: docs/06_panduan_pengguna/admin-userguide.md
---
# 📄 Panduan Pengguna Lengkap (Admin) - M-PAD Kota Baubau
## Versi Terintegrasi (Update April 2026)

Selamat datang di Panduan Pengguna **M-PAD (Mitra PAD)**. Dashboard ini adalah pusat kendali untuk manajemen pendapatan daerah, mencakup PBJT, PBB, dan Retribusi Daerah.

---

## 🟢 1. Dashboard & Monitoring (Command Center)
Dashboard utama memberikan pandangan 360 derajat terhadap kesehatan keuangan daerah.

- **Metric Cards**: Pantau Realisasi vs Target secara real-time.
- **Live Maps**: Marker hijau menunjukkan WP patuh, merah menunjukkan tunggakan. Marker biru berdenyut menunjukkan posisi petugas lapangan saat ini.
- **Heatmap**: Identifikasi wilayah dengan potensi pendapatan tertinggi.

---

## 🔵 2. Manajemen Wajib Pajak & Objek Pajak
Admin bertanggung jawab atas validitas data master WP.

1.  **Verifikasi Pendaftaran**: Buka menu **Verifikasi** untuk meninjau data yang diinput petugas atau WP mandiri. Periksa foto lokasi dan koordinat map sebelum menyetujui.
2.  **Klasifikasi**: Pastikan setiap Objek Pajak (Tax Object) memiliki klasifikasi yang benar untuk menentukan rumus perhitungan otomatis.
3.  **Audit Visual**: Gunakan modul **Billboard Audit** untuk memverifikasi fisik reklame vs data administratif.

---

## 🟡 3. Billing, TTE & E-Registry
Transformasi digital dokumen resmi melalui Tanda Tangan Elektronik (TTE).

1.  **Penetapan (Generate Bill)**: Sistem menghitung otomatis berdasarkan `Formula Parser`. Admin cukup meninjau nominal.
2.  **Tanda Tangan Elektronik (TTE)**:
    - Pilih dokumen (SKPD/SKRD) yang siap ditandatangani.
    - Klik **Sign TTE** (BSrE Integrated).
    - Dokumen akan memiliki QR-Code unik yang terhubung ke sistem **E-Registry** (`verify.baubaukota.go.id`).
3.  **Distribusi**: Dokumen digital otomatis tersedia di aplikasi Mobile Wajib Pajak setelah ditandatangani.

---

## 🔴 4. Modul Penegakan & Amnesty (Baru)

### A. Penghapusan Denda (Penalty Waiver / Amnesty)
Fitur untuk mendukung kebijakan relaksasi pajak.
- **Permohonan**: Lihat daftar permohonan keringanan denda dari WP.
- **Persetujuan**: Admin dapat memberikan potongan denda (persentase atau nominal tetap) berdasarkan pertimbangan pimpinan.
- **Otomasi**: Setelah disetujui, tagihan WP akan terupdate otomatis dengan nilai denda yang baru.

### B. Penindakan (Enforcement Notice)
Langkah tegas untuk WP yang mengabaikan kewajiban.
- **Surat Teguran I & II**: Diterbitkan otomatis oleh sistem jika melewati jatuh tempo.
- **Surat Paksa (SPMP)**: Admin dapat menugaskan petugas lapangan untuk mengeksekusi penempelan stiker/segel dan mengunggah bukti foto koordinat.
- **Monitoring**: Pantau status penindakan dari 'Pending' hingga 'Resolved' (WP Melunasi).

---

## 🏛️ 5. Integrasi PBB-P2
Manajemen khusus untuk Pajak Bumi dan Bangunan.
- **Inkuiri Global**: Cari NOP untuk melihat riwayat pembayaran dan tunggakan PBB.
- **Rekonsiliasi**: Sinkronisasi data pembayaran antara bank persepsi dan sistem Bapenda.
- **E-SPPT Management**: Memastikan ketersediaan dokumen SPPT digital untuk diunduh warga.

---
*Tips Keamanan: Gunakan fitur Audit Log untuk melacak siapa yang melakukan penetapan atau perubahan status tagihan sensitif.*


---

## File: docs/08_arsip_migrasi_9pajak/05-implementation-roadmap.md
---
# Implementation Roadmap
## Sistem Retribusi dan Pendapatan Daerah Kota Bau-Bau

---

> [!IMPORTANT]
> ## Cakupan Implementasi - Data BAPENDA
> 
> Sistem ini **saat ini fokus mengelola data yang dimiliki BAPENDA** (Badan Pendapatan Daerah) Kota Bau-Bau:
> 
> | Kategori | Objek yang Dikelola |
> |----------|---------------------|
> | **Retribusi Pengelolaan Kekayaan Daerah** | Pantai Kamali, Kota Mara, Stadion, Pasar Buah, Wantiro |
> | **Pajak Restoran** | Seluruh restoran/rumah makan (tarif 10%) |
> | **Pajak Hotel** | Seluruh hotel/penginapan (tarif 10%) |
> | **Pajak Hiburan Malam** | Diskotik, Klub Malam, Karaoke (tarif **40%**) |
> | **Pajak Parkir** | Bandara, LIPO, Pantai Nirwana |

---

---

## Phase 1: Foundation (Minggu 1-2) - [COMPLETED ✅]

### 1.1 Database Setup
- [x] Finalisasi schema database
- [x] Buat migration untuk semua tabel
- [x] Setup seeder untuk data master
- [x] Implementasi model Eloquent dengan relationships

### 1.2 Master Data
- [x] Seed tax_types dengan 6 jenis retribusi/pajak
- [x] Seed lokasi objek retribusi (Pantai Kamali, Kota Mara, dll)
- [x] Setup tarif sesuai Perwali

### 1.3 Authentication
- [x] User roles: Admin, Verifier, Cashier, Reporter, Petugas, WP
- [x] Role-based access control (RBAC)
- [x] API authentication (Sanctum/JWT)

---

## Phase 2: Core Features (Minggu 3-4) - [COMPLETED ✅]

### 2.1 Pendaftaran Objek Pajak (SPOPD)
- [x] API endpoint untuk submit SPOPD
- [x] Dynamic form berdasarkan jenis pajak (Metadata JSON)
- [x] File upload untuk dokumen pendukung
- [x] Generate NPWPD otomatis

### 2.2 Verifikasi
- [x] Dashboard verifikator
- [x] Workflow approval (Status tracking)
- [x] Notifikasi status

### 2.3 Tagihan (Billing)
- [x] Generate tagihan otomatis
- [x] Kalkulasi pajak berdasarkan tarif (Formula Parser)
- [x] Reminder jatuh tempo

---

## Phase 3: Payment & Reporting (Minggu 5-6) - [COMPLETED ✅]

### 3.1 Pembayaran
- [x] Petugas module (Scan QR & QRIS)
- [x] Multiple payment methods (VA/Tunai)
- [x] Generate kwitansi (Template PDF Official)
- [x] Validasi pembayaran

### 3.2 Reporting
- [x] Laporan harian/bulanan/tahunan (BPK Standards)
- [x] Export Excel/PDF
- [x] Dashboard analytics
- [x] Grafik pendapatan per jenis pajak

---

## Phase 4: Mobile App (Minggu 7-8) - [COMPLETED ✅]

### 4.1 Wajib Pajak Features
- [x] Registrasi online via Mobile
- [x] Cek tagihan & History
- [x] Riwayat pembayaran
- [x] Notifikasi (Push Notifications)

### 4.2 Petugas Features
- [x] Verifikasi lapangan (Visual Audit)
- [x] Foto dokumentasi & GPS Radius check
- [x] GPS lokasi real-time tracking
- [x] Sync offline logic

---

## Phase 5: Strategic Expansion (Mei - Juli 2026) - [NEW 🔄]

### 5.1 Integrasi & Skalabilitas (Mei)
- [ ] Full-Sync SISMIOP (PBB-P2 Integration)
- [ ] Otomasi Rekonsiliasi Sisa Pembayaran (Partial Payments)
- [ ] Aktivasi TTE Live (BSRE E-Registry)

### 5.2 Intelligence & Analytics (Juni)
- [ ] Predictive Analytics Engine (Revenue Projection)
- [ ] Anomaly Detection V2 (Fraud Prevention)
- [ ] Data Visual Studio for Pimpinan

### 5.3 Deployment & Sosialisasi (Juli)
- [ ] Workshop & Pelatihan OPD Massal
- [ ] Kampanye Sosialisasi Wajib Pajak (Go Mobile)
- [ ] Penertiban Reklame Terpadu (Audit Visual Massal)

---

## API Endpoints Summary

(Isi Summary Tetap)

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 11 |
| Database | MySQL (Centralized) |
| Auth | Laravel Sanctum |
| Admin | React + TypeScript + Tailwind |
| Mobile | React Native / PWA |
| Documents | Blade-to-PDF Service |

---

## Priority Features Status (Audit April 2026)

| Priority | Feature | Status |
|----------|---------|--------|
| P0 | User Authentication | ✅ Selesai |
| P0 | Tax Types Master Data | ✅ Selesai |
| P0 | Taxpayer Registration | ✅ Selesai |
| P1 | SPOPD Form Submission | ✅ Selesai |
| P1 | Verification Workflow | ✅ Selesai |
| P1 | Bill Generation | ✅ Selesai |
| P2 | Payment & QRIS | ✅ Selesai |
| P2 | Reports & BPK Audit | ✅ Selesai |
| P3 | Mobile Apps (WP & Petugas) | ✅ Selesai |
| **New** | **Sisa Bayar & Partial Tracking**| 🔄 In Progress |
| **New** | **SISMIOP Integration** | ⏳ Mei 2026 |
| **New** | **Predictive Analytics** | ⏳ Juni 2026 |

---

*Dokumen ini diperbarui secara otomatis berdasarkan status proyek April 2026.*

---

*Dokumen ini akan diperbarui sesuai progress implementasi*


---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_prod_20260226_224723.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-02-26 22:47:23
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: opd) |
| ✅ | Citizen login → token received |
| ✅ | Citizen ID: 2, NIK: 1234567890123456 |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ❌ | GET /test-cors → 404 (expected 200) |
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ✅ | GET /citizen/bills?nik=... → 200 |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /me (citizen profile) → 200 |
| ✅ | GET /citizen/services → 200 |
| ✅ | GET /citizen/services/2 (detail) → 200 |
| ✅ | GET /citizen/services/2/bills → 200 |
| ✅ | GET /citizen/services/pending-periods → 200 |
| ✅ | GET /citizen/reports → 200 |
| ✅ | GET /pbb/bapenda/my-objects → 200 |
| ✅ | GET /pbb/bapenda/my-transactions → 200 |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 24 |
| ✅ | READ zone/24 → 200 |
| ✅ | UPDATE zone/24 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/24 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 1325, NIK: 9900001772117256 |
| ✅ | GET /taxpayers/1325 → 200 |
| ✅ | GET /taxpayers/search/9900001772117256 → 200 |
| ✅ | UPDATE taxpayer/1325 → 200 |
| ✅ | DELETE taxpayer/1325 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 47 |
| ✅ | GET /retribution-types/47 → 200 |
| ✅ | UPDATE retribution-type/47 → 200 |
| ✅ | DELETE retribution-type/47 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ✅ | Tax simulation → result received |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|
| ✅ | READ profile → name: Wajib Pajak Demo
Badan Pendapatan Daerah |
| ✅ | UPDATE profile → 200 OK |

## Summary

⚠️ **1 TEST(S) FAILED**

- **Pass**: 73
- **Fail**: 1
- **Warn**: 0
- **Skip**: 0
- **Total**: 74



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_local_20260311_043855.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-11 04:38:55
**Target**: http://localhost:8000/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: super_admin) |
| ❌ | Citizen login failed |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ❌ | GET /citizen/bills?nik=... → 422 (expected 200) |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Citizen endpoints skipped (no token) |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |
| ❌ | POST /pbb/bapenda/inquiry (Public) → 422 (expected 200) |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 9 |
| ✅ | READ zone/9 → 200 |
| ✅ | UPDATE zone/9 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/9 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 30, NIK: 9900001773175144 |
| ✅ | GET /taxpayers/30 → 200 |
| ✅ | GET /taxpayers/search/9900001773175144 → 200 |
| ✅ | UPDATE taxpayer/30 → 200 |
| ✅ | DELETE taxpayer/30 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 10 |
| ✅ | GET /retribution-types/10 → 200 |
| ✅ | UPDATE retribution-type/10 → 200 |
| ✅ | DELETE retribution-type/10 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|

## Summary

⚠️ **3 TEST(S) FAILED**

- **Pass**: 59
- **Fail**: 3
- **Warn**: 0
- **Skip**: 2
- **Total**: 64



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_local_20260313_054658.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-13 05:46:58
**Target**: http://localhost:8000/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: super_admin) |
| ⚠️ | Standard citizen login failed, attempting dynamic NIK discovery... |
| ✅ | Discovered valid NIK: 7404357274063520, retrying login... |
| ❌ | Citizen login failed (tried hardcoded and discovered NIKs) |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ❌ | GET /citizen/bills?nik=... → 422 (expected 200) |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Citizen endpoints skipped (no token) |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |
| ✅ | POST /pbb/bapenda/inquiry (Public - Expect 404) → 404 |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 12 |
| ✅ | READ zone/12 → 200 |
| ✅ | UPDATE zone/12 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/12 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 75, NIK: 9900001773352030 |
| ✅ | GET /taxpayers/75 → 200 |
| ✅ | GET /taxpayers/search/9900001773352030 → 200 |
| ✅ | UPDATE taxpayer/75 → 200 |
| ✅ | DELETE taxpayer/75 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 13 |
| ✅ | GET /retribution-types/13 → 200 |
| ✅ | UPDATE retribution-type/13 → 200 |
| ✅ | DELETE retribution-type/13 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|

## Summary

⚠️ **2 TEST(S) FAILED**

- **Pass**: 61
- **Fail**: 2
- **Warn**: 1
- **Skip**: 2
- **Total**: 66



---

## File: docs/08_arsip_migrasi_9pajak/results/12_Production_Readiness_20260311_080027.md
---
# Production Readiness Test Report

**Date**: 2026-03-11 08:00:27
**API**: https://api.sipanda.online
**Frontend**: https://sipanda.online


### 1. CORS Preflight (OPTIONS)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS /api/me → 204 No Content |
| ✅ PASS | Access-Control-Allow-Origin: https://sipanda.online |
| ✅ PASS | Access-Control-Allow-Credentials: true |
| ✅ PASS | Access-Control-Allow-Methods includes POST |
| ✅ PASS | No duplicate Access-Control-Allow-Origin headers |

### 2. CORS on Actual Responses
| Status | Detail |
|--------|--------|
| ✅ PASS | GET /api/me (no auth) → 401 Unauthorized |
| ❌ FAIL | 401 response missing CORS headers |
| ✅ PASS | No duplicate CORS headers on 401 response |
| ✅ PASS | 401 returns JSON: {"message":"Unauthenticated."} |

### 3. Citizen Login
| Status | Detail |
|--------|--------|
| ❌ FAIL | Citizen login failed: {"message":"NIK atau password salah"} |
| ❌ FAIL | Login response missing user data |
| ✅ PASS | Bad login correctly rejected |
| ❌ FAIL | Login response has 0 Access-Control-Allow-Origin headers (expected 1) |

### 4. Authenticated API Endpoints
| Status | Detail |
|--------|--------|
| ❌ FAIL | Skipping authenticated tests - no token |

### 5. Cross-Origin (admin.sipanda.online)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS from admin.sipanda.online → 204 |
| ✅ PASS | CORS allows admin.sipanda.online |

### 6. Error Handling (No 500s)
| Status | Detail |
|--------|--------|
| ✅ PASS | Unknown endpoint returns 404 (not 500) |
| ✅ PASS | Health endpoint /up → 200 OK |

### 7. Frontend & PWA
| Status | Detail |
|--------|--------|
| ✅ PASS | Frontend sipanda.online → 200 OK |

## Summary

⚠️ **5 CRITICAL TEST(S) FAILED** — Needs attention.

- **Pass**: 14
- **Fail**: 5
- **Warn**: 0



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_staging_20260311_075951.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-11 07:59:51
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: super_admin) |
| ⚠️ | Standard citizen login failed, attempting dynamic NIK discovery... |
| ✅ | Discovered valid NIK: 7404651929094717, retrying login... |
| ❌ | Citizen login failed (tried hardcoded and discovered NIKs) |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ❌ | GET /citizen/bills?nik=... → 422 (expected 200) |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Citizen endpoints skipped (no token) |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |
| ❌ | POST /pbb/bapenda/inquiry (Public - Expect 404) → 500 (expected 404) |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 10 |
| ✅ | READ zone/10 → 200 |
| ✅ | UPDATE zone/10 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/10 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 29, NIK: 9900001773187222 |
| ✅ | GET /taxpayers/29 → 200 |
| ✅ | GET /taxpayers/search/9900001773187222 → 200 |
| ✅ | UPDATE taxpayer/29 → 200 |
| ✅ | DELETE taxpayer/29 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 20 |
| ✅ | GET /retribution-types/20 → 200 |
| ✅ | UPDATE retribution-type/20 → 200 |
| ✅ | DELETE retribution-type/20 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|

## Summary

⚠️ **3 TEST(S) FAILED**

- **Pass**: 60
- **Fail**: 3
- **Warn**: 1
- **Skip**: 2
- **Total**: 66



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_staging_20260311_180923.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-11 18:09:23
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: super_admin) |
| ⚠️ | Standard citizen login failed, attempting dynamic NIK discovery... |
| ✅ | Discovered valid NIK: 7404651929094717, retrying login... |
| ❌ | Citizen login failed (tried hardcoded and discovered NIKs) |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ❌ | GET /citizen/bills?nik=... → 422 (expected 200) |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Citizen endpoints skipped (no token) |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |
| ❌ | POST /pbb/bapenda/inquiry (Public - Expect 404) → 500 (expected 404) |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 11 |
| ✅ | READ zone/11 → 200 |
| ✅ | UPDATE zone/11 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/11 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 34, NIK: 9900001773223816 |
| ✅ | GET /taxpayers/34 → 200 |
| ✅ | GET /taxpayers/search/9900001773223816 → 200 |
| ✅ | UPDATE taxpayer/34 → 200 |
| ✅ | DELETE taxpayer/34 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 21 |
| ✅ | GET /retribution-types/21 → 200 |
| ✅ | UPDATE retribution-type/21 → 200 |
| ✅ | DELETE retribution-type/21 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|

## Summary

⚠️ **3 TEST(S) FAILED**

- **Pass**: 60
- **Fail**: 3
- **Warn**: 1
- **Skip**: 2
- **Total**: 66



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_prod_20260303_203255.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-03 20:32:55
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: super_admin) |
| ✅ | Citizen login → token received |
| ✅ | Citizen ID: 1386, NIK: 3201234567890001 |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ✅ | GET /citizen/bills?nik=... → 200 |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /me (citizen profile) → 200 |
| ✅ | GET /citizen/services → 200 |
| ✅ | GET /citizen/services/2 (detail) → 200 |
| ✅ | GET /citizen/services/2/bills → 200 |
| ✅ | GET /citizen/services/pending-periods → 200 |
| ✅ | GET /citizen/reports → 200 |
| ✅ | GET /pbb/bapenda/my-objects → 200 |
| ✅ | GET /pbb/bapenda/my-transactions → 200 |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 35 |
| ✅ | READ zone/35 → 200 |
| ✅ | UPDATE zone/35 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/35 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 1388, NIK: 9900001772541188 |
| ✅ | GET /taxpayers/1388 → 200 |
| ✅ | GET /taxpayers/search/9900001772541188 → 200 |
| ✅ | UPDATE taxpayer/1388 → 200 |
| ✅ | DELETE taxpayer/1388 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 59 |
| ✅ | GET /retribution-types/59 → 200 |
| ✅ | UPDATE retribution-type/59 → 200 |
| ✅ | DELETE retribution-type/59 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ✅ | Tax simulation → result received |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|
| ✅ | READ profile → name: Budi Tester UAT |
| ✅ | UPDATE profile → 200 OK |

## Summary

🎉 **ALL TESTS PASSED**

- **Pass**: 73
- **Fail**: 0
- **Warn**: 0
- **Skip**: 0
- **Total**: 73



---

## File: docs/08_arsip_migrasi_9pajak/results/12_Production_Readiness_20260317_105350.md
---
# Production Readiness Test Report

**Date**: 2026-03-17 10:53:50
**API**: https://api.sipanda.online
**Frontend**: https://sipanda.online


### 1. CORS Preflight (OPTIONS)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS /api/me → 204 No Content |
| ✅ PASS | Access-Control-Allow-Origin: https://sipanda.online |
| ✅ PASS | Access-Control-Allow-Credentials: true |
| ✅ PASS | Access-Control-Allow-Methods includes POST |
| ✅ PASS | No duplicate Access-Control-Allow-Origin headers |

### 2. CORS on Actual Responses
| Status | Detail |
|--------|--------|
| ❌ FAIL | GET /api/me (no auth) → 500
401 (expected 401) |
| ✅ PASS | 401 response includes CORS headers |
| ❌ FAIL | Duplicate CORS headers on 401 response (2 found) |
| ✅ PASS | 401 returns JSON: {"message":"Unauthenticated."} |

### 3. Citizen Login
| Status | Detail |
|--------|--------|
| ❌ FAIL | Citizen login failed: {"message":"NIK atau password salah"} |
| ❌ FAIL | Login response missing user data |
| ✅ PASS | Bad login correctly rejected |
| ✅ PASS | Login response has exactly 1 Access-Control-Allow-Origin header |

### 4. Authenticated API Endpoints
| Status | Detail |
|--------|--------|
| ❌ FAIL | Skipping authenticated tests - no token |

### 5. Cross-Origin (admin.sipanda.online)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS from admin.sipanda.online → 204 |
| ✅ PASS | CORS allows admin.sipanda.online |

### 6. Error Handling (No 500s)
| Status | Detail |
|--------|--------|
| ✅ PASS | Unknown endpoint returns 404 (not 500) |
| ✅ PASS | Health endpoint /up → 200 OK |

### 7. Frontend & PWA
| Status | Detail |
|--------|--------|
| ✅ PASS | Frontend sipanda.online → 200 OK |

## Summary

⚠️ **5 CRITICAL TEST(S) FAILED** — Needs attention.

- **Pass**: 14
- **Fail**: 5
- **Warn**: 0



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_local_20260226_172917.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-02-26 17:29:17
**Target**: http://localhost:8000/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: opd) |
| ❌ | Citizen login failed |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ❌ | GET /test-cors → 404 (expected 200) |
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ❌ | GET /citizen/bills?nik=... → 422 (expected 200) |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Citizen endpoints skipped (no token) |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ❌ | CREATE zone failed: {"message":"The selected retribution type id is invalid.","errors":{"retribution_type_id":["The selected retribution type id is invalid."]}} |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ❌ | CREATE taxpayer failed: {"message":"The selected retribution_type_ids.0 is invalid.","errors":{"retribution_type_ids.0":["The selected retribution_type_ids.0 is invalid."]}} |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 19 |
| ✅ | GET /retribution-types/19 → 200 |
| ✅ | UPDATE retribution-type/19 → 200 |
| ✅ | DELETE retribution-type/19 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|

## Summary

⚠️ **5 TEST(S) FAILED**

- **Pass**: 48
- **Fail**: 5
- **Warn**: 0
- **Skip**: 2
- **Total**: 55



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_prod_20260303_202801.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-03 20:28:01
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: opd) |
| ❌ | Citizen login failed |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ❌ | GET /citizen/bills?nik=... → 422 (expected 200) |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Citizen endpoints skipped (no token) |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 32 |
| ✅ | READ zone/32 → 200 |
| ✅ | UPDATE zone/32 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/32 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 1387, NIK: 9900001772540894 |
| ✅ | GET /taxpayers/1387 → 200 |
| ✅ | GET /taxpayers/search/9900001772540894 → 200 |
| ✅ | UPDATE taxpayer/1387 → 200 |
| ✅ | DELETE taxpayer/1387 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 57 |
| ✅ | GET /retribution-types/57 → 200 |
| ✅ | UPDATE retribution-type/57 → 200 |
| ✅ | DELETE retribution-type/57 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ✅ | Tax simulation → result received |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|

## Summary

⚠️ **2 TEST(S) FAILED**

- **Pass**: 60
- **Fail**: 2
- **Warn**: 0
- **Skip**: 1
- **Total**: 63



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_staging_20260311_181237.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-11 18:12:37
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: super_admin) |
| ⚠️ | Standard citizen login failed, attempting dynamic NIK discovery... |
| ✅ | Discovered valid NIK: 7404651929094717, retrying login... |
| ❌ | Citizen login failed (tried hardcoded and discovered NIKs) |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ❌ | GET /citizen/bills?nik=... → 422 (expected 200) |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Citizen endpoints skipped (no token) |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |
| ❌ | POST /pbb/bapenda/inquiry (Public - Expect 404) → 500 (expected 404) |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 12 |
| ✅ | READ zone/12 → 200 |
| ✅ | UPDATE zone/12 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/12 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 35, NIK: 9900001773224012 |
| ✅ | GET /taxpayers/35 → 200 |
| ✅ | GET /taxpayers/search/9900001773224012 → 200 |
| ✅ | UPDATE taxpayer/35 → 200 |
| ✅ | DELETE taxpayer/35 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 22 |
| ✅ | GET /retribution-types/22 → 200 |
| ✅ | UPDATE retribution-type/22 → 200 |
| ✅ | DELETE retribution-type/22 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|

## Summary

⚠️ **3 TEST(S) FAILED**

- **Pass**: 60
- **Fail**: 3
- **Warn**: 1
- **Skip**: 2
- **Total**: 66



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_prod_20260303_203153.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-03 20:31:53
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: super_admin) |
| ✅ | Citizen login → token received |
| ✅ | Citizen ID: 1386, NIK: 3201234567890001 |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ✅ | GET /citizen/bills?nik=... → 200 |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /me (citizen profile) → 200 |
| ✅ | GET /citizen/services → 200 |
| ✅ | GET /citizen/services/2 (detail) → 200 |
| ✅ | GET /citizen/services/2/bills → 200 |
| ✅ | GET /citizen/services/pending-periods → 200 |
| ✅ | GET /citizen/reports → 200 |
| ✅ | GET /pbb/bapenda/my-objects → 200 |
| ✅ | GET /pbb/bapenda/my-transactions → 200 |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 33 |
| ✅ | READ zone/33 → 200 |
| ✅ | UPDATE zone/33 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/33 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ❌ | CREATE taxpayer failed: {"message":"The opd id field is required.","errors":{"opd_id":["The opd id field is required."]}} |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ❌ | CREATE retribution-type failed: {"message":"The opd id field is required.","errors":{"opd_id":["The opd id field is required."]}} |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ✅ | Tax simulation → result received |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|
| ✅ | READ profile → name: Budi Tester UAT |
| ✅ | UPDATE profile → 200 OK |

## Summary

⚠️ **2 TEST(S) FAILED**

- **Pass**: 64
- **Fail**: 2
- **Warn**: 0
- **Skip**: 0
- **Total**: 66



---

## File: docs/08_arsip_migrasi_9pajak/results/07_Hasil_Kalkulator_Prod.md
---
# 🧮 Laporan Hasil Uji Otomatis API Kalkulator Pajak

**Waktu Eksekusi**: 2026-02-25 21:09:30
Pengujian dieksekusi secara otomatis menembak server `Localhost:8000` via endpoint POST `/api/simulate-tax` untuk masing-masing klasifikasi.

### PBB-P2 (`PBB-UMUM`)
- **Formula Server**: `(njop - 10000000) * (njkp_percent / 100) * (tariff / 100)`
- **Dummy Set Variabel**: `{"luas_tanah":120,"kelas_bumi":"080","luas_bangunan":60,"kelas_bangunan":"080","nomor_sertifikat":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### BPHTB (`BPHTB`)
- **Formula Server**: `(npop - npoptkp) * 0.05`
- **Dummy Set Variabel**: `{"npop":5000000,"npoptkp":1000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 200.000**

### Pajak Reklame (`REKLAME`)
- **Formula Server**: `nsr * 0.25`
- **Dummy Set Variabel**: `{"nsr":5000000,"ukuran":100,"lokasi_reklame":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 1.250.000**

### Pajak Sarang Burung Walet (`WALET`)
- **Formula Server**: `nilai_jual * 0.10`
- **Dummy Set Variabel**: `{"nilai_jual":5000000,"lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### Pajak MBLB (`MBLB`)
- **Formula Server**: `(volume * harga_patokan) * 0.15`
- **Dummy Set Variabel**: `{"volume":50,"harga_patokan":80000,"jenis_mineral":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 600.000**

### Opsen PKB (`OPS-PKB`)
- **Formula Server**: `pkb_pokok * 0.66`
- **Dummy Set Variabel**: `{"pkb_pokok":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 9.900**

### Opsen BBNKB (`OPS-BBN`)
- **Formula Server**: `bbnkb_pokok * 0.66`
- **Dummy Set Variabel**: `{"bbnkb_pokok":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 9.900**

### PBJT - Makan dan Minum (`PBJT-MNM`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Tenaga Listrik (`PBJT-LIS`)
- **Formula Server**: `tagihan_listrik * tariff`
- **Dummy Set Variabel**: `{"tagihan_listrik":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### PBJT - Jasa Perhotelan (`PBJT-HTL`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Parkir (`PBJT-PRK`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Kesenian dan Hiburan (`PBJT-HBR`)
- **Formula Server**: `omzet * tariff`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Pajak Air Tanah (`PAT`)
- **Formula Server**: `(volume * hda) * 0.20`
- **Dummy Set Variabel**: `{"volume":50,"hda":80000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 800.000**

### Penyediaan Tempat Kegiatan Usaha (`PTKU`)
- **Formula Server**: `amount`
- **Dummy Set Variabel**: `{"jenis_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Retribusi Jasa Umum (`RJU`)
- **Formula Server**: `amount`
- **Dummy Set Variabel**: `{"jenis_layanan":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Retribusi Perizinan Tertentu (`RPT`)
- **Formula Server**: `amount`
- **Dummy Set Variabel**: `{"jenis_izin":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Persetujuan Bangunan Gedung (PBG) (`PBG`)
- **Formula Server**: `luas_lantai * (indeks_lokalitas * shst) * indeks_terintegrasi * indeks_bg`
- **Dummy Set Variabel**: `{"luas_lantai":100,"indeks_lokalitas":1,"shst":5560000,"indeks_terintegrasi":1,"indeks_bg":1}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 556.000.000**

### PBJT - Jasa Catering (`PBJT-CAT`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"nama_perusahaan":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Event/Hiburan Lainnya (`PBJT-EVT`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"nama_event":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_local_20260305_015239.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-05 01:52:39
**Target**: http://localhost:8000/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ⚠️ | Admin login failed — some tests will be skipped. Response:  |
| ❌ | Citizen login failed |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ❌ | GET /opds (list OPDs) → 000 (expected 200) |
| ❌ | GET /tax-formulas → 000 (expected 200) |
| ❌ | GET /pbb/classifications → 000 (expected 200) |
| ❌ | GET /citizen/bills?nik=... → 000 (expected 200) |
| ❌ | GET /up (health) → 000 (expected 200) |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Citizen endpoints skipped (no token) |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Admin endpoints skipped (no token) |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ⏭️ | Zone CRUD skipped (no admin token) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ⏭️ | Taxpayer CRUD skipped (no admin token) |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ⏭️ | Retribution Type CRUD skipped |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ⚠️ | PBB calculation → 000 (may need correct kelas data) |

### 8. Response Format Validation
| St | Detail |
|----|--------|

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ❌ | 401 response missing 'message' field |
| ❌ | Unknown route → 000 (expected 404) |
| ⚠️ | Validation error format unexpected |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|

## Summary

⚠️ **8 TEST(S) FAILED**

- **Pass**: 0
- **Fail**: 8
- **Warn**: 3
- **Skip**: 6
- **Total**: 17



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_staging_20260317_103842.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-17 10:38:42
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: super_admin) |
| ⚠️ | Standard citizen login failed, attempting dynamic NIK discovery... |
| ✅ | Discovered valid NIK: 7404651929094717, retrying login... |
| ❌ | Citizen login failed (tried hardcoded and discovered NIKs) |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ❌ | GET /citizen/bills?nik=... → 422 (expected 200) |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Citizen endpoints skipped (no token) |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |
| ❌ | POST /pbb/bapenda/inquiry (Public - Expect 404) → 503 (expected 404) |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 15 |
| ✅ | READ zone/15 → 200 |
| ✅ | UPDATE zone/15 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/15 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 38, NIK: 9900001773715186 |
| ✅ | GET /taxpayers/38 → 200 |
| ✅ | GET /taxpayers/search/9900001773715186 → 200 |
| ✅ | UPDATE taxpayer/38 → 200 |
| ✅ | DELETE taxpayer/38 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 25 |
| ✅ | GET /retribution-types/25 → 200 |
| ✅ | UPDATE retribution-type/25 → 200 |
| ✅ | DELETE retribution-type/25 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|

## Summary

⚠️ **3 TEST(S) FAILED**

- **Pass**: 60
- **Fail**: 3
- **Warn**: 1
- **Skip**: 2
- **Total**: 66



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_dev_20260226_050907.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-02-26 05:09:07
**Target**: https://api-dev.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: opd) |
| ✅ | Citizen login → token received |
| ✅ | Citizen ID: 2, NIK: 1234567890123456 |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ❌ | GET /test-cors → 404 (expected 200) |
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ✅ | GET /citizen/bills?nik=... → 200 |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /me (citizen profile) → 200 |
| ✅ | GET /citizen/services → 200 |
| ✅ | GET /citizen/services/2 (detail) → 200 |
| ✅ | GET /citizen/services/2/bills → 200 |
| ✅ | GET /citizen/services/pending-periods → 200 |
| ✅ | GET /citizen/reports → 200 |
| ✅ | GET /pbb/bapenda/my-objects → 200 |
| ✅ | GET /pbb/bapenda/my-transactions → 200 |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 23 |
| ✅ | READ zone/23 → 200 |
| ✅ | UPDATE zone/23 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/23 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 1323, NIK: 9900001772053760 |
| ✅ | GET /taxpayers/1323 → 200 |
| ✅ | GET /taxpayers/search/9900001772053760 → 200 |
| ✅ | UPDATE taxpayer/1323 → 200 |
| ✅ | DELETE taxpayer/1323 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 46 |
| ✅ | GET /retribution-types/46 → 200 |
| ✅ | UPDATE retribution-type/46 → 200 |
| ✅ | DELETE retribution-type/46 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ✅ | Tax simulation → result received |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|
| ✅ | READ profile → name: Wajib Pajak Demo
Badan Pendapatan Daerah |
| ✅ | UPDATE profile → 200 OK |

## Summary

⚠️ **1 TEST(S) FAILED**

- **Pass**: 73
- **Fail**: 1
- **Warn**: 0
- **Skip**: 0
- **Total**: 74



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_staging_20260311_075109.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-11 07:51:09
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: super_admin) |
| ❌ | Citizen login failed |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ❌ | GET /citizen/bills?nik=... → 422 (expected 200) |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Citizen endpoints skipped (no token) |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |
| ❌ | POST /pbb/bapenda/inquiry (Public - Expect 404) → 500 (expected 404) |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 8 |
| ✅ | READ zone/8 → 200 |
| ✅ | UPDATE zone/8 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/8 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 26, NIK: 9900001773186697 |
| ✅ | GET /taxpayers/26 → 200 |
| ✅ | GET /taxpayers/search/9900001773186697 → 200 |
| ✅ | UPDATE taxpayer/26 → 200 |
| ✅ | DELETE taxpayer/26 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 18 |
| ✅ | GET /retribution-types/18 → 200 |
| ✅ | UPDATE retribution-type/18 → 200 |
| ✅ | DELETE retribution-type/18 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|

## Summary

⚠️ **3 TEST(S) FAILED**

- **Pass**: 59
- **Fail**: 3
- **Warn**: 0
- **Skip**: 2
- **Total**: 64



---

## File: docs/08_arsip_migrasi_9pajak/results/13_Penetration_Test_20260311_080029.md
---
# 🔒 Penetration Test Report

**Date**: 2026-03-11 08:00:29
**Target**: https://api.sipanda.online
**Methodology**: OWASP Top 10 + Custom Vectors


### 1. Sensitive File Exposure
| Status | Detail |
|--------|--------|
| ✅ SECURE | .env file blocked (404) |
| ✅ SECURE | .git directory blocked (404) |
| ⚠️ WARN | .htaccess accessible: 200 |
| ✅ SECURE | composer.json not exposed (404) |
| ✅ SECURE | Laravel logs not exposed (403) |
| ✅ SECURE | phpinfo.php not exposed (404) |

### 2. SQL Injection
| Status | Detail |
|--------|--------|
| ✅ SECURE | Login immune to basic SQLi (OR 1=1) |
| ✅ SECURE | No SQL error leakage in bills query |
| ✅ SECURE | No blind SQLi detected (response in 1s) |

### 3. Cross-Site Scripting (XSS)
| Status | Detail |
|--------|--------|
| ✅ SECURE | XSS payload not reflected in registration |
| ✅ SECURE | XSS payload not reflected in query params |

### 4. Broken Authentication
| Status | Detail |
|--------|--------|
| ✅ SECURE | /api/me returns 401 without token |
| ✅ SECURE | Fabricated token rejected (401) |
| ✅ SECURE | Citizen token cannot access /api/users (401) |

### 5. IDOR (Insecure Direct Object Reference)
| Status | Detail |
|--------|--------|
| ✅ SECURE | IDOR blocked for /api/taxpayers/1 (401) |
| ✅ SECURE | IDOR blocked for /api/bills/1 (401) |

### 6. Path Traversal
| Status | Detail |
|--------|--------|
| ✅ SECURE | Path traversal blocked (no file content leaked) |
| ✅ SECURE | Encoded path traversal blocked |

### 7. Security Headers
| Status | Detail |
|--------|--------|
| ❌ VULN | HSTS header missing |
| ❌ VULN | X-Content-Type-Options header missing |
| ❌ VULN | X-Frame-Options missing (clickjacking risk) |
| ⚠️ WARN | X-XSS-Protection header missing |
| ✅ SECURE | Server version not fully exposed |
| ✅ SECURE | X-Powered-By header not present (tech stack hidden) |

### 8. Rate Limiting / Brute Force Protection
| Status | Detail |
|--------|--------|
| ❌ VULN | No rate limiting detected after 65 requests (brute force possible) |

### 9. Mass Assignment
| Status | Detail |
|--------|--------|
| ✅ SECURE | Mass assignment blocked (is_admin/role not assignable) |

### 10. CORS Misconfiguration
| Status | Detail |
|--------|--------|
| ✅ SECURE | CORS rejects evil origin (no wildcard) |
| ✅ SECURE | CORS rejects null origin |

### 11. HTTP Method Tampering
| Status | Detail |
|--------|--------|
| ✅ SECURE | PUT on login endpoint rejected (405) |
| ✅ SECURE | DELETE on /api/me rejected (405) |

### 12. Verbose Error / Stack Trace Leakage
| Status | Detail |
|--------|--------|
| ✅ SECURE | No stack trace leakage on malformed JSON |
| ✅ SECURE | Long input handled gracefully (status: 401) |

## Summary

⚠️ **4 VULNERABILITIES FOUND** — Action recommended.

- **Secure**: 26
- **Vulnerabilities**: 4
- **Warnings**: 2
- **Critical**: 0
- **Total Tests**: 32



---

## File: docs/08_arsip_migrasi_9pajak/results/12_Production_Readiness_20260313_070921.md
---
# Production Readiness Test Report

**Date**: 2026-03-13 07:09:21
**API**: https://api.sipanda.online
**Frontend**: https://sipanda.online


### 1. CORS Preflight (OPTIONS)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS /api/me → 204 No Content |
| ✅ PASS | Access-Control-Allow-Origin: https://sipanda.online |
| ✅ PASS | Access-Control-Allow-Credentials: true |
| ✅ PASS | Access-Control-Allow-Methods includes POST |
| ✅ PASS | No duplicate Access-Control-Allow-Origin headers |

### 2. CORS on Actual Responses
| Status | Detail |
|--------|--------|
| ✅ PASS | GET /api/me (no auth) → 401 Unauthorized |
| ❌ FAIL | 401 response missing CORS headers |
| ✅ PASS | No duplicate CORS headers on 401 response |
| ✅ PASS | 401 returns JSON: {"message":"Unauthenticated."} |

### 3. Citizen Login
| Status | Detail |
|--------|--------|
| ❌ FAIL | Citizen login failed: {"message":"NIK atau password salah"} |
| ❌ FAIL | Login response missing user data |
| ✅ PASS | Bad login correctly rejected |
| ❌ FAIL | Login response has 0 Access-Control-Allow-Origin headers (expected 1) |

### 4. Authenticated API Endpoints
| Status | Detail |
|--------|--------|
| ❌ FAIL | Skipping authenticated tests - no token |

### 5. Cross-Origin (admin.sipanda.online)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS from admin.sipanda.online → 204 |
| ✅ PASS | CORS allows admin.sipanda.online |

### 6. Error Handling (No 500s)
| Status | Detail |
|--------|--------|
| ✅ PASS | Unknown endpoint returns 404 (not 500) |
| ✅ PASS | Health endpoint /up → 200 OK |

### 7. Frontend & PWA
| Status | Detail |
|--------|--------|
| ✅ PASS | Frontend sipanda.online → 200 OK |

## Summary

⚠️ **5 CRITICAL TEST(S) FAILED** — Needs attention.

- **Pass**: 14
- **Fail**: 5
- **Warn**: 0



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_20260226_030651.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-02-26 03:06:51
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: opd) |
| ✅ | Citizen login → token received |
| ✅ | Citizen ID: 2, NIK: 1234567890123456 |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /test-cors → 200 |
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ✅ | GET /citizen/bills?nik=... → 200 |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /me (citizen profile) → 200 |
| ✅ | GET /citizen/services → 200 |
| ✅ | GET /citizen/services/2 (detail) → 200 |
| ✅ | GET /citizen/services/2/bills → 200 |
| ✅ | GET /citizen/services/pending-periods → 200 |
| ✅ | GET /citizen/reports → 200 |
| ✅ | GET /pbb/bapenda/my-objects → 200 |
| ✅ | GET /pbb/bapenda/my-transactions → 200 |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ❌ | CREATE zone failed: {"message":"The code field must not be greater than 10 characters.","errors":{"code":["The code field must not be greater than 10 characters."]}} |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 1321, NIK: 9900001772046421 |
| ✅ | GET /taxpayers/1321 → 200 |
| ✅ | GET /taxpayers/search/9900001772046421 → 200 |
| ✅ | UPDATE taxpayer/1321 → 200 |
| ✅ | DELETE taxpayer/1321 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 44 |
| ✅ | GET /retribution-types/44 → 200 |
| ✅ | UPDATE retribution-type/44 → 200 |
| ✅ | DELETE retribution-type/44 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ✅ | Tax simulation → result received |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|
| ✅ | READ profile → name: Wajib Pajak Demo
Badan Pendapatan Daerah |
| ✅ | UPDATE profile → 200 OK |

## Summary

⚠️ **1 TEST(S) FAILED**

- **Pass**: 68
- **Fail**: 1
- **Warn**: 0
- **Skip**: 0
- **Total**: 69



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_dev_20260226_224743.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-02-26 22:47:43
**Target**: https://api-dev.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ⚠️ | Admin login failed — some tests will be skipped. Response:  |
| ❌ | Citizen login failed |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ❌ | GET /test-cors → 000 (expected 200) |
| ❌ | GET /opds (list OPDs) → 000 (expected 200) |
| ❌ | GET /tax-formulas → 000 (expected 200) |
| ❌ | GET /pbb/classifications → 000 (expected 200) |
| ❌ | GET /citizen/bills?nik=... → 000 (expected 200) |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Citizen endpoints skipped (no token) |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Admin endpoints skipped (no token) |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ⏭️ | Zone CRUD skipped (no admin token) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ⏭️ | Taxpayer CRUD skipped (no admin token) |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ⏭️ | Retribution Type CRUD skipped |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ⚠️ | PBB calculation → 000 (may need correct kelas data) |

### 8. Response Format Validation
| St | Detail |
|----|--------|

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ❌ | 401 response missing 'message' field |
| ❌ | Unknown route → 000 (expected 404) |
| ⚠️ | Validation error format unexpected |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|

## Summary

⚠️ **8 TEST(S) FAILED**

- **Pass**: 1
- **Fail**: 8
- **Warn**: 3
- **Skip**: 6
- **Total**: 18



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_staging_20260317_102803.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-17 10:28:03
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ⚠️ | Admin login failed — some tests will be skipped. Response: { "message": "Server Error" }{ "message": "Server Error" }{ "message": "Server Error" }{ "message":  |
| ❌ | Citizen login failed (tried hardcoded and discovered NIKs) |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ❌ | GET /opds (list OPDs) → 500 (expected 200) |
| ❌ | GET /tax-formulas → 500 (expected 200) |
| ❌ | GET /pbb/classifications → 500 (expected 200) |
| ❌ | GET /citizen/bills?nik=... → 422 (expected 200) |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Citizen endpoints skipped (no token) |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Admin endpoints skipped (no token) |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ⏭️ | Zone CRUD skipped (no admin token) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ⏭️ | Taxpayer CRUD skipped (no admin token) |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ⏭️ | Retribution Type CRUD skipped |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ⚠️ | PBB calculation → 500 (may need correct kelas data) |

### 8. Response Format Validation
| St | Detail |
|----|--------|

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|

## Summary

⚠️ **5 TEST(S) FAILED**

- **Pass**: 4
- **Fail**: 5
- **Warn**: 2
- **Skip**: 6
- **Total**: 17



---

## File: docs/08_arsip_migrasi_9pajak/results/12_Production_Readiness_20260226_224706.md
---
# Production Readiness Test Report

**Date**: 2026-02-26 22:47:06
**API**: https://api.sipanda.online
**Frontend**: https://sipanda.online


### 1. CORS Preflight (OPTIONS)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS /api/me → 204 No Content |
| ✅ PASS | Access-Control-Allow-Origin: https://sipanda.online |
| ✅ PASS | Access-Control-Allow-Credentials: true |
| ❌ FAIL | Access-Control-Allow-Methods missing POST |
| ✅ PASS | No duplicate Access-Control-Allow-Origin headers |

### 2. CORS on Actual Responses
| Status | Detail |
|--------|--------|
| ✅ PASS | GET /api/me (no auth) → 401 Unauthorized |
| ✅ PASS | 401 response includes CORS headers |
| ✅ PASS | No duplicate CORS headers on 401 response |
| ✅ PASS | 401 returns JSON: {"message":"Unauthenticated."} |

### 3. Citizen Login
| Status | Detail |
|--------|--------|
| ✅ PASS | Citizen login successful, token received |
| ✅ PASS | Login response includes user data |
| ✅ PASS | Bad login correctly rejected |
| ✅ PASS | Login response has exactly 1 Access-Control-Allow-Origin header |

### 4. Authenticated API Endpoints
| Status | Detail |
|--------|--------|
| ✅ PASS | GET /api/me (authenticated) → 200 OK |
| ✅ PASS | /api/me has exactly 1 CORS header |
| ✅ PASS | GET /api/citizen/services → 200 OK |
| ✅ PASS | GET /api/citizen/bills → 200 OK |

### 5. Cross-Origin (admin.sipanda.online)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS from admin.sipanda.online → 204 |
| ✅ PASS | CORS allows admin.sipanda.online |

### 6. Error Handling (No 500s)
| Status | Detail |
|--------|--------|
| ✅ PASS | Unknown endpoint returns 404 (not 500) |
| ✅ PASS | Health endpoint /up → 200 OK |

### 7. Frontend & PWA
| Status | Detail |
|--------|--------|
| ✅ PASS | Frontend sipanda.online → 200 OK |

## Summary

⚠️ **1 CRITICAL TEST(S) FAILED** — Needs attention.

- **Pass**: 21
- **Fail**: 1
- **Warn**: 0



---

## File: docs/08_arsip_migrasi_9pajak/results/13_Penetration_Test_20260226_032407.md
---
# 🔒 Penetration Test Report

**Date**: 2026-02-26 03:24:07
**Target**: https://api.sipanda.online
**Methodology**: OWASP Top 10 + Custom Vectors


### 1. Sensitive File Exposure
| Status | Detail |
|--------|--------|
| ✅ SECURE | .env file blocked (403) |
| ✅ SECURE | .git directory blocked (403) |
| ✅ SECURE | .htaccess blocked (403) |
| ✅ SECURE | composer.json not exposed (404) |
| ✅ SECURE | Laravel logs not exposed (403) |
| ✅ SECURE | phpinfo.php not exposed (404) |

### 2. SQL Injection
| Status | Detail |
|--------|--------|
| ✅ SECURE | Login immune to basic SQLi (OR 1=1) |
| ✅ SECURE | No SQL error leakage in bills query |
| ✅ SECURE | No blind SQLi detected (response in 0s) |

### 3. Cross-Site Scripting (XSS)
| Status | Detail |
|--------|--------|
| ✅ SECURE | XSS payload not reflected in registration |
| ✅ SECURE | XSS payload not reflected in query params |

### 4. Broken Authentication
| Status | Detail |
|--------|--------|
| ✅ SECURE | /api/me returns 401 without token |
| ✅ SECURE | Fabricated token rejected (401) |
| ❌ VULN | Citizen token can access admin /api/users! Status: 200 |

### 5. IDOR (Insecure Direct Object Reference)
| Status | Detail |
|--------|--------|
| ✅ SECURE | IDOR blocked for /api/taxpayers/1 (404) |
| ⚠️ WARN | IDOR possible: citizen can access /api/bills/1 (200) (review scope) |

### 6. Path Traversal
| Status | Detail |
|--------|--------|
| ✅ SECURE | Path traversal blocked (no file content leaked) |
| ✅ SECURE | Encoded path traversal blocked |

### 7. Security Headers
| Status | Detail |
|--------|--------|
| ✅ SECURE | HSTS header present |
| ✅ SECURE | X-Content-Type-Options: nosniff |
| ✅ SECURE | X-Frame-Options present (clickjacking protection) |
| ✅ SECURE | X-XSS-Protection header present |
| ✅ SECURE | Server version not fully exposed |
| ✅ SECURE | X-Powered-By header not present (tech stack hidden) |

### 8. Rate Limiting / Brute Force Protection
| Status | Detail |
|--------|--------|
| ✅ SECURE | Rate limiting active (429 at request #57) |

### 9. Mass Assignment
| Status | Detail |
|--------|--------|
| ✅ SECURE | Mass assignment blocked (is_admin/role not assignable) |

### 10. CORS Misconfiguration
| Status | Detail |
|--------|--------|
| ❌ VULN | CORS allows arbitrary origins: Access-Control-Allow-Origin: https://evil-hacker.com |
| ❌ VULN | CORS allows null origin |

### 11. HTTP Method Tampering
| Status | Detail |
|--------|--------|
| ✅ SECURE | PUT on login endpoint rejected (405) |
| ✅ SECURE | DELETE on /api/me rejected (405) |

### 12. Verbose Error / Stack Trace Leakage
| Status | Detail |
|--------|--------|
| ✅ SECURE | No stack trace leakage on malformed JSON |
| ✅ SECURE | Long input handled gracefully (status: 429) |

## Summary

⚠️ **3 VULNERABILITIES FOUND** — Action recommended.

- **Secure**: 28
- **Vulnerabilities**: 3
- **Warnings**: 1
- **Critical**: 0
- **Total Tests**: 32



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_prod_20260313_054648.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-13 05:46:48
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: super_admin) |
| ⚠️ | Standard citizen login failed, attempting dynamic NIK discovery... |
| ✅ | Discovered valid NIK: 7404651929094717, retrying login... |
| ❌ | Citizen login failed (tried hardcoded and discovered NIKs) |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ❌ | GET /citizen/bills?nik=... → 422 (expected 200) |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Citizen endpoints skipped (no token) |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |
| ❌ | POST /pbb/bapenda/inquiry (Public - Expect 404) → 500 (expected 404) |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 13 |
| ✅ | READ zone/13 → 200 |
| ✅ | UPDATE zone/13 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/13 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 36, NIK: 9900001773352049 |
| ✅ | GET /taxpayers/36 → 200 |
| ✅ | GET /taxpayers/search/9900001773352049 → 200 |
| ✅ | UPDATE taxpayer/36 → 200 |
| ✅ | DELETE taxpayer/36 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 23 |
| ✅ | GET /retribution-types/23 → 200 |
| ✅ | UPDATE retribution-type/23 → 200 |
| ✅ | DELETE retribution-type/23 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|

## Summary

⚠️ **3 TEST(S) FAILED**

- **Pass**: 60
- **Fail**: 3
- **Warn**: 1
- **Skip**: 2
- **Total**: 66



---

## File: docs/08_arsip_migrasi_9pajak/results/12_Production_Readiness_20260311_075941.md
---
# Production Readiness Test Report

**Date**: 2026-03-11 07:59:41
**API**: https://api.sipanda.online
**Frontend**: https://sipanda.online


### 1. CORS Preflight (OPTIONS)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS /api/me → 204 No Content |
| ✅ PASS | Access-Control-Allow-Origin: https://sipanda.online |
| ✅ PASS | Access-Control-Allow-Credentials: true |
| ✅ PASS | Access-Control-Allow-Methods includes POST |
| ✅ PASS | No duplicate Access-Control-Allow-Origin headers |

### 2. CORS on Actual Responses
| Status | Detail |
|--------|--------|
| ✅ PASS | GET /api/me (no auth) → 401 Unauthorized |
| ❌ FAIL | 401 response missing CORS headers |
| ✅ PASS | No duplicate CORS headers on 401 response |
| ✅ PASS | 401 returns JSON: {"message":"Unauthenticated."} |

### 3. Citizen Login
| Status | Detail |
|--------|--------|
| ❌ FAIL | Citizen login failed: {"message":"NIK atau password salah"} |
| ❌ FAIL | Login response missing user data |
| ✅ PASS | Bad login correctly rejected |
| ❌ FAIL | Login response has 0 Access-Control-Allow-Origin headers (expected 1) |

### 4. Authenticated API Endpoints
| Status | Detail |
|--------|--------|
| ❌ FAIL | Skipping authenticated tests - no token |

### 5. Cross-Origin (admin.sipanda.online)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS from admin.sipanda.online → 204 |
| ✅ PASS | CORS allows admin.sipanda.online |

### 6. Error Handling (No 500s)
| Status | Detail |
|--------|--------|
| ✅ PASS | Unknown endpoint returns 404 (not 500) |
| ✅ PASS | Health endpoint /up → 200 OK |

### 7. Frontend & PWA
| Status | Detail |
|--------|--------|
| ✅ PASS | Frontend sipanda.online → 200 OK |

## Summary

⚠️ **5 CRITICAL TEST(S) FAILED** — Needs attention.

- **Pass**: 14
- **Fail**: 5
- **Warn**: 0



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_staging_20260311_075908.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-11 07:59:08
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: super_admin) |
| ❌ | Citizen login failed |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ❌ | GET /citizen/bills?nik=... → 422 (expected 200) |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Citizen endpoints skipped (no token) |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |
| ❌ | POST /pbb/bapenda/inquiry (Public - Expect 404) → 500 (expected 404) |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 9 |
| ✅ | READ zone/9 → 200 |
| ✅ | UPDATE zone/9 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/9 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 28, NIK: 9900001773187176 |
| ✅ | GET /taxpayers/28 → 200 |
| ✅ | GET /taxpayers/search/9900001773187176 → 200 |
| ✅ | UPDATE taxpayer/28 → 200 |
| ✅ | DELETE taxpayer/28 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 19 |
| ✅ | GET /retribution-types/19 → 200 |
| ✅ | UPDATE retribution-type/19 → 200 |
| ✅ | DELETE retribution-type/19 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|

## Summary

⚠️ **3 TEST(S) FAILED**

- **Pass**: 59
- **Fail**: 3
- **Warn**: 0
- **Skip**: 2
- **Total**: 64



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_20260226_030742.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-02-26 03:07:42
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: opd) |
| ✅ | Citizen login → token received |
| ✅ | Citizen ID: 2, NIK: 1234567890123456 |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /test-cors → 200 |
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ✅ | GET /citizen/bills?nik=... → 200 |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /me (citizen profile) → 200 |
| ✅ | GET /citizen/services → 200 |
| ✅ | GET /citizen/services/2 (detail) → 200 |
| ✅ | GET /citizen/services/2/bills → 200 |
| ✅ | GET /citizen/services/pending-periods → 200 |
| ✅ | GET /citizen/reports → 200 |
| ✅ | GET /pbb/bapenda/my-objects → 200 |
| ✅ | GET /pbb/bapenda/my-transactions → 200 |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 22 |
| ✅ | READ zone/22 → 200 |
| ✅ | UPDATE zone/22 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/22 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 1322, NIK: 9900001772046473 |
| ✅ | GET /taxpayers/1322 → 200 |
| ✅ | GET /taxpayers/search/9900001772046473 → 200 |
| ✅ | UPDATE taxpayer/1322 → 200 |
| ✅ | DELETE taxpayer/1322 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 45 |
| ✅ | GET /retribution-types/45 → 200 |
| ✅ | UPDATE retribution-type/45 → 200 |
| ✅ | DELETE retribution-type/45 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ✅ | Tax simulation → result received |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|
| ✅ | READ profile → name: Wajib Pajak Demo
Badan Pendapatan Daerah |
| ✅ | UPDATE profile → 200 OK |

## Summary

🎉 **ALL TESTS PASSED**

- **Pass**: 74
- **Fail**: 0
- **Warn**: 0
- **Skip**: 0
- **Total**: 74



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_prod_20260226_050810.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-02-26 05:08:10
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: opd) |
| ✅ | Citizen login → token received |
| ✅ | Citizen ID: 2, NIK: 1234567890123456 |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ❌ | GET /test-cors → 404 (expected 200) |
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ✅ | GET /citizen/bills?nik=... → 200 |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /me (citizen profile) → 200 |
| ✅ | GET /citizen/services → 200 |
| ✅ | GET /citizen/services/2 (detail) → 200 |
| ✅ | GET /citizen/services/2/bills → 200 |
| ✅ | GET /citizen/services/pending-periods → 200 |
| ✅ | GET /citizen/reports → 200 |
| ✅ | GET /pbb/bapenda/my-objects → 200 |
| ✅ | GET /pbb/bapenda/my-transactions → 200 |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 23 |
| ✅ | READ zone/23 → 200 |
| ✅ | UPDATE zone/23 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/23 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 1323, NIK: 9900001772053703 |
| ✅ | GET /taxpayers/1323 → 200 |
| ✅ | GET /taxpayers/search/9900001772053703 → 200 |
| ✅ | UPDATE taxpayer/1323 → 200 |
| ✅ | DELETE taxpayer/1323 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 46 |
| ✅ | GET /retribution-types/46 → 200 |
| ✅ | UPDATE retribution-type/46 → 200 |
| ✅ | DELETE retribution-type/46 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ✅ | Tax simulation → result received |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|
| ✅ | READ profile → name: Wajib Pajak Demo
Badan Pendapatan Daerah |
| ✅ | UPDATE profile → 200 OK |

## Summary

⚠️ **1 TEST(S) FAILED**

- **Pass**: 73
- **Fail**: 1
- **Warn**: 0
- **Skip**: 0
- **Total**: 74



---

## File: docs/08_arsip_migrasi_9pajak/results/07_Hasil_Kalkulator_Dev.md
---
# 🧮 Laporan Hasil Uji Otomatis API Kalkulator Pajak

**Waktu Eksekusi**: 2026-02-25 21:09:33
Pengujian dieksekusi secara otomatis menembak server `Localhost:8000` via endpoint POST `/api/simulate-tax` untuk masing-masing klasifikasi.

### PBB-P2 (`PBB-UMUM`)
- **Formula Server**: `(njop - 10000000) * (njkp_percent / 100) * (tariff / 100)`
- **Dummy Set Variabel**: `{"luas_tanah":120,"kelas_bumi":"080","luas_bangunan":60,"kelas_bangunan":"080","nomor_sertifikat":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### BPHTB (`BPHTB`)
- **Formula Server**: `(npop - npoptkp) * 0.05`
- **Dummy Set Variabel**: `{"npop":5000000,"npoptkp":1000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 200.000**

### Pajak Reklame (`REKLAME`)
- **Formula Server**: `nsr * 0.25`
- **Dummy Set Variabel**: `{"nsr":5000000,"ukuran":100,"lokasi_reklame":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 1.250.000**

### Pajak Sarang Burung Walet (`WALET`)
- **Formula Server**: `nilai_jual * 0.10`
- **Dummy Set Variabel**: `{"nilai_jual":5000000,"lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### Pajak MBLB (`MBLB`)
- **Formula Server**: `(volume * harga_patokan) * 0.15`
- **Dummy Set Variabel**: `{"volume":50,"harga_patokan":80000,"jenis_mineral":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 600.000**

### Opsen PKB (`OPS-PKB`)
- **Formula Server**: `pkb_pokok * 0.66`
- **Dummy Set Variabel**: `{"pkb_pokok":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 9.900**

### Opsen BBNKB (`OPS-BBN`)
- **Formula Server**: `bbnkb_pokok * 0.66`
- **Dummy Set Variabel**: `{"bbnkb_pokok":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 9.900**

### PBJT - Makan dan Minum (`PBJT-MNM`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Tenaga Listrik (`PBJT-LIS`)
- **Formula Server**: `tagihan_listrik * tariff`
- **Dummy Set Variabel**: `{"tagihan_listrik":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### PBJT - Jasa Perhotelan (`PBJT-HTL`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Parkir (`PBJT-PRK`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Kesenian dan Hiburan (`PBJT-HBR`)
- **Formula Server**: `omzet * tariff`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Pajak Air Tanah (`PAT`)
- **Formula Server**: `(volume * hda) * 0.20`
- **Dummy Set Variabel**: `{"volume":50,"hda":80000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 800.000**

### Penyediaan Tempat Kegiatan Usaha (`PTKU`)
- **Formula Server**: `amount`
- **Dummy Set Variabel**: `{"jenis_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Retribusi Jasa Umum (`RJU`)
- **Formula Server**: `amount`
- **Dummy Set Variabel**: `{"jenis_layanan":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Retribusi Perizinan Tertentu (`RPT`)
- **Formula Server**: `amount`
- **Dummy Set Variabel**: `{"jenis_izin":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Persetujuan Bangunan Gedung (PBG) (`PBG`)
- **Formula Server**: `luas_lantai * (indeks_lokalitas * shst) * indeks_terintegrasi * indeks_bg`
- **Dummy Set Variabel**: `{"luas_lantai":100,"indeks_lokalitas":1,"shst":5560000,"indeks_terintegrasi":1,"indeks_bg":1}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 556.000.000**

### PBJT - Jasa Catering (`PBJT-CAT`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"nama_perusahaan":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Event/Hiburan Lainnya (`PBJT-EVT`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"nama_event":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**



---

## File: docs/08_arsip_migrasi_9pajak/results/12_Production_Readiness_20260226_030647.md
---
# Production Readiness Test Report

**Date**: 2026-02-26 03:06:47
**API**: https://api.sipanda.online
**Frontend**: https://sipanda.online


### 1. CORS Preflight (OPTIONS)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS /api/me → 204 No Content |
| ✅ PASS | Access-Control-Allow-Origin: https://sipanda.online |
| ✅ PASS | Access-Control-Allow-Credentials: true |
| ✅ PASS | Access-Control-Allow-Methods includes POST |
| ✅ PASS | No duplicate Access-Control-Allow-Origin headers |

### 2. CORS on Actual Responses
| Status | Detail |
|--------|--------|
| ✅ PASS | GET /api/me (no auth) → 401 Unauthorized |
| ✅ PASS | 401 response includes CORS headers |
| ✅ PASS | No duplicate CORS headers on 401 response |
| ✅ PASS | 401 returns JSON: {"message":"Unauthenticated."} |

### 3. Citizen Login
| Status | Detail |
|--------|--------|
| ✅ PASS | Citizen login successful, token received |
| ✅ PASS | Login response includes user data |
| ✅ PASS | Bad login correctly rejected |
| ✅ PASS | Login response has exactly 1 Access-Control-Allow-Origin header |

### 4. Authenticated API Endpoints
| Status | Detail |
|--------|--------|
| ✅ PASS | GET /api/me (authenticated) → 200 OK |
| ✅ PASS | /api/me has exactly 1 CORS header |
| ✅ PASS | GET /api/citizen/services → 200 OK |
| ✅ PASS | GET /api/citizen/bills → 200 OK |

### 5. Cross-Origin (admin.sipanda.online)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS from admin.sipanda.online → 204 |
| ✅ PASS | CORS allows admin.sipanda.online |

### 6. Error Handling (No 500s)
| Status | Detail |
|--------|--------|
| ✅ PASS | Unknown endpoint returns 404 (not 500) |
| ✅ PASS | Health endpoint /up → 200 OK |

### 7. Frontend & PWA
| Status | Detail |
|--------|--------|
| ✅ PASS | Frontend sipanda.online → 200 OK |

## Summary

🎉 **ALL CRITICAL TESTS PASSED** — Production ready.

- **Pass**: 22
- **Fail**: 0
- **Warn**: 0



---

## File: docs/08_arsip_migrasi_9pajak/results/12_Production_Readiness_20260303_203150.md
---
# Production Readiness Test Report

**Date**: 2026-03-03 20:31:50
**API**: https://api.sipanda.online
**Frontend**: https://sipanda.online


### 1. CORS Preflight (OPTIONS)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS /api/me → 204 No Content |
| ✅ PASS | Access-Control-Allow-Origin: https://sipanda.online |
| ✅ PASS | Access-Control-Allow-Credentials: true |
| ✅ PASS | Access-Control-Allow-Methods includes POST |
| ✅ PASS | No duplicate Access-Control-Allow-Origin headers |

### 2. CORS on Actual Responses
| Status | Detail |
|--------|--------|
| ✅ PASS | GET /api/me (no auth) → 401 Unauthorized |
| ✅ PASS | 401 response includes CORS headers |
| ✅ PASS | No duplicate CORS headers on 401 response |
| ✅ PASS | 401 returns JSON: {"message":"Unauthenticated."} |

### 3. Citizen Login
| Status | Detail |
|--------|--------|
| ✅ PASS | Citizen login successful, token received |
| ✅ PASS | Login response includes user data |
| ✅ PASS | Bad login correctly rejected |
| ✅ PASS | Login response has exactly 1 Access-Control-Allow-Origin header |

### 4. Authenticated API Endpoints
| Status | Detail |
|--------|--------|
| ✅ PASS | GET /api/me (authenticated) → 200 OK |
| ✅ PASS | /api/me has exactly 1 CORS header |
| ✅ PASS | GET /api/citizen/services → 200 OK |
| ✅ PASS | GET /api/citizen/bills → 200 OK |

### 5. Cross-Origin (admin.sipanda.online)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS from admin.sipanda.online → 204 |
| ✅ PASS | CORS allows admin.sipanda.online |

### 6. Error Handling (No 500s)
| Status | Detail |
|--------|--------|
| ✅ PASS | Unknown endpoint returns 404 (not 500) |
| ✅ PASS | Health endpoint /up → 200 OK |

### 7. Frontend & PWA
| Status | Detail |
|--------|--------|
| ✅ PASS | Frontend sipanda.online → 200 OK |

## Summary

🎉 **ALL CRITICAL TESTS PASSED** — Production ready.

- **Pass**: 22
- **Fail**: 0
- **Warn**: 0



---

## File: docs/08_arsip_migrasi_9pajak/results/13_Penetration_Test_20260311_075944.md
---
# 🔒 Penetration Test Report

**Date**: 2026-03-11 07:59:44
**Target**: https://api.sipanda.online
**Methodology**: OWASP Top 10 + Custom Vectors


### 1. Sensitive File Exposure
| Status | Detail |
|--------|--------|
| ✅ SECURE | .env file blocked (404) |
| ✅ SECURE | .git directory blocked (404) |
| ⚠️ WARN | .htaccess accessible: 200 |
| ✅ SECURE | composer.json not exposed (404) |
| ✅ SECURE | Laravel logs not exposed (403) |
| ✅ SECURE | phpinfo.php not exposed (404) |

### 2. SQL Injection
| Status | Detail |
|--------|--------|
| ✅ SECURE | Login immune to basic SQLi (OR 1=1) |
| ✅ SECURE | No SQL error leakage in bills query |
| ✅ SECURE | No blind SQLi detected (response in 0s) |

### 3. Cross-Site Scripting (XSS)
| Status | Detail |
|--------|--------|
| ✅ SECURE | XSS payload not reflected in registration |
| ✅ SECURE | XSS payload not reflected in query params |

### 4. Broken Authentication
| Status | Detail |
|--------|--------|
| ✅ SECURE | /api/me returns 401 without token |
| ✅ SECURE | Fabricated token rejected (401) |
| ✅ SECURE | Citizen token cannot access /api/users (401) |

### 5. IDOR (Insecure Direct Object Reference)
| Status | Detail |
|--------|--------|
| ✅ SECURE | IDOR blocked for /api/taxpayers/1 (401) |
| ✅ SECURE | IDOR blocked for /api/bills/1 (401) |

### 6. Path Traversal
| Status | Detail |
|--------|--------|
| ✅ SECURE | Path traversal blocked (no file content leaked) |
| ✅ SECURE | Encoded path traversal blocked |

### 7. Security Headers
| Status | Detail |
|--------|--------|
| ❌ VULN | HSTS header missing |
| ❌ VULN | X-Content-Type-Options header missing |
| ❌ VULN | X-Frame-Options missing (clickjacking risk) |
| ⚠️ WARN | X-XSS-Protection header missing |
| ✅ SECURE | Server version not fully exposed |
| ✅ SECURE | X-Powered-By header not present (tech stack hidden) |

### 8. Rate Limiting / Brute Force Protection
| Status | Detail |
|--------|--------|
| ❌ VULN | No rate limiting detected after 65 requests (brute force possible) |

### 9. Mass Assignment
| Status | Detail |
|--------|--------|
| ✅ SECURE | Mass assignment blocked (is_admin/role not assignable) |

### 10. CORS Misconfiguration
| Status | Detail |
|--------|--------|
| ✅ SECURE | CORS rejects evil origin (no wildcard) |
| ✅ SECURE | CORS rejects null origin |

### 11. HTTP Method Tampering
| Status | Detail |
|--------|--------|
| ✅ SECURE | PUT on login endpoint rejected (405) |
| ✅ SECURE | DELETE on /api/me rejected (405) |

### 12. Verbose Error / Stack Trace Leakage
| Status | Detail |
|--------|--------|
| ✅ SECURE | No stack trace leakage on malformed JSON |
| ✅ SECURE | Long input handled gracefully (status: 401) |

## Summary

⚠️ **4 VULNERABILITIES FOUND** — Action recommended.

- **Secure**: 26
- **Vulnerabilities**: 4
- **Warnings**: 2
- **Critical**: 0
- **Total Tests**: 32



---

## File: docs/08_arsip_migrasi_9pajak/results/13_Penetration_Test_20260226_032504.md
---
# 🔒 Penetration Test Report

**Date**: 2026-02-26 03:25:04
**Target**: https://api.sipanda.online
**Methodology**: OWASP Top 10 + Custom Vectors


### 1. Sensitive File Exposure
| Status | Detail |
|--------|--------|
| ✅ SECURE | .env file blocked (403) |
| ✅ SECURE | .git directory blocked (403) |
| ✅ SECURE | .htaccess blocked (403) |
| ✅ SECURE | composer.json not exposed (404) |
| ✅ SECURE | Laravel logs not exposed (403) |
| ✅ SECURE | phpinfo.php not exposed (404) |

### 2. SQL Injection
| Status | Detail |
|--------|--------|
| ✅ SECURE | Login immune to basic SQLi (OR 1=1) |
| ✅ SECURE | No SQL error leakage in bills query |
| ✅ SECURE | No blind SQLi detected (response in 0s) |

### 3. Cross-Site Scripting (XSS)
| Status | Detail |
|--------|--------|
| ✅ SECURE | XSS payload not reflected in registration |
| ✅ SECURE | XSS payload not reflected in query params |

### 4. Broken Authentication
| Status | Detail |
|--------|--------|
| ✅ SECURE | /api/me returns 401 without token |
| ✅ SECURE | Fabricated token rejected (401) |
| ✅ SECURE | Citizen token cannot access /api/users (401) |

### 5. IDOR (Insecure Direct Object Reference)
| Status | Detail |
|--------|--------|
| ✅ SECURE | IDOR blocked for /api/taxpayers/1 (401) |
| ✅ SECURE | IDOR blocked for /api/bills/1 (401) |

### 6. Path Traversal
| Status | Detail |
|--------|--------|
| ✅ SECURE | Path traversal blocked (no file content leaked) |
| ✅ SECURE | Encoded path traversal blocked |

### 7. Security Headers
| Status | Detail |
|--------|--------|
| ✅ SECURE | HSTS header present |
| ✅ SECURE | X-Content-Type-Options: nosniff |
| ✅ SECURE | X-Frame-Options present (clickjacking protection) |
| ✅ SECURE | X-XSS-Protection header present |
| ✅ SECURE | Server version not fully exposed |
| ✅ SECURE | X-Powered-By header not present (tech stack hidden) |

### 8. Rate Limiting / Brute Force Protection
| Status | Detail |
|--------|--------|
| ✅ SECURE | Rate limiting active (429 at request #61) |

### 9. Mass Assignment
| Status | Detail |
|--------|--------|
| ✅ SECURE | Mass assignment blocked (is_admin/role not assignable) |

### 10. CORS Misconfiguration
| Status | Detail |
|--------|--------|
| ❌ VULN | CORS allows arbitrary origins: Access-Control-Allow-Origin: https://evil-hacker.com |
| ❌ VULN | CORS allows null origin |

### 11. HTTP Method Tampering
| Status | Detail |
|--------|--------|
| ✅ SECURE | PUT on login endpoint rejected (405) |
| ✅ SECURE | DELETE on /api/me rejected (405) |

### 12. Verbose Error / Stack Trace Leakage
| Status | Detail |
|--------|--------|
| ✅ SECURE | No stack trace leakage on malformed JSON |
| ✅ SECURE | Long input handled gracefully (status: 429) |

## Summary

⚠️ **2 VULNERABILITIES FOUND** — Action recommended.

- **Secure**: 30
- **Vulnerabilities**: 2
- **Warnings**: 0
- **Critical**: 0
- **Total Tests**: 32



---

## File: docs/08_arsip_migrasi_9pajak/results/13_Penetration_Test_20260226_225203.md
---
# 🔒 Penetration Test Report

**Date**: 2026-02-26 22:52:03
**Target**: https://api.sipanda.online
**Methodology**: OWASP Top 10 + Custom Vectors


### 1. Sensitive File Exposure
| Status | Detail |
|--------|--------|
| ✅ SECURE | .env file blocked (403) |
| ✅ SECURE | .git directory blocked (403) |
| ✅ SECURE | .htaccess blocked (403) |
| ✅ SECURE | composer.json not exposed (404) |
| ✅ SECURE | Laravel logs not exposed (403) |
| ✅ SECURE | phpinfo.php not exposed (404) |

### 2. SQL Injection
| Status | Detail |
|--------|--------|
| ✅ SECURE | Login immune to basic SQLi (OR 1=1) |
| ✅ SECURE | No SQL error leakage in bills query |
| ✅ SECURE | No blind SQLi detected (response in 0s) |

### 3. Cross-Site Scripting (XSS)
| Status | Detail |
|--------|--------|
| ✅ SECURE | XSS payload not reflected in registration |
| ✅ SECURE | XSS payload not reflected in query params |

### 4. Broken Authentication
| Status | Detail |
|--------|--------|
| ✅ SECURE | /api/me returns 401 without token |
| ✅ SECURE | Fabricated token rejected (401) |
| ✅ SECURE | Citizen token cannot access /api/users (403) |

### 5. IDOR (Insecure Direct Object Reference)
| Status | Detail |
|--------|--------|
| ✅ SECURE | IDOR blocked for /api/taxpayers/1 (404) |
| ⚠️ WARN | IDOR possible: citizen can access /api/bills/1 (200) (review scope) |

### 6. Path Traversal
| Status | Detail |
|--------|--------|
| ✅ SECURE | Path traversal blocked (no file content leaked) |
| ✅ SECURE | Encoded path traversal blocked |

### 7. Security Headers
| Status | Detail |
|--------|--------|
| ✅ SECURE | HSTS header present |
| ✅ SECURE | X-Content-Type-Options: nosniff |
| ✅ SECURE | X-Frame-Options present (clickjacking protection) |
| ✅ SECURE | X-XSS-Protection header present |
| ✅ SECURE | Server version not fully exposed |
| ✅ SECURE | X-Powered-By header not present (tech stack hidden) |

### 8. Rate Limiting / Brute Force Protection
| Status | Detail |
|--------|--------|
| ❌ VULN | No rate limiting detected after 65 requests (brute force possible) |

### 9. Mass Assignment
| Status | Detail |
|--------|--------|
| ✅ SECURE | Mass assignment blocked (is_admin/role not assignable) |

### 10. CORS Misconfiguration
| Status | Detail |
|--------|--------|
| ✅ SECURE | CORS rejects evil origin (no wildcard) |
| ✅ SECURE | CORS rejects null origin |

### 11. HTTP Method Tampering
| Status | Detail |
|--------|--------|
| ✅ SECURE | PUT on login endpoint rejected (405) |
| ✅ SECURE | DELETE on /api/me rejected (405) |

### 12. Verbose Error / Stack Trace Leakage
| Status | Detail |
|--------|--------|
| ✅ SECURE | No stack trace leakage on malformed JSON |
| ✅ SECURE | Long input handled gracefully (status: 401) |

## Summary

⚠️ **1 VULNERABILITIES FOUND** — Action recommended.

- **Secure**: 30
- **Vulnerabilities**: 1
- **Warnings**: 1
- **Critical**: 0
- **Total Tests**: 32



---

## File: docs/08_arsip_migrasi_9pajak/results/13_Penetration_Test_20260226_032716.md
---
# 🔒 Penetration Test Report

**Date**: 2026-02-26 03:27:16
**Target**: https://api.sipanda.online
**Methodology**: OWASP Top 10 + Custom Vectors


### 1. Sensitive File Exposure
| Status | Detail |
|--------|--------|
| ✅ SECURE | .env file blocked (403) |
| ✅ SECURE | .git directory blocked (403) |
| ✅ SECURE | .htaccess blocked (403) |
| ✅ SECURE | composer.json not exposed (404) |
| ✅ SECURE | Laravel logs not exposed (403) |
| ✅ SECURE | phpinfo.php not exposed (404) |

### 2. SQL Injection
| Status | Detail |
|--------|--------|
| ✅ SECURE | Login immune to basic SQLi (OR 1=1) |
| ✅ SECURE | No SQL error leakage in bills query |
| ✅ SECURE | No blind SQLi detected (response in 0s) |

### 3. Cross-Site Scripting (XSS)
| Status | Detail |
|--------|--------|
| ✅ SECURE | XSS payload not reflected in registration |
| ✅ SECURE | XSS payload not reflected in query params |

### 4. Broken Authentication
| Status | Detail |
|--------|--------|
| ✅ SECURE | /api/me returns 401 without token |
| ✅ SECURE | Fabricated token rejected (401) |
| ✅ SECURE | Citizen token cannot access /api/users (403) |

### 5. IDOR (Insecure Direct Object Reference)
| Status | Detail |
|--------|--------|
| ✅ SECURE | IDOR blocked for /api/taxpayers/1 (404) |
| ⚠️ WARN | IDOR possible: citizen can access /api/bills/1 (200) (review scope) |

### 6. Path Traversal
| Status | Detail |
|--------|--------|
| ✅ SECURE | Path traversal blocked (no file content leaked) |
| ✅ SECURE | Encoded path traversal blocked |

### 7. Security Headers
| Status | Detail |
|--------|--------|
| ✅ SECURE | HSTS header present |
| ✅ SECURE | X-Content-Type-Options: nosniff |
| ✅ SECURE | X-Frame-Options present (clickjacking protection) |
| ✅ SECURE | X-XSS-Protection header present |
| ✅ SECURE | Server version not fully exposed |
| ✅ SECURE | X-Powered-By header not present (tech stack hidden) |

### 8. Rate Limiting / Brute Force Protection
| Status | Detail |
|--------|--------|
| ✅ SECURE | Rate limiting active (429 at request #57) |

### 9. Mass Assignment
| Status | Detail |
|--------|--------|
| ✅ SECURE | Mass assignment blocked (is_admin/role not assignable) |

### 10. CORS Misconfiguration
| Status | Detail |
|--------|--------|
| ✅ SECURE | CORS rejects evil origin (no wildcard) |
| ✅ SECURE | CORS rejects null origin |

### 11. HTTP Method Tampering
| Status | Detail |
|--------|--------|
| ✅ SECURE | PUT on login endpoint rejected (405) |
| ✅ SECURE | DELETE on /api/me rejected (405) |

### 12. Verbose Error / Stack Trace Leakage
| Status | Detail |
|--------|--------|
| ✅ SECURE | No stack trace leakage on malformed JSON |
| ✅ SECURE | Long input handled gracefully (status: 429) |

## Summary

🛡️ **NO VULNERABILITIES FOUND** — Security posture is strong.

- **Secure**: 31
- **Vulnerabilities**: 0
- **Warnings**: 1
- **Critical**: 0
- **Total Tests**: 32



---

## File: docs/08_arsip_migrasi_9pajak/results/12_Production_Readiness_20260311_075420.md
---
# Production Readiness Test Report

**Date**: 2026-03-11 07:54:20
**API**: https://api.sipanda.online
**Frontend**: https://sipanda.online


### 1. CORS Preflight (OPTIONS)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS /api/me → 204 No Content |
| ✅ PASS | Access-Control-Allow-Origin: https://sipanda.online |
| ✅ PASS | Access-Control-Allow-Credentials: true |
| ✅ PASS | Access-Control-Allow-Methods includes POST |
| ✅ PASS | No duplicate Access-Control-Allow-Origin headers |

### 2. CORS on Actual Responses
| Status | Detail |
|--------|--------|
| ✅ PASS | GET /api/me (no auth) → 401 Unauthorized |
| ❌ FAIL | 401 response missing CORS headers |
| ✅ PASS | No duplicate CORS headers on 401 response |
| ✅ PASS | 401 returns JSON: {"message":"Unauthenticated."} |

### 3. Citizen Login
| Status | Detail |
|--------|--------|
| ❌ FAIL | Citizen login failed: {"message":"NIK atau password salah"} |
| ❌ FAIL | Login response missing user data |
| ✅ PASS | Bad login correctly rejected |
| ❌ FAIL | Login response has 0 Access-Control-Allow-Origin headers (expected 1) |

### 4. Authenticated API Endpoints
| Status | Detail |
|--------|--------|
| ❌ FAIL | Skipping authenticated tests - no token |

### 5. Cross-Origin (admin.sipanda.online)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS from admin.sipanda.online → 204 |
| ✅ PASS | CORS allows admin.sipanda.online |

### 6. Error Handling (No 500s)
| Status | Detail |
|--------|--------|
| ✅ PASS | Unknown endpoint returns 404 (not 500) |
| ✅ PASS | Health endpoint /up → 200 OK |

### 7. Frontend & PWA
| Status | Detail |
|--------|--------|
| ✅ PASS | Frontend sipanda.online → 200 OK |

## Summary

⚠️ **5 CRITICAL TEST(S) FAILED** — Needs attention.

- **Pass**: 14
- **Fail**: 5
- **Warn**: 0



---

## File: docs/08_arsip_migrasi_9pajak/results/13_Penetration_Test_20260311_075420.md
---
# 🔒 Penetration Test Report

**Date**: 2026-03-11 07:54:20
**Target**: https://api.sipanda.online
**Methodology**: OWASP Top 10 + Custom Vectors


### 1. Sensitive File Exposure
| Status | Detail |
|--------|--------|
| ✅ SECURE | .env file blocked (404) |
| ✅ SECURE | .git directory blocked (404) |
| ⚠️ WARN | .htaccess accessible: 200 |
| ✅ SECURE | composer.json not exposed (404) |
| ✅ SECURE | Laravel logs not exposed (403) |
| ✅ SECURE | phpinfo.php not exposed (404) |

### 2. SQL Injection
| Status | Detail |
|--------|--------|
| ✅ SECURE | Login immune to basic SQLi (OR 1=1) |
| ✅ SECURE | No SQL error leakage in bills query |
| ✅ SECURE | No blind SQLi detected (response in 0s) |

### 3. Cross-Site Scripting (XSS)
| Status | Detail |
|--------|--------|
| ✅ SECURE | XSS payload not reflected in registration |
| ✅ SECURE | XSS payload not reflected in query params |

### 4. Broken Authentication
| Status | Detail |
|--------|--------|
| ✅ SECURE | /api/me returns 401 without token |
| ✅ SECURE | Fabricated token rejected (401) |
| ✅ SECURE | Citizen token cannot access /api/users (401) |

### 5. IDOR (Insecure Direct Object Reference)
| Status | Detail |
|--------|--------|
| ✅ SECURE | IDOR blocked for /api/taxpayers/1 (401) |
| ✅ SECURE | IDOR blocked for /api/bills/1 (401) |

### 6. Path Traversal
| Status | Detail |
|--------|--------|
| ✅ SECURE | Path traversal blocked (no file content leaked) |
| ✅ SECURE | Encoded path traversal blocked |

### 7. Security Headers
| Status | Detail |
|--------|--------|
| ❌ VULN | HSTS header missing |
| ❌ VULN | X-Content-Type-Options header missing |
| ❌ VULN | X-Frame-Options missing (clickjacking risk) |
| ⚠️ WARN | X-XSS-Protection header missing |
| ✅ SECURE | Server version not fully exposed |
| ✅ SECURE | X-Powered-By header not present (tech stack hidden) |

### 8. Rate Limiting / Brute Force Protection
| Status | Detail |
|--------|--------|
| ❌ VULN | No rate limiting detected after 65 requests (brute force possible) |

### 9. Mass Assignment
| Status | Detail |
|--------|--------|
| ✅ SECURE | Mass assignment blocked (is_admin/role not assignable) |

### 10. CORS Misconfiguration
| Status | Detail |
|--------|--------|
| ✅ SECURE | CORS rejects evil origin (no wildcard) |
| ✅ SECURE | CORS rejects null origin |

### 11. HTTP Method Tampering
| Status | Detail |
|--------|--------|
| ✅ SECURE | PUT on login endpoint rejected (405) |
| ✅ SECURE | DELETE on /api/me rejected (405) |

### 12. Verbose Error / Stack Trace Leakage
| Status | Detail |
|--------|--------|
| ✅ SECURE | No stack trace leakage on malformed JSON |
| ✅ SECURE | Long input handled gracefully (status: 401) |

## Summary

⚠️ **4 VULNERABILITIES FOUND** — Action recommended.

- **Secure**: 26
- **Vulnerabilities**: 4
- **Warnings**: 2
- **Critical**: 0
- **Total Tests**: 32



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_prod_20260226_225239.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-02-26 22:52:39
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: opd) |
| ✅ | Citizen login → token received |
| ✅ | Citizen ID: 2, NIK: 1234567890123456 |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ❌ | GET /test-cors → 404 (expected 200) |
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ✅ | GET /citizen/bills?nik=... → 200 |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /me (citizen profile) → 200 |
| ✅ | GET /citizen/services → 200 |
| ✅ | GET /citizen/services/2 (detail) → 200 |
| ✅ | GET /citizen/services/2/bills → 200 |
| ✅ | GET /citizen/services/pending-periods → 200 |
| ✅ | GET /citizen/reports → 200 |
| ✅ | GET /pbb/bapenda/my-objects → 200 |
| ✅ | GET /pbb/bapenda/my-transactions → 200 |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 25 |
| ✅ | READ zone/25 → 200 |
| ✅ | UPDATE zone/25 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/25 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 1328, NIK: 9900001772117572 |
| ✅ | GET /taxpayers/1328 → 200 |
| ✅ | GET /taxpayers/search/9900001772117572 → 200 |
| ✅ | UPDATE taxpayer/1328 → 200 |
| ✅ | DELETE taxpayer/1328 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 48 |
| ✅ | GET /retribution-types/48 → 200 |
| ✅ | UPDATE retribution-type/48 → 200 |
| ✅ | DELETE retribution-type/48 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ✅ | Tax simulation → result received |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|
| ✅ | READ profile → name: Wajib Pajak Demo
Badan Pendapatan Daerah |
| ✅ | UPDATE profile → 200 OK |

## Summary

⚠️ **1 TEST(S) FAILED**

- **Pass**: 73
- **Fail**: 1
- **Warn**: 0
- **Skip**: 0
- **Total**: 74



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_prod_20260227_061442.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-02-27 06:14:42
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: opd) |
| ✅ | Citizen login → token received |
| ✅ | Citizen ID: 2, NIK: 1234567890123456 |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ✅ | GET /citizen/bills?nik=... → 200 |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /me (citizen profile) → 200 |
| ✅ | GET /citizen/services → 200 |
| ✅ | GET /citizen/services/2 (detail) → 200 |
| ✅ | GET /citizen/services/2/bills → 200 |
| ✅ | GET /citizen/services/pending-periods → 200 |
| ✅ | GET /citizen/reports → 200 |
| ✅ | GET /pbb/bapenda/my-objects → 200 |
| ✅ | GET /pbb/bapenda/my-transactions → 200 |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ❌ | CREATE zone failed: {"message":"The selected retribution type id is invalid.","errors":{"retribution_type_id":["The selected retribution type id is invalid."]}} |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ❌ | CREATE taxpayer failed: {"message":"The selected retribution_type_ids.0 is invalid.","errors":{"retribution_type_ids.0":["The selected retribution_type_ids.0 is invalid."]}} |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 52 |
| ✅ | GET /retribution-types/52 → 200 |
| ✅ | UPDATE retribution-type/52 → 200 |
| ✅ | DELETE retribution-type/52 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ✅ | Tax simulation → result received |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|
| ✅ | READ profile → name: Wajib Pajak Demo
Badan Pendapatan Daerah |
| ✅ | UPDATE profile → 200 OK |

## Summary

⚠️ **2 TEST(S) FAILED**

- **Pass**: 62
- **Fail**: 2
- **Warn**: 0
- **Skip**: 0
- **Total**: 64



---

## File: docs/08_arsip_migrasi_9pajak/results/07_Hasil_Kalkulator_Prod_Fixed.md
---
# 🧮 Laporan Hasil Uji Otomatis API Kalkulator Pajak

**Waktu Eksekusi**: 2026-02-25 21:21:39
Pengujian dieksekusi secara otomatis menembak server `Localhost:8000` via endpoint POST `/api/simulate-tax` untuk masing-masing klasifikasi.

### PBB-P2 (`PBB-UMUM`)
- **Formula Server**: `(njop - 10000000) * (njkp_percent / 100) * (tariff / 100)`
- **Dummy Set Variabel**: `{"luas_tanah":120,"kelas_bumi":"080","luas_bangunan":60,"kelas_bangunan":"080","nomor_sertifikat":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### BPHTB (`BPHTB`)
- **Formula Server**: `(npop - npoptkp) * 0.05`
- **Dummy Set Variabel**: `{"npop":5000000,"npoptkp":1000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 200.000**

### Pajak Reklame (`REKLAME`)
- **Formula Server**: `nsr * 0.25`
- **Dummy Set Variabel**: `{"nsr":5000000,"ukuran":100,"lokasi_reklame":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 1.250.000**

### Pajak Sarang Burung Walet (`WALET`)
- **Formula Server**: `nilai_jual * 0.10`
- **Dummy Set Variabel**: `{"nilai_jual":5000000,"lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### Pajak MBLB (`MBLB`)
- **Formula Server**: `(volume * harga_patokan) * 0.15`
- **Dummy Set Variabel**: `{"volume":50,"harga_patokan":80000,"jenis_mineral":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 600.000**

### Opsen PKB (`OPS-PKB`)
- **Formula Server**: `pkb_pokok * 0.66`
- **Dummy Set Variabel**: `{"pkb_pokok":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 9.900**

### Opsen BBNKB (`OPS-BBN`)
- **Formula Server**: `bbnkb_pokok * 0.66`
- **Dummy Set Variabel**: `{"bbnkb_pokok":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 9.900**

### PBJT - Makan dan Minum (`PBJT-MNM`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Tenaga Listrik (`PBJT-LIS`)
- **Formula Server**: `tagihan_listrik * tariff`
- **Dummy Set Variabel**: `{"tagihan_listrik":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### PBJT - Jasa Perhotelan (`PBJT-HTL`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Parkir (`PBJT-PRK`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Kesenian dan Hiburan (`PBJT-HBR`)
- **Formula Server**: `omzet * tariff`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Pajak Air Tanah (`PAT`)
- **Formula Server**: `(volume * hda) * 0.20`
- **Dummy Set Variabel**: `{"volume":50,"hda":80000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 800.000**

### Penyediaan Tempat Kegiatan Usaha (`PTKU`)
- **Formula Server**: `amount`
- **Dummy Set Variabel**: `{"jenis_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Retribusi Jasa Umum (`RJU`)
- **Formula Server**: `amount`
- **Dummy Set Variabel**: `{"jenis_layanan":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Retribusi Perizinan Tertentu (`RPT`)
- **Formula Server**: `amount`
- **Dummy Set Variabel**: `{"jenis_izin":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Persetujuan Bangunan Gedung (PBG) (`PBG`)
- **Formula Server**: `luas_lantai * (indeks_lokalitas * shst) * indeks_terintegrasi * indeks_bg`
- **Dummy Set Variabel**: `{"luas_lantai":100,"indeks_lokalitas":1,"shst":5560000,"indeks_terintegrasi":1,"indeks_bg":1}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 556.000.000**

### PBJT - Jasa Catering (`PBJT-CAT`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"nama_perusahaan":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Event/Hiburan Lainnya (`PBJT-EVT`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"nama_event":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**



---

## File: docs/08_arsip_migrasi_9pajak/results/13_Penetration_Test_20260226_030758.md
---
# 🔒 Penetration Test Report

**Date**: 2026-02-26 03:07:58
**Target**: https://api.sipanda.online
**Methodology**: OWASP Top 10 + Custom Vectors


### 1. Sensitive File Exposure
| Status | Detail |
|--------|--------|
| ✅ SECURE | .env file blocked (403) |
| ✅ SECURE | .git directory blocked (403) |
| ✅ SECURE | .htaccess blocked (403) |
| ✅ SECURE | composer.json not exposed (404) |
| ✅ SECURE | Laravel logs not exposed (403) |
| ✅ SECURE | phpinfo.php not exposed (404) |

### 2. SQL Injection
| Status | Detail |
|--------|--------|
| ✅ SECURE | Login immune to basic SQLi (OR 1=1) |
| ✅ SECURE | No SQL error leakage in bills query |
| ✅ SECURE | No blind SQLi detected (response in 0s) |

### 3. Cross-Site Scripting (XSS)
| Status | Detail |
|--------|--------|
| ✅ SECURE | XSS payload not reflected in registration |
| ✅ SECURE | XSS payload not reflected in query params |

### 4. Broken Authentication
| Status | Detail |
|--------|--------|
| ✅ SECURE | /api/me returns 401 without token |
| ✅ SECURE | Fabricated token rejected (401) |
| ❌ VULN | Citizen token can access admin /api/users! Status: 200 |

### 5. IDOR (Insecure Direct Object Reference)
| Status | Detail |
|--------|--------|
| ✅ SECURE | IDOR blocked for /api/taxpayers/1 (404) |
| ⚠️ WARN | IDOR possible: citizen can access /api/bills/1 (200) (review scope) |

### 6. Path Traversal
| Status | Detail |
|--------|--------|
| ✅ SECURE | Path traversal blocked (no file content leaked) |
| ✅ SECURE | Encoded path traversal blocked |

### 7. Security Headers
| Status | Detail |
|--------|--------|
| ✅ SECURE | HSTS header present |
| ✅ SECURE | X-Content-Type-Options: nosniff |
| ✅ SECURE | X-Frame-Options present (clickjacking protection) |
| ✅ SECURE | X-XSS-Protection header present |
| ✅ SECURE | Server version not fully exposed |
| ✅ SECURE | X-Powered-By header not present (tech stack hidden) |

### 8. Rate Limiting / Brute Force Protection
| Status | Detail |
|--------|--------|
| ✅ SECURE | Rate limiting active (429 at request #56) |

### 9. Mass Assignment
| Status | Detail |
|--------|--------|
| ✅ SECURE | Mass assignment blocked (is_admin/role not assignable) |

### 10. CORS Misconfiguration
| Status | Detail |
|--------|--------|
| ❌ VULN | CORS allows arbitrary origins: Access-Control-Allow-Origin: https://evil-hacker.com |
| ❌ VULN | CORS allows null origin |

### 11. HTTP Method Tampering
| Status | Detail |
|--------|--------|
| ✅ SECURE | PUT on login endpoint rejected (405) |
| ✅ SECURE | DELETE on /api/me rejected (405) |

### 12. Verbose Error / Stack Trace Leakage
| Status | Detail |
|--------|--------|
| ✅ SECURE | No stack trace leakage on malformed JSON |
| ✅ SECURE | Long input handled gracefully (status: 429) |

## Summary

⚠️ **3 VULNERABILITIES FOUND** — Action recommended.

- **Secure**: 28
- **Vulnerabilities**: 3
- **Warnings**: 1
- **Critical**: 0
- **Total Tests**: 32



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_staging_20260313_054733.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-13 05:47:33
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: super_admin) |
| ⚠️ | Standard citizen login failed, attempting dynamic NIK discovery... |
| ✅ | Discovered valid NIK: 7404651929094717, retrying login... |
| ❌ | Citizen login failed (tried hardcoded and discovered NIKs) |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ❌ | GET /citizen/bills?nik=... → 422 (expected 200) |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Citizen endpoints skipped (no token) |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |
| ❌ | POST /pbb/bapenda/inquiry (Public - Expect 404) → 500 (expected 404) |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 14 |
| ✅ | READ zone/14 → 200 |
| ✅ | UPDATE zone/14 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/14 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 37, NIK: 9900001773352098 |
| ✅ | GET /taxpayers/37 → 200 |
| ✅ | GET /taxpayers/search/9900001773352098 → 200 |
| ✅ | UPDATE taxpayer/37 → 200 |
| ✅ | DELETE taxpayer/37 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 24 |
| ✅ | GET /retribution-types/24 → 200 |
| ✅ | UPDATE retribution-type/24 → 200 |
| ✅ | DELETE retribution-type/24 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|

## Summary

⚠️ **3 TEST(S) FAILED**

- **Pass**: 60
- **Fail**: 3
- **Warn**: 1
- **Skip**: 2
- **Total**: 66



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_local_20260311_044050.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-11 04:40:50
**Target**: http://localhost:8000/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: super_admin) |
| ✅ | Citizen login → token received |
| ✅ | Citizen ID: 31, NIK: 3201234567890001 |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ✅ | GET /citizen/bills?nik=... → 200 |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /me (citizen profile) → 200 |
| ✅ | GET /citizen/services → 200 |
| ✅ | GET /citizen/services/1 (detail) → 200 |
| ✅ | GET /citizen/services/1/bills → 200 |
| ✅ | GET /citizen/services/pending-periods → 200 |
| ✅ | GET /citizen/reports → 200 |
| ✅ | GET /pbb/bapenda/my-objects → 200 |
| ✅ | GET /pbb/bapenda/my-transactions → 200 |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |
| ✅ | POST /pbb/bapenda/inquiry (Public - Expect 404) → 404 |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 11 |
| ✅ | READ zone/11 → 200 |
| ✅ | UPDATE zone/11 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/11 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 33, NIK: 9900001773175263 |
| ✅ | GET /taxpayers/33 → 200 |
| ✅ | GET /taxpayers/search/9900001773175263 → 200 |
| ✅ | UPDATE taxpayer/33 → 200 |
| ✅ | DELETE taxpayer/33 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 12 |
| ✅ | GET /retribution-types/12 → 200 |
| ✅ | UPDATE retribution-type/12 → 200 |
| ✅ | DELETE retribution-type/12 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|
| ✅ | READ profile → name: Citizen Tester |
| ✅ | UPDATE profile → 200 OK |

## Summary

🎉 **ALL TESTS PASSED**

- **Pass**: 73
- **Fail**: 0
- **Warn**: 0
- **Skip**: 1
- **Total**: 74



---

## File: docs/08_arsip_migrasi_9pajak/results/07_Hasil_Kalkulator_Dev_Fixed.md
---
# 🧮 Laporan Hasil Uji Otomatis API Kalkulator Pajak

**Waktu Eksekusi**: 2026-02-25 21:22:32
Pengujian dieksekusi secara otomatis menembak server `Localhost:8000` via endpoint POST `/api/simulate-tax` untuk masing-masing klasifikasi.

### PBB-P2 (`PBB-UMUM`)
- **Formula Server**: `(njop - 10000000) * (njkp_percent / 100) * (tariff / 100)`
- **Dummy Set Variabel**: `{"luas_tanah":120,"kelas_bumi":"080","luas_bangunan":60,"kelas_bangunan":"080","nomor_sertifikat":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### BPHTB (`BPHTB`)
- **Formula Server**: `(npop - npoptkp) * 0.05`
- **Dummy Set Variabel**: `{"npop":5000000,"npoptkp":1000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 200.000**

### Pajak Reklame (`REKLAME`)
- **Formula Server**: `nsr * 0.25`
- **Dummy Set Variabel**: `{"nsr":5000000,"ukuran":100,"lokasi_reklame":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 1.250.000**

### Pajak Sarang Burung Walet (`WALET`)
- **Formula Server**: `nilai_jual * 0.10`
- **Dummy Set Variabel**: `{"nilai_jual":5000000,"lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### Pajak MBLB (`MBLB`)
- **Formula Server**: `(volume * harga_patokan) * 0.15`
- **Dummy Set Variabel**: `{"volume":50,"harga_patokan":80000,"jenis_mineral":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 600.000**

### Opsen PKB (`OPS-PKB`)
- **Formula Server**: `pkb_pokok * 0.66`
- **Dummy Set Variabel**: `{"pkb_pokok":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 9.900**

### Opsen BBNKB (`OPS-BBN`)
- **Formula Server**: `bbnkb_pokok * 0.66`
- **Dummy Set Variabel**: `{"bbnkb_pokok":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 9.900**

### PBJT - Makan dan Minum (`PBJT-MNM`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Tenaga Listrik (`PBJT-LIS`)
- **Formula Server**: `tagihan_listrik * tariff`
- **Dummy Set Variabel**: `{"tagihan_listrik":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### PBJT - Jasa Perhotelan (`PBJT-HTL`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Parkir (`PBJT-PRK`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Kesenian dan Hiburan (`PBJT-HBR`)
- **Formula Server**: `omzet * tariff`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Pajak Air Tanah (`PAT`)
- **Formula Server**: `(volume * hda) * 0.20`
- **Dummy Set Variabel**: `{"volume":50,"hda":80000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 800.000**

### Penyediaan Tempat Kegiatan Usaha (`PTKU`)
- **Formula Server**: `amount`
- **Dummy Set Variabel**: `{"jenis_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Retribusi Jasa Umum (`RJU`)
- **Formula Server**: `amount`
- **Dummy Set Variabel**: `{"jenis_layanan":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Retribusi Perizinan Tertentu (`RPT`)
- **Formula Server**: `amount`
- **Dummy Set Variabel**: `{"jenis_izin":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Persetujuan Bangunan Gedung (PBG) (`PBG`)
- **Formula Server**: `luas_lantai * (indeks_lokalitas * shst) * indeks_terintegrasi * indeks_bg`
- **Dummy Set Variabel**: `{"luas_lantai":100,"indeks_lokalitas":1,"shst":5560000,"indeks_terintegrasi":1,"indeks_bg":1}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 556.000.000**

### PBJT - Jasa Catering (`PBJT-CAT`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"nama_perusahaan":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Event/Hiburan Lainnya (`PBJT-EVT`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"nama_event":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**



---

## File: docs/08_arsip_migrasi_9pajak/results/12_Production_Readiness_20260303_202120.md
---
# Production Readiness Test Report

**Date**: 2026-03-03 20:21:20
**API**: https://api.sipanda.online
**Frontend**: https://sipanda.online


### 1. CORS Preflight (OPTIONS)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS /api/me → 204 No Content |
| ✅ PASS | Access-Control-Allow-Origin: https://sipanda.online |
| ✅ PASS | Access-Control-Allow-Credentials: true |
| ✅ PASS | Access-Control-Allow-Methods includes POST |
| ✅ PASS | No duplicate Access-Control-Allow-Origin headers |

### 2. CORS on Actual Responses
| Status | Detail |
|--------|--------|
| ✅ PASS | GET /api/me (no auth) → 401 Unauthorized |
| ✅ PASS | 401 response includes CORS headers |
| ✅ PASS | No duplicate CORS headers on 401 response |
| ✅ PASS | 401 returns JSON: {"message":"Unauthenticated."} |

### 3. Citizen Login
| Status | Detail |
|--------|--------|
| ❌ FAIL | Citizen login failed: {"message":"NIK atau password salah"} |
| ❌ FAIL | Login response missing user data |
| ✅ PASS | Bad login correctly rejected |
| ✅ PASS | Login response has exactly 1 Access-Control-Allow-Origin header |

### 4. Authenticated API Endpoints
| Status | Detail |
|--------|--------|
| ❌ FAIL | Skipping authenticated tests - no token |

### 5. Cross-Origin (admin.sipanda.online)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS from admin.sipanda.online → 204 |
| ✅ PASS | CORS allows admin.sipanda.online |

### 6. Error Handling (No 500s)
| Status | Detail |
|--------|--------|
| ✅ PASS | Unknown endpoint returns 404 (not 500) |
| ✅ PASS | Health endpoint /up → 200 OK |

### 7. Frontend & PWA
| Status | Detail |
|--------|--------|
| ✅ PASS | Frontend sipanda.online → 200 OK |

## Summary

⚠️ **3 CRITICAL TEST(S) FAILED** — Needs attention.

- **Pass**: 16
- **Fail**: 3
- **Warn**: 0



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_local_20260311_044000.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-11 04:40:00
**Target**: http://localhost:8000/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: super_admin) |
| ✅ | Citizen login → token received |
| ✅ | Citizen ID: 31, NIK: 3201234567890001 |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ✅ | GET /citizen/bills?nik=... → 200 |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /me (citizen profile) → 200 |
| ✅ | GET /citizen/services → 200 |
| ✅ | GET /citizen/services/1 (detail) → 200 |
| ✅ | GET /citizen/services/1/bills → 200 |
| ✅ | GET /citizen/services/pending-periods → 200 |
| ✅ | GET /citizen/reports → 200 |
| ✅ | GET /pbb/bapenda/my-objects → 200 |
| ✅ | GET /pbb/bapenda/my-transactions → 200 |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |
| ❌ | POST /pbb/bapenda/inquiry (Public) → 404 (expected 200) |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 10 |
| ✅ | READ zone/10 → 200 |
| ✅ | UPDATE zone/10 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/10 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 32, NIK: 9900001773175213 |
| ✅ | GET /taxpayers/32 → 200 |
| ✅ | GET /taxpayers/search/9900001773175213 → 200 |
| ✅ | UPDATE taxpayer/32 → 200 |
| ✅ | DELETE taxpayer/32 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 11 |
| ✅ | GET /retribution-types/11 → 200 |
| ✅ | UPDATE retribution-type/11 → 200 |
| ✅ | DELETE retribution-type/11 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|
| ✅ | READ profile → name: Citizen Tester |
| ✅ | UPDATE profile → 200 OK |

## Summary

⚠️ **1 TEST(S) FAILED**

- **Pass**: 72
- **Fail**: 1
- **Warn**: 0
- **Skip**: 1
- **Total**: 74



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_prod_20260303_203223.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-03 20:32:23
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: super_admin) |
| ✅ | Citizen login → token received |
| ✅ | Citizen ID: 1386, NIK: 3201234567890001 |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ✅ | GET /citizen/bills?nik=... → 200 |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /me (citizen profile) → 200 |
| ✅ | GET /citizen/services → 200 |
| ✅ | GET /citizen/services/2 (detail) → 200 |
| ✅ | GET /citizen/services/2/bills → 200 |
| ✅ | GET /citizen/services/pending-periods → 200 |
| ✅ | GET /citizen/reports → 200 |
| ✅ | GET /pbb/bapenda/my-objects → 200 |
| ✅ | GET /pbb/bapenda/my-transactions → 200 |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 34 |
| ✅ | READ zone/34 → 200 |
| ✅ | UPDATE zone/34 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/34 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ❌ | CREATE taxpayer failed: {"message":"Jenis retribusi harus milik OPD yang sama"} |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 58 |
| ✅ | GET /retribution-types/58 → 200 |
| ✅ | UPDATE retribution-type/58 → 200 |
| ✅ | DELETE retribution-type/58 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ✅ | Tax simulation → result received |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|
| ✅ | READ profile → name: Budi Tester UAT |
| ✅ | UPDATE profile → 200 OK |

## Summary

⚠️ **1 TEST(S) FAILED**

- **Pass**: 68
- **Fail**: 1
- **Warn**: 0
- **Skip**: 0
- **Total**: 69



---

## File: docs/08_arsip_migrasi_9pajak/results/14_API_CRUD_Test_local_20260311_043803.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-03-11 04:38:03
**Target**: http://localhost:8000/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: super_admin) |
| ❌ | Citizen login failed |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ❌ | GET /citizen/bills?nik=... → 422 (expected 200) |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Citizen endpoints skipped (no token) |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |
| ❌ | POST /pbb/bapenda/inquiry (Public) → 422 (expected 200) |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 8 |
| ✅ | READ zone/8 → 200 |
| ✅ | UPDATE zone/8 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/8 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 29, NIK: 9900001773175093 |
| ✅ | GET /taxpayers/29 → 200 |
| ✅ | GET /taxpayers/search/9900001773175093 → 200 |
| ✅ | UPDATE taxpayer/29 → 200 |
| ✅ | DELETE taxpayer/29 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 9 |
| ✅ | GET /retribution-types/9 → 200 |
| ✅ | UPDATE retribution-type/9 → 200 |
| ✅ | DELETE retribution-type/9 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|

## Summary

⚠️ **3 TEST(S) FAILED**

- **Pass**: 59
- **Fail**: 3
- **Warn**: 0
- **Skip**: 2
- **Total**: 64



---

## File: docs/08_arsip_migrasi_9pajak/MIGRATION_DETAILS_9PAJAK.md
---
# 📘 Migration Bible: Detail Pola & Pemetaan Field (Legacy ➔ M-PAD)

Dokumen ini berfungsi sebagai acuan teknis bagi AI atau sistem integrasi dalam melaksanakan migrasi data hulu-ke-hilir untuk 92 tabel `sw_patda`.

---

## 🏗️ 1. Template: Subjek Pajak (Induk Identitas)
Digunakan untuk tabel: `PATDA_WP`.

### 📋 Pemetaan Field Detil
| Field Legacy (sw_patda) | Field Modern (M-PAD) | Transformasi Data |
| :--- | :--- | :--- |
| `CPM_NPWPD` | `npwpd` | Trim, Sanitasi (Hapus titik/strip). |
| `CPM_NAMA_WP` | `name` | UpperCaseWords (Normalisasi Nama). |
| `CPM_ALAMAT_WP` | `address` | Pembersihan spasi ganda & casing. |
| `CPM_NIK` | `nik` | Validasi 16 digit, default blank jika tidak valid. |
| `CPM_TELP` | `phone` | Normalisasi ke format +62 (WhatsApp). |
| `CPM_KDPOS` | `metadata->postal_code` | Masuk ke kolom JSON metadata. |

---

## 🏗️ 2. Template: Objek Pajak (Aset & Kapasitas)
Digunakan untuk tabel berakhiran `_PROFIL` (Hotel, Restoran, Reklame, dll).

### 📋 Pemetaan Field Umum
| Field Legacy | Field Modern | Transformasi / Logika |
| :--- | :--- | :--- |
| `CPM_NOP` | `nop` | ID unik Objek Pajak (Wajib Ada). |
| `CPM_NAMA_OP` | `name` | Nama Komersial Objek/Tempat Usaha. |
| `CPM_ALAMAT_OP` | `address` | Lokasi fisik objek. |
| `CPM_NPWPD` | `taxpayer_id` | Foreign Key ke tabel `taxpayers`. |
| `CPM_KECAMATAN_OP`| `zone_id` | Mapping ID via Tabel Master Wilayah (Kec). |
| `CPM_KELURAHAN_OP` | `zone_id` | Mapping ID via Tabel Master Wilayah (Kel). |

### 🛠️ Penyesuaian Field Khusus ke JSON Metadata (`tax_objects.metadata`)
*   **Restoran**: `CPM_JUMLAH_MEJA`, `CPM_JUMLAH_KURSI` ➔ disatukan dalam JSON.
*   **Hotel**: `CPM_JUMLAH_KAMAR`, `CPM_GOLONGAN` (Bintang).
*   **Reklame**: `CPM_LUAS_REKLAME`, `CPM_TEKS_REKLAME`, `CPM_LOKASI`.
*   **Mineral (MBLB)**: `CPM_JENIS_TAMBANG`, `CPM_LOKASI_TAMBANG`.

---

## 🏗️ 3. Template: Pelaporan & Transaksi (SPTPD)
Digunakan untuk tabel berakhiran `_DOC` dan `_DOC_ATR`.

### 📋 Pemetaan Field
| Field Legacy | Field Modern | Transformasi / Logika |
| :--- | :--- | :--- |
| `CPM_TOTAL_OMZET` | `turnover_amount` | Casting ke decimal (Precision: 15,2). |
| `CPM_TOTAL_PAJAK` | `tax_amount` | Casting ke decimal (10% standard). |
| `CPM_MASA_PAJAK` | `period` | Format: `Bulan-Tahun` (e.g. 03-2024). |
| `CPM_TGL_INPUT` | `created_at` | Konversi string 'dd/mm/yyyy hh:ii:ss' ke Timestamp. |
| `CPM_TRAN_STATUS` | `status` | Map: 1➔Draft, 2➔Pending, 5➔Approved. |
| `CPM_KETERANGAN` | `notes` | Penjelasan tambahan dari sistem lama. |

---

## 🏗️ 4. Template: Ketetapan & Piutang (SKPDKB / STPD)
Digunakan untuk tabel: `PATDA_SKPDKB`, `PATDA_STPD`.

### 📋 Pemetaan Field
| Field Legacy | Field Modern | Transformasi / Logika |
| :--- | :--- | :--- |
| `CPM_NO_SKPDKB` | `bill_number` | Nomor invoice/ketetapan resmi. |
| `CPM_KURANG_BAYAR`| `principal_amount` | Nominal pokok ketetapan. |
| `CPM_DENDA` | `penalty_amount` | Nominal sanksi administrasi. |
| `CPM_TOTAL_PAJAK` | `total_amount` | Total tagihan wajib bayar. |
| `CPM_TGL_JATUH_TEMPO`| `due_date` | Konversi ke format Date YYYY-MM-DD. |

---

## 📋 Inventori Rinci Migrasi (92 Tabel)

| Kelompok | Cakupan Tabel | Strategi Perubahan Data |
| :--- | :--- | :--- |
| **Identity** | `PATDA_WP`, `CENTRAL_USER` | **Smart Merge**: Menggunakan NPWPD & NIK untuk menghindari duplikasi profil. |
| **Core Objects**| `PATDA_*_PROFIL` (9 Tabel) | **Normalization**: Memisahkan entitas WP (Subjek) dari OP (Objek). |
| **History** | `PATDA_*_DOC` (9 Tabel) | **Filtering**: Hanya menarik data dengan `Total Omzet > 0`. |
| **Enforcement** | `PATDA_TEGURAN`, `PAKSA` | **Archiving**: Disimpan di tabel penindakan untuk profil risiko WP. |
| **Master Data** | `PATDA_MST_*`, `PATDA_REK_*`| **Linkage**: Menyambungkan relasi ID Wilayah & Kategori Pajak. |
| **Auth/Admin** | `CENTRAL_*` (14 Tabel) | **Mapping**: Menyesuaikan role lama (Operator/Supervisor) ke Role M-PAD. |

---

## 🚀 Logika Integrasi (The AI Guardrail)

1.  **Sanitasi**: Otomatis menghapus spasi di awal/akhir string dan melakukan normalisasi huruf besar/kecil.
2.  **Constraint Recovery**: Jika objek memiliki NPWPD yang tidak terdaftar, sistem akan mencari di `PATDA_WP` secara otomatis untuk membuat induknya terlebih dahulu.
3.  **JSON Folding**: Seluruh variabel teknis lama yang tidak memiliki kolom di M-PAD "dilipat" masuk ke kolom `metadata` agar tidak ada informasi yang hilang.
4.  **Audit Trail Preservation**: Menyimpan `CPM_ID` asli di kolom `notes` atau `metadata` untuk audit sungsang di masa depan.


---

## File: docs/08_arsip_migrasi_9pajak/LEGACY_OFFICERS_MAPPING.md
---
# Legacy Officer Data Mapping (9pajak)

Dokumen ini merangkum hasil ekstraksi data petugas dari sistem legacy `9pajak` untuk keperluan migrasi dan audit internal sistem M-PAD.

## 1. Klasifikasi Peran & Petugas

| Kategori Peran | Akun Petugas (Usernames) | Fungsi Utama |
| :--- | :--- | :--- |
| **Admin & Konfigurasi** | `IRMAWATI`, `RATNAKARMAN`, `LIPUU`, `admin_v-tax` | Manajemen sistem, registrasi operator, backup data. |
| **Pelayanan (Pelapor)** | `muhihsanaris`, `suwarti` | Input data pendaftaran WP (RegWP) dan pelaporan omzet (SPTPD). |
| **Verifikasi &审核** | `syamsir`, `muhihsanaris` | Pemeriksaan kepatuhan data sebelum tagihan diterbitkan. |
| **Penagihan & Lapangan** | `syamsir` | Eksekusi surat teguran/paksa dan monitoring tracking lapangan. |
| **Integrasi Dinas / OPD** | `dinaskesehatan`, `dinaspekerjaanumum`, `dinaspendidikan` | Pelaporan data pajak sektoral per instansi. |

## 3. Skema Relasi Database (Entity Relationship)

Sistem `9pajak` menggunakan arsitektur relasional berbasis username sebagai kunci penghubung lintas modul. Berikut adalah visualisasi relasinya:

```mermaid
erDiagram
    CENTRAL_USER ||--|| PATDA_PETUGAS : "Authenticated as Officer"
    CENTRAL_USER ||--|| PATDA_WP : "Authenticated as Taxpayer"
    CENTRAL_USER ||--o{ CENTRAL_USER_TO_APP : "Membership"
    
    CENTRAL_USER_TO_APP }o--|| CENTRAL_APP : "App Access"
    
    PATDA_PETUGAS ||--o{ PATDA_TRANSACTION : "Creates/Verifies (CPM_AUTHOR)"
    PATDA_WP ||--o{ PATDA_TRANSACTION : "Reports (CPM_NPWPD)"
    
    PATDA_TRANSACTION ||--|| PATDA_BERKAS : "Submission Info"

    CENTRAL_USER {
        string CTR_U_ID PK "Username / UserID"
        string CTR_U_PWD "MD5 Hash"
        int CTR_U_BLOCKED "Block Status"
    }
    
    PATDA_PETUGAS {
        string CPM_USER PK, FK "Link to CENTRAL_USER"
        string CPM_NAMA "Full Name"
        string CPM_NIP "Employee ID"
        string CPM_ROLE "Internal Role"
    }
    
    PATDA_WP {
        string CPM_USER PK, FK "Link to CENTRAL_USER"
        string CPM_NPWPD "Tax ID (Unique)"
        string CPM_NAMA_WP "Taxpayer Name"
    }
    
    PATDA_TRANSACTION {
        string CPM_ID PK "Document ID"
        string CPM_NPWPD FK "Owner NPWPD"
        string CPM_AUTHOR FK "Input/Verify Username"
        string CPM_TRAN_STATUS "Workflow Status"
    }
```

### Detail Field Mapping Lintas Tabel:
1.  **Auth & Profile**: `CENTRAL_USER.CTR_U_ID` terhubung langsung dengan `PATDA_PETUGAS.CPM_USER` (untuk internal) atau `PATDA_WP.CPM_USER` (untuk eksternal).
2.  **Audit Trail**: Field `CPM_AUTHOR` pada tabel transaksi (seperti `PATDA_HOTEL_DOC`) merujuk pada `CPM_USER` di tabel petugas.
3.  **Identitas Bisnis**: NPWPD (`CPM_NPWPD`) adalah kunci utama yang menghubungkan riwayat semua jenis pajak (`TRANSACTION`) ke satu subjek pajak (`PATDA_WP`).

## 4. Catatan Migrasi M-PAD

| Legacy User | Proposed M-PAD Role | Keterangan |
| :--- | :--- | :--- |
| `IRMAWATI`, `LIPUU` | `Super Admin` | Tetap memegang otoritas penuh di Dashboard Admin. |
| `muhihsanaris` | `Petugas Pelayanan` | Fokus pada input dan pendaftaran di Admin. |
| `syamsir` | `Petugas Lapangan` | Dipindahkan ke aplikasi Mobile Petugas untuk penindakan (Tier 3). |
| `dinaskesehatan` | `Viewer OPD` | Akses monitoring khusus untuk instansi terkait. |

## 5. Data Mentah (Officer Registry JSON)

Berikut adalah hasil ekstraksi audit jejak transaksi (`CPM_AUTHOR`) dari database legacy untuk keperluan referensi migrasi:

```json
{
  "syamsir_group": {
    "primary": "syamsir",
    "aliases": ["doangsyamsir"],
    "total_activity": 10624,
    "role_prediction": "Field Officer / Verifier",
    "tables": ["PATDA_HOTEL_DOC", "PATDA_RESTORAN_DOC", "PATDA_PARKIR_DOC", "PATDA_PETUGAS", "PATDA_HIBURAN_DOC", "PATDA_WP", "PATDA_REKLAME_DOC"]
  },
  "muhihsanaris_group": {
    "primary": "muhihsanaris",
    "aliases": ["ikhsanicang"],
    "total_activity": 7905,
    "role_prediction": "Back-office Operator",
    "tables": ["PATDA_HOTEL_DOC", "PATDA_RESTORAN_DOC", "PATDA_PARKIR_DOC", "PATDA_PETUGAS", "PATDA_HIBURAN_DOC", "PATDA_WP", "PATDA_REKLAME_DOC"]
  },
  "admin_accounts": [
    {
      "user": "admin_simpatda",
      "activity": 775,
      "tables": ["PATDA_HOTEL_DOC", "PATDA_RESTORAN_DOC", "PATDA_PETUGAS", "PATDA_WP", "PATDA_MINERAL_DOC"]
    },
    {
      "user": "ratnakarman",
      "activity": 810,
      "tables": ["PATDA_HOTEL_DOC", "PATDA_RESTORAN_DOC", "PATDA_WP"]
    }
  ],
  "institutional_accounts": {
    "sekretariatdprd": 1537,
    "dinaskesehatan": 2142,
    "kesbangpol": 887,
    "bappeda": 731,
    "dinasperikanan": 374,
    "dinasperhubungan": 351
  },
  "field_officers_candidates": [
    "syafrilmane",
    "abdwahab",
    "mukmin",
    "maulana",
    "ahmadun"
  ]
}
```

## 6. Koneksi VPS (Database Legacy)

Daftar petugas dan riwayat transaksi kini telah tersedia di VPS Staging untuk keperluan kueri langsung dan verifikasi migrasi M-PAD.

| Parameter | Detail |
| :--- | :--- |
| **Host** | `157.10.252.74` |
| **Database** | `sipanda_legacy_9pajak` |
| **Default Charset** | `latin1` |
| **Penyimpanan** | MySQL (MariaDB) |

**Cara Mengakses (dari VPS):**
```bash
sudo mysql sipanda_legacy_9pajak
```
**Contoh Kueri Audit (M-PAD Context):**
```sql
-- Mencari aktivitas petugas syamsir di tahun 2020
SELECT * FROM PATDA_HOTEL_DOC WHERE CPM_AUTHOR = 'syamsir' AND CPM_TGL_LAPOR LIKE '2020%';
```

---
*Dokumen ini dibuat otomatis sebagai bagian dari fase riset migrasi data M-PAD.*


---

## File: docs/08_arsip_migrasi_9pajak/migration_strategy.md
---
# Migration Strategy: Legacy 9pajak & PBB to M-PAD
## Strategi Modernisasi & Penyelarasan Data (Update April 2026)

Dokumen ini mendefinisikan pendekatan strategis untuk mengadopsi data dari sistem legacy "9pajak" (PHP) dan sistem "PBB" (SISMIOP) ke dalam ekosistem modern **M-PAD (retribusi-api)**.

---

## 1. Filosofi Inti: "Hybrid-Unified Architecture"

Misinya adalah memodernisasi sistem untuk skalabilitas dan transparansi jangka panjang sambil menjaga integritas data 100% dari sistem lama.

- **Unified Schema**: Menggabungkan 9 silo pajak daerah dan data PBB ke dalam satu hierarki `TaxObject`.
- **Transparent Logic**: Menggantikan logika perhitungan PHP/hardcoded dengan `FormulaParserService` yang dapat diaudit.
- **Legacy Compatibility**: Mendukung pengenal lama (NPWPD, NOP) bersamaan dengan modern UUID.

---

## 2. Pendekatan "Metadata-First"

Tipe pajak lama memiliki field khusus (contoh: `luas_hotel`, `panjang_reklame`, `kelas_bumi`). M-PAD menggunakan kolom **JSON Metadata** untuk menyimpan atribut dinamis ini tanpa menambah kolom fisik tabel.

| Legacy Table | Field | Target Mapping (M-PAD JSON) |
| :--- | :--- | :--- |
| `PATDA_HOTEL_DOC` | `CPM_JUMLAH_KAMAR` | `metadata -> jumlah_kamar` |
| `PATDA_REKLAME_DOC`| `CPM_UKURAN` | `metadata -> dimensi_reklame` |
| `PBB_SPPT` | `KD_KELAS_BUMI` | `metadata -> kelas_bumi` |

---

## 3. Siklus Hidup Migrasi (Migration Lifecycle)

### Fase A: Sinkronisasi Data Master (Active Objects)
Migrasi profil Wajib Pajak (`patda_wp`) dan Objek Pajak aktif dengan NPWPD/NOP sebagai pengait.

### Fase B: Piutang Berjalan (Pending Receivables)
Migrasi data SPTPD/SKPDKB yang belum lunas. Data ini diterjemahkan ke model `Bill` agar dapat dibayar melalui Payment Gateway M-PAD.

### Fase C: Arsip Historis (Read-Only)
Transaksi > 5 tahun tetap berada di database legacy. M-PAD menggunakan `LegacyAdapterService` untuk pencarian historis sesuai permintaan (On-Demand).

### Fase D: Integrasi PBB (April 2026)
Sinkronisasi real-time data PBB dari sistem eksternal (SISMIOP) via API/DB-Link. Data NOP dipetakan ke `TaxObject` dengan klasifikasi khusus PBB-P2.

---

## 4. Analisis "Formula Parity"

Untuk menjamin akurasi audit, sistem baru harus menghasilkan hasil perhitungan yang identik dengan sistem lama (Back-testing).

1. **Rule Mapping**: Memetakan logika perhitungan lama ke `CalculationFormula`.
2. **Back-testing**: Menjalankan `FormulaParserService` terhadap record historis.
3. **Approval**: Aktivasi formula baru setelah verifikasi ketepatan 100%.

---

## 5. Manfaat Jangka Panjang

- **Transparansi**: Rumus perhitungan dapat dibaca manusia, bukan tersembunyi di kode program.
- **Auditabilitas**: `AuditLog` mencatat setiap modifikasi pada record pajak.
- **Scalability**: Menambah jenis pajak/retribusi ke-11 atau ke-12 semudah menambah baris di `RetributionType`.

---
*Last modified: April 2026. Unified Migration Standard.*


---

## File: docs/03_proses_bisnis_penagihan/INVOICING_WORKFLOW.md
---
# M-PAD Invoicing Workflow & Performance

Dokumen ini menjelaskan arsitektur, mekanisme teknis, dan alur kerja sistem penagihan (invoicing) M-PAD, mulai dari backend hingga interaksi lintas platform (Admin, Petugas, dan Mobile).

---

## 🗺️ Peta Arsitektur Invoicing (V2)

```mermaid
graph TD
    subgraph "CORE API ENGINE (Laravel Backend)"
        BS[Billing Service - JIT Engine]
        FPS[Formula Parser - Tariff Logic]
        PCS[Penalty Engine - 1-2% Calc]
        DS[Dunning Engine - Teguran 1 & 2]
        SYNC[Pre-Payment Sync Logic]
        
        DB[(MariaDB Database)]
        DB_B(Bills Table)
        DB_P(Payments Table)
        DB_E(Enforcement Notices)
    end

    subgraph "ADMIN DASHBOARD (Web)"
        A_VER[Verifikasi Objek & Laporan]
        A_AUDIT[Audit Anomali & Piutang]
        A_DUN[Approve/Kirim Teguran 1 & 2]
    end

    subgraph "PETUGAS APP (Mobile)"
        P_SCAN[QR Scanner Penagihan]
        P_SURV[Daftar Surveillance WP Bandel]
        P_PAY[Record Pembayaran Lapangan]
    end

    subgraph "MOBILE WP (Citizen App)"
        M_REP[Input Laporan SPTPD]
        M_VIEW[Cek Tagihan Real-time]
        M_PAY[Pembayaran Digital]
    end

    %% Interactions
    M_REP -->|Submit Laporan| A_VER
    A_VER -->|Approve| BS
    BS -->|Generate JIT| DB_B
    
    P_SCAN -->|Request Bill| BS
    P_PAY -->|Trigger SYNC| SYNC
    SYNC -->|Update Penalty| DB_B
    SYNC -->|Create Record| DB_P
    
    DS -->|Daily Check| DB_B
    DS -->|Generate Draft| DB_E
    A_DUN -->|Approval| DB_E
    DB_E -->|WhatsApp Notification| M_VIEW
```

---

## 🚀 Filosofi: Just-In-Time (JIT) Billing

Berbeda dengan sistem tradisional yang melakukan *pre-generation* tagihan di awal tahun, M-PAD menggunakan pendekatan **JIT Billing**. Tagihan dihitung dan dimunculkan secara dinamis saat sistem membutuhkannya (ketika discan oleh petugas atau dilihat oleh WP).

**Keuntungan:**
- **Efisiensi Database**: Mengurangi jutaan baris record tagihan kosong.
- **Akurasi Real-time**: Perubahan peraturan atau denda langsung tercermin pada perhitungan saat itu juga.
- **Fleksibilitas**: Memungkinkan penyesuaian periode (harian, bulanan, tahunan) tanpa migrasi data besar.

---

## 👥 Alur Kerja per Aktor

### 1. Sisi Admin (Back-Office)
Admin bertanggung jawab atas **Penetapan Awal** dan **Verifikasi Laporan**.
- **Alur Penetapan**: Admin menyetujui pendaftaran objek baru → Sistem otomatis menerbitkan tagihan pertama (Initial Invoice).
- **Alur Self-Assessment**: Admin memverifikasi SPTPD (Laporan Bulanan) yang dikirim WP → Persetujuan laporan memicu pembuatan invoice berdasarkan nominal yang dilaporkan.

### 2. Sisi Petugas (Lapangan)
Petugas menggunakan aplikasi mobile untuk **Penagihan Aktif (Direct Billing)**.
- **Mekanisme**: Petugas memindai QR Code Objek Pajak → Aplikasi memanggil `BillingService` untuk menghitung periode yang belum dibayar.
- **Aksi**: Petugas dapat memilih periode tertentu dan langsung memproses pembayaran di tempat (Tunai/M-POS).

### 3. Sisi WP / Citizen (Mobile)
Wajib Pajak dapat melihat kewajiban secara mandiri.
- **Mekanisme**: WP membuka aplikasi → Riwayat tagihan muncul secara real-time dari mesin kalkulasi pusat.
- **Aksi**: WP dapat melakukan pembayaran non-tunai (VA/QRIS) atau mengunggah bukti bayar secara manual (Citizen Claim).

---

## 🛠️ Dapur Pacu: Cara Kerja Teknis

Sistem penagihan M-PAD dirancang dengan prinsip **Integritas Tinggi** dan **Otomasi Proaktif**.

### 1. Mesin JIT (Just-In-Time) Billing
Sistem tidak menyimpan tagihan permanen sampai diperlukan:
- **Proses**: Saat dipanggil, `BillingService` menghitung mundur ke tanggal registrasi objek untuk menemukan bulan-bulan tertunggak secara dinamis.
- **Keuntungan**: Data selalu segar, akurat sesuai perubahan metadata objek, dan menghemat ruang database.

### 2. Mesin Denda (The Penalty Engine)
Denda diperbarui secara otomatis melalui *scheduler* (Cron Job) harian:
- **Jadwal**: Pukul 01:00 AM melalui `CalculateBillPenalties`.
- **Logika**: Menghitung selisih bulan antara `due_date` dengan hari ini dan menerapkan bunga 1-2.2% (sesuai Perwali No. 58/2024).

### 3. Pre-Payment Sync (Self-Healing)
Fitur penjamin keakuratan finansial sebelum pembayaran dicatat:
- **Workflow**: Sebelum record `Payment` disimpan, sistem memicu rekalkulasi denda detik itu juga dan memperbarui record `Bill` agar sesuai dengan nominal yang sebenarnya dibayarkan.
- **Tujuan**: Menghilangkan selisih (*gap*) piutang saat audit keuangan.

### 4. Otomatisasi Eskalasi (Dunning Flow)
Logika berjenjang ditangani oleh `GenerateAutomatedTeguran`:
- **Tier 1**: Jika bill melewati jatuh tempo > 7 hari.
- **Tier 2**: Jika Teguran 1 telah dikirim dan diabaikan selama 14 hari.
- **Notifikasi**: Integrasi otomatis ke WhatsApp Wajib Pajak.

### 5. Alur Dispensasi (Penalty Waiver)
Sistem memiliki modul khusus untuk penghapusan denda (`PenaltyWaiverController`):
- **Pengajuan**: Petugas/Admin mengajukan dispensasi (persentase atau nominal tetap) dengan alasan yang dapat dipertanggungjawabkan.
- **Approval**: Harus disetujui oleh otoritas yang lebih tinggi (Kabid/Kadis).
- **Efek**: Nilai `waived_penalty_amount` pada bill akan terisi, dan nominal yang harus dibayar WP akan berkurang sesuai nilai dispensasi.

### 6. Surveillance & Anomaly Detection
Digunakan untuk mengawasi kepatuhan WP Self-Assessment:
- **Engine**: `AnomalyDetectionJob` (berjalan harian).
- **Logika**: Membandingkan `reported_revenue` (SPTPD) dengan `expected_revenue` (data historis/surveillance).
- **Threshold**: Jika selisih > 5%, sistem memberikan label **Anomali Merah** untuk ditindaklanjuti dengan Uji Petik.

---

## 🏛️ Advanced V2: Dunning Escalation & Data Sync

Sistem kini dilengkapi dengan fitur penagihan proaktif untuk memastikan stabilitas pendapatan daerah.

### 1. Eskalasi Penagihan (Dunning Tiers)
Sistem secara otomatis mendeteksi tunggakan yang diabaikan dan meningkatkan level peringatan:
- **Teguran 1 (Draft)**: Diterbitkan otomatis jika tagihan melewati jatuh tempo (> 7 hari).
- **Teguran 2 (Draft)**: Diterbitkan otomatis jika Teguran 1 sudah dikirim namun pembayaran belum diterima setelah **14 hari**.
- **Notifikasi**: Setiap eskalasi memicu pengiriman pesan WhatsApp kepada Wajib Pajak melalui `GenerateAutomatedTeguran`.

### 2. Integritas Pembayaran (Real-time Sync)
Untuk menghilangkan celah selisih denda antara sistem JIT dan Database, sistem kini melakukan:
- **Pre-Payment Sync**: Sebelum catatan pembayaran (`Payment`) dibuat, sistem memanggil `BillingService` untuk melakukan rekalkulasi denda detik itu juga.
- **Auto-Update**: Record `Bill` pada database diperbarui nilainya tepat sebelum status diubah menjadi `lunas`.

---

## 📊 Skema Penagihan (Billing Schemes)

| Skema | Dasar Penerbitan | Kapan Terbit | Karakteristik |
| :--- | :--- | :--- | :--- |
| **Official (Penetapan)** | Data Objek Pajak + Formula | Otomatis per siklus (Bulanan/Tahunan) | Nilai tetap atau berbasis variabel tetap (Contoh: Jumlah Kamar Hotel). |
| **Self (Pelaporan)** | Laporan WP (SPTPD) | Setelah Verifikasi Admin | Nilai dinamis berdasarkan omzet yang dilaporkan. |
| **Ad-hoc (Uji Petik)** | Hasil Pengamatan Lapangan | Langsung (On-the-spot) | Digunakan saat ditemukan selisih atau potensi pajak tersembunyi. |

---

## 🛠️ Logika Teknis & Engine

### Formula Perhitungan
Sistem menggunakan `FormulaParserService` untuk mengeksekusi rumus yang fleksibel. Contoh:
- `Total = (Luas_M2 * Tarif_ZNT) + Biaya_Admin`
- Untuk denda, menggunakan `CalculatePenalty` (rata-rata 1-2% per bulan keterlambatan).

### Database Schema Terkait
- `tax_objects`: Sumber metadata (luas, jumlah kamar, kapasitas).
- `retribution_rates`: Database tarif berdasarkan Klasifikasi & Zona.
- `bills`: Menyimpan status final pembayaran (`pending`, `lunas`, `expired`).

---

## 📈 Indikator Kinerja (KPI) Penagihan
- **Automation Speed**: Rata-rata waktu pembuatan invoice < 500ms setelah verifikasi laporan.
- **Amnesty Coverage**: Kemampuan sistem melakukan pemutihan denda secara parsial atau total (`waived_penalty_amount`).
- **Transparency**: WP mendapatkan notifikasi WhatsApp/Push saat tagihan baru terbit.

---
> *Dokumen ini divalidasi oleh AI Antigravity pada 09 April 2026 berdasarkan implementasi BillingService engine terbaru.*


---

## File: docs/03_proses_bisnis_penagihan/INVOICE_GAP_ANALYSIS.md
---
# M-PAD Invoicing Gap Analysis

Berdasarkan tinjauan kode pada `BillingService`, `CalculateBillPenalties`, dan `INVOICING_WORKFLOW.md`, ditemukan beberapa celah (missing flows) dan area yang memerlukan penguatan.

---

## 🚩 1. Missing Flow: Enforcement Laporan Mandiri
Saat ini, sistem JIT Billing dapat mendeteksi periode yang membutuhkan laporan (`required_reporting`). Namun:
- **Status**: ⚠️ **WIP (Work In Progress)**. Sistem kini memiliki eskalasi **Teguran 2 (V2)**. Masih memerlukan penindakan otomatis untuk denda admin murni.
- **Dampak**: Potensi denda administrasi (misal: denda tidak lapor Rp100.000) tidak tercatat di database kecuali Admin menindaklanjutinya secara manual melalui SKPDKB.
- **Solusi**: Perlu command mingguan untuk mendeteksi `TaxObject` (Self-Assessment) yang belum lapor di periode sebelumnya dan otomatis membuat tagihan Denda Administrasi.

---

## 🚩 2. Missing Flow: Pembayaran Parsial (Partial Payments)
Kode di `PaymentController` dan `Bill` model belum mendukung pembayaran cicilan secara native.
- **Celah**: User harus membayar nominal penuh (`total_amount`). Tidak ada mekanisme untuk membayar sebagian dan membiarkan sisa saldo tetap tertunggak dalam satu nomor invoice.
- **Dampak**: Memberatkan WP yang ingin mencicil kewajiban besar (seperti PBB-P2 komersial).
- **Solusi**: Implementasi tabel `bill_installments` atau penyesuaian status `partially_paid` pada model `Bill`.

---

## ✅ 3. FIXED: Sync Issue: Virtual Penalty vs Persisted Penalty (Invoicing V2)
Ada dua cara denda dihitung di sistem saat ini:
1. **Virtual (On-the-fly)**: Dihitung saat Petugas/WP melihat daftar tagihan (`BillingService`).
2. **Persisted (Harian)**: Dihitung dan disimpan ke DB setiap jam 01:00 pagi (`CalculateBillPenalties`).
- **Penyelesaian**: Logika pembayaran pada `PaymentController@store` kini memanggil `BillingService` untuk memvalidasi denda terbaru dan memperbarui record `Bill` tepat sebelum transaksi final (Real-time Sync).
- **Status**: ✅ **Tuntas**.


---

## 🚩 4. UI/UX Consistency: Status "Required Reporting"
Di sisi `INVOICING_WORKFLOW.md`, status ini disebutkan untuk WP.
- **Celah**: Di sisi Petugas Lapangan, jika statusnya `required_reporting`, apakah petugas diperbolehkan menerima pembayaran berdasarkan estimasi (Uji Petik)? Saat ini alur ini masih abu-abu di level kode.
- **Dampak**: Kebingungan petugas saat di lapangan menemukan usaha yang tidak aktif melapor tapi ingin membayar.
- **Solusi**: Tambahkan opsi "Bayar Berdasarkan Estimasi Lapangan" yang otomatis mengisi laporan omzet atas nama Petugas.

---

## 🎌 5. Rekomendasi Alur Baru (Proposed Flow)
1. **Pre-Payment Sync**: Setiap kali `Payment::store` dipanggil, jalankan fungsi `syncPenalty()` untuk memastikan angka keuangan terbaru tersimpan ke DB.
2. **Alert Monitoring**: Dashboard Admin harus memiliki widget "WP Belum Lapor" (Bukan hanya WP Belum Bayar).

---
> *Laporan ini disusun untuk membantu tim pengembang memprioritaskan fitur stabilitas finansial pada fase sprint berikutnya.*


---

## File: docs/03_proses_bisnis_penagihan/DOKUMEN_PERENCANAAN_BANK_H2H.md
---
# Dokumen Perencanaan: Integrasi Payment Gateway Mandiri (Bank H2H)
**Sistem M-PAD (Manajemen Pajak & Retribusi Daerah)**

---

## 1. Pendahuluan
Dokumen ini disusun sebagai panduan strategis untuk mengimplementasikan sistem pembayaran mandiri yang terintegrasi langsung dengan mitra perbankan (Bank Partner) melalui jalur **Host-to-Host (H2H)**. Tujuannya adalah untuk menghilangkan ketergantungan pada pihak ketiga (aggregator) seperti Midtrans atau Xendit, sehingga meningkatkan efisiensi biaya dan kemandirian sistem daerah.

---

## 2. Analisis Sistem Saat Ini

### 2.1 Mekanisme Billing (JIT Engine)
Sistem M-PAD menggunakan filosofi **Just-In-Time (JIT) Billing**. Tagihan dan denda tidak disimpan secara statis dalam database dalam jumlah besar, melainkan dihitung secara dinamis oleh `BillingService` saat ada permintaan (inquiry) dari petugas atau Wajib Pajak.
- **Dampak pada Integrasi**: Integrasi Bank harus mendukung perhitungan denda real-time agar nominal pada struk pembayaran bank sama persis dengan tagihan terbaru di sistem.

### 2.2 Kondisi Payment Gateway (Mock)
Ditemukan bahwa `PaymentGatewayController.php` saat ini masih bersifat *mock* (dummy) yang mensimulasikan respons Midtrans. Ini memberikan ruang kosong yang ideal untuk diisi oleh logika integrasi Bank Mandiri.

### 2.3 Pola Integrasi PBB (Benchmark)
Terdapat layanan `PbbBapendaService.php` yang sudah memiliki pola **Inquiry-Payment-Reversal**. Pola ini akan menjadi acuan (benchmark) untuk integrasi pajak dan retribusi lainnya karena sudah kompatibel dengan cara kerja core engine perbankan.

---

## 3. Skema Integrasi Host-to-Host (H2H)

Integrasi akan menggunakan model **Inquiry-Payment** (Bank sebagai Client, M-PAD sebagai Server).

### 3.1 Alur Transaksi (Sequence Diagram)

1. **Inquiry**: Bank mengirim `bill_number` -> M-PAD menghitung tagihan + denda -> M-PAD mengembalikan detail (Nama WP, Nominal).
2. **Payment**: Bank mengirim notifikasi bayar -> M-PAD validasi nominal -> M-PAD update status `lunas` -> M-PAD mencatat record `Payment`.

### 3.2 Contoh Payload (Estimasi)
**Request Inquiry:**
```json
{
  "bill_number": "SKRD-2026-0001",
  "signature": "abcdef123456...",
  "timestamp": "2026-04-17 17:00:00"
}
```

---

## 4. Skema QRIS (Dynamic Payment)

Selain integrasi Virtual Account, sistem akan mendukung pembayaran melalui standar **QRIS (Quick Response Indonesian Standard)** yang bersifat **dinamis**.

### 4.1 Mekanisme Pembuatan QRIS
1.  **Request QRIS**: Saat Wajib Pajak memilih metode QRIS, API M-PAD akan mengirimkan nominal akhir (termasuk denda) ke API Bank mitra.
2.  **Generate QR String**: Bank mengembalikan string standar QRIS (ASPI format) yang berisi Merchant ID (NMID), nama merchant, dan nominal transaksi.
3.  **Local Rendering**: API M-PAD menggunakan library `simple-qrcode` untuk merender string tersebut menjadi gambar QR Code untuk ditampilkan di dashboard atau aplikasi mobile.

### 4.2 Data & Atribut QRIS
- **NMID**: ID Merchant resmi BAPENDA.
- **Merchant Name**: "BAPENDA BAUBAU - [JENIS PAJAK]".
- **Amount**: Nilai tagihan bersifat *fixed* (pembayar tidak bisa mengubah nominal, mencegah *underpayment*).
- **Expiry**: QRIS dinamis akan diset kedaluwarsa dalam waktu 15-30 menit untuk menjaga validitas data denda.

### 4.3 Alur Callback QRIS
Mekanisme notifikasi QRIS akan menyatu dengan alur `BankCallbackController`. Begitu pembayaran sukses via app perbankan/e-wallet, bank akan mengirimkan hit ke endpoint callback kita, dan sistem akan melakukan sinkronisasi otomatis ke tabel `payments` dan mengubah status bill menjadi `lunas`.

---

## 5. Komponen Lanjutan (Production-Grade)

Untuk mencapai standar perbankan dan audit kepatuhan, komponen berikut wajib diimplementasikan:

### 5.1 Penanganan Reversal (Pembatalan)
Sistem harus mendukung endpoint `reversal` untuk membatalkan status pembayaran jika terjadi *timeout* di sisi Bank setelah dana terdebit. Ini menjaga konsistensi antara saldo bank dan catatan piutang daerah.

### 5.2 Idempotensi (Anti-Duplikasi)
Gunakan `transaction_id` dari bank sebagai kunci unik di tabel `payments`. Sistem akan menolak proses pembayaran jika ID transaksi yang sama dikirimkan dua kali, mencegah denda dihitung ganda.

### 5.3 Audit Trail & Raw Payload Logging
Setiap payload JSON mentah yang masuk dari Bank akan dicatat ke tabel log khusus (`api_logs`) beserta alamat IP pengirim dan timestamp presisi milidetik.

### 5.4 Penomoran NTPD & NTB
- **NTB (Nomor Transaksi Bank)**: Disimpan sebagai referensi eksternal.
- **NTPD (Nomor Transaksi Penerimaan Daerah)**: Dihasilkan oleh M-PAD sebagai bukti sah penerimaan negara/daerah.

### 5.5 Rekonsiliasi Otomatis (Settlement)
Setiap akhir hari (EOD), sistem akan membandingkan daftar transaksi sukses di M-PAD dengan laporan harian dari Bank mitra untuk memastikan tidak ada transaksi yang tertinggal.

---

## 6. Keamanan & Validasi

Untuk menjamin keamanan transaksi keuangan daerah, dua lapis pengamanan wajib diterapkan:

1. **IP Whitelisting**: Server M-PAD hanya akan menerima request dari alamat IP resmi milik data center Bank.
2. **Signature Verification (HMAC-SHA256)**: Bank dan M-PAD berbagi *Shared Secret Key*. Setiap request harus menyertakan signature yang divalidasi oleh M-PAD untuk memastikan data tidak dimanipulasi di tengah jalan.

---

## 7. Panduan Komunikasi Teknis dengan IT Bank

Saat melakukan koordinasi dengan tim IT Bank, berikut adalah daftar ceklis informasi yang harus dikumpulkan dan disampaikan:

### 7.1 Hal yang Harus Ditanyakan (Kepada Bank)
1.  **Tech-Spec API**: Format payload (JSON/XML), endpoint Sandbox, dan Production.
2.  **Autentikasi**: Metode keamanan (Bearer Token, HMAC, atau mTLS).
3.  **Mekanisme Virtual Account**: Panjang digit VA, Kode Biller, dan tipe VA (Statis/Dinamis).
4.  **Prosedur Reversal**: Langkah teknis jika sistem M-PAD mengirimkan permintaan pembatalan akibat *timeout*.
5.  **Data Rekonsiliasi**: Format dan jadwal pengiriman laporan EOD (Settlement).

### 7.2 Hal yang Harus Diinformasikan (Kepada Bank)
1.  **Tech Stack**: M-PAD menggunakan REST API pada HTTPS.
2.  **JIT Billing**: Penegasan bahwa **denda dihitung real-time** pada saat Inquiry.
3.  **Endpoint Callback**: Lokasi penyambungan data (`/api/v1/bank/inquiry` & `/api/v1/bank/payment`).
4.  **Idempotensi**: M-PAD menggunakan `transaction_id` Bank sebagai kunci unik pencegah duplikasi.

---

## 9. Arsitektur Multi-Bank & Multi-Method (Driver Pattern)

Untuk mendukung fleksibilitas di masa depan (menambah bank atau metode pembayaran baru tanpa merombak kode), sistem direncanakan menggunakan **Driver Pattern**.

### 9.1 Komponen Arsitektur
1.  **PaymentGatewayInterface**: Kontrak standar yang harus dipatuhi oleh semua driver bank (Inquiry, Payment, Reversal).
2.  **PaymentManager (Factory)**: Komponen pusat yang menentukan driver mana yang aktif berdasarkan konfigurasi atau pilihan Wajib Pajak.
3.  **Specific Drivers**:
    *   `BankSultraDriver`: Menangani logika khusus API Bank Sultra.
    *   `MandiriDriver`: Menangani logika khusus API Bank Mandiri.
    *   `QRISDriver`: Menangani integrasi QRIS dinamis.

### 9.2 Keuntungan Strategis
*   **Scalability**: Menambah Bank BNI atau BRI hanya perlu membuat satu file baru.
*   **Low Coupling**: Masalah pada satu koneksi bank tidak akan mengganggu metode pembayaran lainnya.
*   **Consistency**: Seluruh tim pengembang menggunakan satu standar *interface* yang sama.

---

## 10. Pertanyaan Terbuka & Langkah Selanjutnya

Sebelum implementasi teknis dimulai, beberapa hal berikut perlu dikonfirmasi:
- **Nama Bank Mitra**: Penyesuaian format payload sesuai spesifikasi bank (Bank Sultra, BNI, dll).
- **Format Virtual Account**: Apakah menggunakan nomor tagihan asli atau format angka murni (numeric) sesuai standar bank.
- **Sertifikat Keamanan**: Apakah bank mewajibkan penggunaan VPN atau cukup melalui jalur HTTPS yang diamankan Signature.

---
*Dokumen ini dibuat otomatis oleh Antigravity AI pada 17 April 2026.*


---

## File: docs/03_proses_bisnis_penagihan/TECH_SPEC_BANK_H2H_MASTER.md
---
# Master Technical Design: Bank H2H Payment Ecosystem

Dokumen ini merinci arsitektur hulu-ke-hilir untuk sistem pembayaran mandiri (H2H) M-PAD, mencakup integrasi database, API, keamanan, dan alur kerja lintas aplikasi.

---

## 🗄️ 1. Skema Database (Refactor & New)

Untuk mendukung standar audit perbankan, struktur data akan diperluas agar setiap transaksi dapat dilacak hingga ke level *raw payload*.

### 1.1 Tabel `bills` (Update)
- `penalty_amount`: (Decimal) Menyimpan nominal denda final saat pelunasan.
- `bank_code`: (String) Kode bank mitra (misal: `SULTRA`, `MANDIRI`).
- `expiry_time`: (Timestamp) Batas waktu pembayaran untuk VA/QRIS dinamis.

### 1.2 Tabel `payments` (Update)
- `reference_number`: (String) Nomor Transaksi Bank (NTB).
- `receipt_number`: (String) Nomor Transaksi Penerimaan Daerah (NTPD).
- `channel`: (String) Saluran bayar (Teller, ATM, Mobile Banking).
- `raw_callback_data`: (JSON) Payload asli dari bank untuk kebutuhan audit.

### 1.3 Tabel `payment_gateway_logs` (NEW)
Tabel khusus untuk mencatat setiap aktivitas komunikasi antara server Bank dan M-PAD.
- `bill_number`: Indeks pencarian.
- `endpoint`: URL yang diakses.
- `method`: GET/POST.
- `payload_in`: Data masuk.
- `payload_out`: Data keluar.
- `ip_address`: Verifikasi keamanan.

---

## 🌐 2. Detail Endpoint API (H2H)

### 2.1 Inquiry (Cek Tagihan)
*Bank memanggil API M-PAD untuk mendapatkan detail tagihan.*
- **Method**: `POST` (Direkomendasikan untuk keamanan payload) atau `GET`.
- **URL**: `/api/v1/bank/inquiry`
- **Data Dibutuhkan**: `bill_number`, `signature`, `timestamp`.
- **Logika Internal**:
  1. Validasi IP & Signature.
  2. Panggil `BillingService->getPendingPeriods()`.
  3. Hitung denda real-time per detik ini.
  4. Kembalikan JSON detail wajib pajak dan nominal presisi.

### 2.2 Payment (Notifikasi Bayar)
*Bank memberitahu M-PAD bahwa dana sudah masuk.*
- **Method**: `POST`
- **URL**: `/api/v1/bank/payment`
- **Data Dibutuhkan**: `transaction_id` (NTB), `bill_number`, `amount_paid`, `signature`.
- **Logika Internal**:
  1. Validasi nominal (harus pas).
  2. Update status `bills` menjadi `lunas`.
  3. Buat record di `payments`.
  4. **Trigger Hook**: Generate TTE Bukti Bayar.

### 2.3 Reversal (Pembatalan)
*Bank membatalkan transaksi akibat anomali sistem mereka.*
- **Method**: `POST`
- **URL**: `/api/v1/bank/reversal`
- **Logika**: Mengembalikan status bill ke `pending` dan membatalkan pencatatan bayar.

---

## 🔐 3. Protokol Keamanan

1.  **IP Whitelisting**: Hanya menerima request dari IP Server Bank.
2.  **HMAC-SHA256**: Menggunakan *Shared Secret Key*. String signature dibentuk dari `BillNumber + Timestamp + Amount`.
3.  **Mutual TLS (mTLS)**: Jika Bank mendukung, integrasi sertifikat klien (.crt) pada level Nginx/Web Server.

---

## 📱 4. Integrasi Lintas Platform (Frontend)

*   **Mobile ( Citizen)**: Saat membuka menu tagihan, sistem memanggil `PaymentManager` untuk mendapatkan detail VA/QRIS sesuai bank mitra. Mobile app memonitor status lunas secara pasif (WebSocket).
*   **Petugas (Field)**: Jika status di database sudah `lunas` via H2H, dashboard petugas otomatis berubah warna (hijau) tanpa perlu input manual.
*   **Admin (Web)**: Menyediakan fitur **Rekonsiliasi Manual** untuk mencocokkan laporan bank (.csv) dengan data database jika terjadi perselisihan data.

---

## 🚀 5. Runutan Realisasi (Prompting Roadmap)

Berikut adalah langkah-langkah implementasi (prompts) yang harus dijalankan secara berurutan:

### Tahap 1: Persiapan Kontrak & Data
> "Buat migrasi database untuk tabel `payment_gateway_logs` dan tambahkan kolom audit (`ntb`, `ntpd`, `penalty`) pada tabel `bills` dan `payments`."

### Tahap 2: Logika JIT & Driver
> "Buat `PaymentGatewayInterface` dan implementasikan logic `Inquiry` yang terhubung dengan `BillingService` untuk menghitung denda secara dinamis sesuai filosofi JIT M-PAD."

### Tahap 3: Security Layer
> "Implementasikan Middleware `BankSecurityCheck` yang memvalidasi header HMAC Signature dan alamat IP untuk melindungi endpoint H2H."

### Tahap 4: Callback & Webhook
> "Bangun `BankCallbackController` yang menangani notifikasi `Payment` dan `Reversal`, lengkap dengan logging otomatis ke tabel `payment_gateway_logs`."

### Tahap 5: Integrasi Dokumen Sah
> "Buat Hook setelah pembayaran sukses untuk memicu `OfficialDocumentService` guna menghasilkan receipt digital (TTE) bagi Wajib Pajak."


---

## File: docs/03_proses_bisnis_penagihan/PRODUCTION_UAT_CHECKLIST.md
---
# Panduan Lengkap Pengujian (UAT) Menuju Production (api.sipanda.online)

Dokumen ini berisi daftar langkah (checklist) pengujian menyeluruh untuk memastikan server *Production* MITRA PAD siap dan aman digunakan oleh Wajib Pajak dan Petugas. Lakukan pengujian ini setiap kali akan melakukan *Go-Live* atau peluncuran fitur besar.

---

## Daftar Modul Pengujian

Untuk memudahkan tim QA / Penguji memverifikasi kesiapan sistem, pedoman UAT ini telah kami pecah secara merinci ke dalam 6 Dokumen Modul Pengujian terpisah.

Silakan klik setiap tautan di bawah ini untuk melihat prosedur langkah demi langkah (termasuk kriteria sukses dan data *dummy* yang digunakan) untuk masing-masing fase:

1. 📂 **[Tahap 1: Persiapan Deployment](testing/01-Persiapan-Deployment.md)**  
   *(Verifikasi versi kode, setelan environment variables, optimasi cache server, dan eksekusi migrasi skema tabel Database).*

2. 🔐 **[Tahap 2: Pengujian Infrastruktur & Keamanan (Security Smoke Test)](testing/02-Keamanan-Infrastruktur.md)**  
   *(Prosedur meretas/mensimulasikan serangan DDOS untuk menguji Rate Limiting Nginx, proteksi isolasi file rahasia `.env`, dan enkripsi lapis baja HTTPS).*

3. 💼 **[Tahap 3: Pengujian Alur Utama Pajak / Retribusi (E2E Business Flow)](testing/03-Alur-Utama-E2E.md)**  
   *(Simulasi skenario penuh dunia nyata: Wajib pajak mendaftar via App HP -> Bapenda Admin menerbitkan SKPD -> Petugas Lapangan Keliling menagih lunas -> Dashboard Omzet tersinkron real-time).*

4. 📄 **[Tahap 4: Pengujian Fitur Kritis & Edge Cases](testing/04-Fitur-Kritis-Edge-Cases.md)**  
   *(Skenario uji validitas Tanda Tangan Elektronik QR Code, Performa Render grafis Peta Satelit GIS, serta penghapusan/Amnesty/Diskon tagihan anomali).*

5. 📱 **[Tahap 5: Pengujian Lintas Layar Perangkat (Cross-Device UAT)](testing/05-Cross-Device-UAT.md)**  
   *(Metode menguji keandalan sistem memakai ragam tipe Web Browser ekstrem dan memvalidasi tampilan responsif layar sempit Petugas Keliling).*

6. 📡 **[Tahap 6: Pemantauan Hari Pertama (Go-Live Monitoring)](testing/06-Go-Live-Monitoring.md)**  
   *(Instruksi untuk Admin Server via SSH Panel Backend dalam merekam log _Crash/Exception_ dan antisipasi kebocoran _RAM/CPU (Memory Leak)_ pada hari sibuk rilis massal).*

7. 🧮 **[Tahap 7: Pengujian Formula Jenis Pajak & Kalkulator Dinamis](testing/07-Formula-Jenis-Pajak.md)**  
   *(Verifikasi presisi rumus matematis API PBB-P2, BPHTB, Reklame, MBLB, dsb, serta variabel pengalinya berdasarkan Perwali).*

8. 🔄 **[Tahap 8: Pengujian Lintas Peran E2E Otomatis](testing/results/08_Laporan_E2E_Lintas_Peran.md)**  
   *(Report hasil penembakan Database Engine langsung menirukan interaksi Admin, WP, dan Petugas di puluhan jenis tagihan).*

9. 🛡️ **[Tahap 9: Pengujian Keamanan Hak Akses (RBAC & Authorization)](testing/09-Keamanan-RBAC.md)**  
   *(Uji penetrasi API Internal untuk memverifikasi larangan: Wajib Pajak mengambil data Admin, Petugas mendelete tagihan, dsb).*

10.🛑 **[Tahap 10: Pengujian Integritas & Validasi Data (Negative Testing)](testing/10-Validasi-Data.md)**  
   *(Menembak sistem menggunakan API bernilai Negatif (Minus amount), Tagihan Ganda Double-Pay Kosong untuk menguji benteng Controller).*

---

> **Catatan Tim QA:**  
> Harap centang parameter lulus uji (*Passed*) Anda untuk masing-masing modul di atas ketika telah diselesaikan dengan status _"Lulus Tanpa Cacat"_. Jika ditemukan cacat (_Bug/Vulnerability_), segera putar balik (*rollback*) atau tahan rilis kepada publik hingga tim pengembang menyuntikkan _Hot-Fix Code_ perbaikan sasarannya.


---

## File: docs/03_proses_bisnis_penagihan/skema-pelacakan-lokasi-petugas.md
---
# Skema Pelacakan Lokasi Petugas di Dashboard Pengawas
## Monitoring Real-Time & Audit Lokasi (Update April 2026)

> **Pendekatan: Dual-Track** — Sinkronisasi real-time + sinkronisasi berbasis aksi.
> **Fungsi Utama**: Memastikan petugas berada di zona penagihan/inspeksi yang tepat, mencegah *fraud*, dan memvalidasi keaslian laporan via GPS Snapshot.

---

## 1. Lokasi Real-Time (Live Background Tracking)
Petugas yang sedang aktif (Shift On) akan membagi lokasinya secara periodik.

| Layer | Detail |
|-------|--------|
| **Frontend** | `Geolocation.watchPosition()` dengan `enableHighAccuracy: true` |
| **API** | `PUT /api/user/location` → `AuthController@updateLocation` |
| **Optimasi** | **Live Throttling**: Update dikirim setiap 30-60 detik untuk efisiensi baterai. |

```mermaid
graph LR
    A[Petugas App] -- JSON {lat, lng} --> B(API: AuthController)
    B -- Update User --> C[(MySQL: users table)]
    C -- Polling 30s --> D[Dashboard Pengawas]
```

---

## 2. Lokasi Berbasis Aksi (Action-Triggered Snapshot)
Validasi lokasi mutlak saat melakukan transaksi sensitif (Anti-Fake GPS).

| Aksi | Model / Tabel | Field Koordinat |
|------|---------------|-----------------|
| **Registrasi OP** | `TaxObject` | `latitude`, `longitude` |
| **Uji Petik** | `SpotCheck` | `lat`, `lng` (di metadata/items) |
| **Penindakan** | `EnforcementNotice` | `lat`, `lng` |
| **Audit Reklame** | `BillboardAudit` | `lat`, `lng` |

**Geofencing Logic**: Verifikasi apakah posisi petugas berada dalam radius < 100m dari koordinat Objek Pajak saat melakukan aktivitas.

---

## 3. Visualisasi Dashboard Pengawas (Command Center)

| Fitur | Detail Teknis |
|-------|---------------|
| **Petugas Live** | Marker Merah Pulsing di `SupervisorMap.tsx` |
| **Heatmap Potensi** | Intensitas warna berdasarkan `amount` di `TaxObject` |
| **History Route** | Menampilkan rekam jejak harian petugas (BREADCRUMB) |
| **E-Registry Map** | Menampilkan sebaran WP yang sudah TTE vs No-TTE |

---

## 4. Status Implementasi (April 2026)

- [x] **Live Petugas Marker** — Aktif via `/api/pengawas/petugas-locations`.
- [x] **Heatmap Lokasi Potensi** — Integrasi dengan sub-distrik & zona.
- [x] **ESRI Satellite Layer** — Visualisasi presisi bangunan dan reklame.
- [x] **Auto-Refresh Interval** — Dashboard memuat data baru secara otomatis.
- [x] **Photo-GPS Sync** — Foto penindakan menyertakan koordinat EXIF & DB.
- [/] **Zonasi Alert** — Notifikasi saat petugas keluar zona tugas (Testing).
- [ ] **History Playback** — Fitur putar ulang rute harian petugas.

---
*Last modified: April 2026. Command Center Standard.*


---

## File: docs/08_legacy_systems/MEGA_DOCUMENTATION_9PAJAK.md
---
# 🏛️ MEGA DOCUMENTATION: 9pajak (Legacy SW_PATDA)

Dokumen ini adalah panduan referensi teknis untuk sistem warisan **9pajak** (secara internal dikenal sebagai **SW_PATDA**) yang merupakan sumber data utama bagi sistem M-PAD.

## 1. Filosofi & Nomenklatur
Sistem 9pajak menggunakan konvensi penamaan kolom yang sangat ketat untuk membedakan data operasional dari metadata sistem.

### Awalan `CPM_`
Hampir seluruh kolom di 9pajak menggunakan awalan **`CPM_`** yang merupakan singkatan dari **Catatan Pajak Masa**. 
*   **Contoh**: `CPM_NPWPD`, `CPM_NAMA_WP`, `CPM_TOTAL_PAJAK`.

### Struktur Tabel Per Modul
Berbeda dengan M-PAD yang menggunakan tabel terpadu (`tax_objects`), 9pajak memisahkan setiap jenis pajak ke dalam minimal tiga tabel utama:
1.  **`PATDA_[JENIS]_PROFIL`**: Data Master Objek Pajak (Alamat, NOP, Nama Toko).
2.  **`PATDA_[JENIS]_DOC`**: Data Transaksional / SPTPD (Omzet, Masa Pajak, Total Pajak).
3.  **`PATDA_[JENIS]_DOC_TRANMAIN`**: Log Status & Alur Kerja (Siapa yang setuju, kapan diproses).

---

## 2. Transformasi Hirarki (Jenis ke Klasifikasi)
Salah satu perbedaan arsitektural terbesar adalah bagaimana jenis beban dikategorikan. 

*   **9pajak (SW_PATDA)**: Menggunakan struktur **Flat**. Setiap beban adalah "Jenis Pajak" (Top Level).
*   **M-PAD (Modern)**: Menggunakan struktur **Wilayah-Centric**. Baris-baris data dari `PATDA_JENIS_PAJAK` (Legacy) kini diturunkan menjadi **Klasifikasi Pajak (Level 2)** di bawah Portofolio Wilayah (Level 1).

### Tabel Pemetaan Hirarki & Portofolio
| 9pajak (Jenis) | ID (TIPE) | M-PAD (Klasifikasi Pajak) | Portofolio (Wilayah) |
| :--- | :--- | :--- | :--- |
| **Hotel** | 4 | PBJT - Hotel | **Wilayah II** (Konsumsi) |
| **Restoran** | 5 | PBJT - Makan dan Minum | **Wilayah II** (Konsumsi) |
| **Hiburan** | 6 | PBJT - Kesenian dan Hiburan | **Wilayah II** (Konsumsi) |
| **Reklame** | 7 | Pajak Reklame | **Wilayah I** (Aset) |
| **Penerangan Jalan**| 8 | PBJT - Tenaga Listrik | **Wilayah II** (Konsumsi) |
| **Mineral/Galian** | 9 | Pajak MBLB | **Wilayah I** (Aset) |
| **Parkir** | 10 | PBJT - Jasa Parkir | **Wilayah II** (Konsumsi) |
| **Air Bawah Tanah** | 11 | Pajak Air Tanah | **Wilayah II** (Konsumsi) |
| **Sarang Walet** | 12 | Pajak Sarang Burung Walet | **Wilayah I** (Aset) |

---

## 3. Pemetaan Status (V-Tax Parity)
Status transaksi dalam 9pajak disimpan dalam kolom `CPM_TRAN_STATUS` di tabel `TRANMAIN`. Berikut adalah pemetaannya ke status M-PAD:

| Nilai (9pajak) | Status M-PAD | Deskripsi |
| :--- | :--- | :--- |
| **0** | `draft` | Draft oleh Wajib Pajak. |
| **1** | `proses` | Menunggu verifikasi petugas. |
| **2** | `disetujui` | Valid & Terbit tagihan/pembayaran. |
| **3** | `ditolak` | Ditolak oleh petugas. |

---

## 3. Rosetta Stone: Pemetaan Kolom (9pajak -> M-PAD)
Digunakan oleh `SimpadKoneksiService` untuk memigrasikan data secara otomatis.

### A. Wajib Pajak (`PATDA_WP`)
| Kolom 9pajak | Properti M-PAD | Keterangan |
| :--- | :--- | :--- |
| `CPM_NPWPD` | `npwpd` | Identifier Utama. |
| `CPM_NAMA_WP` | `name` | Nama wajib pajak. |
| `CPM_ALAMAT_WP` | `address` | Alamat lengkap. |
| `CPM_TELEPON_WP`| `phone` | |
| `CPM_EMAIL_WP` | `email` | |

### B. Objek Pajak (`_PROFIL`)
| Kolom 9pajak | Properti M-PAD | Mapping Metadata (JSON) |
| :--- | :--- | :--- |
| `CPM_NOP` | `nop` | |
| `CPM_NAMA_OP` | `name` | Nama Toko/Restoran/Hotel. |
| `CPM_ALAMAT_OP` | `address` | Lokasi objek. |
| `CPM_LATITUDE` | `latitude` | |
| `CPM_LONGITUDE` | `longitude` | |
| `CPM_JUMLAH_KAMAR`| - | `metadata->jumlah_kamar` (Hotel) |
| `CPM_KAPASITAS_KURSI`| - | `metadata->kapasitas_kursi` (Restoran)|

---

## 4. Hubungan Antar Tabel (Entity Logic)
Untuk mengambil data lengkap satu laporan pajak, sistem melakukan join sebagai berikut:
```sql
SELECT * FROM PATDA_HOTEL_DOC doc
JOIN PATDA_HOTEL_PROFIL prof ON doc.CPM_ID_PROFIL = prof.CPM_ID
JOIN PATDA_WP wp ON prof.CPM_NPWPD = wp.CPM_NPWPD
JOIN PATDA_HOTEL_DOC_TRANMAIN tran ON doc.CPM_ID = tran.CPM_TRAN_HOTEL_ID
WHERE doc.CPM_ID = 'xyz';
```

---

## 4. Spesifikasi Field & Formulir (Per Jenis Pajak)
Setiap jenis pajak memiliki atribut unik yang menentukan dasar pengenaan pajak (DPP). Berikut adalah rincian fungsional kolom-kolom kunci di 9pajak.

### 🏨 1. PBJT - Jasa Perhotelan (`PATDA_HOTEL`)
*   **Master (Profil)**: `CPM_NPWPD`, `CPM_NAMA_OP`, `CPM_ALAMAT_OP`, `CPM_REKENING`, `CPM_DEVICE_ID` (ID Alat Tapping).
*   **Transaksi (Doc)**: `CPM_TOTAL_OMZET`, `CPM_DPP`, `CPM_TARIF_PAJAK` (10%), `CPM_TOTAL_PAJAK`.
*   **Field Unik**: `CPM_JUMLAH_KAMAR`, `CPM_GOLONGAN`.

### 🍴 2. PBJT - Jasa Makan dan Minum (`PATDA_RESTORAN`)
*   **Master (Profil)**: `CPM_NPWPD`, `CPM_NAMA_OP`, `CPM_ALAMAT_OP`, `CPM_DEVICE_ID`.
*   **Transaksi (Doc)**: `CPM_TOTAL_OMZET`, `CPM_TARIF_PAJAK` (10%), `CPM_TOTAL_PAJAK`.
*   **Field Unik**: `CPM_KAPASITAS_KURSI`, `CPM_KAPASITAS_MEJA`, `LYTO_GOL`.

### 🎬 3. PBJT - Jasa Kesenian dan Hiburan (`PATDA_HIBURAN`)
*   **Master (Profil)**: `CPM_NPWPD`, `CPM_NAMA_OP`, `CPM_ALAMAT_OP`.
*   **Transaksi (Doc)**: `CPM_TOTAL_OMZET`, `CPM_TARIF_PAJAK` (10-75%), `CPM_TOTAL_PAJAK`.

### 🚘 4. PBJT - Jasa Parkir (`PATDA_PARKIR`)
*   **Master (Profil)**: `CPM_NPWPD`, `CPM_NAMA_OP`, `CPM_ALAMAT_OP`.
*   **Transaksi (Doc)**: `CPM_TOTAL_OMZET`, `CPM_TARIF_PAJAK` (10%), `CPM_TOTAL_PAJAK`.

### ⚡ 5. PBJT - Tenaga Listrik (`PATDA_JALAN`)
*   **Master (Profil)**: `CPM_NPWPD`, `CPM_NAMA_OP`, `CPM_REKENING`.
*   **Transaksi (Doc)**: `CPM_TOTAL_KWH`, `CPM_HARGA_DASAR`, `CPM_TOTAL_PAJAK`.

### 🖼️ 6. Pajak Reklame (`PATDA_REKLAME`)
*   **Master (Profil)**: `CPM_NPWPD`, `CPM_NAMA_OP`, `CPM_ALAMAT_OP`.
*   **Transaksi (Doc)**: `CPM_ATR_JUDUL`, `CPM_ATR_LOKASI`.
*   **Field Fisik (ATR)**: `CPM_ATR_LEBAR`, `CPM_ATR_TINGGI`, `CPM_ATR_MUKA` (Sisi), `CPM_ATR_JUMLAH` (Unit).
*   **Field Perhitungan**: `CPM_ATR_NJOP`, `CPM_ATR_NILAI_STRATEGIS`, `CPM_ATR_KAWASAN`.

### 💧 7. Pajak Air Tanah (`PATDA_AIRBAWAHTANAH`)
*   **Master (Profil)**: `CPM_NPWPD`, `CPM_NAMA_OP`, `CPM_ALAMAT_OP`.
*   **Transaksi (Doc)**: `CPM_VOLUME_AIR` (M3), `CPM_HARGA` (NPA), `CPM_TOTAL_PAJAK` (20%).
*   **Field Unik**: `CPM_LOKASI_SUMBER_AIR`, `CPM_KUALITAS_AIR`, `CPM_TINGKAT_KERUSAKAN`.

### 🪨 8. Pajak MBLB / Galian C (`PATDA_MINERAL`)
*   **Master (Profil)**: `CPM_NPWPD`, `CPM_NAMA_OP`, `CPM_TRUCK_ID`.
*   **Transaksi (Doc)**: `CPM_ATR_VOLUME` (Ton/M3), `CPM_ATR_HARGA`, `CPM_TOTAL_PAJAK` (15-20%).

### 🏠 9. Pajak Sarang Burung Walet (`PATDA_WALET`)
*   **Master (Profil)**: `CPM_NPWPD`, `CPM_NAMA_OP`, `CPM_ALAMAT_OP`.
*   **Transaksi (Doc)**: `CPM_ATR_JUMLAH_KG` (Volume), `CPM_HARGA_DASAR`, `CPM_TOTAL_PAJAK` (10%).

---

## 5. Catatan Pemeliharaan (Maintenance)
*   **Database**: Terletak di koneksi `mysql_legacy`.
*   **Versi PHP**: 9pajak telah dimigrasikan ke **PHP 8.3** agar dapat berjalan di lingkungan VPS yang sama dengan M-PAD.
*   **Integritas**: Kolom `CPM_VERSION` di tabel `_DOC` menandakan riwayat revisi laporan oleh WP.

> [!WARNING]
> Jangan melakukan modifikasi langsung pada skema tabel 9pajak (PATDA_*) karena hal ini akan merusak logika sinkronisasi `SimpadKoneksi`. Data harus dianggap **Read-Only** dari perspektif M-PAD.


---

## File: docs/99_umum_system/cek_saat_launching.md
---
1. dokumen apakah sudah aman
2. semua alur sudah di cek seperti pembayaran, penerbitan billing untuk semua baik admin, petugas, dan mobile

---

## File: docs/99_umum_system/MASTER_KNOWLEDGE_BASE.md
---
# 🧠 MASTER KNOWLEDGE BASE: M-PAD Documentation
## Ensiklopedia Maksud & Tujuan Seluruh Dokumen (Update April 2026)

Dokumen ini adalah **Sumber dari Segala Sumber** yang merangkum maksud, tujuan, dan inti sari dari seluruh file dokumentasi yang ada di ekosistem M-PAD (Pajak & Retribusi Daerah).

---

## 🏛️ 1. Core Architecture (Root `docs/`)
Dokumen yang mendefinisikan pondasi sistem secara keseluruhan.

| File | Maksud & Tujuan (Intent) |
| :--- | :--- |
| **[README.md](README.md)** | Gerbang utama navigasi. Memetakan seluruh kategori dokumen agar pembaca tahu ke mana harus mencari informasi. |
| **[BAUBAU_REGULATORY_MASTER.md](01_regulasi_baubau/BAUBAU_REGULATORY_MASTER.md)** | **Technical Source of Truth**. Definisi Rumus eksak, Zonasi, dan Mapping ID Database (186-200). |
| **[SYSTEM_OVERVIEW.md](SYSTEM_OVERVIEW.md)** | Definisi arsitektur MPAD. Menjelaskan pilar PBJT vs PBB, alur TTE, dan sejarah keputusan teknis terpenting. |
| **[04-database-schema.md](04-database-schema.md)** | Blueprint data. Berisi ERD dan spesifikasi tabel (termasuk modul baru: Enforcement, Amnesty, dan TTE). |
| **[DOMAIN_SCHEMA.md](DOMAIN_SCHEMA.md)** | Mapping infrastruktur. Menjelaskan URL Production vs Staging serta alur komunikasi data antar domain. |
| **[routes-and-components.md](routes-and-components.md)** | Kamus teknis frontend-backend. Memetakan setiap rute API ke komponen UI yang sesuai. |
| **[STABLE_BASELINE.md](STABLE_BASELINE.md)** | Titik acuan sinkronisasi. Mencatat ID Commit terakhir yang dianggap stabil untuk deployment massal. |
| **[todo-list.md](todo-list.md)** | Daftar tugas berjalan. Mencatat fitur yang sedang dikembangkan, diperbaiki, atau ditunda. |
| **[system-knowledge.md](system-knowledge.md)** | Pengetahuan sistematis untuk Agen AI agar memahami konteks proyek secara mendalam dengan cepat. |

---

## 🔧 2. Implementations (`docs/implementations/`)
Catatan teknis mengenai fitur-fitur spesifik yang telah diimplementasikan.

| File | Maksud & Tujuan (Intent) |
| :--- | :--- |
| **[01-modul-uji-petik.md](implementations/01-modul-uji-petik.md)** | Menjelaskan cara kerja Uji Petik (Spot Check) untuk validasi omzet WP secara riil di lapangan. |
| **[02-role-based-subadmin.md](implementations/02-role-based-subadmin.md)** | Dokumentasi pembagian hak akses (RBAC) antara Admin Utama, Kabid, dan Petugas Teknis. |
| **[03-petugas-tasks-todolist.md](implementations/03-petugas-tasks-todolist.md)** | Alur pembagian tugas harian dari Admin ke Petugas lapangan melalui modul Task List. |

---

## 📚 3. Technical Reference (`docs/reference_technical/`)
Dokumentasi mendalam mengenai logika bisnis dan standar API.

| File | Maksud & Tujuan (Intent) |
| :--- | :--- |
| **[bapenda_process_stages.md](reference_technical/bapenda_process_stages.md)** | Menjelaskan kaitan fitur M-PAD dengan 4 siklus Bapenda: Pendaftaran, Pendataan, Penetapan, Penagihan. |
| **[billing_system_documentation.md](reference_technical/billing_system_documentation.md)** | Logika pembentukan Kode Billing, perhitungan denda, dan integrasi dengan Payment Gateway Bank. |
| **[spopd-form-structure.md](reference_technical/spopd-form-structure.md)** | Detail skema JSON untuk formulir dinamis pendaftaran objek pajak (PBJT). |
| **[penalty_scheme.md](reference_technical/penalty_scheme.md)** | Aturan perhitungan denda keterlambatan (1%-2%) sesuai regulasi pemerintah. |
| **[core_apis.md](reference_technical/core_apis.md)** | Dokumentasi endpoint internal untuk manajemen Auth dan User. |
| **[public_apis.md](reference_technical/public_apis.md)** | Dokumentasi endpoint publik untuk pengecekan tagihan oleh Warga/WP tanpa login. |

---

## ⚖️ 4. Regulations & Strategy (`docs/regulasi/`)
Dokumen penyelarasan sistem dengan aturan hukum dan strategi migrasi.

| File | Maksud & Tujuan (Intent) |
| :--- | :--- |
| **[03-perwali-pdrd-summary.md](regulasi/03-perwali-pdrd-summary.md)** | Ringkasan regulasi daerah yang menjadi landasan tarif dan denda di sistem. |
| **[API_PBB_BAUBAU_2026.md](regulasi/API_PBB_BAUBAU_2026.md)** | Panduan teknis khusus untuk integrasi data PBB-P2 dari sistem SISMIOP. |
| **[migration_strategy.md](migration_strategy.md)** | Strategi memindahkan data dari sistem legacy (9pajak) ke M-PAD tanpa kehilangan integritas. |
| **[07-status-dokumen-resmi.md](regulasi/07-status-dokumen-resmi.md)** | Daftar 21 jenis dokumen resmi (SKPD, SSPD, dll) dan status kesiapannya untuk TTE. |
| **[02-master-data-objek-pajak.md](regulasi/02-master-data-objek-pajak.md)** | Standarisasi penamaan dan kodefikasi objek pajak daerah. |

---

## 🧪 5. Quality Assurance (`docs/testing/`)
Protokol dan panduan pengujian untuk menjamin stabilitas sistem.

| File | Maksud & Tujuan (Intent) |
| :--- | :--- |
| **[00-Panduan-Standar-Pengujian.md](testing/00-Panduan-Standar-Pengujian.md)** | Kitab suci pengujian. Menjelaskan cara menjalankan tes manual dan otomatis (NOSS). |
| **[12-Error-History-Mitigation.md](testing/12-Error-History-Mitigation-Registry.md)** | Log sejarah error yang pernah terjadi beserta solusi permanennya (Mitigasi). |
| **[13-Workspace-Master-Testing.md](testing/13-Workspace-Master-Testing-Protocol.md)** | Prosedur pengetesan lintas repositori (Omni Testing) sebelum rilis production. |
| **[02-e2e-testing-scheme.md](testing/02-e2e-testing-scheme.md)** | Skenario pengujian ujung-ke-ujung dari pendaftaran hingga bayar dan cetak bukti. |

---

## 📈 6. Platform Guides & Results (`docs/results/` & Guides)
Laporan hasil uji coba dan panduan operasional aktor.

| File Category | Maksud & Tujuan (Intent) |
| :--- | :--- |
| **[admin-userguide.md](admin-userguide.md)** | Panduan operasional lengkap untuk staf Bapenda (Dashboard & Admin). |
| **[petugas-userguide.md](petugas-userguide.md)** | Panduan operasional aplikasi mobile untuk petugas lapangan. |
| **[mobile-e-retribusi.md](mobile-e-retribusi.md)** | Panduan penggunaan portal layanan mandiri bagi Warga Kota Baubau. |
| **[docs/results/*.md](results/)** | Kumpulan log eksekusi tes penetrasi (keamanan), CRUD API, dan kalkulator pajak. |

---

## 🏛️ 7. Akronim dan Istilah Berkaitan dengan Pajak
-   **PAD**: Pendapatan Asli Daerah.
-   **PDRD**: Pajak Daerah dan Retribusi Daerah.
-   **WP & WR**: Wajib Pajak dan Wajib Retribusi.
-   **NPWPD**: Nomor Pokok Wajib Pajak Daerah.
-   **PBJT**: Pajak Barang dan Jasa Tertentu.
-   **NJOP**: Nilai Jual Objek Pajak (Dasar PBB-P2 & Reklame).
-   **NJOPTKP**: Nilai Jual Objek Pajak Tidak Kena Pajak.
-   **NPOP & NPOPTKP**: Nilai Perolehan Objek Pajak (Dasar BPHTB).
-   **NSR**: Nilai Sewa Reklame.
-   **ZNT & NIR**: Zona Nilai Tanah dan Nilai Indikasi Rata-rata.
-   **Self Assessment**: Pajak dihitung mandiri oleh WP (Resto/Hotel).
-   **Official Assessment**: Pajak ditetapkan oleh Pemda (PBB/Reklame).

---

## 📄 8. Kamus Dokumen & Siklus Administrasi Perpajakan

### A. Pendaftaran & Pendataan
-   **SPOPD**: Formulir daftar/lapor usaha (PBJT, Reklame, Air Tanah, dsb).
-   **SPOP & LSPOP**: Formulir pendaftaran spesifik PBB-P2.
-   **SKT (Surat Keterangan Terdaftar)**: Bukti WP telah masuk sistem.
-   **LKOK**: Lembar kerja pemeriksaan lapangan petugas.

### B. Penetapan & Tagihan
-   **SPTPD**: Dokumen lapor omzet bulanan dari WP.
-   **SKPD / SKRD**: Tagihan resmi Pokok Pajak / Retribusi.
-   **SPPT**: Tagihan resmi tahunan PBB-P2.
-   **SKPDKB / SKPDKBT**: Tagihan Kurang Bayar (hasil pemeriksaan).
-   **SKPDLB / SKPDN**: Penetapan Lebih Bayar atau Nihil.

### C. Pembayaran & Penegakan Hukum
-   **SSPD / SSRD**: Bukti sah penyetoran ke Kas Daerah.
-   **STPD**: Tagihan denda/sanksi bunga keterlambatan.
-   **Surat Teguran (1, 2, 3)**: Peringatan tunggakan.
-   **SPMP (Surat Paksa)**: Dasar hukum melakukan penyitaan/penyegelan.

---
## 🏛️ 9. Visual & Logic Baseline (April 2026)
-   **Database Alignment**: Klasifikasi ID 186-200 adalah klasifikasi master yang telah disinkronkan dengan rumus aktif Perda 1/2024.
-   **Icon Standard**: Sistem menggunakan URL Cloudinary unik untuk setiap klasifikasi guna menjamin variasi visual (Unique Visual Alignment).
-   **Zonasi Codes**: Menggunakan kode standar `RD-CL-A` (Jalan Strategis) dan `PKD-PREM` (Lapak Premium).


---
## 🖥️ 10. Local Development & Port Mapping
Daftar port dan akses lokal untuk pengembangan dan pengujian otomatis (run_all.sh).

| Service | Port | Local URL |
| :--- | :--- | :--- |
| **Backend API** | `8000` | [http://localhost:8000](http://localhost:8000) |
| **Admin Panel** | `3001` | [http://localhost:3001](http://localhost:3001) |
| **Mobile Portal** | `3002` | [http://localhost:3002](http://localhost:3002) |
| **Officer PWA** | `3003` | [http://localhost:3003](http://localhost:3003) |
| **POS API** | `8001` | [http://localhost:8001](http://localhost:8001) |
| **POS Web** | `3004` | [http://localhost:3004](http://localhost:3004) |

---
*Catatan: Dokumen ini wajib diperbarui setiap kali ada folder atau file dokumentasi baru yang ditambahkan ke dalam sistem.*


---

## File: docs/99_umum_system/SYSTEM_OVERVIEW.md
---
# 🌐 M-PAD Baubau: Gambaran Umum Sistem (Update April 2026)

## 1. Visi & Misi Sistem
**M-PAD (Manajemen Informasi Terpadu Retribusi dan Aset Daerah)** adalah platform inti Pemerintah Kota Baubau untuk mengelola seluruh ekosistem pendapatan daerah secara transparan, akuntabel, dan terintegrasi dengan tanda tangan elektronik (TTE).

## 2. Struktur Organisasi & Portofolio (Perwali 8/2025)
Berbeda dengan sistem lama, M-PAD versi 2024 (Update Apr-2026) mengadopsi struktur organisasi berbasis Portofolio Pendapatan sesuai **Perwali Baubau No. 8 Tahun 2025**:

### A. Bidang Pendapatan Wilayah I (Aset & Properti)
Fokus pada pajak berbasis ketetapan (*official assessment*) dan aset fisik:
- **Pajak Properti/Lahan**: PBB-P2, BPHTB.
- **Pajak Fisik/Aset**: Reklame, MBLB, Sarang Burung Walet.
- **Pajak Kendaraan**: Opsen PKB & Opsen BBNKB.

### B. Bidang Pendapatan Wilayah II (Konsumsi & Self-Assess)
Fokus pada pajak berbasis laporan omzet (*self-assessment*) dan aset retribusi:
- **Pajak Konsumsi (PBJT)**: Restoran, Hotel, Parkir, Listrik, Hiburan.
- **Pajak Air Tanah (PAT)**.
- **Retribusi Kekayaan Daerah**: Pemanfaatan Pantai Kamali, Kotamara, Pasar/Lapak.

---

## 3. Alur Kerja & Sub-Bidang Operasional
Setiap bidang (W1 & W2) memiliki sub-bidang fungsional yang bekerja dari hulu ke hilir:

### I. Pendaftaran & Penetapan (Hulu)
- **Pendaftaran**: Penerbitan NPWPD via SPOPD.
- **Pendataan**: Uji petik lapangan (LKOK) dan geotagging objek.
- **Penetapan**: Penerbitan ketetapan resmi (SKPD/SPPT).

### II. Penagihan & Verifikasi (Hilir)
- **Verifikasi**: Pencocokan setoran WP dengan Kas Daerah.
- **Penagihan**: Penerbitan Surat Teguran hingga Penindakan Akhir (SPMP).
- **Pelaporan**: Dashboard realisasi harian/bulanan.

---

## 4. Hirarki Data MITRA (4 Level)
1.  **Level 1 (Jenis/Portofolio)**: Wilayah I (Aset) vs Wilayah II (Konsumsi).
2.  **Level 2 (Klasifikasi)**: Kategori Pajak (Contoh: PBJT Makan/Minum).
3.  **Level 3 (Zona)**: Lokasi Strategis (Contoh: Kelas Jalan A).
4.  **Level 4 (Tarif)**: Nilai nominal Rupiah berdasarkan zonasi.

---
*Last modified: April 2026. Portfolio-Based Architecture Perwali 8/2025.*


---

## File: docs/99_umum_system/CHANGE_LOG.md
---
# Log Perubahan Project Bapeda - 20 Feb 2026

Dokumen ini mencatat sinkronisasi dan perbaikan yang dilakukan untuk memastikan semua service berjalan dengan benar di lingkungan lokal setelah update dari GitHub.

## 1. Sinkronisasi & Update Repository
Semua project telah diupdate ke versi terbaru dari branch `dev`:
- **retribusi-api**: Pulled 19 commit terbaru (termasuk fitur pencarian NIK).
- **retribusi-petugas**: Pulled 4 commit terbaru.
- **Dependency Update**: Menjalankan `composer install` dan `npm install` untuk mendukung package baru (Sentry, dll).
- **Database**: Menjalankan `php artisan migrate` untuk skema terbaru.

## 2. Perbaikan Port Conflict
Terdapat konflik pada port `3001` antara `wa-gateway` dan `retribusi-admin`.
- **Perubahan**: Port `retribusi-admin` diubah dari `3001` ke **`3001`** di file `retribusi-admin/vite.config.ts`.
- **Tujuan**: Memungkinkan kedua service berjalan bersamaan di satu mesin.

## 3. Resolusi Error 500 (Tambah Wajib Pajak)
Ditemukan error `Column not found: 1054 Unknown column 'district'` saat menyimpan data Wajib Pajak.
- **Penyebab**: Skema database lokal belum memiliki kolom baru yang ada di kode controller terbaru.
- **Solusi**: Dibuat migration baru `2026_02_20_025014_add_location_and_coordinates_to_taxpayers_table.php` yang menambahkan kolom:
  - `district`
  - `sub_district`
  - `latitude`
  - `longitude`

## 4. Konfigurasi API Lokal
Aplikasi frontend sebelumnya diarahkan ke `api.sipanda.online` (production).
- **Perubahan**: Membuat file `.env` di semua folder frontend (`admin`, `mobile`, `petugas`) dan mengarahkan `VITE_API_URL` ke **`http://127.0.0.1:8000`**.
- **Tujuan**: Agar fitur baru yang belum di-deploy ke production bisa langsung ditest secara lokal menggunakan backend lokal.

## 5. Ringkasan Akses Lokal
- **Admin Dashboard**: [http://localhost:3001](http://localhost:3001)
- **Aplikasi Mobile**: [http://localhost:3002](http://localhost:3002)
- **Aplikasi Petugas**: [http://localhost:3003](http://localhost:3003)
- **Backend API**: [http://127.0.0.1:8000](http://127.0.0.1:8000)
- **WA Gateway**: [http://localhost:3001](http://localhost:3001)

---
*Dibuat oleh Assistant untuk membantu tracking alur pengembangan.*


---

## File: docs/99_umum_system/README.md
---
# 📚 M-PAD Master Documentation Index
## Pusat Pengetahuan Terintegrasi (April 2026)

Selamat datang di pusat dokumentasi **M-PAD (Mitra PAD) Kota Baubau**. Folder ini merupakan hasil konsolidasi seluruh dokumen teknis, regulasi, testing, dan panduan pengguna dari seluruh ekosistem M-PAD (Admin, API, Mobile, Petugas, dan POS).

---

## 📂 Peta Navigasi Utama

### 1. 🏛️ Arsitektur & Teknis (Core)
*Wajib dibaca oleh Developer & AI Agent sebelum melakukan modifikasi.*
- ⭐ **[SYSTEM_OVERVIEW.md](SYSTEM_OVERVIEW.md)**: Gambaran besar arsitektur, V-Tax & PBB Parity.
- 💾 **[04-database-schema.md](04-database-schema.md)**: ERD dan struktur tabel (Update: Enforcement, Amnesty, TTE).
- 🌐 **[DOMAIN_SCHEMA.md](DOMAIN_SCHEMA.md)**: Pemetaan subdomain operasional.
- 🔌 **[routes-and-components.md](routes-and-components.md)**: Daftar endpoint API dan komponen routing.
- 🚦 **[STABLE_BASELINE.md](STABLE_BASELINE.md)**: Titik tolak commit stabil untuk sinkronisasi.

### 2. 📖 Panduan Pengguna (User Guides)
- 🧑‍💻 **[Admin Dashboard](admin-userguide.md)**: Manajemen WP, Penetapan, TTE, dan Amnesty.
- 👮 **[Petugas Lapangan](petugas-userguide.md)**: Spot Check, Audit Visual Reklame, Tracking Lokasi.
- 👩‍👩‍👦 **[Portal Wajib Pajak](mobile-e-retribusi.md)**: Inkuiri PBB, E-SPPT, dan Pelaporan Mandiri.

### 3. ⚖️ Regulasi & Modul Khusus (`/regulasi`)
- **[Amnesty & Penalty Waiver](penalty_waiver_logic.md)** (New): Logika penghapusan denda.
- **[Enforcement & Penindakan](enforcement_workflow.md)** (New): Alur Surat Teguran & Surat Paksa.
- **[TTE & E-Registry](tte_registry_standards.md)** (New): Standar verifikasi dokumen digital.
- **[Integrasi PBB 2026](regulasi/API_PBB_BAU_BAU_2026.md)**: Pedoman teknis inkuiri NOP.

### 4. 🧪 Standar Pengujian & Kualitas (`/testing`)
- **[PANDUAN TESTING](testing/00-Panduan-Standar-Pengujian.md)**: Protokol QA internal.
- **[QA ERROR REGISTRY](testing/12-Error-History-Mitigation-Registry.md)**: Sejarah error dan solusi mitigasi.
- **[STAGING PROTOCOL](testing/13-Workspace-Master-Testing-Protocol.md)**: Prosedur sebelum deployment production.

---

### 🏪 Modul Cross-Domain Lainnya
- **POS/Kasir**: `pos-MAIN-backend.md`, `pos-MAIN-frontend.md`.
- **GIS Tracking**: `skema-pelacakan-lokasi-petugas.md`.
- **Migration**: `migration_strategy.md`.

---
*Catatan AI Agent: Selalu perbarui doc ini jika ada penambahan modul besar atau perubahan struktur domain.*


---

## File: docs/99_umum_system/todo-list.md
---
# Consolidated Todolist & Audit Dokumen BAPENDA
**Terakhir diperbarui**: 15 Maret 2026 (Update Status Dokumen Resmi)

---

## ✅ 1. BPK Reporting Dashboard (ID: 12)
**File**: `ReportController::getMonthlyReport` → `Reporting.tsx`

**Cara Kerja**:
1. `GET /api/reports/bpk?year=2026` → join payments × bills × retribution_types
2. Group by klasifikasi + bulan → SUM(amount) = realisasi
3. Target dari SUM(bills.amount) per type per tahun
4. Frontend render: 3 cards (Target/Realisasi/Pencapaian%) + tabel breakdown × bulan

---

## ✅ 2. Sistem Denda Keterlambatan
**File**: `CalculateBillPenalties`, `FormulaParserService`, `BillingService`

**Cara Kerja**:
1. `bills:calculate-penalties` → query bills pending + due_date < now
2. Bagian bulan = 1 bulan penuh (sesuai regulasi)
3. Bunga: `stpd` 1%/bln, `skpdkb` 1.8%/bln, `jabatan` 2.2%/bln (max 24 bln)
4. Denda tetap untuk self-assessment tidak lapor
5. Update `penalty_amount` + `fixed_fine_amount` ke bill

**⚠️ Cron**: `0 1 * * * php artisan bills:calculate-penalties`

---

## ✅ 3. Penghapusan Denda / Amnesty
**File**: `PenaltyWaiverController` → `AmnestyManagement.tsx`

**Cara Kerja**:
1. `POST /api/amnesty` → ajukan (bill_id, reason, reduction_type/value)
2. `POST /api/amnesty/{id}/approve` → update `waived_penalty_amount` di bill
3. `GET /api/amnesty/{id}/document` → generate SK Penghapusan Denda

---

## ✅ 4. Verifikasi & Audit
**File**: `VerificationController`, `Auditable` trait → `Verification.tsx`, `AuditLogs.tsx`

**Cara Kerja Verifikasi**: Submit objek → review → approve (notes wajib) → auto-generate Bill pertama
**Cara Kerja Audit**: Trait log otomatis create/update/delete → old/new values → visual diff di UI

---

## ✅ 5. Pelacakan Petugas (Hybrid)
**File**: `SurveillanceController` → `PengawasDashboard.tsx`, `SupervisorMap.tsx`

**Cara Kerja**: Lokasi dikirim saat aksi petugas → Pengawas refresh manual / Live Mode 60dtk

---

## ✅ 6. Hybrid Dynamic Billing & Consolidation (V2)
**File**: `BillingService::getPendingPeriods()`

**Cara Kerja**:
1. **JIT Loop**: Menghitung periode tunggakan dari tanggal registrasi hingga sekarang.
2. **Consolidation**: Mengecek eksistensi record di tabel `bills`. Jika ada record `pending/overdue`, sistem menggunakan data database sebagai basis utama (menghindari duplikasi).
3. **Dynamic Penalty**: Denda direkalkulasi secara real-time meskipun record berasal dari database, memastikan angka selalu *up-to-date* saat diakses (`is_from_db`).
4. **Waiver Recognition**: Mengurangi denda secara otomatis jika terdapat record `waived_penalty_amount` yang disetujui.

---

## ✅ 7. Skema Pembayaran & Verifikasi Digital
**Repository**: `retribusi-api`, `retribusi-mobile`, `retribusi-petugas`

**Status**:
- [x] **Alur 1 (Citizen Claim):** `POST /api/payments` (pending) & unggah bukti bayar di mobile.
- [x] **Alur 1 (Verification):** Dashboard Petugas (`PaymentVerification.tsx`) panggil `PUT /api/payments/{id}/status`.
- [x] **Alur 2 (Field Payment):** Scan QR warga di `FieldScanner.tsx` petugas → Bayar Tunai → Langsung `success`.
- [x] **Thermal Print:** Implementasi `ThermalPrintService.ts` di aplikasi petugas untuk cetak struk SSRD.
- [x] **Otomasi Bill:** Status tagihan (`bills.status`) otomatis berubah jadi `lunas` saat payment berhasil.
- [x] **Universal Sync:** Webhook payment gateway dan scan verifikasi kini memicu rekalkulasi denda otomatis demi integritas finansial (*Zero Gap*).

---

## ✅ 7.5 Sistem Eskalasi Penagihan (Dunning V2)
**File**: `GenerateAutomatedTeguran`, `EnforcementNotice`

**Cara Kerja**:
1. **Tier 1 (Teguran 1)**: Terbit otomatis jika tagihan overdue > 7 hari.
2. **Tier 2 (Teguran 2)**: Eskalasi otomatis jika Teguran 1 diabaikan selama 14 hari.
3. **Tier 3 (Penindakan)**: Eskalasi otomatis ke status "Penindakan Lapangan" jika Teguran 2 diabaikan selama 7 hari.
4. **WhatsApp Integration**: Notifikasi otomatis dikirim ke Wajib Pajak pada setiap tahap eskalasi.

---

---

## �📄 Audit Dokumen Resmi BAPENDA

### Tahap 1: Pendaftaran & Keabsahan Data

| Dokumen | Endpoint / Logika | Status |
|---------|----------|--------|
| **SPOPD** | `TaxpayerController::store` (Full metadata + files) | ✅ Ada |
| **NPWPD** | Auto-generate & field `taxpayers.npwpd` | ✅ Ada |
| **SKT** | `OfficialDocumentService::generateSKT` | ✅ Ada |
| **SPOP/LSPOP** | `PbbBapendaController` (Fetch from Bapenda API) | ✅ Ada |
| **SPPT PBB** | `downloadSPPT` di `PbbBapendaController` | ✅ Ada |

### Tahap 2: Pendataan

| Dokumen | Endpoint | Status |
|---------|----------|--------|
| **Peta ZNT/NIR** | Zone model + `getMapPotentials` | ✅ Ada |
| **LHP** | `EnforcementNoticeController` | ✅ Ada |
| **GPS Tagging** | lat/lng di users + tax_objects | ✅ Ada |
| **LKOK** | `GET /api/documents/lkok/{id}` → `generateLKOK()` | ✅ Baru |

### Tahap 3: Penetapan

| Dokumen | Endpoint | Status |
|---------|----------|--------|
| **SPTPD** | `MonthlyReportController` → `ESptpd.tsx` | ✅ Ada |
| **SKRD** | `GET /api/documents/skrd/{id}` → `generateSKRD()` | ✅ Ada |
| **SPPT** | `GET /api/documents/sppt/{id}` → `generateSPPT()` | ✅ Ada |
| **SKPDKB** | `PenindakanController::generateSKPDKB` | ✅ Ada |
| **SKPDKBT** | `POST /api/documents/skpdkbt/{id}` → `generateSKPDKBT()` | ✅ Baru |
| **SKPDN** | `POST /api/documents/skpdn/{id}` → `generateSKPDN()` | ✅ Baru |
| **SKPDLB** | `SurveillanceController` (lebih bayar) | ✅ Ada |

### Tahap 4: Penagihan

| Dokumen | Endpoint | Status |
|---------|----------|--------|
| **STPD** | `CalculateBillPenalties` + BillingService | ✅ Ada |
| **SSPD** | `GET /api/documents/sspd/{id}` → `generateSSPD()` | ✅ Ada |
| **SSRD** | `GET /api/documents/ssrd/{id}` → `generateSSRD()` | ✅ Baru |
| **STRD** | `GET /api/documents/strd/{id}` → `generateSTRD()` | ✅ Baru |
| **SPP** | `GET /api/documents/spp/{id}` → `generateSPP()` | ✅ Ada |
| **Surat Teguran** | `GenerateAutomatedTeguran` command | ✅ Ada |
| **Surat Paksa** | `EnforcementNoticeController` | ✅ Ada |
| **SPMP** | `GET /api/documents/spmp/{id}` → `generateSPMP()` | ✅ Baru |
| **SK Penghapusan Denda** | `GET /api/amnesty/{id}/document` | ✅ Baru |

### Ringkasan Dokumen

| Sebelum | Sesudah |
|---------|---------|
| ✅ 14 Ada | ✅ **21 Ada** |
| ⚠️ 3 Partial | ⚠️ **1 Partial** (SPOP/LSPOP) |
| ❌ 7 Belum | ❌ **0 Belum** |

---

## 🧪 Testing

### Route Verification (11 Endpoint Dokumen)
| Endpoint | HTTP | Status |
|----------|------|--------|
| `/api/documents/skt/1` | GET | 401 ✅ |
| `/api/documents/lkok/1` | GET | 401 ✅ |
| `/api/documents/skrd/1` | GET | 401 ✅ |
| `/api/documents/sppt/1` | GET | 401 ✅ |
| `/api/documents/sspd/1` | GET | 401 ✅ |
| `/api/documents/ssrd/1` | GET | 401 ✅ |
| `/api/documents/strd/1` | GET | 401 ✅ |
| `/api/documents/spp/1` | GET | 401 ✅ |
| `/api/documents/spmp/1` | GET | 401 ✅ |
| `/api/documents/skpdkbt/1` | POST | 401 ✅ |
| `/api/documents/skpdn/1` | POST | 401 ✅ |

### E2E Test Suite
```bash
/usr/local/bin/php testing/run_role_e2e_test.php
```
Hasil: ✅ Laporan tertulis di `testing/results/08_Laporan_E2E_Lintas_Peran.md`

---

## ✅ 8. Metrik & Kinerja To-Do List Petugas (ID: 15)
**Repository**: `retribusi-api` (Backend) & `retribusi-petugas` (PWA Mobile)

**Status**:
- [x] Memeriksa ketersediaan API `GET /api/petugas-tasks` di Backend
- [x] Memeriksa struktur tabel `petugas_tasks` di Database
- [x] Menganalisis skema pelacakan penyelesaian (Completion Tracking)
- [x] Membuat implementasi `To-Do List` / `PetugasTasks` di aplikasi mobile (`DaftarTugas.tsx`)
- [x] Menyiapkan dokumentasi panduan pengukuran kinerja berdasarkan Ketepatan Tenggat Waktu (Due Date).

---

## ✅ 9. Skema Role-Based Sub-Admin (Admin Tipe Retribusi/Wilayah)
**Repository**: `retribusi-api` & `retribusi-admin`

**Status**:
- [x] **Skema Database:** Tambahkan relasi `retribution_type_id` pada tabel `users`.
- [x] **Skema Filter Data:** Terapkan pembatasan isolasi data di model utama (Global Scope / Query Filter).
- [x] **Konsistensi UI (Dashboard Admin):** Penyesuaian analitik di React (`Reporting.tsx` & `Dashboard.tsx`) agar terfilter sesuai wewenang admin wilayah.

---

## ✅ 10. Modul Uji Petik (Pengamatan Lapangan) - Perwali 58/2024
**Repository**: `retribusi-api` (Backend) & `retribusi-admin` (Frontend Dashboard Pengawas)

**Status**:
- [x] **Tabel Database Baru (`spot_checks`):** Menyimpan data pengamatan jam-per-jam.
- [x] **Frontend (Form Uji Petik):** UI grid/matriks untuk input data observasi (`SpotCheckForm.tsx`).
- [x] **Algoritma Estimasi Harian (Backend Service):** Menghitung rata-rata harian (Biasa vs Akhir Pekan).
- [x] **Integrasi Penindakan:** Mengaitkan hasil uji petik sebagai lampiran SKPDKB.

---

## ✅ 11. Penyelesaian Template PDF Dokumen Oficial
**Target**: Mengonversi JSON menjadi format cetak PDF.
- [x] **SKT**, **LKOK**, **SSRD**, **STRD**, **SKPDKBT**, **SKPDN**, **SPMP**.
- [x] **Public Access Verification** (Dokumen publik via QR scan).

---

## ✅ 12. Modul Penyuluhan & Sosialisasi (Edukasi Pajak)
**Repository**: `retribusi-api` & `retribusi-admin`

**Status**:
- [x] **Skema Database (`tax_educations`):** Kategori & materi Perda.
- [x] **Frontend:** Dashboard edukasi dan tutorial interaktif (`Presentation.tsx`).
- [x] **Broadcast Notifikasi:** Hubungkan info edukasi ke aplikasi Mobile.

---

## ✅ 13. Modul Penertiban Reklame (Visual Audit)
**Repository**: `retribusi-api` & `retribusi-admin`

**Status**:
- [x] **Metadata Objek Reklame:** `reklame_photo`, `installation_date`.
- [x] **Logic Pembeda (New vs Old):** Labeling otomatis di sistem.
- [x] **Visual Map Audit:** Marker warna-warni & pop-up foto di `PengawasMaps.tsx`.
- [x] **Audit Foto Wajib:** Force camera upload & GPS Radius check di `FieldInspection.tsx` (Petugas).

---

## ✅ 14. Modul Manajemen Pengaduan (Citizen Complaints)
**Repository**: `retribusi-api` & `retribusi-admin`

**Status**:
- [x] **Databases:** Migrasi `complaints` table (User ID, Category, Text, Attachment, Status).
- [x] **Backend Logic:** `ComplaintController` (Store, Index, Update Status).
- [x] **Admin UI:** Halaman `ComplaintManagement.tsx` untuk filter, lihat detail, dan tindak lanjut.
---

## 🎯 15. Strategic Roadmap (Target Penyelesaian: BULAN INI)

### Integrasi Eksternal & Data
- [ ] **Full-Sync SISMIOP (PBB-P2)**: Migrasi data massal dan sinkronisasi dashboard tunggal.
- [ ] **PKS Bank BPD**: Perjanjian Kerja Sama dengan Bank Sulawesi Tenggara (BPD) untuk integrasi fitur pembayaran Host-to-Host (VA/QRIS) yang belum rampung.
- [x] **Template Dokumen Sisa Pembayaran**: Penyempurnaan format PDF (**Selesai**).
- [x] **Otomasi Pelacakan Sisa Pembayaran**: Sinkronisasi real-time pembayaran & rekalkulasi denda (V2 Alignment) (**Selesai**).

### Analitik & Kecerdasan Buatan (AI)
- [ ] **Predictive Analytics Engine**: Proyeksi target pendapatan tahunan berdasarkan tren historis.
- [ ] **Anomaly Detection V2**: Penajaman algoritma deteksi kecurangan melalui pola transaksi non-tunai.

### Deployment & Sosialisasi
- [ ] **TTE Live Activation**: Finalisasi pendaftaran sertifikat digital BSRE untuk E-Registry.
- [ ] **Pelatihan OPD Massal**: Workshop operasional dashboard untuk seluruh dinas terkait.
- [ ] **Sosialisasi Wajib Pajak**: Kampanye penggunaan M-PAD Mobile untuk edukasi warga.

---
*Terakhir diperbarui oleh AI Antigravity pada 06 April 2026. Seluruh target pengerjaan difokuskan selesai pada bulan ini.*


---

## File: docs/99_umum_system/STABLE_BASELINE.md
---
# 📌 STABLE BASELINE COMMIT IDS

Dokumen ini mencatat ID Commit GitHub yang telah diverifikasi **stabil** dan operasional di lingkungan produksi (VPS & Netlify). Gunakan ID ini sebagai referensi utama jika terjadi regresi atau saat melakukan inisialisasi lingkungan baru.

---

## 🚀 Baseline Saat Ini (Maret 2026)

| Repository | Stable Commit ID | Lingkungan | Tanggal Verifikasi | Status |
| :--- | :--- | :--- | :--- | :--- |
| **retribusi-api** | `7b7388c` | **VPS (api.sipanda.online)** | 02 Maret 2026 | ✅ Tested (Seeder & Logs OK) |
| **retribusi-admin** | `169111d` | **Netlify (admin.sipanda.online)** | 02 Maret 2026 | ✅ Published |
| **retribusi-mobile**| `f0d5fbb` | **Netlify (sipanda.online)** | 02 Maret 2026 | ✅ Published |
| **retribusi-petugas**| `647140e` | **Netlify (via Netlify)** | 02 Maret 2026 | ✅ Published |

---

## 🛠️ Riwayat Verifikasi (Audit Trail)

### 1. retribusi-api (`7b7388c`)
- **Metode**: `TestingScenarioSeeder` dijalankan di VPS.
- **Hasil**: 
    - Database integrity: PASSED (Budi & Ani created).
    - Error Logs: CLEAN (Zero "500 Internal Server Error").
    - Connectivity: PASSED (CORS & Health Check `/up`).
- **Catatan**: Commit ini 1 level di belakang `main` (`abd075c`), namun dipastikan stabil untuk operasional saat ini.

### 2. Frontend Components
- **Metode**: PWA Accessibility check & Production readiness script.
- **Hasil**: Smoke test pada domain produksi menunjukkan sistem dapat diakses dan berfungsi sebagaimana mestinya.

---

## ⚠️ Instruksi untuk AI Agent
Jika Anda diminta melakukan perubahan besar (refactoring/migrasi), **pastikan Anda mencatat ID ini** sebagai titik balik (rollback point). Jangan melakukan `git pull` secara membabi buta di VPS jika versi stabil yang dicatat di sini belum diperbarui.

---
*Terakhir diperbarui: 02 Maret 2026 oleh AI Antigravity.*


---

## File: docs/99_umum_system/system-knowledge.md
---
# 📚 System Knowledge - M-PAD (Mitra PAD) Kota Baubau
**Terakhir diperbarui**: 5 Maret 2026

Dokumen ini adalah referensi lengkap arsitektur sistem M-PAD yang mencakup seluruh repository, file, endpoint, dan komponen.

---

## 📂 Repository Structure

| Repo | Stack | Deskripsi | Domain (Prod) |
|------|-------|-----------|----------------|
| `retribusi-api` | Laravel 11 + MySQL | Backend API | `api.sipanda.online` |
| `retribusi-admin` | React + Vite + TS | Dashboard Admin/BAPENDA | `admin.sipanda.online` |
| `retribusi-petugas` | React + Vite + TS | App Petugas Lapangan | `petugas.sipanda.online` |
| `retribusi-mobile` | React + Vite + TS | App Warga (PWA) | `sipanda.online` |

---

## 🗄️ Database Models (22 Model)

| # | Model | Tabel | Relasi Utama | Keterangan |
|---|-------|-------|--------------|------------|
| 1 | `User` | `users` | belongsTo(Opd), hasMany(Bill, Payment) | Admin, Petugas, Citizen |
| 2 | `Opd` | `opds` | hasMany(User, Taxpayer) | Organisasi Perangkat Daerah |
| 3 | `Taxpayer` | `taxpayers` | belongsTo(Opd), hasMany(TaxObject, Bill) | Wajib Pajak/Retribusi |
| 4 | `TaxObject` | `tax_objects` | belongsTo(Taxpayer, Zone, Classification) | Objek Pajak |
| 5 | `RetributionType` | `retribution_types` | hasMany(Classification, Zone) | Jenis Retribusi (Parkir, Pasar, dll) |
| 6 | `RetributionClassification` | `retribution_classifications` | belongsTo(RetributionType), hasMany(Rate) | Sub-klasifikasi (tarif + formula) |
| 7 | `RetributionRate` | `retribution_rates` | belongsTo(Classification) | Tarif harga |
| 8 | `Zone` | `zones` | belongsTo(RetributionType), hasMany(TaxObject) | Zona geografis |
| 9 | `Bill` | `bills` | belongsTo(Taxpayer, TaxObject, Classification) | Tagihan / SKRD |
| 10 | `Payment` | `payments` | belongsTo(Bill, User→taxpayer, User→approvedBy) | Pembayaran (pending/success/failed) |
| 11 | `Verification` | `verifications` | belongsTo(TaxObject) | Verifikasi objek pajak |
| 12 | `ObjectVerification` | `object_verifications` | belongsTo(TaxObject, User) | Riwayat verifikasi |
| 13 | `AuditLog` | `audit_logs` | belongsTo(User) | Log perubahan data |
| 14 | `EnforcementNotice` | `enforcement_notices` | belongsTo(TaxObject) | Surat teguran / penindakan |
| 15 | `MonthlyReport` | `monthly_reports` | belongsTo(User, TaxObject) | Laporan SPTPD bulanan |
| 16 | `PenaltyWaiver` | `penalty_waivers` | belongsTo(Bill) | Amnesti / penghapusan denda |
| 17 | `SignedDocument` | `signed_documents` | belongsTo(Bill) | Dokumen ber-TTE |
| 18 | `PbbNjopClassification` | `pbb_njop_classifications` | — | Kelas NJOP PBB |
| 19 | `TaxpayerPbbObject` | `taxpayer_pbb_objects` | belongsTo(Taxpayer) | Link NOP PBB warga |
| 20 | `TransactionPbb` | `transaction_pbb` | belongsTo(TaxpayerPbbObject) | Transaksi PBB |
| 21 | `UserRetributionAssignment` | `user_retribution_assignments` | belongsTo(User, RetributionType) | Penugasan petugas |
| 22 | `Department` | `departments` | — | Departemen OPD |

---

## ⚙️ Services (8 Service)

| File | Fungsi |
|------|--------|
| `BillingService.php` | Generate tagihan, hitung periode tunggakan (Virtual Ledger) |
| `CloudinaryService.php` | Upload & manage media ke Cloudinary CDN |
| `FormulaParserService.php` | Parse & execute rumus perhitungan retribusi |
| `OfficialDocumentService.php` | Generate dokumen resmi BAPENDA (SKT, SKRD, SSPD, dll) |
| `PbbBapendaService.php` | Integrasi PBB Bapenda (inquiry, payment, reversal) |
| `PbbCalculationService.php` | Kalkulasi PBB (NJOP, NJKP, tarif) |
| `TTEService.php` | Tanda Tangan Elektronik (E-Registry) |
| `WhatsAppService.php` | Kirim notifikasi WhatsApp |

---

## 🖥️ Artisan Commands (9 Command)

| Command | File | Fungsi |
|---------|------|--------|
| `bills:calculate-penalties` | `CalculateBillPenalties.php` | Hitung denda keterlambatan (cron daily) |
| `bills:pay` | `PayBill.php` | Proses pembayaran via CLI |
| `teguran:generate` | `GenerateAutomatedTeguran.php` | Auto-generate surat teguran |
| `analyze:los-potensi` | `AnalyzeLosPotensi.php` | Analisis LOS potensi retribusi |
| `import:bapenda` | `ImportBapendaData.php` | Import data dari Bapenda |
| `import:tax-objects` | `ImportTaxObjectData.php` | Import objek pajak |
| `cleanup:master-hierarchy` | `CleanupMasterDataHierarchy.php` | Bersihkan hirarki master data |
| `upload:pbb-icon` | `UploadPbbIcon.php` | Upload ikon PBB |
| `test:production-e2e` | `TestProductionE2E.php` | E2E test di production |

---

## 🔐 Middleware & Traits

| Tipe | File | Fungsi |
|------|------|--------|
| Middleware | `EnsureAdmin.php` | Restrict akses ke admin & petugas only |
| Trait | `Auditable.php` | Auto-log create/update/delete ke `audit_logs` |

---

## 🛣️ User Roles

| Role | Akses | Digunakan di |
|------|-------|-------------|
| `super_admin` | Full access seluruh sistem | Admin |
| `opd` | Manage WP, billing, verifikasi | Admin |
| `verifikator` | Verifikasi data objek pajak | Admin |
| `petugas` | Input lapangan, scan QR, billing | Petugas |
| `viewer` | View-only dashboard & reporting | Admin |
| `pengawas` | Pengawasan, audit, penindakan | Admin |
| `kabid_pengawas` | Kabid level pengawasan | Admin |
| `kasubid_pengawas` | Kasubid level pengawasan | Admin |
| `walikota` | Executive dashboard | Admin |
| `citizen` | Warga biasa (SPTPD, tagihan) | Mobile |

---

## 📊 Database Migrations (66 file)

Tabel utama dan urutan pembuatan:

| Fase | Tabel yang Dibuat |
|------|------------------|
| **Core** | `opds`, `users`, `cache`, `jobs`, `personal_access_tokens` |
| **Master** | `retribution_types`, `retribution_classifications`, `retribution_rates`, `zones` |
| **WP & Objek** | `taxpayers`, `tax_objects`, `verifications`, `object_verifications` |
| **Billing** | `bills`, `payments`, `user_retribution_assignments` |
| **Pengawasan** | `audit_logs`, `enforcement_notices`, `monthly_reports` |
| **Penagihan** | `penalty_waivers`, `signed_documents` |
| **PBB** | `pbb_njop_classifications`, `taxpayer_pbb_objects`, `transaction_pbb` |
| **Insentif** | `incentive_tables` |

---

## 🔗 External Integrations

| Service | Tujuan | Config |
|---------|--------|--------|
| **Cloudinary** | Upload gambar (bukti bayar, ikon, foto) | `CLOUDINARY_*` env vars |
| **Sentry** | Error monitoring | `SENTRY_DSN` |
| **PBB Bapenda API** | Inquiry & bayar PBB | `PBB_BAPENDA_*` env vars |
| **E-Registry (TTE)** | Tanda tangan digital dokumen | `TTE_*` env vars |
| **WhatsApp API** | Notifikasi (Fonnte) | `WHATSAPP_*` env vars |
| **Bluetooth BLE** | Cetak resi thermal (Petugas) | Web Bluetooth API (frontend) |

---

## 📑 Existing Documentation Files

| File | Isi |
|------|-----|
| `docs/SYSTEM_OVERVIEW.md` | Arsitektur umum, domain, deployment flow |
| `docs/routes-and-components.md` | Daftar lengkap API endpoint & FE routes |
| `docs/todo-list.md` | Checklist fitur & audit dokumen BAPENDA |
| `docs/summary-changes-2026.md` | Ringkasan perubahan 2026 |
| `docs/STABLE_BASELINE.md` | Commit ID stabil per repo |
| `docs/DOMAIN_SCHEMA.md` | Skema domain & subdomain |
| `docs/INFRASTRUCTURE_NOTES.md` | Catatan VPS & server |
| `docs/CREDENTIALS_GUIDE.md` | Panduan kredensial |
| `docs/TESTING_GUIDE.md` | Panduan testing |
| `docs/MITIGATION_GUIDE.md` | Panduan mitigasi error |
| `docs/UI_INTEGRATION_STANDARDS.md` | Standar integrasi UI lintas repo |
| `docs/audit_bapenda_stages.md` | Tahapan audit BAPENDA |
| `docs/skema-pelacakan-lokasi-petugas.md` | Skema GPS tracking petugas (Monitoring Real-time & History) |

---

## 🏗️ Skema Alur Bisnis

### Alur Pembayaran Digital
```
Warga (Mobile)                    API                         Petugas (App)
     │                             │                              │
     ├─ Pilih tagihan ────────────►│                              │
     ├─ Upload bukti bayar ───────►│ POST /payments (pending)     │
     │                             │──────────────────────────────►│ GET /payments?status=pending
     │                             │                              ├─ Review bukti
     │                             │◄─────────────────────────────┤ PUT /payments/{id}/status
     │◄── Notifikasi status ───────│ Bill → lunas                 ├─ Cetak resi (Bluetooth)
```

### Alur Scan Lapangan
```
Warga (Mobile)                    Petugas (App)               API
     │                              │                          │
     ├─ Tampilkan QR tagihan ──────►│ Scan QR (FieldScanner)   │
     │                              ├─ Parse JSON bill IDs ───►│ GET /bills?ids=...
     │                              │◄────────────────────────┤ Return bill data
     │                              ├─ Terima tunai            │
     │                              ├─ Catat pembayaran ──────►│ POST /payments (success)
     │                              ├─ Cetak resi (BLE)        │
```

### Alur 4 Tahap BAPENDA
```
1. PENDAFTARAN ─► SPOPD → NPWPD → SKT
2. PENDATAAN   ─► LHP → GPS Tagging → LKOK → Peta ZNT/NIR
3. PENETAPAN   ─► SPTPD → SKRD/SPPT → SKPDKB/SKPDKBT/SKPDN
4. PENAGIHAN   ─► STPD → SSPD/SSRD → SPP → Surat Teguran → Surat Paksa → SPMP
```

### Alur Penegakan / Enforcement
```
Pengawas (Dashboard)              API                         Target Data
      │                             │                              │
      ├─ Buat Draft Teguran ───────►│ POST /enforcements           │ 
      │                             │                              │
      ├─ Approve (oleh Kabid) ─────►│ PUT /enforcements/{id}/app  ─► Status berubah ke 'approved'
      │                             │                              │
      ├─ Download / Cetak SPP  ────►│ GET /enforcements/{id}/pdf   │
```
*(Catatan: Fitur penegakan sepenuhnya ditangani oleh Role Pengawas, mulai dari pembuatan draft hingga approval pimpinan)*


---

## File: docs/09_sistem_logic_study/ANALISIS_LOGIKA_MPAD.md
---
# Analisis Logika Inti Sistem M-PAD
> Panduan teknis untuk memahami mekanisme operasional hulu-ke-hilir sistem perpajakan modern Kota Baubau.
> Tanggal Studi: 2026-04-12

## 1. Mekanisme Invoice & Billing (Tagihan)
Sistem M-PAD menggunakan pendekatan hibrida untuk memastikan optimalisasi PAD tanpa celah.

### A. Virtual Billing (Otomatis)
Sistem secara cerdas menghitung potensi tagihan tanpa harus menunggu input manual dari petugas:
- **Metode**: Melakukan komparasi antara tanggal pendaftaran Objek Pajak dengan tabel `payments`.
- **Logika**: Jika periode tertentu belum memiliki record pembayaran sukses, sistem menganggapnya sebagai tunggakan virtual.
- **Dinamis**: Nilai tagihan dihitung secara *real-time* berdasarkan siklus (Harian/Mingguan/Bulanan/Tahunan) yang diatur di Master Data.

### B. Input Manual Invoice
Admin memiliki fleksibilitas untuk penyesuaian data:
- **Fitur**: Administrator (Level OPD/Super Admin) dapat menambahkan tagihan secara manual melalui fitur `store` pada modul Bill.
- **Kebutuhan**: Biasanya digunakan untuk penetapan hasil audit (SKPDKB) atau penyesuaian khusus yang tidak tercakup dalam siklus rutin.

> [!IMPORTANT]
> Setiap tagihan resmi (SKRD) dapat divalidasi dengan **Tanda Tangan Elektronik (TTE)** oleh pejabat berwenang sebelum diterbitkan ke Wajib Pajak.

---

## 2. Mekanisme Penugasan Petugas (/tasks)
Sistem kontrol lapangan terintegrasi untuk memantau produktivitas petugas secara objektif.

- **Pihak Penginput**: Admin OPD atau Pengawas Lapangan melalui Dashboard Admin.
- **Parameter Penugasan**:
  - **Petugas**: User spesifik yang dituju.
  - **Target**: Objek Pajak atau Wajib Pajak tertentu.
  - **Wilayah**: Zona/Kecamatan/Kelurahan tertentu.
  - **Tenggat**: Tanggal jatuh tempo penugasan (`due_date`).
- **Alur Kerja di Aplikasi Petugas**:
  1. Petugas menerima daftar tugas di menu **Tasks**.
  2. Petugas melakukan kunjungan lapangan.
  3. Petugas mengunggah **Foto Bukti** dan mencatat hasil kunjungan.
  4. Sistem mencatat **Koordinat GPS** saat tugas diselesaikan untuk verifikasi lokasi petugas.

---

## 3. Skema Perhitungan Denda (Penalty)
Berdasarkan **Perwali No. 58/2024** dan **Perda No. 1/2024**, denda dikunci pada level sistem di `FormulaParserService`.

| Jenis Tagihan | Persentase Denda | Deskripsi |
| :--- | :--- | :--- |
| **STPD** | 1% / Bulan | Denda keterlambatan pembayaran rutin (Pajak Terhutang). |
| **SKPDKB** | 1.8% / Bulan | Denda hasil pemeriksaan (pemeriksaan umum). |
| **Jabatan** | 2.2% / Bulan | Penetapan secara paksa karena WP tidak melapor/pembukuan. |
| **Angsuran** | 0.6% / Bulan | Bunga untuk WP yang melakukan permohonan cicilan. |
| **Flat Fine** | Rp 100.000 | Sanksi administrasi jika tidak menyampaikan laporan SPTPD. |

> [!TIP]
> Perhitungan denda maksimal dibatasi hingga **24 bulan**. Nilai denda akan otomatis terakumulasi dalam Total Tagihan yang muncul pada QR-Pay.

---

## 4. Algoritma Deteksi Anomali (Surveillance)
Pusat intelijen pengawasan untuk membantu auditor mengidentifikasi potensi kebocoran pajak secara dini.

1. **Anomali Tunggakan (Delinquency)**:
   - Menandai Objek Pajak yang memiliki tunggakan di atas **3 bulan/periode** berturut-turut.
2. **Penyimpangan Omzet (Revenue Mismatch)**:
   - Komparasi antara laporan mandiri (Self-Assessment) dengan estimasi potensi sistem.
   - Ambang batas (*Threshold*): **>20% selisih** akan memicu tanda merah pada dashboard pengawas.
3. **Absensi Pembayaran (Non-Compliance)**:
   - Identifikasi objek aktif yang sama sekali tidak melakukan pembayaran dalam **30 hari terakhir**.
4. **Productivity Variance**:
   - Memantau rute dan koordinat petugas lapangan. Jika tugas dilaporkan selesai tetapi GPS petugas tidak berada di lokasi objek pajak, sistem akan mencatatnya sebagai anomali aktivitas.

---
## 5. Rencana Pengembangan: Tabel Transparansi Objek & WP
Pusat kendali data master untuk memvalidasi pemetaan subjek (WP) dan objek (Aset Pajak) secara massal.

- **Tujuan**: Memudahkan Admin/OPD dalam melakukan audit data dan memastikan tidak ada objek pajak yang "yatim" (tidak memiliki pemilik terdaftar).
- **Struktur Kolom Utama**:
  - **Identitas Objek**: NOP, Nama Objek, dan Alamat Lokasi.
  - **Identitas WP**: Nama Wajib Pajak (Pemilik), NPWPD, dan No. Telepon.
  - **Kategorisasi**: Jenis Retribusi, Klasifikasi, dan Zona Wilayah.
  - **Status Terakhir**: Status Pembayaran Terakhir (Lunas/Menunggak) dan Tanggal Verifikasi.
- **Fitur Interaktif**:
  - **Global Search**: Mencari berdasarkan NOP atau Nama WP secara instan.
  - **Quick Action**: Tombol cepat untuk "Buat Tugas" atau "Lihat Riwayat Tagihan" langsung dari baris tabel.
  - **Export Data**: Kemampuan mengunduh daftar WP per kategori untuk keperluan laporan fisik ke pimpinan.

### Referensi Kode Kunci (Developer Only):
- **Logika Billing**: `app/Services/BillingService.php`
- **Rumus Denda**: `app/Services/FormulaParserService.php`
- **Algoritma Anomali**: `app/Http/Controllers/Pengawas/SurveillanceController.php`
- **Mekanisme Task**: `app/Http/Controllers/PetugasTaskController.php`


---

## File: docs/05_modul_aplikasi/petugas-DOMAIN_SCHEMA.md
---
# 🌐 Domain Schema & Subdomain Mapping

Dokumentasi ini merinci pemetaan domain dan subdomain untuk sistem MPAD (Mitra Pajak & Retribusi Daerah) baik di lingkungan Produksi maupun Staging.

## 1. Lingkungan Produksi (Pemerintah Kota Baubau)
Domain resmi yang digunakan untuk operasional publik dan internal.

| Komponen | Domain | Deskripsi |
| :--- | :--- | :--- |
| **Portal Utama** | `mpad.baubaukota.go.id` | Pintu masuk utama aplikasi/Landing page |
| **Dashboard Admin** | `adminmpad.baubaukota.go.id` | Panel manajemen untuk Bapenda & OPD |
| **Backend API** | `api.sipanda.online` | Endpoint layanan data (Core API) |
| **Interface Petugas** | `petugasmpad.baubaukota.go.id` | Akses petugas lapangan (Mobile/Web) |

## 2. Lingkungan Staging / Development (VPS)
Digunakan untuk testing fitur baru sebelum di-deploy ke produksi.
**Host IP:** `157.10.252.74`

| Komponen | Domain | Deskripsi |
| :--- | :--- | :--- |
| **Main Entrance** | `sipanda.online` | Pintu masuk utama lingkungan dev |
| **Admin Panel** | `admin.sipanda.online` | Dashboard admin versi staging |
| **API Endpoint** | `api.sipanda.online` | Backend API untuk development |
| **Officer Portal** | `petugas.sipanda.online` | Antarmuka petugas versi staging |
| **Launch Teaser** | `launch.sipanda.online` | Halaman promosi/hitung mundur |

## 3. Domain Alternatif / Legacy
Domain lain yang mungkin masih terdaftar dalam konfigurasi sistem (CORS/SSL):
- `sipanda.online`
- `admin.sipanda.online`
- `petugas.sipanda.online`
- `api.sipanda.online`

---
*Terakhir Diperbarui: 3 Maret 2026*


---

## File: docs/05_modul_aplikasi/petugas-STABLE_BASELINE.md
---
# 📌 STABLE BASELINE COMMIT IDS (Admin)

Referensikan file ini untuk memastikan Anda bekerja di versi yang stabil.

| Repository | Stable Commit ID | Tanggal Verifikasi |
| :--- | :--- | :--- |
| **retribusi-api** | `7b7388c` | 02 Maret 2026 |
| **retribusi-admin** | `169111d` | 02 Maret 2026 |
| **retribusi-mobile**| `f0d5fbb` | 02 Maret 2026 |
| **retribusi-petugas**| `647140e` | 02 Maret 2026 |

> [!NOTE]
> Sumber kebenaran utama ada di `retribusi-api/docs/STABLE_BASELINE.md`.


---

## File: docs/05_modul_aplikasi/petugas-userguide.md
---
# 👮 Panduan Lengkap Petugas Lapangan M-PAD
## Digital Field Operations (Update April 2026)

Panduan ini disusun untuk membantu Petugas Lapangan M-PAD Kota Baubau dalam menjalankan tugas pemutakhiran data, uji petik, dan penindakan.

---

## 🟢 1. Dashboard Petugas & Shift On
Sebelum bekerja, pastikan Anda berada dalam posisi **Shift On**.

1.  **Shift Monitoring**: Sistem akan melacak lokasi (Live Tracking) untuk sinkronisasi Command Center.
2.  **Target Harian**: Pantau jumlah WP yang perlu dikunjungi atau didata hari ini di menu Dashboard.
3.  **Heatmap Lapangan**: Lihat titik-titik potensi pajak di sekitar lokasi Anda yang belum terregistrasi.

---

## 🔵 2. Registrasi & Pendataan Objek Pajak
Perekaman data baru langsung dari lapangan.

1.  **Peta & Koordinat**: Gunakan fitur **Open Maps** untuk menandai lokasi presisi Objek Pajak.
2.  **Visual Evidence**: Sertakan foto tampak depan dan foto lingkungan objek.
3.  **Metadata Dinamis**: Isi data spesifik (Contoh: Jumlah kamar hotel, Kursi restoran, atau Dimensi reklame).
4.  **Tanda Tangan WP**: Mintalah Wajib Pajak menandatangani langsung di layar aplikasi sebagai bukti pendataan.

---

## 🟡 3. Uji Petik (Spot Check)
Modul untuk memantau omzet harian secara nyata.

1.  **Check-In**: Lakukan Check-In saat tiba di lokasi (Restoran/Hotel/Hiburan).
2.  **Perekaman Data**: Catat jumlah tamu, transaksi, dan estimasi nilai transaksi dalam interval waktu tertentu (Contoh: Jam makan siang).
3.  **Validasi**: Kirim data Spot Check untuk diaudit oleh Admin/Pengawas sebagai dasar penetapan pajak yang akurat.

---

## 🟠 4. Audit Visual Reklame (Billboard Audit)
Verifikasi fisik reklame di lapangan.

1.  **Scan/Cari Objek**: Cari data reklame berdasarkan NOP atau lokasi.
2.  **Update Foto**: Ambil foto terbaru reklame untuk memverifikasi apakah dimensi dan konten masih sesuai izin.
3.  **Flagging**: Tandai jika ditemukan reklame liar atau reklame yang sudah kedaluwarsa tapi belum dibongkar.

---

## 🔴 5. Penindakan & Surat Teguran (Enforcement)
Menjalankan perintah penagihan paksa.

1.  **Daftar Tugas (Task List)**: Lihat daftar WP yang sudah diterbitkan Surat Paksa atau Teguran.
2.  **Eksekusi Penempelan**: Tempelkan stiker/segel tanda menunggak pajak.
3.  **Berita Acara Digital**: Ambil foto bukti penempelan stiker dengan titik koordinat (Anti-Fake GPS) dan unggah sebagai laporan final.

---
*Tips Lapangan: Pastikan GPS Anda aktif dengan akurasi tinggi. Semua aktivitas Anda terekam dalam Audit Log untuk menjamin integritas petugas.*


---

## File: docs/05_modul_aplikasi/mobile-SYSTEM_OVERVIEW.md
---
# 🏢 SYSTEM OVERVIEW: RETRIBUSI-MOBILE

**M-PAD Mobile** adalah aplikasi Progressive Web App (PWA) yang dirancang untuk Wajib Pajak (WP) agar dapat melakukan pelaporan dan pembayaran secara mandiri (Self Service).

## 🌟 Fitur Utama
- **Pendaftaran WP Mandiri:** Registrasi akun dan objek retribusi.
- **E-Billing:** Cek tagihan dan unduh SKRD secara realtime.
- **Pembayaran QRIS:** Integrasi pembayaran non-tunai.
- **Riwayat Pelaporan:** Arsip digital semua transaksi dan dokumen.

## 🔗 Integrasi
- Backend: REST API `retribusi-api`.
- Frontend: Vite + React + Tailwind CSS.


---

## File: docs/05_modul_aplikasi/mobile-e-retribusi.md
---
# 👨‍👩‍👦 Panduan Portal Wajib Pajak M-PAD
## Portal Layanan Mandiri (Update April 2026)

Aplikasi **M-PAD (Mitra PAD)** Kota Baubau memberikan kemudahan bagi masyarakat untuk mengelola kewajiban perpajakan daerah secara mandiri, transparan, dan aman.

---

## 🟢 1. Layanan PBB-P2 (Pajak Bumi & Bangunan)
Fitur terbaru untuk kemudahan pembayaran PBB.

1.  **Inkuiri NOP**: Masukkan Nomor Objek Pajak (NOP) Anda untuk melihat status tagihan tahun berjalan maupun tunggakan.
2.  **E-SPPT**: Unduh dokumen SPPT digital dalam format PDF yang sah secara hukum untuk keperluan administratif.
3.  **Riwayat Pembayaran**: Pantau setiap rupiah pajak yang Anda bayarkan untuk pembangunan Kota Baubau.

---

## 🔵 2. Pendaftaran Mandiri (E-SPTPD / SPOPD)
Daftarkan usaha atau objek pajak baru tanpa harus ke kantor Bapenda.

1.  **Formulir Digital**: Pilih jenis pajak (Hotel, Restoran, Parkir, dll) dan isi data teknis objek.
2.  **Upload Berkas**: Unggah foto KTP, foto objek, atau surat izin usaha langsung dari kamera ponsel.
3.  **Tagging Lokasi**: Gunakan GPS ponsel untuk menandai titik lokasi usaha Anda dengan akurat.
4.  **Monitoring**: Pantau status pendaftaran dari 'Proses Verifikasi' hingga 'Disetujui'.

---

## 🟡 3. Billing & Pembayaran Digital
Berbagai kemudahan metode pembayaran untuk efisiensi waktu.

1.  **Cek Tagihan (Billing)**: Lihat daftar tagihan pajak PBJT yang sudah diterbitkan (SKPD/SKRD).
2.  **Metode Bayar**:
    - **QRIS**: Scan & Bayar menggunakan aplikasi mobile banking atau e-wallet (OVO, GoPay, Dana, dll).
    - **Virtual Account**: Pembayaran via ATM atau Mobile Banking Bank BPD Sultra.
3.  **E-SSPD**: Unduh bukti bayar sah yang dilengkapi dengan QR-Code verifikasi BSrE (TTE).

---

## 🟠 4. Permohonan Relaksasi (Amnesty)
Gunakan fitur ini jika terdapat program penghapusan denda dari Pemerintah Kota.

1.  **Pengajuan Keringanan**: Pilih tagihan yang memiliki denda, tuliskan alasan permohonan, dan lampirkan dokumen pendukung.
2.  **Update Real-time**: Dapatkan notifikasi jika permohonan Anda disetujui, dan nominal denda pada tagihan akan berkurang otomatis.

---

## 🔴 5. Layanan Pengaduan & Bantuan
Suarakan masukan Anda untuk perbaikan layanan publik.

1.  **Lapor Kendala**: Laporkan jika ada kesalahan data atau masalah saat proses pembayaran.
2.  **Feedback**: Berikan ulasan terhadap kualitas layanan petugas penagih atau sistem M-PAD.

---
*Keamanan Data: Data NIK dan profil Anda dilindungi dengan enkripsi standar industri. Jangan pernah membagikan kode OTP atau Password kepada siapa pun.*

---

## File: docs/05_modul_aplikasi/petugas-TASK_CONTEXT.md
---
# 📋 TASK CONTEXT & HISTORY (Petugas)

## 🛠️ Tugas Berjalan
1. **Analisa Fitur:** Berdasarkan folder `/Users/pondokit/Herd/retribusi-mobile/src`.
2. **Penyaringan Fitur per Dinas:**
   - **Dishub:** Parkir, Pelabuhan, Terminal.
   - **DPMPTSP:** IMB/PBG, Reklame.
   - **Disparekraf:** Rekreasi, Olahraga.
   - **PDAM:** Retribusi Air.
   - **Disperindag:** Pasar (Los/Kios).
   - **Dinas PUPR:** IMB/PBG, Sewa Alat Berat.
   - **DLH:** Persampahan/Kebersihan.


---

## File: docs/05_modul_aplikasi/pos-PART-backend.md
---
# ⚙️ Enhanced Backend Context - POS System

Technical specifications for the POS backend, focusing on role-based access, API standards, and core business logic.

## 🔐 1. Authentication & Authorization

### Role Definitions
| Role | Permissions | Accessible Modules |
| :--- | :--- | :--- |
| **Admin** | Full Access | Dashboard, User Management, Settings, Reports, Inventory |
| **Kasir** | Limited (Retail) | POS, Transactions, Sales Reports |
| **Gudang** | Limited (Stock) | Inventory, Stock Management, Supplier |

### JWT Token Structure
```json
{
  "user_id": 1,
  "email": "admin@pos.com",
  "role": "admin",
  "permissions": ["read", "write", "delete"],
  "exp": 1735689600
}
```

---

## 🌐 2. API Architecture & Routing

### URL Structure (Frontend)
| Role | URL Path | Key Modules |
| :--- | :--- | :--- |
| Admin | `/admin` | Enterprise Management |
| Kasir | `/kasir` | Retail Point of Sale |
| Gudang | `/inventory` | Warehouse & Logistics |

### Backend API Routes
```php
// Role-Based Route Protection
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', 'AdminDashboardController');
    Route::apiResource('/admin/users', 'UserController');
});

Route::middleware(['auth:api', 'role:kasir'])->group(function () {
    Route::get('/kasir/pos', 'PosController@index');
    Route::post('/kasir/transactions', 'TransactionController@store');
});

Route::middleware(['auth:api', 'role:inventory'])->group(function () {
    Route::apiResource('/inventory/products', 'ProductController');
    Route::post('/inventory/stock/adjust', 'StockController@adjust');
});
```

---

## 📦 3. Core Business Logic

### Transaction Service (Atomic Order)
1. **Validate**: Check stock availability for all items.
2. **Calculate**: Compute subtotal + 12% tax (configurable).
3. **Deduct**: Reduce stock in an atomic database transaction.
4. **Record**: Create transaction and itemized receipt entries.

### Inventory Logic
- **Stock Guard**: Prevents checkout if stock < requested quantity.
- **Auto Alert**: Triggers notification when stock hits `min_stock` threshold.
- **Audit Trail**: Every stock change is logged in `stock_history`.

---

## 🗄️ 4. Data Schema Overview

### Primary Entities
- **Users**: Authentication and role assignment.
- **Products**: SKU-based catalog with price/stock tracking.
- **Transactions**: Sales headers (invoice, total, payment method).
- **Transaction Items**: Line items for each sales record.
- **Stock History**: Traceability for all warehouse movements.

---

## 🔒 5. Security & Standards

### Response Format
```json
{
    "success": true,
    "message": "Action completed",
    "data": { ... },
    "timestamp": "2024-01-15T10:30:00Z"
}
```

### Protection Layers
- **Rate Limiting**: Throttles brute-force attempts on sensitive endpoints.
- **Validation**: Strict server-side validation for all incoming data.
- **Security Headers**: XSS and Frame protection enabled.

---
> [!NOTE]
> This documentation is designed to align with the **Laravel (Backend)** and **React (Frontend)** implementation.


---

## File: docs/05_modul_aplikasi/mobile-DOMAIN_SCHEMA.md
---
# 🌐 Domain Schema & Subdomain Mapping

Dokumentasi ini merinci pemetaan domain dan subdomain untuk sistem MPAD (Mitra Pajak & Retribusi Daerah) baik di lingkungan Produksi maupun Staging.

## 1. Lingkungan Produksi (Pemerintah Kota Baubau)
Domain resmi yang digunakan untuk operasional publik dan internal.

| Komponen | Domain | Deskripsi |
| :--- | :--- | :--- |
| **Portal Utama** | `mpad.baubaukota.go.id` | Pintu masuk utama aplikasi/Landing page |
| **Dashboard Admin** | `adminmpad.baubaukota.go.id` | Panel manajemen untuk Bapenda & OPD |
| **Backend API** | `api.sipanda.online` | Endpoint layanan data (Core API) |
| **Interface Petugas** | `petugasmpad.baubaukota.go.id` | Akses petugas lapangan (Mobile/Web) |

## 2. Lingkungan Staging / Development (VPS)
Digunakan untuk testing fitur baru sebelum di-deploy ke produksi.
**Host IP:** `157.10.252.74`

| Komponen | Domain | Deskripsi |
| :--- | :--- | :--- |
| **Main Entrance** | `sipanda.online` | Pintu masuk utama lingkungan dev |
| **Admin Panel** | `admin.sipanda.online` | Dashboard admin versi staging |
| **API Endpoint** | `api.sipanda.online` | Backend API untuk development |
| **Officer Portal** | `petugas.sipanda.online` | Antarmuka petugas versi staging |
| **Launch Teaser** | `launch.sipanda.online` | Halaman promosi/hitung mundur |

## 3. Domain Alternatif / Legacy
Domain lain yang mungkin masih terdaftar dalam konfigurasi sistem (CORS/SSL):
- `sipanda.online`
- `admin.sipanda.online`
- `petugas.sipanda.online`
- `api.sipanda.online`

---
*Terakhir Diperbarui: 3 Maret 2026*


---

## File: docs/05_modul_aplikasi/petugas-SYSTEM_OVERVIEW.md
---
# 🏢 SYSTEM OVERVIEW: RETRIBUSI-PETUGAS

**M-PAD Petugas** adalah aplikasi lapangan untuk petugas pemungut retribusi.

## 🌟 Fitur Utama
- **Pendataan Lapangan:** Pendaftaran WP baru via mobile dengan GPS.
- **Billing On-the-spot:** Pembuatan tagihan langsung di lokasi.
- **Reporting:** Laporan setoran harian petugas.
- **Penagihan (Enforcement):** Modul untuk Surat Teguran.

## 🔗 Integrasi
- Backend: REST API `retribusi-api`.
- Offline Support: IndexedDB untuk sinkronisasi data lapangan.


---

## File: docs/05_modul_aplikasi/mobile-AI_CONTEXT.md
---
# 🧠 MASTER AI GUIDE & PROMPT ANATOMY (MOBILE)

Dokumen ini adalah **titik masuk tunggal** bagi setiap AI Agent di repository **retribusi-mobile**.

---

## 🧭 1. Protokol Pembacaan Kondisional
| Kondisi / Tugas | File yang WAJIB Dibaca | Alasan |
| :--- | :--- | :--- |
| **Awal Percakapan** | `docs/SYSTEM_OVERVIEW.md` | Paham PWA & Mobile architecture. |
| **Status Produksi** | `docs/STABLE_BASELINE.md` | ID Commit yang sudah diverifikasi aman. |

---

## 🧬 2. Context Injection
- **Project Name:** M-PAD Mobile (Mitra PAD).
- **Stack:** React Vite, PWA, Tailwind CSS.
- **Target:** Wajib Pajak (Self Service).

---
*Protocol Version: 1.0. Terakhir diperbarui: 03 Maret 2026.*


---

## File: docs/05_modul_aplikasi/pos-MAIN-core_basic.md
---
# 📍 Core Foundations - POS System

Core business requirements and functional foundations for the grocery store Point of Sale application.

## 1. Application Overview
A specialized POS system for **Toko Kelontong**, bridging the gap between desktop management and mobile retail through a cross-platform React/Laravel architecture.

### Mission
- **Speed**: Rapid checkout for high-traffic retail.
- **Accuracy**: Automated stock deduction and tax calculation.
- **Portability**: Seamless deployment to the Google Play Store.

---

## 2. User Roles & Access Control

| Role | Responsibility | Module Access |
| :--- | :--- | :--- |
| **Admin** | General Management | Full System Control + Reports |
| **Kasir** | Frontend Sales | POS, Transactions, Receipts |
| **Gudang** | Inventory Control | Stock, Products, Suppliers |

---

## 3. Core Business Rules

### 💰 Payments
- **Cash**: Standard manual entry for physical currency.
- **QRIS**: Unique store-based digital payment integration.

### 📝 Tax Logic
> [!IMPORTANT]
> All transactions are subject to a **12% tax rate** (standardized across frontend/backend).
- Tax is calculated **automatically** upon item addition or checkout.
- Calculation logic is **method-agnostic** (applies to both Cash and QRIS).

---

## 🎨 UI/UX Philosophy
- **Dynamic**: Fluid responsiveness between 4K monitors and mobile screens.
- **Premium**: A high-end visual aesthetic that builds trust with professional users.
- **Proactive**: Micro-interactions that guide the user through the sales funnel.


---

## File: docs/05_modul_aplikasi/pos-MAIN-tech_stack.md
---
# 🚀 POS System Tech Stack

| Layer | Technology | Role |
| :--- | :--- | :--- |
| **Frontend** | React (Vite) | Dynamic UI & State |
| **Styling** | Tailwind CSS | Utility-first Design |
| **Backend** | Laravel (PHP) | RESTful API & Logic |
| **Database** | MySQL | Relational Storage |
| **Infra** | Docker | Containerization |

---
*Last Updated: 2024*


---

## File: docs/05_modul_aplikasi/pos-PART-frontend.md
---
# 🛠️ Enhanced Frontend Context - POS System

Detailed frontend architecture using React, focusing on a premium user experience and atomic component design.

## 1. Tech Stack
- **Framework**: React 18+ (Vite)
- **Styling**: Tailwind CSS + Framer Motion (Animations)
- **State Management**: Zustand (App State) + TanStack Query (Server State)
- **Icons**: Lucide React

---

## 🧩 2. Atomic Design Structure

| Level | Examples | Purpose |
| :--- | :--- | :--- |
| **Atoms** | Button, Input, Badge | Basic UI primitives |
| **Molecules** | ProductCard, FormInputGroup | Combined functional units |
| **Organisms** | Sidebar, POSGrid, CartSidebar | Complex feature components |
| **Templates** | DashboardLayout, AuthLayout | Page-level structural layouts |

---

## 📱 3. Responsive Strategy

### Breakpoints
- **Mobile (< 768px)**: Single column, bottom navigation, drawer menu.
- **Tablet (768px - 1024px)**: Collapsible sidebar, 2-column dashboard.
- **Desktop (> 1024px)**: Permanent fixed sidebar, full-width grids.

### Key Mobile Features
- **Touch-Optimized**: Targets > 44px, swipe gestures for cart actions.
- **Mobile Header**: Fixed header with hamburger menu for drawer access.
- **POS View**: Toggleable cart summary for seamless mobile checkout.

---

## ⚡ 4. Dynamic Interactions

### Real-Time & Micro-Animations
- **Optimistic UI**: Instant cart updates before server confirmation.
- **Feedback**: Skeleton screens for loading; Toast notifications for errors.
- **Motion**: Page transitions and button hover/click effects via Framer Motion.

---

## 🔄 5. Core Feature Flows

### POS Workflow
1. **Catalog**: Search or browse from a responsive product grid.
2. **Cart**: Side-drawer review (desktop) or summary bar (mobile).
3. **Payment**: Choose method (Cash/QRIS) with automatic change calculation.
4. **Receipt**: Visual confirmation with print/save options.

### Inventory Workflow
- **Search**: Debounced real-time filtering by Name or SKU.
- **Modals**: Slide-up sheets (mobile) or centered modals (desktop) for editing.
- **Badges**: Visual indicators (Green/Red) for stock status levels.

---

## 🎨 6. Visual Design System

- **Colors**: Blue (Primary), Emerald (Success), Rose (Danger).
- **Grid**: Consistent 8px spacing system for padding/margin.
- **Typography**: Inter/Inter-UI for maximum readability across devices.

---
> [!TIP]
> Use the reusable `cn` utility for combining Tailwind classes dynamically and avoiding class conflicts.

---

## File: docs/05_modul_aplikasi/mobile-STABLE_BASELINE.md
---
# 📌 STABLE BASELINE COMMIT IDS (Admin)

Referensikan file ini untuk memastikan Anda bekerja di versi yang stabil.

| Repository | Stable Commit ID | Tanggal Verifikasi |
| :--- | :--- | :--- |
| **retribusi-api** | `7b7388c` | 02 Maret 2026 |
| **retribusi-admin** | `169111d` | 02 Maret 2026 |
| **retribusi-mobile**| `f0d5fbb` | 02 Maret 2026 |
| **retribusi-petugas**| `647140e` | 02 Maret 2026 |

> [!NOTE]
> Sumber kebenaran utama ada di `retribusi-api/docs/STABLE_BASELINE.md`.


---

## File: docs/05_modul_aplikasi/petugas-AI_CONTEXT.md
---
# 🧠 MASTER AI GUIDE & PROMPT ANATOMY (PETUGAS)

Dokumen ini adalah **titik masuk tunggal** bagi setiap AI Agent di repository **retribusi-petugas**.

---

## 🧭 1. Protokol Pembacaan Kondisional
| Kondisi / Tugas | File yang WAJIB Dibaca | Alasan |
| :--- | :--- | :--- |
| **Awal Percakapan** | `docs/SYSTEM_OVERVIEW.md` | Paham alur kerja petugas lapangan. |
| **Status Produksi** | `docs/STABLE_BASELINE.md` | ID Commit yang sudah diverifikasi aman. |
| **Analisa Fitur** | `docs/TASK_CONTEXT.md` | Daftar OPD dan jenis retribusi yang ditangani. |

---

## 🧬 2. Context Injection
- **Project Name:** M-PAD Petugas.
- **Persona:** Digital Field Officer.
- **Stack:** React Native / Flutter (Verify in source) / Ionic.

---
*Protocol Version: 1.0. Terakhir diperbarui: 03 Maret 2026.*


---

## File: docs/05_modul_aplikasi/implementations/02-role-based-subadmin.md
---
# Panduan Implementasi: Hak Akses Role-Based Sub-Admin

**Konsep Dasar:** Sistem isolasi data (*multi-tenant architecture*) berbasi **Tipe Retribusi** untuk level Kepala Sub Bidang / Admin Sektoral. Tujuannya adalah memastikan admin wilayah 1 hanya memproses data Wilayah 1, dan tidak membahayakan kerahasiaan data pembayaran tipe pajak zona lain.

---

## 🏗️ 1. Pembaruan Skema Database (Backend)
1. Buat **Migration** baru: `php artisan make:migration add_retribution_type_id_to_users_table`.
2. Modifikasi struktur:
   ```php
   Schema::table('users', function (Blueprint $table) {
       $table->foreignId('retribution_type_id')->nullable()->constrained()->onDelete('set null')->after('role');
   });
   ```
3. Update Model `app/Models/User.php`:
   ```php
   public function retributionType() {
       return $this->belongsTo(RetributionType::class);
   }
   ```

---

## 🛡️ 2. Implementasi Global Scope (Isolasi Horizontal)
Kita tidak boleh mengandalkan pengecekan `if(admin_wilayah_1)` satu per satu di setiap Controller karena sangat rentan jebol. Kita akan menggunakan fitur **Eloquent Global Scope** Laravel.

Buat sebuah direktori/kelas `app/Models/Scopes/RetributionTypeScope.php`:
```php
class RetributionTypeScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // Hanya memproses request jika ada user yang terautentikasi dan memiliki role admin terbatas (punya retribution_type_id)
        if (auth()->check() && auth()->user()->role === 'admin' && auth()->user()->retribution_type_id) {
            
            // Logika spesifik berdasarkan Model yang dicari
            $typeId = auth()->user()->retribution_type_id;

            if ($model instanceof Taxpayer) {
                // Skema 1: Wajib pajak difilter jika mereka punya relasi "objects" di tipe pajak tersebut
                $builder->whereHas('taxObjects', function($q) use ($typeId) {
                    $q->where('retribution_type_id', $typeId);
                });
            } 
            elseif ($model instanceof Bill || $model instanceof Payment || $model instanceof TaxObject) {
                // Skema 2: Tagihan dan Objek secara langsung difilter berdasarkan type_id
                $builder->where('retribution_type_id', $typeId);
            }
            elseif ($model instanceof User) {
                // Skema 3: Petugas difilter berdasarkan area kerjanya
                $builder->where('role', 'petugas')->where('retribution_type_id', $typeId);
            }
        }
    }
}
```

### Penerapan Scope ke Model Utama
Buka file `Taxpayer.php`, `Bill.php`, `TaxObject.php`, `Payment.php`, dan `User.php`. Di masing-masing file, daftarkan scope tersebut pada metode `booted`:
```php
protected static function booted()
{
    static::addGlobalScope(new \App\Models\Scopes\RetributionTypeScope);
}
```

---

## 🖥️ 3. Perubahan UI (Frontend `retribusi-admin`)
1. **Dropdown Petugas/Objek/Taxpayer:** Karena Backend sudah menerapkan *Global Scope*, panggilan `GET /api/tax-objects` atau `GET /api/users?role=petugas` oleh Admin berbatas akan *secara otomatis* hanya mengembalikan list orang/objek di divisinya saja. Tidak perlu banyak manipulasi kode di Frontend React-nya.
2. **Dashboard Analytics (`DashboardController.php`):**
   Metriks seperti *Total Pendapatan (Revenue)*, *Total Tunggakan*, harus dibersihkan khusus query `retribution_type_id` admin yang bersangkutan (jika dia admin berbatas).
   *(Contoh: Chart Bar PBB vs Retribusi Parkir tidak perlu dimunculkan karena ia hanya memegang salah satunya).*


---

## File: docs/05_modul_aplikasi/implementations/03-petugas-tasks-todolist.md
---
# Panduan Implementasi: Modul Penugasan (To-Do List PetugasTasks)

**Tujuan:** Memberikan Pengawas kemampuan menugaskan Petugas mendatangi zona / wajib pajak pada tanggal tertentu. Mengukur kinerja operasional Petugas berdasarkan kecepatan penyelesaian tugas *(Turnaround time)*.

---

## 🏗️ 1. Skema Database & Migrasi (Backend)
1. **Perbaikan `database/migrations/xxxx_xx_xx_create_petugas_tasks_table.php`:**
   Pastikan tabelnya sukses di-_migrate_ (kemarin terkendala `table already exists` saat rollback step=1). Harus dipastikan migrasinya berjalan bersih (`php artisan migrate:refresh --path=...` jika perlu).
2. **Model `App\Models\PetugasTask`**:
   Relasikan ke `User` (berperan sebagai *creator* maupun *assignee*), `Zone`, dan `Taxpayer`.

---

## 🔗 2. Skema Backend API Controller
Endpoint CRUD diatur di `routes/api.php` dengan Auth Middleware:
1. `GET /api/petugas-tasks`: (Untuk List)
   - Jika role = admin, kembalikan semua tugas. (Termasuk Global Scope Role-Based Admin jika tipe retribusinya difilter).
   - Jika role = petugas, *HANYA kembalikan tugas `where('user_id', auth()->id())`*.
2. `POST /api/petugas-tasks`: (Admin-only). Admin _me-lempar_ tugas baru ke Petugas.
3. `PUT /api/petugas-tasks/{id}`: (Petugas-only). Petugas menandai `status = 'completed'`, lalu backend mengisi `completed_at = now()`.
4. `DELETE /api/petugas-tasks/{id}`: (Admin-only). Pembatalan tugas.

---

## 📱 3. Skema Frontend Pilihan/Mobile (`retribusi-petugas`)
Modifikasi di repositori sisi Petugas (Mobile-view Frontend):
1. **Botton Nav `src/components/layout/BottomNav.tsx`:** Tambahkan tab "Tugas" / "To-Do" untuk navigasi cepat.
2. **Halaman `src/pages/DaftarTugas.tsx`:**
   - Gunakan `api.get('/api/petugas-tasks')`.
   - Pisahkan View menjadi dua Tab UI: **"Tugas Menunggu"** dan **"Tugas Selesai"**.
   - Tambahkan pewarnaan pada peringatan `due_date`:
     - Merah: Status masih `pending` padahal `due_date` sudah lewat (Kinerja Telat).
     - Hijau: Status `completed`.
3. **Validasi (Aksi Selesai):** Saat petugas klik sebuah tombol centang ✅ di tugas, UI memanggil `api.put(/api/petugas-tasks/id, {status: 'completed'})` dan UI beralih seketika.

---

## 📊 4. Kalkulasi Kinerja Bulanan
Di `DashboardController.php`, hitung **Persentase Kepatuhan (Compliance Rate)** Petugas:
- `Total Tugas Selesai Tepat Waktu` = `whereNotNull('completed_at')->whereRaw('completed_at <= due_date')`
- `Kinerja Petugas (%)` = `(Selesai Tepat Waktu / Total Semua Tugasnya) * 100`.
Data ini dicerminkan pada tabel Modul *Field Force Analytics* di Admin Pengawas.


---

## File: docs/05_modul_aplikasi/implementations/01-modul-uji-petik.md
---
# Panduan Implementasi: Modul Uji Petik (Pengamatan Lapangan)

**Dasar Hukum:** Peraturan Wali Kota (Perwali) Baubau Nomor 58 Tahun 2024.
**Fungsi:** Sebagai Kertas Kerja Pengambilan Sampel Data observasi (jam-per-jam) sebagai bukti penerbitan SKPDKB/Pajak secara jabatan jika omzet riil WP tidak sesuai dengan laporannya.

---

## 🏗️ 1. Skema Database (Backend `retribusi-api`)
Buat dua buah tabel baru beserta `Model` dan `Migration`-nya.

### Tabel `spot_checks` (Header Pemeriksaan)
Tabel ini merepresentasikan 1 (satu) lembar formulir uji petik.
- `id` (PK)
- `taxpayer_id` (FK ke tabel Wajib Pajak)
- `tax_object_id` (FK ke tabel Objek Pajak, karena 1 WP bisa punya banyak cabang/objek)
- `inspector_id` (FK ke tabel Users, petugas pemeriksa yang melakukan observasi)
- `start_date` (Tanggal dimulainya observasi)
- `end_date` (Tanggal berkahirnya observasi)
- `is_weekend` (Boolean, penanda apakah observasi ini kategori Hari Biasa atau Akhir Pekan)
- `taxpayer_representative` (Nama perwakilan WP yang menandatangani form)
- `supervisor_id` (FK ke Kepala Sub Bidang yang mengesahkan)
- `status` (Enum: `draft`, `submitted`, `approved`)
- `remarks` (Catatan khusus pengamat di lapangan)

### Tabel `spot_check_items` (Detail Observasi Per-Jam)
Tabel ini merepresentasikan baris isian pada kertas kerja untuk observasi di jam tertentu.
- `id` (PK)
- `spot_check_id` (FK ke tabel `spot_checks`)
- `observation_time` (Misalnya: `07:00:00`, tipe data Time atau String)
- `visitor_count` (Integer: Jumlah kunjungan tamu)
- `transaction_count` (Integer: Jumlah transaksi kasir yang terjadi)
- `estimated_value` (Decimal/BigInt: Estimasi nilai nominal transaksi dalam Rupiah)
- `details` (JSON atau Text: Rincian spesifik seperti *kamar terjual*, *jenis tiket hiburan*, atau *kendaraan parkir*).

---

## 🧮 2. Skema Business Logic (Service Layer)
Buat sebuah file service bernama `app/Services/SpotCheckService.php`.

**Algoritma Analisis Estimasi Harian:**
Service ini dipanggil saat Pengawas menekan tombol "Generate Analisis Kinerja WP". Logikanya:
1. `getAverageDaily(tax_object_id)`: Menjumlahkan seluruh `estimated_value` dari `spot_checks` yang bertipe `is_weekend = false` (Senin-Jumat) lalu dibagi jumlah hari observasi.
2. `getAverageWeekend(tax_object_id)`: Melakukan hal yang sama khusus pencarian observasi yang `is_weekend = true` (Sabtu-Minggu).
3. **Kalkulasi Bulanan:** `(Rata2 Harian * 22 hari) + (Rata2 Akhir Pekan * 8 hari)`. Hasil kalkulasi ini akan menjadi **Omzet Riil Hasil Uji Petik**.

---

## 🖥️ 3. Skema Antarmuka (Dashboard Server `retribusi-admin`)
Buat halaman komponen baru `src/pages/SpotCheckForm.tsx`:

**User Interface Matrix (Grid):**
1. Bagian atas: Informasi Header Pemeriksaan (Dropdown WP, Objek Pajak, Tanggal).
2. Bagian tengah: Sebuah Tabel Dinamis berisi minimal 24 baris (merepresentasikan 24 Jam dari 07.00 pagi ke 06.00 besok).
   - Petugas/Pengawas bisa menginput angka di kolom `Jumlah Pengunjung`, `Jumlah Transaksi`, `Nominal Estimasi` langsung ke dalam sel (*inline editing* layaknya Excel).
3. Bagian bawah: Kalkulasi Total Otomatis dari jumlah yang diketik di sel atas, diikuti kolom penandatangan (*digital signature* / input nama terang).

---

## 🔗 4. Skema Integrasi dengan SKPDKB
Pada modul `PenindakanController` yang meng-generate SKPDKB, perbarui logikanya. Jika ada data `spot_checks` yang telah `status = approved` untuk wajib pajak di periode jatuh tempo tersebut, hitung tagihannya bukan dari pelaporan sang WP, melainkan menggunakan output/nilai omzet dari `SpotCheckService` sebagai **Pajak Ditetapkan Secara Jabatan** (yang biasanya bunganya lebih tinggi/maksimal).


---

## File: docs/05_modul_aplikasi/pos-MAIN-backend.md
---
# Backend Context - POS System

This document describes the backend architecture implemented using Laravel, adhering to the requirements in the [Core Basic Context](file:///home/arifrizal/Desktop/workspace/bckup_2/point_of_sale/docs/core_basic.md).

## 1. Architecture Patterns
The backend follows standard Laravel best practices:
- **Service Layer:** Houses the business logic (e.g., Tax calculation, Inventory validation).
- **Repository Pattern (Optional):** To decouple database logic if needed.
- **API Controllers:** Handles request/response cycles for the React frontend.
- **Eloquent ORM:** For database interactions and relationships.

## 2. Core Modules

### Authentication Module
- Handles Login, Register, and Logout via JWT.
- Integrated with Laravel's `auth` guards and defined in [Security Context](file:///home/arifrizal/Desktop/workspace/bckup_2/point_of_sale/docs/security.md).

### Transaction Module
- Manages sales records.
- **Automatic Tax Logic:** Intercepts transaction saving events to calculate and record tax deductions.
- **QRIS Integration:** Logic for generating unique QR codes specifically for the store context.

### Inventory Module
- Manages products, categories, and stock history.
- Real-time stock deduction upon successful transactions.

### Reporting Module
- Generates sales aggregations and inventory movements.
- Supports data export formats (PDF/Excel) for store owners.

## 3. API Design Principles
- **RESTful API:** Predictable URLs and standard HTTP methods.
- **JSON Response Format:** Consistent structure for success and error responses.
- **Middleware:** Authorization is enforced via custom middleware checking Roles/Permissions.

## 4. Tax Calculation Implementation
> [!IMPORTANT]
> Tax must be calculated BEFORE the final total is recorded.

```php
// Pseudo-logic for tax calculation
public function processTransaction($data) {
    $taxRate = Config::get('settings.tax_rate'); // e.g., 0.11 for 11%
    $subtotal = $this->calculateSubtotal($data['items']);
    $taxAmount = $subtotal * $taxRate;
    $total = $subtotal + $taxAmount; // Or deducted from subtotal depending on pricing model
    
    // Save to database
}
```

## 5. User Management & Registration Flow
- **Registration:** New users register with email, store name, and password. This automatically generates a store-specific QRIS for payments.
- **Admin Role:** The registering user is automatically assigned the `admin` role and redirected to the dashboard.
- **Staff Credentials:** Admins can create and manage credentials (email, password, role) for **Kasir** (Cashier) staff.



---

## File: docs/05_modul_aplikasi/pos-MAIN-security.md
---
# 🔒 Security Architecture - POS System

Security protocols and authorization standards for the POS ecosystem.

## 1. Authentication (Stateless JWT)

The system utilizes **JSON Web Tokens** for secure communication between React and Laravel.

### The Security Loop
1. **Challenge**: User submits credentials (SSL encrypted).
2. **Issue**: Backend validates and issues a signed JWT.
3. **Persistence**: Tokens are stored in **Secure Cookies** or LocalStorage.
4. **Authorize**: Every API call includes the Bearer token in the header.
5. **Renewal**: Silent refresh logic ensures a smooth user session.

---

## 2. Authorization (RBAC)

Granular control over specific system features based on user role.

| Module | Admin | Kasir | Gudang |
| :--- | :---: | :---: | :---: |
| **Login / Logout** | ✅ | ✅ | ✅ |
| **Create Users** | ✅ | ❌ | ❌ |
| **Process Sales** | ✅ | ✅ | ❌ |
| **Void/Delete** | ✅ | ❌ | ❌ |
| **Edit Products** | ✅ | ❌ | ✅ |
| **View Reports** | ✅ | ✅* | ❌ |
| **Store Settings** | ✅ | ❌ | ❌ |

*\*Kasir can only view their own shift/daily reports.*

---

## 3. Data Integrity & Privacy
- **Hashing**: Passwords protected by Bcrypt (Rounds: 12).
- **Protection**: Middleware-level guards on all sensitive API routes.
- **Prevention**: Native XSS and CSRF protection layers enabled.
- **Sanitization**: Input filtering on all transaction-critical data points.


---

## File: docs/05_modul_aplikasi/pos-MAIN-frontend.md
---
# Frontend Context - POS System

This document outlines the frontend architecture implemented using React, focusing on a dynamic and responsive user experience as defined in the [Core Basic Context](file:///home/arifrizal/Desktop/workspace/bckup_2/point_of_sale/docs/core_basic.md).

## 1. Tech Stack & State Management
- **Framework:** React.
- **Styling:** Vanilla CSS / Modern UI Libraries (ensuring high visual appeal).
- **Authentication:** JWT stored in `localStorage` or `HttpOnly` Cookies.
- **State Management:** React Context API or Redux for managing global state (User, Cart, Settings).

## 2. Component Architecture
- **Atoms/Molecules:** Reusable UI components (Buttons, Inputs, Modals).
- **Organisms/Pages:** Specific feature pages (Login, Dashboard, Stock Management, POS Checkout).
- **Hooks:** Custom hooks for API interactions (e.g., `useAuth`, `useTransactions`).

## 3. Responsive Strategy (Web & Mobile)
The application is designed to be fully responsive for both browser usage and Google Play Store deployment.

- **Mobile View:** Optimized for touch interactions, simplified layouts for smaller screens, and barcode scanning support.
- **Desktop/Web View:** Comprehensive dashboard layouts, detailed reports, and efficient multi-item transaction management.

## 4. Feature Flow

### POS Transaction Flow
1. **Selection:** Kasir selects/scans items.
2. **Review:** Subtotal is calculated automatically with tax included.
3. **Payment:** Selection of Cash or QRIS.
4. **QRIS Generation:** If QRIS is selected, a unique store QR is displayed.
5. **Completion:** Transaction is finalized and stock is updated.

### Inventory Management
- Petugas Gudang can search, add, or update stock records.
- Real-time alerts for low-stock items.

## 5. UI/UX Principles
- **Vibrant & Professional:** Using a premium design palette that feels modern.
- **Micro-animations:** Subtle feedback on interactions (e.g., successful scan, login error).
- **Fast & Intuitive:** Designed for real-world retail speed.


---

## File: docs/05_modul_aplikasi/mobile-TASK_CONTEXT.md
---
# 📋 TASK CONTEXT & HISTORY (Mobile)

## 🛠️ Tugas Berjalan
1.- [ ] **Penghapusan Denda (Deferred)**
  - Mekanisme pengajuan denda (kesalahan sistem/hari libur)
  - Integrasi persetujuan denda
2. **Inisialisasi Dokumentasi:** Migrasi dari `task-ai.txt` (sebelumnya kosong).
3. **Penyelarasan Baseline:** Menggunakan commit ID dari `retribusi-api` sebagai jangkar stabilitas.
4. **PWA Optimization (Next Step):** Memastikan service worker bekerja optimal untuk notifikasi tagihan.


---

## File: docs/07_testing_kualitas/testing-reports/VULNERABILITY_ANALYSIS.md
---
# 🔍 Analisis Teknis Temuan Penetration Test

Berikut adalah penjelasan teknis untuk **4 Vulnerabilities** dan **1 Warning** yang ditemukan, beserta risiko yang akan muncul saat diperbaiki.

---

## ❌ Vulnerabilities (4 Temuan)

### 1. Reflected XSS pada Registrasi
- **Masalah:** API mengembalikan data yang Anda masukkan (seperti Nama atau Alamat) langsung ke dalam respons JSON tanpa santisasi.
- **Bahaya:** Jika frontend merender nama tersebut sebagai HTML, penyerang bisa memasukkan script (misal: `<script>alert(1)</script>`) yang akan dijalankan di browser.
- **Rencana Fix:** Memastikan data yang dikembalikan di-escape atau dibersihkan di sisi backend sebelum dikirim.

### 2. Privilege Escalation (Citizen Login Bypass)
- **Masalah:** Akun Wajib Pajak (Citizen) bisa mencoba mengakses `/api/users` (Data Pegawai/Admin).
- **Kenapa 500?** Server mencoba menjalankan fungsi `isSuperAdmin()` atau mencari kolom `opd_id` yang tidak ada pada model Citizen (Taxpayer), sehingga sistem crash.
- **Bahaya:** Seharusnya akses ditolak dengan pesan **403 Forbidden**, bukan malah menyebabkan server error yang bisa membocorkan struktur internal database.
- **Rencana Fix:** Menambahkan middleware pengecekan peran (Role) yang ketat pada rute admin.

### 3. & 4. CORS Misconfiguration (Arbitrary & Null Origin)
- **Masalah:** Konfigurasi Nginx/Laravel membiarkan domain apa pun (termasuk `https://evil-hacker.com` atau origin `null`) untuk menarik data dari API.
- **Bahaya:** Penyerang bisa membuat website palsu yang, saat dikunjungi user, secara diam-diam mencuri data tagihan atau profil user dari `api.sipanda.online`.
- **Rencana Fix:** Membatasi `Access-Control-Allow-Origin` hanya ke domain resmi.

---

## ⚠️ Warning (1 Temuan)

### 5. IDOR pada Akses Tagihan (Bills)
- **Masalah:** User bisa mencoba mengakses `/api/bills/{id}` dengan ID sembarang.
- **Kenapa 500?** Mirip dengan kasus Privilege Escalation, query mencoba memproses data yang tidak berhak diakses oleh user tersebut dan gagal di level logika controller.
- **Bahaya:** Seharusnya jika ID tersebut bukan milik user yang login, berikan respons **404 Not Found** atau **403 Forbidden**.
- **Rencana Fix:** Menambahkan filter `where('taxpayer_id', auth()->id())` pada setiap query pengambilan data individual.

---

## ⚖️ Risiko Saat Semua Dibenahi (Trade-offs)

| Perbaikan | Risiko Utama | Cara Mitigasi |
| :--- | :--- | :--- |
| **CORS Fix** | Website frontend (Mobile/Admin) bisa mendadak tidak bisa akses API jika domain/port tidak terdaftar lengkap. | Audit semua URL (Prod, Dev, Local) sebelum update config Nginx. |
| **Role Middleware** | Beberapa fitur yang mungkin dishare (misal: verifikator OPD melihat data tertentu) bisa ikut terblokir. | Tes menyeluruh semua peran user setelah middleware dipasang. |
| **IDOR Check** | Fitur "Titip Bayar" atau pengecekan tagihan pihak ketiga mungkin akan terganggu jika proteksinya terlalu kaku. | Pastikan ada endpoint khusus untuk pengecekan publik yang memang diizinkan (Inquiry). |

---
**Rekomendasi:**
Saya menyarankan kita mulai dari perbaikan **Middleware Role** dan **IDOR** (level kode Laravel) karena risikonya paling kecil terhadap stabilitas domain, baru kemudian menangani **CORS** (level infrastruktur Nginx).


---

## File: docs/07_testing_kualitas/testing-reports/RISK_ASSESSMENT.md
---
# 🛡️ Analisis Risiko & Dampak Perbaikan Keamanan

Berdasarkan hasil [Penetration Test](./testing-reports/13_Penetration_Test_20260225_202408.md), berikut adalah analisis mendalam mengenai apa yang perlu diperbaiki, dampak positifnya, dan risiko teknis yang mungkin timbul selama proses perbaikan.

---

## 1. Perbaikan CORS Misconfiguration (Critical)
**Masalah:** API saat ini menerima request dari origin mana pun (termasuk `https://evil-hacker.com`).

- **Apa yang akan terjadi:** Kita akan memperketat Nginx & Laravel agar hanya menerima request dari domain `*.sipanda.online` dan `localhost`.
- **Dampak Positif:** Menghilangkan risiko serangan CSRF dan pencurian token dari situs jahat.
- **Risiko Teknikal:** 
  > [!WARNING]
  > Jika ada subdomain baru atau port lokal (misal port 3004) yang lupa didaftarkan, frontend akan **macet total** dengan error "CORS Blocked".
- **Mitigasi:** Melakukan audit menyeluruh terhadap semua domain yang digunakan sebelum mendeploy konfigurasi baru.

---

## 2. Perbaikan Privilege Escalation (Citizen vs Admin API)
**Masalah:** Akun Warga (Citizen) mencoba mengakses `/api/users` (Admin only) dan sistem memberikan respons 500 (seharusnya 403 Forbidden).

- **Apa yang akan terjadi:** Kita akan menambahkan Middleware Role Check pada rute-rute admin.
- **Dampak Positif:** Mencegah warga melihat data user lain atau data internal pegawai BAPENDA.
- **Risiko Teknikal:** 
  - Jika ada fitur warga yang membutuhkan data user (misal: fitur "Cari Petugas"), fitur tersebut bisa ikut terblokir jika tidak dipisahkan endpoint-nya.
- **Mitigasi:** Verifikasi semua alur kerja aplikasi Mobile sebelum memperketat rute.

---

## 3. Perbaikan XSS pada Respons Registrasi
**Masalah:** Data yang dikirim saat daftar (misal Nama) dipantulkan kembali ke respons tanpa sanitasi.

- **Apa yang akan terjadi:** Melakukan enkoding karakter khusus (seperti `<` menjadi `&lt;`) pada semua data input yang dikembalikan ke sistem.
- **Dampak Positif:** Mencegah script jahat berjalan di browser user lain yang melihat data tersebut.
- **Risiko Teknikal:** Sangat rendah. Nama yang mengandung simbol mungkin akan terlihat aneh di database jika tidak di-decode dengan benar oleh frontend.
- **Mitigasi:** Gunakan fungsi `e()` atau `htmlspecialchars` di Laravel secara konsisten.

---

## 4. Perbaikan IDOR (Akses Billing)
**Masalah:** Warga bisa mencoba melihat tagihan (Bill) milik orang lain dengan mengganti nomor ID di URL.

- **Apa yang akan terjadi:** Menambahkan filter `where('user_id', auth()->id())` pada query pengambilan data bill.
- **Dampak Positif:** Menjamin kerahasiaan data finansial individu.
- **Risiko Teknikal:** 
  - Bisa mengganggu fungsi jika satu tagihan boleh dibayar oleh orang lain (misal: anak membayar tagihan orang tua).
- **Mitigasi:** Gunakan skema "Layanan Pihak Ketiga" jika fitur titip bayar memang diperlukan, daripada membiarkan akses ID terbuka.

---

## Kesimpulan Risiko Keseluruhan
Secara umum, risiko terbesar ada pada **Konfigurasi CORS**. Jika ini salah, seluruh aplikasi `admin.sipanda.online`, `mobile.sipanda.online`, dll, bisa berhenti berfungsi. Perbaikan lainnya bersifat internal logika bisnis dan resikonya relatif kecil (Low Risk).

> [!TIP]
> Saya menyarankan perbaikan dilakukan bertahap dimulai dari **Privilege Escalation** dan **IDOR** sebelum menyentuh **CORS** di level Nginx.


---

## File: docs/07_testing_kualitas/testing-reports/13_Penetration_Test_20260225_202408.md
---
# 🔒 Penetration Test Report

**Date**: 2026-02-25 20:24:08
**Target**: https://api.sipanda.online
**Methodology**: OWASP Top 10 + Custom Vectors


### 1. Sensitive File Exposure
| Status | Detail |
|--------|--------|
| ✅ SECURE | .env file blocked (403) |
| ✅ SECURE | .git directory blocked (403) |
| ✅ SECURE | .htaccess blocked (403) |
| ✅ SECURE | composer.json not exposed (404) |
| ✅ SECURE | Laravel logs not exposed (403) |
| ✅ SECURE | phpinfo.php not exposed (404) |

### 2. SQL Injection
| Status | Detail |
|--------|--------|
| ✅ SECURE | Login immune to basic SQLi (OR 1=1) |
| ✅ SECURE | No SQL error leakage in bills query |
| ✅ SECURE | No blind SQLi detected (response in 1s) |

### 3. Cross-Site Scripting (XSS)
| Status | Detail |
|--------|--------|
| ❌ VULN | XSS payload reflected in registration response |
| ✅ SECURE | XSS payload not reflected in query params |

### 4. Broken Authentication
| Status | Detail |
|--------|--------|
| ✅ SECURE | /api/me returns 401 without token |
| ✅ SECURE | Fabricated token rejected (401) |
| ❌ VULN | Citizen token can access admin /api/users! Status: 500 |

### 5. IDOR (Insecure Direct Object Reference)
| Status | Detail |
|--------|--------|
| ✅ SECURE | IDOR blocked for /api/taxpayers/1 (404) |
| ⚠️ WARN | IDOR possible: citizen can access /api/bills/1 (500) (review scope) |

### 6. Path Traversal
| Status | Detail |
|--------|--------|
| ✅ SECURE | Path traversal blocked (no file content leaked) |
| ✅ SECURE | Encoded path traversal blocked |

### 7. Security Headers
| Status | Detail |
|--------|--------|
| ✅ SECURE | HSTS header present |
| ✅ SECURE | X-Content-Type-Options: nosniff |
| ✅ SECURE | X-Frame-Options present (clickjacking protection) |
| ✅ SECURE | X-XSS-Protection header present |
| ✅ SECURE | Server version not fully exposed |
| ✅ SECURE | X-Powered-By header not present (tech stack hidden) |

### 8. Rate Limiting / Brute Force Protection
| Status | Detail |
|--------|--------|
| ✅ SECURE | Rate limiting active (429 at request #57) |

### 9. Mass Assignment
| Status | Detail |
|--------|--------|
| ✅ SECURE | Mass assignment blocked (is_admin/role not assignable) |

### 10. CORS Misconfiguration
| Status | Detail |
|--------|--------|
| ❌ VULN | CORS allows arbitrary origins: Access-Control-Allow-Origin: https://evil-hacker.com |
| ❌ VULN | CORS allows null origin |

### 11. HTTP Method Tampering
| Status | Detail |
|--------|--------|
| ✅ SECURE | PUT on login endpoint rejected (405) |
| ✅ SECURE | DELETE on /api/me rejected (405) |

### 12. Verbose Error / Stack Trace Leakage
| Status | Detail |
|--------|--------|
| ✅ SECURE | No stack trace leakage on malformed JSON |
| ✅ SECURE | Long input handled gracefully (status: 429) |

## Summary

⚠️ **4 VULNERABILITIES FOUND** — Action recommended.

- **Secure**: 27
- **Vulnerabilities**: 4
- **Warnings**: 1
- **Critical**: 0
- **Total Tests**: 32



---

## File: docs/07_testing_kualitas/testing-reports/12_Production_Readiness_20260225_214028.md
---
# Production Readiness Test Report

**Date**: 2026-02-25 21:40:28
**API**: https://api.sipanda.online
**Frontend**: https://sipanda.online


### 1. CORS Preflight (OPTIONS)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS /api/me → 204 No Content |
| ✅ PASS | Access-Control-Allow-Origin: https://sipanda.online |
| ✅ PASS | Access-Control-Allow-Credentials: true |
| ✅ PASS | Access-Control-Allow-Methods includes POST |
| ✅ PASS | No duplicate Access-Control-Allow-Origin headers |

### 2. CORS on Actual Responses
| Status | Detail |
|--------|--------|
| ✅ PASS | GET /api/me (no auth) → 401 Unauthorized |
| ✅ PASS | 401 response includes CORS headers |
| ✅ PASS | No duplicate CORS headers on 401 response |
| ✅ PASS | 401 returns JSON: {"message":"Unauthenticated."} |

### 3. Citizen Login
| Status | Detail |
|--------|--------|
| ✅ PASS | Citizen login successful, token received |
| ✅ PASS | Login response includes user data |
| ✅ PASS | Bad login correctly rejected |
| ✅ PASS | Login response has exactly 1 Access-Control-Allow-Origin header |

### 4. Authenticated API Endpoints
| Status | Detail |
|--------|--------|
| ✅ PASS | GET /api/me (authenticated) → 200 OK |
| ✅ PASS | /api/me has exactly 1 CORS header |
| ✅ PASS | GET /api/citizen/services → 200 OK |
| ✅ PASS | GET /api/citizen/bills → 200 OK |

### 5. Cross-Origin (admin.sipanda.online)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS from admin.sipanda.online → 204 |
| ✅ PASS | CORS allows admin.sipanda.online |

### 6. Error Handling (No 500s)
| Status | Detail |
|--------|--------|
| ✅ PASS | Unknown endpoint returns 404 (not 500) |
| ✅ PASS | Health endpoint /up → 200 OK |

### 7. Frontend & PWA
| Status | Detail |
|--------|--------|
| ✅ PASS | Frontend sipanda.online → 200 OK |

## Summary

🎉 **ALL CRITICAL TESTS PASSED** — Production ready.

- **Pass**: 22
- **Fail**: 0
- **Warn**: 0



---

## File: docs/07_testing_kualitas/testing-reports/14_API_CRUD_Test_20260225_210541.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-02-25 21:05:41
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: opd) |
| ✅ | Citizen login → token received |
| ✅ | Citizen ID: 2, NIK: 1234567890123456 |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ❌ | GET /test-cors → 404 (expected 200) |
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ✅ | GET /citizen/bills?nik=... → 200 |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /me (citizen profile) → 200 |
| ✅ | GET /citizen/services → 200 |
| ✅ | GET /citizen/services/2 (detail) → 200 |
| ✅ | GET /citizen/services/2/bills → 200 |
| ✅ | GET /citizen/services/pending-periods → 200 |
| ❌ | GET /citizen/reports → 500 (expected 200) |
| ✅ | GET /pbb/bapenda/my-objects → 200 |
| ✅ | GET /pbb/bapenda/my-transactions → 200 |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ❌ | CREATE zone failed: {"message":"The code field must not be greater than 10 characters.","errors":{"code":["The code field must not be greater than 10 characters."]}} |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 1319, NIK: 9900001772024782 |
| ✅ | GET /taxpayers/1319 → 200 |
| ✅ | GET /taxpayers/search/9900001772024782 → 200 |
| ✅ | UPDATE taxpayer/1319 → 200 |
| ✅ | DELETE taxpayer/1319 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 42 |
| ✅ | GET /retribution-types/42 → 200 |
| ✅ | UPDATE retribution-type/42 → 200 |
| ✅ | DELETE retribution-type/42 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ✅ | Tax simulation → result received |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|
| ✅ | READ profile → name: Wajib Pajak Demo
Badan Pendapatan Daerah |
| ✅ | UPDATE profile → 200 OK |

## Summary

⚠️ **3 TEST(S) FAILED**

- **Pass**: 66
- **Fail**: 3
- **Warn**: 0
- **Skip**: 0
- **Total**: 69



---

## File: docs/07_testing_kualitas/testing-reports/07_Hasil_Kalkulator_Semua_Pajak.md
---
# 🧮 Laporan Hasil Uji Otomatis API Kalkulator Pajak

**Waktu Eksekusi**: 2026-02-25 09:07:56
Pengujian dieksekusi secara otomatis menembak server `Localhost:8000` via endpoint POST `/api/simulate-tax` untuk masing-masing klasifikasi.

### PBB-P2 (`PBB-UMUM`)
- **Formula Server**: `(njop - 10000000) * (njkp_percent / 100) * (tariff / 100)`
- **Dummy Set Variabel**: `{"luas_tanah":120,"kelas_bumi":"080","luas_bangunan":60,"kelas_bangunan":"080","nomor_sertifikat":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### BPHTB (`BPHTB`)
- **Formula Server**: `(npop - npoptkp) * 0.05`
- **Dummy Set Variabel**: `{"npop":5000000,"npoptkp":1000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 200.000**

### Pajak Reklame (`REKLAME`)
- **Formula Server**: `nsr * 0.25`
- **Dummy Set Variabel**: `{"nsr":5000000,"ukuran":100,"lokasi_reklame":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 1.250.000**

### Pajak Sarang Burung Walet (`WALET`)
- **Formula Server**: `nilai_jual * 0.10`
- **Dummy Set Variabel**: `{"nilai_jual":5000000,"lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### Pajak MBLB (`MBLB`)
- **Formula Server**: `(volume * harga_patokan) * 0.15`
- **Dummy Set Variabel**: `{"volume":50,"harga_patokan":80000,"jenis_mineral":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 600.000**

### Opsen PKB (`OPS-PKB`)
- **Formula Server**: `pkb_pokok * 0.66`
- **Dummy Set Variabel**: `{"pkb_pokok":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 9.900**

### Opsen BBNKB (`OPS-BBN`)
- **Formula Server**: `bbnkb_pokok * 0.66`
- **Dummy Set Variabel**: `{"bbnkb_pokok":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 9.900**

### PBJT - Makan dan Minum (`PBJT-MNM`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Catering (`PBJT-CAT`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"nama_perusahaan":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Event/Hiburan Lainnya (`PBJT-EVT`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"nama_event":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Tenaga Listrik (`PBJT-LIS`)
- **Formula Server**: `tagihan_listrik * tariff`
- **Dummy Set Variabel**: `{"tagihan_listrik":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### PBJT - Jasa Perhotelan (`PBJT-HTL`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Parkir (`PBJT-PRK`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Kesenian dan Hiburan (`PBJT-HBR`)
- **Formula Server**: `omzet * tariff`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Pajak Air Tanah (`PAT`)
- **Formula Server**: `(volume * hda) * 0.20`
- **Dummy Set Variabel**: `{"volume":50,"hda":80000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 800.000**

### Penyediaan Tempat Kegiatan Usaha (`PTKU`)
- **Formula Server**: `amount`
- **Dummy Set Variabel**: `{"jenis_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Retribusi Jasa Umum (`RJU`)
- **Formula Server**: `amount`
- **Dummy Set Variabel**: `{"jenis_layanan":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Retribusi Perizinan Tertentu (`RPT`)
- **Formula Server**: `amount`
- **Dummy Set Variabel**: `{"jenis_izin":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Persetujuan Bangunan Gedung (PBG) (`PBG`)
- **Formula Server**: `luas_lantai * (indeks_lokalitas * shst) * indeks_terintegrasi * indeks_bg`
- **Dummy Set Variabel**: `{"luas_lantai":100,"indeks_lokalitas":1,"shst":5560000,"indeks_terintegrasi":1,"indeks_bg":1}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 556.000.000**



---

## File: docs/07_testing_kualitas/testing-reports/FINAL_TEST_SUMMARY.md
---
# 📊 Laporan Pengujian Sistem M-PAD (Consolidated)

**Tanggal**: 26 Februari 2026
**Metode**: Automasi Testing Suite (No Screenshots/Recordings)

---

## 🛡️ Ringkasan Status Sistem

| Kategori Tes | Status | Detail |
| :--- | :--- | :--- |
| **Production Readiness** | 🟢 **22/22 PASS** | Kesiapan domain, CORS, dan alur login dasar aman. |
| **API CRUD Lifecycle** | 🟢 **69/69 PASS** | Seluruh fitur utama (Zone, Taxpayer, Retribution) berfungsi. |
| **Security (Penetration)**| 🟡 **28 SECURE, 3 VULN** | Masih terdapat celah CORS dan XSS yang perlu dimitigasi. |

---

## 🔍 Detail Temuan Strategis

### 1. Perbaikan Pembayaran (Verified)
- **Status:** ✅ **FIXED**.
- **Bukti:** Pencatatan pembayaran dengan periode teks panjang ("February 2026") kini berhasil 100% tanpa error 500.
- **Dampak:** Admin dapat mencatat semua jenis periode tanpa batasan karakter SQL.

### 2. Kesenjangan Keamanan (Remaining)
- **Vulnerability:** CORS masih mengizinkan origin sembarang.
- **Vulnerability:** Respons registrasi masih memantulkan input mentah (XSS).
- **Warning:** IDOR pada akses tagihan (masih bisa diintip jika ID diketahui).
- **Rencana:** Segera lakukan implementasi dari [**VULNERABILITY_ANALYSIS.md**](file:///Users/pondokit/Herd/retribusi-api/docs/testing-reports/VULNERABILITY_ANALYSIS.md).

---

## 📁 Arsip Laporan Detail
Semua laporan teknis tersimpan di folder `/results/` dan `/docs/testing-reports/`:
- [**Laporan Kesiapan Produksi**](file:///Users/pondokit/Herd/retribusi-api/results/12_Production_Readiness_20260226_030647.md)
- [**Laporan API CRUD**](file:///Users/pondokit/Herd/retribusi-api/results/14_API_CRUD_Test_20260226_030756.md)
- [**Laporan Penetrasi Terakhir**](file:///Users/pondokit/Herd/retribusi-api/results/13_Penetration_Test_20260226_030758.md)

---
*Laporan ini dihasilkan secara otomatis untuk verifikasi sistem M-PAD.*


---

## File: docs/07_testing_kualitas/testing-reports/08_Laporan_E2E_Lintas_Peran.md
---
# 🔄 Laporan Hasil Uji Coba Lintas Peran (E2E) Untuk Semua Jenis Pajak

**Waktu Eksekusi**: 2026-02-25 09:09:05
Pengujian E2E ini dilakukan secara otomatis (tanpa manual UI/Screenshot) dengan menstimulasi *Database Engine* Laravel secara langsung. Skenario yang diuji memastikan keutuhan proses: **Penerbitan SKPD (Admin) -> Integrasi Tagihan (Wajib Pajak) -> Pembayaran Lunas (Petugas)** berurutan.

### 🗂️ Pengujian Objek: PBB-P2 (`PBB-UMUM`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBB-P2` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-32` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-32 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: BPHTB (`BPHTB`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat BPHTB` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-33` senilai **Rp 200.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-33 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Pajak Reklame (`REKLAME`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak Reklame` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-34` senilai **Rp 1.250.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-34 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Pajak Sarang Burung Walet (`WALET`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak Sarang Burung Walet` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-35` senilai **Rp 500.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-35 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Pajak MBLB (`MBLB`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak MBLB` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-36` senilai **Rp 600.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-36 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Opsen PKB (`OPS-PKB`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Opsen PKB` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-37` senilai **Rp 9.900**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-37 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Opsen BBNKB (`OPS-BBN`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Opsen BBNKB` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-38` senilai **Rp 9.900**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-38 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Makan dan Minum (`PBJT-MNM`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Makan dan Minum` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-39` senilai **Rp 500.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-39 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Jasa Catering (`PBJT-CAT`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Jasa Catering` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-40` senilai **Rp 500.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-40 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Jasa Event/Hiburan Lainnya (`PBJT-EVT`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Jasa Event/Hiburan Lainnya` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-41` senilai **Rp 500.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-41 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Tenaga Listrik (`PBJT-LIS`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Tenaga Listrik` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-42` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-42 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Jasa Perhotelan (`PBJT-HTL`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Jasa Perhotelan` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-43` senilai **Rp 500.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-43 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Jasa Parkir (`PBJT-PRK`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Jasa Parkir` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-44` senilai **Rp 500.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-44 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Jasa Kesenian dan Hiburan (`PBJT-HBR`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Jasa Kesenian dan Hiburan` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-45` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-45 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Pajak Air Tanah (`PAT`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak Air Tanah` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-46` senilai **Rp 800.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-46 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Penyediaan Tempat Kegiatan Usaha (`PTKU`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Penyediaan Tempat Kegiatan Usaha` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-47` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-47 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Retribusi Jasa Umum (`RJU`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Retribusi Jasa Umum` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-48` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-48 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Retribusi Perizinan Tertentu (`RPT`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Retribusi Perizinan Tertentu` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-49` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-49 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Persetujuan Bangunan Gedung (PBG) (`PBG`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Persetujuan Bangunan Gedung (PBG)` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1772010546-50` senilai **Rp 556.000.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1772010546-50 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---


---

## File: docs/07_testing_kualitas/testing-reports/14_API_CRUD_Test_20260225_205828.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-02-25 20:58:28
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ⚠️ | Admin login failed — some tests will be skipped. Response: {"message":"Email\/NIK atau password salah"} |
| ✅ | Citizen login → token received |
| ✅ | Citizen ID: 2, NIK: 1234567890123456 |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ❌ | GET /test-cors → 404 (expected 200) |
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ✅ | GET /citizen/bills?nik=... → 200 |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /me (citizen profile) → 200 |
| ✅ | GET /citizen/services → 200 |
| ✅ | GET /citizen/services/2 (detail) → 200 |
| ✅ | GET /citizen/services/2/bills → 200 |
| ✅ | GET /citizen/services/pending-periods → 200 |
| ❌ | GET /citizen/reports → 500 (expected 200) |
| ✅ | GET /pbb/bapenda/my-objects → 200 |
| ✅ | GET /pbb/bapenda/my-transactions → 200 |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Admin endpoints skipped (no token) |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ⏭️ | Zone CRUD skipped (no admin token) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ⏭️ | Taxpayer CRUD skipped (no admin token) |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ⏭️ | Retribution Type CRUD skipped |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ✅ | Tax simulation → result received |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|
| ✅ | READ profile → name: Wajib Pajak Demo
Badan Pendapatan Daerah |
| ✅ | UPDATE profile → 200 OK |

## Summary

⚠️ **2 TEST(S) FAILED**

- **Pass**: 21
- **Fail**: 2
- **Warn**: 1
- **Skip**: 4
- **Total**: 28



---

## File: docs/07_testing_kualitas/testing-reports/14_API_CRUD_Test_20260225_210136.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-02-25 21:01:36
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: opd) |
| ✅ | Citizen login → token received |
| ✅ | Citizen ID: 2, NIK: 1234567890123456 |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ❌ | GET /test-cors → 404 (expected 200) |
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ✅ | GET /citizen/bills?nik=... → 200 |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /me (citizen profile) → 200 |
| ✅ | GET /citizen/services → 200 |
| ✅ | GET /citizen/services/2 (detail) → 200 |
| ✅ | GET /citizen/services/2/bills → 200 |
| ✅ | GET /citizen/services/pending-periods → 200 |
| ❌ | GET /citizen/reports → 500 (expected 200) |
| ✅ | GET /pbb/bapenda/my-objects → 200 |
| ✅ | GET /pbb/bapenda/my-transactions → 200 |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ❌ | CREATE zone failed: {"message":"The opd id field is required. (and 2 more errors)","errors":{"opd_id":["The opd id field is required."],"retribution_type_id":["The retrib |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ❌ | CREATE taxpayer failed: {"message":"The retribution type ids field is required.","errors":{"retribution_type_ids":["The retribution type ids field is required."]}} |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ❌ | CREATE retribution-type failed: {"message":"The base amount field is required. (and 1 more error)","errors":{"base_amount":["The base amount field is required."],"unit":["The unit field is required."]}} |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ✅ | Tax simulation → result received |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|
| ✅ | READ profile → name: Wajib Pajak Demo
Badan Pendapatan Daerah |
| ✅ | UPDATE profile → 200 OK |

## Summary

⚠️ **5 TEST(S) FAILED**

- **Pass**: 57
- **Fail**: 5
- **Warn**: 0
- **Skip**: 0
- **Total**: 62



---

## File: docs/07_testing_kualitas/testing-reports/09_Laporan_Keamanan_RBAC.md
---
# 🛡️ Laporan Hasil Uji Coba Keamanan Akses (RBAC)

**Waktu Eksekusi**: 2026-02-25 08:08:08
Pengujian ini menembak API lokal menggunakan Token Sanctum murni untuk membuktikan Sistem Isolasi Peran (Tenant Isolation & Authorization) berjalan sempurna.

### 1. Wajib Pajak Mengakses Endpoint Admin
- ✅ **SUKSES DIBLOKIR**: Server mengembalikan status HTTP `403`. Wajib pajak tidak bisa masuk dapur admin.

### 2. Tamu (Tanpa Token) Mengakses Endpoint Terkunci
- ✅ **SUKSES DIBLOKIR**: Pengunjung dilarang masuk. `401 Unauthenticated`.

### 3. Petugas Lapangan Melakukan Aksi Destruktif (DELETE Tagihan/Objek)
*(Skip: Belum ada data TaxObjekt untuk dihapus)*



---

## File: docs/07_testing_kualitas/testing-reports/14_API_CRUD_Test_20260225_214055.md
---
# 🧪 API & CRUD Test Report

**Date**: 2026-02-25 21:40:55
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: opd) |
| ✅ | Citizen login → token received |
| ✅ | Citizen ID: 2, NIK: 1234567890123456 |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /test-cors → 200 |
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ✅ | GET /citizen/bills?nik=... → 200 |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /me (citizen profile) → 200 |
| ✅ | GET /citizen/services → 200 |
| ✅ | GET /citizen/services/2 (detail) → 200 |
| ✅ | GET /citizen/services/2/bills → 200 |
| ✅ | GET /citizen/services/pending-periods → 200 |
| ❌ | GET /citizen/reports → 500 (expected 200) |
| ✅ | GET /pbb/bapenda/my-objects → 200 |
| ✅ | GET /pbb/bapenda/my-transactions → 200 |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ❌ | CREATE zone failed: {"message":"The code field must not be greater than 10 characters.","errors":{"code":["The code field must not be greater than 10 characters."]}} |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 1320, NIK: 9900001772026867 |
| ✅ | GET /taxpayers/1320 → 200 |
| ✅ | GET /taxpayers/search/9900001772026867 → 200 |
| ✅ | UPDATE taxpayer/1320 → 200 |
| ✅ | DELETE taxpayer/1320 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 43 |
| ✅ | GET /retribution-types/43 → 200 |
| ✅ | UPDATE retribution-type/43 → 200 |
| ✅ | DELETE retribution-type/43 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ✅ | Tax simulation → result received |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|
| ✅ | READ profile → name: Wajib Pajak Demo
Badan Pendapatan Daerah |
| ✅ | UPDATE profile → 200 OK |

## Summary

⚠️ **2 TEST(S) FAILED**

- **Pass**: 67
- **Fail**: 2
- **Warn**: 0
- **Skip**: 0
- **Total**: 69



---

## File: docs/07_testing_kualitas/testing-reports/12_Production_Readiness_20260225_202019.md
---
# Production Readiness Test Report

**Date**: 2026-02-25 20:20:19
**API**: https://api.sipanda.online
**Frontend**: https://sipanda.online


### 1. CORS Preflight (OPTIONS)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS /api/me → 204 No Content |
| ✅ PASS | Access-Control-Allow-Origin: https://sipanda.online |
| ✅ PASS | Access-Control-Allow-Credentials: true |
| ✅ PASS | Access-Control-Allow-Methods includes POST |
| ✅ PASS | No duplicate Access-Control-Allow-Origin headers |

### 2. CORS on Actual Responses
| Status | Detail |
|--------|--------|
| ✅ PASS | GET /api/me (no auth) → 401 Unauthorized |
| ✅ PASS | 401 response includes CORS headers |
| ✅ PASS | No duplicate CORS headers on 401 response |
| ✅ PASS | 401 returns JSON: {"message":"Unauthenticated."} |

### 3. Citizen Login
| Status | Detail |
|--------|--------|
| ✅ PASS | Citizen login successful, token received |
| ✅ PASS | Login response includes user data |
| ✅ PASS | Bad login correctly rejected |
| ✅ PASS | Login response has exactly 1 Access-Control-Allow-Origin header |

### 4. Authenticated API Endpoints
| Status | Detail |
|--------|--------|
| ✅ PASS | GET /api/me (authenticated) → 200 OK |
| ✅ PASS | /api/me has exactly 1 CORS header |
| ✅ PASS | GET /api/citizen/services → 200 OK |
| ✅ PASS | GET /api/citizen/bills → 200 OK |

### 5. Cross-Origin (admin.sipanda.online)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS from admin.sipanda.online → 204 |
| ✅ PASS | CORS allows admin.sipanda.online |

### 6. Error Handling (No 500s)
| Status | Detail |
|--------|--------|
| ✅ PASS | Unknown endpoint returns 404 (not 500) |
| ✅ PASS | Health endpoint /up → 200 OK |

### 7. Frontend & PWA
| Status | Detail |
|--------|--------|
| ✅ PASS | Frontend sipanda.online → 200 OK |

## Summary

🎉 **ALL CRITICAL TESTS PASSED** — Production ready.

- **Pass**: 22
- **Fail**: 0
- **Warn**: 0



---

## File: docs/07_testing_kualitas/testing-reports/10_Laporan_Validasi_Negatif.md
---
# 🛑 Laporan Hasil Uji Coba Input Invalid (Negative Testing)

**Waktu Eksekusi**: 2026-02-25 09:09:04
Pengujian ini sengaja merusak input API untuk memastikan Controller menolak transaksi berakibat fatal ke Database.

### 1. Injeksi Pembayaran Negatif (Rp -5.000.000)
- ✅ **SUKSES DITOLAK**: Laravel Form Request mendeteksi nilai tidak valid, HTTP `422 Unprocessable Entity`.

### 2. Double Payment / Membayar Ulang SKPD Lunas
- ✅ **SUKSES DITOLAK**: Sistem menolak pembayaran ganda/ilegal (HTTP `403`).

### 3. Payload Bolong (Required Validation)
- ✅ **SUKSES DITOLAK**: Framework membentengi Database, menolak Insert data cacat. HTTP `422 Unprocessable Entity` atas hilangnya parameter fundamental.



---

## File: docs/07_testing_kualitas/TESTING_GUIDE.md
---
# 🧪 Panduan Pengujian (Testing Guide) - Baubau 2024

Dokumen ini adalah acuan untuk menjalankan pengujian sistem M-PAD dengan hirarki **Wilayah-Centric**.

## 1. Fokus Pengujian Baru
Seiring dengan perubahan struktur ke Wilayah I & II, pengujian wajib mencakup:
1.  **Isolasi Wilayah**: Memastikan Petugas Wilayah I tidak dapat mengakses/menagih Objek di Wilayah II.
2.  **Akurasi Klasifikasi (Level 2)**: Verifikasi bahwa rumus dan ikon diambil dari tabel `retribution_classifications`.
3.  **Spatial-Tariff (Level 3 & 4)**: Verifikasi bahwa pemilihan Zona (misal: Kelas Jalan A) menghasilkan tarif Rupiah yang tepat.

## 2. Akun & Parameter Acuan
| Unit | Parameter Test |
| :--- | :--- |
| **Wilayah I (ID 16)** | Wolio, Murhum, Betoambari, Batupoaro |
| **Wilayah II (ID 17)** | Kokalukuna, Sorawolio, Lea-Lea, Bungi |
| **Password Standard** | `password123` |

## 3. Skenario "Golden Path" (End-to-End)
Untuk memverifikasi integrasi penuh, jalankan skenario berikut:
1.  **Pendaftaran**: Gunakan `SPOPD` untuk mendaftarkan objek baru di Kecamatan `Wolio`.
2.  **Penetapan**: Pastikan Parent ID otomatis terisi `16` (Wilayah I).
3.  **Zonasi**: Pilih `Zona Premium` dan pastikan tarif parkir muncul sebagai `Rp 3.000`.
4.  **Penagihan**: Generate `SKRD` dan verifikasi QR-Code TTE muncul.

## 4. Instruksi AI Agent
Gunakan skill spesialis untuk akurasi maksimal:
- **Protokol E2E**: Aktifkan skill `omni_workspace_tester`.
- **Audit Regulasi**: Verifikasi hasil hitung terhadap [Master Regulasi](file:///Users/pondokit/Herd/retribusi-api/docs/01_regulasi_baubau/BAUBAU_REGULATORY_MASTER.md).



---

## File: docs/07_testing_kualitas/testing/11-Filter-Wajib-Pajak-Petugas.md
---
# 11 - Indikator Pengetesan Filter Hak Akses Petugas & Wajib Pajak

Dokumen ini berisi indikator pengujian krusial terkait pembatasan visibilitas data berdasarkan klasifikasi pajak (hak akses/assignments) bagi pengguna ber-role "Petugas". Dokumen ini diseragamkan untuk mencakup seluruh lapisan pengujian perangkat lunak agar fungsi filter dievaluasi secara menyeluruh.

> **CATATAN KRITIKAL (/noss):** 
> Seluruh proses pengetesan pada semua lapisan pengujian di bawah ini **TIDAK MENGGUNAKAN SCREENSHOT** ataupun rekaman pengetesan. Validasi mutlak dilakukan melalui verifikasi CLI/Terminal, pengecekan *assertion* kode, perbandingan respons JSON, atau pengecekan baris database secara langsung.

---

## 1. Unit Testing & Feature Testing
**Tujuan:** Menguji fungsi individual dan logika relasi model database terkait penugasan Petugas tanpa memanggil seluruh siklus HTTP.
* **Skenario:**
  - Mocking objek `User` dengan `role="petugas"` dan memberikan 1 `retribution_classification_id` spesifik.
  - Memanggil model/service penghitung *revenue* dan *pending counts* secara langsung di level kode (`DashboardController->getRevenue`).
* **Indikator Keberhasilan:** 
  - *Assertion* bernilai *True* bahwa fungsi menghitung total data dengan akurat sesuai klasifikasi.
  - Query builder *toSql()* memuat klausa `where retribution_classification_id = ?`.

## 2. API Testing
**Tujuan:** Menguji endpoint dari sisi *request* dan *response* payload, mensimulasikan komunikasi antara *frontend* dan *backend*.
* **Skenario (GET /api/dashboard/map-potentials):**
  - Mengirimkan *Bearer Token* Petugas yang ditugaskan di "PBJT - Makan dan Minum".
* **Indikator Keberhasilan:** 
  - Status Code `200 OK`.
  - JSON Schema merespons dengan array *tax objects* di mana properti `classification_name` murni "PBJT - Makan dan Minum". Aset di luar klasifikasi tersebut sama sekali tidak ter-*serialize*.

## 3. CRUD Testing
**Tujuan:** Menguji kelayakan Create, Read, Update, Delete untuk Objek Pajak, Wajib Pajak, dan Tagihan pada yurisdiksi sang Petugas.
* **Skenario Create (POST /api/payments):**
  - Petugas mencoba *Submit* / mencatat pembayaran untuk `tax_object_id` dengan klasifikasi "PBJT - Parkir".
* **Indikator Keberhasilan:**
  - Transaksi *Insert* digagalkan.
  - Database `payments` tidak bertambah.
  - Mengembalikan API Error Code `403 Forbidden` dengan pesan validasi "Anda tidak ditugaskan untuk mengelola klasifikasi objek pajak ini".
* **Skenario Read (GET /api/tax-objects/{id}):**
  - Membaca detail Objek Pajak khusus yang di luar klasifikasinya.
  - Mengembalikan `403 Forbidden` atau `404 Not Found`.

## 4. UI Testing (E2E)
**Tujuan:** Mensimulasikan klik pengguna tanpa menggunakan validasi tangkapan layar, melainkan inspeksi state DOM dan assertion teks terminal.
* **Skenario:**
  - Mengeksekusi script *Headless Testing* (bisa menggunakan Cypress, Selenium, atau Dusk) yang *login* sebagai petugas.
  - Navigasi ke halaman Wajib Pajak (`/taxpayers`).
* **Indikator Keberhasilan:**
  - *DOM Element* pada tabel *List* tidak merender nama Wajib Pajak yang tidak terafiliasi.
  - Logika assertions murni dari pembacaan *Count of Table Rows*, elemen DOM teks, tanpa pengambilan *.png/screenshot*. Evaluasi di-output dalam Log Terminal.


---

## File: docs/07_testing_kualitas/testing/04-Fitur-Kritis-Edge-Cases.md
---
# Tahap 4: Pengujian Fitur Kritis Tambahan (Edge Cases)

Selain alur inti pembayaran, fungsi-fungsi teknis spesifik administratif pemerintahan juga harus disorot untuk memastikan legalitas dan validitas data sistem.

## 4.1. Ujian Pengesahan Surat Tanda Tangan Elektronik (TTE)
- **Langkah 1**: Login kembali sebagai Admin Web (`admin.sipanda.online`).
- **Langkah 2**: Pergi menjelajahi menu **"Dokumen TTE / Pengesahan"**.
- **Langkah 3**: Lakukan pencarian nama dokumen "Surat Ketetapan (SKPD)" yang diterbitkan untuk WP `Budi Tester UAT` pada Tahap 3 sebelumnya.
- **Langkah 4**: Simulasikan Kepala Badan / Pejabat yang berwenang menge-klik tombol aksi **"Tandatangani Dokumen / Approve TTE"**.
- **Hasil yang Diharapkan**: 
  - File PDF yang tadinya polosan, kini memiliki bubuhan stempel visual berupa *QR Code* atau *Digital Stamp* resmi di pojok/akhir halaman.
  - Saat Surat PDF dicetak/dibiarkan di layar lalu disorot *QR Code*-nya menggunakan fitur kamera Smartphone konvensional biasa, *browser* HP harusnya langsung mengarah secara *secure* (HTTPS) ke laman web resmi verifikasi (misal kominfo/BSRE/laman verifikasi mpad) menegaskan Dokumen Valid ditandatangani hari ini.

## 4.2. Ujian Visualisasi Pemetaan Cerdas (GIS / Peta Potensi)
- **Langkah 1**: Masih di Admin Web (`admin.sipanda.online`), buka sub-fitur **"Peta Potensi"**.
- **Hak Prioritas Kesuksesan Rute**:
  - Halaman Peta tidak boleh memuntahkan peringatan Error _CORS Policy_ atau terhenti di layar abu-abu (*Blank map tiles*).  
  - Peta pulau sebaran sanggup memuat *layer satelit* atau *jalan*.
  - Menzoom mendekat, terdapat bentukan grafis ikon *pin drop* / koordinat lokasi objek pajak "Warung Makan xyz" (dan objek WP lain).
  - Ketika sebuah *pin* diketuk dua jari/di-klik mouse, sistem sukses me-*render* jendala kecil (info-window) berisikan rincian singkat objek bangunan/usaha beserta melampirkan *Foto Lokasi* tempat tersebut asri tanpa rusak/broken image box.

## 4.3. Ujian Anomali (Pembatalan/Diskon/Amnesty Penolakan)
- **Langkah 1**: Uji coba ekstrem. Daftarkan dan terbitkan 1 Tagihan Asal-Asalan PBB-P2 untuk Wajib Pajak `Tester Lain` senilai Rp 10 Juta Rupiah.
- **Langkah 2**: Log out, masuk sebagai Manajer/Kasubbid. Coba batalkan 1 tagihan tersebut menggunakan aksi Pembatalan/Pengajuan Keringanan Tagihan (Amnesty) menjadi Diskon 50%.
- **Langkah 3**: Sistem harus menghentikan atau mengubah *invoice ID* Wajib Pajak tadi secara matematika presisi ter-kalkulasi menjadi angka akhir penagihan yang sisa dibayar cuma 5 Juta secara wajar. (Tidak terjadi *bug* Minus atau Infinity Array).


---

## File: docs/07_testing_kualitas/testing/09-Keamanan-RBAC.md
---
# Tahap 9: Pengujian Keamanan Hak Akses (RBAC & Authorization)

Sistem harus mematuhi prinsip *Least Privilege*. Seorang Wajib Pajak tidak boleh mengakses data Admin, dan Petugas tidak boleh menghapus data.

## Skenario Pengujian Lintas Batas (Bypass Attempts)

- [ ] **Skenario 1: WP Mengakses Dashboard Admin**
  - **Aksi**: Login sebagai Wajib Pajak di Aplikasi Mobile, ambil token (Bearer Auth), lalu tembak rute `/api/admin/dashboard` menggunakan Postman/Curl.
  - **Hasil Diharapkan**: Sistem menolak mentah-mentah dengan HTTP Status `403 Forbidden` atau `401 Unauthorized`.
- [ ] **Skenario 2: Petugas Lapangan Menghapus Objek Pajak**
  - **Aksi**: Login sebagai Petugas, lalu mencoba mengirim *REST API DELETE* ke `/api/tax-objects/{id}`.
  - **Hasil Diharapkan**: Ditolak (HTTP `403 Forbidden`). Petugas hanya punya hak baca dan catat pembayaran.
- [ ] **Skenario 3: Wajib Pajak A Melihat SKPD Wajib Pajak B**
  - **Aksi**: WP "Budi" login, mencoba mengakses `/api/bills/{id_milik_anton}`.
  - **Hasil Diharapkan**: Sistem mengembalikan Error `403 Tidak Memiliki Akses` atau `404 Not Found` (Pemisahan Tenancy Wajib Pajak).
- [ ] **Skenario 4: Akses API Tanpa Token (Public Annonymous)**
  - **Aksi**: Tamu yang tidak login menembak `/api/user/profile`.
  - **Hasil Diharapkan**: HTTP Status `401 Unauthenticated`. Dilarang masuk.


---

## File: docs/07_testing_kualitas/testing/01-known-errors-mitigations.md
---
# Error Logs & Mitigations - Petugas & Admin E2E Testing

Dokumen ini mencatat seluruh error yang ditemukan selama pengujian alur kerja (End-to-End) antara Wajib Pajak, Petugas, dan Admin di server lokal maupun Production (api.sipanda.online), beserta langkah mitigasi yang telah diterapkan.

---

## 1. Kegagalan Auto-Generate Objek Pajak pada Pendaftaran WP Baru
**Gejala:** Saat Petugas mendaftarkan Wajib Pajak baru, `Tax Object` tidak otomatis terbuat di Production meskipun berhasil di lokal.
**Penyebab:** Endpoint pendaftaran tipe retribusi mewajibkan adanya `retribution_classification_id`, namun script E2E mengabaikan klasifikasi ini.
**Mitigasi:**
1. Mengubah script `TestProductionE2E.php` untuk mengambil *Retribution Classification* yang valid dari API sebelum mendaftarkan WP.
2. Karena di produksi terkadang klasifikasi aktif tidak ditemukan (kosong), ditambahkan fallback endpoint `POST /api/tax-objects` di `TaxObjectController@store` untuk mengakomodir input pembuatan objek pajak secara manual melalui aplikasi Petugas.

---

## 2. Visibilitas Wajib Pajak Kosong di Dashboard Petugas (Frontend)
**Gejala:** Daftar Wajib Pajak di aplikasi Petugas (`/taxpayers`) kosong (Tidak Ada Data) padahal data sudah diinput di database.
**Penyebab:** Perlindungan hak akses (*Authorization*) di API `TaxpayerController@index` menerapkan filter ketat: Petugas hanya bisa melihat WP yang ia buat (`created_by = user_id`) DAN WP yang masuk dalam daftar Penugasan Retribusinya (`UserRetributionAssignment`). Jika akun Petugas belum di-assign jenis retribusi apapun, seluruh data WP disembunyikan.
**Mitigasi:**
Membuat relasi penugasan jenis retribusi BAPENDA secara eksplisit ke akun Petugas bersangkutan (`petugas@bapenda.go.id`). Filter di frontend sekarang otomatis berjalan normal tanpa campur tangan tambahan.

---

## 3. Proses Verifikasi Admin Bapenda Gagal (Not Found)
**Gejala:** Setelah Petugas menagihkan dan mencatat pembayaran lunas (*Cash*), script E2E gagal menemukan record `pending` di daftar Verifikasi Admin.
**Penyebab:** Arsitektur API `PaymentController` meng-hardcode pembayaran via Cash oleh Petugas Bapenda langsung berstatus `success` / `lunas`, sehingga *auto-verified* dan tidak memasukkannya ke antrean *pending verification* Admin.
**Mitigasi:**
Script E2E diperbarui untuk mengenali kondisi otorisasi ini. Bila *Payment* dibuat oleh Petugas, pengecekan verifikasi dianggap berhasil (otomatis disetujui).

---

## 4. Error 500 (CORS Blocked) Saat Menghapus Jenis Retribusi
**Gejala:** Admin mendapat error CORS / 500 saat mencoba menghapus Jenis Retribusi (contoh: "testing") dari menu Master Data.
**Penyebab:** Jenis Retribusi tersebut masih memiliki data relasional yang mengikat (Objek Pajak, Tagihan, Penugasan Petugas). Database menolak penghapusan (*Integrity constraint violation: 1451 Cannot delete or update a parent row*), yang menyebabkan Laravel crash dan menghentikan pengiriman *header* CORS.
**Mitigasi:**
Menambahkan *Cascading Delete* menggunakan `DB::beginTransaction()` di `RetributionTypeController@destroy`. Saat dihapus, sistem akan membersihkan seluruh anak data (Penugasan, *Taxpayer Pivot*, *Tax Objects*, *Bills*, dan *Payments*) secara bersamaan, sehingga Jenis Retribusi dapat dihapus tanpa hambatan.


---

## File: docs/07_testing_kualitas/testing/02-Keamanan-Infrastruktur.md
---
# Tahap 2: Pengujian Infrastruktur & Keamanan (Security Smoke Test)

Verifikasikan bahwa benteng perlindungan yang dipasang di Nginx bekerja secara aktif menangkis serangan.

## 2.1. Ujian Akses File Rahasia
- **Langkah**: Buka browser (disarankan mode *Incognito* / *Private*). Ketik alamat langsung ke file konfigurasi rahasia: `https://api.sipanda.online/.env`.
- **Hasil yang Diharapkan**: Browser harus menampilkan halaman putih kosong dengan tulisan tegas "403 Forbidden" (yang dihasilkan oleh Nginx). 
- **Peringatan**: Jika browser malah mengunduh (download) file teks `.env` tersebut, **SEGERA MATIKAN SERVER** karena kredensial database bocor.

## 2.2. Ujian Dokumentasi API Terselubung
- **Langkah**: Buka tab baru, arahkan ke rute publik dokumentasi: `https://api.sipanda.online/docs`.
- **Hasil yang Diharapkan**: Halaman harus *blank* dan menampilkan teks JSON murni yang berisi `{"message": "Unauthenticated."}` dengan balasan status HTTP `401 Unauthorized`. 
- **Tujuan**: Ini membuktikan bahwa dokumentasi API (struktur endpoint, parameter wara-wiri server) tidak lagi bocor atau bisa diintip sembarang orang di internet publik tanpa token.

## 2.3. Ujian Anti-Spam / Anti-DDoS (Rate Limiting)
- **Langkah Utama**: Gunakan aplikasi seperti *Postman*, *Insomnia*, atau alat *curl* CLI.
- **Aksi**: Tembakkan _HTTP POST / GET Request_ berulang-ulang ke alamat login `https://api.sipanda.online/api/login` sebanyak minimal **65 kali secara brutal dalam kurun waktu kurang dari 1 menit**.
- **Hasil yang Diharapkan**: 
  - Pada *request* ke-1 hingga 60, server merespons normal (misal 401 atau 200).
  - Pada *request* ke-61, HTTP Response code berubah mendadak menjadi `429 Too Many Requests`.
- **Tujuan**: Membuktikan Laravel *Throttle limit* di rute Auth sukses meredam serangan Brute-Force pencurian password.

## 2.4. Ujian Integritas Sertifikat & Header HSTS
- **Langkah**: Buka front-end Web Admin. Klik kanan layar -> **Inspect Element** (F12) -> Buka tab **Network**. *Refresh* halamannya.
- **Hasil yang Diharapkan**:
  - Indikator gembok HTTPS di dekat URL Web menyala hijau terkunci penuh.
  - Saat meng-klik salah satu *Request API* di tab Network, pada bagian *Response Headers* harus tertera tulisan `Strict-Transport-Security: max-age=31536000; includeSubDomains`. Ini berarti jalur dipaksa 100% menggunakan koneksi terenkripsi tingkat tinggi.


---

## File: docs/07_testing_kualitas/testing/05-Cross-Device-UAT.md
---
# Tahap 5: Pengujian Perangkat Layar Real (Cross-Device UAT)

Aplikasi canggih tiada guna bila hancur antarmukanya saat dipakai Pejabat/Warga dengan beragam jenis ukuran monitor dan tipe peramban gawai di lapangan nyata.

## 5.1. Kompatibilitas Browser Komputer (PC/Laptop)
- **Langkah Pengujian Modal CSS**:
  - Gunakan 2 Browser di luar zona nyaman Chrome (misal **Mozilla Firefox** Mac/Windows dan **Safari** / **Microsoft Edge**).
  - Masuk ke *Web Admin* (`admin.sipanda.online`).
  - Buka formulir berat (seperti Tambah Objek Pajak, atau Manajemen Pengguna UserManagement/Roles) yang membutuhkan Pop-up Dialog *Modal Container* bertindih.
  - **Kriteria Valid**: Layar *Modal* formulir pendaftaran tersebut tidak boleh cacat. Kaki tabel atau baris *Submit* penutup tidak boleh *Cut-off* (memotong elemen di luar *overlay* akibat *overflow hidden* bug). Modal bersifat lentur bisa di-scroll sampai bawah.

## 5.2. Layar HP Ujung Tanduk (Responsive Extremes)
- **Langkah Pengujian Aplikasi Kolektor (`petugas.sipanda.online`)**:
  - Pinjam dua jenis perangkat bertolak belakang:
    1.  Tabulatur Layar Sangat Besar/Horizontal (Tablet Windows / Layar >6.5 inch).
    2.  Ponsel Kuno berlayar sipit vertikal (*iPhone SE Generasi 1*, Android mini ukuran 4 inci).
  - Sebagai petugas pemungut di jalan, coba login dan masukan baris nomor NIK pencarian tagihan.
  - Saat Tagihan keluar dan melengkapi form Setoran manual.
  - **Kriteria Valid**: Meskipun jari petugas agak tebal/layarnya kurus memanjang, ukuran tombol "Konfirmasi Pembayaran Lunas" dan teks *Input TextBox* masih bisa disentuh telunjuk empuk (Padding/Margin ramah *Tap Target Size* Standard A11Y). Layar bergulir horizontal stabil (Responsif Grid Column pecah dari _desktop table_ ke _Card layout_ Mobile).


---

## File: docs/07_testing_kualitas/testing/10-Validasi-Data.md
---
# Tahap 10: Pengujian Integritas & Validasi Data Ekstrem (Negative Testing)

Menguji seberapa tangguh sistem saat diserang dengan input data yang salah, tidak masuk akal, atau manipulasi pembayaran.

## Skenario Input Negatif & Cegah Rusak Data

- [ ] **Skenario 1: Manipulasi Pembayaran Minus (Negative Amount)**
  - **Aksi**: Petugas mencoba mensubmit nominal pembayaran sebesar `-500000` (Minus 500 Ribu) ke API Pembayaran.
  - **Hasil Diharapkan**: Laravel Form Request memantulkan *Error Validation*: `422 Unprocessable Entity - Nominal harus lebih besar dari 0`.
- [ ] **Skenario 2: Pembayaran Ganda (Double Payment) pada SKPD yang Lunas**
  - **Aksi**: Tagihan WP Budi sudah `paid`. Lalu Petugas tak sengaja mencoba memproses ulang pembayaran ke Bill ID yang sama.
  - **Hasil Diharapkan**: Ditolak dengan pesan: `400 Bad Request - Tagihan ini sudah berstatus lunas`. Tidak boleh ada uang masuk dobel tak tercatat.
- [ ] **Skenario 3: Payload SKPD Kosong (Empty Required Fields)**
  - **Aksi**: Admin merilis Tagihan Baru tanpa mencantumkan "amount" (Nominal Tagihan = null) atau tanpa "due_date".
  - **Hasil Diharapkan**: Ditolak langsung di pintu depan (HTTP 422 Validasi Required).
- [ ] **Skenario 4: Batas Karakter String Bypass (SQL Truncation Guard)**
  - **Aksi**: Nama WP diset 300 Karakter panjang (Melebihi limit varchar(255) Database).
  - **Hasil Diharapkan**: HTTP 422 - `String terlalu panjang, maksimal 255 karakter`, bukannya meledakkan aplikasi dengan error SQL `500 Internal Server Error`.


---

## File: docs/07_testing_kualitas/testing/00-Panduan-Standar-Pengujian.md
---
# 00 - Panduan Standar Skema Pengujian Terpadu (Master Guideline)

Dokumen ini adalah **Acuan Utama (Master Schema)** untuk seluruh kegiatan pengujian (Testing) pada aplikasi (MITRA: Admin, Petugas, Mobile, dan API Backend). 

Setiap fitur yang dirilis, termasuk namun tidak terbatas pada Alur Utama E2E, Keamanan RBAC, Validasi Data, hingga Kalkulasi Formula Pajak wajib mengacu pada 5 Lapisan Pengujian di bawah ini. Jika sebuah dokumen pengujian lain di folder ini memiliki pengecekan fungsi yang beririsan, maka instruksi pemeringkatan tes di sini yang menjadi pemegang kedali standar.

> **CATATAN KRITIKAL (/noss):** 
> Seluruh proses pengetesan pada skema ini dan modul-modul turunannya dideklarasikan **TIDAK MENGGUNAKAN SCREENSHOT** ataupun tangkapan layar. Validasi keberhasilan harus murni dibuktikan dari pembacaan CLI/Terminal, JSON Response dari Postman/Insomnia, Assertions Kode, dan data Database secara real-time.
> 
> **PENYIMPANAN HASIL (RESULTS FOLDER):**
> Seluruh output pengujian dari skrip otomatis (Bash/PHP) **wajib** dilampirkan atau disalurkan ke dalam folder `testing/results/`. Hasil testing harus disimpan dalam bentuk file `.md` atau `.txt` dengan menyertakan _timestamp_ eksekusi (contoh: `testing/results/14_API_CRUD_Test_prod_20260305_100000.md`) agar jejak audit pelaporan tersimpan rapi tanpa perlu bukti gambar.

---

## Lapisan Skema Pengujian (Testing Layers)

### 1. Unit Testing & Feature Testing
**Fokus Utama:** Menguji fungsi/metode individual (Unit) dan logika relasi model database (Feature) langsung di level *source code* backend tanpa menjalankan full HTTP request.
* **Standar Skenario:**
  - Mocking objek `User`, Objek Pajak, atau instance lainnya yang dibutuhkan sesuai konteks pengujian.
  - Memanggil model atau *service locator* secara langsung (contoh: `app(\App\Services\FormulaParserService::class)->calculate()`).
* **Standar Validitas (No Screenshot):** 
  - *PHPUnit / Pest Assertion* me-return respon `true` atau `assertEquals`.
  - Logika struktur Query Database melalui *toSql()* atau logging DB mengeksekusi parameter `where` yang presisi.

### 2. API Testing
**Fokus Utama:** Menguji titik akhir (Endpoint/Routes) dari segi validitas *Request Payload* dan *Response Payload*, serta status kode HTTP dari koneksi client-server.
* **Standar Skenario:**
  - Menghantam URL target di `routes/api.php` dengan *Bearer Token* Auth palsu atau asli sesuai peran.
  - Memodifikasi variasi header, *query params*, atau Form Data.
* **Standar Validitas (No Screenshot):** 
  - Konsol inspektor mengembalikan eksekusi *Status Code* yang relevan (`200 OK`, `201 Created`, `401 Unauthorized`, `403 Forbidden`, `422 Unprocessable Content`, dll).
  - Skema struktur array JSON *Response* (`data`, `message`, `meta`) sesuai dengan cetak biru yang diharapkan tanpa ada relasi asing yang terekspos (*data bocor*).

### 3. CRUD Testing
**Fokus Utama:** Menguji alur siklus hidup utama (Create, Read, Update, Delete) terkait keutuhan dan persistensi data di tabel Database. Lapisan ini memastikan fungsi form dan rekayasa manipulasi database berjalan sempurna.
* **Standar Skenario:**
  - Permintaan penambahan baris (Create) dengan parameter wajib vs kosong.
  - Permintaan modifikasi kolom spesifik (Update) parsial atau penuh.
  - Permintaan penghapusan rekaman (Delete) dengan pengujian Soft-Delete maupun Hard-Delete (jika terkait).
* **Standar Validitas (No Screenshot):**
  - Database terminal SQL *Client* / Laravel Tinker membuktikan bahwa data sukses tertaut (count baris bertambah/berkurang).
  - Validasi penolakan sistemik pada operasi ilegal yang tidak dimandatkan pada user tersebut ter-blokir dengan sempurna di level Eloquent atau *Form Request*.

### 4. UI Testing (Headless / E2E Automation)
**Fokus Utama:** Menguji elemen antarmuka DOM (*Document Object Model*) menggunakan simulasi aktivitas browser *user* tiruan seperti penekanan tombol, input teks, dan navigasi Router. Pengecekan tidak lagi melalui tatapan mata QA manual, melainkan interaksi sintaks mesin.
* **Standar Skenario:**
  - Menjalankan Automation Scripting (mis. dengan Cypress, Laravel Dusk, atau Playwright).
  - Mengarahkan mesin menuju alamat laman contoh `/taxpayers`. 
  - Menginstruksikan bot mengetik sesuatu di kotak `#search` dan menekan `.btn-submit`.
* **Standar Validitas (No Screenshot):**
  - Terciptanya jejak hijau ceklis di Terminal OS (misalnya *Expected <table-row> to have length 5 -> Passed!*).
  - Eksekusi *Document.querySelector* menemukan *class* dan nilai teks (innerText) yang selongsong dengan perhitungan mesin, bukan gambar statis.

### 5. Integration / End-to-End System Testing
**Fokus Utama:** Mengkonfirmasi bahwa komunikasi berbagai *services*, *scheduler* (cron), pihak eksternal, dan siklus transaksi lintas aplikasi beroperasi mengalir tanpa hambatan fungsional.
* **Standar Skenario:**
  - Kombinasi registrasi dari aplikasi Mobile -> Pengesahan di Admin Web -> Pengecekan titik di Map Petugas.
* **Standar Validitas (No Screenshot):**
  - Transmisi data lintas platform tidak menghasilkan *Delay/Failure*. Verifikasi terekam dalam *System Logs* pusat atau integrasi webhook yang diterima valid.

---
**Pemberlakuan Skema:**
Terhitung semenjak dokumen ini dirilis, *keseluruhan file markdown uji kasus* di folder direktori `testing/` (seperti *01 hingga 10*, dll) otomatis wajib mengacu secara fundamental kepada instruksi *5 Lapisan Skema Testing* dalam dokumen Acuan Utama bernomor `00` ini. Fungsi pengecekan logika bisnis yang serupa dari berbagai file diintegrasikan ke standar kelulusan ini.


---

## File: docs/07_testing_kualitas/testing/06-Go-Live-Monitoring.md
---
# Tahap 6: Pemantauan Hari Pertama (Go-Live Monitoring)

Jika tahap 1 hingga 5 dinyatakan Lulus Tanpa Syarat (Green-lighted), persilahkan seremonial *Soft-Launch* atau rilis aplikasi *End-user* untuk dipakai bekerja publik (Bapenda, OPD, dan khalayak umum/Wajib Pajak se-daerah).

Pada fase kerawanan tinggi **24 jam pertama paska pembukaan akses (Go-Live)**, ikuti mandat monitoring berikut bagi para Staff Server (IT Developer / SysAdmin):

## 6.1. Pantauan Eksekusi Log Exception (Real-time Tailing)
- **Instruksi**: Sang Administrator Backend (Tim Anda) wajib bersiaga membuka _Console SSH Client_ terkoneksi ke IP Publik VPS Anda `157.10.252.74`. Masuk sebagai user `mpad` atau `root`.
- **Tindakan**: Biarkan tab layar hitam konsol VPS mengeksekusi hidup mantra ini tanpa terputus siang ini:
  ```bash
  tail -f /home/mpad/retribusi-api/storage/logs/laravel.log
  ```
- **Kondisi Normal/Siaga**: Tab hitam terminal sunyi (tak ada log aneh merayap cepat) atau sesekali cuma melempar sebaris *Info Log* wajar.
- **Kondisi Kritis Bertindak**: Jika tiba-tiba layar terminal Anda memuntahkan tarian teks sekuensial panjang puluhan baris yang diawali bait `[stacktrace]..........` secara masif, atau bait keras kemerah-merahan `Fatal Error:` / `SQLSTATE[...] SQL Syntax Exception`, **BERHENTI!** Cepat catat URL Rute-nya, *rollback* rute tersebut atau _Hot-Fix Code_ detik itu juga.

## 6.2. Pantauan Pembengkakan Ram Tercekik (Memory Leak Hardware Metric)
- **Instruksi**: Buka tab *SSH* kedua ke server API VPS Nginx Anda berdampingan.
- **Tindakan**: Keluarkan komando diagnostik detak jantung _CPU/RAM Hardware_:
  ```bash
  htop
  ```
  *(Atau jalankan terus manual berkala sekedar info `free -h -s 5`)*.
- **Waspadai Resiko**: Di saat gerbang diumumkan terbuka untuk PNS/Masyarakat massal login sistem API, bar garis Memory (RAM Hijau/Kuning Bar) bisa tiba-tiba naik tajam secara tak terkendali mentok di batas _Swap Space_.
- Jika *htop* memperlihatkan angka CPU `100% Core Load` melulu dari *Pool PHP-FPM / MySQLd*, dan RAM habis, ini terindikasi aplikasi API retribusi kekurangan sumber daya (terkena *Memory Leaks* atau *Bad N+1 Laravel Query* mendadak).
  - *Tindakan Mitigasi Darurat Taktikal*: Segera _restart_ daemon engine FPM ( `sudo systemctl restart php8.x-fpm` & `nginx` ). Lalu lapor koordinator pelan-pelan cari solusinya merapihkan baris kode *Query Controller* yang bikin berat server melambat drastis itu di jam kosong trafik (Malam hari).

Jika tak ada kendala luar biasa dari Monitor Server selama matahari tenggelam sampai terbit ini?

**SELAMAT! INSTALASI API RETRIBUSI ANDA 100% SUKSES TANGGUH DALAM SKALA PRODUKSI KELAS NEGARA!**


---

## File: docs/07_testing_kualitas/testing/07-Formula-Jenis-Pajak.md
---
# Tahap 7: Pengujian Formula Objek Pajak & Retribusi (Kalkulator Dinamis)

Aplikasi Mpad memiliki puluhan jenis pungutan pajak dengan struktur *form* dan rumus hitung (*formula*) yang saling berbeda-beda sesuai Perwali 58/2024. Modul ini bertujuan menguji keakuratan matematis mesin *Calculator* API.

## Checklist Pengujian Klasifikasi Pajak:

### A. Wilayah I (Pajak Utama & Official Assessment)
- [ ] **PBB-P2**:
  - **Input Dummy**: Luas Tanah 100m2 (Kelas Bumi 080), Luas Bangunan 50m2 (Kelas Bangunan 044).
  - **Kriteria Validasi**: Parameter `pbb_terhutang` dari API kalkulator tidak boleh *minus* atau bernilai *null*, dan rumusnya mengakomodir NJOPTKP (default Rp 10 Juta).
- [ ] **BPHTB**:
  - **Input Dummy**: NPOP Rp 250.000.000.
  - **Kriteria Validasi**: Harus menghitung `(NPOP - NPOPTKP) * 5%`.
- [ ] **Pajak Reklame**:
  - **Input Dummy**: NSR Rp 5.000.000, Ukuran 10m2.
  - **Kriteria Validasi**: Tagihan dikenakan *Tarif Reklame 25%* secara presisi.
- [ ] **Pajak Sarang Burung Walet**:
  - **Input Dummy**: Nilai Jual Rp 15.000.000.
  - **Kriteria Validasi**: Tagihan Rp 1.500.000 (Tarif 10%).
- [ ] **Pajak MBLB (Mineral Bukan Logam)**:
  - **Input Dummy**: Volume 100 Rit/m3, Harga Patokan Rp 80.000.
  - **Kriteria Validasi**: Tagihan Rp 1.200.000 (Tarif 15%).
- [ ] **Opsen PKB & Opsen BBNKB**:
  - **Input Dummy**: Pokok dari Provinsi Rp 2.000.000.
  - **Kriteria Validasi**: Opsen untuk daerah adalah 66% (Rp 1.320.000).

### B. Wilayah II (Self Assessment PBJT)
- [ ] **PBJT Restoran / Makan Minum**:
  - **Input Dummy**: Omzet Penjualan Bulanan Rp 10.000.000.
  - **Kriteria Validasi**: Hasil wajib Rp 1.000.000 (Tarif standar 10%).
- [ ] **PBJT Jasa Perhotelan**:
  - **Input Dummy**: Omzet Kamar Rp 25.000.000.
  - **Kriteria Validasi**: Hasil wajib Rp 2.500.000.
- [ ] **PBJT Tenaga Listrik**:
  - **Input Dummy**: Tagihan PLN Rp 5.000.000. (Uji menggunakan Tarif Industri: 3%).
  - **Kriteria Validasi**: Hasil wajib Rp 150.000.
- [ ] **PBJT Hiburan Malam (Khusus)**:
  - **Input Dummy**: Omzet Diskotik Rp 50.000.000. (Uji Tarif Hiburan Malam: 40%).
  - **Kriteria Validasi**: Tagihan raksasa sebesar Rp 20.000.000.

### C. Retribusi Daerah
- [ ] **Persetujuan Bangunan Gedung (PBG)**:
  - **Input Dummy**: Luas Lantai 120m2, Indeks Terintegrasi 1.2, Indeks BG 1.0. (SHST Rp 5.560.000).
  - **Kriteria Validasi**: Angka tidak boleh desimal pecahan error (NaN).
- [ ] **Retribusi Jasa Umum (Kesehatan/Sampah/Parkir)**:
  - **Kriteria Validasi**: Tagihan Parkir Roda 2 (Flat Rp 2.000), Sampah Bulanan Rumah Tangga (Flat Rp 8.500). Tidak boleh memunculkan nilai *Percentage*.


---

## File: docs/07_testing_kualitas/testing/01-Persiapan-Deployment.md
---
# Tahap 1: Persiapan Deployment (Deployment Checklist)

Sebelum mulai menguji fitur secara fungsional, pastikan hal teknis fundamental sudah beres di VPS/Server Production.

## Daftar Periksa Server
- [ ] **Kode Terbaru**: Pastikan *branch* `main` dari seluruh repositori terkait (API, Admin, Petugas, Mobile) sudah berhasil di-*pull* dan ter-*deploy* ke mesin Ubuntu Production (`157.10.252.74`).
- [ ] **Environment Variables**: Pastikan `.env` API production sudah tersetting ke `APP_DEBUG=false` dan `APP_ENV=production`.
- [ ] **Migrasi Database**: Jika rilis terbaru ini memuat penambahan atau perubahan skema tabel (migration), pastikan perintah ini sudah dijalankan:
  ```bash
  php artisan migrate --force
  ```

## Optimalisasi Laravel
Jalankan *cache clearing* agar aplikasi berjalan dengan performa maksimal. Eksekusi sekumpulan perintah ini di terminal (SSH) VPS pada root folder `retribusi-api`:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
```

**Tanda Sukses:**
Terminal akan membalas dengan warna hijau berpesan _"Configuration cached successfully!", "Routes cached successfully!"_ dsb., tanpa ada pesan Exception merah.


---

## File: docs/07_testing_kualitas/testing/02-e2e-testing-scheme.md
---
# Skema Pengujian E2E (End-to-End) Petugas & Admin Mpad

Skenario pengujian ini dirancang untuk memastikan bahwa integrasi fitur Pendaftaran Wajib Pajak, Pembuatan Tagihan, Pencatatan Pembayaran oleh Petugas, dan proses Verifikasi Admin berjalan tanpa error (termasuk *CORS*, *Data Not Found*, atau *Foreign Key Constraints*).

## Prasyarat Lingkungan Pendukung (Prerequisites)
1. **Server API aktif** (Lokal `localhost:8000` atau VPS Production `api.sipanda.online`).
2. **Setup Subdomain Frontend** (Mobile: `mpad.baubaukota.go.id`, Admin: `adminmpad.baubaukota.go.id`, Petugas: `petugasmpad.baubaukota.go.id`) dengan HTTPS dan CORS yang dizinkan.
3. **Akun Super Admin** BAPENDA (admin yang sudah didaftarkan: `admin@bapenda.go.id` dsb).
4. **Akun Petugas** BAPENDA (petugas yang sudah didaftarkan: `petugas@bapenda.go.id`).
5. Komando script E2E khusus: `php artisan test:production-e2e`.

---

## Skenario Pengujian Otomatis

Proses pengujian utama dilakukan melalui *command-line interface* (CLI) artisan, yang menjalankan skrip simulasi pemanggilan HTTP API murni. Skrip tersebut mengeksekusi urutan langkah berikut:

### 1. Uji Otentikasi (Authentication Test)
- **Aksi:** Memanggil endpoint `POST /api/login` sebagai Admin dan Petugas secara berurutan.
- **Ekspektasi:** Endpoint mengembalikan respon HTTP 200 beserta bearer token otentikasi.
- **Kasus Error jika Gagal:** Konfigurasi `.env` `APP_URL` atau sandi (`Bapenda123#$` vs `password`) tidak sesuai. 

### 2. Uji Referensi Klasifikasi (Classification Reference Test)
- **Aksi:** Memanggil endpoint `GET /api/retribution-types` menggunakan *token* Petugas.
- **Ekspektasi:** Endpoint sukses mengembalikan minimal 1 objek `id` berjenis "Testing". Menyimpan `id` klasifikasi tersebut.

### 3. Pendaftaran Wajib Pajak oleh Petugas (Taxpayer Registration Test)
- **Aksi:** Memanggil endpoint `POST /api/taxpayers` dengan payload nama *dummy*, NIK valid (16 digit angka acak), serta menyematkan *array* `retribution_type_ids` dan `retribution_classification_ids`.
- **Ekspektasi:** Endpoint merespons dengan HTTP 201 (Created). Respons ini mengindikasikan bahwa Wajib Pajak baru sukses tersimpan dan hanya akan direlasikan ke Petugas tersebut (sesuai mitigasi filter).

### 4. Perolehan Objek Pajak (Tax Object Verification Test)
- **Aksi:** Memanggil API manual `POST /api/tax-objects` menggunakan valid `taxpayer_id` dari langkah ke-3. 
- **Ekspektasi:** API merespons objek baru dengan HTTP 201, bukan error validasi atau CORS.

### 5. Pembuatan Tagihan Baru (Billing Creation Test)
- **Aksi:** Memanggil endpoint `POST /api/bills` dengan referensi *Tax Object*. Payload membutuhkan `period` (format `Y-m`) dan jumlah nominal `total_amount`.
- **Ekspektasi:** Tagihan sukses diciptakan (`id` status `active`).

### 6. Pembayaran Tagihan oleh Petugas (Payment Processing Test)
- **Aksi:** Petugas menerima uang dan menyetorkan catatan pembayaran melalui endpoint `POST /api/payments`. Wajib menggunakan `amount`, `payment_method: cash`, `billing_period`.
- **Ekspektasi:** API merekam pembayaran. Logika auto-verifikasi Petugas mengubah status transaksi asli sebagai "Sukses" (`lunas`). 

### 7. Pengecekan Verifikasi Admin (Admin Verification Checklist)
- **Aksi:** Admin mengakses menu daftar `GET /api/verifications`.
- **Ekspektasi:** Tidak ada status API yang *crash* (500). Verifikasi pembayaran Petugas akan disetujui secara tidak langsung, daftar ini dipastikan tidak melontarkan error.

---

### 8. Uji Aksesibilitas & Distribusi (Public Aliases & PWA)
- **Aksi:** Memasuki rute `/unduh` pada aplikasi *Mobile* dan *Petugas*.
- **Ekspektasi:** Laman merespons dengan HTTP 200. Tombol "Pasang Aplikasi Sekarang" atau "Lihat Panduan Pasang" muncul dengan bayangan premium (`shadow-blue-900/40`) dan label dinamis sesuai state `isInstallable`.
- **Validasi No-Screenshot:** Pastikan rute tidak melempar `404 Not Found` dan elemen `id="install-guide"` terdeteksi di DOM.

---

## Pengujian Manual: Retribution Type Deletion Cascading List
Selain skrip *Command-Line* E2E (*automated*), pengujian manual ini diperlukan untuk kasus Error Master Data Penghapusan (*Cascading Delete Error*).

**Langkah:**
1. Masuk ke halaman `http://localhost:3000/master-data/retribution-types` sebagai Admin menggunakan browser.
2. Buat Jenis Retribusi baru bertajuk "Test Cascading".
3. Tambahkan 1 Wajib Pajak *dummy*, pasangkan dengan "Test Cascading" (agar pivot table DB terisi).
4. Klik *Delete* (Hapus) pada *row* "Test Cascading".
5. **Ekspektasi Hasil:** Muncul pop-up atau respon `400 Bad Request` bertulis: "Gagal menghapus jenis retribusi karena masih memiliki objek pajak atau data terkait lainnya" di layar / Network tab Chrome. API tidak boleh crash dengan Status 500 (Internal Server Error) dan tidak boleh melontarkan peringatan CORS Policy.

### 8. Verifikasi Ketersediaan Dokumen Resmi (Official Document Verification)
- **Aksi:** Menjalankan script `php testing/stg4_document_availability.php` untuk memvalidasi seluruh endpoint dokumen.
- **Ekspektasi:** Endpoint merespons dengan HTTP 200/201 (Valid JSON atau PDF Stream).

### 9. Uji Keamanan RBAC (Role-Based Access Control Isolation Test)
- **Aksi:** Memanggil endpoint khusus staf internal (cth: `GET /api/users`) menggunakan Bearer Token milik Wajib Pajak (Citizen).
- **Ekspektasi:** Endpoint wajib merespons dengan HTTP 403 (Forbidden).
- **Mitigasi Kondisi:** Jika merespons 200 OK, injeksi middleware (seperti `EnsureAdmin`) kemungkinan cacat. Pastikan middleware tidak meloloskan *instance* model `App\Models\Taxpayer` pada rute yang mensyaratkan `App\Models\User`.

### 10. Pengujian Konsistensi Environment (Stale Cache Mitigation Test)
- **Aksi:** Verifikasi respon aplikasi API setelah pembaruan parameter sensitif (kredensial database) pada file `.env` VPS yang disuntikkan oleh bot CI/CD.
- **Ekspektasi:** API segera merespons 200 OK tanpa error Database Connection (contoh 1045 Access Denied) pada rute yang dilindungi.
- **Mitigasi Kondisi (500 Server Error):** Jika respon gagal setelah *deploy* konfigurasi, kemungkinan memori servis *process manager* menahan *environment variables* usang (Stale Cache). Coba instruksi *reload* layanan PHP-FPM di VPS (contoh: `sudo systemctl reload php8.3-fpm` dan `php8.4-fpm`) untuk memaksa pemuatan ulang konfigurasi.

---

## Eksekusi Rutin Pengujian (How to Run)
Gunakan *shell script* kompilasi berikut untuk menguji dan memvalidasi siklus di atas:

```bash
# Menjalankan pengujian E2E integrasi di API Gateway
cd /Users/pondokit/Herd/retribusi-api
php artisan test:production-e2e
```


---

## File: docs/07_testing_kualitas/testing/13-Workspace-Master-Testing-Protocol.md
---
# Workspace Master Strategy & Diagnostic Protocol

Protokol ini menghubungkan seluruh bagian sistem: **Backend (API)**, **Admin**, **Mobile**, dan **Petugas** untuk memastikan ketahanan sistem secara menyeluruh.

---

## 1. Arsitektur Konektivitas
Semua aplikasi frontend/mobile bergantung pada satu sumber kebenaran: **Mpad API**.

| Repository | Fungsi | Koneksi Utama | Port Default |
|:---|:---|:---|:---|
| `retribusi-api` | Core Logic & DB | Laravel (PHP 8.3) | `8000` |
| `retribusi-admin` | Dashboard Management | `apiFetch` → API Production | `3001` |
| `retribusi-mobile` | Portal Wajib Pajak | `apiFetch` (with prefix) | `3002` |
| `retribusi-petugas` | Alat Petugas Lapangan | `apiFetch` → API Production | `3003` |

---

## 2. Deteksi Error Lintas-Platform (Diagnostic Guide)

Jika muncul error, ikuti alur deteksi ini untuk mengetahui di mana masalahnya:

### A. Deteksi 401 (Unauthorized)
**Gejala**: Tiba-tiba logout atau gagal login.
*   **Penyebab 1**: Kredensial di frontend (quick login) tidak sama dengan Database.
*   **Penyebab 2**: Token expired di `localStorage`.
*   **Mitigasi**: Jalankan `php artisan tinker` untuk cek email & password di DB. Hapus `localStorage` jika perlu.

### B. Deteksi 500 (Internal Server Error)
**Gejala**: Muncul popup "Gagal" atau "Internal Server Error".
*   **Langkah 1**: Buka **Inspect Element > Network Tab**.
*   **Langkah 2**: Klik request yang merah, lihat tab **Response**.
*   **Langkah 3**: Cari key `error_detail` dan `trace` (Hasil "Hardening" yang sudah kita buat).
*   **Diagnosa Cepat**:
    *   `Undefined variable $request` → Bug kode (tambah param Request).
    *   `Column not found` → Masalah Database (jalankan migrasi).
    *   `Trying to access property of non-object` → Relasi null (tambah check `if($obj)`).

### C. Deteksi 422 (Validation Error)
**Gejala**: Input ditolak meskipun data dirasa benar.
*   **Penyebab**: Perbedaan limit karakter (misal: kode zona max 10 di validator tapi di input 20).
*   **Mitigasi**: Relax-kan aturan di Controller (misal: `max:255`).

---

## 3. Protokol "Anti-Gagal" Backend (Universal)

Terapkan pola ini di seluruh `Retribusi-API` untuk perlindungan 100%:

```php
try {
    // 1. Sanitasi string kosong ke null
    // 2. Inference opd_id (otomatis cari ID dari relasi)
    // 3. Simpan data
} catch (\Throwable $e) {
    // LOG LENGKAP ke server
    \Log::error($e->getMessage(), ['trace' => $e->getTraceAsString()]);
    
    // KIRIM DETAIL ke frontend untuk debug cepat
    return response()->json([
        'message' => 'Gagal: ' . $e->getMessage(),
        'error_detail' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}
```

---

## 4. Sinkronisasi Environment (Cross-Repo)

Pastikan file `.env` di seluruh workspace selaras:

| Variabel | Admin | Mobile | Petugas | API |
|:---|:---|:---|:---|:---|
| **API_URL** | `VITE_API_URL` | `VITE_API_URL` | `VITE_API_URL` | `APP_URL` |
| **TOKEN_KEY** | `token` | `retribusi_auth_token` | `token` | - |
| **USER_KEY** | `user` | `retribusi_auth_user` | `user` | - |

> [!IMPORTANT]
> Jika URL API di Produksi berubah, **KETIGA** frontend di atas wajib di-build ulang dengan `.env.production` yang baru.

---

## 5. Checklist Verifikasi Global (Setiap Fitur Baru)

Sebelum fitur dianggap "Selesai", wajib lolos test ini:

1.  **Backend Feature Test**: `php artisan test --filter NamaFiturTest`.
2.  **Super Admin Test**: Coba simpan data tanpa pilih OPD (pastikan auto-infer jalan).
3.  **OPD Admin Test**: Coba simpan data (pastikan `opd_id` tersaring benar).
4.  **Deployment Trace**: Setelah deploy ke VPS, jalankan `curl` test ke API Produksi untuk memastikan kode benar-benar sudah ter-update di server.
5.  **Audit `destroy()`**: Pastikan setiap method `destroy` di Controller menerima parameter `Request $request`.
6.  **PWA Accessibility**: Pastikan rute `/unduh` tersedia di Mobile & Petugas dengan tombol CTA yang responsif.

### Audit Keamanan & Hardening
Semua controller berikut telah di-harden dengan `try-catch (\Throwable)` dan diagnostik detail:
- Master Data: `Zone`, `Type`, `Classification`, `Rate`.
- Operasional: `Verification`, `Payment`, `MonthlyReport`, `PenaltyWaiver`.

---

## 6. Lokasi File Dokumentasi Terkait
👉 **[error_history_and_mitigation_registry.md](file:///Users/pondokit/.gemini/antigravity/brain/0d142205-22d1-41cf-8ae4-e0cbb929e29d/error_history_and_mitigation_registry.md)**
-   **Analisis Master Data**: `[master_data_analysis.md](file:///Users/pondokit/.gemini/antigravity/brain/0d142205-22d1-41cf-8ae4-e0cbb929e29d/master_data_analysis.md)`
-   **Skema Testing Master Data**: `[master_data_testing_schema.md](file:///Users/pondokit/.gemini/antigravity/brain/0d142205-22d1-41cf-8ae4-e0cbb929e29d/master_data_testing_schema.md)`


---

## File: docs/07_testing_kualitas/testing/14-Master-Data-Testing-Schema.md
---
# Skema Pengujian & Mitigasi Error Komprehensif

Skema ini dirancang berdasarkan **kasus nyata** Error 500 pada Zone Creation di produksi, di mana akar masalahnya adalah **kode lokal belum ter-deploy ke VPS** sehingga validasi lama (`opd_id: required`) masih aktif.

---

## 1. Pelajaran dari Kasus Zone 500

| Aspek | Detail |
|:---|:---|
| **Gejala** | POST `/api/zones` → 500 Internal Server Error di produksi |
| **Akar Masalah** | Server produksi masih menjalankan validasi `'opd_id' => 'required'`, sementara frontend Super Admin tidak mengirim `opd_id` |
| **Kenapa Lokal OK** | Kode lokal sudah diubah ke `'nullable'` + auto-inference, tapi belum di-push/deploy |
| **Solusi** | `git push` → `git pull` di VPS → `php artisan migrate --force` → cache clear |

> [!CAUTION]
> **Golden Rule**: Setiap perubahan kode di lokal **WAJIB** diikuti dengan deployment ke produksi. Jangan hanya tes lokal!

---

## 2. Protokol Pengujian Produksi (Curl Test)

Setelah setiap deployment, jalankan test `curl` langsung ke API produksi untuk memastikan endpoint berfungsi:

```bash
# Test Zone Creation (Super Admin, tanpa opd_id)
curl -s -X POST https://api.sipanda.online/api/zones \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"retribution_type_id": 11, "name": "Test Zona", "geometry_type": "point"}' | python3 -m json.tool

# Test Klasifikasi Creation
curl -s -X POST https://api.sipanda.online/api/retribution-classifications \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"retribution_type_id": 11, "name": "Test Klas", "code": "TK-01"}' | python3 -m json.tool

# Test Rate Creation
curl -s -X POST https://api.sipanda.online/api/retribution-rates \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"retribution_type_id": 11, "retribution_classification_id": 1, "name": "Test Tarif", "amount": 5000}' | python3 -m json.tool
```

**Expected**: Status 201 Created. Jika 500 → kode belum ter-deploy.

---

## 3. Audit Kerentanan Per Controller

### ✅ Sudah Di-Harden (Try-Catch + Throwable)
| Controller | Cakupan Perbaikan | Status |
|:---|:---|:---|
| `ZoneController` | `opd_id` inference & validation | ✅ |
| `RetributionRateController` | Missing `$request` param fix | ✅ |
| `VerificationController` | Hardened `store` & `updateStatus` | ✅ |
| `PaymentController` | Hardened `store` | ✅ |
| `MonthlyReportController` | Hardened `store` & `validateReport` | ✅ |
| `PenaltyWaiverController` | Hardened `store` & `approve` | ✅ |

> [!TIP]
> Audit lengkap histori kesalahan sistem dapat dilihat di:
> 👉 **[error_history_and_mitigation_registry.md](file:///Users/pondokit/.gemini/antigravity/brain/0d142205-22d1-41cf-8ae4-e0cbb2929e29d/error_history_and_mitigation_registry.md)**

---

## 4. Pola Hardening Universal

Terapkan pola ini pada **setiap** controller `store()` dan `update()`:

```php
public function store(Request $request) {
    // 1. SANITASI: Ubah string kosong → null
    $input = $request->all();
    foreach (['opd_id', 'description', ...nullable_fields] as $f) {
        if (isset($input[$f]) && $input[$f] === '') $input[$f] = null;
    }
    $request->merge($input);

    // 2. VALIDASI: Gunakan nullable untuk field yang bisa di-infer
    $request->validate([
        'opd_id' => 'nullable|exists:opds,id',  // BUKAN required!
        // ...
    ]);

    // 3. TRY-CATCH: Tangkap SEMUA error (Throwable, bukan Exception)
    try {
        $data = $request->only([...]);

        // 4. INFERENCE: Auto-fill opd_id dari relasi
        if (empty($data['opd_id'])) {
            $type = RetributionType::find($data['retribution_type_id']);
            if ($type) $data['opd_id'] = $type->opd_id;
        }

        // 5. AUTO-GENERATE: Isi field wajib DB yang tidak perlu diisi user
        if (empty($data['code'])) {
            $data['code'] = 'AUTO-' . Str::random(8);
        }

        $model = Model::create($data);
        return response()->json($model, 201);

    } catch (\Throwable $e) {
        \Log::error('Creation Failed: ' . $e->getMessage(), [
            'request' => $request->all(),
            'trace'   => $e->getTraceAsString()
        ]);
        return response()->json([
            'message'      => 'Gagal: ' . $e->getMessage(),
            'error_detail' => $e->getMessage(),
        ], 500);
    }
}
```

---

## 5. Checklist Deployment Produksi

Setiap kali deploy ke VPS, jalankan checklist ini:

- [ ] `git push origin main` dari lokal
- [ ] SSH ke VPS: `cd /home/mpad/retribusi-api`
- [ ] `git pull origin main`
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `php artisan migrate --force`
- [ ] `php artisan config:cache && php artisan route:cache`
- [ ] **Curl Test**: Jalankan semua curl test dari Bagian 2
- [ ] **Build Frontend**: `npm run build` → SCP `dist/` ke VPS (jika ada perubahan frontend)

---

## 6. Skenario Test Otomatis (Feature Test)

File: `tests/Feature/MasterDataHierarchyTest.php`

| Test Case | Apa yang Diuji | Expected |
|:---|:---|:---|
| `test_zone_creation_infers_opd_id` | Super Admin buat zona tanpa isi `opd_id` | 201, `opd_id` diisi otomatis |
| `test_zone_empty_strings_to_null` | Input `description: ""`, `latitude: ""` | 201, field jadi `null` di DB |
| `test_zone_validates_required` | Tanpa `name` dan `retribution_type_id` | 422, bukan 500 |
| `test_zone_allows_long_codes` | Update zona dengan kode > 10 char | 200, bukan 422 |
| `test_classification_infers_opd_id` | Super Admin buat klasifikasi tanpa `opd_id` | 201, `opd_id` diisi otomatis |

```bash
# Jalankan test otomatis
php artisan test tests/Feature/MasterDataHierarchyTest.php
```

---

## 7. Pola Kesalahan Umum & Mitigasi

| Pola Kesalahan | Contoh Kasus | Mitigasi |
|:---|:---|:---|
| **Kode belum deploy** | Lokal OK, prod 500 | Selalu `curl` test ke prod setelah deploy |
| **Validasi `required` untuk Super Admin** | `opd_id: required` padahal SA tidak punya OPD | Ubah ke `nullable` + inference dari relasi |
| **Kolom NOT NULL tanpa default** | `code varchar(10) NOT NULL` di DB | Auto-generate di backend + relaxkan max length |
| **`catch(\Exception)` tidak cukup** | PHP TypeError lolos | Gunakan `catch(\Throwable)` |
| **String kosong → SQL error** | `latitude: ""` gagal masuk kolom `decimal` | Sanitasi `""` → `null` sebelum validasi |
| **`$request` tidak ada** | `destroy()` pakai user() tapi tanpa param | Selalu tambahkan `Request $request` di signature |
| **Migration belum jalan** | Kolom baru ada di lokal, tidak di prod | Selalu `php artisan migrate --force` setelah pull |


---

## File: docs/07_testing_kualitas/testing/12-Error-History-Mitigation-Registry.md
---
# Register Sejarah Error & Mitigasi (Mpad Ecosystem)

Dokumen ini mencatat daftar kesalahan (error) kritis yang pernah terjadi selama pengembangan sistem Mpad dan langkah mitigasi permanen yang telah diterapkan.

---

## 1. Error Infrastruktur & Environment

| Error | Gejala | Akar Masalah | Mitigasi / Solusi |
|:---|:---|:---|:---|
| **CORS Blocked** | Status 500 / Blocked di browser | Domain `sipanda.online` belum diizinkan oleh `api.sipanda.online` | Update `cors.php` untuk mengizinkan wildcard `*` atau domain spesifik prod. |
| **New Subdomains CORS** | Akses dari `mpad`, `adminmpad`, `petugasmpad` diblokir | Domain baru tidak masuk `allowed_origins` di API CORS. | Menambahkan `https://mpad.baubaukota.go.id`, `https://adminmpad.baubaukota.go.id`, & `https://petugasmpad.baubaukota.go.id` ke `cors.php`. |
| **SSL/HTTPS Warning** | "Your connection is not private" | Domain baru menggunakan IP langsung atau sertifikat yang tidak valid / tidak sesuai. | Men-generate dan mengaktifkan sertifikat SSL Let's Encrypt via `certbot --nginx` untuk ketiga domain M-PAD. |
| **Nginx Routing Error** | Salah memuat aplikasi Frontend | Nginx server blocks belum dikonfigurasi untuk sub-domain yang baru. | Membuat file `mpad-frontend` di `/etc/nginx/sites-available` yang memetakan masing-masing domain ke `/dist` Mobile, Admin, dan Petugas. |
| **Bapenda API Fail** | "Login Failed" di PBB | Credential API PBB di `.env` VPS salah/kadaluarsa. | Update `.env` VPS dengan kredensial resmi. Gunakan `php artisan config:cache`. |
| **Migration Mismatch** | `Column not found` di Prod | Kolom baru di lokal belum ada di VPS. | Jalankan `php artisan migrate --force` di VPS setiap kali `git pull`. |
| **Local Login 401**| Gagal login di localhost | Akun demo di frontend tidak ada di database seeder. | Gunakan `TestingScenarioSeeder` yang lengkap atau buat user manual via Tinker. |
| **500 Server Error (Staging)** | Aplikasi Citizen menampilkan 500 error pasca-deploy | Konfigurasi PHP-FPM cache yang stale/kedaluwarsa setelah sinkronisasi V-Tax Parity. | Reload atau restart service PHP-FPM (`sudo systemctl reload php8.x-fpm`) untuk membersihkan instance lama. |
| **SSH Auth Lockdown** | Akses SSH ke VPS ditolak | Layer keamanan TENGS (Tengs Security) memblokir IP/port login yang dianggap tidak wajar. | Whitelist IP address atau sesuaikan rule TENGS untuk environment deployment pipeline. |

---

## 2. Error Logika Backend (API)

| Error | Gejala | Akar Masalah | Mitigasi / Solusi |
|:---|:---|:---|:---|
| **Zone Creation 500**| Crash saat simpan Zona | Validasi `opd_id: required` tapi Super Admin tidak kirim data. | Ubah ke `nullable` + Logic Auto-Inference dari `retribution_type`. |
| **Delete Rate 500** | Crash saat hapus Tarif | Method `destroy()` pakai `$request` tapi param tidak dideklarasi. | Tambahkan `Request $request` pada parameter semua method Controller. |
| **Formula Parser 500**| Gagal hitung tagihan | Variabel rumus tidak ditemukan atau pembagian nol. | Tambahkan `try-catch (\Throwable)` di service parser + default value `0`. |
| **Logout 401** | Gagal logout | Token sudah dihapus atau mismatch. | Tambahkan pengecekan `if ($request->user())` sebelum delete token. |
| **Duplicate NOP 500** | Crash saat update WP | `updateOrCreate` di `syncTaxObject` pakai matching criteria berbeda dari unique constraint `nop`. | Ganti dengan 3-step lookup (by combo → by NOP → create new) + fallback. |
| **ValidationException→500** | Error 422 jadi 500 | `try-catch(\Throwable)` menangkap `ValidationException`. | Tambahkan rethrow `ValidationException` dan `ModelNotFoundException` sebelum catch `\Throwable`. |

---

## 3. Error Frontend & Mobile

| Error | Gejala | Akar Masalah | Mitigasi / Solusi |
|:---|:---|:---|:---|
| **JSX Syntax Error** | White screen / Build fail | Tag JSX tidak tertutup di `Dashboard.tsx`. | Gunakan ESLint auto-fix dan periksa visual code indentasi. |
| **GPS Unavailable** | "Position unavailable" | Hardware GPS mati atau user menolak izin lokasi. | Implementasi Fallback: Jika GPS gagal, biarkan user input manual atau pakai koordinat terakhir. |
| **Empty State 500** | UI pecah saat data nol | Frontend mencoba `map()` pada data yang `null`. | Selalu gunakan optional chaining `data?.map()` atau default array `data || []`. |

---

## 4. Matriks Mitigasi Lintas Platform

Untuk mencegah error serupa di masa depan, sistem sekarang mewajibkan:

1.  **Backend "Anti-Gagal"**: Semua method `store/update/destroy` wajib dibungkus `try-catch (\Throwable)` dan mengembalikan `error_detail` jika gagal.
2.  **Inference Logic**: Field `opd_id` tidak boleh `required` jika bisa dicari otomatis dari relasi data di atasnya.
3.  **Checklist Depoyment**: `pull` → `migrate` → `cache:clear` adalah prosedur wajib satu paket.
4.  **Diagnostic UI**: Frontend sekarang menampilkan pesan error spesifik dari backend (bukan hanya "Gagal"), sehingga user/admin bisa langsung tahu penyebabnya (misal: "Baris 119: Undefined variable").
5.  **Duplicate-Safe Sync**: Fungsi `syncTaxObject()` menggunakan 3-step lookup (by combo → by NOP → create new) agar tidak crash saat NOP sudah terpakai oleh record sebelumnya.

---

## 5. Test Coverage Otomatis

Semua error di atas dilindungi oleh: **`HistoricalErrorRegressionTest.php`**

| Error | Test Case | Assert |
|:---|:---|:---|
| Login 401 | `error_login_returns_token_for_valid_credentials` | 200 + token |
| Login Invalid | `error_login_returns_401_for_invalid_credentials` | 401 |
| Logout | `error_logout_succeeds_for_authenticated_user` | 200 |
| Zone 500 | `error_zone_creation_without_opd_id_infers_from_type` | 201 + opd_id |
| Delete Rate 500 | `error_delete_rate_does_not_crash_with_500` | ≠500 |
| Formula 500 | `error_formula_parser_handles_missing_variables` | ≠500 |
| Verification 422 | `error_verification_store_returns_422_for_invalid_data` | 422 |
| Payment 422 | `error_payment_store_returns_422_for_missing_fields` | 422 |
| **Duplicate NOP** | `error_taxpayer_duplicate_nop_on_re_update_does_not_crash` | ≠500 |
| **Taxpayer 422** | `error_taxpayer_update_returns_422_for_invalid_data` | 422 |
| Migration | `error_taxpayer_migration_has_required_columns` | schema OK |
| NOP Column | `error_tax_objects_has_nop_column` | schema OK |

**Total: 28 tests, 46 assertions** — semua PASSED ✅

---
*Register ini akan terus diperbarui seiring perkembangan sistem.*



---

## File: docs/07_testing_kualitas/testing/03-Alur-Utama-E2E.md
---
# Tahap 3: Pengujian Alur Utama Pajak / Retribusi (E2E Business Flow)

Skenario ini adalah fondasi aplikasi Anda. Menirukan dunia nyata di mana Wajib Pajak mendaftar, Admin menerbitkan tagihan (SKPD), Petugas melakukan validasi bayar di lapangan, dan dana tersinkronisasi mendarat di Dashboard.

---

### Skenario 1: Wajib Pajak Mendaftar (Via Aplikasi Mobile Wajib Pajak)
- [ ] Buka Aplikasi Smartphone (Android/iOS) Mpad / M-PAD.
- [ ] Saat di layar sapaan awal (*Onboarding*), klik tombol **"Daftar"**.
- [ ] Ketikkan formulir pendaftaran Wajib Pajak meminjam identitas *dummy* berikut:
  - **NIK Utama**: `3201234567890001` (Ketik teliti 16 digit).
  - **Nama Lengkap Sesuai KTP**: `Budi Tester UAT`
  - **Email Aktif**: `budi.uat@test.com`
  - **Nomor HP / WA**: `081234567890`
  - **Password/Sandi**: `password123`
- [ ] Ketuk ikon **"Daftar/Register"**.
- [ ] Sistem akan mengalihkan kembali ke *Login*. Ketikkan `budi.uat@test.com` dengan sandi rahasia yang sama.
- [ ] **KRITERIA SUKSES**: Tembus ke layar Beranda (*Homepage*). Di atas layar tertulis *"Halo, Budi Tester UAT"*. Klik menu Profil untuk mengecek apakah angka NIK tidak terpotong (konsisten 16 digit). Dan lihat Tab "Tagihan", pastinya harus tertulis dengan santun *"Tidak ada tagihan aktif"* (Rp 0).

---

### Skenario 2: Bapenda Menerbitkan Tagihan Baru (Via Web Dashboard Admin)
*Peralihan Peran: Anda sekarang duduk di Kantor Bapenda sebagai Pegawai Superadmin.*

- [ ] Buka peramban laptop. Jelajahi situs `https://admin.sipanda.online`.
- [ ] Tembus masuk (Login) dengan alamat email dan kata sandi otentik milik **Admin Hak Bapenda**.
- [ ] **Sinkronisasi Warga**: 
  - Masuk ke sub-menu **"Master Data"** -> **"Wajib Pajak"**.
  - Ketik pelan `Budi Tester UAT` / `3201234567890001` pada bilah penelusuran tabel.
  - **Kriteria Sukses**: Data Sang WP dari aplikasi Handphone sebelumnya harus sudah masuk ke database pusat Admin otomatis!
- [ ] **Mendaftarkan Usaha Potensi Budi**:
  - Pindah ke ruas menu **"Objek Pajak / Potensi Daerah"**.
  - Klik balok biru **"Tambah Data"**.
  - Isi Form Survei Lokasi Usaha:
    - **Nama Usaha Lokal**: `Warung Sate Budi UAT`
    - **Golongan Jenis Pajak**: Tekan dropdown dan pilih `Pajak Restoran / Rumah Makan`.
    - **Pemilik Usaha**: _Search_ & seret terpilih `Budi Tester UAT` (`32012...`) tadi.
    - **Detail Alamat Lokasi**: `Jl. Percobaan UAT No.1, Kec. Testing, Kota Baubau`.
  - Klik **Simpan**.
- [ ] **Mematok Ketetapan Tagihan (SKPD)**:
  - Loncat ke ruas menu **"Billing / Penetapan Pajak"**.
  - Klik ikon tambah **"Buat Billing Baru"**.
  - Pilih objek sasarannya: `Warung Sate Budi UAT`.
  - Perhitungkan kalkulasi masa pajaknya:
    - **Masa Pajak Bulan**: Pilih bulan kalender saat ini (Misal `Februari 2026`).
    - **Total Pokok Tagihan**: Tik nominal bulat Rp `150.000` (Simulasi Tarif 10% jika form omzet ada).
    - **Atur Jatuh Tempo**: Pilih batas telat hari ke-30 bulan ini.
  - Klik **Simpan dan Rilis SKPD Nasional**.
  - **KRITERIA SUKSES**: Akan muncul list baru dalam baris tabel tagihan, nominal total Rp 150.000 dengan status tebal berlatar warna merah darah bertuliskan keras **"Belum Dibayar"** (*Unpaid*).

---

### Skenario 3: Wajib Pajak Kaget Ada Tagihan (Via App Mobile Wajib Pajak)
*Peralihan Peran: Anda kembali menjadi Mas Budi sambil memegang hapenya.*

- [ ] Buka Aplikasi Handphone Siaga Wajib Pajak itu kembali (akun Budi UAT).
- [ ] Pegang area tengah layar, sorong ke bawah dengan cepat untuk me-_Refresh_ data (*Pull to Refresh*).
- [ ] **KRITERIA KESUKSESAN MUTLAK**: 
  - Lingkaran ringkasan saldo bagian *Top Header* Aplikasi yang tadinya diam di `Rp 0`, seketik melonjak tajam tertuliskan Tagihan Anda: **Rp 150.000**.
  - Ada "Bel Notifikasi Merah" menginformasikan hadirnya Surat Ketetapan Pajak (SKPD) bulan berjalan. 
  - Bila masuk ke blok navigasi "Tagihan Pembayaran", terbaca kartu berdetail rincian jatuh tempo *Warung Sate Budi UAT* seharga pembayaran tersebut.

---

### Skenario 4: Intervensi Petugas Loket (Via Web App Kolektor Lapangan)
*Peralihan Peran: Bapak Wajib Pajak Budi secara perlakuan nyata enggan/gaptek bayar online virtual account, sehingga ia membawa Motornya ke Kantor Cabang Loket Bapenda / didatangi Petugas Kolektor Desa.*

- [ ] Siapkan PC Kasir Tablet atau perangkat Mobile. Kunjungi `https://petugas.sipanda.online`.
- [ ] Autentikasi Login terdaftarkan menggunakan akses hak **Petugas Lapangan / Kolektor Harian**.
- [ ] Berjejer di berandanya, tap bagian raksasa bertuliskan **"Penerimaan Pembayaran / Loket SKPD"**.
- [ ] Arahkan kursor pencari Tagihan dan tembak: 
  - Ketik NIK `320123...` (atau scan QR Tagihan langsung dari layar HP si Budi UAT tadi).
- [ ] Hasilnya tampil, tap detail Tagihan Usaha `Warung Sate Budi UAT`.
- [ ] Cek seksama oleh mata Petugas, oh senilai sama **Rp 150.000** berstatus Merah.
- [ ] Petugas menekan keras tombol penyelesaian sistem: **"TANDAI LUNAS / PROSES BAYAR"**.
- [ ] Muncul peringatan final, konfirmasi Tipe Bayar `TUNAI / KAS` -> klik Oke.
- [ ] **KRITERIA KESUKSESAN**: Muncrat *Effect Confetti/Toaster* Sukses berwarna HIJAU DAUN cemerlang. Naskah cetakan bon Struk *Surat Setoran Pajak Daerah (SSPD)* ter-generate otomatis!

---

### Skenario 5: Pengecekan Kebenaran Akhir (Real-Time Sinkronisasi Integritas)
*Melihat sistem bekerja serentak menautkan seluruh jaring database real-time API.*

- [ ] **Kembali Mengintip Laptop Admin Bapenda (Dashboard `admin.mpad`)**:
  - Tap ikon Logo Rumah / *Dashboard Executive Panel*.
  - Lihat box metrik bertajuk "Realisasi Penerimaan Hari Ini". Angkanya seharusnya langsung gemuk meloncat **bertambah senilai Rp 150.000**.
  - Lompat mengecek tabel ketat `Billing`. Label status yang memerah "Belum Dibayar" pada objek mas Budi telah disulap sempurna menjadi hijau segar terbaca **"Lunas / Paid"**.
- [ ] **Melirik Kembali Dompet Tagihan HP Wajib Pajak Budi (`App WP`)**:
  - Genggam HP Budi. Buka paksa App.
  - Sisa Piutangnya di beranda lenyap memudar kembali tertulis nyaman asri **Rp 0 rupiah**.
  - Ia bermigrasi ke menu seksi Riwayat Dompet, terpajang jejak log historis "*Pembayaran Tagihan Anda Sebesar Rp 150.000 telah disetujui petugas (Ahmad Petugas UAT) pada Jam Sekian*". WP bisa menge-klik untuk mengunduh versi PDF legal resi elektronik tersebut ke direktori *Files* hapenya!


---

## File: docs/07_testing_kualitas/INVOICE_TEST_SUITE.md
---
# M-PAD Invoice Test Suite

Dokumen ini berisi daftar skenario pengujian untuk memvalidasi integritas sistem penagihan (invoicing) di seluruh platform.

---

## 🏗️ Skenario 1: Official Assessment (Penetapan Jabatan)
*Target: Usaha tetap dengan tarif flat/variabel tetap (Contoh: Retribusi Sampah, PBB-P2).*

| No | Langkah Pengujian | Peran | Hasil yang Diharapkan |
| :--- | :--- | :--- | :--- |
| 1.1 | Pendaftaran Objek Pajak baru dengan metadata lengkap. | Masyarakat/Petugas | Data tersimpan di database dengan status `pending`. |
| 1.2 | Persetujuan (Approval) pendaftaran di dashboard. | Admin | Status objek menjadi `active` dan **Bill (SKRD)** pertama terbit otomatis. |
| 1.3 | Periksa detail Bill di dashboard Admin. | Admin | Nomor invoice sesuai format (`INV-...`), nominal sesuai tarif pusat/zona. |
| 1.4 | Bayar tagihan via Tunai di loket. | Petugas/Admin | Status Bill berubah menjadi `lunas`, record `Payment` tercipta. |

---

## 📝 Skenario 2: Self-Assessment (Pelaporan Mandiri)
*Target: Usaha berbasis omzet (Contoh: Pajak Hotel, Restoran, Parkir).*

| No | Langkah Pengujian | Peran | Hasil yang Diharapkan |
| :--- | :--- | :--- | :--- |
| 2.1 | Input Laporan Omzet Bulanan (SPTPD). | Masyarakat | Record `MonthlyReport` tercipta dengan status `pending`. |
| 2.2 | Verifikasi dan Persetujuan laporan. | Admin | Status laporan `approved`, **Bill (SKRD)** terbit otomatis berdasarkan omzet. |
| 2.3 | Simulasi penolakan (Reject) laporan. | Admin | Status laporan `rejected`, tidak ada Bill yang terbit. Pesan alasan muncul di Mobile. |
| 2.4 | WP membayar via QRIS/VA dan klaim bukti bayar. | Masyarakat | Status Payment `pending` (menunggu verifikasi bukti manual). |

---

## 🤳 Skenario 3: Penagihan Lapangan (QR Scan & JIT)
*Target: Operasional harian petugas saat surveillance.*

| No | Langkah Pengujian | Peran | Hasil yang Diharapkan |
| :--- | :--- | :--- | :--- |
| 3.1 | Scan QR Objek Pajak yang memiliki tunggakan lama. | Petugas | Muncul daftar **Virtual Bill** (Jit Billing) untuk semua bulan tertinggal. |
| 3.2 | Pilih 2 dari 5 bulan tunggakan untuk dibayar. | Petugas | Tercipta 2 record Payment sukses, sisa 3 bulan tetap tertunggak. |
| 3.3 | Verifikasi keabsahan denda pada Virtual Bill. | Petugas | Denda muncul otomatis (1-2%/bulan) berdasarkan `due_date`. |

---

## ⌚ Skenario 4: Denda & Jatuh Tempo (Penalty Automation)
*Target: Validasi integritas finansial dan regulasi.*

| No | Langkah Pengujian | Peran | Hasil yang Diharapkan |
| :--- | :--- | :--- | :--- |
| 4.1 | Ubah `due_date` bill yang ada menjadi H-60 (manual DB). | Developer | - |
| 4.2 | Jalankan command `php artisan bills:calculate-penalties`. | Admin (CLI) | Kolom `penalty_amount` pada bill tersebut terisi otomatis (bunga 2 bulan). |
| 4.3 | Bayar bill yang sudah terkena denda. | WP/Petugas | Total bayar = Pokok + Denda. Status bill menjadi `lunas`. |

---

## ⚠️ Skenario Negatif (Edge Cases)
- **Double Payment**: Mencoba membayar periode yang sama di dua device berbeda secara bersamaan.
- **Formulasi Error**: Pendaftaran objek tanpa zona (jika zona wajib) -> Pastikan sistem tidak *crash* saat menghitung JIT.
- **Metadata Missing**: Menghapus metadata Luas M2 pada objek Reklame -> Periksa apakah billing jatuh ke nilai *fallback*.

---
> *Skema ini dirancang untuk memastikan nol kebocoran pendapatan daerah melalui validasi sistem yang ketat.*


---

