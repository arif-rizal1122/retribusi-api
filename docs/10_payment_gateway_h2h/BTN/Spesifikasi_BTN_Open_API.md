# DOKUMEN SPESIFIKASI BTN OPEN API (Virtual Account Series v2.04)

Dokumen ini merupakan ringkasan spesifikasi requirement pada layanan BTN OPEN API berdasarkan standar SNAP Bank Indonesia.

## 1. Otorisasi & Autentikasi

Layanan OPEN API Bank BTN menggunakan standarisasi SNAP.
*   **OAuth ID**: Digunakan untuk `X-CLIENT-KEY` dan komposisi `X-SIGNATURE` pada layanan Get Token.
*   **Apikey ID**: Digunakan untuk `X-PARTNER-ID` pada layanan selain Get Token.
*   **Apikey Secret**: Digunakan sebagai `clientSecret` membentuk `X-SIGNATURE` pada layanan selain Get Token.

### 1.1 Autentikasi Signature

**A. Retrieve Token (Asymmetric SHA256withRSA)**
```text
stringToSign = X-CLIENT-KEY + "|" + X-TIMESTAMP
X-SIGNATURE = SHA256withRSA(stringToSign, privateKey)
```

**B. Layanan API (Symmetric HMAC-SHA512)**
```text
stringToSign = HTTPMethod + ":" + EndpointUrl + ":" + AccessToken + ":" + Lowercase(HexEncode(SHA-256(minify(RequestBody)))) + ":" + X-TIMESTAMP
X-SIGNATURE = HMAC-SHA512(stringToSign, clientSecret) dalam base64
```

### 1.2 Header Standar Layanan API

*   `Authorization`: `Bearer <token>`
*   `Content-Type`: `application/json`
*   `X-TIMESTAMP`: Format ISO 8601 (Contoh: `2023-03-15T10:10:00+07:00`, Max toleransi ± 2 menit)
*   `X-SIGNATURE`: Base64 HMAC-SHA512 signature
*   `X-PARTNER-ID`: Apikey ID
*   `X-EXTERNAL-ID`: Unik per request (hanya alfanumerik, max 16 karakter)
*   `CHANNEL-ID`: Channel ID 5 digit angka (misal `00001`)
*   `Origin`: Nama Domain Anda

---

## 2. Retrieve Akses Token (B2B)
**Endpoint**: `POST /snap/v1/access-token/b2b`
*(Sesuai dokumen menggunakan base url dev: https://devapi.btn.co.id)*

**Request Body**:
```json
{
  "grantType": "client_credentials",
  "additionalInfo": {}
}
```

**Response**: (2007300 Successful) Mengembalikan `accessToken` dengan `expiresIn` 900 detik (15 menit).

---

## 3. Akun Virtual (Virtual Account Management)

### 3.1 Create VA
**Endpoint**: `POST /snap/v1/transfer-va/create-va`

**Request Body Parameters**:
*   `partnerServiceId` (String 8, M): Kode Bank + Kode Institusi (Left-Pad Spasi)
*   `customerNo` (String 14, M): Nomor Pelanggan
*   `virtualAccountNo` (String 19, M): Nomor Virtual Account
*   `virtualAccountName` (String 30, M): Nama Virtual Account
*   `trxId` (String 19, M): ID Transaksi (Unique)
*   `totalAmount` (Object, M): Berisi `value` (String 21) dan `currency` (String 3, 'IDR')
*   `virtualAccountTrxType` (String 1, M): Jenis Transaksi (Full/Partial)
*   `expiredDate` (String 25, O): Tanggal expired
*   `description`, `payment`, `paymentCode`, `currentAccountNo` dll pada `additionalInfo`.

### 3.2 Inquiry VA
**Endpoint**: `POST /snap/v1/transfer-va/inquiry-va`

**Request Body**: `partnerServiceId`, `customerNo`, `virtualAccountNo`, `trxId`.

### 3.3 Update VA
**Endpoint**: `POST /snap/v1/transfer-va/update-va`

**Request Body**: Sama seperti Create VA.

### 3.4 Delete VA
**Endpoint**: `POST /snap/v1/transfer-va/delete-va`

**Request Body**: `partnerServiceId`, `customerNo`, `virtualAccountNo`, `trxId`.

### 3.5 Report VA
**Endpoint**: `POST /snap/v1/transfer-va/report`

**Request Body Parameters**:
*   `partnerServiceId` (String 8, M)
*   `startDate` (String 10, O): yyyy-MM-dd
*   `endDate` (String 10, O): yyyy-MM-dd

---

## 4. Transfer Akun Virtual (Payment & Callback)

### 4.1 Inquiry
**Endpoint**: `POST /{Your Domain}/snap/v1/transfer-va/inquiry`

**Request Body Parameters**:
*   `partnerServiceId`, `customerNo`, `virtualAccountNo`
*   `trxDateInit`, `channelCode`, `language`, `sourceBankCode`
*   `inquiryRequestId` (String 12, M)

### 4.2 Payment
**Endpoint**: `POST /{Your Domain}/snap/v1/transfer-va/payment`

**Request Body Parameters**:
*   `partnerServiceId`, `customerNo`, `virtualAccountNo`
*   `trxId` (String 19, C)
*   `paymentRequestId` (String 12, M): Sama dengan `inquiryRequestId`
*   `paidAmount`, `totalAmount`
*   `trxDateTime`, `referenceNo`, `journalNum`, `flagAdvise`
*   `billDetails` (Object)

### 4.3 Status
**Endpoint**: `POST /snap/v1/transfer-va/status`

**Request Body**:
*   `partnerServiceId`, `customerNo`, `virtualAccountNo`
*   `inquiryRequestId` (String 12, M)
*   `paymentRequestId` (String 12, O)

---

## 5. Kode Respon (Standard SNAP)

Format Kode Respon: `[HTTP Code] + [Service Code] + [Case Code]`
Contoh Sukses: `200` + `11` + `00` (tergantung service code)

*   `200 XX 00`: Successful
*   `400 XX 00/01/02`: Bad Request (Parsing Error, Invalid Format, Missing Field)
*   `401 XX 00/01/02/03`: Unauthorized (Signature Invalid, Token Expired/Not Found)
*   `403 XX 00`: Transaction Expired
*   `404 XX 01`: Transaction Not Found
*   `409 XX 00`: Conflict (Duplicate X-EXTERNAL-ID)
*   `409 XX 01`: Duplicate partnerReferenceNo
*   `500 XX 00/01/02`: Internal/External Server Error
