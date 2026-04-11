# Tahap 3: Pengujian Alur Utama Pajak / Retribusi (E2E Business Flow)

Skenario ini adalah fondasi aplikasi Anda. Menirukan dunia nyata di mana Wajib Pajak mendaftar, Admin menerbitkan tagihan (SKPD), Petugas melakukan validasi bayar di lapangan, dan dana tersinkronisasi mendarat di Dashboard.

---

### Skenario 1: Wajib Pajak Mendaftar (Via Aplikasi Mobile Wajib Pajak)
- [ ] Buka Aplikasi Smartphone (Android/iOS) Mpad / M-PAD.
- [ ] Saat di layar sapaan awal (*Onboarding*), klik tombol **"Daftar"**.
- [ ] Ketikkan formulir pendaftaran Wajib Pajak meminjam identitas *dummy* berikut:
  - **NIK Utama**: `3201234567890001` (Ketik teliti 16 digit).
  - **Nama Lengkap Sesuai KTP**: `Budi Tester UAT`
  - **Email Aktif**: `budi.uat@test.com`
  - **Nomor HP / WA**: `081234567890`
  - **Password/Sandi**: `password123`
- [ ] Ketuk ikon **"Daftar/Register"**.
- [ ] Sistem akan mengalihkan kembali ke *Login*. Ketikkan `budi.uat@test.com` dengan sandi rahasia yang sama.
- [ ] **KRITERIA SUKSES**: Tembus ke layar Beranda (*Homepage*). Di atas layar tertulis *"Halo, Budi Tester UAT"*. Klik menu Profil untuk mengecek apakah angka NIK tidak terpotong (konsisten 16 digit). Dan lihat Tab "Tagihan", pastinya harus tertulis dengan santun *"Tidak ada tagihan aktif"* (Rp 0).

---

### Skenario 2: Bapenda Menerbitkan Tagihan Baru (Via Web Dashboard Admin)
*Peralihan Peran: Anda sekarang duduk di Kantor Bapenda sebagai Pegawai Superadmin.*

- [ ] Buka peramban laptop. Jelajahi situs `https://admin.sipanda.online`.
- [ ] Tembus masuk (Login) dengan alamat email dan kata sandi otentik milik **Admin Hak Bapenda**.
- [ ] **Sinkronisasi Warga**: 
  - Masuk ke sub-menu **"Master Data"** -> **"Wajib Pajak"**.
  - Ketik pelan `Budi Tester UAT` / `3201234567890001` pada bilah penelusuran tabel.
  - **Kriteria Sukses**: Data Sang WP dari aplikasi Handphone sebelumnya harus sudah masuk ke database pusat Admin otomatis!
- [ ] **Mendaftarkan Usaha Potensi Budi**:
  - Pindah ke ruas menu **"Objek Pajak / Potensi Daerah"**.
  - Klik balok biru **"Tambah Data"**.
  - Isi Form Survei Lokasi Usaha:
    - **Nama Usaha Lokal**: `Warung Sate Budi UAT`
    - **Golongan Jenis Pajak**: Tekan dropdown dan pilih `Pajak Restoran / Rumah Makan`.
    - **Pemilik Usaha**: _Search_ & seret terpilih `Budi Tester UAT` (`32012...`) tadi.
    - **Detail Alamat Lokasi**: `Jl. Percobaan UAT No.1, Kec. Testing, Kota Baubau`.
  - Klik **Simpan**.
- [ ] **Mematok Ketetapan Tagihan (SKPD)**:
  - Loncat ke ruas menu **"Billing / Penetapan Pajak"**.
  - Klik ikon tambah **"Buat Billing Baru"**.
  - Pilih objek sasarannya: `Warung Sate Budi UAT`.
  - Perhitungkan kalkulasi masa pajaknya:
    - **Masa Pajak Bulan**: Pilih bulan kalender saat ini (Misal `Februari 2026`).
    - **Total Pokok Tagihan**: Tik nominal bulat Rp `150.000` (Simulasi Tarif 10% jika form omzet ada).
    - **Atur Jatuh Tempo**: Pilih batas telat hari ke-30 bulan ini.
  - Klik **Simpan dan Rilis SKPD Nasional**.
  - **KRITERIA SUKSES**: Akan muncul list baru dalam baris tabel tagihan, nominal total Rp 150.000 dengan status tebal berlatar warna merah darah bertuliskan keras **"Belum Dibayar"** (*Unpaid*).

