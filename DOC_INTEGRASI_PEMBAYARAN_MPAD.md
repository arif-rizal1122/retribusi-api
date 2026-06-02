# BLUEPRINT INTEGRASI PEMBAYARAN M-PAD (OMNI-CHANNEL & H2H)
**Pemerintah Kota Baubau - Badan Pendapatan Daerah**

---

## 1. OVERVIEW SISTEM
M-PAD (Management Pajak Daerah) bertindak sebagai **Payment Hub** pusat yang mengorganisir seluruh pembayaran Pajak Bumi & Bangunan (PBB) serta Retribusi Daerah. Sistem ini menggunakan arsitektur **Driver-Based** untuk menjamin interoperabilitas dengan berbagai sistem perbankan nasional.

## 2. ARSITEKTUR OMNI-CHANNEL (JALUR PEMBAYARAN)
Sistem M-PAD melayani pembayaran melalui tiga jalur utama yang terintegrasi secara real-time:

### A. Jalur Mobile Citizen (Self-Service)
*   **Alur**: WP Login via NIK -> Lihat Tagihan -> Pilih Metode (QRIS/VA) -> Bayar.
*   **Teknis**: M-PAD melakukan *request* dinamis ke API Bank untuk mendapatkan QRIS/VA per transaksi.
*   **Status**: Real-time update setelah pembayaran sukses di m-banking/e-wallet manapun.

### B. Jalur Petugas Lapangan (Jemput Bola)
*   **Alur**: Petugas Scan Objek -> Muncul Tagihan -> Tampilkan QRIS Bank di HP Petugas -> WP Scan & Bayar.
*   **Teknis**: Membantu penagihan di lokasi yang tidak memiliki akses fisik ke ATM/Bank.

### C. Jalur Perbankan (H2H Direct)
*   **Alur**: WP ke ATM/Teller -> Masukkan Kode Bayar/NIK -> Inquiry -> Payment.
*   **Teknis**: Bank melakukan *Inquiry* ke server M-PAD untuk validasi nominal dan denda terbaru (Just-In-Time Penalty).

---

## 3. SKEMA DATABASE (AUDIT READY)
Sistem memisahkan pencatatan kewajiban (Invoice) dan arus uang (Transaksi).

### Tabel `bills` (Invoice/Tagihan)
*   Menyimpan data kewajiban WP (Nominal Pokok, Masa Pajak, Jatuh Tempo).
*   Memiliki `payment_code` unik sebagai referensi utama perbankan.

### Tabel `payments` (Transaction/Bukti Bayar)
*   Mencatat mutasi uang masuk dari Bank (`bank_transaction_id`).

---

## 4. INTEGRASI H2H (HOST-TO-HOST) MULTI-BANK
M-PAD menggunakan satu skema standar yang siap dihubungkan dengan Bank Mandiri, BNI, BRI, BPD Sultra, BSI, dan bank lainnya.

### Standar API:
1.  **Inquiry**: Bank mengirim `NIK` atau `Payment Code`. M-PAD membalas dengan rincian `Pokok + Denda + Total`.
2.  **Payment**: Bank mengirim data pelunasan. M-PAD melakukan verifikasi nominal dan mengubah status menjadi `LUNAS`.

---

## 5. SKEMA INQUIRY PBB BERBASIS NIK (PENERTIBAN PEMBAYARAN)
M-PAD menerapkan skema **"NIK-Centric Billing"** untuk meningkatkan disiplin pajak daerah.

1.  **Agregasi NOP**: Satu NIK dihubungkan dengan seluruh Nomor Objek Pajak (NOP) yang dimiliki WP.
2.  **Universal Inquiry**: Saat WP memasukkan NIK di ATM/Mobile Banking, M-PAD tidak hanya menampilkan satu tagihan, melainkan **seluruh daftar tunggakan PBB** untuk semua aset WP tersebut.
3.  **Cross-Discipline**: Fitur ini mencegah WP hanya membayar satu aset dan mengabaikan yang lain, sehingga memaksimalkan penerimaan PAD secara kolektif.

---

## 6. PERSIAPAN PASSWORDLESS LOGIN (OTP WA/EMAIL)
Untuk memudahkan akses Wajib Pajak tanpa perlu menghafal password:

*   **WhatsApp OTP**: M-PAD mengintegrasikan **WA Gateway** (via library Baileys) untuk mengirim kode OTP 6-digit saat login.
*   **Email Fallback**: Pengiriman OTP via SMTP sebagai cadangan jika nomor WA tidak aktif.
*   **Security**: Setiap OTP memiliki masa berlaku (Expiry) 5 menit dan menggunakan algoritma *Rate Limiting* untuk mencegah serangan *Brute Force*.

---

## 7. REFERENSI IMPLEMENTASI NASIONAL (TP2DD)
Integrasi H2H M-PAD sejalan dengan program Tim Percepatan dan Perluasan Digitalisasi Daerah (TP2DD) yang didukung oleh Kemkomdigi dan BI. Beberapa daerah referensi:

*   **Provinsi Bali**: Sukses integrasi PBB-P2 dan Pajak Hotel secara H2H dengan BPD Bali.
*   **Sumatera Utara**: Layanan H2H perbankan terluas untuk percepatan kas daerah.
*   **Kabupaten Bengkalis**: Pelopor integrasi H2H PBB-P2 untuk kemudahan WP di pelosok.
*   **Sragen & Nunukan**: Integrasi H2H BPHTB yang mensinkronkan data antara Pemda, BPD, dan BPN.

---

## 8. KEAMANAN & STANDAR SNAP BI
Integrasi ini mengikuti Standar Nasional Open API Pembayaran (SNAP BI) dari Bank Indonesia untuk menjamin keamanan transaksi antar institusi finansial.

---

## 9. MEKANISME IDENTIFIKASI OTOMATIS (MAPPING LOGIC)
Setiap `payment_code` adalah kunci unik (Master Key) yang secara relasional terhubung ke data Wajib Pajak, Objek Pajak, dan OPD Pengelola, memungkinkan respon API di bawah 10ms.
