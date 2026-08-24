# Dokumen Perencanaan: Integrasi Payment Gateway Mandiri (Bank H2H)
**Sistem M-PAD (Manajemen Pajak & Retribusi Daerah)**

---

## 1. Pendahuluan
Dokumen ini disusun sebagai panduan strategis untuk mengimplementasikan sistem pembayaran mandiri yang terintegrasi langsung dengan mitra perbankan (Bank Partner) melalui jalur **Host-to-Host (H2H)**. Tujuannya adalah untuk menghilangkan ketergantungan pada pihak ketiga (aggregator) seperti Midtrans atau Xendit, sehingga meningkatkan efisiensi biaya dan kemandirian sistem daerah.

---

## 2. Analisis Sistem Saat Ini

### 2.1 Mekanisme Billing (JIT Engine)
Sistem M-PAD menggunakan filosofi **Just-In-Time (JIT) Billing**. Tagihan dan denda tidak disimpan secara statis dalam database dalam jumlah besar, melainkan dihitung secara dinamis oleh `BillingService` saat ada permintaan (inquiry) dari petugas atau Wajib Pajak.
- **Dampak pada Integrasi**: Integrasi Bank harus mendukung perhitungan denda real-time agar nominal pada struk pembayaran bank sama persis dengan tagihan terbaru di sistem.

### 2.2 Kondisi Payment Gateway (Mock)
Ditemukan bahwa `PaymentGatewayController.php` saat ini masih bersifat *mock* (dummy) yang mensimulasikan respons Midtrans. Ini memberikan ruang kosong yang ideal untuk diisi oleh logika integrasi Bank Mandiri.

### 2.3 Pola Integrasi PBB (Benchmark)
Terdapat layanan `PbbBapendaService.php` yang sudah memiliki pola **Inquiry-Payment-Reversal**. Pola ini akan menjadi acuan (benchmark) untuk integrasi pajak dan retribusi lainnya karena sudah kompatibel dengan cara kerja core engine perbankan.

---

## 3. Skema Integrasi Host-to-Host (H2H)

Integrasi akan menggunakan model **Inquiry-Payment** (Bank sebagai Client, M-PAD sebagai Server).

### 3.1 Alur Transaksi (Sequence Diagram)

1. **Inquiry**: Bank mengirim `bill_number` -> M-PAD menghitung tagihan + denda -> M-PAD mengembalikan detail (Nama WP, Nominal).
2. **Payment**: Bank mengirim notifikasi bayar -> M-PAD validasi nominal -> M-PAD update status `lunas` -> M-PAD mencatat record `Payment`.

### 3.2 Contoh Payload (Estimasi)
**Request Inquiry:**
```json
{
  "bill_number": "SKRD-2026-0001",
  "signature": "abcdef123456...",
  "timestamp": "2026-04-17 17:00:00"
}
```

---

## 4. Skema QRIS (Dynamic Payment)

Selain integrasi Virtual Account, sistem akan mendukung pembayaran melalui standar **QRIS (Quick Response Indonesian Standard)** yang bersifat **dinamis**.

### 4.1 Mekanisme Pembuatan QRIS
1.  **Request QRIS**: Saat Wajib Pajak memilih metode QRIS, API M-PAD akan mengirimkan nominal akhir (termasuk denda) ke API Bank mitra.
2.  **Generate QR String**: Bank mengembalikan string standar QRIS (ASPI format) yang berisi Merchant ID (NMID), nama merchant, dan nominal transaksi.
3.  **Local Rendering**: API M-PAD menggunakan library `simple-qrcode` untuk merender string tersebut menjadi gambar QR Code untuk ditampilkan di dashboard atau aplikasi mobile.

### 4.2 Data & Atribut QRIS
- **NMID**: ID Merchant resmi BAPENDA.
- **Merchant Name**: "BAPENDA BAUBAU - [JENIS PAJAK]".
- **Amount**: Nilai tagihan bersifat *fixed* (pembayar tidak bisa mengubah nominal, mencegah *underpayment*).
- **Expiry**: QRIS dinamis akan diset kedaluwarsa dalam waktu 15-30 menit untuk menjaga validitas data denda.

### 4.3 Alur Callback QRIS
Mekanisme notifikasi QRIS akan menyatu dengan alur `BankCallbackController`. Begitu pembayaran sukses via app perbankan/e-wallet, bank akan mengirimkan hit ke endpoint callback kita, dan sistem akan melakukan sinkronisasi otomatis ke tabel `payments` dan mengubah status bill menjadi `lunas`.

---

## 5. Komponen Lanjutan (Production-Grade)

Untuk mencapai standar perbankan dan audit kepatuhan, komponen berikut wajib diimplementasikan:

### 5.1 Penanganan Reversal (Pembatalan)
Sistem harus mendukung endpoint `reversal` untuk membatalkan status pembayaran jika terjadi *timeout* di sisi Bank setelah dana terdebit. Ini menjaga konsistensi antara saldo bank dan catatan piutang daerah.

