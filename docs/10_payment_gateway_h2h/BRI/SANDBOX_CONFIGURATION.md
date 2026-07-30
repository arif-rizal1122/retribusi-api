# Konfigurasi Sandbox BRIVA SNAP

Dokumen ini adalah panduan konfigurasi runtime `retribusi-api` untuk BRIVA Online model inbound: BRI memanggil endpoint token, inquiry, dan payment milik M-PAD. Nilai credential, key, IP, dan identitas resmi harus diberikan atau dikonfirmasi melalui onboarding BRI. Jangan menyalin nilai rahasia ke dokumen tracked, issue, chat, atau log validasi.

Panduan ini tidak menyatakan sistem sudah lulus sandbox/UAT. Kesesuaian response SNAP terhadap spesifikasi bank merupakan task terpisah.

## Endpoint yang Dipublikasikan

Laravel memberi prefix `/api` pada `routes/api.php`. Endpoint runtime saat ini adalah:

| Fungsi | Method dan path |
| --- | --- |
| Access token B2B BRIVA | `POST /api/snap/v1.1/access-token/b2b` |
| Inquiry BRIVA | `POST /api/snap/v1.0/transfer-va/inquiry` |
| Callback pembayaran BRIVA | `POST /api/snap/v1.0/transfer-va/payment` |

Route token `/api/snap/v1.0/access-token/b2b` tetap tersedia sebagai compatibility alias, tetapi matriks akses sandbox lokal mencantumkan versi `v1.1`. Semua URL sandbox harus memakai domain publik HTTPS. Konfirmasi BRI pada 2026-07-30 menyatakan path/subdomain sebelum `/snap` dibebaskan; bagian setelah `/snap` tetap harus mengikuti template. Dengan konfirmasi ini, prefix Laravel `/api` dapat dipakai selama endpoint akhir tetap `/api/snap/v1.1/access-token/b2b`, `/api/snap/v1.0/transfer-va/inquiry`, dan `/api/snap/v1.0/transfer-va/payment`.

## Environment Variable Runtime

Sumber kebenaran nama variable adalah `config/snap.php`. Isi nilai sebenarnya melalui secret manager atau `.env` server yang tidak dilacak Git.

### Wajib untuk request inbound BRI

| Variable | Isi | Catatan |
| --- | --- | --- |
| `BRI_SNAP_ENABLED` | `true` setelah konfigurasi lengkap | Default `false`; selama `false`, API tidak mengiklankan BRIVA ke mobile dan create payment request BRIVA ditolak. |
| `BRI_SNAP_PARTNER_ID` | Partner ID resmi | Dipakai untuk mencocokkan header transaksi `X-PARTNER-ID`. |
| `BRI_SNAP_CLIENT_KEY` | Client key resmi | Dipakai untuk mencocokkan `X-CLIENT-KEY` saat meminta token. |
| `BRI_SNAP_SIGNATURE_ALGORITHM` | `rsa_sha256` untuk sandbox BRI saat ini | Konfirmasi BRI 2026-07-30 menyatakan `X-SIGNATURE` memakai RSA-SHA256. HMAC tetap tersedia di kode hanya untuk compatibility bila partner lain memerlukannya. |
| `BRI_SNAP_SIGNATURE_SECRET` | Shared secret BRI untuk HMAC | Jangan diisi public key. Simpan hanya di secret manager atau `.env` server yang tidak dilacak Git. |
| `BRI_SNAP_PUBLIC_KEY_PATH` | Path public key BRI untuk RSA | Dipakai saat `BRI_SNAP_SIGNATURE_ALGORITHM=rsa_sha256`; file key jangan berada dalam Git. |
| `BRI_SNAP_PARTNER_SERVICE_ID` | `partnerServiceId` resmi 8 digit bila diberikan BRI | Jika BRI hanya memberikan prefix VA seperti `19960`, kosongkan nilai ini dan pakai `BRI_SNAP_VA_PREFIX`. |
| `BRI_SNAP_CUSTOMER_NO_LENGTH` | Panjang `customerNo` | Default `20`; sesuaikan jika provisioning BRI menetapkan panjang berbeda. |
| `BRI_SNAP_VA_PREFIX` | Prefix resmi BRI atau prefix dummy local smoke | Prefix `19960` dikonfirmasi BRI sebagai environment sandbox dan hanya valid pada channel sandbox. Prefix dummy `777` hanya boleh dipakai local smoke. |
| `BRI_SNAP_VA_LENGTH` | Panjang VA final | Default `28` untuk format `partnerServiceId + customerNo`; bila memakai prefix `19960`, set sesuai total panjang yang disetujui sandbox. Smoke lokal 2026-07-29 memakai 18 digit. |
| `BRI_SNAP_PAYMENT_REQUEST_EXPIRY_MINUTES` | Masa aktif request | Default `1440`; harus disepakati dengan aturan produk bank. |

