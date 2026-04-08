---
name: Auditor & Enforcement Specialist
description: Panduan tingkat lanjut untuk auditor dan pengawas dalam melakukan Surveillance, Uji Petik (Spot Check), Penertiban Reklame, dan Penindakan (SKPDKB/Enforcement Notices).
---

# 🕵️ Auditor & Enforcement Specialist

Skill ini mendefinisikan standar operasional prosedur (SOP) untuk modul pengawasan, penertiban, dan penindakan di ekosistem M-PAD.

## 📈 Alur Pengawasan (Surveillance)
1. **Anomaly Detection**: Gunakan `GET /api/pengawas/anomalies` untuk deteksi WP yang omzetnya jauh di bawah rata-rata.
2. **Heatmap Monitoring**: Pantau kontribusi pendapatan per wilayah atau sub-wilayah.

## 📍 Modul Uji Petik (Spot Check)
Uji petik adalah instrumen validasi kejujuran Wajib Pajak (PBJT).
- **Langkah 1**: Buat Kertas Kerja Uji Petik (`POST /api/spot-checks`).
- **Langkah 2**: Jalankan kalkulasi estimasi (`GET /api/spot-checks/tax-object/{id}/estimation`). Sistem akan membandingkan data lapangan dengan data pelaporan.
- **Langkah 3**: Jika ada selisih (>20%), tingkatkan status menjadi `completed` untuk dasar penerbitan SKPDKB.

## 🖼️ Modul Penertiban Reklame (Billboard Audit)
Verifikasi objek reklame fisik vs izin administratif.
- **Visual Evidence**: Capture foto reklame terbaru dengan koordinat GPS.
- **Status Audit**: Update `audit_status` di `TaxObject` menjadi `clean`, `anomaly`, atau `under_review`.
- **Flagging**: Tandai reklame liar yang tidak memiliki NPWPD untuk penindakan penempelan stiker.

## ⚖️ Penindakan & Penalty Waiver (Amnesty)
Langkah tegas dan relaksasi untuk Wajib Pajak.
1. **Penerbitan SKPDKB**: `POST /api/pengawas/penindakan/issue-skpdkb` untuk menetapkan kurang bayar hasil pemeriksaan.
2. **Enforcement Notice**: Terbitan Surat Teguran atau Surat Paksa (`POST /api/pengawas/enforcements`).
3. **Amnesty (Penghapusan Denda)**: Permohonan keringanan denda (`PenaltyWaiver`) diproses melalui otorisasi pimpinan untuk mendorong pelunasan pokok.

## 🔍 Audit Trail
- Setiap aktivitas di modul ini wajib terekam dalam `AuditLog`.
- Data penindakan wajib menyertakan bukti foto dengan titik koordinat (Anti-Fake GPS) untuk keabsahan hukum.
- Pastikan seluruh dokumen PDF hasil penindakan menggunakan QR Code verifikasi TTE BSrE.
