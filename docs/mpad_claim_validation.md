# Validasi Klaim M-PAD: Evaluasi & Skema Pengujian

Dokumen ini memvalidasi setiap klaim dalam deskripsi sistem M-PAD terhadap kode aktual di keempat repository, lalu menyiapkan skema pengujian untuk setiap poin.

---

## Bagian 1: Hasil Evaluasi Klaim

### Legenda Status
| Ikon | Arti |
|------|------|
| ✅ | **Terverifikasi** — Kode implementasi ada dan fungsional |
| ⚠️ | **Parsial/Mock** — Kode ada tapi menggunakan data dummy atau belum terintegrasi penuh |
| ❌ | **Belum Terimplementasi** — Tidak ditemukan kode yang mendukung klaim |

---

### A. Tahap 1: Pendaftaran

| # | Klaim | Status | Bukti Kode |
|---|-------|--------|------------|
| A1 | **Mobile: Registrasi mandiri dari HP** | ✅ | `retribusi-mobile/src/pages/Register.tsx` — form registrasi warga dengan NIK, `ServiceDetail.tsx` — daftar objek pajak mandiri |
| A2 | **Mobile: Integrasi NIK (e-KYC)** | ⚠️ | `IdentityValidationService.php` — ada tapi **mock** (hanya reject NIK ending '999'), belum terkoneksi ke API Dukcapil asli |
| A3 | **Mobile: Upload foto izin usaha** | ✅ | `ServiceDetail.tsx` — form upload file dengan `FormData`, dikirim ke API `citizen/services/{id}/register` |
| A4 | **Petugas: Daftar WP baru + GPS Tagging** | ✅ | `retribusi-petugas/src/pages/TaxpayerManagement.tsx` — Step 1-4 dengan GPS auto-detect & MapPicker |
| A5 | **Admin: Verifikasi dokumen** | ✅ | `retribusi-admin/src/pages/Verification.tsx` — approve/reject, `VerificationController.php` |
| A6 | **Admin: NPWPD auto-generate** | ✅ | `Taxpayer::resolveNpwpd()` & `generateNpwpd()` — format `P.YYYY.XXXXX`, terintegrasi di `TaxpayerController@store` |

### B. Tahap 2: Pendataan

| # | Klaim | Status | Bukti Kode |
|---|-------|--------|------------|
| B1 | **Petugas: Verifikasi lapangan (uji petik)** | ✅ | `retribusi-admin/src/pages/spot-checks/SpotCheckForm.tsx` + `SpotCheckList.tsx`, API endpoint `/api/spot-checks` |
| B2 | **Petugas: Foto visual lokasi** | ✅ | `TaxpayerManagement.tsx` Step 3 — `Camera` upload, `CloudinaryService.php` — upload ke cloud |
| B3 | **Petugas: GPS koordinat** | ✅ | Step 4 map + `getCurrentLocation()` geolocation API + draggable Marker |
| B4 | **Petugas: Metadata dinamis** | ✅ | Step 3 — dynamic `form_schema` rendering (select, text, number, checkbox, textarea, google_map, constant) |
| B5 | **Admin: Heatmap Potensi** | ✅ | `RevenueHeatmap.tsx` + `HeatmapLayer.tsx`, API `/api/analytics/heatmap`, toggle markers/heatmap di Dashboard |
| B6 | **Admin: Overlay Zona Nilai Tanah (ZNT)** | ⚠️ | `HeatmapLayer` ada, tapi **tidak ada layer ZNT terpisah** yang di-overlay. Data peta hanya berbasis revenue heatmap |
| B7 | **Mobile: SPTPD Self-Assessment** | ✅ | `SptpdReporting.tsx` — filter `is_self_assessment`, form "Lapor Omzet", API `citizen/sptpd` |

### C. Tahap 3: Penetapan

