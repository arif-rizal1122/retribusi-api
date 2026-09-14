# AM-PAY-01: Inventaris integrasi bank per produk

Tanggal: 14 September 2026. Snapshot API sebelum checkpoint: `5515483`; mobile `f1363d9`. Status task: inventaris source dan bukti historis lokal selesai. Aktivasi bank, pengujian bank, dan perubahan runtime tidak termasuk checkpoint ini.

BRI, BNI, Sultra, dan Mandiri memang memiliki implementasi lokal, tetapi tingkat kesiapan tiap produk berbeda. BTN juga memiliki jalur tersendiri. Keberadaan driver, konfigurasi, HTTP 200, atau judul commit tidak cukup untuk menyatakan checkout warga sampai settlement produksi sudah berhasil.

## Cara membaca bukti

- **Implementasi:** ditemukan alur source yang disebutkan; belum berarti lulus test atau aktif di server.
- **Simulator/mock:** source sengaja memberi respons simulasi. Pada BNI, jalur sandbox dapat tetap menulis Bill/Payment; kata sandbox tidak berarti bebas perubahan database.
- **Catatan keberhasilan historis:** dokumen/judul commit lokal menyebut hasil tertentu. Ini bukti laporan pengembang pada waktu itu, bukan hasil pengujian ulang atau sertifikasi UAT bank.
- **Sandbox/UAT terbukti lengkap:** perlu environment, tanggal, skenario, request/reference yang disamarkan, respons bank, serta hasil ledger yang cocok. Bukti lengkap ini belum diperiksa pada checkpoint ini.
- **Produksi terverifikasi:** memerlukan bukti deployment/konfigurasi aktif dan transaksi/reconciliation nyata. Tidak dinyatakan untuk bank mana pun pada inventaris ini.

Tidak membaca atau menyalin `.env`, private key, arsip credential, dan raw log bank. Konfigurasi aktif/database produksi tidak diperiksa. Tidak menghubungi endpoint bank. Dokumen lama dipakai sebagai konteks; route dan perilaku sekarang ditentukan dari source terbaru.

## Matriks produk dan environment

