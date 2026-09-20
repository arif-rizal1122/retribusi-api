# 🔒 DOKUMEN MASTER KONTEKS INTEGRASI TTE BSRE & BUKTI LUNAS RESMI M-PAD
## Pemerintah Kota Baubau — Badan Pendapatan Daerah & OPD Pengampu Layanan
*Status: TERKUNCI & TERUJI (Lolos 31 Assertions Uji Otomasi Feature Test)*  
*Tanggal Penguncian: 17 September 2026*  
*Bahan Rujukan Resmi: Konsultasi Pra-Integrasi BSrE BSSN Pusat & Operasional M-PAD V5*

---

## 1. Ringkasan Eksekutif & Posisi Arsitektur

Dokumen ini mengunci seluruh konteks arsitektur, proses bisnis, payung regulasi, dan implementasi teknis integrasi **Tanda Tangan Elektronik (TTE) Balai Sertifikasi Elektronik (BSrE - BSSN)** pada ekosistem **M-PAD (Mitra Pajak, Retribusi, dan Aset Daerah) Kota Baubau**.

### A. Posisi dalam Tri-Perspective User Journey
Integrasi TTE berada pada **Fase 5 (Final Settlement & Legal Document Issuance)**:
1. **Fase 1: Pendaftaran & Pendataan**: Warga / Petugas input data via Smart Form OPD (Auto-Fill GIS & NIB).
2. **Fase 2: Penetapan & Billing**: Terbit surat ketetapan awal (`e-SPPT`, `e-SKPD`, `e-SKRD`).
3. **Fase 3: Pembayaran**: Multi-kanal perbankan (SNAP BI, VA Bank Sultra, BRIVA, QRIS Dinamis).
4. **Fase 4: Settlement Kasda**: Webhook settlement mengonfirmasi pelunasan, mengunci data (`lockForUpdate`), dan mencatat NTPD.
5. **Fase 5: Auto-TTE Hook & E-Registry**: Sistem otomatis men-generate naskah resmi PDF/A-1b dan membubuhkan Segel Elektronik Instansi / TTE Pejabat BSrE secara instan (<3 detik).

### B. Nilai Yuridis Bukti Pelunasan
Dokumen bukti lunas ber-TTE adalah **Alat Bukti Hukum Sah** (Pasal 5 & 11 UU ITE) dengan sifat **Non-Repudiation** (tidak dapat disangkal) yang menjadi prasyarat mutlak:
- **e-SSPD BPHTB**: Syarat pendaftaran hak tanah dan balik nama di Kantor Pertanahan (ATR/BPN).
- **e-SSRD PBG**: Syarat penerbitan Izin Persetujuan Bangunan Gedung di DPMPTSP dan SIMBG KemenPUPR.
- **SIPAB & BAST Alat**: Syarat fisik mobilisasi alat berat UPTD Workshop PUPR ke lapangan pemohon.
- **SIPA BMD**: Syarat serah terima kunci pemakaian gedung pertemuan / ruko daerah BPKAD.

---

## 2. Matriks 10 Sektor Layanan OPD & Dokumen Resmi Bukti Lunas Ber-TTE

