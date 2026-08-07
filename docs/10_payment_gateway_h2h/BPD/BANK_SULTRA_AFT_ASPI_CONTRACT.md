# Bank Sultra AFT ASPI Contract Audit

Tanggal audit: 2026-08-03
Scope task: `AFT-ASPI-001`
Status: kontrak target terdokumentasi; produk final Bank Sultra masih harus dikonfirmasi sebelum runtime coding.

## Kesimpulan

AFT atau auto-debit Bank Sultra harus diperlakukan sebagai produk SNAP keluarga Transfer Debit, bukan sebagai BRIVA. Implementasi runtime belum boleh memakai path, payload, atau response dummy yang ada saat ini. Produk final yang harus dipilih bersama Bank Sultra adalah salah satu dari dua jalur berikut:

1. Direct Debit H2H: service code `54/55/56/57/58`.
2. Direct Debit BI-FAST/e-Mandate: service code `70/71/72`.

Rekomendasi teknis awal adalah mulai dari Direct Debit H2H hanya jika Bank Sultra mengonfirmasi bahwa SKDR/account binding diselesaikan di luar API dan M-PAD cukup men-trigger pendebetan. Jika Bank Sultra mensyaratkan e-mandate yang diregistrasikan melalui API, gunakan Direct Debit BI-FAST/e-Mandate.

## Non-Scope

Jangan mengubah atau menggabungkan task ini dengan BRIVA. Jalur berikut tetap non-scope:

- `POST /api/snap/v1.0/transfer-va/inquiry`
- `POST /api/snap/v1.0/transfer-va/payment`
- `POST /api/citizen/payment-requests`
- service/config/test BRIVA yang sudah berjalan

Setiap implementasi AFT harus memakai namespace/config/service Bank Sultra sendiri agar regression BRIVA tetap stabil.

## Sumber Audit

- ASPI API Services: `https://apidevportal.aspi-indonesia.or.id/api-services`
- ASPI Transfer Debit: `https://apidevportal.aspi-indonesia.or.id/api-services/transfer-debit`
- ASPI Direct Debit: `https://apidevportal.aspi-indonesia.or.id/api-services/transfer-debit/direct-debit`
- ASPI Direct Debit BI-FAST: `https://apidevportal.aspi-indonesia.or.id/api-services/transfer-debit/direct-debit-bi-fast`
- ASPI Keamanan: `https://apidevportal.aspi-indonesia.or.id/api-services/keamanan`
- Lokal: `docs/10_payment_gateway_h2h/BPD/API_Spec_BPD_AFT_DirectDebit.md`
- Lokal: `app/Jobs/ExecuteAutoDebitAFT.php`
- Lokal: `app/Http/Controllers/Api/Merchant/KalkulatorBisnisController.php`
- Lokal: `app/Http/Middleware/BankSecurityCheck.php`
- Lokal: `tests/Feature/AftTriggerEngineTest.php`

## Kandidat Produk 1: Direct Debit H2H

| Fungsi | Service code | Method | Path ASPI | Catatan |
| --- | --- | --- | --- | --- |
| Payment | `54` | `POST` | `.../{version}/debit/payment-host-to-host` | Kandidat utama untuk trigger pemotongan AFT dari rekening merchant ke rekening penampung pajak. |
| Status | `55` | `POST` | `.../{version}/debit/status` | Wajib dipakai bila payment dapat menghasilkan pending/in-progress atau timeout. |
| Notify | `56` | `POST` | `.../{version}/debit/notify` | Perlu dikonfirmasi apakah Bank Sultra akan memanggil M-PAD setelah pendebetan selesai. |
| Cancel | `57` | `POST` | `.../{version}/debit/cancel` | Jangan expose aksi mobile sebelum Bank Sultra mengonfirmasi lifecycle cancel. |
| Refund | `58` | `POST` | `.../{version}/debit/refund` | Bukan scope awal kecuali proses koreksi pajak mensyaratkannya. |

Kontrak field penting yang harus dipetakan:

