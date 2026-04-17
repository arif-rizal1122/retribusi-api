---
name: Documentation Context & Maintenance
description: Menjaga konsistensi pemahaman AI tentang arsitektur sistem dari folder docs/ dan aturan memperbarui dokumentasi.
---

## 🧭 Protokol Pembacaan Kondisional (Context Routing)
AI Agent **TIDAK BOLEH** melakukan riset buta. Gunakan tabel ini untuk menentukan skill mana yang harus diaktifkan:

| Kondisi / Tugas | Skill yang WAJIB Dibaca | Alasan |
| :--- | :--- | :--- |
| **Awal Percakapan** | `docs_context` | Memahami Master Index & Aturan Keselamatan. |
| **Terjadi Error 500/CORS** | `qa_error_registry` | Daftar bug historis & fix yang sudah ada. |
| **Akses VPS / Staging** | `staging_deployment_manager` | Protokol deployment & infra. |
| **Testing / Verifikasi** | `omni_workspace_tester` | Protokol pengujian E2E & PDF Integrity. |
| **Penindakan / SPT** | `audit_enforcement` | SOP pengawas & penindakan. |
| **Data Warisan / Migrasi** | `data_migration` | Riset folder `docs/08_legacy_systems/`. |

## 🧬 Aturan Keselamatan Mutlak (The Never-Do's)
- **DILARANG** meng-hardcode password/secrets di dokumentasi atau kode.
- **DILARANG** merubah CORS pada Nginx tanpa mendaftarkan domain secara eksplisit.
- **DILARANG** mengosongkan folder `storage` atau `bootstrap/cache` di server.
- **DILARANG** menggunakan `named arguments` pada middleware (Gunakan Positional Arguments).
- **DILARANG** melakukan verifikasi visual (Screenshot). Gunakan protokol `/noss` (Terminal/Script).

## 🧠 Daftar Skill Agen (Specialized Knowledge)
... (list existing skills) ...

## 🛠️ Task Dispatcher (Alur Kerja Agen)
Jika user memberikan tugas kompleks, bagi ke dalam kategori:
1. **Infrastruktur/Deployment**: Aktifkan `staging_deployment_manager`.
2. **Logika Bisnis/Pajak**: Aktifkan `reporting_billing` atau `regulatory_logic`.
3. **Security/QA**: Aktifkan `qa_error_registry` & `omni_workspace_tester`.
4. **Dokumen Resmi/TTE**: Aktifkan `tte_documents`.
5. **Manajemen Dokumen/Drive**: Aktifkan `docs_sync_management`.

## Aturan Pembaruan Dokumentasi
1. Jika Anda mengubah struktur Database, perbarui `docs/04-database-schema.md`.
2. Jika Anda mengubah rute API, perbarui `docs/routes-and-components.md`.
3. Catat perubahan signifikan di `docs/todo-list.md` (Update status [x]).
4. Jika terdapat perubahan skema atau mapping 9pajak, perbarui `docs/08_legacy_systems/MEGA_DOCUMENTATION_9PAJAK.md`.
