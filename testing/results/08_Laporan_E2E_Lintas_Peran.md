# 🔄 Laporan Hasil Uji Coba Lintas Peran (E2E) Untuk Semua Jenis Pajak

**Waktu Eksekusi**: 2026-02-26 09:30:08
Pengujian E2E ini dilakukan secara otomatis (tanpa manual UI/Screenshot) dengan menstimulasi *Database Engine* Laravel secara langsung. Skenario yang diuji memastikan keutuhan proses: **Penerbitan SKPD (Admin) -> Integrasi Tagihan (Wajib Pajak) -> Pembayaran Lunas (Petugas)** berurutan.



❌ **FATAL ERROR**: Pengujian Terhenti. Pesan Sistem: SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`retribusi`.`taxpayers`, CONSTRAINT `taxpayers_opd_id_foreign` FOREIGN KEY (`opd_id`) REFERENCES `opds` (`id`) ON DELETE CASCADE) (Connection: mysql, SQL: insert into `taxpayers` (`nik`, `name`, `phone`, `address`, `opd_id`, `updated_at`, `created_at`) values (3201999999999999, Wajib Pajak Automasi, 0899999999, Jl. Test, 1, 2026-02-26 09:30:08, 2026-02-26 09:30:08))