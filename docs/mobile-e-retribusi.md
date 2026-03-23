# Dokumentasi Aplikasi E-Retribusi Kota Baubau

Dokumen ini menjelaskan fitur dan fungsionalitas dari aplikasi E-Retribusi Kota Baubau berdasarkan implementasi frontend yang ada.

## 1. Pendahuluan

Aplikasi E-Retribusi Kota Baubau adalah sebuah platform digital yang dirancang untuk memudahkan masyarakat dalam melakukan pembayaran berbagai jenis retribusi daerah secara online. Aplikasi ini bertujuan untuk meningkatkan efisiensi, transparansi, dan akuntabilitas dalam pengelolaan pendapatan asli daerah (PAD) Kota Baubau.

## Keuntungan dan Kelebihan

Aplikasi E-Retribusi ini menawarkan berbagai keuntungan dan kelebihan dibandingkan dengan sistem pembayaran retribusi konvensional:

1.  **Sentralisasi dan Kemudahan Akses**: Mengumpulkan berbagai jenis retribusi dalam satu platform, sehingga masyarakat tidak perlu lagi mengunjungi kantor yang berbeda untuk setiap pembayaran.
2.  **Efisiensi Waktu dan Biaya**: Mengurangi kebutuhan untuk datang langsung ke loket pembayaran, sehingga menghemat waktu dan biaya transportasi.
3.  **Transparansi Real-time**: Pengguna dapat melihat status tagihan dan riwayat pembayaran secara langsung dan transparan, mengurangi potensi ketidaksesuaian data.
4.  **Fleksibilitas Pembayaran**: Menyediakan metode pembayaran modern seperti Virtual Account dan QRIS yang dapat diakses kapan saja dan di mana saja.
5.  **Peningkatan Pelayanan Publik**: Adanya fitur pengaduan, kritik, dan saran memungkinkan pemerintah daerah untuk lebih cepat merespons masukan dari masyarakat guna meningkatkan kualitas layanan.
6.  **Akuntabilitas**: Setiap transaksi tercatat secara digital, meningkatkan akuntabilitas dalam pengelolaan Pendapatan Asli Daerah (PAD).
7.  **Fitur Lengkap**: Menyediakan fitur yang komprehensif mulai dari pembayaran, pengecekan tagihan, riwayat transaksi, hingga penyampaian pengaduan dalam satu aplikasi.
8.  **Antarmuka Modern**: Didesain dengan antarmuka pengguna (UI) yang bersih, modern, dan mudah digunakan untuk kenyamanan pengguna.

## 2. Fitur Utama

Aplikasi ini memiliki beberapa fitur utama yang dapat diakses oleh pengguna:

### 2.1. Autentikasi dan Profil Pengguna

*   **Login**: Pengguna dapat masuk ke dalam aplikasi menggunakan Nomor Induk Kependudukan (NIK) dan kata sandi yang telah terdaftar.
*   **Profil Pengguna**: Setelah login, pengguna dapat melihat data profil mereka, termasuk nama lengkap, NIK, email, nomor telepon, alamat, dan tanggal registrasi.

### 2.2. Halaman Utama (Dashboard)

Halaman utama merupakan pusat informasi bagi pengguna. Di sini, pengguna dapat melihat:

*   **Sapaan Selamat Datang**: Menampilkan nama pengguna yang sedang login.
*   **Informasi Saldo**: Menampilkan jumlah simpanan pengguna yang dapat ditampilkan atau disembunyikan.
*   **Akses Cepat**: Tombol untuk fitur-fitur utama seperti Transfer, Top Up, QRIS, dan Riwayat.
*   **Layanan Retribusi**: Menampilkan daftar ikon layanan retribusi yang tersedia. Sebagian layanan ditampilkan di halaman utama, dan sisanya dapat diakses melalui menu "Lainnya".
*   **Transaksi Terakhir**: Menampilkan informasi transaksi terakhir yang berhasil dilakukan.
*   **Tunggakan Tagihan**: Menampilkan daftar tagihan yang masih berstatus "pending" atau belum dibayar.

### 2.3. Layanan Retribusi

Aplikasi ini menyediakan berbagai jenis layanan pembayaran retribusi, antara lain:

