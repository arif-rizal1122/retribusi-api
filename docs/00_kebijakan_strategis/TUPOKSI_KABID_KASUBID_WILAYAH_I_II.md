# Tupoksi Kabid & Kasubid Bidang Pajak Daerah — Wilayah I & II
## M-PAD Bapenda Kota Baubau

**Dasar hukum:** Peraturan Wali Kota Baubau No. 8 Tahun 2025 (Struktur Organisasi & Tata Kerja)
**Perda acuan:** Perda Kota Baubau No. 1 Tahun 2024 (Pajak Daerah & Retribusi Daerah)
**Tanggal dokumen:** 23 September 2026
**Status:** Selaras dengan implementasi sistem (terverifikasi kode + database produksi)

---

## 1. Dasar Pembagian Wilayah

Bapenda Kota Baubau membagi pemungutan pajak daerah menjadi **dua bidang wilayah**
dengan beban kerja dan karakteristik objek pajak yang berbeda. Pembagian ini
bukan sekadar administratif — masing-masing wilayah punya gate persetujuan
(approval gate) yang berbeda karena sifat penetapannya tidak sama.

| Aspek | Wilayah I | Wilayah II |
|---|---|---|
| **Karakter objek** | Objek berbasis dokumen & formalitas (sertifikat, akta, izin) | Objek berbasis transaksi & omzet harian |
| **Sifat penetapan** | *Official assessment* — pemerintah menetapkan | Sebagian *self-assessment* — WP melaporkan sendiri |
| **Approval gate** | Mutasi PBB, pembetulan PBB, verifikasi notaris, penghapusan denda | Pelaporan mandiri PBJT, penghapusan denda |
| **Jumlah jenis layanan** | 6 jenis pajak | 7 jenis pajak |

---

## 2. Pembagian Jenis Pajak (terverifikasi dari tabel `retribution_classifications`)

### Wilayah I — 6 Jenis Pajak

| Kode Klasifikasi | ID | Nama Layanan |
|---|---|---|
| `PBB-P2` | 186 | Pajak Bumi dan Bangunan Perdesaan & Perkotaan |
| `BPHTB` | 187 | Bea Perolehan Hak atas Tanah dan Bangunan |
| `REKLAME` | 188 | Pajak Reklame |
| `MBLB` | 189 | Pajak Mineral Bukan Logam dan Batuan |
| `WALET` | 190 | Pajak Sarang Burung Walet |
| `OPSEN-PKB` / `OPSEN-BBNKB` | 191 | Opsen Pajak (PKB & BBNKB) |

### Wilayah II — 7 Jenis Pajak

| Kode Klasifikasi | ID | Nama Layanan |
|---|---|---|
| `PBJT-PRK` | 172 | PBJT — Jasa Parkir |
| `PBJT-FOOD` | 192 | PBJT — Makanan dan Minuman |
| `PBJT-HTL` | 193 | PBJT — Jasa Perhotelan |
| `PBJT-HBR` | 194 | PBJT — Jasa Kesenian dan Hiburan |
| *(khusus)* | 195 | Hiburan Malam (Khusus) |
| `PBJT-LISTRIK` | 196 | PBJT — Tenaga Listrik (Non-PLN / Dihasilkan Sendiri) |
| `AIR-TANAH` | 197 | Pajak Air Tanah |

---

## 3. Tupoksi Kepala Bidang (Kabid) Pajak Daerah

### 3.1 Kabid Pajak Daerah Wilayah I

**Jabatan:** Kepala Bidang Pajak Daerah Wilayah I
**Role sistem:** `kabid_wilayah_1`
**Domain data:** `wilayah_1` (terkunci — tidak bisa akses data Wilayah II)
**Akun produksi:** `kabid_pajak@mpad.online` (ID 298)

**Tugas pokok:**

1. **Perencanaan & Penetapan** — menyusun rencana kerja pemungutan pajak
   Wilayah I; menetapkan target penerimaan per jenis pajak.
2. **Otorisasi hukum penetapan** — menandatangani SK Mutasi/Pembetulan PBB-P2
   secara elektronik (TTE) dan mengesahkan SPPT definitif. Bertindak sebagai
   **Level 3 (Signer/Approver)** dalam rantai verifikasi berjenjang PBB-P2.
