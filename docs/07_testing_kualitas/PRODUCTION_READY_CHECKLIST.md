# 🚀 Production Readiness Checklist - MPAD Baubau

Dokumen ini mendefinisikan standar "Siap Publish" untuk setiap perubahan kode di ekosistem MPAD. Perubahan hanya boleh di-merge ke branch `main` jika seluruh poin di bawah ini terpenuhi.

## 1. Validasi Struktural & Integritas
- [ ] **Hierarki Wilayah**: Pastikan `retribution_types` tetap berjumlah 2 (Wilayah I & II).
- [ ] **Relasi Klasifikasi**: Pastikan klasifikasi baru dikaitkan ke kedua Wilayah jika berlaku secara global.
- [ ] **Skema Database**: Seluruh migrasi database bersifat non-destruktif (tidak menghapus data historis).

## 2. Verifikasi Pipeline Testing
- [ ] **Local Audit**: Menjalankan `qa7_ultimate_mpad_audit.php` dengan hasil 100% Lulus (✅ OK).
- [ ] **E2E Role Flow**: Menjalankan `run_role_e2e_test.php` (Admin -> WP -> Petugas).
- [ ] **Citizen Experience**: Menjalankan `qa11_citizen_e2e_flow.php` untuk verifikasi aplikasi Mobile.

## 3. Sinkronisasi Environment
- [ ] **Dev Sync**: Kode sudah stabil di branch `dev` dan teruji secara integrasi.
- [ ] **Staging Sign-off**: Kode sudah di-deploy ke lingkungan Staging (`sipanda.online`) dan diverifikasi oleh pihak Bapenda/User.
- [ ] **CORS & Env**: Pastikan `.env.production` sudah menunjuk ke domain resmi `api.mpad.baubaukota.go.id`.

## 4. Keamanan & Performa
- [ ] **RBAC Isolation**: Menjalankan `run_rbac_test.php` untuk memastikan tidak ada kebocoran data antar wilayah.
- [ ] **PDF Integrity**: Verifikasi template SKRD/SSPD tidak pecah saat digenerate di server.

## 5. Protokol Merger (Main Branch)
- [ ] **No Conflict**: Tidak ada konflik saat melakukan merge dari `dev` ke `main`.
- [ ] **Deployment Warning**: Jika ada perubahan skema DB, siapkan script rollback.
- [ ] **Database Production**: **DILARANG** melakukan sinkronisasi data (dump) dari staging ke production. Hanya skema yang boleh berubah.

---
*Gunakan skill `Production Deployment Manager` untuk mengeksekusi proses ini secara otomatis.*
