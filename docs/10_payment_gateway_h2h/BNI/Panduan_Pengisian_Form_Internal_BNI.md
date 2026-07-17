# Panduan Pengisian Form Internal Integrasi Layanan API BNI (v1.3)

**Dokumen Acuan:** `Form Internal Integrasi Layanan API BNI_v1.3_.pdf`
**Konteks Sistem:** M-PAD (Smart Revenue System) - Bapenda Kota Baubau

> [!NOTE]
> **Konteks Penting**
> Di bagian atas form (Hal. 1) tertulis jelas: **"(TIDAK UNTUK DIISI OLEH NASABAH ATAU EKSTERNAL)"**. 
> Formulir ini sebenarnya adalah dokumen internal Divisi WDC BNI. Namun dalam prakteknya, pihak Bank sering mengirimkan draf ini agar Nasabah/Mitra menyetorkan data (menjawab/mengisi) sesuai format tersebut, untuk kemudian mereka *input* ke sistem internal BNI.
>
> Panduan di bawah ini disusun agar selaras dengan arsitektur **M-PAD**, khusus untuk layanan **Virtual Account** dan **QRIS Dinamis (Standar SNAP BI)**.

---

## Halaman 1: Informasi Pemohon
Bagian ini mendefinisikan identitas institusi dan tujuan penggunaan API.

- **Jenis Permohonan:** ☑️ Pendaftaran Layanan
- **Nama Perusahaan:** `Bapenda Kota Baubau` (atau Pemerintah Kota Baubau)
- **Nama Alias:** `M-PAD (Smart Revenue System)`
- **Jenis Usaha:** ☑️ **Bukan Teknologi Finansial**. (Tuliskan: `Instansi Pemerintah Daerah - Badan Pendapatan Daerah`)
- **Tujuan Penggunaan API:** `Integrasi pembayaran Pajak dan Retribusi Daerah secara Host-to-Host (H2H) menggunakan Virtual Account dan QRIS Dinamis API (Standar SNAP BI) ke sistem M-PAD.`
- **Nomor Aplikasi/Formulir Produk:** `M-PAD`

---

## Halaman 2: Contact Person (Data PIC)
Bagian ini berisi daftar orang yang bertanggung jawab secara teknis dan operasional.

- **Daftar PIC:** Isi nama-nama tim teknis Bapenda (Project Manager, PIC Credentials, PIC Integrasi API).
- *Saran:* Gunakan email resmi kedinasan (misal domain `@baubaukota.go.id` jika ada) agar terlihat lebih kredibel di sisi BNI.
- **Penggunaan Layanan API (Tabel Bawah):** 
  - **KOSONGKAN SEMUA** (termasuk *One Gate Payment, SNAP Transfer Credit, MPN*).
  - *Alasan:* M-PAD berfokus pada **Penerimaan (Collection)** PAD, bukan layanan transfer dana/MPN.

---

## Halaman 3: Layanan API (2)
Bagian ini khusus untuk layanan P2P, RDN, dan Remittance.

- **Tindakan:** **KOSONGKAN SEMUA** *(RDN, P2P Lending, RDF, Outgoing/Incoming Remittance, BNI Move)*.

---

## Halaman 4: Layanan API (3) 👉 FOKUS UTAMA VIRTUAL ACCOUNT
Halaman ini sangat krusial karena memuat persetujuan layanan Virtual Account H2H.

- **BNIDirect, FSCM, VA Debit, E-Collection:** KOSONGKAN.
- **SNAP Virtual Account:** **(WAJIB DICENTANG)**
  - ☑️ **Create VA** *(Digunakan oleh JIT Penalty Engine M-PAD untuk "Push" pembuatan tagihan spesifik ke BNI).*
  - ☑️ **Update VA** *(Opsional tapi disarankan, jika M-PAD butuh mengubah nominal atau masa kadaluarsa VA yang sudah aktif).*
  - ☑️ **Inquiry VA (VA Billing)** *(Wajib untuk mendukung fitur Reverse Inquiry NIK/Multi-tagihan wajib pajak).*
  - ☑️ **Payment (Callback)** *(Sangat krusial untuk menerima Notifikasi Pembayaran Real-time, yang akan men-trigger Auto-TTE BSrE).*
  - ☑️ **Inquiry Status** *(Digunakan sebagai "Check Status API" untuk sinkronisasi paksa dan rekonsiliasi harian jika webhook BNI gagal diterima akibat masalah jaringan).*
