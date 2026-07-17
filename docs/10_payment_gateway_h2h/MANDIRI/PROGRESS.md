# Progres Integrasi QRIS API Bank Mandiri

Dokumen ini mencatat progres integrasi sistem mPAD Pemkot Baubau dengan QRIS API Bank Mandiri (H2H), berdasarkan log komunikasi dengan pihak Bank Mandiri.

## Timeline Progres

### 5 Mei 2026
- **Inisiasi Komunikasi**: Pembentukan grup koordinasi yang melibatkan tim Bank Mandiri Baubau, tim Bapenda Kota Baubau, tim TBR Mandiri Regional Makassar, dan Tim TBRS Mandiri Kantor Pusat.

### 7 Mei 2026
- **Penjelasan Alur Integrasi**: Pihak Bank Mandiri menjelaskan 3 tahap integrasi:
  1. **SIT (System Integration Test)**: Pihak bank menyediakan _sandbox_ untuk keperluan testing oleh partner (Bapenda).
  2. **Pra UAT & UAT**: Pemberian kredensial UAT. Membutuhkan **Public Key** dan **Callback URL UAT** dari pihak Bapenda. Testing dilakukan bersama.
  3. **PTR (Production)**: Pemberian kredensial Production. Membutuhkan **Public Key** dan **Callback URL Production**.
- **Tindak Lanjut**: Tim Bapenda (Muhdan Fyan) telah mengirimkan data untuk pengajuan *sandbox* SIT (Nama, Email, dan No HP) kepada pihak Bank Mandiri. Pihak bank sedang memproses kredensial *sandbox*.

### 25 Juni 2026
- **Status UAT**: Tim teknis Bank Mandiri menginfokan bahwa sistem sudah bisa naik ke tahap UAT.
- **Kebutuhan**: Pihak Bank kembali mengingatkan kebutuhan **Public Key** dan **Callback URL** dari Bapenda untuk UAT.

### 29 Juni 2026
- **Persyaratan Keamanan Baru (TLS 1.3)**: Regulator (Bank Indonesia dan ASPI) mengharuskan peningkatan standar keamanan menggunakan protokol **TLS 1.3**.
- **Konfirmasi Kesiapan Bapenda**: Tim Teknis Bapenda mengonfirmasi bahwa sistem telah mendukung penuh TLS 1.3 dan menyatakan kesiapan untuk melaksanakan pengujian (_Get Token_, _Generate QR_, dan _Payment_). Bapenda kembali meminta kredensial _Environment_ UAT/DEV (Client ID, Secret Key, Base URL).

### 1 Juli 2026
- **Tindak Lanjut UAT**: Koordinator IT Bapenda mendesak percepatan penyediaan Kredensial Environment UAT/DEV mengingat aplikasi m-PAD akan segera di-_launching_.
- **Generate Public Key**: Pihak Bank Mandiri menyarankan jika Public Key belum ada, tim Bapenda dapat melakukan *generate* RSA Key Pair (default 1024-bit) menggunakan tools seperti `cryptotools.net/rsagen`.
- **Status Saat Ini**: Tim *developer* Bapenda sedang mempersiapkan *Public Key* tersebut untuk segera diserahkan ke pihak Mandiri agar kredensial UAT dapat dirilis.

---

## Action Items (Pending)

1. [ ] **Bapenda**: Melakukan generate RSA Key Pair (Public & Private Key, direkomendasikan 1024-bit).
2. [ ] **Bapenda**: Menyiapkan URL Endpoint Callback untuk UAT.
3. [ ] **Bapenda**: Menyerahkan Public Key dan URL Callback UAT ke pihak Bank Mandiri.
4. [ ] **Bank Mandiri**: Menerbitkan dan menyerahkan Kredensial UAT (Client ID, Secret Key, Base URL, dll) dan dokumentasi pendukung.
5. [ ] **Bapenda & Bank Mandiri**: Melakukan pengujian integrasi UAT (Get Token, Generate QR, Payment, dan Notifikasi/Callback).
