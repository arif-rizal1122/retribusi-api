---
name: Production Deployment Manager
description: Skill khusus untuk manajemen deployment, monitoring, dan emergency recovery pada lingkungan production Pemkot Baubau (*.baubaukota.go.id & sipanda.online).
---

# 🚀 Production Deployment Manager

Skill ini adalah gerbang terakhir sebelum kode dipublikasikan ke masyarakat luas. Anda wajib menggunakan skill ini untuk memastikan transisi dari Staging ke Production berjalan mulus.

## 📋 Tugas Utama & Alur Kerja
Ikuti alur kerja ini untuk setiap deployment ke Production:

1. **Pre-Deployment Audit**:
   - Baca `docs/07_testing_kualitas/PRODUCTION_READY_CHECKLIST.md`.
   - Jalankan `php testing/qa7_ultimate_mpad_audit.php` di lingkungan Staging.
2. **Main Branch Locking**:
   - Pastikan seluruh pengujian di Staging berstatus Lulus.
   - Lakukan merge dari `dev` ke `main`.
3. **Execution Safety**:
   - Jangan pernah menjalankan `migrate:fresh` atau `truncate` di Production.
   - Hanya gunakan `php artisan migrate` untuk penambahan skema.
4. **Post-Deployment Monitoring**:
   - Verifikasi endpoint `/api/health` (jika ada) atau hit `/api/user` untuk memastikan server up.

## 🛡️ Aturan Keamanan (Hard Rules)
- **Database Isolation**: Dilarang menyalin data user atau transaksi dari lingkungan testing ke Production.
- **Credential Protection**: Jangan pernah melakukan commit file `.env` atau kunci API ke repositori.
- **Rollback Readiness**: Selalu identifikasi commit ID terakhir yang stabil sebelum melakukan push terbaru.

## 🧠 Cara Kerja & Mekanisme Maksimal
Skill ini bekerja dengan menggabungkan audit statis (pembacaan dokumen) dan audit dinamis (eksekusi script). Untuk mendapatkan hasil maksimal:
1. **Berikan Konteks**: Sebelum memulai deployment, beri tahu agen: *"Lakukan audit kesiapan produksi untuk fitur [Nama Fitur]"*.
2. **Verifikasi Bertahap**: Agen akan memeriksa apakah testing lokal dan staging sudah dilakukan.
3. **Konfirmasi Destruktif**: Agen akan meminta konfirmasi eksplisit sebelum melakukan perintah yang mengubah database production.

## 🛠️ Tooling
- `testing/production_verify_remote.php`: Verifikasi integritas file di server production.
- `testing/stg1_health_check.php`: Health check menyeluruh.
