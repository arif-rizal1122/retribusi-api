---
name: V-Tax Parity & 9-Tax Logic
description: Panduan arsitektur untuk menjaga keselarasan sistem M-PAD dengan 9 jenis pajak V-Tax, mencakup standarisasi status, alur verifikasi modular, dan siklus penagihan/penetapan (STPD/SKPDKB).
---

# V-Tax Parity & 9-Tax Logic Specialist

Skill ini digunakan untuk memastikan seluruh pengembangan di M-PAD mengikuti standar 9 jenis pajak daerah dan alur dokumen resmi (Correspondence) yang selaras dengan sistem V-Tax Baubau.

## 1. 9 Klasifikasi Pajak Daerah (PBJT)
Pastikan setiap objek pajak, tagihan, dan verifikasi merujuk pada salah satu dari 9 klasifikasi utama sesuai UU HKPD & Perwali 58/2024:
1.  **PBJT Jasa Perhotelan** (Hotel)
2.  **PBJT Makan dan Minum** (Restoran)
3.  **PBJT Jasa Kesenian dan Hiburan** (Hiburan)
4.  **Pajak Reklame**
5.  **PBJT Tenaga Listrik**
6.  **Pajak MBLB**
7.  **PBJT Jasa Parkir**
8.  **Pajak Air Tanah**
9.  **Pajak Sarang Burung Walet**

## 2. Standarisasi Pipeline Verifikasi
Gunakan alur status yang seragam untuk semua modul (Pendaftaran, Pelaporan, Penagihan, Penetapan):
-   **proses**: Draft/Menunggu peninjauan.
-   **disetujui**: Disahkan (menerbitkan Tagihan atau Nomor SKPD).
-   **ditolak**: Dibatalkan dengan alasan yang jelas (`rejection_notes`).

> [!IMPORTANT]
> Gunakan skill `reporting_billing` untuk alur SPTPD dan `audit_enforcement` untuk alur penindakan guna memastikan integritas data keuangan.

## 3. Correspondence Lifecycle (Surat Teguran)
M-PAD mendukung siklus penagihan proaktif:
1.  **Teguran SPTPD**: Jika WP belum lapor (E-SPTPD).
2.  **Teguran I**: Setelah Jatuh Tempo (Linked to `Bill`).
3.  **Teguran II**: Teguran terakhir sebelum Surat Paksa.
4.  **Surat Paksa (SPMP)**: Upaya paksa penagihan.

## 4. Integrasi Skill Terkait
Untuk memaksimalkan efisiensi, libatkan skill berikut:
-   **Regulatory Compliance (`regulatory_logic`)**: Pastikan tarif dan denda sesuai Perwali 58/2024.
-   **Auditor & Enforcement (`audit_enforcement`)**: Untuk logika penerbitan SKPDKB/Pemeriksaan.
-   **Reporting & Billing (`reporting_billing`)**: Untuk validasi nominal omzet dan pelaporan mandiri.
-   **TTE & Digital Document (`tte_documents`)**: Untuk proses tanda tangan elektronik pada dokumen hasil verifikasi.

## 5. Granular Transaction Logging
Gunakan tabel `tax_transactions` untuk mencatat setiap detak transaksi harian (Parity dengan modul Surveillance/Monitoring Hotel). Data ini digunakan untuk rekonsiliasi dan analisis potensi pajak.
