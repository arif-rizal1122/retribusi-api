---
name: Project Testing Standards
description: Panduan dan aturan untuk melakukan pengujian (testing) pada project ini, mengacu pada TESTING_GUIDE.md dan standar /noss.
---

# Instruksi Pengujian (Testing Standards)
Setiap kali Anda diminta untuk melakukan pengujian (testing) atau memvalidasi fitur baru di project ini, Anda **WAJIB** mengikuti panduan berikut:

## Aturan Utama
1. **Patuhi aturan `/noss` (No Screenshot).** Semua pengujian HARUS terotomatisasi dan diverifikasi melalui script atau terminal. Dilarang mengandalkan verifikasi visual (screenshot) CLI/browser.
2. Gunakan perintah `curl`, `assert`, `grep`, atau jalankan script CLI (`bash`/`php`) untuk memvalidasi perubahan pada Database dan Response Body.
3. Selalu rujuk ke `docs/TESTING_GUIDE.md` untuk informasi kredensial akun demo dan panduan testing yang ada.
4. Laporan hasil testing dari bash script biasanya tercatat di folder `testing/results/`.
5. Jika membuat skenario test via PHP PHPUnit, tempatkan di folder `tests/Unit` atau `tests/Feature` dan pastikan mematuhi best practices (Gunakan `RefreshDatabase` atau hindari merusak database production).

## Eksekusi Script Testing
Terdapat script shell yang sudah disiapkan di folder `testing/`:
- `testing/test_production_ready.sh` (Health check infrastruktur)
- `testing/test_api_crud.sh` (Validasi CRUD API)
- `testing/test_penetration.sh` (Audit Keamanan)

Jika Anda perlu membuat test baru untuk kasus spesifik, buat script bash/PHP di folder `testing/` atau buat class test di `tests/Feature/`. Pastikan tidak merusak skenario test utama yang telah ada.
