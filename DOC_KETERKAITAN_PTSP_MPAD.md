# Dokumentasi Keterkaitan Layanan M-PAD dengan PTSP (DPMPTSP)

**Proyek:** M-PAD Kota Baubau
**Tanggal:** Agustus 2026
**Status:** Implementasi di lingkungan DEV (backend `retribusi-api-dev` + source frontend `retribusi-admin-src`). **Deploy ke produksi menunggu arahan.**

---

## 1. Latar Belakang

Admin PTSP (DPMPTSP) tidak dapat melakukan koneksi H2H langsung ke OSS Pusat,
sehingga difasilitasi menjadi **admin internal M-PAD dengan role `admin_ptsp`**.
Dengan role ini, admin PTSP dapat:

- Mengoleksi data NIB real-time dari kepengurusan warga (melalui mekanisme
  `nib_nop_mappings` hasil sinkronisasi BHUMI/OSS dan entri manual).
- Memantau pertumbuhan perusahaan ber-NIB untuk keperluan analisis.
- Menjadikan **NIB sebagai syarat wajib** kontrak/registrasi layanan M-PAD
  (gate NIB saat warga mendaftar layanan).

## 2. Daftar Layanan M-PAD yang Terkait PTSP

Diambil dari tabel `retribution_classifications` (endpoint
`GET /api/admin/ptsp/dashboard/related-services` — URL final `GET /api/ptsp/dashboard/related-services`).
Jenis keterkaitan ditentukan dari kode klasifikasi:

- **`PZ-*`** → *Izin terbit PTSP* (produk izin yang diterbitkan PTSP/DPMPTSP)
- **PBJT / REKLAME / MBLB / WALET / AIR-TANAH / JU-* / RET-KES** → *Wajib NIB untuk usaha*
  (NIB menjadi identitas usaha & syarat registrasi layanan)
- **PBB-P2 / BPHTB** → *Data pendukung (validasi)* (validasi objek & data warga)

| ID | Kode | Nama Layanan | Type | Keterkaitan |
|----|------|--------------|------|-------------|
| 212 | PZ-PBG | Persetujuan Bangunan Gedung | 107 (Perizinan Tertentu) | Izin terbit PTSP |
| 213 | PZ-TKA | Tenaga Kerja Asing | 107 (Perizinan Tertentu) | Izin terbit PTSP |
| 188 | REKLAME | Pajak Reklame | 16 (Pajak) | Wajib NIB untuk usaha |
| 189 | MBLB | Pajak MBLB | 16 (Pajak) | Wajib NIB untuk usaha |
| 190 | WALET | Sarang Burung Walet | 16 (Pajak) | Wajib NIB untuk usaha |
| 192 | PBJT-FOOD | PBJT Makanan & Minuman | 16 (Pajak) | Wajib NIB untuk usaha |
| 193 | PBJT-HTL | PBJT Jasa Perhotelan | 16 (Pajak) | Wajib NIB untuk usaha |
| 194 | PBJT-HBR | PBJT Jasa Kesenian & Hiburan | 16 (Pajak) | Wajib NIB untuk usaha |
| 195 | PBJT-HBR-SP | PBJT Hiburan (spesifik) | 16 (Pajak) | Wajib NIB untuk usaha |
| 197 | AIR-TANAH | Pajak Air Tanah | 16 (Pajak) | Wajib NIB untuk usaha |
| 203 | JU-TKU | Jasa Umum - Tempat Khusus Usaha | 106 (Jasa Usaha) | Wajib NIB untuk usaha |
| 204 | JU-PEL | Jasa Umum - Pelabuhan | 106 (Jasa Usaha) | Wajib NIB untuk usaha |
| 206 | JU-VILA | Jasa Usaha - Vila | 106 (Jasa Usaha) | Wajib NIB untuk usaha |
| 207 | JU-RPH | Jasa Usaha - Rumah Potong Hewan | 106 (Jasa Usaha) | Wajib NIB untuk usaha |
| 208 | JU-PELAB | Jasa Usaha - Pelabuhan | 106 (Jasa Usaha) | Wajib NIB untuk usaha |
| 209 | JU-REK | Jasa Usaha - Rekreasi | 106 (Jasa Usaha) | Wajib NIB untuk usaha |
| 210 | JU-PROD | Jasa Usaha - Produksi | 106 (Jasa Usaha) | Wajib NIB untuk usaha |
| 211 | JU-ASET | Jasa Usaha - Aset | 106 (Jasa Usaha) | Wajib NIB untuk usaha |
| 201 | RET-KES | Retribusi Pelayanan Kesehatan | 17 (Jasa Umum) | Wajib NIB untuk usaha |
| 186 | PBB-P2 | Pajak Bumi & Bangunan P2 | - | Data pendukung (validasi) |
| 187 | BPHTB | Bea Perolehan Hak atas Tanah & Bangunan | - | Data pendukung (validasi) |

