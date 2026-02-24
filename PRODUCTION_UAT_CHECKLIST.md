# Panduan Lengkap Pengujian (UAT) Menuju Production (api.sipanda.online)

Dokumen ini berisi daftar langkah (checklist) pengujian menyeluruh untuk memastikan server *Production* MITRA PAD siap dan aman digunakan oleh Wajib Pajak dan Petugas. Lakukan pengujian ini setiap kali akan melakukan *Go-Live* atau peluncuran fitur besar.

---

## 1. Persiapan Deployment (Deployment Checklist)
Sebelum mulai menguji fitur, pastikan hal teknis fundamental sudah beres di VPS:
- [ ] **Kode Terbaru**: Pastikan *branch* `main` dari seluruh repo terkait (API, Admin, Petugas, Mobile) sudah ter-*deploy* ke mesin Ubuntu `157.10.252.74`.
- [ ] **Optimalisasi Laravel**: Jalankan *cache clearing* agar aplikasi berjalan cepat dengan mengeksekusi ini di root repo API VPS:
  ```bash
  php artisan optimize:clear
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```
- [ ] **Migrasi Database**: Jika rilis ini memuat tabel baru, pastikan `php artisan migrate --force` sudah dijalankan.

## 2. Pengujian Infrastruktur & Keamanan (Security Smoke Test)
Verifikasikan bahwa benteng yang kita pasang di Nginx bekerja:
- [ ] **Ujian Akses File Rahasia (`/.env`)**: Buka browser *Incognito*. Ketik alamat `https://api.sipanda.online/.env`. 
  - **Hasil yang Diharapkan:** Harus muncul halaman kosong dengan tulisan "403 Forbidden". Jika file teks terdownload, SEGERA MATIKAN SERVER.
- [ ] **Ujian Dokumentasi Terselubung**: Buka `https://api.sipanda.online/docs`.
  - **Hasil yang Diharapkan:** Halaman harus blank/JSON mengembalikan `{"message": "Unauthenticated."}` dengan status HTTP 401. Ini membuktikan bahwa dokumentasi API internal (struktur endpoint) tidak lagi bocor ke publik.
- [ ] **Ujian Anti-Spam (Rate Limiting)**: Buka aplikasi *Postman* atau terminal *Curl*, tembakkan URL `https://api.sipanda.online/api/login` sebanyak 65 kali dalam waktu kurang dari 1 menit secara berturut-turut.
  - **Hasil yang Diharapkan:** Pada _request_ ke-61 dan seterusnya, server harus menolak dengan respons HTTP HTTP `429 Too Many Requests`. Ini membuktikan sistem anti-DDoS/Brute Force aktif.

## 3. Pengujian Alur Utama Pajak / Retribusi (End-to-End Business Flow)
Simulasikan 1 siklus transaksi penuh yang melibatkan ketiga peran utama:

### A. Aktor 1: Wajib Pajak (Via Aplikasi Mobile `retribusi-mobile`)
- [ ] Buka Aplikasi Mobile di HP (atau emulator).
- [ ] Klik **"Daftar"**. Isi data KTP (NIK palsu numerik acak), Nama, Email, dan Password.
- [ ] Klik **"Login"** menggunakan email & password tadi.
- [ ] **Hasil yang Diharapkan:** Berhasil masuk ke halaman Beranda Utama (Welcome). Menu profil menampilkan nama Wajib Pajak baru, dan tab "Tagihan Pembayaran" tidak menampilkan pesan *error* (meskipun saat ini daftarnya kosong/Rp0).

### B. Aktor 2: Admin Bapenda (Via Web Dashboard `admin.sipanda.online`)
- [ ] Buka browser laptop, pergi ke `https://admin.sipanda.online`.
- [ ] Login menggunakan kredensial akun **Admin Utama (Superadmin/Bapenda)**.
- [ ] **Pembuatan Wajib Pajak**: Masuk ke menu "Master Data" -> "Wajib Pajak". Klik "Tambah Data" dan cocokkan datanya dengan email/NIK WP yang didaftarkan di HP tadi.
- [ ] **Penetapan Objek Pajak**: Masuk ke menu "Objek Pajak / Potensi Daerah". Tambahkan Objek Pajak baru (misalnya "Warung Makan xyz" / Pajak Restoran). Tarik nama Pemilik ke akun Wajib Pajak yang baru kita buat.
- [ ] **Penerbitan Tagihan (SKPD)**: Buka menu "Billing / Penetapan". Pilih Objek Pajak tadi, lalu isi form penetapan pajak (misal Bulan ini nilainya Rp 50.000). Klik **Simpan / Terbitkan**.
- [ ] **Hasil yang Diharapkan:** Muncul baris data tagihan baru dengan status **"Belum Dibayar"** warna merah/kuning di tabel Web Admin.