- `partnerReferenceNo`: ID transaksi M-PAD yang idempotent.
- `merchantId` atau `subMerchantId`: ID merchant sesuai provisioning Bank Sultra.
- `amount.value`: string decimal dua digit, contoh `10000.00`.
- `amount.currency`: `IDR`.
- `validUpTo`, `urlParams`, `payOptionDetails`, dan field customer/token: hanya dipakai jika Bank Sultra menyatakan wajib untuk produk yang dipilih.
- `responseCode`: mengikuti pola SNAP `HTTP code + service code + case code`; contoh service lain pada portal adalah `2005500`, `2005600`, `2005700`, dan `2005800`. Untuk Payment `54`, konfirmasi contoh resmi Bank Sultra karena output portal yang dirender perlu dicocokkan dengan aturan panjang `responseCode`.

## Kandidat Produk 2: Direct Debit BI-FAST/e-Mandate

| Fungsi | Service code | Method | Path ASPI | Catatan |
| --- | --- | --- | --- | --- |
| Registrasi e-Mandate | `70` | `POST` | `.../{version}/debit/fast-emandate` | Dipakai bila mandat pendebetan harus didaftarkan ke bank via API. |
| Trigger Direct Debit Transfer | `71` | `POST` | `.../{version}/debit/fast-payment` | Dipakai untuk payment setelah `eMandateReffId` tersedia. |
| Notify Direct Debit | `72` | `POST` | `.../{version}/debit/fast-notify` | Callback status dari bank ke M-PAD. |

Kontrak field penting yang harus dipetakan:

- `bankCode`: kode bank berdasarkan Bank Indonesia Code.
- `sourceAccountNo` dan `sourceAccountName`: rekening yang didebet; wajib berasal dari data kontrak/merchant yang sah.
- `beneficiaryAccountNo` dan `beneficiaryAccountName`: rekening tujuan/penampung pajak.
- `billerId`, `billerName`, `customerId`: wajib untuk registrasi e-mandate.
- `expiredDatetime`: masa berlaku e-mandate.
- `eMandateReffId`: wajib untuk payment dan notify setelah registrasi.
- `amount.value` dan `amount.currency`: nominal debit dan mata uang.

## Header, Signature, dan Security Baseline

Baseline ASPI yang harus dipenuhi sebelum outbound AFT aktif:

- OAuth 2.0 dan Bearer token.
- Header transaksi minimal dari sampel ASPI: `Authorization`, `X-TIMESTAMP`, `X-SIGNATURE`, `X-PARTNER-ID`, `X-EXTERNAL-ID`, dan `CHANNEL-ID`; field tambahan seperti `Authorization-Customer`, `X-IP-ADDRESS`, `X-DEVICE-ID`, lokasi, atau `ORIGIN` harus mengikuti produk Bank Sultra.
- Access token memakai asymmetric signature `SHA256withRSA` dengan string-to-sign yang dikonfirmasi bank.
- Signature transaksi dapat memakai `HMAC_SHA512` atau asymmetric signature sesuai produk dan provisioning Bank Sultra; jangan mengasumsikan sama dengan BRIVA BRI.
- `X-EXTERNAL-ID` tidak boleh dipakai ulang pada hari yang sama untuk payload berbeda. Replay identik harus dirancang idempotent di API.
- TLS harus mengikuti standar ASPI; TLS 1.3 menjadi target, TLS 1.2 hanya bila cipher minimum dan kebijakan bank mengizinkan.

## Gap Implementasi Lokal Saat Ini

