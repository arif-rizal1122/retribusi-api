# Pemetaan Arsitektur & Fase Pengujian E2E (Omni Workspace)

Dokumen ini memetakan seluruh rute (routes), model, view (pages/components), dan controller dari 4 repositori M-PAD ke dalam **8 Fase Pengujian Terstruktur**. Pengelompokan ini disusun agar Anda dapat melakukan validasi sistem secara komprehensif dari hulu ke hilir (End-to-End Test).

> [!TIP]
> **Cara Penggunaan untuk Testing:**
> Jalankan pengujian secara berurutan mulai dari Fase 1. Pastikan setiap model dan logic controller pada sebuah fase sudah lulus uji sebelum beralih ke fase berikutnya untuk mencegah "Cascading Errors" (Error Beruntun).

---

## FASE 1: Authentication & Hak Akses (RBAC)
**Fokus Pengujian:** Validasi login lintas peran, registrasi Warga/OPD/PPAT, pembaruan profil, dan perlindungan Rute Guarded di Frontend & Backend.

- **Models (`retribusi-api`)**:
  - `User`, `Opd`
- **Controllers (`retribusi-api`)**:
  - `AuthController`, `OpdController`, `UserController`, `MeController`
- **Backend Routes**:
  - `POST /api/login`, `POST /api/citizen/login`, `POST /api/logout`
  - `POST /api/opd/register`, `POST /api/citizen/register`
  - `GET /api/user`, `GET /api/me`, `GET /api/users`
- **Views & Components (`React/Vite`)**:
  - **Admin**: `Login.tsx`, `OpdRegistration.tsx`, `PublicRegistration.tsx`, `Profile.tsx`, `ProtectedRoute.tsx`
  - **Petugas**: `PetugasLogin.tsx`, `PetugasRegister.tsx`
  - **Mobile**: `Login.tsx`, `Register.tsx`, `UserProfile.tsx`, `ChangePassword.tsx`

---

## FASE 2: Master Data & Konfigurasi Ekosistem
**Fokus Pengujian:** Pengelolaan struktur fondasi pajak, formula perhitungan dasar, penentuan wilayah/zona, serta struktur OPD penanggung jawab.

- **Models (`retribusi-api`)**:
  - `RetributionType`, `RetributionClassification`, `RetributionRate`, `Zone`, `Department`
- **Controllers (`retribusi-api`)**:
  - `RetributionTypeController`, `RetributionClassificationController`, `RetributionRateController`, `ZoneController`
- **Backend Routes**:
  - `GET|POST|PUT|DELETE /api/retribution-types`
  - `GET|POST|PUT|DELETE /api/retribution-classifications`
  - `GET|POST|PUT|DELETE /api/retribution-rates`
  - `GET|POST|PUT|DELETE /api/zones`
  - `GET /api/tax-formulas`, `POST /api/simulate-tax`
- **Views & Components (`React/Vite`)**:
  - **Admin**: `MasterData.tsx`, `SystemAdmin.tsx`, `OpdManagement.tsx`
  - **Petugas**: `MasterData.tsx`, `TaxCalculator.tsx`

---

## FASE 3: Wajib Pajak & Objek Pajak (Pendaftaran & Verifikasi)
**Fokus Pengujian:** Pendaftaran Wajib Pajak (WP) baru, inventarisasi Objek Pajak (Titik Reklame, Parkir, dll), dan proses Verifikasi data oleh Admin/Petugas.

- **Models (`retribusi-api`)**:
  - `Taxpayer`, `TaxObject`, `ObjectVerification`
- **Controllers (`retribusi-api`)**:
  - `TaxpayerController`, `TaxObjectController`, `TaxpayerSearchController`, `VerificationController`
- **Backend Routes**:
  - `GET|POST|PUT|DELETE /api/taxpayers`
  - `GET /api/taxpayers/search/{nik}`
  - `GET|POST|PUT|DELETE /api/tax-objects`
  - `GET|POST /api/verifications`, `PUT /api/verifications/{id}/status`
- **Views & Components (`React/Vite`)**:
  - **Admin**: `Verification.tsx`, `TaxpayerEditModal.tsx`
  - **Petugas**: `FieldScanner.tsx`, `TaxpayerManagement.tsx`, `TaxpayerDetail.tsx`

---

## FASE 4: Pelaporan (SPTPD) & Penetapan Tagihan (Billing)
**Fokus Pengujian:** Proses Warga/WP melaporkan pajaknya (Self Assessment SPTPD), persetujuan SPTPD oleh Admin, serta penerbitan dan pembuatan Tagihan SKPD/SKRD oleh Petugas/Admin (Official Assessment).

- **Models (`retribusi-api`)**:
  - `MonthlyReport`, `Bill`
- **Controllers (`retribusi-api`)**:
  - `MonthlyReportController`, `BillController`, `CitizenServiceController`
- **Backend Routes**:
  - `POST /api/citizen/reports` (Submit SPTPD)
  - `GET /api/reports/monthly`, `PUT /api/reports/monthly/{report}/validate`
  - `GET /api/bills`, `POST /api/bills` (Buat Tagihan manual)
  - `GET /api/citizen/bills`
  - `GET /api/documents/skrd/{billId}`, `GET /api/documents/sppt/{billId}`
- **Views & Components (`React/Vite`)**:
  - **Admin**: `ESptpd.tsx`, `Billing.tsx`
  - **Petugas**: `CreateSKPD.tsx`, `Billing.tsx`
  - **Mobile**: `SptpdReporting.tsx`, `LayananRetribusi.tsx`, `Tagihan.tsx`, Modul Spesifik (`Parkir.tsx`, `Reklame.tsx`, dll)

