---
name: Reporting & Billing Lifecycle
description: Standarisasi alur hulu-ke-hilir dari Pelaporan Mandiri (SPTPD), Verifikasi Admin, hingga Penerbitan Tagihan (Billing).
---

# 🔄 Reporting & Billing Lifecycle

Skill ini adalah jantung dari operasional MPAD, mengelola transisi dari laporan warga menjadi ketetapan pajak.

## 📝 Fase 1: Pelaporan (Citizen Reporting)
- **Modul**: `MonthlyReportController` (Backend) & `SptpdReporting` (Mobile).
- **Proses**: Citizen mengirimkan bukti transaksi bulanan melalui `POST /api/citizen/reports`.
- **Kritikal**: Validasi metadata (luas, jumlah kamar, omzet) harus dilakukan di level frontend sebelum dikirim ke API.

## 🔎 Fase 2: Verifikasi & Validasi (Admin)
- **Modul**: `VerificationController` & `MonthlyReportController::validateReport`.
- **Alur**: 
  1. Admin memeriksa lampiran foto bukti.
  2. Gunakan `PUT /api/reports/monthly/{report}/validate` untuk menyetujui atau menolak laporan.
  3. Jika disetujui, sistem secara otomatis menyiapkan basis data untuk penerbitan SKRD/Billing.

## 💳 Fase 3: Penetapan & Billing
- **Proses**: Penerbitan tagihan secara massal atau individu melalui `POST /api/bills`.
- **Relasi**: Setiap `Bill` harus tertaut ke `MonthlyReport` atau `TaxObject`.
- **Status Tagihan**:
  - `pending`: Belum dibayar.
  - `unpaid`: Melewati jatuh tempo.
  - `paid`: Lunas.

## 📑 Fase 4: Dokumen Resmi
Output dari siklus ini adalah dokumen yang dapat diunduh:
1. **SKRD**: Surat Ketetapan Retribusi Daerah.
2. **SSPD**: Surat Setoran Pajak Daerah (setelah lunas).
3. **SPPT**: Untuk PBB.

## 🤝 Fase 5: Rekonsiliasi & Settlement (Cash Orchestration)
Fase ini memastikan uang tunai yang dipungut Petugas di lapangan benar-benar sampai ke kas negara melalui Admin.
- **Workflow**:
  1. **Pending**: Setiap pembayaran tunai (`cash`) oleh Petugas otomatis berstatus `metadata->settlement_status = 'pending'`.
  2. **Setoran**: Petugas menyetorkan uang fisik ke Admin Bapenda.
  3. **Approval**: Admin melakukan rekonsiliasi via Dashboard Pelaporan (Admin) menggunakan tombol "Settle".
  4. **Settled**: Status berubah menjadi `settled`, dan jumlah `pending_settlement` di Dashboard Petugas berkurang.
- **Kritikal**: Selalu pantau `GET /api/reports/petugas-performance` untuk melihat akumulasi dana yang belum disetorkan per petugas.

## 🛡️ Aturan Keamanan & Integritas
- **Immutability**: Tagihan yang sudah berstatus `paid` tidak boleh diubah nilainya kecuali melalui proses `Reversal` (khusus PBB) atau oleh Super Admin dengan log audit yang ketat.
- **Double Entry**: Pastikan setiap pembayaran (`Payment`) terekam ke dalam tabel ledger virtual untuk rekonsiliasi keuangan.
