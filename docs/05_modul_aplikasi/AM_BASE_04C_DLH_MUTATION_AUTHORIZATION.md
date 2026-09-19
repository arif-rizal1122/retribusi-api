# AM-BASE-04C — Otorisasi Mutasi Persampahan DLH

Tanggal checkpoint: 19 September 2026

## Perubahan

- `POST /api/dlh/trash/transfer-occupant/{taxObjectId}` dan `POST /api/dlh/trash/generate-bills` sekarang hanya dapat dijalankan `User` internal yang merupakan super admin atau memiliki `opd_id = 4` (DLH).
- Taxpayer tetap dapat memakai inquiry dan kalkulator tarif yang bersifat baca saja; akun warga tidak dapat mengubah penghuni atau menerbitkan billing massal.
- Transfer penghuni hanya mencari objek dalam scope DLH (`opd_id = 4` atau klasifikasi 198). Objek OPD lain tidak dapat dimutasi melalui endpoint DLH.
- Tidak ada consumer mobile/admin/petugas yang memakai endpoint mutasi ini dalam source yang diaudit, sehingga guard tidak memerlukan perubahan frontend.

## Batas

Pemutakhiran penghuni oleh warga dari mobile tetap memerlukan workflow verifikasi, tanggal efektif, bukti dan kebijakan DLH (AM-SMP-05/D05). Guard ini tidak menggantikan approval dashboard dan tidak mengubah UI operasional.

## Validasi

`DlhTrashBillingTest` mencakup user internal DLH, Taxpayer, dan user lintas OPD; suite DLH terkait dijalankan pada SQLite in-memory. Tidak ada migration atau integrasi bank yang diubah.
