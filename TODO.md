# API Backlog

Terakhir diperbarui: 2026-07-17

## Status

- `[x]` Selesai dan terverifikasi.
- `[ ]` Siap dikerjakan.
- `[BLOCKED]` Memerlukan kontrak, akses, atau keputusan eksternal.

## Pembayaran BRIVA

- [x] **PAY-002** Feature test citizen payment request lulus: create, reuse request aktif, ownership, expiry, cancel, serta response payload.
- [x] **PAY-002** Test callback BRIVA multi-tagihan lulus: nominal snapshot, status bill, payment record, dan item request konsisten.
- [ ] Dokumentasikan konfigurasi sandbox yang diperlukan tanpa menaruh credential pada file tracked.
- [ ] Verifikasi response SNAP terhadap spesifikasi bank yang dipakai sebelum UAT.

## Aturan Lanjutan

- Setelah PAY-002 selesai, perbarui `../WORKBOARD.md`, lalu lanjutkan hanya jika pengguna menginstruksikan.
- Endpoint atau perubahan schema baru wajib menambahkan/menyesuaikan test yang relevan.
