# Log Perubahan Project Bapeda - v2 (20 Feb 2026)

Dokumen ini mencatat perbaikan khusus terkait fitur Kalkulator PBB pada aplikasi mobile.

## 1. Resolusi Error 422 (Unprocessable Content) - Kalkulasi PBB
Ditemukan error 422 saat melakukan simulasi perhitungan PBB melalui aplikasi mobile.

- **Penyebab**: API `/api/pbb/calculate` membutuhkan parameter wajib seperti `luas_bumi`, `kelas_bumi`, `luas_bangunan`, dan `kelas_bangunan`. Namun, pada aplikasi mobile, field-field ini tidak muncul di modal simulasi karena tidak terdefinisi dalam `form_schema` layanan PBB-P2 di database.
- **Solusi**: Saya telah memperbarui field `form_schema` pada tabel `retribution_classifications` untuk record **PBB-P2** (ID: 2).
- **Update Skema**: Menambahkan field berikut ke dalam form:
  - `luas_bumi` (Number)
  - `kelas_bumi` (Text)
  - `luas_bangunan` (Number)
  - `kelas_bangunan` (Text)
- **Hasil**: Sekarang tombol "Simulasi" di detail layanan PBB aplikasi mobile akan menampilkan input yang lengkap, sehingga perhitungan dapat dilakukan tanpa error.

## 2. Sinkronisasi Database
Perubahan ini dilakukan langsung pada database lokal menggunakan `artisan tinker` untuk memastikan fitur simulasi berjalan segera (hotfix).

---
*Dibuat oleh Assistant untuk membantu tracking alur pengembangan.*
