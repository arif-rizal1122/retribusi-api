# 🏦 mPaD Host-to-Host (H2H) API Specification

Dokumen ini menjelaskan spesifikasi teknis untuk mengintegrasikan sistem perbankan dengan Gateway Pembayaran mPaD (Host-to-Host Multi-Bank).

---

## 🔐 Keamanan (Security)

Semua komunikasi API H2H wajib menggunakan protokol **HTTPS** dan skema autentikasi **HMAC-SHA256**.

### Header Wajib
Setiap request ke server mPaD harus menyertakan header berikut:

| Header | Deskripsi | Contoh |
|:---|:---|:---|
| `X-Signature` | Tanda tangan digital HMAC-SHA256. | `a1b2c3d4...` |
| `X-Timestamp` | Waktu saat request dikirim (ISO 8601). | `2026-04-19T10:00:00Z` |
| `Accept` | Harus diset ke `application/json`. | `application/json` |

### Rumus Signature
Signature dihitung menggunakan kunci rahasia (**Secret Key**) yang dibagikan secara aman kepada pihak Bank.

```javascript
// Struktur Data untuk Signature
data = bill_number + timestamp + amount;

// Algoritma
signature = HMAC_SHA256(data, secret_key);
```
*Catatan: Gunakan `amount = 0` untuk request Inquiry.*

---

## 📡 Endpoint Integrasi

### 1. Inquiry (Cek Tagihan)
Digunakan oleh Bank untuk mengambil detail tagihan berdasarkan nomor bayar.

**Request:**
- **URL**: `POST /api/v1/bank/inquiry`
- **Body**:
```json
{
  "bill_number": "PBJT-202601-XX"
}
```

**Response (Success):**
```json
{
  "status": "success",
  "data": {
    "bill_number": "PBJT-202601-XX",
    "taxpayer_name": "MUHDAN FYAN",
    "tax_object": "RUMAH MAKAN SEDAP",
    "period": "2026-01",
    "amount_pokok": 1000000,
    "penalty_amount": 20000,
    "total_amount": 1020000,
    "due_date": "2026-02-15 23:59:59"
  }
}
```

---

### 2. Payment Notification (Pelunasan)
Digunakan oleh Bank untuk memberitahukan bahwa pembayaran telah berhasil diproses di sisi bank.

**Request:**
- **URL**: `POST /api/v1/bank/payment`
- **Body**:
```json
{
  "bill_number": "PBJT-202601-XX",
  "amount_paid": 1020000,
  "transaction_id": "NTB-12345678",
  "channel": "MOBILE_BANKING"
}
```

**Response (Success):**
```json
{
  "status": "success",
  "data": {
    "ntpd": "NTPD-20260419-ABC123XYZ",
    "bill_number": "PBJT-202601-XX",
    "status": "LUNAS"
  }
}
```

---

### 3. Reversal (Pembatalan)
Digunakan oleh Bank untuk membatalkan transaksi yang sudah terlanjur dikirim karena kesalahan teknis di sisi bank (misal: timeout).

**Request:**
- **URL**: `POST /api/v1/bank/reversal`
- **Body**:
```json
{
  "transaction_id": "NTB-12345678",
  "reason": "Technical Timeout"
}
```

**Response (Success):**
```json
{
  "status": "success",
  "message": "Reversal berhasil diproses"
}
```

---

## ⚠️ Kode Status & Error

| Kode | Arti | Keterangan |
|:---|:---|:---|
| `200` | OK | Request berhasil diproses. |
| `401` | Unauthorized | Signature atau Timestamp tidak valid. |
| `403` | Forbidden | IP Bank tidak terdaftar dalam whitelist. |
| `404` | Not Found | Nomor tagihan atau transaksi tidak ditemukan. |
| `422` | Unprocessable Entity | Tagihan sudah lunas atau nominal tidak sesuai. |
| `500` | Server Error | Terjadi kesalahan internal pada sistem mPaD. |

---

> [!TIP]
> **Idempotency**: Endpoint Payment Notification bersifat idempotent. Mengirim ulang `transaction_id` yang sama untuk tagihan yang sama akan mengembalikan respon sukses yang sama tanpa menduplikasi data pembayaran.
