# Blueprints & Dokumentasi Teknis Integrasi BRI SNAP BI (M-PAD Kota Baubau)

Dokumen ini merupakan ringkasan komprehensif dari hasil rapat dan dokumen teknis yang terdapat dalam folder `Hasil Rapat Dengan BRI`. Dokumen ini disusun sebagai panduan utama bagi tim pengembang dan stakeholder untuk memahami alur kerja, standar keamanan, dan spesifikasi API yang diimplementasikan.

---

## 1. Tata Kelola & Roadmap Integrasi (Gmail Context)
Berdasarkan korespondensi resmi BRI, integrasi ini mengikuti standar **SNAP BI** (Standar Nasional Open API Pembayaran Indonesia) yang diatur oleh Bank Indonesia.

### Tahapan Implementasi:
1.  **Administrasi & Legal**: Penandatanganan PKS (Perjanjian Kerja Sama) dan pendaftaran di Portal Developer BRIAPI.
2.  **Sandboxing**: Pengujian fitur di lingkungan sandbox menggunakan credential dummy.
3.  **Sertifikasi ASPI**: Melakukan self-assessment teknis melalui portal ASPI Devsite untuk mendapatkan sertifikat kelayakan.
4.  **Produksi**: Migrasi ke lingkungan live dengan whitelisting IP dan Public Key asli.

---

## 2. Arsitektur Teknis & Konektivitas
Sistem M-PAD bertindak sebagai **Partner/Mitra** yang menyediakan endpoint untuk dipanggil oleh BRI (Callback) dan memanggil API BRI.

### A. Spesifikasi Koneksi (Whitelisting)
- **IP Address**: Dibutuhkan IP Public statis dari server M-PAD untuk didaftarkan pada firewall BRI.
- **Protokol**: Komunikasi wajib menggunakan **HTTPS** dengan TLS 1.2 ke atas.

### B. Keamanan & Kriptografi
- **Signature**: Menggunakan RSA-2048 dengan algoritma SHA-256.
- **Header Standar SNAP**:
  - `X-PARTNER-ID`: ID Mitra yang diberikan BRI.
  - `X-SIGNATURE`: Signature RSA untuk validasi keaslian request.
  - `X-TIMESTAMP`: Waktu request dalam format ISO8601.
  - `X-EXTERNAL-ID`: ID unik untuk setiap transaksi (idempotency).

---

## 3. Spesifikasi API (Hasil Ekstraksi XLSX)

### A. Virtual Account (BRIVA) Online
| Fitur | Deskripsi | Endpoint Callback (M-PAD) |
| :--- | :--- | :--- |
| **Auth Token** | Pertukaran Client ID/Secret untuk Access Token | `/snap/v1.0/access-token/b2b` |
| **Inquiry** | BRI mengecek data tagihan WP ke M-PAD | `/snap/v1.0/transfer-va/inquiry` |
| **Payment** | BRI mengirim notifikasi pembayaran sukses | `/snap/v1.0/transfer-va/payment` |

### B. Fitur Onboarding & KYC (Detail SIT)
Dokumen SIT menunjukkan adanya fitur pendukung (kemungkinan untuk pendaftaran WP baru):
- **Submit Data**: Mengirim data KTP, Nama, Tanggal Lahir (wajib sesuai KTP/Dukcapil).
- **OTP Management**: API Resend OTP dengan masa berlaku (lifetime) **5 Menit**.
- **Email Verification**: API Resend Email untuk verifikasi akun.
- **Send KYC**: Integrasi dengan layanan KYC (seperti Privy) untuk persetujuan dokumen.
- **Check Progress**: Pengecekan status pengajuan (Status `11` = Sukses, Status `4-10` = Proses).

---

## 4. Skenario Pengujian Fungsional (SIT)
Berikut adalah daftar kondisi yang harus lulus uji sebelum Go-Live:

