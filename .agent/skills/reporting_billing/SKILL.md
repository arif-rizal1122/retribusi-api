---
name: Reporting & Billing Lifecycle
description: Standarisasi alur hulu-ke-hilir dari Pelaporan Mandiri (SPTPD), Verifikasi Admin, hingga Penerbitan Tagihan (Billing).
---

# 🔄 Reporting & Billing Lifecycle

Skill ini adalah jantung dari operasional MPAD, mengelola transisi dari laporan warga menjadi ketetapan pajak.

## 📝 Fase 1: Pendaftaran & Pelaporan
- **Pendaftaran**: Menggunakan **SPOPD** (Usaha) atau **SPOP/LSPOP** (PBB) untuk mendapatkan **SKT**.
- **Pelaporan**: Wajib Pajak Self-Assessment (Resto/Hotel) mengirimkan **SPTPD** (Laporan Mandiri) bulanan melalui `MonthlyReportController`.
- **Kritikal**: Validasi omzet dan metadata harus dilakukan sebelum status laporan berlanjut ke tahap penetapan.

## 🔎 Fase 2: Verifikasi & Penetapan (Assessment)
- **Proses**: Admin memverifikasi SPTPD atau melakukan penetapan jabatan (Official Assessment).
- **Output**: Penerbitan **SKPD** (Pajak), **SKRD** (Retribusi), atau **SPPT** (PBB) sebagai dokumen tagihan resmi.
- **Kurang Bayar**: Jika ada selisih audit, diterbitkan **SKPDKB**.

## 💳 Fase 3: Penetapan & Billing
- **Proses**: Penerbitan tagihan secara massal atau individu melalui `POST /api/bills`.
- **Relasi**: Setiap `Bill` harus tertaut ke `MonthlyReport` atau `TaxObject`.
- **Status Tagihan**:
  - `pending`: Belum dibayar.
  - `unpaid`: Melewati jatuh tempo.
  - `paid`: Lunas.

## 📑 Fase 4: Pembayaran & Bukti Sah
- **Bukti Bayar**: Setelah pelunasan di Bank atau melalui Petugas, sistem menerbitkan **SSPD** (Surat Setoran Pajak Daerah) atau **SSRD**.
- **Dokumen Penagihan Aktif**: Jika menunggak, sistem menerbitkan **STPD**, **Surat Teguran**, hingga **SPMP** (Surat Paksa).

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
