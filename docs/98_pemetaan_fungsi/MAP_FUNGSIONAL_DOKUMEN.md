# 🗺️ Pemetaan Fungsional Dokumentasi M-PAD

Dokumen ini memetakan seluruh aset dokumentasi di ekosistem M-PAD berdasarkan fungsi utamanya dengan prinsip **Piramida SSOT (Single Source of Truth)** untuk navigasi cepat dan pencegahan redundansi informasi.

---

## 🧭 0. Kebijakan Strategis & Transformasi Regional (Sultra Connect)
Arahan pimpinan, matriks akses RBAC, dan cetak biru perluasan multi-daerah tingkat provinsi:
- `/docs/00_kebijakan_strategis/USER_ACCESS_MATRIX.md` (Matriks peran & hak akses pengelola, pengawas, WP, perbankan, dan instansi vertikal)
- `/docs/00_kebijakan_strategis/ROADMAP_SULTRA_CONNECT.md` (Visi transformasi Sultra Connect, benchmark nasional Sapawarga/DKI/Simpada/Inaportnet)
- `/docs/00_kebijakan_strategis/SKEMA_KALKULATOR_BISNIS_DAN_AFT.md` (Inovasi kalkulator bisnis & split-payment AFT pengganti Tapping Box)
- `/docs/00_kebijakan_strategis/MASTER_STRATEGY_MPAD_BAUBAU.md` (Strategi implementasi bertahap Kota Baubau)
- `/docs/00_kebijakan_strategis/Dokumen_Perencanaan_Sistem_Pendapatan_Baubau.md` (Rencana induk digitalisasi pendapatan)
- `/docs/00_kebijakan_strategis/legal/Draft_Surat_Kuasa_Debet_AFT.md` (Draf legal persetujuan debet rekening bank)

## ⚖️ 1. Regulasi & Dasar Hukum
Dasar hukum resmi perpajakan dan retribusi daerah (UU HKPD, Perda 1/2024, Perwali Baubau, MCP KPK):
- `/docs/01_regulasi_baubau/BAUBAU_REGULATORY_MASTER.md` (Master regulasi pajak & retribusi Baubau)
- `/docs/01_regulasi_baubau/MCP_KPK_INTEGRASI_BPN_DUKCAPIL.md` (Pemenuhan indikator MCP KPK pencegahan korupsi)
- `/docs/01_regulasi_baubau/regulasi/03-perwali-pdrd-summary.md`
- `/docs/01_regulasi_baubau/regulasi/02-master-data-objek-pajak.md`
- `/docs/01_regulasi_baubau/summary-changes-2026.md`
- `/docs/01_regulasi_baubau/penalty_scheme.md` (Skema sanksi & denda keterlambatan)
- `/docs/01_regulasi_baubau/07-status-dokumen-resmi.md` (Status legalitas dokumen resmi Bapenda)
- `/docs/01_regulasi_baubau/surveillance_tte.md` (Pemberlakuan TTE pengawasan)

## 🏛️ 2. Arsitektur & Database
Desain skema basis data, kamus data, dan modernisasi relasi:
- `/docs/02_arsitektur_database/DOMAIN_SCHEMA.md`
- `/docs/02_arsitektur_database/04-database-schema.md`
- `/docs/02_arsitektur_database/mpad_schema.dbml`
- `/docs/02_arsitektur_database/database_modernization_comparison.md`
- `/docs/02_arsitektur_database/reference_technical/billing_system_documentation.md`

## 💸 3. Proses Bisnis & Alur Penagihan
Alur penerbitan invoice, verifikasi, BRD, dan alur pelacakan petugas:
- `/docs/03_proses_bisnis_penagihan/INVOICING_WORKFLOW.md`
- `/docs/03_proses_bisnis_penagihan/BRD_H2H_BPN_DUKCAPIL.md` (Business Requirement H2H BPN BHUMI & Dukcapil)
- `/docs/03_proses_bisnis_penagihan/DOKUMEN_PERENCANAAN_BANK_H2H.md`
- `/docs/03_proses_bisnis_penagihan/TECH_SPEC_BANK_H2H_MASTER.md`
- `/docs/03_proses_bisnis_penagihan/INVOICE_GAP_ANALYSIS.md`
- `/docs/03_proses_bisnis_penagihan/skema-pelacakan-lokasi-petugas.md`

## ⚙️ 4. Infrastruktur & API
Routing, arsitektur server, API specifications, dan SRS:
- `/docs/04_infrastruktur_api/SRS_H2H_BPN_DUKCAPIL.md` (Software Requirement Specification H2H)
- `/docs/04_infrastruktur_api/routes-and-components.md`
- `/docs/04_infrastruktur_api/core_apis.md` & `public_apis.md`
- `/docs/04_infrastruktur_api/CREDENTIALS_GUIDE.md`
- `/docs/04_infrastruktur_api/INFRASTRUCTURE_NOTES.md`
- `/docs/04_infrastruktur_api/DEPLOYMENT_SCRIPTS_README.md`

