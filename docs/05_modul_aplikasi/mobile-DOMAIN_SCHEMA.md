# 🌐 Domain Schema & Subdomain Mapping

Dokumentasi ini merinci pemetaan domain dan subdomain untuk sistem MPAD (Mitra Pajak & Retribusi Daerah) pada semua lingkungan deployment.

## 1. Lingkungan Development (Dev)
Digunakan murni untuk pengembangan (eksperimen fitur) dengan lingkungan VPS/Server non-kritis.
**Host IP:** `157.10.252.74`

| Komponen | Domain | Deskripsi |
| :--- | :--- | :--- |
| **Portal Utama** | `sipanda.online` | Aplikasi Warga / Landing page |
| **Dashboard Admin** | `admin.sipanda.online` | Panel manajemen Bapenda & OPD |
| **Interface Petugas** | `petugas.sipanda.online` | Akses petugas lapangan |
| **Backend API** | `api.sipanda.online` | Endpoint layanan data dev |
| **Launch Teaser** | `launch.sipanda.online` | Halaman promosi/hitung mundur |

## 2. Lingkungan Staging (UAT)
Digunakan sebagai tiruan (*replica*) dari *Production* untuk pengujian final (UAT / *Sandboxing* bersama pihak Bank).

| Komponen | Domain | Deskripsi |
| :--- | :--- | :--- |
| **Portal Utama** | `mpad.online` | Aplikasi Warga / Landing page staging |
| **Dashboard Admin** | `admin.mpad.online` | Panel manajemen versi staging |
| **Interface Petugas** | `petugas.mpad.online` | Akses petugas lapangan staging |
| **Backend API** | `api.mpad.online` | Endpoint layanan data staging |

## 3. Lingkungan Produksi (Prod)
Lingkungan *Live* yang diakses langsung oleh masyarakat Wajib Pajak dan pegawai Pemerintahan Kota Baubau.

| Komponen | Domain | Deskripsi |
| :--- | :--- | :--- |
| **Portal Utama** | `mpad.baubaukota.go.id` | Pintu masuk utama aplikasi/Landing page |
| **Dashboard Admin** | `adminmpad.baubaukota.go.id` | Panel manajemen untuk Bapenda & OPD |
| **Interface Petugas** | `petugasmpad.baubaukota.go.id` | Akses petugas lapangan (Mobile/Web) |
| **Backend API** | `apimpad.baubaukota.go.id` | Endpoint layanan data (Core API) |

---
*Terakhir Diperbarui: 7 Juli 2026*
