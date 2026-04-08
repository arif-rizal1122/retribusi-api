---
name: V-Tax Parity & 9-Tax Logic
description: Arsitektur sinkronisasi sistem M-PAD dengan 9 jenis pajak PBJT dan PBB-P2, mencakup standarisasi status, alur verifikasi modular, dan siklus penagihan terintegrasi (End-to-End).
---

# V-Tax Parity & 9-Tax Logic Specialist

Skill ini digunakan untuk memastikan seluruh pengembangan di M-PAD mengikuti standar regulasi daerah dan alur dokumen resmi (Correspondence) yang selaras dengan sistem V-Tax dan SISMIOP Baubau.

## 1. Klasifikasi Pendapatan Daerah
Setiap objek pajak, tagihan, dan verifikasi wajib merujuk pada klasifikasi utama sesuai UU HKPD & Perwali 58/2024:

### A. 9 Jenis Pajak PBJT (Pajak Barang & Jasa Tertentu)
1. **Hotel**, 2. **Restoran**, 3. **Hiburan**, 4. **Reklame**, 5. **TL**, 6. **MBLB**, 7. **Parkir**, 8. **Air Tanah**, 9. **Sarang Burung Walet**.

### B. PBB-P2 (Pajak Bumi dan Bangunan)
Integrasi dengan data SISMIOP untuk:
- **Inkuiri NOP**: Pengecekan saldo tunggakan per Nomor Objek Pajak.
- **E-SPPT**: Distribusi dokumen SPPT digital (PDF).

## 2. Standarisasi Pipeline & Status
Gunakan alur status yang seragam di seluruh repository (API, Admin, Petugas, Mobile):
- **draft**: Input awal (Petugas/WP).
- **proses**: Menunggu verifikasi Bapenda.
- **disetujui**: Sah, diterbitkan tagihan (SKPD/SKRD/SPPT) & Nomor Dokumen.
- **ditolak**: Dikembalikan untuk perbaikan (Wajib menyertakan `rejection_notes`).

## 3. Correspondence Lifecycle (Penagihan Terpadu)
M-PAD mendukung siklus penagihan proaktif (Correspondence):
1. **Teguran SPTPD**: Jika belum lapor bulanan.
2. **Surat Teguran I & II**: Setelah lewat jatuh tempo tagihan.
3. **Surat Paksa (SPMP)**: Upaya hukum penagihan paksa.
4. **Tanda Tangan Elektronik (TTE)**: Setiap dokumen resmi wajib melalui `signTTE` via BSrE.

## 4. Integrasi Skill Terkait
- **Auditor & Enforcement (`audit_enforcement`)**: Untuk logika Uji Petik & Penindakan.
- **TTE & Digital Document (`tte_documents`)**: Untuk validasi keaslian dokumen via E-Registry.
- **PBB Specialist (`pbb_specialist`)**: Untuk logika spesifik NJOP dan penetapan masa PBB.

## 5. Granular Transaction Logging
Gunakan tabel `tax_transactions` sebagai *buffer* data transaksi (Parity dengan monitoring Tapbox). Data ini digunakan untuk rekonsiliasi harian dan deteksi anomali pendapatan.
