# API PBB Bapenda - 2026 Schema

This document defines the 2026 implementation of the PBB POS (Pajak Bumi dan Bangunan) API by Bapenda Kota Baubau.

## 1. Revision History

| Version | Date | Changes |
|---------|------|---------|
| 2025.1  | -    | Legacy schema using `Api_pos` endpoints. |
| 2026.1  | 2026-03-04 | Updated to `Api_service` endpoints and normalized response handling. |

## 2. Global Configuration

- **Base URL**: `http://103.182.72.241:8000/pospbb/Api_service`
- **Method**: `POST` (All endpoints)
- **Header**: `Content-Type: multipart/form-data` (Recommended) or `application/x-www-form-urlencoded`
- **Authentication**: Bearer Token (obtained via Login)

## 3. Endpoints

### Login
- **URL**: `{{BASE_URL}}/login`
- **Input**:
    - `username`: string
    - `password`: string
    - `outlet`: string (e.g., "m-PAD") [MANDATORY]
- **Success Response (200)**:
    ```json
    {
      "status": 200,
      "token": "JWT_TOKEN_HERE"
    }
    ```

### Inquiry (Cek Tagihan)
- **URL**: `{{BASE_URL}}/inquiry`
- **Header**: `Authorization: Bearer {{TOKEN}}`
- **Input**:
    - `nop`: string (18 digits)
    - `tahun`: string (4 digits)
- **Success Response (200)**:
    ```json
    {
      "status": 200,
      "data": {
        "nama_wp": "...",
        "alamat_wp": "...",
        "kelurahan": "...",
        "kota": "...",
        "tahun": "...",
        "pbb_pokok": 0,
        "denda": 0,
        "total_harus_dibayar": 0,
        "status_bayar": "BLM BAYAR"
      }
    }
    ```

### Payment (Pembayaran)
- **URL**: `{{BASE_URL}}/payment`
- **Header**: `Authorization: Bearer {{TOKEN}}`
- **Input**:
    - `nop`: string
    - `tahun`: string
    - `tagihan`: number (Full amount from Inquiry) [MANDATORY]
    - `keterangan`: string (Optional description)
- **Success Response (200)**:
    ```json
    {
      "status": 200,
      "msg": "Pembayaran sukses",
      "ntpd": "..."
    }
    ```
- **Already Paid Response (201)**:
    ```json
    {
      "status": 201,
      "msg": "SPPT sudah pernah dibayar tanggal ..."
    }
    ```

### Reversal (Pembatalan)
- **URL**: `{{BASE_URL}}/reversal`
- **Header**: `Authorization: Bearer {{TOKEN}}`
- **Input**:
    - `nop`: string
    - `tahun`: string
    - `keterangan`: string
- **Success Response (200)**:
    ```json
    {
      "status": 200,
      "msg": "Pembayaran Sukses dibatalkan"
    }
    ```

## 4. Development Notes & Obstacles (2025 Lessons)

> [!WARNING]
> **Endpoint Shift**: The path changed from `Api_pos` to `Api_service`. All legacy calls must be updated.
> 
> **Status Check**: Always prioritize checking the `status` field in the JSON body, as the API might return HTTP 200 even for failed business logic (e.g., Token Expired or Data Not Found).

> [!TIP]
> **Auto-Retry Mechanism**: Implementation should include a middleware or service-level retry that clears token cache and re-authenticates on 401/Unauthorized status.