| Bank / produk | Implementasi yang ada sekarang | Bukti lokal/historis | Sandbox/UAT dan produksi | Checkout warga sekarang |
|---|---|---|---|---|
| **BRI BRIVA Online/SNAP** | Token B2B, inquiry, payment, idempotency, PaymentRequest multi-tagihan, Bill/Payment/item settlement dan receipt; readiness gate | Journey mencatat token `2007300` pada 5 Agustus; commit `29d405b` mencatat inquiry `2002400`; source test lifecycle dan callback tersedia | Bukti parsial handshake/inquiry historis; tidak ditemukan bukti lengkap pembayaran bank/UAT dalam sumber yang diperiksa. Produksi belum diverifikasi | `bri_va` didukung citizen API, bergantung readiness; model biller memetakan VA lokal dan bank melakukan inquiry/payment |
| **BRI QRIS notification** | Route notify memvalidasi request lalu memberikan ACK; tidak memanggil settlement Bill/Payment | Dokumen dan route ada; tidak ada bukti settlement QRIS dari handler ini | ACK tidak membuktikan kas masuk/ledger lunas; UAT/produksi belum diverifikasi | Citizen payment-request tidak menerima `qris` |
| **Sultra VA SNAP** | Driver outbound `createVA`; callback terpisah dengan mapping bill/NOP; terdapat perhitungan verifikasi signature | Commit `202c6d7` mencatat create VA HTTP 200/`2002700`; `f5b946c` mencatat signature callback `valid:true` pada 27 Agustus | Environment keberhasilan tidak dapat ditetapkan hanya dari judul commit. Belum membuktikan checkout multi-tagihan atau produksi | `sultra_va` diterima tetapi membentuk VA lokal tanpa memanggil driver; callback belum menyelesaikan PaymentRequest/items |
| **Sultra H2H generik** | Inquiry/payment/reversal melalui gateway HMAC; driver mengubah Bill/Payment dan mendukung rekonsiliasi input transaksi | Test H2H generik dan DLH sampah/lab tersedia; commit `4b4c0ee` menambahkan test DLH | Pengujian request ke aplikasi/DB test bukan panggilan bank. Bukan bukti route VA SNAP Sultra | Jalur terpisah dari citizen create VA dan callback `/h2h/bank-sultra/callback` |
| **Sultra AFT/direct debit** | Readiness, kontrak merchant/SKDR, service request/response SNAP, job outbound, history aman | Commit `88c77e8`, `bdbd75f`, `2a24e3b`, `85ed08a`, `57d1f3e`; source test tersedia | Produk final/mandat, environment dan bukti debit bank tetap D03. VA berhasil tidak membuktikan AFT berhasil | Endpoint merchant tersedia; dua halaman AFT lama mobile redirect Home. Bukan syarat pembayaran layanan biasa |
| **BNI VA Billing** | Callback v1.1 dan settlement sandbox Bill/Payment; beberapa operasi driver menggunakan mock | `6c718a8` menyebut Client Simulator ASPI; `4a61ef2`/`3e2f7b1` menambahkan/memperbaiki settlement 1 September | Driver non-sandbox masih mengembalikan `501`; verifikasi kriptografis callback masih TODO. Belum siap dinyatakan produksi | Tidak ada `bni_va` dalam validasi citizen payment-request |
| **Mandiri QRIS** | Driver mencoba OAuth dan generate QR outbound; notify memiliki alur Bill/Payment | `872fa1b` hanya sinkronisasi konfigurasi contoh; source payload generate masih diberi komentar placeholder | Tidak ditemukan bukti transaksi bank/UAT dalam sumber yang diperiksa; format resmi dan settlement perlu dibuktikan | Tidak dihubungkan ke citizen payment-request; keberadaan nama bank di monitor bukan integrasi checkout |
| **BTN VA Collections** | Token/signature helper, outbound create VA, inquiry/payment callback | Riwayat 1 September memperbaiki format VA dan tambahan field; test memakai HTTP/service mock | Nominal create/inquiry masih `0.00`; notify driver hanya memetakan payload tanpa settlement DB. UAT/produksi belum terbukti | Tidak ada `btn_va` dalam citizen payment-request |
| **QRIS generik (bukan bukti bank tertentu)** | `QRISDriver::inquiry` membuat string QR simulasi; notify memiliki perubahan ledger | Komentar source eksplisit mock; tidak memanggil acquiring bank saat generate | Tidak boleh dianggap QRIS dinamis resmi atau bukti koneksi BRI/Sultra/Mandiri | Mobile menampilkan informasi QRIS belum tersedia; jangan mengaktifkan berdasarkan driver ini |

Transfer manual ke rekening yang diterbitkan API merupakan alur klaim/verifikasi tersendiri. Nama bank pada rekening, master channel, atau pilihan monitoring tidak membuktikan koneksi payment gateway bank tersebut.

## Route aktual dan sumber otoritas

Semua path berikut termasuk prefix `/api`; lihat [routes/api.php](../../routes/api.php).

| Jalur | Endpoint | Sumber utama |
|---|---|---|
| Checkout warga | `POST /api/citizen/payment-requests`, `GET /{paymentRequest}`, `POST /{paymentRequest}/refresh`, `POST /{paymentRequest}/cancel` di bawah prefix payment-requests | [CitizenPaymentRequestController](../../app/Http/Controllers/Api/V1/Payment/CitizenPaymentRequestController.php) |
| Opsi per tagihan | `GET /api/citizen/bills` | [BillController](../../app/Http/Controllers/BillController.php), `citizenPaymentOptions` |
| BRI token | `POST /api/snap/v1.0/access-token/b2b`, alias v1.1 | [SnapBIController](../../app/Http/Controllers/Api/V1/Payment/SnapBIController.php) |
| BRI BRIVA | `POST /api/snap/v1.0/transfer-va/inquiry`, `/payment` | [SnapBrivaService](../../app/Services/Payment/Snap/SnapBrivaService.php) |
| QRIS notification SNAP | `POST /api/snap/v1.1/qr/qr-mpm-notify` | `SnapBIController::qrisNotify`: validasi + ACK |
| Sultra VA callback | `POST /api/h2h/bank-sultra/callback` | [BpdWebhookController](../../app/Http/Controllers/Api/H2h/BpdWebhookController.php) |
| H2H generik | `POST /api/v1/bank/inquiry`, `/payment`, `/reversal` | [BankH2HController](../../app/Http/Controllers/Api/V1/Bank/BankH2HController.php), [BankSecurityCheck](../../app/Http/Middleware/BankSecurityCheck.php) |
| BNI callback | `POST /api/snap/v1.1/transfer-va/inquiry`, `/payment` | [BniSnapController](../../app/Http/Controllers/Api/V1/Payment/BniSnapController.php) |
| BTN callback | `POST /api/snap/v1/transfer-va/inquiry`, `/payment` | [BtnSnapController](../../app/Http/Controllers/Api/V1/Payment/BtnSnapController.php) |
| Merchant Sultra AFT | `POST /api/citizen/merchant/submit-omzet`; GET `aft-availability`, `aft-history` di prefix merchant | [KalkulatorBisnisController](../../app/Http/Controllers/Api/Merchant/KalkulatorBisnisController.php), [ExecuteAutoDebitAFT](../../app/Jobs/ExecuteAutoDebitAFT.php) |

