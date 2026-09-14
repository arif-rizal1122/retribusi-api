# AM-GOV-05 — Matriks mitigasi inquiry dan reverse inquiry

14 September 2026. Status: rancangan mitigasi selesai; perilaku target di bawah belum seluruhnya diimplementasikan. Acuan: [inventaris bank](AM_PAY_01_BANK_PRODUCT_INVENTORY.md), [kontrak citizen](../05_modul_aplikasi/AM_BASE_02_CITIZEN_SERVICE_CONTRACTS.md), [routes](../../routes/api.php). Matriks ini menjadi masukan AM-PAY-09/10, AM-SMP-02/05, AM-LAB-04; tidak mengklaim task implementasi tersebut telah selesai.

## Istilah dan batas sumber

- **Inquiry objek/tagihan:** mencari objek dan kewajiban yang boleh dilihat principal. Keberhasilan menemukan objek bukan izin mengambil data pemilik, mengubah ownership, atau menagih.
- **Inquiry VA dari bank:** bank meminta tagihan pada biller. Contohnya BRIVA `/api/snap/v1.0/transfer-va/inquiry`; ini belum pembayaran.
- **Status inquiry keluar/reverse inquiry:** istilah kerja untuk M-PAD memeriksa status transaksi ke bank bila produk menyediakan endpoint. Path, service code, reference, status final dan aturan retry harus mengikuti kontrak bank. Bukan lookup spasial BPN/Bhumi dan bukan pembalikan transaksi.
- **Reversal/refund:** koreksi transaksi/dana dengan referensi bank dan kewenangan yang ditetapkan. Tombol cancel lokal atau status `disputed` tidak otomatis membatalkan dana pada bank.
- **ACK:** bank menerima response callback. ACK tidak boleh disajikan ke warga sebagai bukti settlement; ledger/receipt hanya berubah setelah validasi dan transaksi database selesai.

Pada source saat ini, refresh citizen hanya membaca status lokal; outbound status BRI pernah ditambahkan lalu dihapus. BRI QRIS notify hanya ACK; callback Sultra VA dan H2H generik merupakan jalur berbeda. BNI non-sandbox masih 501 dan QRIS generik masih simulasi. Rancangan ini tidak menyediakan endpoint bank tebakan sebagai pengganti keterbatasan tersebut.

## Kontrak audit target

Setiap kasus memakai `event_id`, `correlation_id`, waktu server, actor type/ID internal, scope OPD/assignment bila relevan, operasi, resource opaque ID, bank+produk+environment, reason code, hasil/transisi sebelum-sesudah, dan referensi event sebelumnya. Untuk pembayaran tambahkan payment request/item/bill reference, currency/amount snapshot, external ID, bank reference tersamarkan, attempt count dan fingerprint payload yang telah dikanonisasi. Data audit dibatasi akses/retensi sesuai kebijakan D05.

Jangan menyimpan credential, bearer token, key, header signature penuh, nomor rekening penuh, NIK atau sampel/hasil lab lengkap dalam log umum. Bukti bank yang perlu investigasi disimpan di tempat dengan akses khusus, bukan dicetak pada error mobile. Bukti akses ditolak tetap tercatat tanpa mengungkap apakah objek milik orang lain tersedia.

## Matriks kasus dan perilaku target

Kolom API adalah rancangan perilaku untuk implementasi berikutnya. Status sekarang dan task penutup disebutkan terpisah agar tidak disamakan dengan proteksi yang telah berjalan.

