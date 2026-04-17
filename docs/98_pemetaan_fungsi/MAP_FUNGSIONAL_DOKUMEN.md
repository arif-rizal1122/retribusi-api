# 🗺️ Pemetaan Fungsional Dokumentasi M-PAD

Dokumen ini memetakan seluruh aset dokumentasi (.md) di ekosistem M-PAD berdasarkan fungsi utamanya untuk memudahkan navigasi bagi tim pengembang dan pemangku kepentingan.

---

## ⚖️ 1. Regulasi & Dasar Hukum
Dokumen yang mendasari aturan bisnis perpajakan dan retribusi di Kota Baubau.
- `/docs/01_regulasi_baubau/BAUBAU_REGULATORY_MASTER.md`
- `/docs/01_regulasi_baubau/regulasi/03-perwali-pdrd-summary.md`
- `/docs/01_regulasi_baubau/regulasi/02-master-data-objek-pajak.md`
- `/DATA PAJAK/Perwali_58_2024_Full_Text.md`
- `/docs/01_regulasi_baubau/summary-changes-2026.md`

## 🏛️ 2. Arsitektur & Database
Dokumentasi mengenai desain sistem, skema database, dan teknis backend.
- `/docs/02_arsitektur_database/DOMAIN_SCHEMA.md`
- `/docs/02_arsitektur_database/04-database-schema.md`
- `/docs/02_arsitektur_database/database_modernization_comparison.md`
- `/docs/02_arsitektur_database/reference_technical/billing_system_documentation.md`
- `/docs/02_arsitektur_database/reference_technical/penalty_scheme.md`

## 💸 3. Proses Bisnis & Alur Penagihan
Logika inti mengenai cara kerja invoicing (JIT), payment gateway, dan pelayanan lapangan.
- `/docs/03_proses_bisnis_penagihan/INVOICING_WORKFLOW.md`
- `/docs/03_proses_bisnis_penagihan/DOKUMEN_PERENCANAAN_BANK_H2H.md` (Integrasi Bank Mandiri)
- `/docs/03_proses_bisnis_penagihan/INVOICE_GAP_ANALYSIS.md`
- `/docs/03_proses_bisnis_penagihan/skema-pelacakan-lokasi-petugas.md`
- `/docs/09_sistem_logic_study/ANALISIS_LOGIKA_MPAD.md`

## ⚙️ 4. Infrastruktur, API & Deployment
Konfigurasi server, rute API, dan panduan deployment.
- `/docs/04_infrastruktur_api/INFRASTRUCTURE_MAP.md`
- `/docs/04_infrastruktur_api/CREDENTIALS_GUIDE.md`
- `/docs/04_infrastruktur_api/DEPLOYMENT_SCRIPTS_README.md`
- `/docs/04_infrastruktur_api/routes-and-components.md`

## 📦 5. Modul-modul Aplikasi
Dokumentasi spesifik untuk masing-masing platform dalam ekosistem.
- **Admin**: `/retribusi-admin/docs/SYSTEM_OVERVIEW.md`, `/retribusi-admin/docs/STABLE_BASELINE.md`
- **Petugas**: `/docs/05_modul_aplikasi/petugas-SYSTEM_OVERVIEW.md`, `/docs/05_modul_aplikasi/petugas-userguide.md`
- **Mobile (WP)**: `/docs/05_modul_aplikasi/mobile-e-retribusi.md`, `/docs/05_modul_aplikasi/mobile-SYSTEM_OVERVIEW.md`

## 🧪 6. Quality Assurance & Hasil Testing
Laporan pengujian keamanan, fungsionalitas, dan standar kualitas.
- `/docs/07_testing_kualitas/TESTING_GUIDE.md`
- `/docs/07_testing_kualitas/testing-reports/FINAL_TEST_SUMMARY.md`
- `/docs/07_testing_kualitas/testing-reports/VULNERABILITY_ANALYSIS.md`
- `/testing/results/Mitigation_Registry.md`
- `/docs/07_testing_kualitas/testing-reports/08_Laporan_E2E_Lintas_Peran.md`

## 📂 7. Migrasi & Legacy Systems
Arsip data dan strategi transisi dari sistem lama (9pajak).
- `/docs/08_arsip_migrasi_9pajak/MIGRATION_DETAILS_9PAJAK.md`
- `/docs/08_legacy_systems/MEGA_DOCUMENTATION_9PAJAK.md`
- `/docs/08_arsip_migrasi_9pajak/migration_strategy.md`

## 🤖 8. Agentic AI & Standar Operasional (.agent)
Logika kecerdasan buatan, skill, dan aturan otomatisasi yang digunakan oleh asisten AI.
- `/.agent/Skills/omni_deployment_sync/SKILL.md`
- `/.agent/Skills/pbb_specialist/SKILL.md`
- `/.agent/Skills/master_calculation/SKILL.md`
- `/.agent/SOUL.md`

## 🌐 9. Umum & Manajemen Proyek
Dokumen root, log perubahan, dan gambaran umum sistem.
- `/README.md`
- `/docs/99_umum_system/MEGA_DOCUMENTATION.md`
- `/docs/99_umum_system/CHANGE_LOG.md`
- `/docs/99_umum_system/cek_saat_launching.md`

---
*Mapping ini diperbarui secara otomatis pada April 2026.*
