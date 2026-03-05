# Daftar Lengkap: API Endpoints, FE Routes & Komponen
**Terakhir diperbarui**: 5 Maret 2026

---

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
| `GET` | `/api/pengawas/enforcements/history/{tax_object_id}` | `EnforcementNoticeController::getHistory` | Riwayat penindakan |
| `GET` | `/api/pengawas/enforcements/{id}/pdf` | `EnforcementNoticeController::generatePDF` | Cetak PDF |
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
│   └── 14-Master-Data-Testing-Schema.md
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
│   └── test_production_regression.sh  (Bash — Regression test)
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

### Pemetaan Endpoint → Test Coverage

Tabel berikut menunjukkan endpoint API mana yang sudah ter-cover oleh skrip test yang ada.

#### Fase 1: Autentikasi & User
| Endpoint | Method | Test Script | Fase |
|----------|--------|-------------|------|
| `/api/login` | POST | `test_api_crud.sh`, `run_role_e2e_test.php` | Auth |
| `/api/citizen/login` | POST | `run_role_e2e_test.php` | Auth |
| `/api/citizen/register` | POST | `run_role_e2e_test.php` | Auth |
| `/api/logout` | POST | `run_role_e2e_test.php` | Auth |
| `/api/user` | GET | `test_api_crud.sh` | Auth |
| `/api/me` | GET | `run_role_e2e_test.php` | Auth |
| `/api/users` | CRUD | `run_rbac_test.php` | RBAC |

#### Fase 2: Master Data
| Endpoint | Method | Test Script | Fase |
|----------|--------|-------------|------|
| `/api/retribution-types` | CRUD | `test_api_crud.sh`, `run_role_e2e_test.php` | Master |
| `/api/retribution-classifications` | CRUD | `test_api_crud.sh` | Master |
| `/api/retribution-rates` | CRUD | `test_api_crud.sh` | Master |
| `/api/zones` | CRUD | `test_api_crud.sh`, `run_role_e2e_test.php` | Master |
| `/api/opds` | CRUD | `run_rbac_test.php` | Master |

#### Fase 3: Wajib Pajak & Objek Pajak
| Endpoint | Method | Test Script | Fase |
|----------|--------|-------------|------|
| `/api/taxpayers` | CRUD | `test_api_crud.sh`, `run_role_e2e_test.php` | WP |
| `/api/taxpayers/search/{nik}` | GET | `test_api_crud.sh` | WP |
| `/api/tax-objects` | CRUD | `test_api_crud.sh`, `run_role_e2e_test.php` | Objek |
| `/api/verifications` | CRUD | `run_role_e2e_test.php` | Verifikasi |

#### Fase 4: Billing & Pembayaran
| Endpoint | Method | Test Script | Fase |
|----------|--------|-------------|------|
| `/api/bills` | GET/POST | `run_role_e2e_test.php` | Billing |
| `/api/bills/{bill}` | GET | `run_role_e2e_test.php` | Billing |
| `/api/bills/{bill}/pay` | POST | `run_role_e2e_test.php` | Payment |
| `/api/payments` | GET/POST | ⚠️ **Baru** — Perlu ditambahkan | Payment |
| `/api/payments/{id}/status` | PUT | ⚠️ **Baru** — Perlu ditambahkan | Verifikasi |
| `/api/tax-objects/{id}/pending-periods` | GET | `run_role_e2e_test.php` | Billing |

#### Fase 5: Kalkulasi & Simulasi
| Endpoint | Method | Test Script | Fase |
|----------|--------|-------------|------|
| `/api/simulate-tax` | POST | `run_kalkulator_test.php` | Kalkulasi |
| `/api/tax-formulas` | GET | `run_kalkulator_test.php` | Kalkulasi |
| `/api/pbb/calculate` | POST | `run_kalkulator_test.php` | PBB |
| `/api/pbb/classifications` | GET | `run_kalkulator_test.php` | PBB |
| `/api/pbb/bapenda/inquiry` | POST | `test_api_crud.sh` | PBB |

#### Fase 6: Pengawasan & Penindakan
| Endpoint | Method | Test Script | Fase |
|----------|--------|-------------|------|
| `/api/pengawas/audit-logs` | GET | `run_rbac_test.php` | Pengawasan |
| `/api/pengawas/anomalies` | GET | `run_rbac_test.php` | Pengawasan |
| `/api/pengawas/enforcements` | GET/POST | `run_rbac_test.php` | Penindakan |
| `/api/pengawas/penindakan` | GET | `run_rbac_test.php` | Penindakan |
| `/api/amnesty` | GET/POST | `run_rbac_test.php` | Amnesti |

#### Fase 7: Reporting & Dokumen
| Endpoint | Method | Test Script | Fase |
|----------|--------|-------------|------|
| `/api/reports/bpk` | GET | `run_role_e2e_test.php` | Report |
| `/api/reports/summary` | GET | `run_role_e2e_test.php` | Report |
| `/api/dashboard/stats` | GET | `run_role_e2e_test.php` | Dashboard |
| `/api/documents/skrd/{id}` | GET | `run_role_e2e_test.php` | Dokumen |
| `/api/documents/sspd/{id}` | GET | `run_role_e2e_test.php` | Dokumen |
| `/api/documents/sppt/{id}` | GET | `run_role_e2e_test.php` | Dokumen |
| `/api/documents/skt/{id}` | GET | `run_role_e2e_test.php` | Dokumen |

#### Fase 8: Keamanan
| Endpoint | Method | Test Script | Fase |
|----------|--------|-------------|------|
| Semua endpoint | — | `test_penetration.sh` | SQL Injection |
| Semua endpoint | — | `test_penetration.sh` | XSS |
| Semua endpoint | — | `test_penetration.sh` | IDOR |
| `.env`, `debug` | — | `test_production_ready.sh` | File sensitif |
| CORS headers | — | `test_production_cors.sh` | CORS |

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

