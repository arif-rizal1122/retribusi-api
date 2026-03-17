# 📊 Laporan Status Implementasi Dokumen BAPENDA

Berdasarkan audit teknis terhadap sistem **M-PAD**, berikut adalah rincian status implementasi dokumen resmi yang terbagi dalam 4 tahapan pemungutan PDRD dan klasifikasi pajak.

## 🏛️ 4 Tahapan Pemungutan & Status Dokumen

Secara keseluruhan, sistem telah memiliki **logika data (JSON)** untuk 21 jenis dokumen, namun baru **4 dokumen utama** yang sudah memiliki template PDF siap cetak.

### 🟢 Tahap 1: Pendaftaran (Registration)
*Status: Logika Ready | Template PDF: Belum Lengkap*
- [x] **NPWPD**: Tergenerasi otomatis saat registrasi Wajib Pajak.
- [x] **SKT (Surat Keterangan Terdaftar)**: Logika backend `generateSKT()` sudah siap, namun template PDF belum tersedia.
- [x] **SPOPD**: Terintegrasi via `TaxpayerController`.

### 🟢 Tahap 2: Pendataan (Data Collection)
*Status: Logika Ready | Template PDF: Belum Lengkap*
- [x] **LKOK (Lembar Kerja Objek Khusus)**: Logika backend `generateLKOK()` sudah siap untuk objek komersil.
- [x] **Peta ZNT/NIR**: Tersedia via `ZoneController`.
- [ ] **SPOP/LSPOP (PBB)**: Masih berstatus *Partial* (sinkronisasi dari sistem lama).

### 🔵 Tahap 3: Penetapan (Assessment/Billing)
*Status: **Sangat Baik** | Template PDF: Ready (Utama)*
- [x] **SKRD (Surat Ketetapan Retribusi Daerah)**: **Sudah Jalan** (Template PDF Tersedia).
- [x] **SPPT (Surat Pemberitahuan Pajak Terutang)**: **Sudah Jalan** (Khusus PBB, Template PDF Tersedia).
- [x] **SKPDKBT / SKPDN**: Logika sudah siap (Kurang Bayar Tambahan & Nihil), template PDF belum ada.
- [x] **SK Penghapusan Denda**: Logika sudah siap via modul Amnesty/Waiver.

### 🔴 Tahap 4: Penagihan (Collection/Enforcement)
*Status: Terimplementasi Sebagian | Template PDF: Ready (Utama)*
- [x] **SSPD (Surat Setoran Pajak Daerah)**: **Sudah Jalan** (Template PDF Tersedia).
- [x] **SPP (Surat Perintah Pemeriksaan)**: **Sudah Jalan** (Template PDF Tersedia).
- [x] **STRD / SSRD**: Logika siap (Tagihan Retribusi), namun template PDF belum ada.
- [x] **SPMP (Surat Paksa/Penyitaan)**: Logika penindakan sudah ada di `EnforcementNoticeController`.

---

## 📂 Dokumen Berdasarkan Klasifikasi Pajak

| Klasifikasi | Dokumen Utama | Status Mekanisme |
| :--- | :--- | :--- |
| **PBB-P2** | SPPT, SSPD | **Full (PDF Ready)** - Menggunakan `PbbCalculationService`. |
| **Retribusi** | SKRD, SSRD | **Partial** - Perhitungan sudah jalan, SSRD masih JSON. |
| **Pajak (PBJT)** | SKPD, SSPD | **Partial** - Menggunakan template umum SKRD/SSPD. |

## 🛠️ Ringkasan Temuan (Sudah vs Belum)

> [!TIP]
> **Sudah Jalan (Full PDF):**
> 1. **SKRD** (Penetapan Retribusi)
> 2. **SPPT** (Penetapan PBB)
> 3. **SSPD** (Bukti Bayar Pajak)
> 4. **SPP** (Surat Tugas Pemeriksaan)

> [!WARNING]
> **Sudah Ada Logika (JSON Only - Perlu Template PDF):**
> 1. **SKT** (Pendaftaran)
> 2. **LKOK** (Pendataan/Potensi)
> 3. **SSRD / STRD** (Penagihan Retribusi)
> 4. **SKPDKBT / SKPDN** (Penetapan Audit)
> 5. **SPMP** (Surat Paksa/Penyitaan)

---
*Laporan ini dihasilkan secara otomatis berdasarkan analisis kode pada 15 Maret 2026 dan diamankan dalam dokumentasi resmi proyek.*