| # | Klaim | Status | Bukti Kode |
|---|-------|--------|------------|
| C1 | **Admin: Formula Parser Engine** | ✅ | `FormulaParserService.php` — `calculate()` method, `calculatePenalty()`, digunakan di `BillingService` & `BillController` |
| C2 | **Admin: Perhitungan otomatis berdasarkan Perda** | ✅ | `BillingService::calculateAmountForPeriod()` — lookup `RetributionRate` + `calculation_formula`, variabel dari metadata |
| C3 | **Admin: TTE (Tanda Tangan Elektronik)** | ⚠️ | `TTEService.php` + `OfficialDocumentService::signDocument()` — kode ada dan terintegrasi di billing flow, tapi **mock BSrE** (belum konek API BSRE/DSIGN asli) |
| C4 | **Admin: Dokumen SKPD/SPPT digital** | ✅ | `DocumentController.php` — generate PDF untuk SKRD, SPPT, Surat Teguran. `PdfController.php` — template PDF |
| C5 | **Mobile: Push notification (WA/App)** | ⚠️ | `WhatsAppService.php` ada, digunakan di `GenerateAutomatedTeguran` & `MonthlyReportController`, tapi `SendDueDateNotifications` masih **Log::info mock** |
| C6 | **Mobile: E-SPPT/E-SKPD PDF download** | ✅ | `PdfController.php` — endpoint generate PDF, `DocumentController` — berbagai template dokumen resmi |

### D. Tahap 4: Penagihan

| # | Klaim | Status | Bukti Kode |
|---|-------|--------|------------|
| D1 | **Mobile: QRIS Dinamis** | ⚠️ | `QRISDriver.php` — inquiry, notify, reconcile lengkap. Tapi `qr_string` menggunakan **mock EMVCo string**, belum konek ke QRIS aggregator asli |
| D2 | **Mobile: Virtual Account** | ⚠️ | `PaymentGatewayController.php` — support `va_bca, va_mandiri, va_bri` tapi masih **mock response** |
| D3 | **Mobile: E-Bukti Bayar (SSPD) real-time** | ✅ | `BillingService::settleBill()` — update status + trigger TTE signing otomatis, return receipt data |
| D4 | **Petugas: Billing On-the-spot + QR scan** | ✅ | `PaymentConfirmation.tsx` — konfirmasi bayar dari petugas, `FieldScanner.tsx` — QR scanner |
| D5 | **Petugas: Cetak struk bluetooth** | ⚠️ | Referensi ada di `PaymentVerification.tsx` & `PbbBapenda.tsx` (error handler "Pastikan printer Bluetooth terhubung"), tapi **implementasi print engine** Web Bluetooth API tidak ditemukan secara eksplisit |
| D6 | **Admin: Dashboard real-time** | ✅ | `DashboardController.php` — statistik aggregate, trend, chart. `Dashboard.tsx` admin — card pendapatan, grafik, peta |
| D7 | **Admin: Penalty Engine (2%/bulan)** | ✅ | `CalculateBillPenalties.php` — command otomatis, `FormulaParserService::calculatePenalty()`, scheduler-ready |
| D8 | **Admin: Auto Surat Teguran → Surat Paksa** | ✅ | `GenerateAutomatedTeguran.php` — eskalasi otomatis: Teguran 1 (7 hari) → Teguran 2 (14 hari) → field action, kirim WA |

### E. Tupoksi Platform & Pemotongan Birokrasi

| # | Klaim | Status | Bukti Kode |
|---|-------|--------|------------|
| E1 | **Paperless: E-Document + QR TTE** | ✅ | `TTEService.php` — generate QR verification URL, `PublicVerificationController.php` — publik verifikasi dokumen via QR |
| E2 | **Unified Database (1 akun = semua pajak)** | ✅ | Model `Taxpayer` → many-to-many `retributionTypes` & `retributionClassifications`, NIK sebagai primary link |
| E3 | **Tanpa antrean loket Bank** | ⚠️ | Infrastruktur payment gateway ada (QRIS + Bank H2H), tapi masih **mock** — perlu integrasi bank asli |
| E4 | **Rekonsiliasi H+0** | ⚠️ | `reconcile()` method ada di BankSultra & QRIS driver + endpoint API, tapi belum ada **cron scheduler** yang menjalankan harian secara otomatis |
| E5 | **Amnesty (penghapusan denda)** | ✅ | `AmnestyManagement.tsx` admin — CRUD + approve/reject, API `/api/amnesty` |
| E6 | **Mobile: Amnesty dari warga** | ❌ | Tidak ada halaman amnesty di `retribusi-mobile`. Fitur hanya tersedia dari sisi admin/pengawas |

---

## Bagian 2: Ringkasan Skor

