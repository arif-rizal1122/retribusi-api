# API Backlog

Terakhir diperbarui: 2026-08-03

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
- [x] **PAY-GATE-001** Tambahkan readiness gate BRIVA default-off: `BRI_SNAP_ENABLED=false`, validasi partner/client key, public key, prefix/length VA, prefix dummy hanya untuk local/testing, serta IP whitelist untuk non-local sebelum API mengiklankan atau menerima BRIVA.
- [x] **PAY-GATE-001** Jawaban BRI 2026-07-30 diterapkan pada readiness/docs: signature sandbox BRI memakai RSA-SHA256, prefix `19960` boleh menjadi prefix sandbox resmi tanpa `partnerServiceId`, path sebelum `/snap` bebas, dan BRImo production bukan channel uji sandbox.
- [x] **PAY-GATE-001** Tambahkan command read-only `snap:briva-sandbox-handoff` untuk mencetak paket VA/endpoint sandbox yang aman dibagikan ke BRI tanpa credential, token, key, NIK, atau nomor HP.
- [BLOCKED] **PAY-SNAP-LIFE-001** Implementasikan status/create/update/delete/report VA setelah BRI mengonfirmasi scope produk, path, dan schema response.
- [BLOCKED] **PAY-SEC-001** Konfirmasi status, rotasi bila aktif, dan redaksi credential-like values pada dokumen sandbox tracked; rewrite history hanya dengan otorisasi eksplisit.

## Integrasi Citizen Billing

- [x] **SPTPD-001** Tambahkan endpoint owner-scoped `GET /api/citizen/tax-objects` untuk consumer SPTPD mobile, termasuk metadata klasifikasi self-assessment.

- [x] **INT-001** Lindungi `GET /api/citizen/bills` dengan Sanctum dan batasi hasil berdasarkan taxpayer pemilik token, bukan NIK dari query string.
- [x] **INT-001** Implementasikan `GET /api/citizen/payments/history` yang owner-scoped, paginated, dan tidak mengekspos callback payload maupun metadata approval internal.
- [x] **INT-001** Tambahkan feature test untuk guest access, ownership, pagination, response aman, dan penolakan internal user pada endpoint citizen.
- [x] **MOB-BILL-001** Normalisasi status response citizen menjadi `pending`, `overdue`, `paid`, `pending_verification`, atau `cancelled`; sertakan `status_label` dan `can_pay` agar klaim yang menunggu verifikasi tidak dapat dibayar ulang.
- [x] **MOB-PAY-001** Terbitkan rekening transfer, admin fee, total, expiry, dan instruksi dari API; proses klaim manual multi-tagihan secara atomik dengan nominal dari bill serta rollback penuh bila satu tagihan konflik.

## Merchant Auto Fund Transfer

- [BLOCKED] **AFT-API-001** Jangan aktifkan AFT di production sebelum kontrak merchant/SKDR, tarif PBJT, city, rekening sumber/tujuan, signature SNAP, dan direct-debit Bank Sultra resmi menggantikan nilai placeholder pada controller/job.
- [x] **AFT-ASPI-001** Dokumentasikan kontrak target Bank Sultra terhadap ASPI sebelum coding runtime: kandidat produk final Direct Debit H2H (`54/55/56/57/58`) atau Direct Debit BI-FAST/e-Mandate (`70/71/72`), path, header, body wajib, responseCode, signature, token, idempotency, gap implementasi lokal, dan pertanyaan Bank Sultra sudah dicatat di `docs/10_payment_gateway_h2h/BPD/BANK_SULTRA_AFT_ASPI_CONTRACT.md`. Scope task ini hanya dokumen/guard; route/service BRIVA `/api/snap/v1.0/transfer-va/*` tidak diubah.
- [x] **AFT-API-002** Tambahkan readiness gate dan konfigurasi SNAP Bank Sultra yang default-off: base URL, client/partner/channel ID, key/secret path, algoritma signature, timeout, allowed IP, account source/destination, dan selected product. Gate mencegah submit merchant serta outbound job AFT saat konfigurasi belum lengkap, memakai `snap.bank_sultra_aft`/`snap.partners.BANK_SULTRA`, dan tidak lagi memakai fallback URL/credential placeholder lama.
- [ ] **AFT-API-003** Buat service terpisah untuk Bank Sultra SNAP Direct Debit: token/header builder, canonical string/signature, `X-EXTERNAL-ID`, amount `{ value, currency }`, payload payment/status/notify sesuai produk terpilih, parser `responseCode` 7 digit, dan audit masking. Jangan reuse `BankSecurityCheck` generic HMAC untuk SNAP Bank Sultra kecuali sudah dipisahkan jelas dari flow BRIVA.
- [ ] **AFT-API-004** Ganti `ExecuteAutoDebitAFT` agar tidak lagi memakai dummy payload atau success check `status === SUCCESS`; job hanya boleh menandai success dari response SNAP resmi, menyimpan reference bank, menangani `202 Request In Progress`, failure code, timeout, retry, serta idempotent replay tanpa menggandakan pemotongan.
- [ ] **AFT-API-005** Harden endpoint merchant AFT: authorization merchant eksplisit, taxpayer/merchant ownership, city dari data resmi, tarif PBJT dari backend/config pajak, rekening sumber/tujuan dari profil/kontrak SKDR, dan penolakan bila SKDR belum disetujui. Hapus asumsi `city_id=1`, taxpayer simulasi, rekening dummy, dan pajak hardcoded dari jalur production.
- [ ] **AFT-API-006** Tambahkan endpoint/status history AFT yang aman untuk mobile: expose status warga (`pending`, `processing`, `success`, `failed`, `cancelled`) tanpa credential, raw bank payload, rekening penuh, signature, atau internal audit fields; sertakan `can_retry/can_cancel` hanya jika API benar-benar mendukungnya.
- [ ] **AFT-API-007** Tambahkan regression test AFT Bank Sultra tanpa credential nyata: readiness gate default-off, merchant ownership, SKDR required, payload Direct Debit/BI-FAST sesuai kontrak terpilih, signature/header builder, responseCode parser, `202` pending, success, failure, timeout, idempotency, dan audit masking. Pastikan test BRIVA SNAP tetap berjalan tanpa perubahan kontrak.

## Aturan Lanjutan

- Setelah PAY-002 selesai, perbarui `../WORKBOARD.md`, lalu lanjutkan hanya jika pengguna menginstruksikan.
- Endpoint atau perubahan schema baru wajib menambahkan/menyesuaikan test yang relevan.
