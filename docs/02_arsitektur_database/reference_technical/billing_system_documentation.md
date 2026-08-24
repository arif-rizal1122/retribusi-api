# Dokumentasi Sistem Billing (Penagihan)

Dokumen ini menjelaskan arsitektur, logika perhitungan, dan prosedur operasional untuk penerbitan tagihan (billing) dalam sistem retribusi.

---

## 1. Arsitektur Data (Schema)

Pusat data penagihan dikelola melalui dua entitas utama:

- **`bills` (Model: `Bill.php`)**:
  - Rekaman formal tagihan (Invoice/SKRD).
  - Memiliki `bill_number` unik (Format: `INV-YYYYMMDD-XXXXXX`).
  - Menyimpan rincian nominal: Pokok (`amount`), Bunga (`penalty_amount`), Denda Administrasi (`fixed_fine_amount`), dan Potensi Pengurangan (`waived_penalty_amount`).
  - Status: `pending`, `success`, `expired`, `cancelled`.

- **`payments` (Model: `Payment.php`)**:
  - Catatan realisasi transaksi.
  - Terhubung ke `bill_id` (jika membayar tagihan spesifik) atau langsung ke `tax_object_id` (untuk pembayaran periodik tanpa invoice).

---

## 2. Metodologi Penerbitan Billing

Penerbitan tagihan dilakukan secara **Trigger-Based** (manual/masal) atau **Virtual** (on-demand), bukan melalui otomatisasi Cron Job murni.

### A. Manual Single Billing (On-Demand)
Digunakan untuk kasus khusus atau pendaftaran baru.
- **Lokasi**: `BillController@store` (`POST /api/bills`)
- **Prosedur**: Petugas memilih objek pajak, periode, dan tanggal jatuh tempo.

### B. Bulk Generation (Penerbitan Masal)
Digunakan untuk penerbitan awal bulan/tahun bagi seluruh wajib pajak dalam satu kategori.
- **Lokasi**: `BillController@bulkStore` (`POST /api/bills/bulk`)
- **Prosedur**: Sistem mencari seluruh `TaxObject` aktif di bawah `RetributionType` tertentu dan membuat record tagihan secara paralel.

### C. Self-Assessment Flow
Untuk jenis retribusi yang memerlukan laporan mandiri (misal: Parkir/Restoran).
- **Alur**: Laporan Masuk (`MonthlyReport`) → Persetujuan Hubungan/Admin → Terbit Billing berdasarkan nominal laporan.

### D. Virtual Billing Discovery
Sistem dapat mendeteksi tunggakan tanpa invoice fisik melalui **`BillingService.php`**.
- **Logika**: Membandingkan `created_at` objek pajak dengan siklus billing vs riwayat pembayaran sukses.
- **Tujuan**: Memungkinkan Wajib Pajak membayar periode tertentu meskipun Admin belum "menerbitkan" invoice di tabel `bills`.

---

## 3. Konsep Masa Depan: **Hybrid Dynamic Billing**

Untuk meningkatkan efisiensi dan ketaatan pajak tanpa mengorbankan validitas audit, sistem bergerak menuju model **Hybrid**:

### A. Virtual Discovery (Sisi Wajib Pajak/UX)
- **Logic**: Sistem tidak lagi mengandalkan tabel `bills` yang telah terbit secara statis.
- **Mekanisme**: Saat User membuka aplikasi, `BillingService@getPendingPeriods` akan menghitung "utang berjalan" secara dinamis berdasarkan: `Tanggal Daftar` vs `Hukum Siklus` vs `Riwayat Pembayaran Sukses`.
- **Manfaat**: User selalu melihat tagihan terbaru tanpa perlu Admin melakukan "Generate" manual.

### B. Just-in-Time (JIT) Billing (Sisi Audit/Legalitas)
- **Logic**: Record di tabel `bills` (SKRD resmi) hanya dibuat pada saat aksi nyata dilakukan.
- **Mekanisme**: Begitu User menekan tombol **"BAYAR"**, sistem akan:
  1. Generate record `bills` baru untuk periode tersebut.
  2. Memberikan nomor invoice resmi.
  3. Melakukan sinkronisasi ke tabel `payments`.
- **Manfaat**: Database tetap bersih dari "tagihan sampah", namun setiap rupiah yang masuk tetap memiliki bukti audit (Nomor Invoice/SKRD) yang sah.

### C. Dashboard Ketaatan (Sisi Admin)
- **Mekanisme**: Admin melihat "Tingkat Ketaatan" (Compliance Rate) yang dihitung secara *real-time* dengan membandingkan potensi (objek pajak aktif) terhadap realisasi (pembayaran sukses), bukan sekadar melihat tagihan yang terbit.

---

## 4. Logika Perhitungan Nominal

Nominal tagihan ditentukan melalui hirarki berikut (diatur di `BillingService.php`):

1. **Fixed Rate**: Mengambil nilai tetap dari `RetributionRate`.
2. **Formula-Based**: Menggunakan `FormulaParserService` untuk menghitung rumus dinamis (contoh: `volume * tarif`). Data diambil dari `metadata` objek pajak.
3. **PBB-P2 Special**: Menggunakan `PbbCalculationService` (Rumus: `(NJOP - NJOPTKP) * Tarif`).

---

## 4. Sistem Denda Otomatis

Meskipun penerbitan tagihan bersifat manual/masal, update denda dilakukan secara otomatis setiap hari.
- **Command**: `php artisan bills:calculate-penalties`
- **File**: `app/Console/Commands/CalculateBillPenalties.php`
- **Logika**:
  - Jika `due_date` terlewati, denda bunga (persentase per bulan) mulai dihitung.
  - Bagian dari bulan (sekalipun 1 hari) dihitung sebagai 1 bulan penuh.

---

## 5. File-File Penting (Developer Reference)

| File | Peran |
| :--- | :--- |
| `app/Models/Bill.php` | Struktur data & relasi tagihan. |
| `app/Http/Controllers/BillController.php` | API Endpoints untuk manajemen billing. |
| `app/Services/BillingService.php` | Mesin perhitungan tunggakan & nominal. |
| `app/Services/FormulaParserService.php` | Parser rumus denda & tarif dinamis. |
| `app/Console/Commands/CalculateBillPenalties.php` | Skrip otomatisasi penalty harian. |
| `database/migrations/*create_bills_table.php` | Definisi skema tabel di database. |

---
*Terakhir diupdate: 3 Maret 2026*
