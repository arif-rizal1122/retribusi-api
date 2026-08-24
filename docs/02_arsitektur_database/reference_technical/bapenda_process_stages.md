# 📑 TAHAPAN PEMUNGUTAN PDRD (BAPENDA)

Dokumen ini memetakan proses bisnis pemungutan Pajak Daerah dan Retribusi Daerah (PDRD) ke dalam modul-modul sistem **M-PAD**.

---

## 1. Tahap Pendaftaran
*Pendaftaran Wajib Pajak dan Objek Pajak baru ke dalam sistem.*

- **Dokumen Terkait**:
    - **SPOPD** (Surat Pendaftaran Objek Pajak Daerah)
    - **NPWPD** (Nomor Pokok Wajib Pajak Daerah)
    - **SKT** (Surat Keterangan Terdaftar)
- **Implementasi M-PAD**:
    - Modul pendaftaran di **M-PAD Mobile** (WP Mandiri) dan **Petugas App** (Pendaftaran oleh petugas).
    - Verifikasi pendaftaran di **Admin Panel**.
    - Auto-generate NPWPD setelah verifikasi disetujui.

## 2. Tahap Pendataan
*Identifikasi potensi dan pemutakhiran data objek pajak di lapangan.*

- **Dokumen Terkait**:
    - **LKOK** (Lembar Kerja Objek Khusus)
    - **Peta ZNT** (Zona Nilai Tanah) & NIR (Nilai Indikasi Rata-rata)
    - **LHP** (Laporan Hasil Penelitian/Pemeriksaan)
- **Implementasi M-PAD**:
    - **GPS Tagging** & Foto lokasi objek pajak via Petugas App.
    - Overlay **Zona (ZNT)** di Dashboard Admin untuk validasi potensi.
    - Modul Survei Potensi oleh Petugas Lapangan.

## 3. Tahap Penetapan
*Perhitungan nilai pajak/retribusi yang harus dibayar.*

- **Dokumen Terkait**:
    - **SPTPD** (Surat Pemberitahuan Pajak Daerah) - *Self Assessment*
    - **SKPD** (Surat Ketetapan Pajak Daerah) - *Official Assessment*
    - **SKRD** (Surat Ketetapan Retribusi Daerah)
    - **SPPT** (Surat Pemberitahuan Pajak Terutang) - PBB-P2
- **Implementasi M-PAD**:
    - **Billing Engine**: Perhitungan otomatis berdasarkan rumus dinamis (Perda No. 1/2024).
    - Generator PDF untuk dokumen ketetapan (SPTPD/SKPD/SKRD) dengan QR Code.

## 4. Tahap Penagihan
*Proses monitoring pembayaran hingga tindakan penagihan sanksi.*

- **Dokumen Terkait**:
    - **SSPD** (Surat Setoran Pajak Daerah) / **SSRD**
    - **STPD** (Surat Tagihan Pajak Daerah) - Untuk sanksi/denda.
    - **Surat Teguran** 1, 2, & 3.
- **Implementasi M-PAD**:
    - **Real-time Monitoring**: Dashboard realisasi pendapatan.
    - **Penalty Engine**: Auto-calculate denda 2% per bulan.
    - **Notifikasi OTOMATIS**: Pengiriman draf Surat Teguran via WhatsApp (Fonnte).

---
*Referensi: Perwali Tata Cara Pemungutan PDRD Kota Baubau.*
