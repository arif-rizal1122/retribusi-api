# 🔄 Master Omni Sync Pipeline Protocol
**Status**: STANDBY
**Repositories**: API, Admin, Petugas, Mobile

Gunakan pipeline ini untuk melakukan sinkronisasi lintas environment secara aman dan terverifikasi.

## 🏁 STEP 1: Local Integrity Lockdown
Sebelum melakukan push ke Dev, Anda wajib menjalankan audit lokal.
- **Action**: `php testing/qa7_ultimate_mpad_audit.php`
- **Goal**: Memastikan relasi hirarki L1-L4 tidak terputus dan Wilayah-Centric mapping sudah benar.
- **Checkpoint**: Perbaiki status ❌ ERROR pada "Retribution Types" (L1) jika belum bernilai 2 (Wilayah I & II).

## 🛠️ STEP 2: Sync to DEV Environment
Sinkronkan perubahan lokal ke branch development.
- **Command**: `./run_sync_dev.sh` (Simulasi: Git Push origin dev)
- **Validation**: Jalankan `qa11_citizen_e2e_flow.php` pada domain `api.sipanda.online` (Ganti Base URL).

## 🧪 STEP 3: Staging Verification (Dev -> Staging)
Setelah Dev stabil, tarik perubahan ke Staging.
- **Sync Protocol**:
  1. Merge `dev` ke `staging` branch.
  2. Push ke staging.
- **Audit**: Jalankan `testing/stg1_health_check.php` di server staging.
- **Confirmation**: Pastikan dokumen TTE (SKRD/SSPD) bisa digenerate di lingkungan staging.

## 🚀 STEP 4: Production Sync (The Final Gate)
**ATURAN MUTLAK**: Jangan lakukan sinkronisasi Database ke Production.
- **Sync Protocol**:
  1. Hanya sinkronkan **SOURCE CODE** (Git Main).
  2. Database Production tetap menggunakan data existing.
  3. Lakukan migrasi database hanya jika ada penambahan kolom baru (non-destruktif).

---
*Gunakan skill `Omni Testing & Structural Integrity Specialist` untuk memandu setiap langkah di atas.*