`PaymentManager` mendaftarkan driver `sultra`, `qris`, `mandiri`, `btn`, `bni`. File `BankBRIDriver` legacy juga ada, tetapi BRIVA SNAP yang dibahas di sini masuk melalui `SnapBIController`/`SnapBrivaService`. Jangan menilai kemampuan BRIVA hanya dari method `501` pada driver legacy atau dari `HasMockResponse`.

H2H generik memeriksa HMAC-SHA256 terhadap bill number/timestamp/amount memakai driver default konfigurasi. Jalur ini berbeda dari signature SNAP dan callback khusus tiap bank. Tes generik tidak otomatis menguji kontrak atau keamanan callback khusus.

## Temuan yang menentukan pekerjaan lanjutan

### BRIVA dan isolasi antarproduk

[SnapBrivaReadiness](../../app/Services/Payment/Snap/SnapBrivaReadiness.php) memeriksa enabled flag, identitas partner/client, material signature, format VA, dan whitelist di luar local/testing. Keberadaan gate tidak membuktikan nilai aktif produksi telah diperiksa. BRI mendukung pemetaan VA lokal dalam model biller; tidak adanya outbound create VA pada jalur ini bukan otomatis bug.

`SnapBrivaService::resolveBill` mencari PaymentRequest dari VA/external ID tanpa filter `payment_channel` pada query tersebut. Setelah Sultra ditambahkan, isolasi channel harus masuk pengujian AM-PAY-05; jangan menganggap semua PaymentRequest yang cocok pasti BRIVA. `refresh` citizen saat ini hanya membaca status lokal dan memperbarui expiry, bukan status inquiry ke bank. Commit penambahan outbound BRI `2aadeb3` sudah diikuti penghapusan `1394b62`; jangan menawarkan reverse inquiry yang source-nya telah dihapus.

### Sultra: driver berhasil belum sama dengan checkout tersambung

[BankSultraDriver](../../app/Services/Payment/Drivers/BankSultraDriver.php) memiliki `createVA` yang mengirim request bank dan memeriksa `responseCode=2002700`. Driver memakai customer NOP dan external ID terkait bill; beberapa konfigurasi VA mengambil namespace `bank_sultra_aft`. Source outbound juga memakai `withoutVerifying()`; catatan koneksi berhasil bukan bukti validasi sertifikat TLS.

Sebaliknya, citizen `sultra_va` membuat nomor dari partner ID dan **PaymentRequest ID**, external ID acak, serta total snapshot multi-tagihan. Controller tidak memanggil `createVA`. `BillController` memberi `sultra_va.available = canPay`, tanpa gate kesiapan setara BRIVA. Jadi nomor VA muncul di mobile tidak membuktikan registrasi/pemetaan bank selesai.

Callback khusus Sultra memetakan **Bill ID** dari external ID atau mencari bill berdasarkan NOP/nominal. Nominal dibandingkan ke `bill.amount`, berbeda dari total snapshot multi-tagihan/biaya/denda. Cabang VA baru mencatat pembayaran jika `snap.bank_sultra_aft.enabled` true dan bill cocok; tidak memperbarui PaymentRequest/items. ACK `2003300` masih dapat dikirim ketika tidak ada settlement. Hasil `signatureValid` dicatat tetapi tidak menjadi syarat penolakan sebelum pemrosesan cabang VA. Hal ini perlu ditangani dalam scope callback, bukan dengan sekadar menyalakan AFT.

