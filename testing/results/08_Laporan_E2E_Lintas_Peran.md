# 🔄 Laporan Hasil Uji Coba Lintas Peran (E2E) Untuk Semua Jenis Pajak

**Waktu Eksekusi**: 2026-02-24 14:38:47
Pengujian E2E ini dilakukan secara otomatis (tanpa manual UI/Screenshot) dengan menstimulasi *Database Engine* Laravel secara langsung. Skenario yang diuji memastikan keutuhan proses: **Penerbitan SKPD (Admin) -> Integrasi Tagihan (Wajib Pajak) -> Pembayaran Lunas (Petugas)** berurutan.

### 🗂️ Pengujian Objek: PBB-P2 (`PBB-UMUM`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**


❌ **FATAL ERROR**: Pengujian Terhenti. Pesan Sistem: SQLSTATE[HY000]: General error: 1364 Field 'retribution_type_id' doesn't have a default value (Connection: mysql, SQL: insert into `tax_objects` (`opd_id`, `taxpayer_id`, `name`, `address`, `latitude`, `longitude`, `updated_at`, `created_at`) values (4, 14, Usaha Dummy Cepat PBB-P2, Jl. Automasi Mesin No.99, -5.485434, 122.585572, 2026-02-24 14:38:47, 2026-02-24 14:38:47))