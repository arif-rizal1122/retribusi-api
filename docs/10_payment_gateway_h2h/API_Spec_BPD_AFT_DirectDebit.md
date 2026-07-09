# Spesifikasi API Direct Debit (Auto Fund Transfer)
## Terintegrasi dengan Standar SNAP BI

### 1. Tujuan Dokumen
Spesifikasi ini disusun untuk tim IT Bank Pembangunan Daerah (Bank Sultra) agar mengekspos dua (2) endpoint API utama yang dibutuhkan oleh M-PAD (Sultra Connect) guna mengeksekusi mekanisme *Auto-Debit* (AFT) Pajak Daerah secara *Host-to-Host* (H2H).

### 2. Keamanan & Otorisasi
- Autentikasi menggunakan OAuth 2.0 (B2B Token) standar SNAP BI.
- Menggunakan *Digital Signature* (SHA-256 with RSA) dan Header `X-TIMESTAMP`, `X-PARTNER-ID`, `X-EXTERNAL-ID`.

---

### 3. API 1: Webhook Notifikasi Saldo Masuk
*Bank menembak M-PAD ketika ada transaksi konsumen masuk ke rekening operasional Wajib Pajak (Merchant).*

- **Method:** `POST`
- **URL (M-PAD):** `https://api.baubaukota.go.id/snap/v1.0/webhook/incoming-transfer`
- **Payload Request dari Bank:**
```json
{
  "partnerReferenceNo": "BNK-20260709-12345",
  "accountNo": "1234567890", // Rekening Merchant
  "transactionAmount": {
    "value": "110000.00",
    "currency": "IDR"
  },
  "transactionDate": "2026-07-09T12:00:00+08:00",
  "description": "Pembayaran QRIS Konsumen"
}
```

---

### 4. API 2: Direct Debit (AFT)
*M-PAD menembak Bank BPD untuk memotong otomatis komponen Pajak (10%) dari rekening Merchant ke Rekening Kas Daerah (Escrow Pajak).*

- **Method:** `POST`
- **URL (Bank BPD):** `https://api.banksultra.co.id/snap/v1.0/direct-debit/payment`
- **Payload Request dari M-PAD:**
```json
{
  "partnerReferenceNo": "MPAD-AFT-20260709-001",
  "merchantId": "MERCHANT-001",
  "sourceAccountNo": "1234567890", // Rekening Merchant
  "destinationAccountNo": "0987654321", // Rekening Kas Daerah
  "amount": {
    "value": "10000.00", // Hanya memotong pajak (10% dari 100rb)
    "currency": "IDR"
  },
  "feeAmount": {
    "value": "0.00", // Bebas Biaya (PKS Pemda)
    "currency": "IDR"
  },
  "remark": "Auto-Debit Pajak PBJT"
}
```
- **Response Sukses (Bank BPD):**
```json
{
  "responseCode": "2000000",
  "responseMessage": "Successful",
  "partnerReferenceNo": "MPAD-AFT-20260709-001",
  "referenceNo": "BPD-REF-998877",
  "status": "SUCCESS"
}
```
