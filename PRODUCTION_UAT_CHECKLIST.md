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
- [ ] **Coba Akses File Terlarang**: Buka browser ke `https://api.sipanda.online/.env`. **Harus ditolak (403 Forbidden)**.
- [ ] **Coba Akses Dokumentasi Internal**: Buka `https://api.sipanda.online/docs` di mode *incognito* / HTTP Request manual tanpa Bearer token. **Harus diblokir (401 Unauthorized)**.
- [ ] **Coba Banjiri Limit API (Rate Limiting)**: Kirim _request_ berulang kali (> 60x) ke `/login` atau halaman utama API dalam rentang 1 menit menggunakan *Postman*/*Insomnia*. Sistem harus merespons dengan **429 Too Many Requests**.
- [ ] **Cek Sertifikat & Header**: Buka web frontend (Admin/Petugas), cek kolom *Network* di *Inspect Element*. Pastikan indikator gembok HTTPS menyala hijau dan tidak ada error CSS/JS (*Mixed Content*). Pastikan *Strict-Transport-Security* ada pada *Response Headers*.

## 3. Pengujian Alur Utama (End-to-End Business Flow)
Simulasikan 1 siklus transaksi penuh yang melibatkan ketiga peran utama:

- [ ] **Aktor: Wajib Pajak (Aplikasi Mobile)** 
    - Lakukan pendaftaran akun baru (Bisa dihapus nanti).
    - Login sukses.
    - Beranda, Menu Profil, dan riwayat Pajak tampil normal (meski kosong).
- [ ] **Aktor: Admin Bapenda (Web Admin - `admin.sipanda.online`)**
    - Login dengan Role *Admin/Superadmin*.
    - Buat "Wajib Pajak Baru" dan tautkan ke "Objek Pajak" (misal: Pajak Hotel atau Reklame).
    - Terbitkan **1 Ketetapan Billing / SKPD (Surat Ketetapan Pajak Daerah)** resmi untuk objek pajak tersebut.
- [ ] **Aktor: Wajib Pajak (Aplikasi Mobile)**
    - *Refresh* halaman utama aplikasi mobile.
    - Pastikan notifikasi/tab tagihan untuk SKPD yang baru dibuat oleh Admin langsung muncul di layar.
- [ ] **Aktor: Petugas Lapangan (Aplikasi Petugas - `petugas.sipanda.online`)**
    - Login dengan Role *Petugas/Surveillance*.
    - Lakukan pencarian nama/NPWPD pengguna tersebut di menu Pembayaran/Cek Tagihan.
    - Lakukan simulasi **Konfirmasi Bayar** seolah WP membayar secara non-tunai di lapangan atau WP membayar lewat teller (Tandai Lunas).
- [ ] **Verifikasi Final (Seluruh Pihak)**
    - [ ] **Web Admin**: Cek *Dashboard Analytics*, grafik pendapatan harian harusnya menunjukkan kenaikan saldo sesuai tagihan lunas di atas.
    - [ ] **Aplikasi Mobile / Petugas**: Pastikan Struk Pembayaran (SSPD) berhasil di*generate* sebagai PDF dan bisa di-download oleh WP maupun Petugas.

## 4. Pengujian Fitur Kritis Tambahan (Edge Cases)
Uji fungsi spesifik pemerintahan yang sangat penting:
- [ ] **Modul TTE (Tanda Tangan Elektronik)**: Pergi ke menu pengesahan SKPD/SSPD. Minta salah satu pejabat berwenang mencoba memvalidasi dan menandatangani dokumen. Pastikan file PDF akhir memuat visual *QR Code* /*digital stamp* BSDN yang valid.
- [ ] **Pemetaan & GIS**: Buka fitur Peta Potensi. Pastikan _pin drop_ / pelengkung kordinat tidak mengalami *Error CORS*, serta gambar foto potensi ruko/papan reklame termuat sempurna.
- [ ] **Penolakan Retribusi/PBB**: Coba batalkan 1 tagihan atau ajukan "Amnesty / Keringanan". Pastikan perhitungan nominal dan penalti denda terkalkulasi secara wajar.

## 5. Pengujian Perangkat Real (Cross-Device UAT)
- [ ] Tes *Web Admin* di laptop menggunakan browser selain Chrome (misal Mozilla Firefox/Safari Edge) untuk mengecek bug CSS Modal / Tabel yang terpotong.
- [ ] Jalankan *Aplikasi Petugas* dari peramban/browser di minimum 2 jenis Handphone: Layar besar (di atas 6 inch) dan layar kecil (iPhone SE/serupa) untuk memastikan formulir panjang tidak cacat ketika digulir (*scrollable*).

## 6. Pemantauan Hari Pertama (Go-Live Monitoring)
Jika tahap 1 hingga 5 sukses, persilahkan *End-user* menggunakannya. Pada 24 jam pertama pembukaan kepada Bapenda/khalayak ramai:
- [ ] Masuk SSH ke VPS `157.10.252.74`
- [ ] Pantau file error secara _real-time_: `tail -f /home/sipanda/retribusi-api/storage/logs/laravel.log`. Pastikan rentetan *Exception/Fatal Error* beruntun tidak muncul.
- [ ] Cek *usage* sumber daya server (`htop` atau `free -h`) untuk mewaspadai kebocoran memori (Memory Leak) jika beban akses serentak ke API mendadak terlalu tinggi.
