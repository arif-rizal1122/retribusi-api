# 📚 M-PAD Master Documentation Index
## Pusat Pengetahuan Terintegrasi (April 2026)

Selamat datang di pusat dokumentasi **M-PAD (Mitra PAD) Kota Baubau**. Folder ini merupakan hasil konsolidasi seluruh dokumen teknis, regulasi, testing, dan panduan pengguna dari seluruh ekosistem M-PAD (Admin, API, Mobile, Petugas, dan POS).

---

## 📂 Peta Navigasi Utama

### 1. 🏛️ Arsitektur & Teknis (Core)
*Wajib dibaca oleh Developer & AI Agent sebelum melakukan modifikasi.*
- ⭐ **[SYSTEM_OVERVIEW.md](SYSTEM_OVERVIEW.md)**: Gambaran besar arsitektur, V-Tax & PBB Parity.
- 💾 **[04-database-schema.md](04-database-schema.md)**: ERD dan struktur tabel (Update: Enforcement, Amnesty, TTE).
- 🌐 **[DOMAIN_SCHEMA.md](DOMAIN_SCHEMA.md)**: Pemetaan subdomain operasional.
- 🔌 **[routes-and-components.md](routes-and-components.md)**: Daftar endpoint API dan komponen routing.
- 🚦 **[STABLE_BASELINE.md](STABLE_BASELINE.md)**: Titik tolak commit stabil untuk sinkronisasi.

### 2. 📖 Panduan Pengguna (User Guides)
- 🧑‍💻 **[Admin Dashboard](admin-userguide.md)**: Manajemen WP, Penetapan, TTE, dan Amnesty.
- 👮 **[Petugas Lapangan](petugas-userguide.md)**: Spot Check, Audit Visual Reklame, Tracking Lokasi.
- 👩‍👩‍👦 **[Portal Wajib Pajak](mobile-e-retribusi.md)**: Inkuiri PBB, E-SPPT, dan Pelaporan Mandiri.

### 3. ⚖️ Regulasi & Modul Khusus (`/regulasi`)
- **[Amnesty & Penalty Waiver](penalty_waiver_logic.md)** (New): Logika penghapusan denda.
- **[Enforcement & Penindakan](enforcement_workflow.md)** (New): Alur Surat Teguran & Surat Paksa.
- **[TTE & E-Registry](tte_registry_standards.md)** (New): Standar verifikasi dokumen digital.
- **[Integrasi PBB 2026](regulasi/API_PBB_BAU_BAU_2026.md)**: Pedoman teknis inkuiri NOP.

### 4. 🧪 Standar Pengujian & Kualitas (`/testing`)
- **[PANDUAN TESTING](testing/00-Panduan-Standar-Pengujian.md)**: Protokol QA internal.
- **[QA ERROR REGISTRY](testing/12-Error-History-Mitigation-Registry.md)**: Sejarah error dan solusi mitigasi.
- **[STAGING PROTOCOL](testing/13-Workspace-Master-Testing-Protocol.md)**: Prosedur sebelum deployment production.

---

### 🏪 Modul Cross-Domain Lainnya
- **POS/Kasir**: `pos-MAIN-backend.md`, `pos-MAIN-frontend.md`.
- **GIS Tracking**: `skema-pelacakan-lokasi-petugas.md`.
- **Migration**: `migration_strategy.md`.

---
*Catatan AI Agent: Selalu perbarui doc ini jika ada penambahan modul besar atau perubahan struktur domain.*