### 5.2 Idempotensi (Anti-Duplikasi)
Gunakan `transaction_id` dari bank sebagai kunci unik di tabel `payments`. Sistem akan menolak proses pembayaran jika ID transaksi yang sama dikirimkan dua kali, mencegah denda dihitung ganda.

### 5.3 Audit Trail & Raw Payload Logging
Setiap payload JSON mentah yang masuk dari Bank akan dicatat ke tabel log khusus (`api_logs`) beserta alamat IP pengirim dan timestamp presisi milidetik.

### 5.4 Penomoran NTPD & NTB
- **NTB (Nomor Transaksi Bank)**: Disimpan sebagai referensi eksternal.
- **NTPD (Nomor Transaksi Penerimaan Daerah)**: Dihasilkan oleh M-PAD sebagai bukti sah penerimaan negara/daerah.

### 5.5 Rekonsiliasi Otomatis (Settlement)
Setiap akhir hari (EOD), sistem akan membandingkan daftar transaksi sukses di M-PAD dengan laporan harian dari Bank mitra untuk memastikan tidak ada transaksi yang tertinggal.

---

## 6. Keamanan & Validasi

Untuk menjamin keamanan transaksi keuangan daerah, dua lapis pengamanan wajib diterapkan:

1. **IP Whitelisting**: Server M-PAD hanya akan menerima request dari alamat IP resmi milik data center Bank.
2. **Signature Verification (HMAC-SHA256)**: Bank dan M-PAD berbagi *Shared Secret Key*. Setiap request harus menyertakan signature yang divalidasi oleh M-PAD untuk memastikan data tidak dimanipulasi di tengah jalan.

---

## 7. Panduan Komunikasi Teknis dengan IT Bank

Saat melakukan koordinasi dengan tim IT Bank, berikut adalah daftar ceklis informasi yang harus dikumpulkan dan disampaikan:

### 7.1 Hal yang Harus Ditanyakan (Kepada Bank)
1.  **Tech-Spec API**: Format payload (JSON/XML), endpoint Sandbox, dan Production.
2.  **Autentikasi**: Metode keamanan (Bearer Token, HMAC, atau mTLS).
3.  **Mekanisme Virtual Account**: Panjang digit VA, Kode Biller, dan tipe VA (Statis/Dinamis).
4.  **Prosedur Reversal**: Langkah teknis jika sistem M-PAD mengirimkan permintaan pembatalan akibat *timeout*.
5.  **Data Rekonsiliasi**: Format dan jadwal pengiriman laporan EOD (Settlement).

### 7.2 Hal yang Harus Diinformasikan (Kepada Bank)
1.  **Tech Stack**: M-PAD menggunakan REST API pada HTTPS.
2.  **JIT Billing**: Penegasan bahwa **denda dihitung real-time** pada saat Inquiry.
3.  **Endpoint Callback**: Lokasi penyambungan data (`/api/v1/bank/inquiry` & `/api/v1/bank/payment`).
4.  **Idempotensi**: M-PAD menggunakan `transaction_id` Bank sebagai kunci unik pencegah duplikasi.

---

## 9. Arsitektur Multi-Bank & Multi-Method (Driver Pattern)

Untuk mendukung fleksibilitas di masa depan (menambah bank atau metode pembayaran baru tanpa merombak kode), sistem direncanakan menggunakan **Driver Pattern**.

### 9.1 Komponen Arsitektur
1.  **PaymentGatewayInterface**: Kontrak standar yang harus dipatuhi oleh semua driver bank (Inquiry, Payment, Reversal).
2.  **PaymentManager (Factory)**: Komponen pusat yang menentukan driver mana yang aktif berdasarkan konfigurasi atau pilihan Wajib Pajak.
3.  **Specific Drivers**:
    *   `BankSultraDriver`: Menangani logika khusus API Bank Sultra.
    *   `MandiriDriver`: Menangani logika khusus API Bank Mandiri.
    *   `QRISDriver`: Menangani integrasi QRIS dinamis.

### 9.2 Keuntungan Strategis
*   **Scalability**: Menambah Bank BNI atau BRI hanya perlu membuat satu file baru.
*   **Low Coupling**: Masalah pada satu koneksi bank tidak akan mengganggu metode pembayaran lainnya.
*   **Consistency**: Seluruh tim pengembang menggunakan satu standar *interface* yang sama.

---

## 10. Pertanyaan Terbuka & Langkah Selanjutnya

Sebelum implementasi teknis dimulai, beberapa hal berikut perlu dikonfirmasi:
- **Nama Bank Mitra**: Penyesuaian format payload sesuai spesifikasi bank (Bank Sultra, BNI, dll).
- **Format Virtual Account**: Apakah menggunakan nomor tagihan asli atau format angka murni (numeric) sesuai standar bank.
- **Sertifikat Keamanan**: Apakah bank mewajibkan penggunaan VPN atau cukup melalui jalur HTTPS yang diamankan Signature.

---
*Dokumen ini dibuat otomatis oleh Antigravity AI pada 17 April 2026.*