### Skenario Positif (Success Path)
- Inquiry dengan nomor VA yang valid dan muncul data Nama/Nominal sesuai sistem.
- Payment dengan nominal yang tepat dan status di M-PAD berubah menjadi 'Lunas' seketika.
- Resend OTP berhasil mengirim ulang kode jika belum melewati batas percobaan.

### Skenario Negatif (Failure Path)
- **Invalid Signature**: Request ditolak jika signature tidak valid (Kode `401xx01`).
- **VA Not Found**: Respon jika nomor VA tidak terdaftar di sistem M-PAD (Kode `404xx00`).
- **OTP Expired**: Respon jika memasukkan OTP setelah 5 menit (Kode `4030612`).
- **Data Mismatch**: Penolakan jika Nama atau Tanggal Lahir tidak sesuai data Dukcapil saat registrasi.
- **Already Paid**: Respon jika tagihan yang sudah lunas dibayar kembali (Kode `409xx00`).

---

## 5. Standar Operasional Prosedur (SOP) Rekonsiliasi
Sesuai dengan `Template Dokumen Rekonsiliasi.docx`, proses penyamaan data keuangan dilakukan setiap hari:

### Aturan Main:
1.  **Harian**: Rekonsiliasi dilakukan untuk transaksi hari sebelumnya (H+1).
2.  **Data Utama**: Nomor VA, Tanggal Transaksi, Nominal, dan Bank Reference Number.
3.  **Penanganan Selisih**:
    - Jika data ada di BRI tapi tidak ada di M-PAD (*Missing in Partner*), tim IT harus melakukan pengecekan log callback.
    - Jika nominal berbeda, transaksi ditandai untuk investigasi manual.
4.  **Otorisasi**: Laporan rekonsiliasi wajib ditandatangani oleh *Tested & Verified By* (IT) dan *Approved By* (Manager Keuangan).

---

## 6. Daftar Kepatuhan ASPI (Compliance Checklist)
Tim IT wajib memastikan poin berikut terpenuhi di portal ASPI Devsite:
- [ ] Implementasi Endpoint Token B2B sesuai standar SNAP.
- [ ] Implementasi Signature Auth B2B (Asymmetric).
- [ ] Validasi Request Body terhadap JSON Schema SNAP BI.
- [ ] Penanganan Error Code sesuai tabel standar ASPI.
- [ ] Pengunggahan Log Transaksi sukses dan gagal ke portal sebagai bukti UAT.

---
**Catatan**: Dokumen ini bersifat dinamis dan harus diperbarui jika ada perubahan teknis dari pihak BRI selama masa pengembangan.

## 7. Sandboxing Credentials (Extracted from Value QRIS Notif Sandboxing.xlsx)
Data berikut merupakan credential sandbox dari BRI yang wajib digunakan selama tahap pengujian:

### A. Konfigurasi Email
Email berikut telah didaftarkan pada portal Developer BRI (https://developers.bri.co.id/id):
- **Notification QRIS**: `bapendakotabaubau21@gmail.com`
- **BRIVA Online**: `bapendakotabaubau21@gmail.com`

### B. Konfigurasi BRIVA Online (Environment Variables)
> [!IMPORTANT]
> **Client ID dan Client Secret di bawah ini di-generate secara mandiri oleh pihak Bapenda (M-PAD), bukan diberikan oleh BRI.**
> Ini berarti M-PAD bertindak sebagai *Authorization Server* untuk request yang masuk dari BRI (BRI akan memanggil endpoint `/access-token/b2b` dengan kredensial ini untuk mendapatkan Access Token). Untuk tahap *Production*, Bapenda wajib men-generate kredensial baru yang aman dan menyerahkannya ke BRI.

- **URL Service token**: `https://api.mpad.online/api/snap/v1.1/access-token/b2b`
- **URL Service Notify**: `https://api.mpad.online/api/snap/v1.1/qr/qr-mpm-notify`
- **client ID**: `[REDACTED_CLIENT_ID]` (Sandboxing)
- **client Secret**: `[REDACTED_CLIENT_SECRET]` (Sandboxing)

