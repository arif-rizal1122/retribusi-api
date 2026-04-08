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