> Nama layanan di atas mengikuti verifikasi awal dari database produksi. Untuk
> nama final persis per baris, gunakan endpoint `relatedServices()` — daftar
> diurutkan berdasarkan `id`.

## 3. Skema Alur Sinkronisasi (NIB ↔ M-PAD)

```
                        +-----------------------+
                        |   OSS / BHUMI (Pusat) |
                        +-----------+-----------+
                                    | (tarik NIB / sertifikat tanah)
                                    v
   +-------------------+   +----------------------------+
   | Entry PTSP        +-->| nib_nop_mappings           |
   | (manual / import) |   | nib, nop, taxpayer_name,   |
   +-------------------+   | match_status, source,      |
                           | last_synced_at, timestamps |
                           +-------------+--------------+
                                         | (auto/periodik)
                                         v
                           +-----------------------------+
                           | Sync BHUMI (cocokkan NIB    |
                           | ↔ NOP PBB, nama taxpayer)   |
                           +-------------+--------------+
                                         |
                    +--------------------+--------------------+
                    v                                         v
   +--------------------------------+        +--------------------------------+
   | Dashboard Pertumbuhan NIB      |        | Gate NIB saat daftar layanan    |
   | (stats + growth per bulan,     |        | (wajib punya NIB untuk layanan  |
   |  sektor) — khusus admin_ptsp   |        |  ber-keterkaitan "Wajib NIB")   |
   +--------------------------------+        +--------------------------------+
```

**Tahapan:**

1. **Entry PTSP** — NIB warga/pelaku usaha dimasukkan (manual oleh admin PTSP atau
   import hasil kepengurusan). Tersimpan di `nib_nop_mappings` dengan
   `source = 'manual'`, `match_status = 'pending'`.
2. **Sync BHUMI** — proses sinkronisasi mencocokkan NIB dengan NOP PBB dan nama
   taxpayer; `match_status` diperbarui menjadi `matched` / `conflict` /
   `not_found`, `source = 'bhumi'`, `last_synced_at` diisi.
3. **Dashboard growth** — data `created_at` per mapping diagregasi per bulan
   untuk grafik tren pertumbuhan perusahaan ber-NIB (endpoint `growth()`).
4. **Gate NIB** — saat warga mendaftar layanan M-PAD yang berketerkaitan
   "Wajib NIB untuk usaha", sistem memvalidasi kepemilikan NIB (via nama/NOP
   pada `nib_nop_mappings`) sebelum registrasi disetujui.

## 4. Struktur Data (hasil eksplorasi)

**`nib_nop_mappings`** (migration prod `2026_08_02_100001_create_nib_nop_mappings_table.php`):

- `nib` (string 16, unique), `nop` (string 18, nullable, indexed),
  `taxpayer_name` (nullable), `land_area_m2`, `building_area_m2` (decimal),
  `znt` (nullable), `match_status` enum(`pending`,`matched`,`conflict`,`not_found`),
  `source` enum(`bhumi`,`manual`), `last_synced_at`, `created_at`, `updated_at`.
- **Tidak ada foreign key ke `taxpayers`** — kecocokan dilakukan via nama
  (`taxpayer_name` ↔ `taxpayers.name`) dan/atau NOP.

**Join untuk status NIB per warga** (endpoint `taxpayers()`):

- `taxpayers.id` → `taxpayer_pbb_objects.taxpayer_id` (1:N, objek PBB warga)
- `taxpayers.id` → `taxpayer_nops.taxpayer_id` (1:N, NOP legacy — tabel ini hanya
  ada di prod; di DB dev belum ada)
- `taxpayer_pbb_objects.nop` / `taxpayer_nops.nop` → `nib_nop_mappings.nop` (match NOP)
- `taxpayers.name` ↔ `nib_nop_mappings.taxpayer_name` (match nama, case-insensitive)

