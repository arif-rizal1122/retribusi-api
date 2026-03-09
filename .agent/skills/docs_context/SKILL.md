---
name: Documentation Context & Maintenance
description: Menjaga konsistensi pemahaman AI tentang arsitektur sistem dari folder docs/ dan aturan memperbarui dokumentasi.
---

# Instruksi Manajemen Dokumen & Konteks
Saat bekerja dalam project ini, terutama untuk fitur besar atau ketika diminta mempelajari konteks arsitektur sistem, Anda harus mematuhi aturan dokumentasi berikut:

## Rujukan Pengetahuan Utama
Sebelum membuat penyesuaian logika bisnis besar atau arsitektural, periksa dokumen berikut di folder `docs/`:
1. `docs/system-knowledge.md` - Untuk mempelajari struktur Models, Services, dan Commands.
2. `docs/routes-and-components.md` - Untuk melihat pemetaan Endpoint API.
3. `docs/04-database-schema.md` & `docs/DOMAIN_SCHEMA.md` - Untuk memahami referensi tabel dan skema relasi.
4. `docs/TESTING_GUIDE.md` - Untuk standar eksekusi dan instruksi testing.

## Aturan Pembaruan Dokumentasi
1. Jika Anda mengubah struktur Database (Migration/Seeder), Anda **wajib** memperbarui file `docs/04-database-schema.md` atau `DOMAIN_SCHEMA.md` jika relevan.
2. Jika Anda menambahkan/mengubah rute API atau Controller, perbarui `docs/routes-and-components.md` agar pemetaan API tetap relevan.
3. Selalu pastikan pembaruan menggunakan standar markdown proyek.
4. Catat setiap perubahan atau modifikasi fitur yang signifikan di file `CHANGE_LOG.md` (di root directory).
5. Jangan berasumsi arsitektur bisnis tanpa merujuk ke direktori `docs/` terlebih dahulu.
