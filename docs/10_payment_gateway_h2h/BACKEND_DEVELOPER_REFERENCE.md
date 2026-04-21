# 🛠️ Backend Developer Reference: Payment System

Dokumen ini merupakan referensi teknis bagi pengembang internal mPaD untuk memahami arsitektur dan alur kerja sistem pembayaran Host-to-Host (H2H).

---

## 🏗️ Arsitektur: Driver Pattern

Sistem pembayaran mPaD dirancang agar *extensible* menggunakan **Driver Pattern**. Seluruh logika gateway harus mengimplementasikan `PaymentGatewayInterface`.

### Komponen Utama:
1. **`PaymentGatewayInterface`**: Kontrak standar untuk seluruh gateway (Inquiry, Notify, Reversal, Reconcile).
2. **`PaymentManager`**: *Entry point* yang memilih driver yang tepat berdasarkan konfigurasi `payment.default`.
3. **`BankSultraDriver`**: Implementasi spesifik untuk integrasi Host-to-Host Bank Sultra.
4. **`QRISDriver`**: Implementasi untuk pembayaran berbasis QRIS (Dynamic QR).

---

## 🔄 Alur Kerja Internal (Internal Workflows)

### 1. JIT Billing (Just-In-Time)
Berbeda dengan sistem lama, mPaD tidak menyimpan denda secara statis setiap hari. Denda dihitung secara *real-time* saat terjadi **Inquiry** atau **Payment Notification**.

- **Service**: `BillingService::getPendingPeriods()`
- **Logic**: Menentukan denda berdasarkan selisih bulan dari tanggal jatuh tempo hingga hari ini sesuai Perda (2% per bulan, max 24 bulan).
- **Integritas**: Saat pembayaran diterima, denda yang berlaku didokumentasikan di kolom `penalty_at_payment` pada tabel `bills`.

### 2. Idempotency & NTB
Untuk mencegah duplikasi pembayaran pada sistem H2H Bank, sistem menggunakan **NTB (Nomor Transaksi Bank)** sebagai kunci unik.
- Setiap notifikasi pembayaran dicek terhadap `payments.reference_number`.
- Jika NTB sudah ada, API akan mengembalikan respon sukses (Idempotent) tanpa memproses ulang transaksi.

### 3. Automated TTE Hook (Digital Signature)
Setelah status tagihan diupdate menjadi `lunas`, sistem secara otomatis memicu pembuatan dan penandatanganan dokumen elektronik (SSPD/SSRD).
- **Service**: `OfficialDocumentService::signDocument()`
- **Trigger**: Terletak di dalam `DB::transaction()` pada masing-masing driver.
- **Dampak**: Wajib pajak segera mendapatkan kuitansi digital yang sah secara hukum sesaat setelah membayar.

---

## 📈 Auditing & Observability

Setiap komunikasi yang masuk melalui `BankH2HController` dicatat secara detail untuk kebutuhan forensik dan audit.
- **Tabel**: `payment_gateway_logs` / `payment_audits`
- **Data yang dicatat**: Request payload, Response body, HTTP code, dan IP pengirim.
- **Monitoring**: Admin dapat melihat log ini di menu **H2H Monitoring** pada Dashboard Admin.

---

## 🛠️ Menambahkan Driver Baru

Untuk menambahkan dukungan bank atau metode pembayaran baru:
1. Buat class baru di `app/Services/Payment/Drivers`.
2. Implementasikan `PaymentGatewayInterface`.
3. Daftarkan driver di `config/payment.php`.
4. Tambahkan logika *routing* atau *provider* jika diperlukan.

---

## 🔐 Keamanan & Audit (Security Audit Findings)

Berdasarkan audit keamanan terbaru:
- **Timestamp Validation**: Sangat disarankan untuk memverifikasi kedaluwarsa (expiration) dari `X-Timestamp` untuk mencegah serangan Replay.
- **Data Isolation**: Dokumentasi publik (PDF) harus selalu diverifikasi otoritasnya menggunakan data session/token sebelum diunduh.
- **Error Handling**: Hindari membocorkan stack trace atau detail query SQL dalam respon API publik.

---

> [!CAUTION]
> **Database Transaction**: Selalu gunakan `DB::transaction()` saat melakukan update status tagihan dan pembuatan record pembayaran untuk menjaga integritas data (Atomicity).
