# Checklist Pengujian Sistem MITRA

Gunakan checklist ini untuk memverifikasi seluruh perubahan fitur dan rebranding yang telah diimplementasikan.

## 1. Rebranding MITRA (Visual & Identity)
- [ ] **Admin App**: Pastikan title bar browser bertuliskan "MITRA Admin" dan logo di halaman Login adalah "MITRA".
- [ ] **Mobile App**: Pastikan onboarding slide pertama menyebutkan "MITRA" dan tagline baru.
- [ ] **Petugas App**: Pastikan logo di header dan halaman landing sudah berganti menjadi "MITRA Petugas".
- [ ] **PDF Dokumen**: Generate satu SSPD dan pastikan footer mencantumkan "MITRA (Mitra PAD...)".
- [ ] **Link Integrity**: Pastikan fitur login tetap berjalan (api.sipanda.online tidak berubah).

## 2. Fitur Profil & Foto (Admin & Mobile)
- [ ] **Admin Profile**: 
    - [ ] Buka Pengaturan Profil -> Pilih Foto -> Simpan. 
    - [ ] Pastikan foto terupdate di header (kanan atas) tanpa refresh halaman.
- [ ] **Mobile Profile**:
    - [ ] Ke menu Akun -> Klik ikon Kamera -> Upload Foto.
    - [ ] Pastikan tidak ada error 422 (Unprocessable Content) dan foto muncul di kartu profil.

## 3. Dokumentasi Viewer (`/docs`)
- [ ] **Aksesibilitas**: Buka `http://localhost:8000/docs`.
- [ ] **Navigasi**: Klik menu **Public**, **Core**, **Penalty**, dan **Advanced**. Pastikan konten Markdown tampil sempurna.
- [ ] **README**: Pastikan link "Project Artifacts" di bagian bawah README mengarah ke list task pengerjaan.

## 4. Backend Logic & Penalty
- [ ] **Penalty Calculation**:
    - [ ] Jalankan perintah: `php artisan bills:calculate-penalties`.
    - [ ] Cek database/UI: Pastikan tagihan jatuh tempo bertambah nilai dendanya (1% per bulan).
- [ ] **Denda Lapor**: Cek tagihan Pajak Restoran (Self Assessment) yang belum lapor omzet, pastikan denda Rp 100.000 terhitung.
- [ ] **Amnesty Workflow**: Cek menu Amnesty di Admin, pastikan request bisa di-approve oleh level Kabid/Superadmin.

## 5. Sinkronisasi Data
- [ ] **Git Check**: Pastikan branch `dev` di Git sudah sinkron dengan pengerjaan lokal terakhir.
