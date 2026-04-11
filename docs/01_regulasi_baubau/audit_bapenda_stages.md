# 🧾 AUDIT IMPLEMENTASI TAHAPAN BAPENDA
## Evaluasi Keselarasan M-PAD dengan Regulasi (Update April 2026)

Dokumen ini mendokumentasikan hasil audit teknis terhadap keselarasan sistem **M-PAD** dengan 4 tahapan pemungutan PDRD sesuai regulasi BAPENDA Kota Baubau.

---

## 🟢 Tahap 1: Pendaftaran (Registration)
- **Status**: TERIMPLEMENTASI
- **Fitur Utama**: 
    - E-SPOPD/SPTPD untuk 9 jenis Pajak PBJT.
    - **Self-Service**: Wajib Pajak mendaftar mandiri via aplikasi Mobile.
    - **Petugas-Assisted**: Pendaftaran objek baru langsung di lapangan.
    - Master Data: Integrasi NIK (KTP) dan NPWPD otomatis.
- **Rujukan Kode**: `TaxpayerController.php`, `PublicRegistrationController.php`.

## 🟢 Tahap 2: Pendataan (Assessment & Data Collection)
- **Status**: TERIMPLEMENTASI & OPTIMIZED
- **Fitur Utama**:
    - **Visual Audit Reklame**: Audit fisik berbasis foto dan koordinat GPS untuk objek reklame.
    - **Spot Check (Uji Petik)**: Pemantauan omzet harian (Restoran/Hotel) secara berkala (weekend/weekday).
    - **GIS Potential Mapping**: Pemetaan objek pajak di atas peta Satelit (ESRI) untuk melihat potensi yang belum terdaftar.
    - **Live Tracking**: Monitoring lokasi petugas pendata secara real-time.
- **Rujukan Kode**: `SpotCheckController.php`, `BillboardAuditController.php`, `AnalyticsController@getHeatmapData`.

## 🟢 Tahap 3: Penetapan (Official Assessment)
- **Status**: TERIMPLEMENTASI
- **Fitur Utama**:
    - **Formula Parser v2**: Perhitungan dinamis berdasarkan parameter teknis (luas, titik, masa, klasifikasi).
    - **PBB-P2 Module**: Penetapan Nilai Jual Objek Pajak (NJOP) dan penerbitan SPPT.
    - **TTE (Digital Signature)**: Pengesahan dokumen SKPD/SKRD menggunakan tanda tangan elektronik BSrE.
    - **Penalty Engine**: Perhitungan denda otomatis (1% - 2% per bulan) saat jatuh tempo.
- **Rujukan Kode**: `BillController.php`, `FormulaParserService.php`, `PbbCalculationService.php`.

## 🟢 Tahap 4: Penagihan & Penindakan (Collection & Enforcement)
- **Status**: TERIMPLEMENTASI (Full Cycle)
- **Fitur Utama**:
    - **Penalty Waiver (Amnesty)**: Modul penghapusan denda untuk program relaksasi pajak daerah.
    - **Enforcement Notice**: Penerbitan Surat Teguran I, II, dan Surat Paksa (SPMP) digital.
    - **E-Receipt (SSPD)**: Bukti bayar digital dengan pengaman QR-Code.
    - **Payment Gateway**: Integrasi pembayaran via QRIS, Virtual Account, dan loket Bank.
- **Rujukan Kode**: `PaymentController.php`, `PenaltyWaiverController.php`, `EnforcementNoticeController.php`.

---
**Kesimpulan**: Sistem M-PAD telah mencapai kematangan penuh (Full Parity) dalam mendukung siklus 4 Tahap BAPENDA, diperkuat dengan modul Audit Visual dan Penindakan Digital.
*Tanggal Audit Terakhir: 01 April 2026*
