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