| No | OPD Pengampu | Objek / Layanan | Dokumen Ketetapan Awal | Dokumen Resmi Bukti Lunas Ber-TTE | Skema TTE BSrE | Template PDF Engine |
|:---|:---|:---|:---|:---|:---|:---|
| **1** | **Bapenda** | PBB-P2, BPHTB, PBJT (Restoran, Hotel, Hiburan, Parkir, Listrik), Reklame, PAT, MBLB, Sarang Walet | `e-SPPT`, Draf `e-SSPD BPHTB`, `e-SKPD`, `e-SPTPD` | **`e-SSPD`** (Pajak Daerah) & **`e-STTS`** (PBB-P2) | **Segel Elektronik Otomatis** | `pdf.sspd`, `pdf.sspd_bphtb`, `pdf.pbb-receipt` |
| **2** | **Dinas Lingkungan Hidup (DLH)** | Retribusi Persampahan & Pengujian Lab Air/Sungai/Limbah | `e-SKRD` Sampah Bulanan, Tagihan Uji Lab (`LAB-DLH-...`) | **`e-SSRD Persampahan`** & **`Laporan Hasil Uji (LHU) Sah`** | Segel Elektronik (SSRD) & TTE Personal Kepala Lab (LHU) | `pdf.ssrd`, `DlhLabServiceController::verifyPublic` |
| **3** | **Dinas Perhubungan (Dishub)** | Parkir Tepi Jalan, Tempat Khusus Parkir, Kepelabuhanan (Jembatan Batu) | `e-SKRD Parkir`, Billing Tambat Labuh | **`e-SSRD Retribusi Dishub`** & **`Karcis Digital QRIS`** | Segel Elektronik Otomatis | `pdf.ssrd`, Scanner POS Petugas |
| **4** | **Disperindag** | Retribusi Pasar Tradisional (6 Pasar Resmi Baubau) & RPH | `e-SKRD` Sewa Kios/Los Bulanan, Kartu Retribusi Harian Pasar | **`e-SSRD Pelayanan Pasar`** & **`Kuitansi Sah Kios/RPH`** | Segel Elektronik Otomatis | `pdf.ssrd` |
| **5** | **Dinas PUPR** | Retribusi PBG (Indeks SIMBG) & Uji Lab Konstruksi | `e-SKRD PBG` (Indeks SIMBG), Pengantar Uji Lab | **`e-SSRD PBG`** & **`Sertifikat Hasil Uji Bahan`** | Segel Elektronik (SSRD) & TTE Personal Kadis (Hasil Uji) | `pdf.ssrd` |
| **6** | **BPKAD (Aset BMD)** | Pemanfaatan Gedung Daerah, Ruko/Kios Daerah, Tanah Pemda | SKP-Aset Daerah, Draf Kontrak Sewa | **`Surat Perjanjian Sewa Resmi BMD`** & **`SIPA-BMD`** | TTE Personal Kepala BPKAD *(Fallback: Kode Numerik OPD)* | `pdf.sipab`, `OfficialDocumentService::generateSIPAB` |
| **7** | **Dinas PUPR (Alat Berat)** | Sewa Alat Berat (Excavator, Wheel Loader, Tandem Roller, Dump Truck) | `SKRD` Sewa Alat Berat, Draf Kontrak Sewa | **`Surat Izin Pemakaian Alat Berat (SIPAB)`** & **`BAST Unit`** | TTE Personal Kadis PUPR *(Fallback: Kode Numerik OPD)* | `pdf.sipab` (`GET /api/public/pdf/sipab/{billId}`) |
| **8** | **Dinas Pariwisata** | Retribusi Cagar Budaya (Benteng Wolio, Nirwana), Sewa Gedung Promosi | Tiket Rombongan, Tagihan Sewa Gedung | **`e-SSRD Retribusi Pariwisata`** & **`Bukti Booking Sah`** | Segel Elektronik Otomatis | `pdf.ssrd` |
| **9** | **Dinas Perikanan** | Retribusi Pelelangan Ikan (TPI) & Tambat Labuh Nelayan | `e-SKRD` Lelang Berbasis Nilai Transaksi | **`e-SSRD Pelayanan TPI`** / **`Struk NTPD Sah`** | Segel Elektronik Otomatis | `pdf.ssrd`, `TpiTransactionController::verifyPublic` |
| **10** | **DPMPTSP (PTSP)** | Persetujuan Bangunan Gedung (PBG Final) | Validasi KSWP & `e-SSRD PUPR` | **`Sertifikat Izin PBG Definitif Digital`** | TTE Personal Kepala DPMPTSP | Alur Verifikasi Portal SIMBG / PTSP |

---

## 3. Tata Kelola Penandatanganan Aset: Solusi Transisi "Kode Numerik Resmi OPD"

### Masalah Lapangan
Sebagian Kepala OPD pengelola Barang Milik Daerah (Kadis PUPR, Kepala BPKAD, Kadis Disperindag) belum memiliki akun/sertifikat TTE aktif di BSrE karena antrean verifikasi atau permohonan susulan.

### Mekanisme Perlindungan Ganda (Dual-Layer Fallback)
1. **Kode Registrasi Verifikasi OPD (`token_code`)**:
   - Diterbitkan secara unik kriptografis: `[OPD]-AST-[YYYY-MM]-[8_HEX_RANDOM]` (contoh: `PUPR-AST-2026-09-ARSFSFUL`).
   - Tersimpan permanen pada tabel `asset_tokens` dan diikat ke ID tagihan serta nama penyewa.
2. **Segel Elektronik Institusi Cadangan**:
   - Dokumen PDF tetap disegel di latar belakang oleh sistem menggunakan **Segel Elektronik Pemkot Baubau** via API BSrE (*tamper-evident*).
3. **Klausul Naskah Sah**:
   > *"Dokumen ini disahkan oleh Kepala Dinas [Nama OPD] Kota Baubau melalui Sistem Digital Terintegrasi M-PAD dengan Kode Registrasi Verifikasi OPD: [TOKEN_CODE]. Keabsahan dokumen dilindungi oleh Segel Elektronik Resmi Pemerintah Kota Baubau dan terdaftar pada E-Registry Pemkot Baubau."*

---

## 4. Implementasi Teknis yang Telah Aktif di Codebase

### A. Migrasi & Skema Basis Data
- **File**: `database/migrations/2026_09_17_000001_enhance_tte_and_asset_token_tables.php` (Batch 74, Sukses Ran).
- **Tabel `signed_documents`**: Diperluas dengan field `signature_type` (`ELECTRONIC_SEAL`, `PERSONAL_TTE`, `NUMERIC_TOKEN_TRANSITION`), `sha256_checksum`, `opd_id`, `signer_name`, `signer_nip`, `signer_role`.
- **Tabel `tte_logs`**: Mencatat audit trail request H2H ke BSrE (`endpoint`, `request_payload`, `response_code`, `response_body`, `duration_ms`, `status`). Model: `App\Models\TteLog`.
- **Tabel `asset_tokens`**: Menyimpan token transisi resmi OPD. Model: `App\Models\AssetToken`.