*   Parkir
*   e-Ticket
*   Pasar
*   PDAM
*   Sampah
*   Izin Usaha Mikro dan Kecil (IUMK)
*   Izin Mendirikan Bangunan (IMB)
*   Tanda Daftar Perusahaan (TDP) / Surat Izin Usaha Perdagangan (SIUP)
*   Internet
*   Kendaraan
*   Jasa Kepelabuhanan
*   Retribusi Reklame
*   Terminal
*   Penerangan Jalan Umum (PJU)

Setiap layanan memiliki halaman detailnya sendiri yang menampilkan ringkasan tagihan (belum dibayar dan sudah dibayar) serta daftar tagihan.

### 2.4. Halaman Tagihan

Halaman ini berfungsi sebagai pusat pengelolaan tagihan pengguna. Fitur-fitur di halaman ini meliputi:

*   **Daftar Tagihan**: Menampilkan semua tagihan pengguna, baik yang sudah maupun yang belum dibayar.
*   **Pemilihan Tagihan**: Pengguna dapat memilih satu atau beberapa tagihan untuk dibayar sekaligus.
*   **Tombol Bayar**: Tombol untuk melanjutkan ke proses pembayaran tagihan yang dipilih.

### 2.5. Proses Pembayaran

Halaman pembayaran dirancang untuk memberikan kemudahan bagi pengguna dalam menyelesaikan transaksi. Alur pembayarannya adalah sebagai berikut:

1.  **Ringkasan Pembayaran**: Menampilkan rincian tagihan yang dipilih, biaya admin, dan total yang harus dibayar.
2.  **Pilihan Metode Pembayaran**: Pengguna dapat memilih metode pembayaran yang diinginkan:
    *   **Transfer Virtual Account**: Pembayaran melalui transfer ke nomor Virtual Account Bank BPD Sultra.
    *   **QRIS - Scan & Bayar**: Pembayaran dengan cara memindai kode QRIS yang ditampilkan di aplikasi menggunakan aplikasi mobile banking atau e-wallet.
    *   **QRIS - Scan Petugas**: Pembayaran di loket dengan cara petugas memindai QR code dari aplikasi e-wallet pengguna.
3.  **Instruksi Pembayaran**: Setelah memilih metode, aplikasi akan menampilkan instruksi langkah-demi-langkah untuk menyelesaikan pembayaran.
4.  **Konfirmasi Pembayaran**: Setelah pembayaran berhasil, aplikasi akan menampilkan halaman konfirmasi.

### 2.6. Pengaduan dan Ulasan

Pengguna dapat memberikan masukan kepada pengelola layanan melalui fitur ini.

*   **Formulir Pengaduan**: Pengguna dapat mengirimkan pengaduan terkait layanan dengan mengisi deskripsi dan melampirkan file pendukung.
*   **Tingkat Kepuasan**: Pengguna dapat memberikan penilaian tingkat kepuasan terhadap layanan.
*   **Kritik dan Saran**: Pengguna dapat mengirimkan kritik dan saran untuk perbaikan layanan.

## 3. Alur Pengguna

Secara umum, alur penggunaan aplikasi adalah sebagai berikut:

1.  Pengguna melakukan **login** ke dalam aplikasi.
2.  Di **halaman utama**, pengguna melihat ringkasan informasi dan memilih layanan retribusi yang ingin dibayar.
3.  Pengguna dapat langsung menuju ke **halaman pembayaran** dari menu tunggakan di halaman utama, atau melalui **halaman tagihan** untuk memilih tagihan yang akan dibayar.
4.  Di **halaman pembayaran**, pengguna memilih metode pembayaran dan mengikuti instruksi untuk menyelesaikan transaksi.
5.  Setelah pembayaran berhasil, status tagihan akan diperbarui.
6.  Pengguna dapat melihat riwayat pembayaran di halaman detail retribusi atau di halaman tagihan.

## 4. Integrasi

Aplikasi ini terintegrasi dengan **Bank BPD Sultra (Bank Sulawesi Tenggara)** sebagai penyedia gateway pembayaran untuk memproses semua transaksi secara aman dan real-time.