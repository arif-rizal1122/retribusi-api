# Kredensial & Informasi Callback DEV - Bapenda Kota Baubau (M-PAD)

Berikut adalah informasi yang dibutuhkan oleh Bank BTN untuk melakukan pengujian Callback ke sistem m-PAD Bapenda Kota Baubau pada environment DEV/UAT.

## 1. URL Endpoint Callback (M-PAD)
Sistem M-PAD telah menyediakan endpoint sesuai dengan standar SNAP BI:
*   **Access Token B2B:** `https://apimpad.baubaukota.go.id/api/snap/v1.1/access-token/b2b`
*   **Inquiry Callback (BRIVA):** `https://apimpad.baubaukota.go.id/api/snap/v1.0/transfer-va/inquiry`
*   **Payment Callback (BRIVA):** `https://apimpad.baubaukota.go.id/api/snap/v1.0/transfer-va/payment`
*   **QRIS Notify:** `https://apimpad.baubaukota.go.id/api/snap/v1.1/qr/qr-mpm-notify`

## 2. Kredensial Callback (DEV)
Untuk otentikasi saat memanggil endpoint callback di atas, Bank BTN dapat menggunakan kredensial berikut untuk melakukan generate signature atau access token:
*   **Client ID / OAuth ID:** `MPADBTN2026DEV`
*   **Client Secret:** `MPADBTNSECRET2026DEV`

## 3. Public Key Bapenda (DEV)
File Public Key (Asymmetric RSA) telah kami sertakan pada folder ini dengan nama file `bapenda_public_key.pem`. Public Key ini digunakan untuk memverifikasi _signature_ dari layanan akses token M-PAD.

---
*Tim Pengembang IT (M-PAD)*
*Bapenda Kota Baubau*
