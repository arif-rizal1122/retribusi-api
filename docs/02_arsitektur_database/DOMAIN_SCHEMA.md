# 🌐 Domain Schema & Subdomain Mapping
## Arsitektur M-PAD (April 2026)

Dokumentasi ini merinci pemetaan domain dan subdomain untuk sistem M-PAD (Mitra Pajak & Retribusi Daerah) Kota Baubau.

## 1. Lingkungan Produksi (Pemerintah Kota Baubau)
Domain resmi yang digunakan untuk operasional publik (Wajib Pajak) dan internal (Bapenda/Petugas).

| Komponen | Domain | Deskripsi |
| :--- | :--- | :--- |
| **Portal Layanan WP** | `mpad.baubaukota.go.id` | PWA: Pendaftaran, Inkuiri NOP PBB, E-SPPT |
| **Dashboard Admin** | `adminmpad.baubaukota.go.id` | Backoffice: Penetapan, Verifikasi, TTE Dokumen |
| **Backend API** | `api.sipanda.online` | Centralized Logic: CRUD, Auth, Integrasi Bank |
| **Portal Petugas** | `petugasmpad.baubaukota.go.id` | Lapangan: Spot Check, Audit Reklame, Live Tracking |
| **E-Registry** | `verify.baubaukota.go.id` | Validasi QR-Code TTE (Publik) |

## 2. Lingkungan Staging / Testing (VPS)
Digunakan untuk validasi fitur baru (Hotfix & Minor/Major Releases).

| Komponen | Domain | Deskripsi |
| :--- | :--- | :--- |
| **Main Entrance** | `sipanda.online` | Mobile/Citizen frontend staging |
| **Admin Panel** | `admin.sipanda.online` | Admin dashboard staging |
| **API Endpoint** | `api.sipanda.online` | Backend API staging |
| **Officer Portal** | `petugas.sipanda.online` | Petugas field portal staging |

## 3. Alur Komunikasi Data
1. **WP/Petugas** mengirimkan request ke `api.sipanda.online`.
2. **API** memproses logika (Formula Parser, PBB Inquiry).
3. **Database** (MySQL) menyimpan state dan `metadata` JSON.
4. **TTE Service** (BSrE) dipanggil untuk penandatanganan dokumen resmi.
5. **Gateway Payment** memproses billing via `api.sipanda.online` (Redirect/Callback).

---
*Last modified: April 2026. Unified Branding M-PAD.*