### Kontrol keamanan bersama

| Variable | Nilai yang disarankan | Perilaku runtime |
| --- | --- | --- |
| `SNAP_ALLOWED_IPS` | Daftar IP BRI dipisahkan koma | Pencocokan exact IP. Nilai kosong mematikan whitelist dan hanya layak untuk local development sebelum IP resmi tersedia. |
| `SNAP_TIMESTAMP_TOLERANCE_SECONDS` | `300` | Menolak timestamp di luar toleransi. |
| `SNAP_REQUIRE_BEARER_TOKEN` | `true` | Inquiry dan payment wajib memakai token yang diterbitkan endpoint B2B. |
| `SNAP_TOKEN_TTL_SECONDS` | `900` | TTL token pada Laravel cache. |
| `SNAP_IDEMPOTENCY_TTL_MINUTES` | `1440` | Retensi idempotency `X-EXTERNAL-ID`. |
| `SNAP_MODE` | `sandbox` | Penanda environment; saat ini belum mengubah base URL atau alur callback. |

Token B2B disimpan melalui Laravel Cache. Sandbox bersama atau deployment multi-instance harus memakai cache bersama/persisten, misalnya Redis atau database yang sama. Cache lokal per instance dapat membuat token valid pada satu instance tetapi ditolak pada instance lain.

### Variable yang sudah tersedia tetapi belum dipakai alur inbound

- `BRI_SNAP_CLIENT_SECRET` tersedia di config partner, tetapi token endpoint saat ini mengautentikasi client key dan signature request, bukan client secret secara langsung.
- `SNAP_CALLBACK_BASE_URL` tersedia di config, tetapi endpoint inbound berasal dari route Laravel dan belum membangun URL dari nilai ini.
- `SNAP_MPAD_PRIVATE_KEY_PATH` dan `SNAP_MPAD_PUBLIC_KEY_PATH` tersedia untuk kebutuhan signing/key exchange berikutnya, tetapi belum dikonsumsi oleh alur token, inquiry, atau payment saat ini.

Jangan menganggap variable cadangan tersebut sebagai bukti implementasi outbound atau kesiapan UAT.

## Penyimpanan Key

Simpan key operasional di luar repository. Jika local development memakai `storage/app/keys`, direktori tersebut sudah di-ignore Git.

Contoh penempatan server:

```text
/var/www/mpad/shared/keys/bri_public.pem
/var/www/mpad/shared/keys/mpad_private.pem
```

Aturan minimum:

- berikan hak baca hanya kepada user proses PHP;
- jangan simpan private key dalam source code, database umum, atau log;
- jangan mencetak isi key saat validasi;
- hanya tukarkan shared secret melalui kanal onboarding resmi;
- catat pemilik, tanggal berlaku, dan prosedur rotasi key di luar repository publik.

## Urutan Konfigurasi Sandbox

1. Konfirmasikan dengan BRI: domain HTTPS, partner ID, client key, IP sumber, panjang final VA/customer number, expiry, dan canonical string-to-sign. Per 2026-07-30, BRI sudah mengonfirmasi environment sandbox, prefix `19960` hanya channel sandbox, path sebelum `/snap` bebas, dan signature RSA-SHA256.
2. Simpan public key RSA BRI melalui secret manager/file server yang tidak dilacak Git. Shared secret HMAC hanya dipakai bila ada partner lain yang secara eksplisit memakai HMAC.
3. Isi environment server menggunakan nama variable pada tabel di atas.
4. Pastikan database memiliki migration `payment_requests`, `payment_request_items`, dan `snap_idempotency_keys`. Jalankan migration hanya melalui prosedur deployment yang diotorisasi.
5. Pastikan cache store dapat dipakai bersama oleh semua instance API.
6. Bersihkan dan bangun ulang cache konfigurasi setelah perubahan environment:

```powershell
php artisan config:clear
php artisan config:cache
```

7. Verifikasi route tanpa mencetak credential:

```powershell
php artisan route:list --path=snap
```

8. Jalankan regression test SNAP lokal:

```powershell
php artisan test tests/Feature/Payment/Snap tests/Unit/Payment/Snap --do-not-cache-result
```

9. Lakukan test positif dan negatif memakai credential sandbox resmi: token, signature salah, timestamp kedaluwarsa, IP tidak diizinkan, inquiry valid/tidak ditemukan/lunas/expired, payment valid, amount mismatch, dan duplicate `X-EXTERNAL-ID`.

Baseline lokal 2026-07-28: suite feature dan unit SNAP lulus 39 test dengan 140 assertion untuk scope SNAP. Hasil lokal ini tetap bukan bukti kelulusan sandbox/UAT dengan credential dan skenario resmi BRI.

## Menyiapkan VA untuk Trigger BRI

Setelah payment request BRIVA aktif dibuat dari aplikasi warga/API, operator dapat mencetak paket handoff yang aman dibagikan ke BRI sandbox:

```powershell
php artisan snap:briva-sandbox-handoff {payment_request_id} --base-url=https://apimpad.baubaukota.go.id --json
```

Output command berisi nomor VA, `partnerServiceId`/`customerNo` hasil mapping konfigurasi saat ini, nominal, expiry, daftar nomor tagihan, serta URL token/inquiry/payment. Command ini read-only dan tidak mencetak credential, token, public key, private key, NIK, atau nomor HP.

## Checklist Sebelum Menyerahkan Callback ke BRI

- [ ] Domain sandbox dapat diakses melalui HTTPS dengan sertifikat valid.
- [x] Path dengan prefix `/api` dapat dipakai karena BRI membebaskan path/subdomain sebelum `/snap`, selama bagian setelah `/snap` mengikuti template.
- [ ] `BRI_SNAP_ENABLED=true` hanya setelah seluruh konfigurasi di bawah lengkap.
- [ ] `BRI_SNAP_PARTNER_ID` dan `BRI_SNAP_CLIENT_KEY` bukan nilai contoh.
- [x] Algoritma signature untuk sandbox BRI dikonfirmasi RSA-SHA256; public key BRI harus tersedia dan bisa dibaca aplikasi.
- [ ] IP BRI sudah masuk `SNAP_ALLOWED_IPS`.
- [ ] Prefix `19960` dikonfirmasi sebagai sandbox-only; total panjang VA dan expiry operasional masih perlu bukti dari spesifikasi/contoh request BRI.
- [ ] Cache token konsisten pada seluruh instance.
- [ ] Migration payment request dan idempotency sudah diterapkan pada environment sandbox.
- [ ] Config cache dibangun ulang setelah perubahan environment.
- [ ] Test positif dan negatif BRI/ASPI memiliki bukti hasil tanpa credential di log.
- [ ] Response code dan schema telah diverifikasi terhadap spesifikasi bank yang dipakai.

## Batas Local Smoke

Local smoke boleh memakai prefix VA dummy atau prefix yang diberikan BRI tetapi belum aktif di channel bank. Local smoke tidak boleh disebut sandbox/UAT dan tidak boleh memakai nilai dummy pada server yang diserahkan kepada BRI.

Konfirmasi BRI 2026-07-30:

- Prefix `19960` adalah environment sandbox dan tidak aktif pada BRImo/ATM production.
- VA sandbox tidak bisa diuji melalui BRImo biasa; BRI dapat membantu trigger inquiry dan payment pada channel sandbox jika M-PAD menyediakan VA.
- Endpoint token, inquiry, dan payment yang aktif saat ini sudah sesuai selama suffix setelah `/snap` mengikuti template.
- Signature request dari BRI memakai RSA-SHA256 dan diverifikasi dengan public key BRI yang sudah dikirimkan.

Catatan smoke lokal 2026-07-29:

- Public key BRI disimpan sebagai file di luar repo API, lalu `.env` lokal menunjuk ke `BRI_SNAP_PUBLIC_KEY_PATH`.
- Dengan asumsi sementara `BRI_SNAP_SIGNATURE_ALGORITHM=rsa_sha256` dan prefix `BRI_SNAP_VA_PREFIX=19960`, API berhasil membuat payment request lokal dengan VA `199600000000000003`, membaca ulang status `pending`, lalu membatalkan request menjadi `cancelled`.
- Uji BRImo production terhadap VA tersebut memang tidak valid karena prefix/credential masih sandbox. Langkah berikutnya adalah menyediakan VA aktif kepada BRI agar mereka men-trigger inquiry dan payment dari channel sandbox.