AM-PAY-02/04/05/11 perlu menyelesaikan readiness, identitas transaksi, nominal, signature enforcement, isolasi AFT dan settlement atomik. Registrasi/format callback yang benar tetap memerlukan kontrak D03; jangan mengubah konsumen dashboard berdasarkan asumsi.

### BNI, Mandiri, BTN, dan QRIS

- [BankBNIDriver](../../app/Services/Payment/Drivers/BankBNIDriver.php): `isSandbox()` memakai `BankConfig.is_sandbox` dan default true melalui [HasMockResponse](../../app/Services/Payment/Traits/HasMockResponse.php), bukan otomatis mengikuti `APP_ENV`. `notify` sandbox benar-benar dapat settle bill. Callback hanya memeriksa keberadaan header/timestamp; signature verification masih TODO. Mock dan endpoint ini tidak boleh dipakai sebagai bukti production-ready (AM-PAY-07).
- [BankMandiriDriver](../../app/Services/Payment/Drivers/BankMandiriDriver.php): ada OAuth `/v1/oauth2/token` dan generate `/v1.0/qr/qr-mpm-generate`, tetapi source sendiri menyebut payload placeholder. Belum ditemukan wiring Mandiri ke citizen PaymentRequest; perlu kontrak provider/create/status/notify sebelum AM-PAY-08 diaktifkan.
- [BankBTNDriver](../../app/Services/Payment/Drivers/BankBTNDriver.php): create VA memakai nominal `0.00`; inquiry callback juga memberi `0.00`. `notify` mengembalikan hasil mapping dan controller mengirim ACK, tanpa penyimpanan Bill/Payment pada jalur yang diperiksa. Test bernama `test_btn_payment_webhook_updates_status` memock driver dan hanya memeriksa pemanggilan/ACK, sehingga nama test bukan bukti settlement.
- [QRISDriver](../../app/Services/Payment/Drivers/QRISDriver.php) membuat QR simulasi; `SnapBIController::qrisNotify` hanya ACK. Keduanya bukan satu alur generate-to-settle resmi. Mobile `Pembayaran.tsx` sudah membatasi gateway submit ke `bri_va`/`sultra_va` dan memberi pesan QRIS belum tersedia; tipe `qris` di service tidak mengubah validasi API.

## Register bukti historis yang aman

| Referensi | Tanggal | Yang didukung | Batas bukti |
|---|---|---|---|
| [BRI_SNAP_JOURNEY](BRI/BRI_SNAP_JOURNEY.md), `83ae04a` | 5 Agustus | Catatan token BRI `2007300` | Dokumen masih menandai inquiry/payment menunggu; perlu dibandingkan commit sesudahnya |
| `29d405b` | 5 Agustus | Judul commit mencatat inquiry BRI `2002400` | Lebih baru dari status journey; tidak membuktikan payment bank, UAT final, atau produksi |
| [SNAP_RESPONSE_VERIFICATION](BRI/SNAP_RESPONSE_VERIFICATION.md) | Pembaruan Juli | Pemetaan response/schema SIT dan daftar gap saat itu | Bukan laporan uji bank terkini; keputusan signature/route harus dicek dengan source lebih baru |
| `202c6d7`, `f5b946c` | 27 Agustus | Laporan create VA Sultra dan signature callback berhasil | Environment dan settlement lengkap tidak teridentifikasi dari metadata Git |
| `cc97333`, `1e570da` | 7 September | Perbaikan mapping external ID/amount callback Sultra | Perbaikan kode tidak membuktikan checkout warga multi-tagihan lulus |
| `9be0cad` | 12 September | Citizen `sultra_va` ditambahkan | Hanya membuktikan perubahan implementasi; belum terhubung driver outbound |
| `6c718a8`, `4a61ef2`, `3e2f7b1` | 1 September | BNI simulator callback dan settlement sandbox | Bukan implementasi driver produksi/signature lengkap |
| `872fa1b` | 11 September | Sinkronisasi contoh konfigurasi Mandiri | Bukan token/QR/payment berhasil |
| `c58fd00`, `4929454` | 1 September | Perbaikan format dan field create VA BTN | Tidak membuktikan nominal dan settlement; [PROGRESS BTN](BTN/PROGRESS.md) masih berupa kronologi administratif Juli |
| [Kontrak AFT Sultra](BPD/BANK_SULTRA_AFT_ASPI_CONTRACT.md), commit implementasi Agustus pada matriks | 3 Agustus | Pemetaan direct debit H2H vs BI-FAST, readiness dan implementasi | Produk/mandat dan validasi bank harus dikonfirmasi tersendiri |

