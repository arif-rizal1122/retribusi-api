# Verifikasi Response BRIVA SNAP terhadap SIT BRI

Tanggal audit: 2026-07-22  
Scope: endpoint inbound BRIVA yang aktif pada `retribusi-api`, yaitu access token, inquiry VA, dan payment VA.

## Kesimpulan

Response code dan message yang eksplisit pada SIT untuk token invalid, signature, mandatory/format, inquiry, payment, paid, expired, not found, serta invalid amount sudah diselaraskan pada scope endpoint aktif. Sistem tetap belum dapat dinyatakan siap UAT karena ekspor SIT tidak memuat contoh response body/schema final, replay identik masih memerlukan keputusan BRI, dan lifecycle/status/report VA belum dikonfirmasi.

## Sumber yang Dibandingkan

- `seethis/SIT_FUNCTIONAL_TEST_SCENARIOS_V4_1.md`, bagian `Transfer VA`, baris skenario 11.1-11.33. Dokumen ini adalah ekspor Markdown dari workbook SIT lokal.
- `docs/10_payment_gateway_h2h/BRI/snap_va.csv` hanya untuk path endpoint yang diajukan pada akses sandbox. Nilai credential di dalam file tidak disalin ke dokumen ini.
- `routes/api.php` untuk route runtime.
- `SnapBIController`, `SnapResponseMapper`, `SnapBrivaService`, dan service security SNAP untuk perilaku response saat ini.
- `tests/Feature/Payment/Snap` dan `tests/Unit/Payment/Snap` untuk bukti test lokal.

## Path Endpoint

| Fungsi | Path matriks sandbox lokal | Route runtime | Status |
| --- | --- | --- | --- |
| Access token | `/api/snap/v1.1/access-token/b2b` | `v1.1` tersedia; `v1.0` juga tersedia sebagai alias | PASS |
| Inquiry VA | `/api/snap/v1.0/transfer-va/inquiry` | Tersedia | PASS |
| Payment VA | `/api/snap/v1.0/transfer-va/payment` | Tersedia | PASS |

## Matriks Response SIT terhadap Implementasi

Kode `xx` pada skenario umum mengikuti service code endpoint: `24` untuk inquiry dan `25` untuk payment. Penerapan angka tersebut pada kode umum adalah inferensi dari pola SIT dan harus dikonfirmasi pada contoh resmi BRI sebelum UAT.

| SIT | Skenario | Expected SIT | Implementasi saat audit | Status |
| --- | --- | --- | --- | --- |
| 11.1 | Access token invalid | `401xx01`, `Access Token Invalid` | Inquiry `4012401`; payment `4012501`; keduanya memiliki feature test | PASS pada service aktif |
| 11.2 | Unauthorized signature | `401xx00`, `Unauthorized Signature` | Inquiry `4012400`; payment `4012500`; message sesuai | PASS pada service aktif |
| 11.3 | Missing mandatory field | `400xx02`, `Invalid Mandatory Field {...}` | Header dan field body yang dipakai service menghasilkan `4002402`/`4002502` | PASS pada kontrak aktif; schema final belum tersedia |
| 11.4 | Invalid field format | `400xx01`, `Invalid Field Format {...}` | Validator dasar identifier, paid amount, dan currency menghasilkan `4002401`/`4002501` | PASS pada kontrak aktif; schema final belum tersedia |
| 11.5 | Duplicate `X-EXTERNAL-ID` | `409xx00`, `Conflict` | Payload berbeda ditolak `4092400`; replay payload sama mengembalikan response tersimpan; payment belum memakai service code `25` | PARTIAL, perlu keputusan BRI |
| 11.6 | Inquiry VA valid | `2002400`, `Successful` | Sesuai dan memiliki feature test | PASS |
| 11.7 | Inquiry VA sudah lunas | `4042414`, `Bill has been paid` | Kode dan message sesuai, memiliki feature test | PASS |
| 11.8 | Inquiry VA kedaluwarsa | `4042419`, `Bill expired` | Kode dan message sesuai, memiliki feature test | PASS |
| 11.9 | Inquiry VA tidak terdaftar | `4042412`, `Bill not found` | Kode dan message sesuai, memiliki feature test | PASS |
| 11.10 | Payment VA valid | `2002500`, `Successful` | Sesuai, termasuk settlement multi-bill | PASS |
| 11.11 | Payment VA tidak terdaftar | `4042512`, `Bill not found` | Kode dan message sesuai, memiliki feature test | PASS |
| 11.12 | Payment VA invalid amount | `4042513`, `Invalid Amount` | Kode dan message sesuai; settlement tidak terjadi | PASS |
| 11.13-11.18 | Status/create/update/delete lifecycle VA | `2002600` sampai `2003100` | Route belum tersedia | BLOCKED, konfirmasi scope produk BRI |
| 11.32 | Get report VA | `2003500`, `Successful` | Route belum tersedia | BLOCKED, konfirmasi kebutuhan rekonsiliasi/report BRI |
| 11.33 | Transaction status inquiry | `2003600`, `Successful` | Route belum tersedia | BLOCKED, konfirmasi path dan schema BRI |