3. **Verifikasi berjenjang PBB-P2** — memberi otorisasi akhir atas 9 skenario
   mutasi & pembetulan PBB-P2; memutuskan status `APPROVED` atau `REJECTED`.
4. **Penetapan BPHTB** — memverifikasi & menetapkan BPHTB atas transaksi
   perolehan hak, termasuk validasi kesesuaian dengan data notaris/PPAT.
5. **Pengawasan objek formalitas** — mengawasi Pajak Reklame (termasuk audit
   visual billboard), MBLB, dan Sarang Burung Walet.
6. **Penghapusan denda (penalty waiver)** — memberi persetujuan atas
   permohonan penghapusan/pengurangan denda pajak Wilayah I.
7. **Evaluasi & pelaporan** — mengevaluasi capaian penerimaan Wilayah I dan
   melaporkan ke Kepala Bapenda.

**Batasan:**

- **Tidak** dapat mengakses data, tagihan, atau objek pajak Wilayah II.
- **Tidak** dapat memproses penetapan PBJT (kewenangan Wilayah II).
- Percobaan akses lintas wilayah ditolak sistem (HTTP 403).

### 3.2 Kabid Pajak Daerah Wilayah II

**Jabatan:** Kepala Bidang Pajak Daerah Wilayah II
**Role sistem:** `kabid_wilayah_2`
**Domain data:** `wilayah_2` (terkunci — tidak bisa akses data Wilayah I)
**Akun produksi:** `kabid_wilayah2@mpad.online` (ID 302)

**Tugas pokok:**

1. **Perencanaan & Penetapan** — menyusun rencana kerja pemungutan pajak
   Wilayah II; menetapkan target penerimaan per jenis pajak.
2. **Verifikasi pelaporan mandiri PBJT** — memeriksa kewajaran omzet yang
   dilaporkan WP restoran, hotel, hiburan, parkir, dan listrik. Bertindak
   sebagai gate `pbjt_self_assessment`.
3. **Pengawasan kepatuhan PBJT** — mengawasi kewajiban pelaporan berkala PBJT;
   menindaklanjuti WP yang tidak melapor atau melaporkan tidak wajar.
4. **Penetapan Pajak Air Tanah** — memverifikasi & menetapkan PAT atas
   pemanfaatan air tanah, termasuk koordinasi uji petik debit air.
5. **Otorisasi penetapan** — menandatangani SK/SKPD Wilayah II secara
   elektronik (TTE).
6. **Penghapusan denda (penalty waiver)** — memberi persetujuan atas
   permohonan penghapusan/pengurangan denda pajak Wilayah II.
7. **Evaluasi & pelaporan** — mengevaluasi capaian penerimaan Wilayah II dan
   melaporkan ke Kepala Bapenda.

**Batasan:**

- **Tidak** dapat mengakses data, tagihan, atau objek pajak Wilayah I.
- **Tidak** dapat memproses mutasi/pembetulan PBB-P2 & BPHTB (kewenangan Wilayah I).
- Percobaan akses lintas wilayah ditolak sistem (HTTP 403).

---

## 4. Tupoksi Kepala Sub Bidang (Kasubid)

### 4.1 Kasubid Pendaftaran, Pendataan & Penetapan — Wilayah I

**Role sistem:** `kasubid_pendataan_wil1`
**Domain data:** `wilayah_1`
**Akun produksi:** `kasubid.pendataan.w1@mpad.online` (ID 303)

**Tugas pokok:**

1. **Pendaftaran objek pajak baru** — menerima & memverifikasi permohonan
   pendaftaran objek PBB-P2, Reklame, MBLB, dan Walet di Wilayah I.
2. **Pendataan & pemutakhiran data** — memperbarui data objek (luas, ZNT,
   kondisi bangunan) berdasarkan hasil survei lapangan.
3. **Verifikasi Level 2 (Checker/Analyst)** — memverifikasi kesesuaian ZNT &
   DBKB bangunan; memvalidasi hasil survei/LSPOP; menghitung piutang
   proporsional. Mengubah status permohonan ke `PRE_APPROVED_L2`.
4. **Disposisi tugas survei** — menugaskan petugas lapangan untuk survei
   (`SURVEY`) atas objek yang perlu verifikasi fisik.
5. **Penyiapan berkas penetapan** — menyiapkan warkah untuk otorisasi Kabid.

**Batasan:** tidak dapat memproses penetapan final (kewenangan Kabid) dan
tidak dapat mengakses Wilayah II.

