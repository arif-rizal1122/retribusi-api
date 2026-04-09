---
name: Invoicing & Billing Specialist
description: Panduan mendalam untuk menganalisis, melakukan trobleshooting, dan memverifikasi logika penerbitan tagihan (Invoice/SKRD) di ekosistem M-PAD.
---

# 💸 Invoicing & Billing Specialist

Skill ini mendefinisikan kemampuan agen dalam menangani siklus hidup tagihan di M-PAD, mulai dari kalkulasi JIT (Just-In-Time) hingga pelunasan.

## 🧠 Domain Knowledge

### 1. Mesin Kalkulasi (Calculation Engine)
- **Logic Location**: `App\Services\BillingService` dan `App\Services\FormulaParserService`.
- **Primary Data**: `TaxObject` metadata (jumlah kamar, omzet, luas lahan) + `RetributionRate`.
- **Hybrid Support**: Mendukung perhitungan Pajak Daerah (PBB-P2, Hotel, Restoran) dan Retribusi Jasa Umum/Usaha.

### 2. Status Transisi Tagihan
| Status | Deskripsi |
| :--- | :--- |
| `pending` | Tagihan terbit namun belum dibayar. |
| `unpaid` | Status virtual dari mesin JIT untuk periode yang belum dibayar tapi record `bills` belum dibuat. |
| `lunas` | Pembayaran berhasil diverifikasi. |
| `expired` | Melewati masa berlaku billing (biasanya untuk tagihan VA/QRIS). |

### 3. Logika Denda & Eskalasi (Dunning)
- **STPD (Surat Tagihan Pajak Daerah)**: Digunakan untuk menagih bunga 1-2% akibat keterlambatan.
- **Waiver Logic**: Penghapusan denda dikelola melalui `PenaltyWaiverController`.
- **Eskalasi Otomatis (V2)**:
  - **Teguran 1**: Dibuat jika Bill melewati `due_date`.
  - **Teguran 2**: Dibuat jika Teguran 1 berumur > 14 hari dan belum lunas.
  - **Automasi**: Dijalankan via `enforcements:generate-drafts`.

### 4. Integritas Data (Payment Sync V2)
Sistem menjamin keakuratan saldo piutang melalui **Pre-Payment Sync**:
- Sesaat sebelum pembayaran disimpan, sistem melakukan rekalkulasi denda secara real-time.
- Record `Bill` di database diperbarui agar sesuai dengan jumlah yang dibayarkan jika mencakup denda.

## 🛠️ Instruksi Operasional Agen

### Troubleshooting Tagihan Tidak Muncul
Jika pengguna melaporkan tagihan tidak muncul pada Objek Pajak tertentu:
1. Periksa `created_at` pada `tax_objects` (JIT Billing menghitung sejak tanggal ini).
2. Periksa status objek (harus `active`).
3. Periksa relasi `retribution_type_id`, jika null maka `BillingService` akan membalas koleksi kosong.
4. Gunakan script `AnalyzeLosPotensi.php` untuk mendeteksi gap data.

### Audit Eskalasi Penagihan (Dunning Audit)
Jika WP bertanya mengapa mendapatkan Teguran 2:
1. Cek riwayat `enforcement_notices` untuk objek tersebut.
2. Bandingkan tanggal `teguran_1` dengan tanggal hari ini.
3. Pastikan tidak ada pembayaran sukses untuk periode terkait di tabel `payments`.

### Verifikasi Akurasi Nominal
Untuk memastikan nominal tagihan benar:
1. Ambil metadata objek (`SELECT metadata FROM tax_objects WHERE id = ...`).
2. Cari tarif yang berlaku di `retribution_rates`.
3. Jalankan `FormulaParserService` secara manual (via tinker atau script test) dengan variabel metadata tersebut.

## 📝 Contoh Query Pendukung

### Melihat Outstanding Per Objek
```sql
SELECT period, amount, penalty_amount, status 
FROM bills 
WHERE tax_object_id = ? AND status != 'lunas';
```

### Mencari Data Laporan Mandiri (SPTPD) Tanpa Tagihan
```sql
SELECT * FROM monthly_reports 
WHERE status = 'approved' 
AND id NOT IN (SELECT monthly_report_id FROM bills WHERE monthly_report_id IS NOT NULL);
```

## ⚠️ Aturan Penting (Golden Rules)
1. **Jangan Generate Massal**: Hindari pembuatan record `bills` secara massal tanpa pemicu (event). Biarkan mesin JIT yang menanganinya saat dibutuhkan.
2. **Presisi Tanggal**: Selalu gunakan `Carbon::now()->startOfMonth()` untuk perbandingan periode bulanan guna menghindari selisih hari.
3. **Audit Trail**: Setiap perubahan status tagihan manual WAJIB mencatat `user_id` yang melakukan aksi tersebut.
