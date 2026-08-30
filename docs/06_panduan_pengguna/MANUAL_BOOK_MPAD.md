# BUKU PANDUAN PENGGUNA (MANUAL BOOK)
# SISTEM INFORMASI MITRA PENDAPATAN ASLI DAERAH (M-PAD)
### PEMERINTAH KOTA BAUBAU — BADAN PENDAPATAN DAERAH (BAPENDA)

---

## DAFTAR ISI
- [BAB I: PENDAHULUAN](#bab-i-pendahuluan)
  - 1.1 Latar Belakang & Dasar Hukum
  - 1.2 Maksud dan Tujuan
  - 1.3 Ruang Lingkup Sistem
- [BAB II: DESKRIPSI SISTEM & ARSITEKTUR](#bab-ii-deskripsi-sistem--arsitektur)
  - 2.1 Ekosistem 4 Aplikasi M-PAD
  - 2.2 Hak Akses & Peran Pengguna (RBAC)
  - 2.3 Persyaratan Perangkat Keras & Lunak
- [BAB III: PANDUAN PENGGUNAAN PER PERAN (USER GUIDE)](#bab-iii-panduan-penggunaan-per-peran-user-guide)
  - 3.1 Panduan Wajib Pajak / Warga (`retribusi-mobile`)
    - 3.1.1 Registrasi Akun & Validasi IKD
    - 3.1.2 Pendaftaran Objek Pajak Mandiri (SPOP / Reklame)
    - 3.1.3 Pengecekan Tagihan & Keranjang Multi-Billing
    - 3.1.4 Pembayaran Digital (QRIS Dinamis & BRIVA)
    - 3.1.5 Pengunduhan Bukti Bayar Sah (SSPD) & Rating Layanan
  - 3.2 Panduan Petugas Lapangan (`retribusi-petugas`)
    - 3.2.1 Login Petugas & Penugasan Harian
    - 3.2.2 Peta Lapangan & Pelacakan Objek Pajak (GIS)
    - 3.2.3 Quick Scan QR & Pencarian Identitas Wajib Pajak
    - 3.2.4 Penerimaan Pembayaran Tunai di Lapangan
    - 3.2.5 Cetak Resi Thermal 58mm (Bluetooth ESC/POS)
    - 3.2.6 Survei Lapangan & Pengisian Formulir LSPOP
  - 3.3 Panduan Verifikator & Admin OPD (`retribusi-admin`)
    - 3.3.1 Validasi Pelaporan Mandiri (SPTPD & Bukti Bayar)
    - 3.3.2 Penerbitan Ketetapan (SKRD, SPPT, SKPDKB)
    - 3.3.3 Rekonsiliasi Gateway & Monitoring Pendapatan
  - 3.4 Panduan Superadmin (`retribusi-admin`)
    - 3.4.1 Pengelolaan Master Tarif, Klasifikasi & Zonasi
    - 3.4.2 Manajemen Pengguna, Hak Akses & Tanda Tangan Elektronik (TTE)
    - 3.4.3 Ekspor Laporan Keuangan SIPD & Log Audit
- [BAB IV: TROUBLESHOOTING & PUSAT BANTUAN](#bab-iv-troubleshooting--pusat-bantuan)
  - 4.1 Pertanyaan yang Sering Diajukan (FAQ)
  - 4.2 Panduan Kendala Printer Thermal Bluetooth
  - 4.3 Kontak Helpdesk & Dukungan Teknis

---

## BAB I: PENDAHULUAN

### 1.1 Latar Belakang & Dasar Hukum
Dalam rangka modernisasi pengelolaan Pendapatan Asli Daerah (PAD) serta mendukung transparansi pemungutan pajak dan retribusi di Kota Baubau, Bapenda Kota Baubau meluncurkan platform **M-PAD (Mitra Pendapatan Asli Daerah)**. 

Dasar hukum penyelenggaraan sistem ini meliputi:
1. **Undang-Undang Nomor 1 Tahun 2022** tentang Hubungan Keuangan antara Pemerintah Pusat dan Pemerintahan Daerah (HKPD).
2. **Peraturan Daerah Kota Baubau Nomor 1 Tahun 2024** tentang Pajak Daerah dan Retribusi Daerah.
3. **Peraturan Wali Kota Baubau Nomor 11 Tahun 2025** tentang Tata Cara Pemungutan Pajak Reklame.
4. **Peraturan Wali Kota Baubau Nomor 58 Tahun 2024** tentang Petunjuk Pelaksanaan Pajak Daerah dan Retribusi Daerah.

### 1.2 Maksud dan Tujuan
Buku panduan ini disusun untuk memberikan petunjuk teknis operasional yang jelas, sistematis, dan terstandarisasi bagi seluruh pemangku kepentingan: Wajib Pajak, Petugas Pemungut Lapangan, Pejabat OPD Pengelola, hingga Administrator Sistem.

### 1.3 Ruang Lingkup Sistem
Sistem M-PAD mencakup seluruh siklus pemungutan:
- Pendataan dan pendaftaran objek pajak/retribusi daerah secara spasial (GIS).
- Penilaian dan penetapan tagihan otomatis berdasarkan formula Perda.
- Pembayaran terintegrasi Multi-Bank (H2H Bank BRI, BRIVA, QRIS Dinamis).
- Penagihan lapangan dengan pencetakan bukti bayar instan (Thermal Printer).
- Pelaporan real-time, audit trail, serta integrasi SIPD RI.

---

## BAB II: DESKRIPSI SISTEM & ARSITEKTUR

### 2.1 Ekosistem 4 Aplikasi M-PAD
1. **M-PAD Warga (`retribusi-mobile`)**: Aplikasi Progressive Web App (PWA) yang ditujukan bagi Wajib Pajak / Wajib Retribusi untuk mengakses seluruh layanan perizinan pajak, pelaporan, dan pembayaran dari smartphone.
2. **M-PAD Petugas (`retribusi-petugas`)**: Aplikasi operasional lapangan berbasis mobile/tablet untuk pemungutan retribusi, verifikasi survei fisik, dan pencetakan struk tanda terima resmi.
3. **M-PAD Admin (`retribusi-admin`)**: Dashboard web manajemen berbasis desktop untuk verifikasi ketetapan, monitoring penerimaan PAD harian, konfigurasi tarif, dan approval TTE.
4. **M-PAD Core API (`retribusi-api`)**: Mesin pemrosesan transaksi pusat, database, gateway perbankan, dan validasi keamanan.

### 2.2 Hak Akses & Peran Pengguna (RBAC)
- **Wajib Pajak (Citizen)**: Mengelola objek pajak miliknya, menerbitkan invoice, membayar via QRIS/VA.
- **Petugas Lapangan (Officer)**: Memiliki hak verifikasi objek di wilayah tugasnya, menerima bayaran tunai, dan mencetak SSPD thermal.
- **Verifikator OPD**: Memeriksa berkas permohonan dan pelaporan SPTPD dari wajib pajak.
- **Admin OPD**: Menerbitkan SKRD/SKPDKB dan memonitor target realisasi OPD terkait.
- **Superadmin (Bapenda)**: Memiliki akses penuh terhadap konfigurasi sistem, tarif, user management, dan audit trail.

### 2.3 Persyaratan Perangkat Keras & Lunak
- **Aplikasi Warga & Petugas**:
  - Smartphone Android (v8.0+) atau iOS (v14.0+) dengan koneksi internet aktif.
  - Browser: Google Chrome, Safari, atau Microsoft Edge versi terbaru.
  - Perangkat Tambahan Petugas: Printer Thermal Bluetooth 58mm (ESC/POS compatible).
- **Dashboard Admin**:
  - Komputer / Laptop dengan resolusi minimal 1366x768 piksel.
  - Browser modern dengan dukungan HTML5 & PDF Viewer.

---

## BAB III: PANDUAN PENGGUNAAN PER PERAN (USER GUIDE)

### 3.1 Panduan Wajib Pajak / Warga (`retribusi-mobile`)

#### 3.1.1 Registrasi Akun & Validasi IKD
1. Buka alamat aplikasi: `https://m-pad.baubaukota.go.id` pada browser ponsel Anda.
2. Klik tombol **Daftar Akun Baru**.
3. Masukkan NIK (Nomor Induk Kependudukan), Nama Lengkap, Nomor WhatsApp, dan Kata Sandi.
4. Masukkan kode verifikasi OTP 6-digit yang dikirimkan ke WhatsApp Anda.
5. Akun siap digunakan. Profil Anda otomatis tersinkronisasi.

#### 3.1.2 Pendaftaran Objek Pajak Mandiri (SPOP / Reklame)
1. Pilih menu **Layanan** → Pilih jenis pajak yang ingin didaftarkan (misal: **PBB-P2** atau **Pajak Reklame**).
2. Tentukan titik lokasi objek pada peta interaktif (**MapPicker**).
3. Isi ukuran fisik (contoh Reklame: Panjang, Lebar, Sisi; contoh PBB: Luas Tanah & Bangunan).
4. Unggah foto lokasi fisik dan dokumen kepemilikan (Sertifikat/AJB/Desain Reklame).
5. Klik **Kirim Pendaftaran**. Anda akan mendapatkan nomor registrasi pelacakan.

#### 3.1.3 Pengecekan Tagihan & Keranjang Multi-Billing
1. Buka menu **Tagihan Saya**.
2. Sistem akan menampilkan seluruh tagihan aktif beserta rincian pokok dan denda (jika ada).
3. Centang satu atau beberapa tagihan yang ingin dibayar sekaligus, lalu klik **Bayar Sekarang**.

#### 3.1.4 Pembayaran Digital (QRIS Dinamis & BRIVA)
1. Pada halaman konfirmasi, pilih metode pembayaran yang diinginkan:
   - **QRIS Dinamis**: Tampilkan kode QR di layar dan scan menggunakan BCA Mobile, Livin, GoPay, OVO, Dana, ShopeePay, dsb.
   - **Virtual Account BRI (BRIVA)**: Salin 16-digit nomor VA dan bayar via BRImo, ATM BRI, atau Agen BRILink.
2. Status pembayaran akan otomatis terverifikasi secara *real-time* (idempotent) tanpa perlu upload bukti transfer.

#### 3.1.5 Pengunduhan Bukti Bayar Sah (SSPD) & Rating Layanan
1. Setelah pembayaran berhasil, klik tombol **Unduh Bukti Bayar (SSPD)**.
2. Dokumen PDF ber-barcode resmi dan bernomor NTPD akan tersimpan di perangkat Anda.
3. Berikan penilaian bintang dan ulasan kepuasan layanan Bapenda Kota Baubau.

---

### 3.2 Panduan Petugas Lapangan (`retribusi-petugas`)

#### 3.2.1 Login Petugas & Penugasan Harian
1. Buka aplikasi M-PAD Petugas di ponsel/tablet.
2. Masukkan NIP/Email dan Password resmi yang diberikan Bapenda.
3. Pada halaman beranda, periksa daftar target tugas penagihan harian Anda.

#### 3.2.2 Peta Lapangan & Pelacakan Objek Pajak (GIS)
1. Buka menu **Peta Lapangan**.
2. Peta akan menampilkan titik-titik objek pajak di sekitar lokasi GPS Anda (Pin Hijau: Lunas, Pin Merah: Menunggak).
3. Klik pada pin objek untuk melihat rincian nama pemilik dan jumlah tunggakan.

#### 3.2.3 Quick Scan QR & Pencarian Identitas Wajib Pajak
1. Klik tombol **Scanner QR** di navigasi bawah.
2. Arahkan kamera ke QR Code milik Wajib Pajak atau lembar SKRD fisik.
3. Aplikasi akan langsung membuka data identitas Wajib Pajak dan seluruh tagihannya.

#### 3.2.4 Penerimaan Pembayaran Tunai di Lapangan
1. Pilih tagihan yang akan dibayar pada halaman **Bayar Lapangan**.
2. Pilih metode pembayaran: **Tunai (Cash / Lapangan)**.
3. Klik **Konfirmasi Lunas**. Status tagihan seketika berubah menjadi `Lunas` di server Bapenda.

#### 3.2.5 Cetak Resi Thermal 58mm (Bluetooth ESC/POS)
1. Pastikan printer thermal mini 58mm menyala dan Bluetooth aktif.
2. Setelah konfirmasi lunas, klik tombol **Cetak Resi**.
3. Struk tanda terima resmi berlogo Bapenda Kota Baubau akan tercetak seketika dan dapat langsung diserahkan kepada Wajib Pajak.

---

### 3.3 Panduan Verifikator & Admin OPD (`retribusi-admin`)

#### 3.3.1 Validasi Pelaporan Mandiri (SPTPD)
1. Masuk ke Dashboard Admin → Menu **Verifikasi Pajak**.
2. Periksa dokumen pendukung yang diunggah wajib pajak.
3. Cocokkan nominal omzet atau spesifikasi teknis reklame.
4. Klik **Setujui** untuk menerbitkan tagihan atau **Tolak** dengan menyertakan alasan revisi.

#### 3.3.2 Penerbitan Ketetapan (SKRD, SPPT, SKPDKB)
1. Buka menu **Billing & Penetapan**.
2. Pilih penetapan massal atau per objek.
3. Sistem secara otomatis menerapkan formula tarif Perda 1/2024 dan menghasilkan nomor SKRD resmi.

---

### 3.4 Panduan Superadmin (`retribusi-admin`)

#### 3.4.1 Pengelolaan Master Tarif, Klasifikasi & Zonasi
1. Buka menu **Master Data** → **Klasifikasi & Tarif Retribusi**.
2. Administrator dapat menambah klasifikasi baru, mengatur rumus kalkulasi dinamis, serta mengunggah batas poligon zonasi.

#### 3.4.2 Tanda Tangan Elektronik (TTE) & Sertifikat Digital
1. Buka menu **Pengaturan TTE**.
2. Kelola sertifikat digital pejabat yang berwenang menandatangani SKRD / SSPD / SPPT digital secara sah sesuai regulasi BSrE / BSSN.

---

## BAB IV: TROUBLESHOOTING & PUSAT BANTUAN

### 4.1 Pertanyaan yang Sering Diajukan (FAQ)
- **Q: Apakah QRIS M-PAD bisa menerima pembayaran dari semua bank?**  
  *A: Ya, QRIS M-PAD adalah QRIS Nasional (NMID resmi) yang dapat dipindai oleh seluruh aplikasi mobile banking dan e-wallet di Indonesia.*
- **Q: Bagaimana jika koneksi internet terputus saat petugas sedang mencetak resi?**  
  *A: Transaksi yang sudah tersimpan dapat dicetak ulang kapan saja melalui menu Billing → Riwayat Bayar → Cetak Ulang Resi.*

### 4.2 Panduan Kendala Printer Thermal Bluetooth
1. Pastikan izin Bluetooth aktif pada browser (disarankan Google Chrome di Android).
2. Buka menu **Printer Thermal** di aplikasi Petugas.
3. Klik tombol **Cari & Hubungkan Printer** → Pilih nama printer Anda (misal: `MPT-II`, `RPP02N`, `POS-58`).
4. Tekan **Cetak Struk Uji Coba** untuk memastikan koneksi stabil.

### 4.3 Kontak Helpdesk & Dukungan Teknis
- **Badan Pendapatan Daerah (Bapenda) Kota Baubau**
- Alamat: Kompleks Perkantoran Bumi Praja Palagimata, Kota Baubau, Sulawesi Tenggara.
- Layanan Pengaduan WhatsApp: `0811-400-XXXX`
- Email Dukungan: `bapenda@baubaukota.go.id`
- Portal Resmi: `https://bapenda.baubaukota.go.id`
