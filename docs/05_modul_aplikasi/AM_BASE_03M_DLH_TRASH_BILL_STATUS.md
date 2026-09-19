# AM-BASE-03M — Status Bill Inquiry Persampahan DLH

Tanggal checkpoint: 20 September 2026

## Perubahan

`GET /api/v1/dlh/trash/inquiry` dan alias `/api/dlh` sekarang menerbitkan status bill pada batas citizen dengan kontrak `CitizenBillStatus`:

- `status`: `pending`, `overdue`, `paid`, `pending_verification`, `cancelled`, `expired`, `disputed`, atau `unknown`.
- `status_label`: label bahasa Indonesia dari aturan status bersama.
- `can_pay`: izin bayar yang dihitung server.
- `status_raw`: nilai storage uppercase untuk audit/migrasi consumer; field ini tidak menjadi sumber keputusan UI.

Nilai database tidak ditulis ulang. Alias seperti `UNPAID`, `PAID`, `lunas`, tanggal jatuh tempo, klaim pembayaran pending, dan payment request aktif dipetakan saat response dibentuk. `active_bill` mempertahankan urutan tagihan terbaru dan memprioritaskan tagihan pending, jatuh tempo, atau sedang menunggu verifikasi; `bill_history` tetap dibatasi lima tagihan terbaru.

Field identitas objek, nominal, periode, denda, total, dan tanggal jatuh tempo tetap tersedia. Inquiry tetap exact-match pada ID Bangunan dan owner-scoped untuk Taxpayer.

## Consumer

Mobile `SampahInquiry` menggunakan `normalizeCitizenBillStatus`, `getCitizenBillStatusLabel`, dan `isCitizenBillPayable`. Badge lunas dan tombol bayar tidak lagi membaca alias uppercase secara langsung. Audit source admin dan petugas tidak menemukan consumer endpoint inquiry ini.

## Validasi

- `DlhTrashBillingTest`: **11 test/48 assertion lulus**.
- Regresi bank/core/DLH/lab/citizen billing/SNAP/dokumen: **113 test/557 assertion lulus**.
- PHP lint, Pint/diff check terkait API dijalankan.
- Mobile `npm.cmd run typecheck`, ESLint terarah, dan `npm.cmd run build` lulus. Test halaman terarah belum dapat dimuat karena Vitest berhenti pada `spawn EPERM` saat memuat `externalize-deps`.

Tidak ada migration, perubahan `.env`, credential, gateway, admin, atau petugas. Parent AM-BASE-03/AM-SMP-02 tetap terbuka untuk kebijakan objek pihak lain, scheduler, rekonsiliasi, dan UAT eksternal.
