# API Backlog

Terakhir diperbarui: 2026-07-20

## Status

- `[x]` Selesai dan terverifikasi.
- `[ ]` Siap dikerjakan.
- `[BLOCKED]` Memerlukan kontrak, akses, atau keputusan eksternal.

## Pembayaran BRIVA

- [x] **PAY-002** Feature test citizen payment request lulus: create, reuse request aktif, ownership, expiry, cancel, serta response payload.
- [x] **PAY-002** Test callback BRIVA multi-tagihan lulus: nominal snapshot, status bill, payment record, dan item request konsisten.
- [ ] Dokumentasikan konfigurasi sandbox yang diperlukan tanpa menaruh credential pada file tracked.
- [ ] Verifikasi response SNAP terhadap spesifikasi bank yang dipakai sebelum UAT.

## Integrasi Citizen Billing

- [x] **INT-001** Lindungi `GET /api/citizen/bills` dengan Sanctum dan batasi hasil berdasarkan taxpayer pemilik token, bukan NIK dari query string.
- [x] **INT-001** Implementasikan `GET /api/citizen/payments/history` yang owner-scoped, paginated, dan tidak mengekspos callback payload maupun metadata approval internal.
- [x] **INT-001** Tambahkan feature test untuk guest access, ownership, pagination, response aman, dan penolakan internal user pada endpoint citizen.

## Aturan Lanjutan

- Setelah PAY-002 selesai, perbarui `../WORKBOARD.md`, lalu lanjutkan hanya jika pengguna menginstruksikan.
- Endpoint atau perubahan schema baru wajib menambahkan/menyesuaikan test yang relevan.