### 4.2 Kasubid Penagihan, Verifikasi & Pelaporan — Wilayah I

**Role sistem:** `kasubid_penagihan_wil1`
**Domain data:** `wilayah_1`
**Akun produksi:** `kasubid.penagihan.w1@mpad.online` (ID 304)

**Tugas pokok:**

1. **Penagihan piutang pajak** — menyusun & melaksanakan strategi penagihan
   atas tunggakan PBB-P2, BPHTB, Reklame, MBLB, dan Walet.
2. **Penerbitan surat penagihan** — menyiapkan Surat Teguran I, Teguran II,
   dan Surat Paksa sesuai tahapan & jatuh tempo.
3. **Verifikasi pembayaran** — memverifikasi kesesuaian pembayaran dengan
   tagihan (NTPD, NOP, nilai setoran).
4. **Rekonsiliasi & pelaporan** — menyusun laporan realisasi penerimaan
   Wilayah I; merekonsiliasi data tagihan vs pembayaran.
5. **Penanganan keberatan & restitusi** — menelaah keberatan WP dan mengusulkan
   penyelesaian ke Kabid.

**Batasan:** tidak dapat mengakses Wilayah II.

### 4.3 Kasubid Pendaftaran, Pendataan & Penetapan — Wilayah II

**Role sistem:** `kasubid_pendataan_wil2`
**Domain data:** `wilayah_2`
**Akun produksi:** `kasubid.pendataan.w2@mpad.online` (ID 305)

**Tugas pokok:**

1. **Pendaftaran WP PBJT baru** — mendaftarkan WP restoran, hotel, hiburan,
   parkir, listrik, dan pemanfaat air tanah.
2. **Pendataan potensi** — memetakan potensi PBJT & PAT di Wilayah II
   (termasuk pemetaan titik usaha baru).
3. **Verifikasi pelaporan mandiri** — memeriksa kelengkapan & kewajaran
   laporan omzet PBJT yang disampaikan WP.
4. **Disposisi uji petik** — menugaskan petugas lapangan uji petik omzet &
   pengukuran debit air tanah.
5. **Penyiapan berkas penetapan** — menyiapkan berkas untuk otorisasi Kabid.

**Batasan:** tidak dapat mengakses Wilayah I.

### 4.4 Kasubid Penagihan, Verifikasi & Pelaporan — Wilayah II

**Role sistem:** `kasubid_penagihan_wil2`
**Domain data:** `wilayah_2`
**Akun produksi:** `kasubid.penagihan.w2@mpad.online` (ID 306)

**Tugas pokok:**

1. **Penagihan piutang PBJT & PAT** — menyusun strategi penagihan tunggakan
   Wilayah II.
2. **Penerbitan surat penagihan** — menyiapkan Surat Teguran I/II dan Surat
   Paksa sesuai tahapan.
3. **Verifikasi pembayaran** — memverifikasi setoran PBJT & PAT.
4. **Rekonsiliasi & pelaporan** — menyusun laporan realisasi penerimaan
   Wilayah II; merekonsiliasi data tagihan vs pembayaran.
5. **Penanganan keberatan & restitusi** — menelaah keberatan WP Wilayah II.

**Batasan:** tidak dapat mengakses Wilayah I.

### 4.5 Kasubid Koordinasi, Kerjasama & Pengembangan

**Role sistem:** `kasubid_pengembangan`
**Domain data:** `all` (lintas wilayah — sah, karena fungsi koordinasi)
**Akun produksi:** `kasubid.pengembangan@mpad.online` (ID 307)

**Tugas pokok:**

1. **Koordinasi lintas OPD** — menjembatani Bapenda dengan DPMPTSP, Dukcapil,
   BPN/ATR, dan OPD pemungut lain untuk pertukaran data.
2. **Kerjasama pengembangan pendapatan** — menyiapkan PKS/MoU, termasuk
   rencana integrasi KSWP dengan DPMPTSP dan tapping box dengan pelaku usaha.
3. **Pengembangan sistem & inovasi** — mengusulkan perbaikan proses bisnis
   dan fitur sistem M-PAD.
4. **Penyuluhan & sosialisasi** — merancang program edukasi pajak daerah
   bersama OPD terkait.
5. **Kajian potensi baru** — menelaah sumber pendapatan baru & kebijakan
   opsen PKB/BBNKB bersama Provinsi Sulawesi Tenggara.

