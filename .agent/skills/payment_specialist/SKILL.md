---
name: Payment Gateway Specialist
description: Panduan tingkat lanjut untuk pengembangan, integrasi, dan trobleshooting sistem pembayaran Multi-Bank (H2H) di ekosistem mPaD.
---

# 💳 Payment Gateway Specialist

Gunakan skill ini untuk memastikan setiap integrasi bank baru mengikuti standar keamanan, keandalan, dan transparansi data mPaD.

## 🎯 1. Task (Tugas)
Tujuan utama adalah menjaga integritas alur dana dari *Inquiry* hingga *Settlement* tanpa ada selisih nominal (JIT Accuracy).

## 📄 2. Context Files (File Konteks)
- [PaymentGatewayInterface.php](file:///Users/pondokit/Herd/retribusi-api/app/Contracts/PaymentGatewayInterface.php) — Kontrak wajib untuk semua driver bank.
- [PaymentManager.php](file:///Users/pondokit/Herd/retribusi-api/app/Services/Payment/PaymentManager.php) — Pusat orkestrasi multi-bank.
- [BankSecurityCheck.php](file:///Users/pondokit/Herd/retribusi-api/app/Http/Middleware/BankSecurityCheck.php) — Gerbang keamanan HMAC & IP Whitelist.

## 🛠️ 3. Rules (Aturan Baku)
- **Always** gunakan `DB::transaction` pada callback pembayaran untuk menjamin atomisitas (update tagihan & buat record bayar).
- **Always** panggil `BillingService->getPendingPeriods()` saat Inquiry untuk mendapatkan denda terbaru (JIT).
- **Always** log setiap request/response ke tabel `payment_gateway_logs`.
- **Never** simpan *Shared Secret Key* di dalam database dalam bentuk teks biasa; gunakan `.env`.
- **Never** mengizinkan pembayaran parsial pada jalur H2H kecuali diperintahkan secara eksplisit.

## 🔐 4. Security Blueprint (HMAC-SHA256)
Setiap transaksi H2H wajib divalidasi dengan signature:
```text
Signature = Hash("sha256", BillNumber + Timestamp + Amount + SecretKey)
```

## 📋 5. Success Brief
Implementasi dianggap sukses jika:
- Transaksi berhasil tercatat di mPaD dalam < 2 detik.
- NTPD terbentuk dengan format yang benar.
- Dokumen TTE terpanggil otomatis tanpa error.
- Log audit terisi dengan payload asli dari bank.
