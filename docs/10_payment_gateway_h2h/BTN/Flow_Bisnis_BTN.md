# FLOW BISNIS INTEGRASI BTN - M-PAD
## BADAN PENDAPATAN DAERAH (BAPENDA) KOTA BAUBAU

Dokumen ini menjelaskan alur teknis dan bisnis integrasi pembayaran *Virtual Account* menggunakan standar **BTN OPEN API** (SNAP) antara sistem **M-PAD** (Manajemen Pendapatan Asli Daerah) Kota Baubau dengan **Bank BTN**.

---

### 1. Inisiasi & Pendaftaran Tagihan (Create VA)
1. **Wajib Pajak (WP)** masuk ke dalam aplikasi M-PAD (melalui Web Admin, Petugas, atau Mobile App) dan memilih tagihan retribusi/pajak daerah yang belum dibayar.
2. Wajib Pajak memilih metode pembayaran **Bank BTN (Virtual Account)**.
3. Sistem **M-PAD (Backend)** meng-generate ID Transaksi unik dan menyusun data tagihan.
4. M-PAD melakukan pemanggilan ke server BTN (`POST /snap/v1/transfer-va/create-va`) untuk mendaftarkan tagihan secara resmi di sistem bank.
5. Server BTN merespons dengan status berhasil, dan Aplikasi M-PAD menampilkan **Nomor BTN VA** beserta batas waktu pembayaran kepada Wajib Pajak.

### 2. Pengecekan Pembayaran (Inquiry)
1. Wajib Pajak memasukkan Nomor BTN VA melalui salah satu *channel* pembayaran Bank BTN (BTN Mobile, ATM, Internet Banking, atau Teller).
2. Terdapat 2 opsi skema Inquiry pada BTN Open API:
   * **Skema Internal Bank**: Bank BTN mengecek tagihan secara internal berdasarkan data yang sudah di-push melalui *Create VA* sebelumnya.
   * **Skema Biller (Callback)**: Sistem BTN memanggil *endpoint Inquiry* milik server M-PAD (`POST /{Domain M-PAD}/snap/v1/transfer-va/inquiry`) untuk memvalidasi tagihan secara *real-time*.
3. Channel Bank BTN menampilkan detail tagihan (Nama WP dan Nominal Tagihan) kepada Wajib Pajak.
4. Wajib Pajak menekan tombol bayar/konfirmasi. Saldo WP terpotong dan dana masuk ke rekening (RKUD) Bapenda Kota Baubau di Bank BTN.

### 3. Konfirmasi Otomatis (Payment Callback)
1. Sesaat setelah pembayaran sukses diproses pada jaringan Bank BTN, sistem **BTN** mengirimkan *webhook/callback* notifikasi pembayaran sukses ke *endpoint Payment* milik M-PAD (`POST /{Domain M-PAD}/snap/v1/transfer-va/payment`).
2. Server **M-PAD** menerima *request* tersebut dan memvalidasi keaslian *Digital Signature* (Symmetric HMAC-SHA512) serta *Timestamp*.
3. Server M-PAD memverifikasi bahwa nominal yang ditransfer sudah sesuai dengan nominal tagihan.
4. M-PAD memperbarui status tagihan di dalam *database* menjadi **LUNAS (Paid)**.
5. M-PAD mengirimkan balasan `HTTP 200 OK` (Kode Respon Sukses `2002500` atau sesuai service code) ke BTN sebagai tanda notifikasi berhasil diterima dan diproses.

### 4. Penyelesaian & Penerbitan Struk
1. Sistem M-PAD mencatat riwayat transaksi secara mendetail ke dalam *Payment Logs* dan *Virtual Ledger* pemerintah daerah.
2. Wajib Pajak menerima notifikasi di aplikasi M-PAD (misalnya pada M-PAD Mobile) bahwa pembayaran telah sukses.
3. M-PAD secara otomatis menerbitkan **Bukti Bayar Sah (SSRD / Surat Setoran Retribusi Daerah)** yang dilengkapi dengan QR Code otentikasi. Bukti ini sah secara hukum dan dapat diunduh oleh Wajib Pajak maupun Petugas Bapenda secara *real-time*.

---
*Dokumen ini merupakan panduan implementasi teknis dan alur bisnis antara Bapenda Kota Baubau dan Bank BTN berlandaskan dokumen Spesifikasi BTN OPEN API (Virtual Account Series v2.04).*
