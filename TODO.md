# API Backlog

Terakhir diperbarui: 2026-07-28

## Status

- `[x]` Selesai dan terverifikasi.
- `[ ]` Siap dikerjakan.
- `[BLOCKED]` Memerlukan kontrak, akses, atau keputusan eksternal.

## Pembayaran BRIVA

- [x] **PAY-002** Feature test citizen payment request lulus: create, reuse request aktif, ownership, expiry, cancel, serta response payload.
- [x] **PAY-002** Test callback BRIVA multi-tagihan lulus: nominal snapshot, status bill, payment record, dan item request konsisten.
- [x] **MOB-PAY-002** Terbitkan referensi provider serta receipt/NTPD dan path unduh SSPD per bill pada payment request berstatus paid.
- [x] **PAY-SBX-001** Dokumentasikan konfigurasi sandbox yang diperlukan tanpa menaruh credential pada file tracked; selaraskan nama variable `.env.example` dengan `config/snap.php` dan ignore direktori key lokal.
- [x] **PAY-TEST-001** Selaraskan unit test `SnapHeaderValidator`, `SnapSignatureService`, dan `SnapTokenService` dengan kontrak bank context service saat ini.
- [x] **PAY-SPEC-001** Verifikasi response SNAP terhadap matriks SIT BRI; success inquiry/payment sesuai, sedangkan gap error mapping, schema, lifecycle, dan credential hygiene dicatat di `SNAP_RESPONSE_VERIFICATION.md`.
- [x] **PAY-SNAP-ERR-001** Selaraskan error code/message inquiry dan payment yang sudah eksplisit di SIT, lalu tambahkan feature test untuk token invalid, mandatory/format, paid, expired, not found, dan invalid amount.
- [x] **PAY-XCH-001** Tutup konflik lintas channel: klaim manual pending memblokir create BRIVA, BRIVA aktif memblokir klaim manual, approval manual ditolak bila bill sudah settled oleh pembayaran lain, serta callback SNAP hanya menerima `payment_request` BRIVA yang masih `pending`.
- [BLOCKED] **PAY-SNAP-LIFE-001** Implementasikan status/create/update/delete/report VA setelah BRI mengonfirmasi scope produk, path, dan schema response.
- [BLOCKED] **PAY-SEC-001** Konfirmasi status, rotasi bila aktif, dan redaksi credential-like values pada dokumen sandbox tracked; rewrite history hanya dengan otorisasi eksplisit.

## Integrasi Citizen Billing

- [x] **INT-001** Lindungi `GET /api/citizen/bills` dengan Sanctum dan batasi hasil berdasarkan taxpayer pemilik token, bukan NIK dari query string.
- [x] **INT-001** Implementasikan `GET /api/citizen/payments/history` yang owner-scoped, paginated, dan tidak mengekspos callback payload maupun metadata approval internal.
- [x] **INT-001** Tambahkan feature test untuk guest access, ownership, pagination, response aman, dan penolakan internal user pada endpoint citizen.
- [x] **MOB-BILL-001** Normalisasi status response citizen menjadi `pending`, `overdue`, `paid`, `pending_verification`, atau `cancelled`; sertakan `status_label` dan `can_pay` agar klaim yang menunggu verifikasi tidak dapat dibayar ulang.
- [x] **MOB-PAY-001** Terbitkan rekening transfer, admin fee, total, expiry, dan instruksi dari API; proses klaim manual multi-tagihan secara atomik dengan nominal dari bill serta rollback penuh bila satu tagihan konflik.

## Merchant Auto Fund Transfer

- [BLOCKED] **AFT-API-001** Jangan aktifkan AFT di production sebelum kontrak merchant/SKDR, tarif PBJT, city, rekening sumber/tujuan, signature SNAP, dan direct-debit Bank Sultra resmi menggantikan nilai placeholder pada controller/job.
- [ ] Setelah kontrak tersedia, tambahkan authorization merchant yang eksplisit serta feature test ownership untuk submit omzet dan history AFT.

## Aturan Lanjutan

- Setelah PAY-002 selesai, perbarui `../WORKBOARD.md`, lalu lanjutkan hanya jika pengguna menginstruksikan.
- Endpoint atau perubahan schema baru wajib menambahkan/menyesuaikan test yang relevan.