Prioritas pencocokan: **NOP dulu (lebih presisi), lalu nama**.

## 5. Endpoint API (role `admin_ptsp`, grup middleware `admin`)

| Method | URL | Fungsi |
|--------|-----|--------|
| GET | `/api/ptsp/dashboard/stats` | Total NIB per status, total taxpayers, pelaku usaha ber-NIB, jumlah layanan terkait |
| GET | `/api/ptsp/dashboard/growth` | Tren NIB per bulan + komposisi sektor (jika kolom sektor tersedia) |
| GET | `/api/ptsp/dashboard/related-services` | 22 layanan M-PAD terkait PTSP + jenis keterkaitan |
| GET | `/api/ptsp/dashboard/taxpayers?limit=&search=` | Daftar warga/pelaku usaha + status NIB |

**Catatan kompatibilitas:** endpoint dijaga dengan `Schema::hasTable()` —
di DB dev (`retribusi_dev`) tabel `nib_nop_mappings` belum ada sehingga
mengembalikan 0/kosong; di produksi (`retribusi`) otomatis berisi data penuh.
Tidak ada migration yang dijalankan dari sisi dev.

## 6. Best Practice

1. **Satu NIB = satu identitas usaha** — dorong warga mencantumkan NIB saat
   registrasi layanan M-PAD; NIB menjadi kunci join lintas layanan (PBB, PBJT,
   retribusi jasa usaha, perizinan).
2. **Rutin sinkronisasi BHUMI** — jalankan sync periodik (mis. harian) agar
   `match_status` tidak menumpuk di `pending` dan data growth akurat.
3. **Tangani `conflict` secara manual** — mapping dengan status `conflict`
   (nama/NOP tidak cocok) harus diverifikasi admin PTSP/BAPENDA sebelum
   digunakan sebagai gate NIB.
4. **Jaga kualitas nama taxpayer** — pencocokan via nama bersifat fuzzy;
   standarkan format nama (tanpa gelar/inisial ganda) untuk memperbaiki akurasi
   `matched_by = 'name'`.
5. **Dashboard = alat analisis & pengawasan** — gunakan tren bulanan untuk
   memantau efektivitas kepengurusan NIB dan potensi wajib pajak baru.
6. **RBAC minimal** — role `admin_ptsp` hanya diberi akses ke endpoint PTSP
   (bukan seluruh endpoint admin) jika diperlukan; saat ini grup `ptsp` berada
   di dalam grup `admin` untuk kemudahan, sesuaikan bila perlu pemisahan
   middleware khusus.

## 7. Status Implementasi

- [x] Role `admin_ptsp` ditambahkan di `User.php` + `isAdminPtsp()`
- [x] `ROLE_ADMIN_PTSP` ditambahkan ke `EnsureAdmin::$allowedRoles`
- [x] `PtspDashboardController` dibuat (stats, growth, related-services, taxpayers)
- [x] Route group `/ptsp` didaftarkan di `routes/api.php` (dalam grup `admin`)
- [x] Halaman `src/pages/PtspDashboard.tsx` dibuat (kartu statistik, grafik
      recharts, tabel layanan terkait, tabel warga + status NIB)
- [x] Route `/ptsp/dashboard` + redirect HomeRoute untuk role `admin_ptsp`
- [x] Tipe `UserRole` diperluas (`'admin_ptsp'`)
- [x] Build frontend sukses (vite build)
- [ ] **Deploy ke produksi — MENUNGGU ARAHAN** (belum ada perubahan pada
      `retribusi-api` prod maupun `retribusi-admin/dist`)

### File yang diubah/dibuat (DEV)

- `retribusi-api-dev/app/Models/User.php`
- `retribusi-api-dev/app/Http/Middleware/EnsureAdmin.php`
- `retribusi-api-dev/app/Http/Controllers/PtspDashboardController.php` (baru)
- `retribusi-api-dev/routes/api.php`
- `retribusi-admin-src/src/pages/PtspDashboard.tsx` (baru)
- `retribusi-admin-src/src/App.tsx`
- `retribusi-admin-src/src/types/index.ts`
- `retribusi-api-dev/DOC_KETERKAITAN_PTSP_MPAD.md` (dokumen ini)