| ID / kasus | Perilaku API dan tampilan warga yang dituju | Bukti audit khusus | Retry / eskalasi | Implementasi/dependensi |
|---|---|---|---|---|
| INQ-01 ID kosong/salah format/namespace | 422 pada format; ID tidak ditemukan/di luar scope memakai 404 generik. Jangan fallback ke pencarian NIK/nama | Tipe ID, reason code, fingerprint input; bukan identitas mentah | Warga memperbaiki input; tanpa retry otomatis | SMP-01/02; namespace resmi D07 |
| INQ-02 Enumeration/tebak ID | Owner-scoped detail; inquiry awal minimum/masked; rate limit per principal dan sumber, 429 dengan Retry-After; jangan memberi beda pesan owner lain vs tidak ada | Jumlah percobaan, scope, reason code tanpa daftar objek korban | Backoff mengikuti Retry-After; investigasi penyalahgunaan oleh operator berwenang | BASE-04/SMP-02; policy akses D05 |
| INQ-03 Satu ID cocok beberapa objek/data ganda | Jangan pilih record pertama; `needs_review`, detail pribadi tidak dilepas; kasus review mapping | Candidate IDs internal, sumber/versi mapping, alasan ambigu | Tidak retry pembayaran; petugas data mengesahkan mapping | SMP-01/10, D07 |
| INQ-04 Satu orang beberapa bangunan | Daftar hanya objek tertaut yang sah; bill tetap per objek/periode. Akun sama bukan alasan menggabungkan ownership semua hasil inquiry | Relation ID, periode berlaku, actor linking | Claim objek melalui verifikasi; tidak auto-link karena tahu ID | SMP-01/02; D05/D07 |
| INQ-05 Penghuni pindah/kosong/data kontak berubah | Catat permohonan perubahan dan tanggal efektif; status billing historis dipertahankan sampai keputusan sah. Kontak baru tidak mengambil alih akun/utang otomatis | Relation sebelum/sesudah, effective date, kasus/bukti/decision reference | Eskalasi penanggung jawab; reminder mengikuti keputusan, bukan berhenti/menagih ulang dari tebakan | SMP-05/GOV-02; D04/D05 |
| INQ-06 Tarif/nominal/rit/tronton berubah | Quote baru memiliki version/snapshot; 409 pada accept versi usang. Bill terbayar immutable; selisih lewat tagihan/koreksi sah | Quote versions, sumber tarif, rincian delta, approval reference | Muat penawaran baru dan minta keputusan warga; tanpa pembayaran ulang otomatis | BASE-05/PUPR-07/PAY-10; D02/D05 |
| INQ-07 Double-submit pengajuan/billing | Key idempotensi dicakup actor+operasi; request identik mendapat resource yang sama, payload berbeda 409. Unique constraint+lock untuk bill objek/periode | Key fingerprint, resource asal, duplicate event, transaction outcome | Retry key yang sama setelah timeout; jangan buat key baru sampai status request lama jelas | BASE-02/SMP-04/LAB-05 |
| PAY-01 Timeout create VA/payment outbound | Tandai hasil `unknown`/sedang direkonsiliasi secara internal; warga melihat diproses. Jangan menyatakan gagal atau mengizinkan transaksi pengganti bebas | Attempt ID, send time, timeout phase, external ID, amount snapshot | Status inquiry dengan referensi semula jika didukung; tanpa endpoint, antrean review/bukti bank | PAY-04/09; D03 |
| PAY-02 Bank mengembalikan pending/202 | Simpan pending/in-progress sesuai parser produk; belum terbit receipt lunas | responseCode aman, bank reference, next_check_at | Retry inquiry terjadwal sesuai kontrak; bukan mengulang debit dengan ID baru | PAY-09/AFT; D03 |
| PAY-03 Refresh UI/reload/login | GET/read pemilik memulihkan request yang sama; polling bukan create. Expiry lokal tidak membuktikan bank gagal | Request ID, last confirmed state/time; hindari log setiap poll berisi data pribadi | Backoff polling UI; refresh status bank hanya jika kontrak mendukung | PAY-06/09 |
| PAY-04 Callback berulang/out-of-order | Validasi autentikasi, referensi/amount/channel; lock aggregate dan semua bill/items; duplicate identik mengembalikan hasil tersimpan. Event lama tidak menurunkan paid ke pending | Bank event/external ID, fingerprint, duplicate_of, transisi | Retry callback idempoten; payload berbeda dengan ID sama masuk konflik/review | PAY-05/09; sebagian BRIVA ada, audit lintas channel belum selesai |
| PAY-05 Callback signature invalid/missing/stale | Tolak sebelum settlement, tanpa ACK sukses palsu; kode mengikuti kontrak bank. Data bisnis tidak berubah | Validasi yang gagal, timestamp delta, partner key version tanpa key/signature mentah | Tidak retry menggunakan payload tak sah; bank/operator meninjau key/time/path | PAY-04/05/07; Sultra saat ini hanya log hasil signature, BNI verifikasi masih TODO |
| PAY-06 Nominal/currency/fee/denda mismatch | Bandingkan snapshot transaksi resmi seluruh item; jangan melunasi sebagian atau bill senilai terdekat | Expected/received amount+currency, matched reference, reason code | Rekonsiliasi manual jika bank sudah mendebit; tidak auto-refund atau memilih bill lain | PAY-04/05/10; D03/D05 |
| PAY-07 Channel/ref salah | Cocokkan provider+produk+merchant/tujuan+environment+reference; VA Sultra tidak diselesaikan callback BRI hanya karena nomor cocok | Source channel, expected channel, mapping version | Tahan settlement, investigasi referensi; jangan reroute diam-diam | PAY-05; resolver BRIVA perlu isolasi channel |
| PAY-08 Callback setelah cancel/expiry | Pisahkan permintaan cancel lokal, expiry dan hasil final bank. Jangan membuang bukti dana atau membuka bill untuk pembayaran kedua ketika status ambigu | Cancellation request/time, callback time, bank finality dan existing settlement | Status inquiry/review; penyelesaian/refund sesuai otoritas produk | PAY-03/05/09/10; D03/D05 |
| PAY-09 Bank paid, aplikasi pending/failed | Simpan kasus mismatch; settle hanya setelah bukti final terautentikasi dan amount/reference cocok, secara atomik dan idempoten | Bank evidence reference, reconciliation run, decision actor, settlement event | Inquiry/reconciliation ulang sesuai jadwal; jika belum dapat dipastikan tampil diproses | PAY-09; D03 |
| PAY-10 Aplikasi paid, bank pending/not found | Jangan membatalkan receipt/utang otomatis. Pertahankan ledger append-only dan buka kasus rekonsiliasi | Receipt/payment ID, bank response time/finality, mismatch history | Review sumber pembayaran dan accounting; koreksi oleh pihak berwenang | PAY-09/10; D03/D05 |
| PAY-11 Callback concurrent/manual cash claim/VA baru | Lock bill dan request; hanya satu settlement; perpindahan channel tidak menghilangkan request bank in-flight. Setoran kasir dibedakan dari dana RKUD | Semua attempt/channel, payment/claim/setoran IDs, lock outcome | Pihak berwenang menyelesaikan pembayaran rangkap; jangan menyembunyikan penerimaan kedua | PAY-05/SMP-07; UI petugas tidak diubah sepihak |
| PAY-12 Reversal/refund/dispute | Warga membuat kasus; tidak ada reversal bebas. Simpan transisi koreksi dan referensi bank terpisah dari transaksi asli | Alasan, case ID, approval, bank correction ref, jurnal koreksi | Ikuti lifecycle bank dan kewenangan; keberatan saja tidak memicu refund | PAY-10/GOV-02; D03/D05 |
| PAY-13 Layanan selesai tetapi bill belum paid | Workflow tetap completed bila benar; billing tetap unpaid/pending sesuai ledger. Tidak menghasilkan receipt dari status layanan | Workflow decision vs billing confirmation | Eskalasi piutang atau cek payment request; bukan auto-settle | BASE-03/PUPR-11/LAB-05 |
| LAB-01 Hasil lab direvisi | Versikan hasil/dokumen; tandai versi sebelumnya superseded. Pemilik mendapat versi final sah; dokumen pending TTE tidak disebut terverifikasi | Sample/order ID, result version, correction reason, approver, document digest/version | Lab menyetujui revisi; retry unduh memakai ID versi, bukan URL publik identitas warga | LAB-04/07; D01/D06 |
| LAB-02 LHU/SIPAB tidak ada, link kedaluwarsa, layanan file gagal | `can_download=false` bila belum terbit; error unduh tidak mengubah hasil/payment. URL publik hanya verifikasi minimum setelah kontrak privasi dipenuhi | Document ID/version, publish state, download decision | Retry owner-scoped untuk berkas tersedia; hubungi penerbit bila hilang/invalid | BASE-04/PUPR-10/LAB-07; D06 |

