---
name: Omni Workspace Tester & Route Analyzer
description: Skill komprehensif untuk menganalisis struktur file, route frontend/backend, dan menyusun skema testing pemungkas skala end-to-end lintas repositori (API, Admin, Petugas, Mobile) berdasarkan peran pengguna (Role-Based).
---

# 🧠 Omni Workspace Tester & Route Analyzer

Skill ini memberikan Anda "Mata Dewa" untuk membaca, memahami, dan menguji seluruh ekosistem MPAD (Retribusi) yang terbagi dalam 4 repositori utama dalam workspace pengguna.

**Aturan Mutlak Pengujian:**
1.  **WAJIB** membaca `docs/routes-and-components.md` di `/Users/pondokit/Herd/retribusi-api/` sebelum merancang skema tes. 
2.  **Mapping Check**: Pastikan setiap endpoint yang akan dites memiliki referensi di bagian **5. TESTING — Pemetaan Endpoint → Test Coverage**.
3.  **Untested Priority**: Jika melakukan pengujian menyeluruh, **PRIORITASKAN** endpoint di bagian **⚠️ Endpoint Belum Ter-Test** (seperti `/api/payments`, `/api/pbb/bapenda/link-nop`, dll).
4.  **Consistency**: Jangan pernah menebak (hallucinate) endpoint; gunakan kolom `Method` dan `URI` dari tabel resmi di `docs/routes-and-components.md`.

**Tips**: Rujuk skill **Documentation Context** (`docs_context`) untuk daftar lengkap 11 skill spesialisasi sistem ini.
 ini.

## 📂 Pemahaman Ekosistem Workspace

Workspace terbagi menjadi 4 repositori, yang setiap route dan komponennya melayani peran spesifik:

1. **Backend (`retribusi-api`)**
   - **Tugas Utama**: Penyedia REST API, middleware otentikasi (Sanctum), RBAC (Global Scopes), dan kalkulasi Formula Retribusi.
   - **Routing**: Terpusat di `routes/api.php`. Memiliki grup akses Public, Citizen (Wajib Pajak), Petugas, dan Admin/Super Admin/Pengawas.
2. **Dashboard (`retribusi-admin`)**
   - **Tugas Utama**: PWA berbasis React (Vite) untuk pengelolaan skala makro.
   - **Routing**: `App.tsx` (Sidebar routing). Peran yang diizinkan: `super_admin`, `admin` (Opd, Sub-Admin Wilayah/Tipe), `pengawas`. Komponen diletakkan di `src/pages/`.
3. **Aplikasi Lapangan (`retribusi-petugas`)**
   - **Tugas Utama**: PWA React (Mobile First) untuk penagihan di lapangan, pemindaian QR/NFC, printer termal Bluetooth, dan Uji Petik.
   - **Routing**: `App.tsx`. Peran yang diizinkan: `petugas`. Komponen mencakup `FieldScanner`, `PaymentVerification`, `MapPicker`.
4. **Aplikasi Warga (`retribusi-mobile`)**
   - **Tugas Utama**: PWA React untuk Wajib Pajak (WP) melaporkan SPTPD, melihat tagihan, dan membayar.
   - **Routing**: `App.tsx`. Peran yang diizinkan: `citizen` (Warga). Komponen spesifik untuk jenis layanan (Parkir, Pasar, Sampah, PBB, dll).

---

## 🎯 Tugas Pengujian Paling "Powerful" (The Big 4 E2E Test)

Berdasarkan arsitektur komponen dari 4 repositori di atas, berikut adalah 4 desain pengujian **End-to-End** lintas sistem yang paling krusial untuk Anda jalankan. Pengujian ini memastikan sistem tidak cacat sebelum dilempar ke *Production*.