| Area | Kondisi sekarang | Gap terhadap ASPI |
| --- | --- | --- |
| Dokumen lama BPD | `API_Spec_BPD_AFT_DirectDebit.md` memakai `/snap/v1.0/direct-debit/payment` dan response `2000000`/`status=SUCCESS`. | Path dan response belum sama dengan ASPI Transfer Debit. Dokumen lama harus dianggap draft historis, bukan kontrak implementasi. |
| Job AFT | `ExecuteAutoDebitAFT` memakai fallback URL placeholder, source/destination dummy, header minimal, dan success check `status === SUCCESS`. | Harus diganti service SNAP Bank Sultra dengan token/signature/header lengkap, parser `responseCode`, timeout, pending, failure, dan idempotency. |
| Controller merchant | `KalkulatorBisnisController` memakai tarif 10%, `city_id=1`, dan langsung dispatch job. | Tarif, city, rekening, merchant, dan SKDR harus berasal dari backend/kontrak resmi; submit harus ditolak bila readiness belum lengkap. |
| Security generic H2H | `BankSecurityCheck` memakai HMAC SHA256 atas `bill_number + timestamp + amount`. | Tidak cukup untuk SNAP Bank Sultra. Buat security service/middleware Bank Sultra terpisah. |
| Config | `ExecuteAutoDebitAFT` membaca `services.bpd.*`, tetapi `config/services.php` belum mendefinisikan blok BPD. | `AFT-API-002` harus menambah config default-off tanpa credential placeholder. |
| Test | `AftTriggerEngineTest` masih menerima dummy success dan dapat skip route webhook. | Regression baru harus menguji readiness gate, payload/header, parser responseCode, pending/failure/timeout, idempotency, dan audit masking tanpa credential nyata. |

## Pertanyaan Wajib ke Bank Sultra

1. Produk final yang dipakai: Direct Debit H2H atau Direct Debit BI-FAST/e-Mandate?
2. Base URL sandbox dan production, termasuk prefix path sebelum `/{version}/...`.
3. Versi API final: `v1.0` atau versi lain.
4. Mekanisme token: B2B saja atau perlu B2B2C/Authorization-Customer?
5. Algoritma signature transaksi: `HMAC_SHA512`, `SHA256withRSA`, atau varian bank-specific.
6. Canonical string-to-sign untuk access token dan transaksi.
7. Nilai resmi `client_key`, `partner_id`, `channel_id`, merchant/biller ID, dan kode bank.
8. Aturan `X-EXTERNAL-ID`: uniqueness, TTL, replay identik, dan response untuk payload berbeda.
9. Field wajib request/response untuk payment, status, notify, cancel, refund, atau e-mandate.
10. Status yang mungkin muncul: success, pending/in-progress, failed, expired, cancelled, reversed.
11. Aturan timeout dan retry yang tidak menggandakan debit.
12. Apakah Bank Sultra akan mengirim notify callback ke M-PAD, dan path callback yang disetujui.
13. Format rekening sumber/tujuan, masking, validasi nama rekening, dan larangan menyimpan raw payload.
14. Biaya transaksi AFT, fee type, dan bukti PKS bebas biaya jika diklaim Rp 0.
15. Syarat legal SKDR/e-mandate, termasuk apakah mandat harus diregistrasikan ke bank.
16. IP whitelist dan sertifikat/TLS requirement.
17. Skenario SIT/UAT positif dan negatif yang harus lulus.

## Urutan Implementasi Setelah Kontrak Dipilih

1. `AFT-API-002`: readiness gate dan config default-off.
2. `AFT-API-003`: service SNAP Bank Sultra untuk token, signature, headers, payload, response parser, dan audit masking.
3. `AFT-API-004`: job AFT memakai service resmi dan tidak settle dari dummy status.
4. `AFT-API-005`: hardening endpoint merchant, ownership, SKDR, city, tarif, rekening.
5. `AFT-API-006`: kontrak availability/status aman untuk mobile.
6. `AFT-API-007`: regression test dan guard BRIVA.

## Acceptance AFT-ASPI-001

- Kontrak kandidat Direct Debit dan Direct Debit BI-FAST terdokumentasi.
- Gap implementasi lokal terhadap ASPI terdokumentasi.
- Pertanyaan eksternal ke Bank Sultra terdokumentasi.
- Scope BRIVA ditandai non-scope.
- Tidak ada perubahan runtime, route, schema, `.env`, credential, atau key.
