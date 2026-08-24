# Poin Pembahasan IT mPaD - Meeting Integrasi H2H BNI

**Topik:** Rencana Integrasi Pembayaran H2H Virtual Account & QRIS API (Standar SNAP BI)
**Tanggal:** Jumat, 17 Juli 2026
**Posisi Kita:** Tim Teknis Bapenda (Sistem mPaD)

Sebagai tim teknis mPaD, fokus kita dalam meeting ini adalah memastikan arsitektur sistem mPaD dapat terhubung dengan mulus dan aman ke sistem BNI sesuai standar BI. Berikut adalah daftar pertanyaan dan poin konfirmasi yang perlu Anda sampaikan kepada Tim IT BNI:

## 1. Topologi, Infrastruktur & Keamanan (Network & Security)
- **Whitelisting IP:** "Kami akan menyiapkan IP Public server produksi mPaD (Biznet NEO). Kami butuh daftar IP Address Public dari sisi BNI (baik untuk UAT maupun Production) agar dapat kami _whitelist_ di firewall server kami."
- **Konektivitas:** "Apakah koneksi menggunakan Internet Publik biasa (dengan pengamanan JWS/Signature standar SNAP) atau BNI mewajibkan penggunaan VPN IP-Sec (Leased Line)?"
- **Dokumen Arsitektur:** "Kami sedang menyiapkan dokumen Topologi Jaringan, ke mana dokumen teknis ini harus kami _submit_ nantinya?"

## 2. Standar Kriptografi & Signature (SNAP BI)
- **Pertukaran Key (Asymmetric RSA):** "Untuk standar SNAP BI, apakah kami (mPaD) harus men-generate RSA Key Pair (Private & Public Key) sendiri dan mengirimkan Public Key-nya ke BNI, atau BNI menyediakan sistem portal untuk pendaftaran _Key_?"
- **Library/SDK:** "Apakah BNI menyediakan contoh _code_, _SDK_, atau _library_ (khususnya untuk PHP/Laravel) untuk mempermudah pembuatan Signature (Asymmetric RS256 / HMAC SHA256)?"

## 3. Skema Transaksi H2H & Fitur Unggulan (JIT Billing & NIK Inquiry)
- **Keputusan Arsitektur Integrasi (Model Aggregator/Push):** Sesuai arahan Kepala Bapenda (Bpk. Massad), alur yang disepakati adalah **Bapenda yang secara aktif menembak API Create VA (Push) ke BNI**.
- **Two-Way Handshake & Mitigasi Gagal Transfer:** Kami akan menerapkan mekanisme dua arah: Bapenda melakukan *Create VA*, lalu BNI merespons dengan *Real-time Callback/Webhook* saat terbayar. M-PAD juga dilengkapi *Check Status API* untuk *auto-correction* saat proses rekonsiliasi (*menyandingkan data*) harian.
- **Just-In-Time (JIT) Penalty Engine:** "Saat Wajib Pajak mengakses tagihan di mPaD, _engine_ kami akan menghitung denda keterlambatan secara presisi detik itu juga, baru kemudian kami *Push Create VA* beserta nominal final ke BNI."
- **Idempotency Check:** "Untuk _Payment_, sistem kami memiliki _Idempotency Check_ untuk mencegah _double payment_. Jika terjadi _retry_ dari BNI karena _timeout_ jaringan, tagihan akan otomatis terkunci."
- **Reverse Inquiry Berbasis NIK (Ultimate UX):** "Kami memiliki fitur di mana Wajib Pajak cukup memberikan 16-digit NIK ke Teller/ATM, dan API kami akan mengembalikan _array_ seluruh tagihan aktif (PBB, Reklame, Retribusi). Apakah fitur _Multi-Inquiry_ ini didukung di _channel_ BNI?"