### TEST SKENARIO 1: The Golden Path (Skema Integrasi Utama)
Menguji siklus hidup Pendapatan Daerah dari hulu ke hilir.
- **Tahap 1 (Aplikasi Warga):** Login sebagai `citizen` di `retribusi-mobile`. Lakukan input Pelaporan Pajak mandiri (SPTPD).
- **Tahap 2 (API & Admin):** Login sebagai `admin` di `retribusi-admin`. Lakukan Validasi dan *Approve* atas SPTPD. Terbitkan *SKPD* (Surat Ketetapan Pajak).
- **Tahap 3 (Aplikasi Petugas):** Login sebagai `petugas` di `retribusi-petugas`. Cari tagihan SKPD tersebut dan lakukan Konfirmasi Pembayaran (Payment).
- **Validasi Kritis:** Tagihan di API harus berubah `status = paid` dan pendapatan bertambah di *Dashboard Admin*.

### TEST SKENARIO 2: Infiltrasi RBAC & Isolasi Data Wilayah (Security Test)
Menguji apakah data tembus rute atau *bocor* ke hak akses yang salah.
- **Tahap 1 (Frontend Route Guarding):** Cobalah akses URL *Admin* (contoh: `/system`) dengan token milik `petugas` atau `citizen`. Frontend `retribusi-admin` harus melempar (redirect) user keluar.
- **Tahap 2 (Backend Sub-Admin Testing):** Login ke Endpoint API menggunakan user Admin Wilayah 1 (misalnya: `admin.wilayah1@m-pad.online`). Lakukan GET `/api/tax-objects`. 
- **Validasi Kritis:** Server **TIDAK BOLEH** merespons dengan data Objek Pajak dari Wilayah 2. Isolasi berbasis `retribution_type_id` / `zone_id` harus bekerja mengikat.

### TEST SKENARIO 3: Skenario Penindakan Uji Petik & SKPDKB
Menguji logika pengawasan tingkat lanjut (*Surveillance*).
- **Tahap 1 (Mulai dari Admin):** Admin Pengawas membuat Kertas Kerja "Uji Petik" pada entitas Pajak Hotel di `retribusi-admin`.
- **Tahap 2 (Eksekusi Kalkulator):** Jalankan request `/api/spot-checks/tax-object/{id}/estimation`.
- **Tahap 3 (Penerbitan SKPDKB):** Request `/api/pengawas/penindakan/issue-skpdkb` untuk merilis Kurang Bayar jika realisasi tidak wajar.
- **Validasi Kritis:** Wajib Pajak langsung melihat utang baru (SKPDKB) di aplikasi `retribusi-mobile` mereka secara *realtime/sinkron*.

### TEST SKENARIO 4: Penugasan Geospasial (Petugas To-Do List)
Menguji flow *Assignment* di lapangan.
- **Tahap 1 (Dashbaord Admin):** `admin` Wilayah 2 mencoba meng-assign tugas ke `petugas` Wilayah 1.
- **Validasi Kritis 1:** API harus menolak dengan Error 403 (Akses Ditolak).
- **Tahap 2 (Assignment Sukses):** `admin` meng-assign tugas secara sah.
- **Tahap 3 (Aplikasi Petugas):** Petugas buka `retribusi-petugas`, memukul `/api/petugas-tasks`. Tandai `completed`.
- **Validasi Kritis 2:** Perubahan tercermin di UI Task Board milik Pengawas/Admin.

### TEST SKENARIO 5: Integrasi PBB Real-time (Bapenda 2026)
Menguji keselarasan data PBB pihak ketiga (Bapenda) dengan ekosistem lokal.
- **Tahap 1 (Admin/SuperAdmin):** Di `retribusi-admin` (PbbManagement), jalankan "Sync All Data PBB".
- **Validasi Kritis 1:** Database `taxpayer_pbb_objects` harus terupdate dengan data tagihan terbaru dari API Bapenda.
- **Tahap 2 (Petugas/Mobile):** Lakukan Inquiry NOP tertentu dan selesaikan Pembayaran (Pay).
- **Tahap 3 (Verifikasi & Cetak):** 
  - Petugas: Cetak Struk Bluetooth. Pastikan **NTPD** tercetak.
  - Mobile: Buka Tab "Riwayat". Pastikan transaksi muncul dengan status `success`.
