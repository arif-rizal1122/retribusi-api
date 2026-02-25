# 🤖 AI Context & Prompt Anatomy Guide

Dokumen ini adalah acuan utama bagi AI Agent untuk bekerja secara efisien di proyek M-PAD.

## 1. Anatomi Prompt (Prompt Anatomy Schema)
Setiap kali AI Agent memulai tugas baru atau memberikan solusi, gunakan skema berikut sebagai acuan pengerjaan:

| Segment | Deskripsi / Constraint |
| :--- | :--- |
| **Objective** | Fokus pada stabilitas lintas 8 domain (Prod/Dev) dan kepatuhan branding M-PAD. |
| **Context Aware** | Selalu periksa `SYSTEM_OVERVIEW.md` untuk alur Git-Flow dan `MITIGATION_GUIDE.md` untuk riwayat bug (CORS/500). |
| **Tool Choice** | Prioritaskan `curl -I` untuk validasi status dan `git pull` manual di VPS jika GitHub Actions gagal. |
| **Action Rule** | Gunakan **Positional Arguments** pada Laravel Middleware (kompatibilitas PHP VPS). |
| **Verification** | Wajib menjalankan script di folder `testing/` sebelum menandai tugas selesai. |

## 2. Peta Jalan Konteks (Shortcut)
- **`docs/SYSTEM_OVERVIEW.md`**: Arsitektur, Domain, dan Alur Kerja.
- **`docs/INFRASTRUCTURE_NOTES.md`**: Detail VPS dan Maintenance.
- **`docs/MITIGATION_GUIDE.md`**: Solusi eror masa lalu (Wajib Baca).
- **`docs/TESTING_GUIDE.md`**: Akun demo dan prosedur pengujian.

## 3. Fakta Cepat (Zero-Research Facts)
- **Framework:** Laravel 11 (API), React Vite (Frontend).
- **Environment:** 2 Env (Prod & Dev), 8 Domain Total pada IP `157.10.252.74`.
- **Autentikasi:** Laravel Sanctum (Bearer Token).
- **Branding:** Nama resmi: **M-PAD** (Manajemen Integrasi Tax, Retribusi, dan Aset Daerah).
- **CORS Rule:** Nginx menangani `OPTIONS`, Laravel menangani request utama.

## 4. Efisiensi Token
1. **Paham Struktur:** Dokumentasi terpusat di `/docs`. Jangan lakukan `find` berulang pada direktori yang sama.
2. **Minimalisir Read:** Jangan membaca kembali file yang sudah pernah di-view dalam satu percakapan jika isinya tidak berubah.
3. **Verifikasi Ringan:** Gunakan endpoint `/up` untuk cek kesehatan sistem secara berkala.

---
*Anatomy v1.0. Terakhir diperbarui: 26 Februari 2026.*
