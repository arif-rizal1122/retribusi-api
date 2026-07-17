# Panduan Penyediaan Screenshot untuk Assessment TPRA BNI

Berdasarkan arsitektur sistem mPaD, berikut adalah panduan detail mengenai screenshot apa saja yang harus dipersiapkan untuk memenuhi permintaan dokumen pendukung pada formulir TPRA BNI:

## 1. Autentikasi (MFA / Login System)
**Yang diminta:** Bukti pengamanan saat login aplikasi/sistem.

**Cara mendapatkan Screenshot:**
* Buka halaman Login aplikasi mPaD (Admin/Petugas).
* Ambil screenshot dari form login.
* *(Opsional)* Jika sistem saat ini sudah memiliki fitur OTP (via WhatsApp/SMS) atau PIN, ambil screenshot saat layar meminta OTP tersebut. 
* **Keterangan di Excel:** Jika belum ada MFA (hanya username & password), Anda bisa menuliskan: *"Saat ini menggunakan standar keamanan otentikasi JWT (JSON Web Token) dengan password terenkripsi bcrypt. Pengembangan MFA masuk dalam roadmap berikutnya."*

## 2. Antivirus & Perlindungan Server
**Yang diminta:** Bukti perlindungan terhadap malware di server.

**Cara mendapatkan Screenshot:**
* Karena mPaD di-host di VPS (seperti Biznet NEO/Linux), ambil screenshot terminal VPS Anda yang menunjukkan status keamanan.
* Jalankan perintah `sudo ufw status` (menunjukkan Firewall aktif) atau `sudo systemctl status fail2ban` (menunjukkan pencegahan brute-force aktif), lalu screenshot terminalnya.

## 3. Pengamanan Akses Server H2H
**Yang diminta:** Daftar siapa saja yang punya akses (kredensial) ke server.

**Cara mendapatkan Screenshot:**
* Buka terminal/SSH VPS server produksi mPaD Anda.
* Ketik perintah `getent group sudo` atau `cat /etc/passwd | grep bash`.
* Ambil screenshot hasilnya untuk membuktikan bahwa hanya user tertentu (admin/developer) yang memiliki hak akses shell ke server, dan tidak ada "sharing user-ID".

## 4. Audit Trail (Log Aktivitas)
**Yang diminta:** Catatan rekam jejak aktivitas API / H2H (siapa yang mengakses dan kapan).

**Cara mendapatkan Screenshot:**
* Buka database mPaD Anda dan ambil screenshot tabel `activity_logs` (jika Anda menggunakan library log) yang mencatat log user.
* **Alternatif:** Ambil screenshot isi dari file `storage/logs/laravel.log` yang menampilkan *request* API masuk, atau screenshot dashboard Laravel Telescope / Pulse.

## 5. API Security Tools
**Yang diminta:** Tools untuk memantau keamanan dan mencegah serangan API (seperti DDOS/Spam).

**Cara mendapatkan Screenshot:**
* Jika domain mPaD menggunakan **Cloudflare**, buka dashboard Cloudflare bagian *Security -> WAF (Web Application Firewall)* atau *Events*, lalu screenshot.
* **Alternatif dari Kode:** Buka file middleware atau `RouteServiceProvider.php`, lalu screenshot baris kode `RateLimiter::for('api', ...)` yang membatasi limit hit API (contoh: *60 request per menit*) untuk mencegah *spam/brute-force*.

## 6. Keamanan Data Pribadi (Enkripsi)
**Yang diminta:** Bukti bahwa data rahasia dilindungi (disandikan).

**Cara mendapatkan Screenshot:**
* **Data in Rest:** Buka database mPaD (melalui DBeaver/phpMyAdmin), lalu ambil screenshot pada tabel `users`. Tunjukkan bahwa kolom `password` isinya berupa karakter acak (sudah di-*hash* menggunakan Bcrypt).
* **Data in Transit:** Buka API mPaD di browser, klik ikon gembok (Lock) di URL bar, dan ambil screenshot sertifikat **SSL / HTTPS (Connection is secure)**.

---

### Tips Memasukkan ke Excel:
1. Beri nama file screenshot yang jelas (contoh: `1_Keamanan_Login.png`, `2_UFW_Firewall_Server.png`).
2. Masukkan semua gambar ke folder Google Drive (misalnya: **"Lampiran Assessment BNI - mPaD"**), atur aksesnya menjadi **"Anyone with the link can view"**.
3. Di file Excel TPRA, pada kolom **"Attachment Supporting Document"**, Anda cukup menuliskan: *"Terlampir pada tautan Google Drive berikut: [Masukkan Link Drive Anda]"*.