- **URL Callback Notif DEV:** `https://apimpad.baubaukota.go.id/api/v1/payment/bni/callback` *(Ubah dengan routing Staging M-PAD jika berbeda).*
- **URL Callback Notif PROD:** `https://api.baubaukota.go.id/api/v1/payment/bni/callback` *(Ubah dengan routing Production M-PAD jika berbeda).*

---

## Halaman 5: Layanan API (4) 👉 FOKUS UTAMA QRIS DINAMIS
Halaman ini krusial untuk pembayaran via QRIS sesuai standar SNAP.

- **VA Payment, SPC, SMART VA, SNAP Direct Debit:** KOSONGKAN.
- **QRIS (MPM):** **(WAJIB DICENTANG)**
  - ☑️ **Generate QR** *(Sistem M-PAD menolak QRIS Statis, sehingga wajib menggunakan API untuk Generate QRIS Dinamis per nomor tagihan).*
  - ☑️ **Payment Notification** *(Webhook untuk menerima notifikasi real-time saat QRIS berhasil dibayar).*
  - ☑️ **Query Payment** *(API untuk mengecek status transaksi QRIS secara manual jika dibutuhkan).*
- **URL Callback Notif DEV:** `https://api.mpad.online/api/v1/payment/bni/qris/callback`
- **URL Callback Notif PROD:** `https://apimpad.baubaukota.go.id/api/v1/payment/bni/qris/callback`

---

## Halaman 6: Layanan API (5)
Bagian ini mengatur limit transaksi (Rate Limiting).

- **QRIS (CPM), Sharing Billers, dll:** KOSONGKAN.
- **Klasifikasi Pembatasan Hit Transaksi:** Pilih ☑️ **HIGH (10 - 150 Avg Hit/Jam)**.
  - *Alasan:* Sebagai antisipasi traffic tinggi saat musim puncak pembayaran PBB-P2 jatuh tempo.

---

## Halaman 7: Data Layanan API Virtual Account
Detail spesifik mengenai tipe VA yang akan diterbitkan.

- **Tipe VA:** ☑️ **Billing** 
  - *Alasan:* Sistem M-PAD menggunakan skema "Closed Payment", di mana nominal tagihan dikunci secara spesifik oleh Bapenda dan tidak bisa diubah oleh Wajib Pajak di layar ATM/Mobile Banking.
- **Tipe VA Debit / Cardless:** Abaikan (Jika dari BNI mewajibkan diisi, pilih "Tidak Berkartu").
- **SNAP Direct Debit & Disbursement (Bagian Bawah):** KOSONGKAN.

---

## Halaman 8: Data Detail IP Layanan API 👉 KRUSIAL UNTUK WHITELISTING JARINGAN
Sesuai dengan agenda meeting terkait *Network & Security*, BNI wajib mem-whitelist IP M-PAD.

- **Nama Layanan API:** `SNAP Virtual Account & QRIS MPM`
- **Aksi:** ☑️ **TAMBAH**
- **IP Dev:** Isi dengan **IP Public Server Biznet NEO** yang digunakan untuk Staging (`sipanda.online`).
- **IP Prod:** Isi dengan **IP Public Server Biznet NEO** yang digunakan untuk Production (`baubaukota.go.id`).

> [!IMPORTANT]
> Tanpa mendaftarkan alamat IP di bagian ini, seluruh request API dari server M-PAD ke server BNI (seperti *Create VA* atau *Generate QRIS*) akan diblokir oleh Firewall BNI (terkena *Connection Timeout / Access Denied*).

---

## Halaman 9: Validasi
- **KOSONGKAN.** 
- Halaman ini murni diperuntukkan bagi coretan pengesahan dan tanda tangan internal divisi implementor BNI sendiri (Product Specialist & Department Head).
