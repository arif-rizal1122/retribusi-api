# Hasil Pengujian E2E WhatsApp Gateway (M-PAD Notification Subsystem)

**Tanggal Pengujian:** 30 Agustus 2026  
**Target Komponen:** OpenWA / Baileys Engine VPS (`127.0.0.1:2785` / port internal container), `WaGatewayService.php`, `GenerateAutomatedTeguran.php`, `SendDueDateNotifications.php`.  
**Leantime Ticket Reference:** #2055

---

## 1. Arsitektur Gateway

Sistem notifikasi WhatsApp M-PAD beroperasi secara non-blocking melalui REST Bridge:

```
[ Laravel Backend / Cron Jobs ]
        ↓ HTTP POST /send-message (Payload: { number, message })
[ WA Gateway Engine (VPS: 127.0.0.1:2785) ]
        ↓ WebSocket Multi-Device Protocol
[ WhatsApp Web Infrastructure / Meta Servers ]
        ↓ Push Notification
[ Smartphone Wajib Pajak / Petugas ]
```

---

## 2. Parameter Endpoint & Kontrak Payload

### A. Cek Status Koneksi
- **Endpoint**: `GET /api/admin/wa-gateway/status`
- **Headers**: `Authorization: Bearer <ADMIN_TOKEN>`
- **Response Format**:
```json
{
  "status": "ONLINE",
  "connected": true,
  "user": "6281234567890@c.us",
  "qr": null
}
```

### B. Pengujian Kirim Pesan (Test Send)
- **Endpoint**: `POST /api/admin/wa-gateway/send-test`
- **Headers**: `Authorization: Bearer <ADMIN_TOKEN>`
- **Request Body**:
```json
{
  "phone": "081234567890",
  "message": "Halo! Ini adalah pesan uji coba sistem notifikasi M-PAD Kota Baubau.",
  "type": "custom"
}
```
- **Response Format**:
```json
{
  "success": true,
  "message": "Pesan WhatsApp berhasil dikirim",
  "detail": "Halo! Ini adalah pesan uji coba sistem notifikasi M-PAD Kota Baubau."
}
```

### C. Otomasi Pengiriman Notifikasi Tagihan (Surat Teguran & Jatuh Tempo)
- Command: `php artisan notifications:send-due-date` (H-3 sebelum jatuh tempo).
- Command: `php artisan teguran:generate-automated` (Surat Teguran I, II, III).
- Template Pesan:
```
*PEMERINTAH KOTA BAUBAU - BAPENDA*
Pemberitahuan Tagihan Daerah (M-PAD)

Yth. Bapak/Ibu {NAMA_WP},
Tagihan retribusi/pajak Anda dengan nomor *{BILL_NUMBER}* sebesar *Rp {TOTAL_AMOUNT}* akan jatuh tempo pada *{DUE_DATE}*.

Silakan melakukan pembayaran melalui aplikasi M-PAD Mobile atau Virtual Account / QRIS resmi Bapenda Kota Baubau.
```

---

## 3. Hasil Verifikasi & Quality Check

| Skenario Uji | Target | Hasil | Status |
| :--- | :--- | :--- | :---: |
| **Normalisasi Nomor Telepon** | Format `08...` → `628...` | Sukses di-format di `WaGatewayService.php` | ✅ PASS |
| **Pesan OTP Register/Login** | Kode 6 digit OTP acak | Template OTP 5 menit terkirim | ✅ PASS |
| **Pesan Peringatan Tagihan** | Variable binding (Nama, NOP, Total) | Data tersinkron dengan tabel `bills` | ✅ PASS |
| **Error Handling Offline** | Timeout 10 detik fallback ke Log | Log tersimpan tanpa merusak request thread | ✅ PASS |
