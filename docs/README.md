# 📚 M-PAD Master Documentation Index

Selamat datang di pusat dokumentasi **M-PAD (Mitra PAD) Kota Baubau**. Folder ini merupakan hasil konsolidasi seluruh dokumen teknis, regulasi, testing, dan panduan pengguna dari seluruh sub-repositori ekosistem (Admin, API, Mobile, Petugas, dan POS). 

Fungsi file ini adalah sebagai **Peta Navigasi Utama** agar Anda tidak tersesat di antara puluhan file markdown.

---

## 📂 Peta Navigasi Direktori

### 1. 🏛️ Regulasi & Penyelarasan (Folder `/regulasi`)
Memuat aturan daerah (Perwali), standar penagihan pajak daerah (V-Tax Parity), dan status kelengkapan 21 jenis formulir.
- [Ringkasan Perwali 58/2024](regulasi/03-perwali-pdrd-summary.md)
- [Status Implementasi 21 Dokumen Resmi (PDF & TTE)](regulasi/07-status-dokumen-resmi.md)
- [Standarisasi Master Data Pajak](regulasi/02-master-data-objek-pajak.md)
- [Pedoman Integrasi PBB 2026](regulasi/API_PBB_BAUBAU_2026.md)

### 2. 📖 Panduan Pengguna (User Guides)
Dokumentasi langkah demi langkah (*step-by-step*) lengkap dengan panduan visual untuk masing-masing aktor sistem.
- 🧑‍💻 **Administrator (Bapenda)**: [admin-userguide.md](admin-userguide.md)
- 👮 **Petugas Lapangan**: [petugas-userguide.md](petugas-userguide.md)
- 👩‍👩‍👦 **Warga (Citizen App)**: [mobile-e-retribusi.md](mobile-e-retribusi.md)

### 3. ⚙️ Arsitektur & Teknis (Core Tech)
Informasi esensial untuk para Developer, Software Engineer, dan Agen AI.
- ⭐ **M-PAD System Overview**: [SYSTEM_OVERVIEW.md](SYSTEM_OVERVIEW.md) *(Mulai baca dari sini)*
- **Database Schema & ERD**: [04-database-schema.md](04-database-schema.md)
- **Modul API & Frontend Routing**: [routes-and-components.md](routes-and-components.md)
- **To-Do List Terpusat**: [todo-list.md](todo-list.md)

### 4. 🧪 Standar Pengujian (`/testing`)
Protokol QA mutlak yang harus ditaati sebelum memindahkan kode ke Production.
- **Standar Utama Testing**: [testing/00-Panduan-Standar-Pengujian.md](testing/00-Panduan-Standar-Pengujian.md)
- 🛡️ **QA Error & Mitigation Registry**: [testing/12-Error-History-Mitigation-Registry.md](testing/12-Error-History-Mitigation-Registry.md) *(Buku panduan sejarah error & mitigasinya)*
- **Staging Readiness**: [testing/13-Workspace-Master-Testing-Protocol.md](testing/13-Workspace-Master-Testing-Protocol.md)

### 5. 📊 Laporan Uji Coba (`/results`)
Folder ini menampung log historikal pengujian penetrasi keamanan, simulasi kalkulator, dan tes CRUD API.

### 6. 🏪 Modul Cross-Domain Khusus
- **POS/Kasir**: `pos-MAIN-backend.md`, `pos-MAIN-frontend.md`
- **Integrasi Peta**: `skema-pelacakan-lokasi-petugas.md`

---
*Catatan AI Agent: Jika Anda seorang AI yang bertugas melanjutkan pengerjaan modul, bacalah `SYSTEM_OVERVIEW.md` dan `04-database-schema.md` terlebih dahulu.*
