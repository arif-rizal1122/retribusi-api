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

Route token `/api/snap/v1.0/access-token/b2b` tetap tersedia sebagai compatibility alias, tetapi matriks akses sandbox lokal mencantumkan versi `v1.1`. Semua URL sandbox harus memakai domain publik HTTPS. Konfirmasikan path lengkap dengan BRI sebelum menyerahkan formulir callback karena dokumen lokal lama tidak konsisten antara path dengan dan tanpa `/api`.

## Environment Variable Runtime

Sumber kebenaran nama variable adalah `config/snap.php`. Isi nilai sebenarnya melalui secret manager atau `.env` server yang tidak dilacak Git.

### Wajib untuk request inbound BRI

| Variable | Isi | Catatan |
| --- | --- | --- |
| `BRI_SNAP_PARTNER_ID` | Partner ID resmi | Dipakai untuk mencocokkan header transaksi `X-PARTNER-ID`. |
| `BRI_SNAP_CLIENT_KEY` | Client key resmi | Dipakai untuk mencocokkan `X-CLIENT-KEY` saat meminta token. |
| `BRI_SNAP_PUBLIC_KEY` | Public key BRI format PEM | Pilih ini atau `BRI_SNAP_PUBLIC_KEY_PATH`, jangan keduanya. Inline key harus menyimpan newline sebagai `\n`. |
| `BRI_SNAP_PUBLIC_KEY_PATH` | Path public key BRI | Pilihan yang disarankan. Gunakan path absolut yang dapat dibaca user proses PHP. |
| `BRI_SNAP_VA_PREFIX` | Prefix VA resmi | Tidak boleh ditebak. Nilai dummy seperti `777` hanya boleh untuk smoke lokal. |
| `BRI_SNAP_VA_LENGTH` | Panjang VA resmi | Default kode adalah `18`; samakan dengan hasil provisioning BRI. |
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

- `BRI_SNAP_CLIENT_SECRET` tersedia di config partner, tetapi token endpoint saat ini mengautentikasi client key dan RSA signature, bukan secret.
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
- hanya tukarkan public key melalui kanal onboarding resmi;
- catat pemilik, tanggal berlaku, dan prosedur rotasi key di luar repository publik.

## Urutan Konfigurasi Sandbox

1. Konfirmasikan dengan BRI: domain HTTPS, path callback, partner ID, client key, public key BRI, IP sumber, prefix/panjang VA, expiry, dan canonical string-to-sign.
2. Pasang public key BRI pada path server yang aman.
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

Baseline lokal 2026-07-22: suite feature dan unit SNAP lulus 33 test dengan 114 assertion. Hasil lokal ini tetap bukan bukti kelulusan sandbox/UAT dengan credential dan skenario resmi BRI.

## Checklist Sebelum Menyerahkan Callback ke BRI

- [ ] Domain sandbox dapat diakses melalui HTTPS dengan sertifikat valid.
- [ ] Path `/api/snap/v1.0/...` telah dikonfirmasi BRI.
- [ ] `BRI_SNAP_PARTNER_ID` dan `BRI_SNAP_CLIENT_KEY` bukan nilai contoh.
- [ ] Public key BRI terbaca aplikasi tanpa berada di Git.
- [ ] IP BRI sudah masuk `SNAP_ALLOWED_IPS`.
- [ ] Prefix, panjang, dan expiry VA sudah dikonfirmasi.
- [ ] Cache token konsisten pada seluruh instance.
- [ ] Migration payment request dan idempotency sudah diterapkan pada environment sandbox.
- [ ] Config cache dibangun ulang setelah perubahan environment.
- [ ] Test positif dan negatif BRI/ASPI memiliki bukti hasil tanpa credential di log.
- [ ] Response code dan schema telah diverifikasi terhadap spesifikasi bank yang dipakai.

## Batas Local Smoke

Local smoke boleh memakai prefix VA dummy dan key test yang hanya hidup selama test. Local smoke tidak boleh disebut sandbox/UAT dan tidak boleh memakai nilai dummy pada server yang diserahkan kepada BRI.