### C. Aktor 3: Wajib Pajak (Via Aplikasi Mobile Kembali)
- [ ] Di HP, tarik layar ke bawah untuk *Refresh* halaman utama aplikasi mobile Wajib Pajak.
- [ ] **Hasil yang Diharapkan:** Di layar HP, nilai tagihan mendadak berubah. Terdapat notifikasi 1 SKPD / Tagihan baru bernilai Rp 50.000 dari "Pajak Restoran".
- [ ] Klik panel tagihan tersebut untuk melihat rincian tanggal jatuh tempo dan tombol bayar. Keluar dari aplikasinya (anggaplah WP pergi menemui Petugas Loket untuk bayar tunai).

### D. Aktor 4: Petugas Lapangan/Loket (Via Aplikasi Petugas `petugas.sipanda.online`)
- [ ] Buka browser laptop/tablet, pergi ke `https://petugas.sipanda.online`.
- [ ] Login menggunakan kredensial akun **Petugas (Kolektor/Kasir)**.
- [ ] Pilih menu **"Pembayaran / Loket"** atau gunakan mesin Scanner Kode Bayar.
- [ ] Ketik NIK atau Nomor Tagihan SKPD milik Wajib Pajak tadi.
- [ ] Saat datanya muncul di layar Petugas, pastikan nominalnya cocok (Rp 50.000).
- [ ] Klik tombol **"Tandai Lunas / Verifikasi Pembayaran"**. Konfirmasi pop-up.
- [ ] **Hasil yang Diharapkan:** Muncul centang Hijau. Sistem menerbitkan resi pembayaran Struk.

### E. Verifikasi Sinkronisasi Final
- [ ] Buka kembali **Web Admin (`admin.sipanda.online`)**. Lihat menu *Dashboard*.
- [ ] **Hasil yang Diharapkan:** Angka Grafik Total Realisasi Pendapatan **NAIK** sebesar Rp 50.000. Data SKPD di tabel billing statusnya berubah jadi label hijau **"Lunas"**.
- [ ] Buka kembali **Aplikasi Mobile WP**. 
- [ ] **Hasil yang Diharapkan:** Tagihan aktif berubah menjadi Rp 0. Di menu "Riwayat", ada PDF tanda bukti pelunasan **SSPD (Surat Setoran Pajak Daerah)** yang bisa diunduh oleh WP.

## 4. Pengujian Fitur Kritis Edge Cases (Opsional namun Penting)
- [ ] **Ujian Pengesahan Surat TTE**: Admin Web masuk ke menu "Dokumen TTE". Cari cetakan SKPD dari tes di atas. Minta Kepalo Bapenda/Pejabat melakukan klik "Tandatangani Dokumen". Buka file PDF hasilnya, sorot _QR Code_ BSDN di ujung kertas pakai kamera HP biasa; harus dialihkan ke link verifikasi resmi kominfo/balai sertifikasi.
- [ ] **Ujian Performa Peta**: Admin Web pindah ke menu "Peta Potensi". Klik ikon layer Peta satelit/jalan. Pastikan tidak diam membeku (Blank map tiles), dan ikon *pin drop* lokasi objek pajak "Warung Makan xyz" muncul dan bila di klik memunculkan rincian + foto tempat usahanya.

## 5. Pemantauan Hari Pertama (Go-Live Monitoring)
Jika tahap 1 hingga 4 sukses dan disetujui, informasikan tim dinas bahwa server Live. Tugas Tim IT Backend:
- [ ] Buka Terminal/SSH ke VPS `157.10.252.74` menggunakan kredensial _sipanda_.
- [ ] Standby menjalankan *command* ini di terminal:
  ```bash
  tail -f /home/sipanda/retribusi-api/storage/logs/laravel.log
  ```
- [ ] Amati barisan teks log yang muncul selama 6 jam pertama *Go-Live*. Jika tidak ada baris panjang dengan awalan `[stacktrace]` atau `Exception: `, berarti rilis berjalan mulus tanpa cacat sintaks fatal di backend!
