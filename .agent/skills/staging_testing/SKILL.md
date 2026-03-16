---
name: Staging Domain Testing (Comprehensive)
description: Master skill untuk pengujian menyeluruh pada lingkungan staging *.mpad.online, mencakup infrastruktur, keamanan RBAC, akurasi formula, dan integrasi sistem.
---

# Instruksi Pengujian Terpadu (mpad.online)
Gunakan skill ini untuk melakukan audit dan validasi menyeluruh pada sistem MPAD. Skill ini mengacu pada seluruh standar di folder `/testing/`.

## 🚨 Aturan Emas (The Golden Rule)
Setiap perubahan kode di lokal **WAJIB** diikuti dengan deployment ke staging dan diuji langsung di domain `*.mpad.online`. Jangan hanya mengandalkan tes lokal.

## 🛠️ Cakupan Pengujian & Diagnostik

### 1. Keamanan & RBAC (Ref: 09-Keamanan-RBAC.md)
Pastikan prinsip *Least Privilege* terjaga. Uji skenario bypass:
- **WP:** Harus ditolak (403) jika akses rute admin.
- **Petugas:** Harus ditolak (403) jika mencoba menghapus (`DELETE`) data.
- **Tenancy:** Wajib Pajak A dilarang melihat data/tagihan Wajib Pajak B.

### 2. Akurasi Formula & Kalkulasi (Ref: 07-Formula-Jenis-Pajak.md)
Validasi hasil matematis pada kalkulator API:
- **PBB-P2 2026**: 
  - NJOPTKP (default 10jt) harus terhitung.
  - **NOP Inquiry**: Response Bapenda harus ter-render real-time.
  - **NTPD Persistence**: Pastikan NTPD tersimpan di DB lokal setelah bayar sukses.
- **BPHTB:** `(NPOP - NPOPTKP) * 5%`.
- **Self-Assessment:** PBJT Restoran/Hotel (10%), Hiburan Malam (40%).
- **Retribusi:** Pastikan tarif flat (Parkir/Sampah) tidak menghasilkan nilai percentage.

### 3. Filter & Visibilitas Data (Ref: 11-Filter-Wajib-Pajak-Petugas.md)
Uji penugasan pengguna:
- **Petugas:** Hanya boleh melihat dan mencatat tagihan pada klasifikasi yang ditugaskan (misal: "PBJT - Parkir").
- **API Response:** Pastikan array data tidak "bocor" (relasi di luar klasifikasi tidak muncul).

### 4. Kesiapan Infrastruktur (Ref: test_production_ready.sh)
Lakukan sanity check periodik:
- **CORS:** Cek preflight `OPTIONS` dan header `Access-Control-Allow-Origin` dari `admin`/`petugas` ke `api.mpad.online`.
- **SSL & Health:** Pastikan HTTPS aktif dan endpoint `/up` merespon 200 OK.
- **Environment Sync:** Pastikan `.env.staging` di seluruh repo selaras.

### 5. Alur E2E & Master Data (Ref: 02-e2e-testing-scheme.md)
Uji siklus hidup data:
- Registrasi WP (Mobile) ➡️ Verifikasi (Admin) ➡️ Tagihan & Bayar (Petugas).
- **Cascading Delete:** Pastikan penghapusan master data yang memiliki relasi tertolak dengan pesan error yang tepat (400 Bad Request), bukan 500 error.

## 🚀 Eksekusi Pengujian (No Screenshot)
Sesuai standar **/noss**, semua bukti harus murni dari Terminal/Log:
- Jalankan `./testing/test_api_crud.sh` untuk validasi logika bisnis.
- Jalankan `./testing/test_penetration.sh` untuk audit keamanan.
- Hasil pengujian **wajib** disimpan di `testing/results/` dengan timestamp lengkap.

## 🛰️ Deployment & Environment Sync (Git & VPS)

Jika `git pull` gagal di VPS karena "divergent branches" atau rintangan lainnya, gunakan strategi paksa (Hard Sync):
1. **Hard Reset**: Gunakan `git fetch origin` lalu `git reset --hard origin/staging` untuk memastikan VPS identik dengan remote.
2. **Permission Handling**: Jika reset gagal karena `Permission denied`, gunakan `sudo chown` sementara ke user `sipanda` untuk folder `storage` & `bootstrap/cache`, lakukan git sync, lalu kembalikan ke `www-data`.
3. **Expect Automation**: Gunakan script `.exp` (Expect) untuk otomatisasi SSH, Sudo, dan input password yang repetitif guna menghindari human error.

## 🩺 Protokol Troubleshooting
Jika ditemukan 500 Error:
1. Buka Network Tab ➡️ Cek `error_detail` dan `trace`.
2. Verifikasi apakah kode sudah ter-deploy ke VPS (`git pull` & `migrate` atau via fallback `scp` manual).
3. Cek `laravel.log` di server staging.
4. **Stale Environment Memory:** Jika `.env` sudah di-update namun DB tetap `Access denied`, jalankan *reload* pada process manager PHP di server (contoh: `sudo systemctl reload php8.3-fpm && sudo systemctl reload php8.4-fpm`) untuk memaksa pemuatan variabel baru dari memori.
