# 📋 Reporting Schema Test

**Waktu Eksekusi**: 2026-08-23 03:44:27
**Target**: http://127.0.0.1:8000
**Kontrak**: interface `ReportSummary` & `RecentReport` di `retribusi-petugas/src/pages/Reporting.tsx`

## Hasil

| Status | Kasus Uji | Hasil | Detail |
|---|---|---|---|
| ✅ | Login petugas@bapenda.go.id | PASS | Token diterima |
| ✅ | Login admin@retribusi.id | PASS | Token diterima |
| ✅ | Summary tanpa token → 401 | PASS | HTTP 401 |
| ✅ | Recent tanpa token → 401 | PASS | HTTP 401 |
| ✅ | Summary periode valid → 200 | PASS | HTTP 200 |
| ✅ | Summary.total_revenue bertipe number | PASS | actual: 0 (integer) |
| ✅ | Summary.revenue_by_type adalah array | PASS |  |
| ✅ | RevenueByType[] sesuai skema {type, amount, percentage, target} | PASS | 0 item valid |
| ✅ | Summary.stats.total_transactions number | PASS | actual: 0 |
| ✅ | Summary.stats.avg_transaction number | PASS | actual: 0 |
| ✅ | Konsistensi: total_revenue == sum(amount) | PASS | total=0 vs sum=0 |
| ❌ | Summary data terisi (1 item): RevenueByType.amount bertipe number | FAIL | [0].amount="212202000.00" (harus number) — SUM() MySQL dikembalikan sebagai string |
| ✅ | Summary tanpa param (default) → schema valid | PASS | HTTP 200 |
| ✅ | Summary tanggal terbalik (start > end) | PASS | HTTP 200 — backend tidak validasi urutan tanggal |
| ❌ | Summary start_date invalid ("inikacau") | FAIL | HTTP 500 — Carbon::parse melempar exception (500) |
| ✅ | Recent → 200 | PASS | HTTP 200 |
| ✅ | Recent adalah array of object | PASS | 10 item |
| ✅ | Recent maksimal 10 item | PASS | 10 item |
| ❌ | RecentReport sesuai skema {id, taxpayer_name, type, amount, date, method, status} | FAIL | 10/10 item punya amount sebagai STRING numerik (mis. "475000.00") — melanggar kontrak `amount: number` (Payment::amount tanpa cast numerik) |
| ✅ | Recent.date format datetime valid | PASS | format Y-m-d H:i:s |

**Total**: 17 PASS, 0 WARN, 3 FAIL

## Temuan Penting

1. **[SCHEMA MISMATCH]** `amount` dikembalikan sebagai STRING numerik di kedua endpoint (mis. "475000.00") — melanggar kontrak `amount: number`. Penyebab: kolom decimal tanpa cast numerik di `app/Models/Payment.php:38` + hasil `SUM()` MySQL berupa string. UI masih berjalan karena `Intl.NumberFormat.format()` memaksa konversi, namun rapuh untuk aritmetika (CSV export menulis string, perbandingan angka bisa salah). Perbaikan: tambah `'amount' => 'decimal:2'` pada `` Payment dan bungkus SUM dengan `(float)` / `(int)`.
2. **[INPUT VALIDATION]** `getSummary()` mem-parsing `start_date`/`end_date` langsung dengan `Carbon::parse()` tanpa validasi — input non-tanggal menghasilkan HTTP 500.
3. **[INFO]** Backend mengirim field tambahan `period`, `count`, dan placeholder `target = amount * 1.2` (ReportController.php:46) yang tidak dikonsumsi frontend.
