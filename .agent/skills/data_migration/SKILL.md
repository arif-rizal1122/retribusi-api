---
name: Data Modernization & Migration Framework
description: Panduan strategis untuk migrasi data dari sistem lama (9pajak) ke ekosistem M-PAD yang modern, fleksibel, dan transparan.
---

# Data Modernization & Migration Framework

Skill ini digunakan untuk mengelola transisi data dari sistem **9pajak (PHP Legacy)** ke **M-PAD (Laravel API)** dengan prinsip **Simple, Transparent, and Scalable**.

## 1. Prinsip "Metadata-First"
Jangan membuat kolom baru di tabel `tax_objects` untuk data warisan. Gunakan kolom `metadata` (JSON).

-   **Aturan Pemetaan**: Konversikan field `CPM_*` dari tabel `PATDA_DOC` lama menjadi kunci JSON yang relevan.
-   **Contoh**: 
    -   `CPM_LUAS_BANGUNAN` → `metadata->luas_bangunan`
    -   `CPM_OMZET` → `metadata->omzet`

## 2. Kontinuitas Identitas (Legacy ID Lifecycle)
Pastikan Wajib Pajak tetap bisa menggunakan identitas lama mereka.

-   **NPWPD**: Simpan NPWPD lama di kolom `npwpd` pada tabel `taxpayers`. 
-   **NOP/NOPD**: Simpan nomor objek lama di kolom `nop` pada tabel `tax_objects`.
-   **Legacy Linking**: Gunakan kolom `metadata->legacy_id` untuk menyimpan primary key (UUID atau Autoincrement) dari sistem `9pajak`.
-   **Hierarchy Transformation**: Petakan `legacy_jenis` (dari `PATDA_JENIS_PAJAK`) menjadi `retribution_classifications` (Level 2). Pastikan penetapan `retribution_type_id` (Level 1) mengikuti pembagian Portofolio Wilayah (I/II).

## 3. Akurasi Perhitungan (Formula Parity)
Setiap penetapan (billing) yang dimigrasikan harus bisa diverifikasi ulang menggunakan `FormulaParserService`.

-   **Langkah Verifikasi**:
    1. Ambil data `metadata` (NPOP, dll) dari objek pajak.
    2. Masukkan ke dalam rumus di `RetributionClassification`.
    3. **Threshold Check**: Pastikan NPOPTKP yang digunakan sesuai (80jt untuk Umum, 300jt untuk Waris Sedarah).
    4. Pastikan hasilnya sama dengan `CPM_TOTAL_PAJAK` di sistem lama.
-   **Anomaly**: Jika ada selisih, catat di kolom `rejection_notes` atau `metadata->migration_anomaly`.

## 4. Alur Status Dokumen (State Mapping)
Konversikan status `CPM_TRAN_STATUS` lama ke status M-PAD yang baru:

| 9pajak Status | M-PAD Status | Keterangan |
| :--- | :--- | :--- |
| 1 (Draft) | `proses` | Draft pelaporan. |
| 2 (Lapor) | `proses` | Menunggu verifikasi. |
| 3 (Verifikasi) | `disetujui` | Sudah divalidasi petugas. |
| 4 (Ditolak) | `ditolak` | Butuh perbaikan. |
| 5 (Disetujui) | `disetujui` | Siap bayar/Menjadi Tagihan. |

---

## 5. Integrasi Database (Legacy Adapter)
Untuk akses data historis yang sangat besar:
- Gunakan koneksi database sekunder (misal: `mysql_legacy`) di Laravel.
- Implementasikan `LegacyMigrationService` untuk menarik data *On-Demand* (saat dipanggil saja).
- Jangan memigrasikan data transaksi lunas yang berumur >5 tahun ke database utama M-PAD guna menjaga performa.
