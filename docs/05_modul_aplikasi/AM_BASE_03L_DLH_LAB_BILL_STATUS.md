# AM-BASE-03L — Status Bill pada Reservasi Lab DLH

Tanggal checkpoint: 20 September 2026

## Perubahan

Response billing dan detail order pada route lab DLH sekarang menerbitkan kontrak status bill yang sama dengan batas citizen lainnya:

- `status`: `pending`, `overdue`, `paid`, `pending_verification`, `cancelled`, `expired`, `disputed`, atau `unknown`.
- `status_label`: label bahasa Indonesia dari `CitizenBillStatus`.
- `can_pay`: boolean hasil aturan status server.

Status disusun dari nilai storage legacy (`UNPAID`, `PAID`, `lunas`, dan alias lain), tanggal jatuh tempo, klaim pembayaran pending, serta payment request aktif. Nilai database tidak ditulis ulang. Field bill lama dan nominal tetap dipertahankan.

Route yang menerbitkan kontrak ini:

- `POST /api/v1/dlh/lab/orders/{order}/billing` dan alias `/api/dlh`.
- `GET /api/v1/dlh/lab/orders/{order}` serta lookup nomor order dan aliasnya.

Verifikasi publik e-LHU tetap memakai status order lab dan tidak diubah.

## Consumer dan batas kompatibilitas

Mobile memakai `DlhLabOrder` untuk status order dan tipe bill yang masih generik; field additive ini tidak mengubah alur pengajuan/tracking. Admin dan petugas tidak memiliki consumer route billing/detail lab dalam source yang diaudit. Tidak ada perubahan frontend, migration, credential, atau gateway.

## Validasi

- `DlhLabServiceTest`, `CitizenDlhLabOwnershipTest`, dan `DlhBankSultraPaymentIntegrationTest`: **10 test/76 assertion lulus**.
- PHP lint dan `git diff --check` dijalankan pada file yang berubah.

Checkpoint ini menutup konsistensi status pada bill layanan lab lokal. Parent AM-BASE-03 masih terbuka untuk reverse inquiry/retry provider dan UAT gateway eksternal.