### B. Core Services
- **`App\Services\TTEService`**:
  - Client REST API BSrE (`/api/sign/pdf`), timeout 3.000 ms.
  - Circuit Breaker fallback ke SHA-256 local seal jika BSrE sandbox/offline.
  - Generator token transisi aset (`generateAssetTransitionToken` & `signAssetWithTransitionToken`).
- **`App\Services\AutoTteHookService`**:
  - Event hook otomatis saat tagihan berstatus `lunas`.
  - Menerbitkan `e-SSPD`, `e-SSRD`, `e-STTS`, atau `SIPAB` secara seketika dan idempoten.
  - Terintegrasi di: `PaymentGatewayController::webhook`, `SnapBrivaService`, `BankSultraDriver`, `BankBRIDriver`, `BankMandiriDriver`, `QRISDriver`.

### C. Naskah Template PDF & Endpoint Publik
- **Template Blade**: `resources/views/pdf/sipab.blade.php`, `sspd.blade.php`, `ssrd.blade.php`, `sppt.blade.php`.
- **Koreksi Wilayah**: Seluruh naskah disahkan di **Kota Baubau**.
- **Endpoint Unduh Publik**:
  - `GET /api/public/pdf/sspd/{billId}` (SSPD Pajak Daerah)
  - `GET /api/public/pdf/ssrd/{billId}` (SSRD Retribusi Daerah)
  - `GET /api/public/pdf/sipab/{billId}` (SIPAB Sewa Alat Berat / Aset BMD)
  - `GET /api/public/pdf/sppt/{billId}` (SPPT PBB-P2)

### D. Pembaruan Antarmuka Pengguna (UI Mobile Warga)
- **`retribusi-mobile/src/components/service-detail/ServiceBillsCard.tsx`**:
  - Tagihan lunas menampilkan badge **`[ LUNAS / BER-TTE ]`** dengan ikon `ShieldCheck`.
  - Disediakan tombol **`[ Unduh PDF (TTE) ]`** (`FileDown`) untuk mengunduh bukti lunas resmi secara instan.
- **`retribusi-mobile/src/components/payment/GatewayInstructionPanel.tsx`**:
  - Tombol bukti bayar menampilkan NTPD dan teks **`Unduh Bukti Lunas Resmi Ber-TTE`**.

---

## 5. Bukti Pengujian Otomasi (Test Evidence)

Seluruh alur kerja telah diuji secara menyeluruh melalui test suite resmi:  
**`tests/Feature/TteBsreIntegrationAndEvidenceTest.php`**

```text
PASS  Tests\Feature\TteBsreIntegrationAndEvidenceTest
✓ asset transitional token generation and signing                      0.98s  
✓ tte service audit trail and cryptographic seal                       0.08s  
✓ auto tte hook service dispatch and idempotency                       0.04s  
✓ document controller can render sspd ssrd and sipab pdf               1.30s  

Tests:    4 passed (31 assertions)
Duration: 2.50s
```

**Verifikasi Frontend**:
- `npm run typecheck` (`retribusi-mobile`): **0 Error (Pass)**
- `npm run build` (`retribusi-mobile`): **Built in 12.17s (Pass)**
- `npx cap sync android`: **Tersinkronisasi ke aset native Android**.

---

## 6. Bahan Rapat Konsultasi BSrE Pusat (17 September 2026, 09:00 WITA)

- **Platform**: Zoom Meeting (ID: `794 650 2742` | Passcode: `baubau01`)
- **Tautan**: [Join Zoom Meeting BSrE & Pemkot Baubau](https://us06web.zoom.us/j/7946502742?pwd=ZPR86w6gBNIbb0SYYtE7Lt9EYJwIV4.1&omn=81058679048)

### 4 Poin Kunci untuk Ditanyakan ke Tim BSrE:
1. **Segel Elektronik Otomatis (Server-to-Server / Seal)**:
   - Konfirmasi bahwa penerbitan bukti lunas massal 24/7 di M-PAD dapat menggunakan API Segel Elektronik Instansi tanpa passphrase manual per dokumen.
2. **Kapasitas Throughput & Kuota Sandbox**:
   - Alokasi rate limit (request/detik) dan concurrent request untuk menghadapi musim jatuh tempo massal PBB-P2 (Agustus–September).
3. **Format Naskah & Visual Stamp QR Code**:
   - Penempatan visual QR Code ukuran 2.5 x 2.5 cm di pojok kanan bawah berkas PDF/A-1b yang menautkan langsung ke validasi dokumen Pemkot Baubau.
4. **Jalur Percepatan Sertifikat TTE Kepala OPD**:
   - Permohonan jalur fasilitasi percepatan verifikasi sertifikat TTE perorangan bagi para Kepala OPD pengelola aset (Kadis PUPR, BPKAD, Disperindag, Dishub, Pariwisata, DLH) melalui Diskominfo Kota Baubau.