Register memakai metadata Git dan bagian dokumen yang relevan; tidak menyalin payload, rekening, credential atau signature. Pencarian dilakukan pada histori Git lokal; keadaan remote terbaru belum terverifikasi sampai fetch berhasil.

## Inventaris pengujian dan validasi checkpoint

| Sumber test | Cakupan yang ditemukan dari source | Tidak dibuktikan oleh test tersebut |
|---|---|---|
| [CitizenPaymentRequestTest](../../tests/Feature/Payment/CitizenPaymentRequestTest.php) | Lifecycle/reuse/ownership/manual conflict BRIVA; pembuatan request Sultra | Registrasi VA Sultra dan callback bank nyata |
| [SNAP feature tests](../../tests/Feature/Payment/Snap) | Token, inquiry, payment, amount mismatch, expired, signature, duplicate, multi-bill dan logging | Konektivitas/UAT BRI saat ini |
| [DlhBankSultraPaymentIntegrationTest](../../tests/Feature/DlhBankSultraPaymentIntegrationTest.php), [BankH2HTest](../../tests/Feature/Api/V1/Bank/BankH2HTest.php) | Request H2H ke aplikasi, Bill/Payment, duplikasi; DLH sampah/lab | Callback VA khusus dan multi-item PaymentRequest Sultra |
| [Unit SNAP/AFT](../../tests/Unit/Payment/Snap) | Readiness, header/signature, parser dan masking | Pendebetan rekening merchant nyata |
| [BankBTNIntegrationTest](../../tests/Feature/BankBTNIntegrationTest.php) | HTTP/service mock untuk create dan ACK callback | Perubahan ledger; assertion format VA masih mengharapkan padding spasi, sementara source kini trim (perbedaan statis, belum diuji) |

Validasi yang dilakukan: inspeksi source empat repo secara read-only, metadata Git, route/controller/driver/test dan dokumen pembayaran lokal; pemeriksaan tautan/referensi serta `git diff --check`. **Suite PHP tidak dijalankan:** `Test-Path vendor/autoload.php` menghasilkan false. PHPUnit mengatur SQLite memory, tetapi dependency belum tersedia; tidak memasang dependency atau menjalankan migration untuk checkpoint dokumentasi ini. Tidak ada klaim test lulus dari inspeksi source.

Consumer yang diperiksa: mobile `paymentRequestService.ts` dan `Pembayaran.tsx`; admin `H2HLogs.tsx`/`H2HMonitor.tsx`; petugas service payment/QRIS dan kasir parkir. Tidak ada perubahan route/response/frontend pada checkpoint ini. Peta bank ini tidak memigrasikan transaksi petugas ke citizen endpoint.

## Kriteria penutupan dan kelanjutan

AM-PAY-01 selesai sebagai inventaris lokal per produk/environment, termasuk simulator, klaim keberhasilan historis dan bukti yang masih kurang. **D03 tetap terbuka untuk pengujian dan aktivasi bank; bukan blocker penulisan inventaris ini.**

Pekerjaan mandiri berikutnya adalah AM-BASE-02 (kontrak layanan citizen), sesuai urutan backlog. Untuk pembayaran, gunakan temuan ini sebagai input AM-PAY-02/05 dan spesifikasi AM-PAY-04/09/11. BNI/QRIS final, registrasi Sultra dan reversal/refund tetap mengikuti dependensinya; jangan menandai task-task itu selesai dari inventaris ini.

Paket bukti bank yang kelak melengkapi D03 minimal menyebut bank+produk, environment, versi kontrak, tanggal/skenario, reference tersamarkan, hasil create/inquiry/payment/status/duplicate/timeout, perubahan semua item dan receipt, serta hasil rekonsiliasi. HTTP 200/ACK saja tidak mencukupi.
