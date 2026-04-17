# Master Technical Design: Bank H2H Payment Ecosystem

Dokumen ini merinci arsitektur hulu-ke-hilir untuk sistem pembayaran mandiri (H2H) M-PAD, mencakup integrasi database, API, keamanan, dan alur kerja lintas aplikasi.

---

## 🗄️ 1. Skema Database (Refactor & New)

Untuk mendukung standar audit perbankan, struktur data akan diperluas agar setiap transaksi dapat dilacak hingga ke level *raw payload*.

### 1.1 Tabel `bills` (Update)
- `penalty_amount`: (Decimal) Menyimpan nominal denda final saat pelunasan.
- `bank_code`: (String) Kode bank mitra (misal: `SULTRA`, `MANDIRI`).
- `expiry_time`: (Timestamp) Batas waktu pembayaran untuk VA/QRIS dinamis.

### 1.2 Tabel `payments` (Update)
- `reference_number`: (String) Nomor Transaksi Bank (NTB).
- `receipt_number`: (String) Nomor Transaksi Penerimaan Daerah (NTPD).
- `channel`: (String) Saluran bayar (Teller, ATM, Mobile Banking).
- `raw_callback_data`: (JSON) Payload asli dari bank untuk kebutuhan audit.

### 1.3 Tabel `payment_gateway_logs` (NEW)
Tabel khusus untuk mencatat setiap aktivitas komunikasi antara server Bank dan M-PAD.
- `bill_number`: Indeks pencarian.
- `endpoint`: URL yang diakses.
- `method`: GET/POST.
- `payload_in`: Data masuk.
- `payload_out`: Data keluar.
- `ip_address`: Verifikasi keamanan.

---

## 🌐 2. Detail Endpoint API (H2H)

### 2.1 Inquiry (Cek Tagihan)
*Bank memanggil API M-PAD untuk mendapatkan detail tagihan.*
- **Method**: `POST` (Direkomendasikan untuk keamanan payload) atau `GET`.
- **URL**: `/api/v1/bank/inquiry`
- **Data Dibutuhkan**: `bill_number`, `signature`, `timestamp`.
- **Logika Internal**:
  1. Validasi IP & Signature.
  2. Panggil `BillingService->getPendingPeriods()`.
  3. Hitung denda real-time per detik ini.
  4. Kembalikan JSON detail wajib pajak dan nominal presisi.

### 2.2 Payment (Notifikasi Bayar)
*Bank memberitahu M-PAD bahwa dana sudah masuk.*
- **Method**: `POST`
- **URL**: `/api/v1/bank/payment`
- **Data Dibutuhkan**: `transaction_id` (NTB), `bill_number`, `amount_paid`, `signature`.
- **Logika Internal**:
  1. Validasi nominal (harus pas).
  2. Update status `bills` menjadi `lunas`.
  3. Buat record di `payments`.
  4. **Trigger Hook**: Generate TTE Bukti Bayar.

### 2.3 Reversal (Pembatalan)
*Bank membatalkan transaksi akibat anomali sistem mereka.*
- **Method**: `POST`
- **URL**: `/api/v1/bank/reversal`
- **Logika**: Mengembalikan status bill ke `pending` dan membatalkan pencatatan bayar.

---

## 🔐 3. Protokol Keamanan

1.  **IP Whitelisting**: Hanya menerima request dari IP Server Bank.
2.  **HMAC-SHA256**: Menggunakan *Shared Secret Key*. String signature dibentuk dari `BillNumber + Timestamp + Amount`.
3.  **Mutual TLS (mTLS)**: Jika Bank mendukung, integrasi sertifikat klien (.crt) pada level Nginx/Web Server.

---

## 📱 4. Integrasi Lintas Platform (Frontend)

*   **Mobile ( Citizen)**: Saat membuka menu tagihan, sistem memanggil `PaymentManager` untuk mendapatkan detail VA/QRIS sesuai bank mitra. Mobile app memonitor status lunas secara pasif (WebSocket).
*   **Petugas (Field)**: Jika status di database sudah `lunas` via H2H, dashboard petugas otomatis berubah warna (hijau) tanpa perlu input manual.
*   **Admin (Web)**: Menyediakan fitur **Rekonsiliasi Manual** untuk mencocokkan laporan bank (.csv) dengan data database jika terjadi perselisihan data.

---

## 🚀 5. Runutan Realisasi (Prompting Roadmap)

Berikut adalah langkah-langkah implementasi (prompts) yang harus dijalankan secara berurutan:

### Tahap 1: Persiapan Kontrak & Data
> "Buat migrasi database untuk tabel `payment_gateway_logs` dan tambahkan kolom audit (`ntb`, `ntpd`, `penalty`) pada tabel `bills` dan `payments`."

### Tahap 2: Logika JIT & Driver
> "Buat `PaymentGatewayInterface` dan implementasikan logic `Inquiry` yang terhubung dengan `BillingService` untuk menghitung denda secara dinamis sesuai filosofi JIT M-PAD."

### Tahap 3: Security Layer
> "Implementasikan Middleware `BankSecurityCheck` yang memvalidasi header HMAC Signature dan alamat IP untuk melindungi endpoint H2H."

### Tahap 4: Callback & Webhook
> "Bangun `BankCallbackController` yang menangani notifikasi `Payment` dan `Reversal`, lengkap dengan logging otomatis ke tabel `payment_gateway_logs`."

### Tahap 5: Integrasi Dokumen Sah
> "Buat Hook setelah pembayaran sukses untuk memicu `OfficialDocumentService` guna menghasilkan receipt digital (TTE) bagi Wajib Pajak."
