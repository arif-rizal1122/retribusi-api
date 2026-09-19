# API Backlog

## Lifecycle callback BRIVA terlambat — 19 September 2026

- [x] **AM-PAY-03A** Callback pembayaran BRIVA yang tiba setelah \`expires_at\` ditolak dengan response SNAP \`4032500\` dan tidak mengubah Bill, PaymentRequest, atau menerbitkan Payment/receipt. Skenario cancel, amount snapshot, multi-bill, inquiry, receipt dan idempotensi existing tetap diregresikan pada suite SNAP.
- Validasi: \`php artisan test tests/Feature/Payment/Snap tests/Unit/Payment/Snap --do-not-cache-result\` lulus **58 test/197 assertion**. Kontrak [AM_PAY_03_BRIVA_LIFECYCLE_CHECKPOINT.md](docs/05_modul_aplikasi/AM_PAY_03_BRIVA_LIFECYCLE_CHECKPOINT.md). Ini uji aplikasi lokal/SQLite; bukan bukti UAT bank.
- Parent AM-PAY-03 tetap terbuka untuk bukti create/reuse citizen end-to-end, lifecycle/status bank, rekonsiliasi dan receipt pada environment bank yang disetujui.

## Isolasi resolver BRIVA antar-channel — 19 September 2026

- [x] **AM-PAY-05A** \`SnapBrivaService\` kini hanya resolve \`PaymentRequest\` dengan \`payment_channel=BRI\` dan \`method=VA\` untuk VA maupun external ID. Inquiry/payment BRIVA tidak dapat mengambil request channel Bank Sultra atau produk lain yang kebetulan memiliki nomor sama.
- Validasi: \`SnapBrivaInquiryTest\` lulus 4 test/17 assertion; kontrak [AM_PAY_05_BRIVA_CHANNEL_ISOLATION.md](docs/05_modul_aplikasi/AM_PAY_05_BRIVA_CHANNEL_ISOLATION.md). Consumer audit mobile/admin/petugas tidak menemukan endpoint SNAP inbound yang dipakai frontend; tidak ada perubahan frontend.
- Parent AM-PAY-05 tetap terbuka untuk lock/idempotensi paralel, amount/fee lintas channel, dan uji callback bersama.

## Kesiapan payment option citizen — 19 September 2026

- [x] AM-PAY-02: payment_options API sekarang memakai gate readiness per produk. BRIVA tetap melalui SnapBrivaReadiness; transfer manual hanya tersedia bila rekening klasifikasi valid; Sultra VA citizen default-off dan baru boleh dibuat/diiklankan setelah BANK_SULTRA_CITIZEN_VA_ENABLED serta BANK_SULTRA_CITIZEN_VA_CONTRACT_CONFIRMED dikonfirmasi.
- Validasi: suite readiness/checkout **23 test/206 assertion lulus**; regresi DLH/lab/citizen/payment/SNAP/rekening manual **54 test/387 assertion lulus**; PHP lint, Pint, dan diff check lulus. Kontrak: docs/05_modul_aplikasi/AM_PAY_02_CITIZEN_PAYMENT_AVAILABILITY.md.
- Tidak ada migration, credential, callback, atau outbound bank request. Flag contract_confirmed adalah guard operasional dan tidak menjadi bukti UAT/provisioning. Consumer audit mobile/admin/petugas selesai; mobile sudah menghormati available=false dari API.

## Status payment request pada batas citizen — 19 September 2026

- [x] AM-BASE-03G: Top-level payment request dan active_payment_request pada citizen bills sekarang memakai CitizenPaymentRequestStatus. Alias success/settled/processing/void dan status tidak dikenal dinormalisasi; label channel BRI/Sultra konsisten. can_refresh/can_cancel tidak diperluas di luar lifecycle storage pending.
- Validasi: suite lintas DLH/lab/citizen/payment **50 test/360 assertion lulus**; Pint, PHP lint, dan diff check lulus. Kontrak: docs/05_modul_aplikasi/AM_BASE_03G_PAYMENT_REQUEST_STATUS.md.
- Consumer audit: mobile sudah memiliki PaymentRequestStatus canonical dan tidak membuat keputusan status dari raw database; admin/petugas tidak memiliki consumer endpoint citizen payment request. Parent AM-BASE-03 tetap terbuka untuk reverse inquiry, rekonsiliasi gateway dan UAT eksternal.

## Status bill pada payment request citizen — 19 September 2026

- [x] AM-BASE-03F: Detail data.bills[] pada payment request citizen menerbitkan status, status_label, dan can_pay dari CitizenBillStatus. Request aktif menghasilkan pending_verification; request cancelled/expired tidak mengunci bill pending/overdue; status storage tidak diubah.
- Validasi: suite DLH/lab/citizen/payment 49 test/346 assertion lulus; Pint, PHP lint, dan diff check lulus. Kontrak: docs/05_modul_aplikasi/AM_BASE_03F_PAYMENT_REQUEST_BILL_STATUS.md.
- Consumer audit: tipe mobile PaymentRequestBillDetail mengikuti field additive dan normalisasi alias lama tetap defensif; typecheck/build/targeted ESLint lulus. Vitest terhalang spawn EPERM saat memuat config. Admin dan petugas tidak memiliki consumer endpoint citizen ini. Parent AM-BASE-03 tetap terbuka untuk reverse inquiry, rekonsiliasi gateway dan UAT eksternal.

## Status bill pada riwayat pembayaran citizen — 19 September 2026

- [x] **AM-BASE-03E** Endpoint `GET /api/citizen/payments/history` menormalkan status bill nested dengan `CitizenBillStatus` dan menerbitkan `status_label` additive. Status storage lama (`lunas`, `PAID`, `pending`, dan variasinya) tidak diubah; ownership, pagination, dan status pembayaran top-level tetap kompatibel.
- Validasi: suite DLH/lab/citizen billing **33 test/230 assertion lulus**; Pint, PHP lint, dan diff check lulus. Regresi H2H DLH menerbitkan bill melalui `DlhTrashBillingService` agar inquiry, callback, idempotensi, dan rekap memakai fixture layanan yang sama. [Kontrak](docs/05_modul_aplikasi/AM_BASE_03E_CITIZEN_PAYMENT_HISTORY_STATUS.md).
- Consumer audit: mobile hanya memakai field top-level untuk tren/aktivitas dan sekarang mendeklarasikan status nested canonical; admin dan petugas tidak memakai endpoint ini. Typecheck/build mobile lulus; lint penuh dan Vitest terhalang baseline/environment yang dicatat di kontrak. Parent AM-BASE-03 tetap terbuka untuk rekonsiliasi gateway lain dan alur settlement eksternal.

## Settlement dan rekap DLH — 19 September 2026

- [x] **AM-BASE-03D / AM-SMP-08A / AM-LAB-06A** Normalisasi pembacaan status settlement DLH pada rekap eksekutif dan peta kepatuhan. Status H2H `lunas`/variasi `PAID` dihitung sebagai lunas; piutang dan denda memakai status kanonik. Bill lab yang tertaut tidak dihitung ulang sebagai persampahan; order lab yang bill-nya sudah lunas masuk realisasi satu kali.
- Validasi: `DlhSpatialComplianceTest` + `DlhBankSultraPaymentIntegrationTest` lulus **5 test/42 assertion**; regresi `DlhTrashBillingTest`, `DlhLabServiceTest`, `CitizenDlhLabOwnershipTest`, dan `CitizenBillingReadTest` lulus **27 test/183 assertion**. Pint test, PHP lint, dan diff check lulus. [Kontrak dan batas](docs/05_modul_aplikasi/AM_BASE_03D_DLH_RECONCILIATION.md).
- Consumer audit: `retribusi-admin/src/pages/DlhDashboard.tsx` memakai field response yang sama tanpa perubahan; mobile dan petugas tidak menemukan consumer endpoint rekap/peta. Tidak ada perubahan migration, credential, admin, petugas, atau UI. Parent AM-BASE-03/AM-SMP-08/AM-SMP-09/AM-LAB-06 tetap terbuka untuk gateway lain, scheduler, tarif resmi, ekspor dan data eksternal.

## Snapshot quote sewa aset PUPR - 19 September 2026

- [x] **AM-BASE-05B / AM-PUPR-03B** Setiap pengajuan rental citizen yang berhasil menyimpan `metadata.quote` dengan versi adapter, sumber/master aset, waktu perubahan master, tarif/satuan, kuantitas/hari, tronton, total, requested DP, confirmed DP, dan sisa pembayaran. Snapshot memakai angka server yang sama dengan kolom rental dan tidak membutuhkan migration.
- Validasi: `CitizenAssetRentalTest` lulus **38 test/219 assertion** pada SQLite `:memory:`; PHP lint, Pint test, dan diff check lulus. Consumer mobile/admin/petugas tetap kompatibel karena metadata bersifat additive. [Kontrak](docs/05_modul_aplikasi/AM_BASE_05B_QUOTE_SNAPSHOT.md). Versi tarif Perda/zona resmi dan payment settlement rental tetap menunggu D02/D04/PAY.

## Otoritas quote sewa aset PUPR - 19 September 2026

- [x] **AM-BASE-05A / AM-PUPR-03A** Pengajuan citizen tidak lagi mempercayai tarif, satuan, biaya tronton, atau DP dari payload mobile. Tarif/satuan diambil dari master `asset_items`, biaya tronton dihitung dari band server yang sudah ada, alat `wajib_tronton` tetap memaksa mobilisasi, dan DP kiriman disimpan sebagai `metadata.requested_dp` tanpa dianggap settlement.
- Tarif master nol ditolak dengan `ASSET_RATE_UNAVAILABLE`; bentuk response rental tetap kompatibel dengan consumer mobile. Tidak ada migration atau perubahan admin/petugas. [Kontrak](docs/05_modul_aplikasi/AM_BASE_05A_SERVER_ASSET_QUOTE.md). Pencocokan band tronton terhadap Perda/SOP dan update DP setelah payment confirmed tetap terbuka pada D02/D04/PAY.
- Validasi: `CitizenAssetRentalTest` lulus **38 test/206 assertion** pada SQLite `:memory:`; `PuprInspectionAndDocumentsTest` lulus **4 test/14 assertion**. Suite `AssetRentalWorkflowTest` dan `AssetRentalBookingConflictTest` masih gagal sebelum request karena fixture membuat slug `alat-berat` yang sudah di-seed migration (`UNIQUE asset_categories.slug`); bukan regresi pada controller quote. PHP lint, Pint test, dan diff check lulus. Commit `a21f920` dibuat; push ditolak non-fast-forward dan fetch untuk rebase aman gagal proxy `127.0.0.1:9`, tanpa force-push/reset.

## Otorisasi mutasi persampahan DLH — 19 September 2026

- [x] **AM-BASE-04C** Endpoint transfer penghuni dan generate billing massal kini hanya untuk `User` internal super admin atau operator `opd_id=4`; Taxpayer tetap dapat membaca inquiry/kalkulator tetapi tidak dapat mengubah objek atau menerbitkan tagihan. Transfer juga dibatasi ke objek scope DLH.
- Validasi: `DlhTrashBillingTest` lulus **10 test/38 assertion** pada SQLite `:memory:`; Pint controller/test lulus. Pencarian consumer di mobile/admin/petugas tidak menemukan pemakai endpoint mutasi. [Kontrak](docs/05_modul_aplikasi/AM_BASE_04C_DLH_MUTATION_AUTHORIZATION.md). Pemutakhiran penghuni oleh warga tetap menunggu workflow verifikasi D05.

## Inquiry ID Bangunan persampahan — 19 September 2026

- [x] **AM-SMP-01A/02A** Inquiry warga memakai `tax_objects.nop` sebagai ID Bangunan yang dicari secara exact-match. Sesi `Taxpayer` hanya dapat melihat objek miliknya; NIK, nama, alamat, dan ID database tidak lagi menjadi kunci. Bills dibatasi ke `tax_object_id` yang diinquiry, status/total dinormalisasi, dan route diberi throttle 30 permintaan/menit pada alias `/api/dlh` serta `/api/v1/dlh`.
- Validasi: `php artisan test tests/Feature/DlhTrashBillingTest.php tests/Feature/DlhBankSultraPaymentIntegrationTest.php tests/Feature/DlhSpatialComplianceTest.php --do-not-cache-result` lulus **12 test/65 assertion** pada SQLite `:memory:`; PHP lint, Pint controller/test, route list, dan diff check lulus. Consumer admin/petugas tidak memakai endpoint ini; mobile memakai kontrak langsung tanpa fallback daftar tagihan.
- [Kontrak](docs/05_modul_aplikasi/AM_SMP_01A_BUILDING_INQUIRY_OWNERSHIP.md). Parent `AM-SMP-01` tetap terbuka untuk master ID Bangunan resmi/backfill, relasi penghuni dan rekonsiliasi sumber data. `AM-SMP-03/04` tetap menunggu tarif resmi dan kebijakan scheduler.

## Checkpoint pengujian rental warga - 14 September 2026

- [x] **AM-BASE-04B** Order laboratorium citizen diikat ke `Taxpayer` sesi; detail dan billing owner-scoped; input hasil hanya untuk model `User` internal. Warga lain mendapat 404 dan tidak dapat mengubah hasil. Public QR verification tidak diubah.
- Validasi AM-BASE-04B: `CitizenDlhLabOwnershipTest` + `DlhLabServiceTest` lulus 7 test/34 assertion pada SQLite :memory:. PHP lint, Pint check dan diff check lulus. Mobile `dlhLabService.ts` memakai route yang sama dan tidak berubah; admin/petugas mempertahankan akses operasional. [Kontrak](docs/05_modul_aplikasi/AM_BASE_04B_CITIZEN_LAB_OWNERSHIP.md). Parent AM-BASE-04 tetap terbuka untuk billing, hasil, dokumen dan setoran layanan lain.

- [x] **AM-LAB-01A** Tambahkan lookup owner-scoped `/lab/orders/by-number/{orderNumber}` untuk pelacakan citizen, dengan envelope detail yang sama dan alias `/v1/dlh`. Route publik QR tetap terpisah. Commit `7c8d84e`; [kontrak](docs/05_modul_aplikasi/AM_LAB_01A_OWNER_TRACKING.md).
- Validasi AM-LAB-01A: tiga suite lab/gateway lulus 10 test/61 assertion; route alias dan consumer mobile diperiksa. Tidak ada migration atau perubahan admin/petugas. Push commit belum berhasil karena proxy lokal menolak koneksi.

- [x] **AM-PUPR-04C** Validasi titik WGS84 pada pengajuan citizen, simpan di metadata.koordinat dan kembalikan pada create/list/detail. Input opsional dan record lama tanpa titik tetap null. Tidak mengubah polygon_geom/skema/endpoint internal. [Kontrak dan bukti](docs/05_modul_aplikasi/AM_PUPR_04C_CITIZEN_PROJECT_POINT.md).
- Validasi akhir: `CitizenAssetRentalTest` lulus 35 test/186 assertion pada SQLite :memory:, Pint rule/test, PHP lint controller dan diff check lulus. Regresi Aset.location/MapPicker mobile lulus 12 test. Browser belum terverifikasi karena koneksi MySQL lokal ditolak; parent AM-PUPR-04/D04 tetap terbuka untuk batas area resmi dan integrasi dashboard.

- [x] **AM-BASE-04A** Create/list/detail rental citizen hanya menerima identitas Taxpayer dari sesi; akun internal dengan ID angka sama ditolak 403, guest 401, detail warga lain 404, query/body taxpayer_id tidak menentukan pemilik. Identitas diperiksa sebelum validasi pengajuan. Nama konsumen layanan tinja yang tidak diisi memakai nama Taxpayer terautentikasi.
- Validasi AM-BASE-04A: suite `CitizenAssetRentalTest` lulus 10 test/56 assertion pada SQLite :memory:, termasuk regresi tanggal. Test ID internal sebelum fix mendapat HTTP 200 dan gagal. PHP lint controller, Pint test file dan diff check lulus. Route/consumer tiga frontend diperiksa; endpoint internal admin/petugas terpisah dan tidak berubah. Parent AM-BASE-04 masih terbuka untuk layanan/aksi lain.

- [x] **AM-BASE-06G** Perbaiki tanggal kalender pada create/list/detail `/api/citizen/asset/rentals`: tanggal_mulai, tanggal_selesai dan tanggal_pengembalian berupa YYYY-MM-DD/null, sehingga mobile tidak mundur sehari akibat serialisasi UTC. Timestamp survei tetap timestamp; model bersama dan endpoint internal tidak berubah.
- Validasi: `php artisan test tests/Feature/CitizenAssetRentalTest.php --do-not-cache-result` pada SQLite :memory: lulus 5 test/39 assertion (WITA, UTC, America/Los_Angeles). Sebelum fix, 4 test gagal. `php -l app/Http/Controllers/CitizenAssetController.php` lulus. Consumer mobile: `npm.cmd run test -- src/pages/Aset.history.test.tsx src/pages/Aset.location.test.tsx --pool=threads --maxWorkers=1` lulus 17 test. Pencarian tiga frontend: endpoint citizen dipakai assetService mobile; admin/petugas memakai endpoint internal `/api/asset/rentals`. Konfirmasi visual browser masih tertunda karena halaman belum selesai memuat; tidak ada perubahan DB atau gateway.

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
- [x] **AFT-API-003** Buat service terpisah untuk Bank Sultra SNAP Direct Debit: `BankSultraAftDirectDebitService` membangun token/transaction headers, canonical string/signature, `X-EXTERNAL-ID`, amount `{ value, currency }`, payload payment/status/notify sesuai produk terpilih, parser `responseCode` 7 digit, dan audit masking rekening/header sensitif. Service memakai namespace/config `BANK_SULTRA` dan tidak memakai `BankSecurityCheck` generic HMAC atau flow BRIVA.
- [x] **AFT-API-004** Ganti `ExecuteAutoDebitAFT` agar tidak lagi memakai dummy payload atau success check `status === SUCCESS`; job sekarang memakai `BankSultraAftDirectDebitService`, meminta token B2B, mengirim payload/header SNAP resmi, hanya settle dari `responseCode` service payment resmi, menyimpan reference bank serta metadata audit masked, menangani `202 Request In Progress`, failure code, timeout retryable, dan replay idempotent dengan `X-EXTERNAL-ID` yang sama.
- [x] **AFT-API-005** Harden endpoint merchant AFT: authorization merchant eksplisit, taxpayer/merchant ownership, city dari data resmi, tarif PBJT dari backend/config pajak, rekening sumber/tujuan dari profil/kontrak SKDR, dan penolakan bila SKDR belum disetujui. Hapus asumsi `city_id=1`, taxpayer simulasi, rekening dummy, dan pajak hardcoded dari jalur production.
- [x] **AFT-API-006** Tambahkan endpoint/status history AFT yang aman untuk mobile: expose status warga (`pending`, `processing`, `success`, `failed`, `cancelled`) tanpa credential, raw bank payload, rekening penuh, signature, atau internal audit fields; sertakan `can_retry/can_cancel` hanya jika API benar-benar mendukungnya.
- [x] **AFT-API-007** Tambahkan regression test AFT Bank Sultra tanpa credential nyata: readiness gate default-off, merchant ownership, SKDR required, payload Direct Debit/BI-FAST sesuai kontrak terpilih, signature/header builder, responseCode parser, `202` pending, success, failure, timeout, idempotency, dan audit masking. Pastikan test BRIVA SNAP tetap berjalan tanpa perubahan kontrak.

## Digitalisasi Pendapatan Dishub & Milestone M32

- [x] **DISHUB-M32-001** Implementasikan skema Quadruple-Lock: top-up kuota deposit, transaksi tunai `prepaid-cash` dengan auto-deduct 70% RKUD : 30% Jukir, thermal print payload 58mm dengan watermark anti-pungli.
- [x] **DISHUB-M32-002** Implementasikan modul Proxy GT (Golongan I-IV) untuk estimasi tonase kapal rakyat tanpa intervensi yurisdiksi KSOP (Perda 1/2024 Pasal 91 & SOP 05 Dishub).
- [x] **DISHUB-M32-003** Implementasikan recurring auto-billing (e-SKRD) 15 Kios Kawasan Jembatan Batu (Rp 120.000 / tenant / bulan, Perda 1/2024 Pasal 84) secara idempoten.
- [x] **DISHUB-M32-004** Automated feature test suite `DishubQuadrupleLockAndJembatanBatuTest` lulus 100% (13 test cases, 85 assertions).

## Persiapan portal warga Dishub/PUPR/DLH — 14 September 2026

- [x] **AM-BASE-03B — implementasi dan validasi endpoint selesai.** CitizenBillStatus memetakan alias legacy pada response `/api/citizen/bills`, mempertahankan expired/disputed/unknown, dan hanya memperhitungkan klaim/request aktif/jatuh tempo pada status pending/overdue yang dikenal. can_pay dan label konsisten; status database, route shared admin/petugas, serta settlement tidak diubah. Parent BASE-03 tetap terbuka untuk integrasi DLH/gateway/rekap.
- Validasi lanjutan 14 September 2026 setelah Composer dipulihkan pengguna: `php artisan test tests/Unit/CitizenBillStatusTest.php tests/Feature/CitizenBillingReadTest.php --do-not-cache-result` **35 test / 183 assertion lulus**. Proses test memakai APP_ENV=testing, DB_CONNECTION=sqlite, DB_DATABASE=:memory:, DB_URL kosong; database UAT MySQL tidak dipakai oleh RefreshDatabase. Mencakup guest/internal-role, ownership/pagination, alias/status non-payable, pending claim dan readiness BRIVA. Ini uji endpoint lokal, bukan UAT bank/produksi. `git diff --check` lulus. Catatan kegagalan bootstrap di bawah merupakan bukti historis sebelum dependency pulih.
- Validasi aktual: `php tests/Standalone/citizen_bill_status.php` 26 kasus lulus (78 pemeriksaan status/can_pay/label) menggunakan class produksi langsung tanpa Laravel/DB; `php -l` enam file PHP terkait dan `git diff --check` lulus. PHPUnit Unit memakai matriks kasus yang sama. `php artisan test --filter=CitizenBillingReadTest` gagal bootstrap karena `vendor/autoload.php` tidak tersedia; test feature baru dan existing belum dieksekusi. Tidak mengklaim pengujian endpoint lulus.
- Consumer: pencarian source admin/petugas tidak menemukan pemakai `/api/citizen/bills`; mobile `citizenBillingService`, Tagihan dan Pembayaran sudah mendukung expired/disputed/unknown. `npm.cmd run test -- src/services/citizenBillingService.test.ts` di mobile 18/18 lulus. Tidak mengubah frontend; typecheck/build frontend tidak dijalankan ulang. Tidak ada migration/deploy/perubahan .env. Jalankan feature test terisolasi setelah dependency API tersedia sebelum menerima integrasi.

- [x] **AM-BASE-01** Petakan owner data lokal, route, ID, status, dokumen, dan consumer aset/bisnis. Hasil: [peta kontrak](docs/05_modul_aplikasi/AM_BASE_01_DASHBOARD_CONTRACT_MAP.md). Dashboard `retribusi-aset`/`retribusi-bisnis` direferensikan workspace tetapi folder belum tersedia; D01 tetap terbuka untuk integrasi. Tidak mengubah runtime/admin/petugas.
- Validasi checkpoint dokumentasi: inspeksi source empat repo, pengecekan target tautan/referensi source, dan `git diff --check`. Tidak ada pengujian bank, browser, atau produksi.
- [x] **AM-PAY-01** Inventaris bank per produk/environment: [hasil pemeriksaan](docs/10_payment_gateway_h2h/AM_PAY_01_BANK_PRODUCT_INVENTORY.md). Pisahkan BRIVA/QRIS BRI, VA/H2H/AFT Sultra, BNI simulator, Mandiri QRIS, BTN dan QRIS generik. D03 tetap terbuka untuk pengujian/aktivasi bank; tidak mengubah runtime.
- Validasi AM-PAY-01: 29 tautan lokal tersedia, 21 commit bukti merupakan ancestor HEAD API, pemeriksaan source/consumer empat repo dan `git diff --check`. Suite PHP tidak dijalankan karena `vendor/autoload.php` tidak tersedia; bukan hasil UAT/produksi.
- [x] **AM-BASE-02** Susun [kontrak citizen per layanan](docs/05_modul_aplikasi/AM_BASE_02_CITIZEN_SERVICE_CONTRACTS.md): katalog, quote, pengajuan, status, bill/payment/document reference, field wajib serta matriks kompatibilitas empat consumer. Endpoint usulan dipisahkan dari route existing; bukan implementasi atau aktivasi layanan. Validasi: 6 tautan lokal, inspeksi validator/envelope/consumer, `git diff --check`.
- [x] **AM-GOV-05** Susun [matriks mitigasi inquiry/reverse inquiry](docs/10_payment_gateway_h2h/AM_GOV_05_INQUIRY_MITIGATION_MATRIX.md): 22 skenario, perilaku API target, audit, retry/eskalasi, rancangan antrean dan dependensi implementasi. Spesifikasi selesai; PAY-09/10, SMP-02/05, LAB-04 tetap terbuka. Validasi: 3 tautan, 22 baris/5 kolom, source referensi, `git diff --check`.
- Backlog lengkap dan hasil commit/push: `../TODO_MPAD_API_MOBILE_2026-09-14.md` serta `../WORKBOARD.md`. Pengguna meminta beberapa task mandiri pada sesi ini; setiap task tetap memiliki checkpoint terpisah.

## Aturan Lanjutan

- Setelah PAY-002 selesai, perbarui `../WORKBOARD.md`, lalu lanjutkan hanya jika pengguna menginstruksikan.
- Endpoint atau perubahan schema baru wajib menambahkan/menyesuaikan test yang relevan.
