---
name: TTE & Digital Document Specialist
description: Skill khusus untuk mengelola Tanda Tangan Elektronik (TTE), E-Registry dokumen, dan standarisasi 21 jenis dokumen resmi Bapenda Kota Baubau.
---

# 🖋️ TTE & Digital Document Specialist

Skill ini menjamin keabsahan hukum dan integritas seluruh dokumen digital yang diterbitkan oleh sistem MPAD.

## 🏛️ E-Registry & Verifikasi Umum
Setiap dokumen resmi (SKRD, SSPD, SPPT, dll) memiliki nomor unik yang dapat diverifikasi publik:
- **Verifier Route**: `/api/verify/bill/{number}` (Publik).
- **Frontend Page**: `ERegistry.tsx` di Admin dan `verify/:number` di Petugas.
- **Standard**: Gunakan QR Code yang mengarah ke link verifikasi unik untuk setiap lembar dokumen.

## 🔑 Alur Tanda Tangan Elektronik (TTE)
- **Modul**: `EregistryController` & `BillController::signTTE`.
- **Proses**: Pembubuhan sertifikat digital pada dokumen PDF. 
- **Endpoint**: `POST /api/tte/sign`.
- **Logika**: Dokumen hanya bisa di-TTE jika status verifikasi sudah `approved` oleh pejabat berwenang (Kabid/Kadis).

## 📄 Inventori 21 Dokumen BAPENDA
Backend `DocumentController` melayani eksportasi dokumen-dokumen berikut:
1.  **Pendaftaran**: SKT.
2.  **Pendataan**: LKOK.
3.  **Penetapan**: SKRD, SPPT, SKPDKBT, SKPDN.
4.  **Penagihan**: SSPD, SSRD, STRD, SPP, SPMP.
5.  *(Dan jenis dokumen turunannya sesuai Perwali)*.

## 🛡️ Prinsip Keamanan Dokumen
1. **Anti-Tamper**: Dokumen yang sudah terekam di `signed_documents` tidak boleh di-generate ulang dengan data yang berbeda tanpa membatalkan signature lama.
2. **Metadata Transparency**: Simpan hash dokumen pada saat ditandatangani untuk keperluan audit di masa depan.
3. **Availability**: Dokumen harus selalu tersedia untuk diunduh oleh Wajib Pajak melalui aplikasi Mobile setelah diterbitkan secara sah.

## 🛠️ Debugging TTE
Jika QR Code tidak muncul:
- Periksa library generation QR.
- Pastikan `APP_URL` di `.env` sudah sesuai dengan domain publik (atau staging).
