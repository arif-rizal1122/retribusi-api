---
name: Auditor & Enforcement Specialist
description: Panduan tingkat lanjut untuk auditor dan pengawas dalam melakukan Surveillance, Uji Petik (Spot Check), dan penindakan (SKPDKB/Enforcement Notices).
---

# 🕵️ Auditor & Enforcement Specialist

Skill ini mendefinisikan standar operasional prosedur (SOP) untuk modul pengawasan dan penindakan di ekosistem MPAD.

## 📈 Alur Pengawasan (Surveillance)
1. **Anomaly Detection**: Gunakan `GET /api/pengawas/anomalies` untuk mendeteksi WP yang melaporkan data jauh di bawah potensi rata-rata wilayah.
2. **Compliance Monitoring**: Pantau statistik kepatuhan per wilayah melalui `GET /api/pengawas/compliance-stats`.

## 📍 Modul Uji Petik (Spot Check)
Uji petik adalah instrumen utama untuk memvalidasi kejujuran WP.
- **Langkah 1**: Buat Kertas Kerja Uji Petik (`POST /api/spot-checks`).
- **Langkah 2**: Jalankan kalkulasi estimasi (`GET /api/spot-checks/tax-object/{id}/estimation`). Sistem akan membandingkan data riil lapangan dengan pelaporan WP.
- **Langkah 3**: Jika ada selisih signifikan, status Uji Petik ditingkatkan menjadi `completed` dan siap untuk penindakan.

## ⚖️ Penindakan & SKPDKB
Jika WP terbukti kurang bayar:
1. **Issue SKPDKB**: Gunakan `POST /api/pengawas/penindakan/issue-skpdkb`. Ini akan menciptakan tagihan piutang baru bagi WP.
2. **Enforcement Notice**: Jika WP tetap tidak patuh, terbitkan Surat Teguran (`POST /api/pengawas/enforcements`).
3. **Approval Flow**: Seluruh penindakan harus melewati persetujuan Kabid/Atasan sebelum PDF (`generatePDF`) dapat diunduh.

## 🔍 Audit Trail
- Setiap tindakan di modul ini wajib terekam di `GET /api/pengawas/audit-logs`.
- Pastikan setiap entitas `SpotCheck` memiliki relasi yang jelas ke `TaxObject` dan `User` (Auditor).

## 🛡️ Aturan Pengembang
- **Presisi Data**: Kalkulasi estimasi di `SpotCheckController` harus menggunakan parameter waktu yang sama dengan periode pelaporan WP.
- **Validasi Dokumen**: Pastikan PDF yang dihasilkan menyertakan QR Code verifikasi TTE (jika sudah di-approve).
