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

## ✅ 6. Hybrid Dynamic Billing
**File**: `BillingService::getPendingPeriods()`

**Cara Kerja**: Loop periode registrasi → sekarang, hitung virtual bill + denda JIT per periode belum bayar

---

## ✅ 7. Skema Pembayaran & Verifikasi Digital
**Repository**: `retribusi-api`, `retribusi-mobile`, `retribusi-petugas`

**Status**:
- [x] **Alur 1 (Citizen Claim):** `POST /api/payments` (pending) & unggah bukti bayar di mobile.
- [x] **Alur 1 (Verification):** Dashboard Petugas (`PaymentVerification.tsx`) panggil `PUT /api/payments/{id}/status`.
- [x] **Alur 2 (Field Payment):** Scan QR warga di `FieldScanner.tsx` petugas → Bayar Tunai → Langsung `success`.
- [x] **Thermal Print:** Implementasi `ThermalPrintService.ts` di aplikasi petugas untuk cetak struk SSRD.
- [x] **Otomasi Bill:** Status tagihan (`bills.status`) otomatis berubah jadi `lunas` saat payment berhasil.

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
- [x] **Navigation:** Integrasi menu "Pengaduan" di Sidebar Layout Admin/Pengawas.