## 4. Alur Transaksi QRIS (API Dinamis)
- **Penolakan QRIS Statis:** "Kami menegaskan bahwa mPaD **tidak dapat menggunakan QRIS Statis** (stiker/cetak). Kami mutlak membutuhkan **QRIS Dinamis via API (Open API/H2H)** agar nominal terkunci otomatis dan _callback_ bisa kami terima secara _real-time_."
- **Standar Keamanan API:** "Apakah format API QRIS Dinamis ini sudah menggunakan standar **SNAP BI (Signature Asymmetric RSA/HMAC)**, atau masih menggunakan _legacy API_ BNI?"
- **Masa Berlaku (Expiry Time):** "Apakah batas waktu kedaluwarsa (_expiry time_) QR Code ini bisa kami atur sendiri secara dinamis dari sisi mPaD (misal: kadaluarsa dalam 24 jam)?"
- **Limit Transaksi:** "Berapa limit maksimal nominal tagihan untuk satu kali *scan* QRIS Dinamis BNI ini? Mengingat tagihan PBB/Pajak Daerah terkadang bernominal besar di atas Rp10.000.000."
- **Skenario Layar Teller/POS (Terminal ID):** "Jika digunakan di loket Bapenda atau tablet Petugas (sebagai POS), apakah API mengharuskan kami mengirim **Terminal ID** spesifik per alat, atau cukup 1 _Merchant ID_ global?"
- **Skenario QR Expired di Loket:** "Jika _QR Code_ kedaluwarsa sebelum sempat di-_scan_ oleh warga, apakah API mengizinkan kami me-request _Generate_ QR ulang dengan **Nomor Transaksi (Bill ID) yang sama**, atau harus _generate_ Bill ID baru?"
- **Notifikasi Keberhasilan (Callback):** "Saat warga selesai bayar, apakah BNI langsung menembak _webhook_ ke server kami detik itu juga (_real-time_), atau ada jeda?"
- **Check Status API:** "Apabila _callback_ dari BNI gagal kami terima (misal karena jaringan), apakah tersedia API _Check Status_ untuk mengecek status pembayaran QRIS secara manual dari sisi kami?"

## 5. Otomasi Pasca-Bayar & Rekonsiliasi (Settlement Kas Daerah)
- **Settlement H+0 (Real-time Kas Daerah):** "Di presentasi BNI disebutkan _settlement_ (pencairan) QRIS adalah H+1. Mengingat ini adalah uang negara (Pajak Daerah), apakah BNI bisa memberikan pengecualian **Settlement H+0 (Hari yang sama)** langsung masuk ke Rekening Kas Umum Daerah (RKUD) tanpa menunggu keesokan harinya?"
- **Auto-TTE Hook (BSrE):** "Informasikan bahwa _Response HTTP 200 OK_ dari BNI pada saat _Payment_ akan langsung memicu _webhook_ ke server BSrE untuk penerbitan Bukti Bayar ber-TTE saat itu juga."
- **Rekonsiliasi Otomatis:** "Setiap pukul 00:00, sistem mPaD melakukan _Matching Harian_. Kami membutuhkan **Format Laporan Rekonsiliasi harian** dari BNI, dan bagaimana SOP untuk _Force Settlement_ jika ada transaksi anomali (_pending_ di mPaD tapi sukses di BNI)?"

## 6. Daftar Kebutuhan Teknis (API Handshake) & UAT
- **Dokumentasi & Sandbox:** "Kapan kami bisa mendapatkan Dokumentasi API Terbaru (Spesifikasi Inquiry-Payment, Create VA, Generate QRIS) beserta Kredensial Sandbox/UAT?"
- **Parameter UI/UX:** "Berapa batas maksimal karakter (Spasi/Huruf) yang diperbolehkan tampil di layar ATM/Mobile Banking BNI untuk 'Nama Wajib Pajak' dan 'Detail Tagihan'?"
- **Grup Komunikasi Teknis:** "Kami mengusulkan agar dibuatkan grup komunikasi teknis khusus (misal via WhatsApp) antara Developer mPaD dan Tim IT Support BNI agar proses _troubleshooting_ selama UAT berjalan cepat."

---
**💡 Tips Saat Meeting:**
Anda dapat melakukan _screen-sharing_ **Dokumen Topologi Jaringan H2H** yang telah disiapkan sebelumnya untuk menunjukkan kesiapan arsitektur mPaD kepada pihak BNI.