---

## FASE 5: Pembayaran & Rekonsiliasi (Payment & Settlement)
**Fokus Pengujian:** Eksekusi pembayaran melalui Bank Multi-Channel (H2H), e-Wallet, atau pembayaran Tunai kepada Petugas di lapangan, beserta konfirmasi kasir.

- **Models (`retribusi-api`)**:
  - `Payment`, `PaymentGatewayLog`, `TaxTransaction`
- **Controllers (`retribusi-api`)**:
  - `PaymentController`, `BankH2HController`, `PublicVerificationController`
- **Backend Routes**:
  - `GET|POST /api/payments`
  - `POST /api/bills/{bill}/pay`
  - `PUT /api/payments/{payment}/status`
  - `POST /api/v1/bank/payment`, `POST /api/v1/bank/inquiry`, `POST /api/v1/bank/reversal`
  - `GET /api/verify/payment/{number}`
- **Views & Components (`React/Vite`)**:
  - **Admin**: `H2HLogs.tsx`
  - **Petugas**: `PaymentConfirmation.tsx`, `PaymentVerification.tsx`
  - **Mobile**: `Pembayaran.tsx`, `PaymentCard.tsx`, `AtmCard.tsx`

---

## FASE 6: Pengawasan, Penugasan & Penindakan (Surveillance)
**Fokus Pengujian:** Modul Pengawas untuk melakukan inspeksi (Uji Petik), mencatat Kertas Kerja, mendeteksi anomali realisasi, menetapkan denda (SKPDKB), dan memberikan tugas Geospasial ke Petugas di lapangan.

- **Models (`retribusi-api`)**:
  - `SpotCheck`, `SpotCheckItem`, `EnforcementNotice`, `AuditLog`, `PetugasTask`, `Complaint`, `PenaltyWaiver`
- **Controllers (`retribusi-api`)**:
  - `SpotCheckController`, `SurveillanceController`, `EnforcementNoticeController`, `PenindakanController`, `AuditLogController`, `PenaltyWaiverController`
- **Backend Routes**:
  - `GET|POST /api/spot-checks`, `PATCH /api/spot-checks/{id}/status`
  - `GET /api/pengawas/anomalies`, `GET /api/pengawas/petugas-locations`
  - `POST /api/pengawas/enforcements`, `POST /api/pengawas/penindakan/issue-skpdkb`
  - `GET|POST /api/petugas-tasks`, `PUT /api/petugas-tasks/{id}`
  - `GET|POST /api/amnesty` (Penghapusan Denda)
- **Views & Components (`React/Vite`)**:
  - **Admin**: `SpotCheckList.tsx`, `SpotCheckForm.tsx`, `PengawasDashboard.tsx`, `Enforcement.tsx`, `PengawasMaps.tsx`, `Amnesty.tsx`, `AuditLogs.tsx`, `FieldForce.tsx`
  - **Petugas**: `FieldInspection.tsx`, `DaftarTugas.tsx`
  - **Mobile**: `Pengaduan.tsx`

---

## FASE 7: PBB, BPN H2H, BPHTB & Integrasi TTE Dokumen
**Fokus Pengujian:** Tarik data PBB dari Server Bapenda, sinkronisasi NOP, Pelayanan Notaris (BPHTB), dan Pembuatan serta Pemindaian (Scan QR) Tanda Tangan Elektronik dokumen SKPD/SKRD.

- **Models (`retribusi-api`)**:
  - `PbbNopApplication`, `TaxpayerPbbObject`, `TransactionPbb`, `BpnH2hMapping`, `BphtbSubmission`, `SignedDocument`
- **Controllers (`retribusi-api`)**:
  - `PbbBapendaController`, `PbbClassificationController`, `EregistryController`, `DocumentController`
- **Backend Routes**:
  - `POST /api/pbb/bapenda/inquiry`, `POST /api/pbb/bapenda/pay`, `POST /api/pbb/bapenda/link-nop`
  - `POST /api/tte/sign`, `GET /api/tte/verify/{number}`, `GET /api/tte/documents`
  - `GET /api/documents/spp/{noticeId}`, `GET /api/documents/skpdkbt/{billId}`
- **Views & Components (`React/Vite`)**:
  - **Admin**: `PbbManagement.tsx`, `ERegistry.tsx`
  - **Petugas**: `PbbBapenda.tsx`
  - **Mobile**: `PbbTagihan.tsx`, `PbbCalculator.tsx`

---

## FASE 8: Analytics, Dashboard & Pelaporan BPK/SIPD
**Fokus Pengujian:** Pengecekan kalkulasi data agregrasi pendapatan bulanan, output PDF laporan, dan Peta Heatmap Potensi.

- **Models (`retribusi-api`)**:
  - `TaxEducation`
- **Controllers (`retribusi-api`)**:
  - `DashboardController`, `AnalyticsController`, `ReportController`, `DocumentationController`
- **Backend Routes**:
  - `GET /api/dashboard/stats`, `GET /api/dashboard/revenue-trend`
  - `GET /api/analytics/realization`, `GET /api/analytics/heatmap`
  - `GET /api/reports/summary`, `GET /api/reports/bpk`, `GET /api/reports/sipd`
  - `GET /api/reports/petugas-performance`
- **Views & Components (`React/Vite`)**:
  - **Admin**: `Dashboard.tsx`, `Reporting.tsx`, `ObjectAchievement.tsx`, `ClassificationAchievement.tsx`, `SupervisorMap.tsx`
  - **Petugas**: `Dashboard.tsx`, `Reporting.tsx`, `PetaLapangan.tsx`