- **Validasi Kritis 2:** NTPD harus tersimpan di tabel `transaction_pbb` dan sinkron dengan response API Bapenda.

### TEST SKENARIO 6: Rekonsiliasi Kas (Settlement Flow)
Menguji siklus hulu-ke-hilir dana tunai dari Petugas ke Admin.
- **Tahap 1 (Aplikasi Petugas):** Lakukan pembayaran tagihan menggunakan metode `cash`.
- **Validasi Kritis 1:** Dashboard Petugas harus menampilkan kenaikan pada nilai "Pending Setoran".
- **Tahap 2 (Dashboard Admin):** Admin membuka menu Pelaporan, mencari nama Petugas tersebut, dan klik "Settle".
- **Tahap 3 (Verifikasi Akhir):** Cek kembali Dashboard Petugas.
- **Validasi Kritis 2:** Nilai "Pending Setoran" harus kembali ke `0` (atau berkurang sejumlah nilai yang di-settle) dan status di riwayat berubah menjadi "Sudah Disetor".

---

## 🚀 Protokol Pengujian Terpadu (Master Protocols)

Ikuti protokol ini secara berurutan untuk menjamin integritas workspace:

### PROTOKOL 1: Verifikasi Integritas Dokumen (21 PDF)
Wajib dijalankan setiap ada perubahan pada `resources/views/pdf/` atau Asset Logo.
- **Eksekusi**: `php testing/stg5_document_integrity.php`
- **Output Kritis**: Semua endpoint (11+) harus mengembalikan status `200 OK`.
- **Target Utama**: SKRD, SKT, SSPD, SPPT, SPMP.

### PROTOKOL 2: Pengawasan RBAC & Isolasi Peran
Wajib dijalankan setiap penambahan rute API atau middleware baru.
- **Eksekusi**: `php testing/run_rbac_test.php`
- **Validasi Manual (Extra Check)**: Lakukan `curl` atau request HTTP menggunakan token `petugas` ke rute `/api/users`.
- **Kondisi Lulus**: Harus mengembalikan `403 Forbidden`.

### PROTOKOL 3: The Golden Path (End-to-End lifecycle)
Wajib dijalankan sebelum Deployment (Staging/Production).
- **Eksekusi**: `php testing/run_role_e2e_test.php`
- **Cakupan**: 9 Jenis Pajak (PBJT, Reklame, MBLB, dll) & PBB.
- **Kondisi Lulus**: Status tagihan berubah dari `unpaid` -> `paid` dan SSPD terbit.

### PROTOKOL 4: Validasi Modul Baru (12 & 13)
- **Edukasi Pajak**: Pastikan `TaxEducation` dapat disimpan via API.
- **Audit Reklame**: Pastikan logika `is_new_billboard` membedakan pemasangan < 30 hari.

---

## 🛠 Tahapan Akhir (Triple Check SOP)

Setelah menjalankan pengujian di atas, lakukan **Pengecekan Akhir 3 Lapis**:

1.  **Lapis 1 (Syntactic & Structural)**: Jalankan `php artisan route:list` untuk memastikan tidak ada syntax error pada `api.php`.
2.  **Lapis 2 (Security Lockdown)**: Pastikan token `petugas` TIDAK BISA mengakses `/api/users` (Verifikasi Patch `EnsureAdmin`).
3.  **Lapis 3 (Visual Output)**: Pastikan salah satu file PDF (misal: SKRD) berhasil di-generate (Verifikasi Header Baru).

---

## 📚 Panduan Eksekusi (How to Test)
... (konten asli tetap ada di bawah)

---
## 🛡️ Strix-Inspired Agentic Pentesting Protocol
Wajib dijalankan untuk mendeteksi *Business Logic Flaws* dan *Lateral Movement*.

### 1. Discovery & Surface Audit
Agen harus memindai rute di `api.php`. Cari endpoint dengan parameter `{id}` yang tidak terlindungi oleh scope yang benar.