| Kategori | Terverifikasi ✅ | Parsial ⚠️ | Belum Ada ❌ |
|----------|-----------------|-----------|------------|
| **A. Pendaftaran** (6 poin) | 5 | 1 | 0 |
| **B. Pendataan** (7 poin) | 6 | 1 | 0 |
| **C. Penetapan** (6 poin) | 4 | 2 | 0 |
| **D. Penagihan** (8 poin) | 4 | 3 | 0 |  
| **E. Tupoksi & Birokrasi** (6 poin) | 3 | 2 | 1 |
| **TOTAL (33 poin)** | **22 (67%)** | **9 (27%)** | **2 (6%)** |

> [!IMPORTANT]
> **22 dari 33 klaim sepenuhnya terverifikasi.** 9 klaim ada infrastrukturnya tapi menggunakan mock/dummy data (terutama payment gateway dan e-KYC). 2 klaim belum diimplementasikan sama sekali (amnesty mobile & ZNT overlay).

---

## Bagian 3: Skema Pengujian Komprehensif

### Metodologi
Pengujian dilakukan via **API testing** (`curl`/`read_url_content`) terhadap server lokal (`127.0.0.1:8000`) tanpa screenshot browser. Setiap test case memiliki:
- **Prasyarat** (data yang diperlukan)
- **Langkah** (HTTP request yang dieksekusi)
- **Ekspektasi** (response yang diharapkan)
- **Klaim yang diuji** (mapping ke kode klaim di atas)

---

### TC-A: Tahap Pendaftaran

#### TC-A1: Registrasi Warga (Mobile) → Klaim A1, A2
```
PRASYARAT: Server API berjalan
LANGKAH:
  1. POST /api/citizen/register
     Body: { nik: "7404012309900001", name: "Test Warga", phone: "08123456789", password: "password123" }
  2. POST /api/citizen/register  
     Body: { nik: "7404012309900999", ... }  ← NIK ending '999' (Dukcapil mock reject)
EKSPEKTASI:
  1. → 201 Created, response berisi token & user data
  2. → 422 Validation Error, message "NIK tidak terdaftar di sistem Dukcapil"
```

#### TC-A2: Pendaftaran WP oleh Petugas + GPS + Auto NPWPD → Klaim A4, A6
```
PRASYARAT: Login sebagai petugas, memiliki opd_id
LANGKAH:
  1. POST /api/taxpayers
     Body: { nik: "7404010101010001", name: "WP Baru", address: "Jl. Soekarno", 
             retribution_type_ids: [1], latitude: -5.463, longitude: 122.607 }
     (tanpa npwpd)
EKSPEKTASI:
  → 201 Created
  → response.data.npwpd MATCH format "P.2026.XXXXX"
  → response.data.latitude = -5.463
```

#### TC-A3: NIK Detection (WP sudah ada) → Klaim A6
```
PRASYARAT: WP dari TC-A2 sudah terdaftar
LANGKAH:
  1. POST /api/taxpayers
     Body: { nik: "7404010101010001", name: "WP Baru Objek 2", retribution_type_ids: [2] }
EKSPEKTASI:
  → 200 OK (update existing)
  → npwpd SAMA dengan TC-A2 (detected by NIK)
```

#### TC-A4: Verifikasi Dokumen oleh Admin → Klaim A5
```
PRASYARAT: Login sebagai admin/opd, ada verification pending
LANGKAH:
  1. GET /api/verifications?status=pending
  2. PUT /api/verifications/{id}/approve
     Body: { notes: "Dokumen lengkap dan valid" }
EKSPEKTASI:
  1. → 200 OK, list verifications with status "pending"
  2. → 200 OK, status berubah menjadi "approved"
```

---

### TC-B: Tahap Pendataan

#### TC-B1: Uji Petik (Spot Check) → Klaim B1
```
PRASYARAT: Login sebagai pengawas
LANGKAH:
  1. POST /api/spot-checks
     Body: { taxpayer_id: 1, tax_object_id: 1, findings: "Sesuai data", result: "compliant" }
  2. GET /api/spot-checks
EKSPEKTASI:
  1. → 201 Created
  2. → 200 OK, list spot checks termasuk data baru
```

#### TC-B2: Upload Foto & Metadata Dinamis → Klaim B2, B3, B4
```
PRASYARAT: Login sebagai petugas
LANGKAH:
  1. POST /api/taxpayers/{id} (multipart/form-data)
     Body: { foto_lokasi_open_kamera: [file.jpg], 
             metadata: { "jumlah_kursi": 20, "luas_m2": 50 },
             latitude: -5.462, longitude: 122.608 }
EKSPEKTASI:
  → 200 OK
  → metadata.foto_lokasi_open_kamera = Cloudinary URL
  → metadata.jumlah_kursi = 20
  → latitude/longitude updated
```

