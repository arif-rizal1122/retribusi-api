# Keputusan Format NOP — Task 2325

**Tanggal:** 23 September 2026
**Status:** DIPUTUSKAN — format 18 digit dipertahankan
**Pelaksana:** Aiman (asisten teknis Pondok Informatika)

---

## Keputusan

Sistem M-PAD memakai **NOP resmi 18 digit**. Format 14 digit yang disebut sebagian
dokumen **tidak diterapkan**.

## Dasar Keputusan

### 1. Struktur NOP 18 digit (standar nasional PBB-P2)

```
74 710 100 100 100 010 1
│  │   │   │   │   │   └── [17]    digit cek (1 digit)
│  │   │   │   │   └────── [14-16] nomor urut (3 digit)
│  │   │   │   └────────── [11-13] kode blok (3 digit)
│  │   │   └────────────── [8-10]  kode kelurahan (3 digit)
│  │   └────────────────── [5-7]   kode kecamatan (3 digit)
│  └────────────────────── [2-4]   kode kabupaten/kota (3 digit)
└───────────────────────── [0-1]   kode provinsi (2 digit)
```

Contoh Kota Baubau: `74.710.100.100.100-010.1`

### 2. Bukti data produksi

| Sumber | Temuan |
|---|---|
| `sppt_records.nop` (varchar 18) | seluruh baris 18 digit, konsisten |
| `tax_objects.nop` (varchar 255) | mengikuti NOP 18 digit |
| Validasi `PbbBapendaController` | 6 endpoint memakai `size:18` |
| Log request H2H BPN | 624 request 18 digit vs 12 request 16 digit |

### 3. Kenapa 14 digit ditolak

- Format 14 digit = NOP versi **ringkas lama** (tanpa kode provinsi & digit cek).
- Bukan standar nasional yang berlaku untuk PBB-P2 saat ini.
- Menerapkannya akan **memutus** kompatibilitas dengan:
  - data existing (`sppt_records`, `tax_objects`),
  - layanan H2H BPN / Bhumi yang mengirim NOP 18 digit.

## Implementasi

### `app/Support/NopHelper.php` (baru)

Helper terpusat: `normalize()`, `isValid()`, `isLegacyShort()`, `parse()`,
`format()`, `validationMessage()`.

Pesan kesalahan bersifat **menjelaskan**, bukan sekadar "invalid":

> NOP yang dikirim 14 digit — itu format ringkas lama. Sistem memakai NOP resmi
> 18 digit (PP.KKK.Kec.Kel.Blok.Urut.C). Mohon lengkapi kode provinsi di depan
> dan digit cek di belakang.

### `app/Rules/ValidNop.php` (baru)

Validation rule agar pesan konsisten lintas controller.

### `H2HBpnController::checkPbb`

- Sebelumnya: `'nop' => 'required|string|max:50'` — NOP cacat lolos validasi,
  lalu gagal senyap di query.
- Sekarang: `['required', 'string', new ValidNop]` + normalisasi input
  sehingga format bertitik (`74.710.100.100.100-010.1`) tetap diterima.

## Verifikasi

| Uji | Input | Hasil |
|---|---|---|
| NOP valid | `747101001001000101` | 200 — `ada_tunggakan: true` |
| NOP bertitik | `74.710.100.100.100-010.1` | 200 — dinormalkan, NOP sama ditemukan |
| NOP 14 digit | `74710100100100` | 422 + pesan penjelas |
| Kosong | `""` | 422 — "NOP wajib diisi." |
| Non-numerik | `abcdefghij12345678` | 422 — "hanya boleh berisi angka" |

## Catatan

Bila di masa depan ada kebutuhan menampilkan NOP versi ringkas untuk pengguna,
gunakan `NopHelper::format()` untuk tampilan — **bukan** mengubah penyimpanan.