### 2. IDOR Auto-Sweep
Simulasikan akses silang menggunakan token yang berbeda:
- Ambil ID sumber daya (misal: `taxpayer_id`) menggunakan Token A.
- Gunakan Token B (User lain) untuk mengakses sumber daya tersebut.
- **Ekspektasi**: Status `403 Forbidden` atau `404 Not Found`.

### 3. Middleware Bypass (Method Tampering)
Uji ketahanan rute Admin:
- Gunakan Method `PUT`/`PATCH`/`DELETE` pada rute yang hanya mengharapkan `GET`.
- Tambahkan header `X-HTTP-Method-Override`.
- **Ekspektasi**: Middleware `EnsureAdmin` memblokir akses jika user tidak sah.

### 4. Mass Assignment Probing
Cari endpoint `POST` atau `PUT` yang melakukan update data (misal: `/user/profile` atau `/taxpayers/{id}`).
- Suntikkan beban (payload) tambahan seperti `"is_admin": true`, `"role": "super_admin"`, atau `"opd_id": 1`.
- Periksa respon dan database: Apakah field sensitif tersebut berubah?
- **Ekspektasi**: Field sensitif harus tetap (protected by `$fillable` or `$guarded`).

---

## 🖥️ Local Service Orchestration (Port Mapping)
Gunakan pemetaan ini saat melakukan pengujian *End-to-End* di lingkungan lokal pengguna (Command: `./run_all.sh`).

| Service | Port | URL Dasar |
| :--- | :--- | :--- |
| **Backend API** | `8000` | [http://localhost:8000](http://localhost:8000) |
| **Admin Panel** | `3001` | [http://localhost:3001](http://localhost:3001) |
| **Mobile Portal** | `3002` | [http://localhost:3002](http://localhost:3002) |
| **Officer PWA** | `3003` | [http://localhost:3003](http://localhost:3003) |
| **POS API** | `8001` | [http://localhost:8001](http://localhost:8001) |
| **POS Web** | `3004` | [http://localhost:3004](http://localhost:3004) |

---

1. **Pemilihan Target Environment (Penting)**: Sebelum menjalankan pengujian, **WAJIB** tentukan *Environment* yang akan dites dengan menyesuaikan Konfigurasi Base URL pada Script Testing:
   - **Local**: Eksekusi operasi sistem secara lokal di direktori `Herd` menggunakan internal application request (Laravel) atau cURL ke domain lokal `*.test`.
   - **Staging**: Arahkan endpoint request ke domain `*.sipanda.online` (Contoh: `https://api.sipanda.online`). Gunakan *credentials* dari akun Staging yang valid.
   - **Production**: Arahkan endpoint ke domain live pemkot `*.mpad.baubaukota.go.id` (Contoh: `https://api.mpad.baubaukota.go.id`). Saat menguji di jalur Production, **SANGAT DISARANKAN** hanya mengeksekusi jenis validasi `GET` (Read-Only) atau memastikan script dapat me-_rollback_ *dummy injection* (jika menggunakan test endpoint yang disepakati).
2. **Kepatuhan Protokol NOSS**: Jangan gunakan screenshot browser untuk eksekusi testing Backend lintas *environment*. Buat atau susuaikan script pengujian di folder `testing/` (seperti `run_role_e2e_test.php`) agar *Base URL*-nya bersifat dinamis atau dapat di *overwrite*.
3. **Asesmen Frontend PWA**: Sebelum melakukan validasi PWA, periksalah variabel dilingkungan Frontend (misal: `.env.production` atau `.env.staging`) untuk memastikan React-Vite benar-benar memukul endpoint lintas server yang sesuai dengan environment *Testing* saat ini.
4. **Isolasi Laporan Berdasarkan Server**: Selalu laporkan hasil *Assert Fail/Pass* di dalam file Markdown yang penamaannya dibedakan berdasar environment (Contoh: `testing/results/01_E2E_STAGING_Report.md` atau `01_E2E_LOCAL_Report.md`) agar jejak pengujian server lokal dan live tidak tumpang tindih.