#### TC-B3: Heatmap Analytics → Klaim B5
```
PRASYARAT: Login sebagai admin
LANGKAH:
  1. GET /api/analytics/heatmap
EKSPEKTASI:
  → 200 OK
  → Array of { latitude, longitude, intensity/revenue }
```

#### TC-B4: SPTPD Self-Assessment (Mobile) → Klaim B7
```
PRASYARAT: Login sebagai warga (citizen), punya objek is_self_assessment
LANGKAH:
  1. GET /api/citizen/tax-objects  → filter is_self_assessment
  2. POST /api/citizen/sptpd
     Body: { tax_object_id: X, period: "2026-04", revenue: 50000000 }
EKSPEKTASI:
  1. → 200 OK, objek dengan is_self_assessment = true
  2. → 201 Created, SPTPD record dengan status "submitted"
```

---

### TC-C: Tahap Penetapan

#### TC-C1: Formula Parser → Auto Perhitungan Nominal → Klaim C1, C2
```
PRASYARAT: Klasifikasi retribusi dengan calculation_formula (misal: "omzet * 0.10")
LANGKAH:
  1. POST /api/bills/generate
     Body: { tax_object_id: X, period: "2026-04" }
EKSPEKTASI:
  → 201 Created
  → amount dihitung otomatis dari formula (bukan manual input)
  → bill_number tergenerate
```

#### TC-C2: TTE Signing → Klaim C3
```
PRASYARAT: Bill sudah ada
LANGKAH:
  1. POST /api/bills/sign-tte
     Body: { bill_id: X, notes: "Penetapan resmi" }
EKSPEKTASI:
  → 200 OK
  → response berisi signed_at, doc_number, verification_url, qr_code
```

#### TC-C3: Generate PDF Dokumen → Klaim C4, C6
```
PRASYARAT: Bill & Taxpayer exists
LANGKAH:
  1. GET /api/documents/skrd/{bill_id}
  2. GET /api/public/pdf/npwpd/{taxpayer_id}
EKSPEKTASI:
  1. → 200 OK, Content-Type: application/pdf
  2. → 200 OK, Content-Type: application/pdf
```

---

### TC-D: Tahap Penagihan

#### TC-D1: QRIS Payment Flow → Klaim D1
```
PRASYARAT: Bill unpaid
LANGKAH:
  1. POST /api/payment/generate
     Body: { bill_id: X, method: "qris" }
  2. POST /api/v1/bank/h2h/notify (mock callback)
     Body: { bill_number: "INV-...", amount: ..., bank_code: "QRIS", trx_id: "TRX123" }
EKSPEKTASI:
  1. → 200 OK, response berisi qr_string, qr_image_url, expiry
  2. → 200 OK, bill status → "paid", NTPD generated
```

#### TC-D2: Virtual Account Payment → Klaim D2
```
PRASYARAT: Bill unpaid
LANGKAH:
  1. POST /api/payment/generate
     Body: { bill_id: X, method: "va_bri" }
EKSPEKTASI:
  → 200 OK, response berisi va_number, expiry_date
```

#### TC-D3: Bank H2H Settlement + Auto TTE → Klaim D3
```
PRASYARAT: Bill unpaid
LANGKAH:
  1. POST /api/v1/bank/h2h/notify
     Body: { bill_number: "INV-...", amount: ..., bank_code: "SULTRA", trx_id: "H2H-001" }
EKSPEKTASI:
  → 200 OK
  → Bill status = "paid"
  → payment_method = "h2h"
  → ntpd NOT NULL
  → TTE auto-signed (official_documents record created)
```

#### TC-D4: Petugas Billing On-the-spot → Klaim D4
```
PRASYARAT: Login sebagai petugas
LANGKAH:
  1. POST /api/payments
     Body: { bill_id: X, payment_method: "cash", amount: 100000, notes: "Bayar tunai di lokasi" }
EKSPEKTASI:
  → 201 Created, payment recorded, bill status updated
```