## 📦 5. Modul-Modul Aplikasi
Dokumentasi teknis spesifik per jenis pajak/retribusi daerah:
- PBB-P2 & Pemetaan: `/docs/05_modul_aplikasi/pbb_p2.md`, `/docs/05_modul_aplikasi/spopd/`
- BPHTB & Notaris/PPAT: `/docs/05_modul_aplikasi/bphtb.md`, `/docs/05_modul_aplikasi/implementasi_onboarding_ppat.md`
- Pajak Reklame: `/docs/05_modul_aplikasi/reklame_workflow.md`
- PBJT: `/docs/05_modul_aplikasi/pbjt_makanan_minuman.md`, `pbjt_perhotelan.md`, `pbjt_hiburan.md`
- Retribusi Jasa Umum/Usaha: `/docs/05_modul_aplikasi/retribusi_pasar.md`, `retribusi_persampahan.md`, `sewa_tanah_bangunan.md`, `sewa_alat_berat.md`
- Petugas & Mobile: `/docs/05_modul_aplikasi/petugas-SYSTEM_OVERVIEW.md`, `mobile-SYSTEM_OVERVIEW.md`

## 📖 6. Panduan Pengguna (User Manuals)
SOP operasional pengguna lapangan dan pengelola internal:
- `/docs/06_panduan_pengguna/MANUAL_BOOK_MPAD.md`
- `/docs/06_panduan_pengguna/admin-userguide.md`
- `/docs/06_panduan_pengguna/UI_INTEGRATION_STANDARDS.md`
- `/docs/06_panduan_pengguna/MANUAL_BOOK_SCREENSHOT_PLAN.md`

## 🧪 7. Quality Assurance & Testing
Checklist pengujian, skema testing E2E, integrasi dan audit keamanan:
- `/docs/07_testing_kualitas/TESTING_GUIDE.md`
- `/docs/07_testing_kualitas/PRODUCTION_READY_CHECKLIST.md`
- `/docs/07_testing_kualitas/OMNI_SYNC_PIPELINE.md`
- `/docs/07_testing_kualitas/testing_architecture_map.md`
- `/docs/07_testing_kualitas/mpad_claim_validation.md`

## 📂 8. Migrasi & Legacy Systems (9Pajak)
Seluruh data warisan, pemetaan tabel 9pajak, dan riwayat migrasi terpusat di satu folder:
- `/docs/08_legacy_systems/MEGA_DOCUMENTATION_9PAJAK.md`
- `/docs/08_legacy_systems/MIGRATION_DETAILS_9PAJAK.md`
- `/docs/08_legacy_systems/LEGACY_OFFICERS_MAPPING.md`
- `/docs/08_legacy_systems/migration_strategy.md`

## 🧠 9. Studi Logika Sistem
- `/docs/09_sistem_logic_study/ANALISIS_LOGIKA_MPAD.md`

## 💳 10. Payment Gateway & H2H Perbankan
Spesifikasi integrasi perbankan multi-bank:
- `/docs/10_payment_gateway_h2h/BRI/` (Termasuk `BRI_SNAP_JOURNEY.md`)
- `/docs/10_payment_gateway_h2h/BNI/`
- `/docs/10_payment_gateway_h2h/MANDIRI/`
- `/docs/10_payment_gateway_h2h/BPD/`
- `/docs/10_payment_gateway_h2h/BTN/`
- `/docs/10_payment_gateway_h2h/BPN/`
- `/docs/10_payment_gateway_h2h/BSRE/` (Dokumentasi TTE BSrE, spesifikasi server Esign, dan pemenuhan 11 standar)
- `/docs/10_payment_gateway_h2h/mPaD_H2H_API_Specification.md`

## 🚀 11. Kesiapan Launching & Materi Rapat
- `/docs/11_pre_launch_readiness/MATERI_RAPAT_KADIS_PTSP_MPAD.md`
- `/docs/11_pre_launch_readiness/M-PAD_READINESS_GUIDE.md`
- `/docs/11_pre_launch_readiness/WA_E2E_TEST_RESULT.md`

## 🌐 99. Master Index & System Overview
- `/docs/99_umum_system/MEGA_DOCUMENTATION.md` (Executive Index & Single Entry Point)
- `/docs/99_umum_system/SYSTEM_OVERVIEW.md`
- `/docs/99_umum_system/MASTER_KNOWLEDGE_BASE.md`
- `/docs/99_umum_system/CHANGE_LOG.md`
- `/docs/99_umum_system/cek_saat_launching.md`

## 🗄️ Arsip Mentah (Non-Markdown & Transkrip Asli)
Tempat pengarsipan file biner, bahan rapat eksternal, dan transkrip asli:
- `/docs/arsip_mentah/transkrip_arahan/pak_massad_original.md`
- `/docs/arsip_mentah/materi_rapat_dan_kajian/` (File .docx, .pdf, .xlsx dari pihak luar / rapat dinas)
- `/docs/arsip_mentah/legacy_snapshots/` (Snapshot historis folder sipanda-docs dan artifacts lama)
