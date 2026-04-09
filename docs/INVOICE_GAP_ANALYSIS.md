# M-PAD Invoicing Gap Analysis

Berdasarkan tinjauan kode pada `BillingService`, `CalculateBillPenalties`, dan `INVOICING_WORKFLOW.md`, ditemukan beberapa celah (missing flows) dan area yang memerlukan penguatan.

---

## 🚩 1. Missing Flow: Enforcement Laporan Mandiri
Saat ini, sistem JIT Billing dapat mendeteksi periode yang membutuhkan laporan (`required_reporting`). Namun:
- **Status**: ⚠️ **WIP (Work In Progress)**. Sistem kini memiliki eskalasi **Teguran 2 (V2)**. Masih memerlukan penindakan otomatis untuk denda admin murni.
- **Dampak**: Potensi denda administrasi (misal: denda tidak lapor Rp100.000) tidak tercatat di database kecuali Admin menindaklanjutinya secara manual melalui SKPDKB.
- **Solusi**: Perlu command mingguan untuk mendeteksi `TaxObject` (Self-Assessment) yang belum lapor di periode sebelumnya dan otomatis membuat tagihan Denda Administrasi.

---

## 🚩 2. Missing Flow: Pembayaran Parsial (Partial Payments)
Kode di `PaymentController` dan `Bill` model belum mendukung pembayaran cicilan secara native.
- **Celah**: User harus membayar nominal penuh (`total_amount`). Tidak ada mekanisme untuk membayar sebagian dan membiarkan sisa saldo tetap tertunggak dalam satu nomor invoice.
- **Dampak**: Memberatkan WP yang ingin mencicil kewajiban besar (seperti PBB-P2 komersial).
- **Solusi**: Implementasi tabel `bill_installments` atau penyesuaian status `partially_paid` pada model `Bill`.

---

## ✅ 3. FIXED: Sync Issue: Virtual Penalty vs Persisted Penalty (Invoicing V2)
Ada dua cara denda dihitung di sistem saat ini:
1. **Virtual (On-the-fly)**: Dihitung saat Petugas/WP melihat daftar tagihan (`BillingService`).
2. **Persisted (Harian)**: Dihitung dan disimpan ke DB setiap jam 01:00 pagi (`CalculateBillPenalties`).
- **Penyelesaian**: Logika pembayaran pada `PaymentController@store` kini memanggil `BillingService` untuk memvalidasi denda terbaru dan memperbarui record `Bill` tepat sebelum transaksi final (Real-time Sync).
- **Status**: ✅ **Tuntas**.


---

## 🚩 4. UI/UX Consistency: Status "Required Reporting"
Di sisi `INVOICING_WORKFLOW.md`, status ini disebutkan untuk WP.
- **Celah**: Di sisi Petugas Lapangan, jika statusnya `required_reporting`, apakah petugas diperbolehkan menerima pembayaran berdasarkan estimasi (Uji Petik)? Saat ini alur ini masih abu-abu di level kode.
- **Dampak**: Kebingungan petugas saat di lapangan menemukan usaha yang tidak aktif melapor tapi ingin membayar.
- **Solusi**: Tambahkan opsi "Bayar Berdasarkan Estimasi Lapangan" yang otomatis mengisi laporan omzet atas nama Petugas.

---

## 🎌 5. Rekomendasi Alur Baru (Proposed Flow)
1. **Pre-Payment Sync**: Setiap kali `Payment::store` dipanggil, jalankan fungsi `syncPenalty()` untuk memastikan angka keuangan terbaru tersimpan ke DB.
2. **Alert Monitoring**: Dashboard Admin harus memiliki widget "WP Belum Lapor" (Bukan hanya WP Belum Bayar).

---
> *Laporan ini disusun untuk membantu tim pengembang memprioritaskan fitur stabilitas finansial pada fase sprint berikutnya.*
