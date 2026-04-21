---
name: Omni Testing & Structural Integrity Specialist
description: Skill komprehensif untuk mengelola testing end-to-end lintas repositori dengan menjaga integritas hirarki 4-tingkat dan protokol sinkronisasi environment.
---

# 🛡️ Omni Testing & Structural Integrity Specialist

Skill ini adalah protokol utama untuk menjaga "Kesehatan Jantung" sistem MPAD Baubau. Anda wajib menggunakan skill ini saat melakukan testing, migrasi data, atau sinkronisasi environment.

## 🏛️ Pemahaman Struktur (Source of Truth)
Rujuk selalu ke `docs/99_umum_system/STRUCTURAL_INTEGRITY_GUIDE.md` untuk memahami relasi database.

**Aturan Emas Hierarki:**
1. **Level 1 (Wilayah)**: Harus tetap 2 (Wilayah I & II). Jangan menambah atau menghapus level ini.
2. **Level 3 (Klasifikasi)**: Dilarang menghapus klasifikasi pajak yang sudah ada. Jika tidak digunakan, kaitkan saja ke Wilayah yang relevan.
3. **Data Relasi**: Pastikan `retribution_type_id` pada klasifikasi selalu merujuk ke ID Wilayah yang benar.

## 🧪 Protokol Testing Bertingkat
Jangan pernah melakukan sinkronisasi ke environment yang lebih tinggi tanpa melewati gerbang testing ini:

### 1. Gerbang LOCAL (Local Readiness)
- **Script**: `php testing/qa7_ultimate_mpad_audit.php`
- **Tujuan**: Memastikan struktur DB, rute API, dan file frontend selaras.
- **Syarat Lulus**: Seluruh Phase 1-5 harus berstatus ✅ OK.

### 2. Gerbang DEV (Integration Sync)
- **Prosedur**: Push dari `main` lokal ke branch `dev`.
- **Testing**: Verifikasi konektivitas antar komponen (API membalas request dari Admin/Petugas/Mobile di server dev).

### 3. Gerbang STAGING (UAT & Parity)
- **Prosedur**: Sinkronkan branch `dev` ke `staging`.
- **Penting**: Jalankan `php testing/stg5_document_integrity.php` untuk memastikan TTE dan PDF berjalan di server staging.

### 4. Gerbang PRODUCTION (Final Deployment)
- **Peringatan**: Database Production tidak boleh disinkronkan (overwrite) sampai pengujian Staging selesai dan dikonfirmasi oleh user.
- **Sync**: Hanya lakukan sync kode sumber (PHP/JS).

## ⚠️ Protokol Destruktif (Safety First)
Jika Anda mendeteksi perintah atau script yang mengandung `DROP`, `TRUNCATE`, atau `DELETE` massal:
1. **STOP** eksekusi.
2. **WARNING**: Berikan pesan peringatan keras kepada user:
   > ⚠️ **CAUTION**: Operasi ini akan menghapus data historis penagihan yang sangat krusial! Apakah Anda yakin ingin melanjutkan?
3. **KONFIRMASI**: Tunggu jawaban "Ya" atau "Lanjutkan" sebelum mengeksekusi.

## 🛠️ Tooling & Scripts
Gunakan script berikut secara rutin:
- `testing/qa7_ultimate_mpad_audit.php`: Audit Struktur & Route.
- `testing/qa11_citizen_e2e_flow.php`: Simulasi pengalaman warga (Mobile).
- `testing/run_role_e2e_test.php`: Simulasi alur Admin -> Petugas.