### 4.6 Kasubid Pengawasan & Pengendalian (Wasdal)

**Role sistem:** `kasubid_pengawas` (alias: `kasubid_wasdal`)
**Domain data:** `all` (lintas wilayah — sah, fungsi pengawasan)
**Akun produksi:** `kasubid@retribusi.id` (ID 24)
**Grup dashboard:** `audit`

**Tugas pokok:**

1. **Pengawasan kepatuhan** — mengawasi kepatuhan WP dan kepatuhan internal
   aparatur dalam pemungutan pajak daerah.
2. **Audit uji petik** — melaksanakan uji petik omzet, luas objek, dan
   kesesuaian data objek pajak di lapangan.
3. **Pemeriksaan kepatuhan** — menelaah indikasi WP tidak melapor, kurang
   bayar, atau objek pajak tidak terdaftar.
4. **Pengendalian mutu data** — memeriksa konsistensi & kelengkapan data
   objek pajak di seluruh wilayah.
5. **Laporan pengawasan** — menyusun laporan temuan dan rekomendasi
   perbaikan kepada Kepala Bapenda.
6. **Penindakan & enforcement** — mengusulkan langkah penindakan atas
   temuan pelanggaran, termasuk usulan penertiban bersama Satpol PP.

**Catatan pemetaan:** Nomenklatur "Kasubid Wasdal" **digabung** ke role
`kasubid_pengawas` sesuai keputusan pengelola (23 Sep 2026) — tidak dibuat
role baru, agar tidak menambah percabangan tanpa kebutuhan operasional.

---

## 5. Rantai Verifikasi Berjenjang PBB-P2 (Maker–Checker–Signer)

Dokumen acuan: `docs/mpad_rbac/04_MATRIKS_PEMBAGIAN_LAYANAN_OPD_DAN_ROLE_MAPPING.md`

| Level | Aktor | Role | Wewenang | Status akhir |
|---|---|---|---|---|
| **L1 Maker/Validator** | Staf Pelayanan / Verifikator | `verifikator` / `admin` | Periksa kelengkapan warkah, verifikasi spasial batas bidang via GIS | `VERIFIED_L1` |
| **L2 Checker/Analyst** | Kasubid Pendataan | `kasubid_pendataan` / `kasubid_pendataan_wil1` | Verifikasi ZNT & DBKB, disposisi survei, validasi LSPOP, hitung piutang | `PRE_APPROVED_L2` |
| **L3 Signer/Approver** | Kabid Pajak Daerah | `kabid_wilayah_1` / `kabid_wilayah_2` | Otorisasi hukum, TTE SK Mutasi/Pembetulan, sahkan SPPT definitif | `APPROVED` / `REJECTED` |

---

## 6. Matriks Kewenangan Ringkas

| Kewenangan | Kabid Wil I | Kabid Wil II | Kasubid Pendataan | Kasubid Penagihan | Kasubid Pengembangan | Kasubid Wasdal |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Lihat data Wilayah I | ✅ | ❌ | ✅ *(w1)* | ✅ *(w1)* | ✅ | ✅ |
| Lihat data Wilayah II | ❌ | ✅ | ✅ *(w2)* | ✅ *(w2)* | ✅ | ✅ |
| Pendaftaran & pendataan objek | ✅ | ✅ | ✅ | ❌ | ❌ | 👁️ |
| Verifikasi L2 PBB-P2 | ✅ | — | ✅ | ❌ | ❌ | 👁️ |
| Otorisasi/TTE penetapan (L3) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Penetapan mutasi/pembetulan PBB-P2 | ✅ | ❌ | ✅ *(usul)* | ❌ | ❌ | 👁️ |
| Verifikasi pelaporan PBJT | ❌ | ✅ | ✅ *(usul)* | ❌ | ❌ | 👁️ |
| Penagihan & surat paksa | 👁️ | 👁️ | ❌ | ✅ | ❌ | 👁️ |
| Verifikasi pembayaran | 👁️ | 👁️ | ❌ | ✅ | ❌ | 👁️ |
| Penghapusan denda | ✅ | ✅ | ❌ | ❌ | ❌ | 👁️ |
| Uji petik & audit lapangan | 👁️ | 👁️ | ❌ | ❌ | ❌ | ✅ |
| Koordinasi lintas OPD & PKS | 👁️ | 👁️ | ❌ | ❌ | ✅ | 👁️ |
| Laporan ke Kepala Bapenda | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