#### TC-D5: Penalty Engine (Denda Otomatis 2%/bulan) → Klaim D7
```
PRASYARAT: Bill dengan due_date 2 bulan lalu, status unpaid
LANGKAH:
  1. php artisan billing:calculate-penalties
  2. GET /api/bills/{id}
EKSPEKTASI:
  → penalty_amount > 0
  → penalty_amount ≈ (amount * 0.02 * 2)  [2% x 2 bulan]
```

#### TC-D6: Auto Surat Teguran Eskalasi → Klaim D8
```
PRASYARAT: Bill overdue > 7 hari, belum ada enforcement notice
LANGKAH:
  1. php artisan enforcements:generate-drafts
  2. GET /api/enforcement-notices?bill_id=X
EKSPEKTASI:
  → EnforcementNotice record created
  → type = "teguran_1"
  → status = "draft"
  → number format "TEG1-..."
```

---

### TC-E: Cross-Platform & Birokrasi

#### TC-E1: Paperless → QR Verification → Klaim E1
```
LANGKAH:
  1. GET /api/verify/{document_number}
EKSPEKTASI:
  → 200 OK, document details dengan signer info, signed_at, QR verification
```

#### TC-E2: Unified Database → 1 NIK Multi Pajak → Klaim E2
```
PRASYARAT: Taxpayer dengan 2+ retribution types
LANGKAH:
  1. GET /api/taxpayers/{id}?include=retributionTypes,retributionClassifications
EKSPEKTASI:
  → 200 OK
  → retribution_types count ≥ 2
  → Semua menggunakan NIK yang sama
```

#### TC-E3: Amnesty Management → Klaim E5
```
PRASYARAT: Login sebagai pengawas/kabid
LANGKAH:
  1. POST /api/amnesty
     Body: { bill_id: X, reason: "Program amnesty 2026", waive_percentage: 100 }
  2. POST /api/amnesty/{id}/approve
     Body: { notes: "Disetujui Kabid" }
  3. GET /api/bills/{bill_id}
EKSPEKTASI:
  1. → 201 Created, amnesty request
  2. → 200 OK, status "approved"
  3. → waived_penalty_amount > 0, effective penalty reduced
```

#### TC-E4: Reconciliation → Klaim E4
```
PRASYARAT: Ada transaksi yang sudah dibayar
LANGKAH:
  1. POST /api/v1/bank/h2h/reconcile
     Body: { driver: "bank_sultra", transactions: [...] }
EKSPEKTASI:
  → 200 OK, reconciliation summary (matched, unmatched counts)
```

---

## Bagian 4: Prioritas Perbaikan

Berdasarkan evaluasi, berikut item yang perlu ditindaklanjuti agar klaim 100% valid:

### Prioritas Tinggi (Klaim utama yang masih mock)
| # | Item | Status | Aksi yang Diperlukan |
|---|------|--------|---------------------|
| 1 | e-KYC Dukcapil | ⚠️ Mock | Integrasi API Dukcapil asli atau SIAK Daerah |
| 2 | QRIS Aggregator | ⚠️ Mock | Integrasi ke QRIS aggregator (Bank Sultra/Netzme) |
| 3 | Virtual Account Bank | ⚠️ Mock | Integrasi H2H ke BRI/Mandiri/BCA |
| 4 | TTE BSrE | ⚠️ Mock | Integrasi API BSrE Kemkominfo |

### Prioritas Sedang (Fitur klaim tapi belum lengkap)
| # | Item | Status | Aksi yang Diperlukan |
|---|------|--------|---------------------|
| 5 | Amnesty dari Mobile | ❌ | Buat halaman amnesty di `retribusi-mobile` |
| 6 | ZNT Overlay di Peta | ⚠️ | Tambahkan layer GeoJSON ZNT dari BPN di peta admin |
| 7 | Bluetooth Print Engine | ⚠️ | Implementasi Web Bluetooth API untuk thermal printer |
| 8 | WA Notification Due Date | ⚠️ | Ubah dari `Log::info` mock ke `WhatsAppService::send()` yang sudah ada |
| 9 | Cron Reconciliation | ⚠️ | Tambahkan scheduler di `Kernel.php` untuk reconcile harian |

### Prioritas Rendah (Nice to have)
| # | Item | Aksi |
|---|------|------|
| 10 | Push Notification Firebase | Integrasi FCM untuk mobile app |
| 11 | E-Wallet (GoPay, OVO) | Tambahkan driver payment selain QRIS |