## Rancangan retry dan antrean rekonsiliasi

Usulan record internal: `operation`, resource ID, bank+produk+environment, original external reference, idempotency fingerprint, `status` (`queued`, `in_progress`, `waiting_provider`, `manual_review`, `resolved`), `attempt_count`, `next_attempt_at`, `last_error_code`, `resolved_by`, `resolution_evidence_id`. Record dan histori event tidak dimunculkan penuh pada response citizen.

1. Klaim job dengan lock/lease; hanya satu worker aktif untuk transaksi yang sama. Recovery worker mempertahankan original external ID.
2. Retry otomatis hanya untuk operasi read/status yang memang didukung atau resend idempoten yang dijamin kontrak produk. Timeout saat mengirim debit tidak boleh memicu debit baru dengan referensi lain.
3. Jadwal backoff, jitter, batas percobaan/deadline dan rate limit disimpan per produk sesuai kontrak. Tidak menetapkan angka retry bank dari asumsi. Hormati Retry-After bila disediakan.
4. Jika belum ada endpoint status (termasuk refresh lokal BRI), masukkan `manual_review`/menunggu bukti bank; jangan membuat hasil inquiry sintetis.
5. Resolver memakai bukti final, amount/currency/tujuan/ref yang cocok dan versi transaksi terkini. Event hasil bank dicatat sebelum/bersama settlement atomik. Duplicate hasil resolver tidak menerbitkan Payment/receipt kedua.
6. Hentikan retry otomatis saat terminal yang sah; kasus mismatch/koreksi tetap dapat ditinjau pihak berwenang. Error teknis tidak membuka data pribadi kepada warga.

