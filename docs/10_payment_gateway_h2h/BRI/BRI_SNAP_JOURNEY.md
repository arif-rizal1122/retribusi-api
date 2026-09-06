# BRI SNAP — Riwayat Perjalanan Integrasi (04-05 Agu 2026)

## Ringkasan
Akses-token BRI SNAP berhasil tembus **2007300 Successful** pada 05 Agu 2026 10:20 WITA.
Akar masalah utama: BRI menggunakan **RSA-SHA256** signature (bukan HMAC-SHA512) dengan
stringToSign **`X-CLIENT-KEY|X-TIMESTAMP`** (format BTN-style).

## Kronologi
| Waktu | Error | Akar Masalah |
|-------|-------|--------------|
| 04 Agu 08:15 | 4017300 Client Key | BRI pakai placeholder "DASHBOARD BRI" (disengaja) |
| 04 Agu | 4017300 Signature | Client ID/Secret asli di-update, format masih beda |
| 04 Agu | Fix flex path variants | Commit a9cdfc1 — 5 variasi path/body → 2007300 |
| 05 Agu 07:05-09:35 | 4017300 Signature | BRI hit tiap 15 menit, tetap mismatch |
| 05 Agu 09:50 | 5007300 General Error | BUG diagnostic (closure $request tanpa use) |
| 05 Agu 09:57 | Fix bug 500 | Sekarang error jujur: 4017300 Signature |
| 05 Agu 10:05 | 4017300 Signature | BRI ganti ke RSA-2048 (344 char) |
| 05 Agu 10:15 | MATCH ditemukan | RSA-SHA256 PKCS1v15: clientKey\|timestamp |
| **05 Agu 10:20** | **2007300 Successful** | Fix fallback RSA deployed (83ae04a) |

## Root Cause
Signature BRI untuk access-token = **RSA-SHA256 PKCS1v15**:
```
stringToSign = X-CLIENT-KEY|X-TIMESTAMP
Contoh: lAeTvF1KvLlHwYS9IR94pz7irRrovTDH|2026-08-05T09:05:36+07:00
```
Deteksi cepat: signature 344 char base64 = RSA-2048; 88 char = HMAC-SHA512.

## Fix
1. `SnapSignatureService::verify()` — kandidat + format `clientKey|timestamp`
2. `verifyString()` — HMAC dulu, fallback `verifyRsa()` (public key BRI)
3. `SnapRequestCapture` (baru) — simpan raw request BRI ke storage/logs/bri_capture/
4. `analyze_bri_capture.py` — brute-force semua format signature

## Status
- [x] Access-token B2B → 2007300 (05 Agu 10:20)
- [ ] Inquiry VA → menunggu test BRI (VA siap: PR #2)
- [ ] Payment VA → menunggu inquiry sukses
- [ ] Whitelist IP outbound BRI