---

### Skenario 3: Wajib Pajak Kaget Ada Tagihan (Via App Mobile Wajib Pajak)
*Peralihan Peran: Anda kembali menjadi Mas Budi sambil memegang hapenya.*

- [ ] Buka Aplikasi Handphone Siaga Wajib Pajak itu kembali (akun Budi UAT).
- [ ] Pegang area tengah layar, sorong ke bawah dengan cepat untuk me-_Refresh_ data (*Pull to Refresh*).
- [ ] **KRITERIA KESUKSESAN MUTLAK**: 
  - Lingkaran ringkasan saldo bagian *Top Header* Aplikasi yang tadinya diam di `Rp 0`, seketik melonjak tajam tertuliskan Tagihan Anda: **Rp 150.000**.
  - Ada "Bel Notifikasi Merah" menginformasikan hadirnya Surat Ketetapan Pajak (SKPD) bulan berjalan. 
  - Bila masuk ke blok navigasi "Tagihan Pembayaran", terbaca kartu berdetail rincian jatuh tempo *Warung Sate Budi UAT* seharga pembayaran tersebut.

---

### Skenario 4: Intervensi Petugas Loket (Via Web App Kolektor Lapangan)
*Peralihan Peran: Bapak Wajib Pajak Budi secara perlakuan nyata enggan/gaptek bayar online virtual account, sehingga ia membawa Motornya ke Kantor Cabang Loket Bapenda / didatangi Petugas Kolektor Desa.*

- [ ] Siapkan PC Kasir Tablet atau perangkat Mobile. Kunjungi `https://petugas.sipanda.online`.
- [ ] Autentikasi Login terdaftarkan menggunakan akses hak **Petugas Lapangan / Kolektor Harian**.
- [ ] Berjejer di berandanya, tap bagian raksasa bertuliskan **"Penerimaan Pembayaran / Loket SKPD"**.
- [ ] Arahkan kursor pencari Tagihan dan tembak: 
  - Ketik NIK `320123...` (atau scan QR Tagihan langsung dari layar HP si Budi UAT tadi).
- [ ] Hasilnya tampil, tap detail Tagihan Usaha `Warung Sate Budi UAT`.
- [ ] Cek seksama oleh mata Petugas, oh senilai sama **Rp 150.000** berstatus Merah.
- [ ] Petugas menekan keras tombol penyelesaian sistem: **"TANDAI LUNAS / PROSES BAYAR"**.
- [ ] Muncul peringatan final, konfirmasi Tipe Bayar `TUNAI / KAS` -> klik Oke.
- [ ] **KRITERIA KESUKSESAN**: Muncrat *Effect Confetti/Toaster* Sukses berwarna HIJAU DAUN cemerlang. Naskah cetakan bon Struk *Surat Setoran Pajak Daerah (SSPD)* ter-generate otomatis!

---

### Skenario 5: Pengecekan Kebenaran Akhir (Real-Time Sinkronisasi Integritas)
*Melihat sistem bekerja serentak menautkan seluruh jaring database real-time API.*

- [ ] **Kembali Mengintip Laptop Admin Bapenda (Dashboard `admin.mpad`)**:
  - Tap ikon Logo Rumah / *Dashboard Executive Panel*.
  - Lihat box metrik bertajuk "Realisasi Penerimaan Hari Ini". Angkanya seharusnya langsung gemuk meloncat **bertambah senilai Rp 150.000**.
  - Lompat mengecek tabel ketat `Billing`. Label status yang memerah "Belum Dibayar" pada objek mas Budi telah disulap sempurna menjadi hijau segar terbaca **"Lunas / Paid"**.
- [ ] **Melirik Kembali Dompet Tagihan HP Wajib Pajak Budi (`App WP`)**:
  - Genggam HP Budi. Buka paksa App.
  - Sisa Piutangnya di beranda lenyap memudar kembali tertulis nyaman asri **Rp 0 rupiah**.
  - Ia bermigrasi ke menu seksi Riwayat Dompet, terpajang jejak log historis "*Pembayaran Tagihan Anda Sebesar Rp 150.000 telah disetujui petugas (Ahmad Petugas UAT) pada Jam Sekian*". WP bisa menge-klik untuk mengunduh versi PDF legal resi elektronik tersebut ke direktori *Files* hapenya!
