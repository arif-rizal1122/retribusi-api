---
name: Analytics & Financial Reporting
description: Panduan pengembangan dan analisis data untuk Dashboard, Heatmap, dan Pelaporan Keuangan (SIPD/BPK) di ekosistem M-PAD.
---

# 📊 Analytics & Financial Reporting

Skill ini digunakan untuk mengelola visualisasi data dan standarisasi laporan keuangan daerah agar selaras dengan kebutuhan pimpinan dan auditor (BPK/KPK).

## 🚀 Dashboard Real-time (Command Center)
- **Stats**: Total Target, Realisasi, Tunggakan, dan Rasio Pertumbuhan.
- **Trend Analysis**: Grafik garis (`revenue-trend`) per bulan/tahun.
- **Region Performance**: Peringkat realisasi per Kecamatan/Kelurahan.

## 🗺️ GIS & Heatmap Potential
- **Heatmap Data**: `GET /api/analytics/heatmap`.
- **Logic**: Visualisasi kepadatan potensi pajak berdasarkan koordinat Objek Pajak.
- **Layering**: Menggunakan peta Satelit ESRI untuk identifikasi bangunan/objek reklamasi yang belum terdaftar.

## 📉 Pelaporan Keuangan (Financial Standard)
M-PAD menyediakan ekspor data untuk integrasi sistem eksternal:
1. **Laporan BPK**: `GET /api/reports/bpk` (Format audit bulanan).
2. **Laporan SIPD**: `GET /api/reports/sipd` (Sinkronisasi dengan Sistem Informasi Pemerintahan Daerah).
3. **Laporan Pajak 9 Jenis**: `GET /api/reports/summary` (Ringkasan PBJT).

## 👮 Performance Tracker
- **Petugas Stats**: `GET /api/reports/petugas-performance`.
- **Audit Log**: `GET /api/pengawas/audit-logs` (Mencatat setiap penetapan, penghapusan denda, dan verifikasi).

## 🛠️ Aturan Pengembangan Analytics
- **Aggregation**: Gunakan raw SQL atau Eloquent aggregation untuk performa tinggi pada dataset besar.
- **Exporting**: Pastikan setiap laporan Excel/CSV memiliki *header* yang sesuai dengan standar akuntansi daerah.
- **Caching**: Gunakan Laravel Cache pada endpoint statistik yang jarang diperbarui secara instan untuk mempercepat respons dashboard pimpinan.
