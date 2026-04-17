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

