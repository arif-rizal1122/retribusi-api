---
name: TTE & Digital Document Specialist
description: Skill khusus untuk mengelola Tanda Tangan Elektronik (TTE), E-Registry dokumen, dan standarisasi 21 jenis dokumen resmi Bapenda Kota Baubau.
---

# 🖋️ TTE & Digital Document Specialist

Skill ini menjamin keabsahan hukum dan integritas seluruh dokumen digital yang diterbitkan oleh sistem M-PAD melalui integrasi Sertifikat Elektronik BSrE.

## 🏛️ E-Registry & Verifikasi Umum
Setiap dokumen resmi (SKPD, SKRD, SSPD, SPPT, dll) memiliki QR-Code unik untuk verifikasi publik:
- **Verifier URL**: `https://verify.baubaukota.go.id/doc/{number}`.
- **Route Backend**: `/api/tte/verify/{number}` (Publik).
- **Frontend Page**: `ERegistry.tsx` di Admin dan `verify/:number` di Petugas/Mobile.

## 🔑 Alur Penandatanganan Elektronik (TTE)
- **Modul**: `SignedDocument` model & `BillController::signTTE`.
- **Integrasi**: BSrE API (Badan Siber dan Sandi Negara). 
- **Persyaratan**: Dokumen hanya bisa di-TTE jika status verifikasi sudah `approved` oleh pejabat berwenang (Kadis/Kabid).
- **Logika**: Pembubuhan tanda tangan akan men-generate hash unik yang disimpan di tabel `signed_documents`.

## 📄 Inventori Dokumen BAPENDA
Backend `DocumentController` melayani pembentukan PDF untuk jenis dokumen berikut:
1.  **Pendaftaran**: SKT (Surat Keterangan Terdaftar).
2.  **Pendataan**: LKOK (Laporan Kunjungan Objek Pajak).
3.  **Penetapan**: SKPD, SKRD, SPPT, SKPDKBT, SKPDN.
4.  **Penagihan**: SSPD (Surat Setoran), SSRD, STRD (Teguran), SPP, SPMP (Paksa).

## 🛡️ Prinsip Keamanan Dokumen
1. **Immutable Result**: Dokumen yang sudah bertanda tangan (`status: signed`) adalah *final*. Perubahan data objek wajib membatalkan (`revoked`) signature lama.
2. **Metadata Transparency**: Simpan `signature_hash` dan `signed_at` pada saat ditandatangani untuk keperluan audit BPK.
3. **Availability**: Dokumen harus segera tersedia untuk diunduh oleh Wajib Pajak melalui aplikasi Mobile seketika setelah TTE berhasil.

## 🛠️ Debugging TTE
- **QR Code Kosong**: Periksa `APP_URL` di `.env` (harus HTTPS untuk produksi).
- **Signature Failed**: Pastikan kredensial sertifikat pejabat masih aktif di sistem BSrE.
