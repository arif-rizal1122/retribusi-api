# 📋 Reporting Schema Test

**Waktu Eksekusi**: 2026-08-23 03:52:58
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
| ✅ | Summary data terisi (1 item): RevenueByType.amount bertipe number | PASS | semua amount number |
| ✅ | Summary tanpa param (default) → schema valid | PASS | HTTP 200 |
| ✅ | Summary tanggal terbalik (start > end) → 422 | PASS | HTTP 422 |
| ✅ | Summary start_date invalid ("inikacau") → 422 | PASS | HTTP 422 |
| ✅ | Recent → 200 | PASS | HTTP 200 |
| ✅ | Recent adalah array of object | PASS | 10 item |
| ✅ | Recent maksimal 10 item | PASS | 10 item |
| ✅ | RecentReport sesuai skema {id, taxpayer_name, type, amount, date, method, status} | PASS | Semua field & tipe sesuai |
| ✅ | Recent.date format datetime valid | PASS | format Y-m-d H:i:s |

**Total**: 20 PASS, 0 WARN, 0 FAIL

## Status Perbaikan

1. **[FIXED - SCHEMA]** \`amount\` kini dikembalikan sebagai number di kedua endpoint. Perbaikan: cast `'amount' => 'float'` pada `app/Models/Payment.php` + `(float)`/`(int)` pada hasil SUM di `ReportController::getSummary()`.
2. **[FIXED - INPUT VALIDATION]** `start_date`/`end_date` kini divalidasi (`date`, `after_or_equal:start_date`). Input invalid/tanggal terbalik mengembalikan HTTP 422, bukan 500.
3. **[INFO]** Backend tetap mengirim field tambahan `period`, `count`, dan placeholder `target = amount * 1.2` yang tidak dikonsumsi frontend — tidak berbahaya.
