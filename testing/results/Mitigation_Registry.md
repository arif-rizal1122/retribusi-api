# 🛡️ Buku Catatan Mitigasi Error Uji Ekosistem MPAD (QA Registry)

Dokumen ini adalah rekam jejak penyelesaian masalah (Troubleshooting Log) dari error yang muncul selama masa testing. AI dan Pengembang wajib membaca file ini sebelum melakukan _debugging_ agar tidak mengulangi kesalahan yang sama.

---

### [BUG-001] Validasi Sub-Admin Gagal (Null Property) - 09 Mar 2026
- **Environment**: Local
- **Endpoint/Kasus**: `POST /api/petugas-tasks` (QA 3: Validasi Penugasan Lintas Wilayah)
- **Deskripsi Error**: HTTP 500 Server Error - `Attempt to read property "retribution_type_id" on null in PetugasTaskController.php:54`.
- **Akar Masalah (Root Cause)**: Admin 1 mencoba mencari profil `Petugas 2` untuk di-_assign_. Namun, sistem *Global Scope* (`RetributionTypeScope`) milik Laravel aktif membajak kueri pencarian. Scope ini otomatis menyembunyikan Petugas 2 dari pandangan Admin 1 karena beda zona/tipe pajak. Akibatnya, `User::find()` mengembalikan nilai `null`.
- **Solusi (Mitigation)**: Mematikan *Global Scope* sesaat sebelum mencari pengguna yang ditargetkan untuk validasi _role_ menggunakan chain perintah `User::withoutGlobalScope(RetributionTypeScope::class)->find($id)`. Kemudian ditambahkan validasi eksplisit `if (!$targetUser) return 404;` untuk memastikan objek aman dieksekusi sebelum ditolak via Error 403.
