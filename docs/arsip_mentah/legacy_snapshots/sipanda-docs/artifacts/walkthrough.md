# Walkthrough: Profile Picture Update Fix

I have resolved the 422 "Unprocessable Content" error when updating profile pictures and implemented the missing avatar upload functionality in the Admin application.

## User Acceptance Testing (UAT) Skenario

Berikut adalah panduan bagi pengguna atau QA untuk melakukan pengujian:

| Fitur / Card | Skenario Pengujian | Hasil yang Diharapkan |
| :--- | :--- | :--- |
| **SPPT Online** | Masuk ke menu Billing, cari tagihan PBB, lalu klik "Cetak SPPT". | PDF berhasil diunduh dan menampilkan rincian NJOP. |
| **Profile Fix (Admin)** | Buka halaman Profil di Sidebar. Ubah foto profil menggunakan ikon kamera baru. | Foto profil berubah di halaman profil dan di header aplikasi. |
| **Profile Fix (Mobile)** | Buka aplikasi Mobile, masuk ke Profil, dan coba ubah foto profil. | Tidak ada error 422, dan foto berhasil tersimpan di server. |
| **Cetak Thermal** | Gunakan aplikasi Petugas, pilih transaksi yang sudah lunas, dan klik Cetak via Bluetooth. | Printer thermal berhasil terhubung dan mencetak struk. |

## Verification Results

### Backend Verification
Ran tinker command to verify model configuration:
```bash
User metadata fillable: Yes
User metadata cast: array
```

### Manual Verification
- [x] **Mobile App**: Profile update request now sends correct multipart data (boundary string included).
- [x] **Admin App**: Profile page now displays the user's avatar and allows changing it with immediate preview.
- [x] **Database**: Avatars are successfully stored in the `users` table's `metadata` column.

render_diffs(file:///Users/pondokit/Herd/retribusi-api/database/migrations/2026_02_19_060000_add_metadata_to_users_table.php)
render_diffs(file:///Users/pondokit/Herd/retribusi-api/app/Models/User.php)
render_diffs(file:///Users/pondokit/Herd/retribusi-api/app/Http/Controllers/MeController.php)
render_diffs(file:///Users/pondokit/Herd/retribusi-mobile/src/pages/UserProfile.tsx)
render_diffs(file:///Users/pondokit/Herd/retribusi-admin/src/pages/Profile.tsx)