Keterangan: ✅ = berwenang · ❌ = tidak berwenang · 👁️ = hanya baca/pantau

---

## 7. Penegakan di Sistem

| Mekanisme | Komponen | Perilaku |
|---|---|---|
| **Kunci domain** | `TaxDomainHelper::getDomain()` | Membaca `metadata.tax_domain`; role `admin`/`super_admin` → `all` |
| **Gate route** | Middleware `EnsureTaxDomain` (alias `tax_domain`) | Fail-closed: mencoba akses lintas wilayah → **403** |
| **Filter data** | `WilayahController` | Filter lapis kedua pada query objek & tagihan |
| **Isolasi OPD** | Middleware `EnsureOpdScope` | Menegakkan isolasi data per `opd_id` |
| **Gate internal** | Middleware `EnsureAdmin` | Menolak akun warga (`Taxpayer`) dari endpoint aparatur |
| **Maker-checker** | Middleware `EnsureRole` | Gate persetujuan aksi bernilai uang/data |

**Rute khusus wilayah (terpasang):**

- `GET /api/wilayah-1/objek` · `GET /api/wilayah-1/tagihan` — domain `wilayah_1`
- `GET /api/wilayah-2/objek` · `GET /api/wilayah-2/tagihan` — domain `wilayah_2`

---

## 8. Akun Produksi (terverifikasi 23 September 2026)

| ID | Email | Role | Domain | Jabatan |
|---|---|---|---|---|
| 289 | `kepala.bapenda@baubaukota.go.id` | `kepala_bapenda` | `all` | Kepala Bapenda |
| 298 | `kabid_pajak@mpad.online` | `kabid_wilayah_1` | `wilayah_1` | Kabid Pajak Daerah Wilayah I |
| 302 | `kabid_wilayah2@mpad.online` | `kabid_wilayah_2` | `wilayah_2` | Kabid Pajak Daerah Wilayah II |
| 303 | `kasubid.pendataan.w1@mpad.online` | `kasubid_pendataan_wil1` | `wilayah_1` | Kasubid Pendataan Wilayah I |
| 304 | `kasubid.penagihan.w1@mpad.online` | `kasubid_penagihan_wil1` | `wilayah_1` | Kasubid Penagihan Wilayah I |
| 305 | `kasubid.pendataan.w2@mpad.online` | `kasubid_pendataan_wil2` | `wilayah_2` | Kasubid Pendataan Wilayah II |
| 306 | `kasubid.penagihan.w2@mpad.online` | `kasubid_penagihan_wil2` | `wilayah_2` | Kasubid Penagihan Wilayah II |
| 307 | `kasubid.pengembangan@mpad.online` | `kasubid_pengembangan` | `all` | Kasubid Koordinasi & Pengembangan |
| 297 | `kasubid_pendataan@mpad.online` | `kasubid_pendataan` | `all` | Kasubid Pendataan (Checker L2 lintas bidang) |
| 24 | `kasubid@retribusi.id` | `kasubid_pengawas` | `all` | Kasubid Pengawasan & Pengendalian (Wasdal) |

**Catatan keamanan:** seluruh password akun di atas telah direset ke nilai acak
kuat (16 karakter, campuran huruf besar/kecil/angka) pada 23 September 2026.
Password **tidak** dicatat dalam dokumen ini.

---

## 9. Hal yang Belum Terimplementasi

Berdasarkan audit sistem 23 September 2026:

| Fitur | Status | Keterangan |
|---|---|---|
| SLA Tracker penugasan | ❌ Belum | Belum ada kolom SLA/eskalasi di DB |
| Generator STPD | ❌ Belum | Perlu modul SPTPD lebih dulu |
| Modul KSWP | ❌ Belum | Menunggu PKS dengan DPMPTSP |
| Tapping Box / POS monitoring | ❌ Belum | Menunggu vendor & kesepakatan pelaku usaha |
| Opsen PKB/BBNKB | ❌ Belum | Perlu kesepakatan data dengan Provinsi Sultra |
| NOPD 14 digit | ❌ Tidak dipakai | Sistem konsisten NOP 18 digit (keputusan 23 Sep 2026) |
| Aging schedule piutang | ⚠️ Sebagian | Modul enforcement ada, penjadwalan bertahap |
