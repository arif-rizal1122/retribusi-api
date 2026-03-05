# Consolidated Todolist & Audit Dokumen BAPENDA
**Terakhir diperbarui**: 5 Maret 2026

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

## � 7. Skema Pembayaran & Verifikasi Digital
**Repo Terkait**: `retribusi-api`, `retribusi-mobile`, `retribusi-petugas`

### Alur 1: Pembayaran Mandiri (Citizen Claim)
1. **Mobile**: Warga membayar via Transfer/VA/QRIS → Unggah bukti bayar → `POST /api/payments`.
2. **API**: Mencatat pembayaran dengan `status = pending`. Bill tetap `pending`.
3. **Petugas**: Melihat notifikasi/antrean verifikasi → Review bukti bayar.
4. **Petugas**: Klik "Setujui" → API panggil `PUT /api/payments/{id}/status` (success).
5. **API**: Otomatis update `bills.status = lunas`.

### Alur 2: Pembayaran Lapangan (QR Discan Petugas)
1. **Mobile**: Warga menunjukkan QR khusus (berisi Bill IDs).
2. **Petugas**: Scan QR warga → API fetch data tagihan terkait.
3. **Petugas**: Terima uang tunai → Klik "Bayar Tunai" → `POST /api/payments`.
4. **API**: Karena diinput petugas, status langsung `success` & bill `lunas`.
5. **Petugas**: Cetak resi via Thermal Printer.

### Status Pembayaran (Payments Table)
- `pending`: Menunggu verifikasi petugas (khusus input dari warga).
- `success`: Pembayaran valid & tagihan lunas.
- `failed`: Bukti bayar ditolak petugas.

---

## �📄 Audit Dokumen Resmi BAPENDA

### Tahap 1: Pendaftaran

| Dokumen | Endpoint | Status |
|---------|----------|--------|
| **SPOPD** | `TaxpayerController::store` | ✅ Ada |
| **NPWPD** | Auto-generate saat create taxpayer | ✅ Ada |
| **SKT** | `GET /api/documents/skt/{id}` → `generateSKT()` | ✅ Baru |
| **SPOP/LSPOP** | `PbbBapendaController` (import from Bapenda) | ⚠️ Partial |

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