Skenario 11.19-11.31 adalah alur Partner A-PJP-Partner B dan tidak dimasukkan ke scope implementasi sampai BRI menyatakan produk tersebut wajib untuk M-PAD.

## Status Implementasi PAY-SNAP-ERR-001

Implementasi memakai service code `24` untuk inquiry, `25` untuk payment, `73` untuk access token, dan `52` untuk QR notification pada pipeline security. `SnapRequestValidator` menutup validasi minimum field yang benar-benar dipakai endpoint aktif. Regression lokal feature dan unit SNAP lulus 33 test dengan 114 assertion.

## Response Body

Kolom `Response Body` pada ekspor SIT lokal kosong. Implementasi saat ini mengembalikan `virtualAccountData` berisi identitas VA, wajib pajak, total, `billDetails`, dan status inquiry/payment. Struktur ini belum dapat diberi status PASS karena tidak ada schema, contoh JSON, panjang field, mandatory/optional rule, atau aturan masking resmi pada sumber yang diaudit.

Data yang masih dibutuhkan dari BRI:

- contoh request dan response JSON untuk token, inquiry, payment, dan setiap error;
- aturan canonical string-to-sign serta path yang masuk ke signature;
- konfirmasi service code final untuk error umum pada endpoint payment terhadap contoh response resmi;
- aturan replay `X-EXTERNAL-ID` dengan payload identik;
- daftar field mandatory, format, panjang, enum, dan decimal amount;
- scope lifecycle/status/report VA yang benar-benar wajib untuk produk BRIVA M-PAD.

## Temuan Credential Hygiene

File sumber sandbox yang tracked memuat nilai yang diberi label sebagai credential. Nilainya tidak dicantumkan di dokumen ini. Sebelum repo dibagikan atau dipakai UAT:

1. konfirmasikan apakah nilai tersebut masih aktif;
2. rotasi melalui PIC/portal BRI bila pernah aktif;
3. redaksi file tracked dan tentukan apakah history Git perlu dibersihkan;
4. simpan nilai pengganti hanya melalui secret manager atau `.env` server yang tidak tracked.

Tindakan rotasi dan rewrite history memerlukan otorisasi pengguna serta koordinasi eksternal, sehingga dicatat sebagai blocker terpisah.

## Tindak Lanjut

- `PAY-SNAP-ERR-001`: selesai untuk kode/message yang eksplisit dan field pada kontrak aktif; schema final tetap menunggu artefak BRI.
- `PAY-SNAP-LIFE-001`: konfirmasikan dan implementasikan lifecycle/status/report hanya setelah path dan schema resmi diberikan BRI.
- `PAY-SEC-001`: rotasi/redaksi credential-like values pada dokumen sandbox tracked dengan prosedur yang disetujui.
