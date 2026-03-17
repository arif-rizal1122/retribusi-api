---
name: Documentation Context & Maintenance
description: Menjaga konsistensi pemahaman AI tentang arsitektur sistem dari folder docs/ dan aturan memperbarui dokumentasi.
---

# Instruksi Manajemen Dokumen & Konteks
Saat bekerja dalam project ini, terutama untuk fitur besar atau ketika diminta mempelajari konteks arsitektur sistem, Anda harus mematuhi aturan dokumentasi berikut:

## Rujukan Pengetahuan Utama
Sebelum membuat penyesuaian logika bisnis besar atau arsitektural, periksa dokumen berikut di folder `docs/`:
1. `docs/system-knowledge.md` - Untuk mempelajari struktur Models, Services, dan Commands.
2. `docs/routes-and-components.md` - Untuk melihat pemetaan Endpoint API.
3. `docs/04-database-schema.md` & `docs/DOMAIN_SCHEMA.md` - Untuk memahami referensi tabel dan skema relasi.
4. `docs/TESTING_GUIDE.md` - Untuk standar eksekusi dan instruksi testing.
5. `API_PBB_BAUBAU_2026.md` - Spesifikasi utama integrasi PBB Bapenda.

## 🧠 Daftar Skill Agen (Specialized Knowledge)
Gunakan skill berikut sesuai konteks tugas untuk mendapatkan panduan SOP yang mendalam:
1.  **Auditor & Enforcement Specialist** (`audit_enforcement`): Fokus pada surveillance & penindakan.
2.  **Backend Expert** (`backend_expert`): Fokus pada performance & GPS live tracking.
3.  **Documentation Context** (`docs_context`): Standar penulisan & pemeliharaan dokumen.
4.  **Omni Workspace Tester** (`omni_workspace_tester`): Strategi testing E2E lintas 4 repositori.
5.  **QA Error Registry** (`qa_error_registry`): Database mitigasi bug & known issues.
6.  **Regulatory Compliance** (`regulatory_logic`): Aturan pajak (Perwali 58) & PBB 2026.
7.  **Reporting & Billing Lifecycle** (`reporting_billing`): Siklus SPTPD hingga SKRD/Billing.
8.  **Staging Domain Testing** (`staging_testing`): Protokol pengujian di mpad.online.
9.  **Project Testing Standards** (`testing_standards`): Aturan /noss & skrip testing.
10. **TTE & Digital Document** (`tte_documents`): Alur TTE & 21 jenis dokumen resmi.
11. **POS Specialist** (`pos_specialist`): Khusus untuk pengembangan di repo `retribusi-pos`.

## Aturan Pembaruan Dokumentasi
1. Jika Anda mengubah struktur Database (Migration/Seeder), Anda **wajib** memperbarui file `docs/04-database-schema.md` atau `DOMAIN_SCHEMA.md` jika relevan.
2. Jika Anda menambahkan/mengubah rute API atau Controller, perbarui `docs/routes-and-components.md` agar pemetaan API tetap relevan.
3. Selalu pastikan pembaruan menggunakan standar markdown proyek.
4. Catat setiap perubahan atau modifikasi fitur yang signifikan di file `CHANGE_LOG.md` (di root directory).
5. Jangan berasumsi arsitektur bisnis tanpa merujuk ke direktori `docs/` terlebih dahulu.
