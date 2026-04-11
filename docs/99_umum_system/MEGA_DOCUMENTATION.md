# 🗺️ M-PAD: Peta Navigasi Dokumentasi (Update Portofolio 2024)

Dokumen ini adalah Hub Utama untuk memahami sistem **M-PAD (Manajemen Integrasi Tax, Retribusi, dan Aset Daerah)** Kota Baubau. 

### 🏛️ Paradigma Portofolio (Perwali 8/2025)
M-PAD kini menggunakan pembagian **Wilayah I (Manajemen Aset & Properti)** dan **Wilayah II (Manajemen Konsumsi & Retail)** sebagai struktur organisasi utama.

## 1. Fondasi & Regulasi
Untuk memahami aturan dasar, tarif (10% Parkir), dan hirarki portofolio:
- [Pusat Regulasi Baubau 2024 (Source of Truth)](file:///Users/pondokit/Herd/retribusi-api/docs/01_regulasi_baubau/BAUBAU_REGULATORY_MASTER.md)
- [Gambaran Umum Sistem & Organisasi](file:///Users/pondokit/Herd/retribusi-api/docs/99_umum_system/SYSTEM_OVERVIEW.md)

## 2. Struktur Data & Teknis
Detail mengenai tabel database, ERD, dan kredensial server:
- [Skema Database M-PAD (Portfolio Hierarchy)](file:///Users/pondokit/Herd/retribusi-api/docs/02_arsitektur_database/04-database-schema.md)
- [Catatan Infrastruktur VPS](file:///Users/pondokit/Herd/retribusi-api/docs/04_infrastruktur_api/INFRASTRUCTURE_NOTES.md)

## 3. Operasional & Panduan
Bagaimana menggunakan aplikasi untuk Bidang I dan Bidang II:
- [Panduan Admin Bapenda](file:///Users/pondokit/Herd/retribusi-api/docs/06_panduan_pengguna/admin-userguide.md)
- [Panduan Petugas Lapangan](file:///Users/pondokit/Herd/retribusi-api/docs/06_panduan_pengguna/petugas-userguide.md)
- [Workflow Penagihan & Verifikasi (Hulu-ke-Hilir)](file:///Users/pondokit/Herd/retribusi-api/docs/03_proses_bisnis_penagihan/INVOICING_WORKFLOW.md)

## 4. Testing & Kualitas
Prosedur pengujian untuk tim IT dan AI Agent:
- [Panduan Pengujian (Portfolio RBAC Overhaul)](file:///Users/pondokit/Herd/retribusi-api/docs/07_testing_kualitas/TESTING_GUIDE.md)

## 5. Sistem Warisan (Legacy)
Konteks sejarah dan pemetaan data dari purwarupa sistem:
- **[9pajak (SW_PATDA) - Mega Documentation](file:///Users/pondokit/Herd/retribusi-api/docs/08_legacy_systems/MEGA_DOCUMENTATION_9PAJAK.md)**: Skema `CPM_`, Tabel `_PROFIL`/`_DOC`, dan Rosetta Stone pemetaan ke M-PAD.

---

> [!IMPORTANT]
> **Source of Truth**: Selalu merujuk pada `BAUBAU_REGULATORY_MASTER.md` untuk deskripsi tugas masing-masing wilayah guna memastikan tidak ada tumpang tindih operasional antara Bidang I (Aset) dan Bidang II (PBJT/Konsumsi).

*Dokumen ini diperbarui April 2026. Sinkronisasi Organisasi Berbasis Perwali 8/2025.*
