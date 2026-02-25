# Checklist Pengujian Sistem MITRA

Gunakan checklist ini untuk memverifikasi seluruh perubahan fitur dan rebranding yang telah diimplementasikan.

> **Standar Skema Pengujian:**
> Seluruh poin pengujian di bawah ini (serta di seluruh dokumentasi folder `testing/`) wajib mematuhi standar validitas *No Screenshot* dengan mengeksekusi 5 lapisan: **Unit Testing, Feature Testing, API Testing, CRUD Testing, dan UI Testing**. Rujuk panduan metodologi lengkap pada berkas: [`testing/00-Panduan-Standar-Pengujian.md`](file:///Users/pondokit/Herd/retribusi-api/testing/00-Panduan-Standar-Pengujian.md).

## 1. Rebranding MITRA (Visual & Identity)
- [ ] **Admin App**: Pastikan title bar browser bertuliskan "MITRA Admin" dan logo di halaman Login adalah "MITRA".
- [ ] **Mobile App**: Pastikan onboarding slide pertama menyebutkan "MITRA" dan tagline baru.
- [ ] **Petugas App**: Pastikan logo di header dan halaman landing sudah berganti menjadi "MITRA Petugas".
- [ ] **PDF Dokumen**: Generate satu SSPD dan pastikan footer mencantumkan "MITRA (Mitra PAD...)".
- [ ] **Link Integrity**: Pastikan fitur login tetap berjalan (api.sipanda.online tidak berubah).

## 2. Pengujian Sistem Upload File (Lintas Platform)
- [ ] **Admin App (Web)**: 
    - [ ] Buka Pengaturan Profil -> Pilih Foto -> Simpan. Pastikan validasi ekstensi jpeg, png, heic ukuran <= 5MB tertangani dengan baik.
    - [ ] Verifikasi foto avatar di Navbar Header terupdate seketika (tanpa perlu _refresh_ manual).
- [ ] **Mobile App (Wajib Pajak)**:
    - [ ] Arahkan ke menu **Akun** -> Klik ikon Kamera -> Upload Foto.
    - [ ] Pastikan tidak ada _error Unprocessable Content_ 422 dan foto tampil elegan di kartu profil.
- [ ] **Petugas App (On-Site Billing)**:
    - [ ] Pada modal **Konfirmasi Pembayaran**, unggah file *Bukti Pembayaran (Opsional)* dari galeri / tangkapan kamera _smartphone_.
    - [ ] Verifikasi indikator _loading_ "MENGUNGGAH BUKTI..." dan pratinjau _thumbnail_ tampil. Pastikan URL Cloudinary terlampir sukses di _payload_ API pembayaran.

## 3. Skenario Pengujian Tupoksi Role (Multi-Frontend)

### A. Admin OPD & Super Admin (Frontend: `retribusi-admin`)
- [ ] **Super Admin**: Pastikan seluruh menu Sidebar terbuka sempurna (Manajemen OPD, User, Layanan Retribusi, dll). Coba fungsionalitas _Approve_ relaksasi/Amnesty Pajak.
- [ ] **Admin OPD (Dinas Terkait)**: 
    - [ ] Pastikan login profil OPD hanya memuat Objek Pajak, WP, dan Laporan Rekapitulasi di bawah wewenang dinasnya (*data isolation*).
    - [ ] Verifikasi grafik Dashboard (revenue, potensial) teraplikasikan berdasarkan filter `opd_id`.
- [ ] **Penetapan Pajak (Official Assessment)**: Admin menerbitkan tagihan SKPD secara masal maupun spesifik entitas, dan memonitor status tunggakannya.

### B. Petugas Lapangan Ops (Frontend: `retribusi-petugas`)
- [ ] **Akses Terbatas Teritori**: Buka Dashboard & Peta Lapangan. Verifikasi sebaran Marker WP yang muncul mutlak berada pada wilayah zonasi teguran Petugas (`officerZoneIds`) serta spesifik ke klasifikasi tipe pajaknya.
- [ ] **On-Site Billing Simulator**:
    - [ ] Datangi WP Belum Bayar via Peta -> "Buat SKPD Baru".
    - [ ] Isi Form Kalkulator di `/calculator` -> tekan "Buat & Bayar SKPD".
    - [ ] Konfirmasi Pembayaran Tunai, unggah lampiran bukti opsional, dan terima notifikasi sukes.
- [ ] **Mobilitas Kasir**: Buka Menu Billing Indeks, coba unduh tanda terima dan validasi aksi cetak resi SSPD / SKRD via *Print Button*.

### C. Wajib Pajak (Frontend: `retribusi-mobile` / Citizen Portal)
- [ ] **Self-Assessment (Pajak Restoran)**: Wajib Pajak melaporkan omzet harian/bulanan secara mandiri (SPTPD).
- [ ] **Pembayaran Digital**:
    - [ ] Masuk ke menu Tagihan -> Munculkan ID Billing.
    - [ ] Pilih metode bayar VA / QRIS -> Simulasikan setoran lunas di eksekusi backend.
    - [ ] Periksa notifikasi "Tagihan Lunas" dengan status _badge_ hijau.
- [ ] **Penalty Otomasi (Denda Lapor/Bayar)**:
    - [ ] Sengaja melampaui masa `due_date` pelaporan SPTPD (*Self Assessment*).
    - [ ] Jalankan _command_ `php artisan bills:calculate-penalties`. Pastikan Sanksi Administratif (SKPDKB / denda flat 100k) diterapkan dalam total perhitungan.

## 4. Dokumentasi Viewer (`/docs`)
- [ ] **Aksesibilitas & Integritas**: Buka `http://localhost:8000/docs`, telusuri struktur hirarki *Public, Core, Penalty*.
- [ ] **Navigasi Bantuan**: Cek tautan repositori menuju Artifacts Markdown untuk merangkum hasil kerja harian _Developer_ kepada atasan QA.

## 5. Sinkronisasi Kesatuan Sistem
- [ ] Cek status *commit* Tree Backend & Frontend di Git untuk serah terima tahap Alpha Testing.