## Penerimaan implementasi

Untuk setiap baris matriks, test harus membuktikan response/izin aksi, perubahan DB atau tidak berubah, event audit aman, serta retry/escalation yang benar. Skenario concurrency memakai dua attempt berbeda pada resource sama; test duplicate memakai ID/payload sama dan ID sama dengan payload berbeda. Akun/OPD berbeda harus diuji. Token/signature/rekening mentah tidak boleh ada pada output audit umum.

Uji bank terpisah memerlukan bukti environment dan penerimaan kontrak D03. Uji privasi/ownership memakai data sintetis pada database terisolasi. Tidak ada test/perubahan runtime pada checkpoint matriks ini; validasi hanya referensi dokumen/source, kelengkapan 22 skenario (5 kolom), dan `git diff --check`.

## Dependensi dan otoritas

Tim API/mobile dapat menyiapkan kontrak, test terisolasi, adapter status, masking dan antrean setelah spesifikasi tersedia. Bank menentukan finality/lifecycle/status/reversal produknya (D03); OPD/Bapenda menetapkan hak data, penanggung jawab utang, keberatan dan koreksi keuangan (D05); dashboard/lab menyediakan keputusan serta versi hasil (D01/D06/D07). Tidak membuat kebijakan sanksi, tarif, mutasi ownership, atau pemotongan rekening baru dari matriks ini.

AM-GOV-05 selesai sebagai matriks perilaku target, bukti audit dan retry/eskalasi. Dependensi implementasi di kolom terakhir tetap terbuka dan dilacak pada task asal. Task ini tidak memerlukan keputusan pihak ketiga untuk menuliskan risiko dan kontrak yang dibutuhkan.
