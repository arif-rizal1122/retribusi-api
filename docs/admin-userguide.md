# 📄 Panduan Pengguna Lengkap (Admin) - Mpad Kota Baubau

Selamat datang di Panduan Pengguna **Mpad (Sistem Informasi Pajak dan Retribusi Daerah)**. Panduan ini dirancang khusus untuk **Admin Dinas** agar dapat mengoperasikan sistem mulai dari manajemen pengguna hingga pelaporan pendapatan.

---

## 🟢 1. Fase 1: Autentikasi & Manajemen Pengguna

### A. Cara Masuk ke Sistem (Login)
1. Buka browser dan arahkan ke [adminmpad.baubaukota.go.id](https://adminmpad.baubaukota.go.id).
2. Masukkan email: `bapenda@baubaukota.go.id` dan kata sandi: `password123`.
3. Tekan tombol **Login**.

### B. Penjelasan Komponen Dashboard
Setelah login berhasil, Anda akan diarahkan ke halaman **Dashboard** yang menampilkan ringkasan data retribusi. Berikut adalah komponen utama pada halaman ini:
1. **Sidebar Navigasi (Kiri)**: Menu utama untuk mengakses fitur seperti Wajib Pajak, Billing & Tagihan, PBB Bapenda, Verifikasi, Reporting, dan Master Data.
2. **Header (Atas)**: Menampilkan profil pengguna yang sedang login (misal: Admin BAPENDA), ikon notifikasi, dan pengaturan tema (Light/Dark mode).
3. **Filter Waktu (Kanan Atas)**: Memungkinkan Anda memfilter data berdasarkan rentang waktu harian, pekanan, atau bulanan.
4. **Kartu Ringkasan Metrik**: 
   - **Pendapatan (Target)**: Menampilkan total target pendapatan.
   - **Realisasi Saat Ini**: Total pendapatan yang sudah terealisasi.
   - **Piutang Menunggak**: Total tagihan yang belum dibayar oleh WP.
   - **Wajib Pajak Aktif**: Jumlah wajib pajak yang terdaftar di sistem.
5. **Peta Potensi Retribusi**: Peta interaktif yang memvisualisasikan sebaran lokasi wajib pajak berdasarkan zonasi (Premium, Medium, Lainnya) serta status pembayaran (Lunas, Tunggakan).
6. **Performa Wilayah**: Grafik atau tabel yang menunjukkan detail performa penerimaan di masing-masing wilayah administrasi.
7. **Geographic Revenue Heatmap (Bawah)**: Pemetaan panas berdasarkan potensi atau kontribusi pendapatan dari wilayah tertentu.

![Beranda Dashboard](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/admin_dashboard_tutorial_1772296498783.png)
![Beranda Dashboard Bagian Bawah](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/admin_dashboard_bottom_1772298108275.png)

### C. Registrasi User Baru (Manajemen User)
Admin dapat mendaftarkan akun bawahan untuk membantu operasional:
1. Masuk ke menu **User Management** di sidebar.
2. Klik tombol **Tambah User**.
3. Isi form dengan data pengguna baru dan pilih **Role** yang sesuai:
   - **Petugas**: Untuk personil lapangan (menggunakan aplikasi mobile).
   - **Verifikator**: Untuk personil pemeriksa data lapangan.
   - **Viewer**: Untuk personil yang hanya perlu memantau laporan.

**Siklus CRUD (Buat, Baca, Perbarui, Hapus) Manajemen User**:
1. **Tambah (Create)**: Klik Tambah User, isi data lengkap, klik Tambah.
2. **Lihat (Read)**: User langsung muncul di tabel daftar pengguna.
3. **Ubah (Update)**: Klik tombol ikon pensil di baris user, akan muncul *Form Edit Pengguna*.
4. **Hapus (Delete)**: Klik ikon tempat sampah untuk mencabut akses user seketika.

*Berikut adalah alur form beserta hasil eksekusi sukses (Success List):*
![Form Tambah Pengguna CRUD](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/crud_user_add_form_1772299710243.png)
![Pengguna Berhasil Ditambahkan](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/crud_user_add_success_1772299774285.png)
![Form Edit Pengguna](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/admin_user_edit_modal_1772298332147.png)

> [!TIP]
> **Video Demonstrasi Otomatisasi (Manajemen User & Master Data):**
> ![Video Tutorial User & Master](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/admin_full_crud_execution_1772298690457.webp)

---

## 🔵 2. Fase 2: Manajemen Data Wajib Pajak (CRUD)

### A. Daftar Wajib Pajak & Pencarian
Gunakan menu **Wajib Pajak** untuk melihat semua WP yang sudah terdaftar. Anda dapat memfilter data berdasarkan OPD atau mencari langsung melalui kolom **Nama/NIK**.

**Penjelasan Komponen Halaman Wajib Pajak**:
- **Statistik Cepat**: Menampilkan total Wajib Pajak dan Wajib Pajak Baru bulan ini.
- **Filter Wilayah**: Dropdown untuk menyaring data berdasarkan Kecamatan dan Kelurahan.
- **Tabel Wajib Pajak**: Menampilkan nama WP, informasi objek pajak (nama dan jenis retribusi), lokasi, status verifikasi (Pending/Approved), dan aksi.
- **Tombol Tambah/Edit/Hapus**: Untuk manajemen data WP secara langsung.

![Daftar Wajib Pajak](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/admin_wp_list_png_1772296664003.png)

### B. Proses Penginputan (Tahap Sistematis - Create)
1.  **Langkah 1: Identitas WP** - Masukkan NIK (Wajib), Nama Lengkap, Nomor WA, Pekerjaan, dan Alamat Lengkap.
2.  **Langkah 2: Objek & Kategori** - Pilih Kecamatan, Kelurahan, Kategori Utama, dan Klasifikasi Retribusi yang spesifik, serta masukkan nama objek dan volume/satuan jika diperlukan.
3.  **Langkah 3: Data Dukung** - Memasukkan profil pendapatan / tanggal registrasi. Mengunggah bukti pendukung (KTP/SK/Foto Objek).
4.  **Langkah 4: Lokasi Map** - Tandai titik lokasi pasti objek retribusi di atas peta digital (bisa menggunakan GPS atau geser penanda). Peta ini akan memengaruhi zonasi wajib pajak.
5.  **Langkah 5 (Review)** - Tinjau seluruh data yang diinput sebelum *submit* final.

![Tahap 1 - Identitas](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/crud_wp_add_step1_1772303014976.png)
![Tahap 2 - Klasifikasi](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/crud_wp_add_step2_1772303133138.png)
![Tahap 3 - Berkas](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/crud_wp_add_step3_1772303182792.png)
![Tahap 4 - Peta](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/crud_wp_add_step4_1772303204922.png)
![Tahap 5 - Review](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/crud_wp_add_step5_review_1772303232534.png)
![Berhasil Ditambahkan](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/crud_wp_add_success_list_1772303286626.png)

### C. Pembaruan Data (Edit) dan Penghapusan (Update & Delete)
Untuk mengubah data yang sudah ada, klik ikon **Pensil (Edit)** pada baris data di menu Wajib Pajak. Semua tahap form akan terbuka kembali sehingga bisa diperbarui (*Update*). 
Jika diperlukan, Anda juga dapat menghapus data dengan menekan ikon **Tempat Sampah (Hapus)** yang akan memunculkan modal konfirmasi untuk mencegah penghapusan yang tidak disengaja.

![Form Edit WP](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/crud_wp_edit_step1_1772303349552.png)
![Konfirmasi Hapus WP](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/crud_wp_delete_confirm_1772303502275.png)
![Hapus Berhasil - Tabel WP](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/crud_wp_delete_success_list_1772303601073.png)

> [!TIP]
> **Video Demonstrasi Otomatisasi (Manajemen Wajib Pajak Full CRUD):**
> ![Video Tutorial WP](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/admin_wp_crud_1772302557004.webp)

---

## 💰 3. Fase 3: Billing, Tagihan & Verifikasi

### A. Alur Verifikasi Data
Setiap data yang diinput oleh **Petugas** lapangan akan masuk ke menu **Verifikasi** dengan status **Pending**. **Verifikator** wajib memeriksa:
1. Kesesuaian Foto dan Dokumen Berkas.
2. Keaslian Foto Objek.
3. Akurasi Titik Koordinat di Peta.
4. Klik tombol **Review** pada baris data untuk melihat detail dan menyetujui (Approve) atau menolak (Reject/Draft).

**Penjelasan Komponen Halaman Verifikasi**:
- **Statistik Verifikasi**: Menampilkan jumlah antrean yang perlu diverifikasi dan jumlah yang sudah disetujui (Approved) secara real-time.
- **Tabel Verifikasi**: Memiliki kolom Petugas Penginput, Informasi Registrasi WP, Dokumen/Foto, dan Status Review.
- **Tombol Review**: Berwarna biru, digunakan untuk masuk ke detail formulir verifikasi komprehensif.

![Daftar Verifikasi](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/admin_verifikasi_list_png_1772296696383.png)
![Modal Form Review Verifikasi](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/admin_verifikasi_review_modal_1772298192778.png)

### B. Pembuatan Tagihan (Billing & Tagihan)
Setelah data WP berstatus **Approved**, Admin dapat membuat atau mengelola tagihan di menu **Billing & Tagihan**:
1. Masuk ke menu **Billing & Tagihan**.
2. Klik **Catat Pembayaran** atau cari tagihan berdasarkan No. Tagihan/Nama WP.
3. Untuk membuat tagihan baru, pilih WP, tentukan periode tagihan, klasifikasi bulan ini, dan sistem akan meng-generate kode billing.
4. Nominal pembayaran bisa diteruskan ke kasir/petugas pemungut lapangan.

**Penjelasan Komponen Halaman Billing**:
- **Tabel Tagihan Aktif**: Memuat informasi Periode, No. Tagihan, WP Pembayar, Jatuh Tempo, Nilai, dan Status Pembayaran (Lunas/Draft).
- **Form Catat Pembayaran**: Digunakan sebagai Point of Sales (POS) sistem untuk menyimpan catatan pelunasan.

![Manajemen Billing](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/admin_billing_list_png_1772296734052.png)
![Generate Billing Form](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/admin_generate_billing_form_1772297603770.png)

---

---

## 📊 4. Fase 4: Integrasi & Penindakan Khusus

### A. Integrasi PBB Bapenda
Menu **PBB Bapenda** digunakan khusus untuk memantau data Wajib Pajak Pajak Bumi dan Bangunan yang disinkronisasi dari sistem utama Bapenda. Modul ini memungkinkan admin untuk memvalidasi subjek pajak dan mengecek status riwayat pajaknya secara terpisah dari retribusi biasa.

![PBB Bapenda](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/admin_pbb_bapenda_1772298127271.png)

### B. Penegakan (Enforce)
Menu **Penegakan (Enforce)** adalah fitur untuk mendata wajib pajak yang sudah jatuh tempo dalam waktu lama dan membutuhkan tindakan tegas (segel/SP). Halaman ini menampilkan daftar WP yang sedang dalam proses penindakan.

![Halaman Penegakan](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/admin_penegakan_1772298152587.png)

---

## 📈 5. Fase 5: Reporting & Master Data

### A. Analisis Laporan (Reporting)
Dashboard **Reporting** menyajikan visualisasi data yang mendalam:
- **Grafik Tren Pendapatan**: Visual garis untuk melihat pergerakan dari waktu ke waktu (Realisasi vs Target).
- **Filter Fleksibel**: Saring data berdasarkan Rentang Tanggal, Kategori OPD, dan Jenis Retribusi.
- **Geographic Breakdown**: Lihat performa dan kontribusi pendapatan berdasarkan pembagian wilayah.

**Penjelasan Komponen Halaman Reporting**:
- **Statistik Header**: Total Pendapatan, Transaksi Sukses, Menunggu Pembayaran.
- **Tabel Detail Transaksi (Bawah)**: Ringkasan seluruh data pembayaran sukses sesuai parameter waktu untuk diaudit.

![Laporan & Analitik](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/admin_reporting_page_png_1772296776836.png)

### B. Hirarki Master Data
Sistem diatur dengan hirarki ketat untuk akurasi perhitungan. Anda dapat mengaturnya di menu **Master Data**:
1.  **Jenis Retribusi**: Kategori besar layanan pemerintah (Contoh: Pelayanan Pasar).
2.  **Klasifikasi**: Sub-jenis pelayanan spesifik (Contoh: Sewa Pelataran, Sewa Kios Kategori B).
3.  **Zona**: Penentuan wilayah geografis yang menentukan perkalian tarif dasar (Contoh: Zona 1 Premium, Zona Pinggiran).
4.  **Tarif**: Nilai nominal konstan Rupiah yang menjadi dasar tagihan.

**Penjelasan Komponen Halaman Master Data & Siklus CRUD**:
Halaman ini menggunakan mekanisme **Tab** (Jenis, Klasifikasi, Zona, Tarif) untuk kemudahan navigasi. Admin bisa *Create, Update,* dan *Delete* pada semua variabel ini dengan cara yang persis sama. 

1. **Tambah (Create)**: Klik *Tambah Jenis/Klasifikasi/Zona/Tarif*.
2. **Lihat (Read)**: Di-list secara responsif dalam tabel masing-masing.
3. **Ubah (Update)**: Terhadap setiap Master Data dapat ditekan Edit untuk memperbaiki nama atau kategori/satuan.
4. **Hapus (Delete)**: Tempat sampah merah digunakan untuk menghilangkan komponen master yang tidak valid.

![Master - Jenis Form Tambah](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/master_jenis_add_form_1772301441665.png)
![Master - Jenis Ditambahkan](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/master_jenis_add_1772301619689.png)
![Master - Klasifikasi](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/admin_master_klasifikasi_1772297683428.png)
![Master - Zona](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/admin_master_zona_1772297686722.png)
![Master - Tarif](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/admin_master_tarif_1772297690120.png)

> [!TIP]
> **Video Demonstrasi Otomatisasi (Seluruh Tab Master Data CRUD):**
> ![Video Tutorial Master Data](/Users/pondokit/.gemini/antigravity/brain/518a7460-bf50-48ac-b481-0289c35e8490/admin_master_crud_1772300567082.webp)

---

## 💡 5. Tips Keamanan
- Jangan bagikan akun `bapenda@baubaukota.go.id` kepada pihak luar.
- Lakukan verifikasi data secara rutin untuk menjaga kualitas basis data pendapatan daerah.
