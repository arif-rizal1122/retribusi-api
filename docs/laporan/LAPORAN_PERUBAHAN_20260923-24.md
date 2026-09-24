# Laporan Perubahan M-PAD
**Periode:** 23 – 24 September 2026
**Lingkup:** `retribusi-api` (backend), `retribusi-admin` (frontend), database produksi
**Server:** 157.10.252.74 · `apimpad.baubaukota.go.id` · `adminmpad.baubaukota.go.id`

---

## 1. RINGKASAN

Dua hari ini dikerjakan **satu rangkaian besar**: alur penugasan lapangan
maker-checker, plus penutupan lubang RBAC yang ditemukan saat audit.

| Area | Status |
|---|---|
| Mesin auto-assign petugas (backend) | Selesai, uji lulus |
| Alur serah-terima lapangan petugas → Kasubid → Kabid | Selesai, uji lulus |
| Halaman UI Penugasan Lapangan (3 sudut pandang) | Selesai, uji browser lulus |
| Lubang RBAC fail-open | **Ditutup, diverifikasi 403** |
| Push GitHub (backend + frontend) | Selesai |
| Migrasi database | Aditif, 0 baris hilang |

---

## 2. PERUBAHAN DATABASE

### 2.1 Migrasi yang dijalankan
`database/migrations/2026_09_24_210000_create_autoassign_petugas_tables.php`
(batch 93)

**Sifat: seluruhnya ADITIF.** Tidak ada `DROP`, tidak ada `DELETE`.
27 baris `user_retribution_assignments` lama tetap utuh.

### 2.2 Kolom baru — `user_retribution_assignments`

| Kolom | Tipe | Guna |
|---|---|---|
| `assigned_by_user_id` | bigint unsigned, nullable | Kabid yang menetapkan |
| `is_active` | boolean, default true | Nonaktifkan tanpa hapus |
| `revoked_at` | timestamp, nullable | Kapan dicabut |

Index baru: `ura_user_active_idx` (user_id + is_active),
`ura_cls_active_idx` (retribution_classification_id + is_active).

### 2.3 Tabel baru — `service_task_assignment_logs`

Tabel **audit terpisah** (bukan menumpang kolom `notes`). Satu baris per
peristiwa, tidak pernah dihapus.

Kolom: `assignment_id`, `event`, `actor_user_id`, `from_user_id`,
`to_user_id`, `stage_from`, `stage_to`, `status_from`, `status_to`,
`reason`, `meta` (json), `created_at`.

Index: `stalog_assignment_idx`, `stalog_event_idx`, `stalog_to_user_idx`.

**Jenis event yang dicatat:** `ASSIGNED`, `REASSIGNED`, `REVOKED`,
`SUBMITTED`, `RETURNED`, `VERIFIED`, `VERIFICATION_CLOSED`, `SUPERSEDED`,
`CONFIRMED`, `REJECTED_BY_KABID`, `MARKED_UNRESPONSIVE`.

### 2.4 Keadaan data sekarang

| Tabel | Jumlah | Catatan |
|---|---|---|
| `service_task_assignment_logs` | 33 baris | Jejak audit |
| `service_task_assignments` | 16 baris | 9 APPROVED, 3 CANCELLED, 2 REJECTED, 1 REVISI, 1 TERVERIFIKASI |
| `user_retribution_assignments` | 38 (32 aktif) | Master petugas × layanan |
| `petugas_tasks` | 72 baris | |
| `verifications` | 49 baris | 2 dibuat hari ini |

### 2.5 Data uji yang dibersihkan (soft — TIDAK dihapus)
- 6 penetapan master uji → `is_active = false` + `revoked_at`
- 3 penugasan uji (#19, #20, #21) → status `CANCELLED` + catatan alasan
- Total baris `user_retribution_assignments` **tidak berkurang** (tetap 32 lama + 6 uji)
- Backup: `/home/sipanda/backups/autoassign-aa1-20260924_143118/`

---

## 3. PERUBAHAN BACKEND (`retribusi-api`)

Commit: **`81e6670`** — *feat(pajak): alur penugasan lapangan maker-checker + tutup lubang RBAC fail-open*
Push: `main` → `github.com/muhdanfyan/retribusi-api`

### 3.1 Berkas baru

| Berkas | Isi |
|---|---|
| `app/Services/AutoAssignService.php` | Least-loaded assign, `gantiPetugas()`, `daftarPetugasDenganBeban()`, `catatLog()` |
| `app/Services/LapanganVerifikasiService.php` | `ajukanHasil()`, `verifikasi()`, `tolak()`, `antrian()`, `detail()` |
| `app/Http/Controllers/Api/V1/PekerjaanLapanganController.php` | 6 endpoint petugas + Kasubid |
| `app/Http/Controllers/Api/V1/KabidPerObjekController.php` | 5 endpoint Kabid |
| `app/Http/Controllers/Api/V1/PenugasanPetugasController.php` | Master petugas × layanan |
| `database/migrations/2026_09_24_210000_...php` | Migrasi aditif |
| `database/seeders/PatchParkirGroupSeeder.php` | Seed grup parkir |
| `docs/00_kebijakan_strategis/RENCANA_AUTO_ASSIGN_PETUGAS.md` | Rencana induk |

### 3.2 Berkas yang diubah

| Berkas | Perubahan |
|---|---|
| `app/Http/Middleware/EnsureServiceAction.php` | **Fail-open → fail-closed** (lihat §4) |
| `app/Support/ServiceAccessMatrix.php` | Daftarkan role `kabid_pengawas` |
| `app/Services/SlaTrackerService.php` | 3 status baru + 2 tahap SLA |
| `app/Http/Controllers/Api/V1/BidangPajakController.php` | Penyesuaian alur |
| `routes/api.php` | +48 baris rute baru |
| `.gitignore` | Larang `*.bak`, `*.bak.*`, `*.bak-*` |

### 3.3 Status & tahap baru
Status: `MENUNGGU_VERIFIKASI`, `TERVERIFIKASI`, `REVISI`
Tahap SLA: `VERIFIKASI_HASIL_SURVEI`, `VERIFIKASI_HASIL_PENAGIHAN` (12 jam, eskalasi 9 jam)

### 3.4 Endpoint baru

```
# Petugas (tanpa gate service_action)
GET  /api/bidang-pajak/lapangan/tugas-saya
POST /api/bidang-pajak/lapangan/{id}/ajukan

# Kasubid (gate service_action:verify)
GET  /api/bidang-pajak/lapangan/daftar
GET  /api/bidang-pajak/lapangan/{id}
POST /api/bidang-pajak/lapangan/{id}/verifikasi
POST /api/bidang-pajak/lapangan/{id}/tolak        (catatan wajib min 5)

# Kabid (gate service_action:assign)
GET  /api/bidang-pajak/kabid/objek
GET  /api/bidang-pajak/kabid/objek/{id}
GET  /api/bidang-pajak/kabid/objek/{id}/kandidat
POST /api/bidang-pajak/kabid/objek/{id}/konfirmasi
POST /api/bidang-pajak/kabid/objek/{id}/ganti-petugas  (alasan wajib min 5)

# Master petugas × layanan (gate service_action:assign)
GET  /api/bidang-pajak/petugas-layanan
POST /api/bidang-pajak/petugas-layanan/tetapkan
POST /api/bidang-pajak/petugas-layanan/cabut
```

---

## 4. ⚠️ PERBAIKAN KEAMANAN — LUBANG RBAC

### 4.1 Yang ditemukan

Audit dengan uji HTTP nyata per role menemukan: **akun `petugas`
mendapat HTTP 200 di SELURUH endpoint Kabid.**

```
petugas → /bidang-pajak/kabid/objek            → 200  (seharusnya 403)
petugas → /bidang-pajak/otorisasi/daftar       → 200
petugas → /bidang-pajak/petugas-layanan        → 200
petugas → /bidang-pajak/verifikasi/daftar      → 200
```

### 4.2 Akar masalah

`EnsureServiceAction.php` berbunyi:

```php
if (! ServiceAccessMatrix::isServiceScopedRole($role)) {
    return $next($request);   // ← LOLOS
}
```

Komentarnya berasumsi *"role di luar matriks tetap dijaga EnsureAdmin"*.
**Asumsi itu salah** — role tak terdaftar justru melewati gate tanpa
pemeriksaan apa pun.

### 4.3 Dampak terukur

| Role | Jumlah akun | Dampak |
|---|---|---|
| `petugas` | 170 | Tembus semua gate |
| `opd` | 51 | Tembus semua gate |
| `kabid_pengawas` | 1 | Tembus (tidak terdaftar di matriks) |
| `pengawas` | 6 | Tembus |
| `admin_ptsp` | 2 | Tembus |
| `verifikator`, `viewer`, `notaris`, `warga`, `citizen`, `wajib_pajak` | 12 | Tembus |

### 4.4 Perbaikan

`EnsureServiceAction` sekarang **fail-closed**: hanya `super_admin` dan
`admin` yang dikecualikan eksplisit; role lain di luar matriks → **403**.

`ServiceAccessMatrix` didaftarkan role `kabid_pengawas` (domain `all`,
grup `kabid`, tanpa aksi `approve` — otorisasi akhir tetap milik Kabid
Pendapatan wilayah / Kepala Bapenda).

### 4.5 Bukti perbaikan (uji HTTP ulang)

```
petugas              → kabid/objek        → 403 ✅
petugas              → petugas-layanan    → 403 ✅
petugas              → otorisasi/daftar   → 403 ✅
petugas              → verifikasi/daftar  → 403 ✅
petugas              → lapangan/daftar    → 403 ✅
kasubid_penagihan_w1 → kabid/objek        → 403 ✅
kasubid_penagihan_w1 → verifikasi/daftar  → 200 ✅ (sesuai tupoksi)
kasubid_penagihan_w1 → lapangan/daftar    → 200 ✅
kabid_w1             → semua endpoint     → 200 ✅
kabid_w2             → semua endpoint     → 200 ✅
```

---

## 5. PERUBAHAN FRONTEND (`retribusi-admin`)

Commit: **`61e1b2f`** — *feat(pajak): halaman Penugasan Lapangan + perbaikan tampilan verifikasi*
Push: `main` → `github.com/muhdanfyan/retribusi-admin`

### 5.1 Halaman baru
`src/pages/PenugasanLapangan.tsx` (607 baris) — rute `/penugasan-lapangan`,
satu halaman tiga sudut pandang dari `user.role`:

- **PETUGAS** — "Tugas Lapangan Saya", tombol **Ajukan Hasil**
- **KASUBID** — "Antrian Verifikasi Hasil Lapangan", tombol **Verifikasi** / **Kembalikan**
- **KABID** — "Konfirmasi Pekerjaan Lapangan", tombol **Konfirmasi Selesai** / **Alihkan ke Petugas Lain**, plus ringkasan beban

### 5.2 Berkas yang diubah
`src/App.tsx`, `src/components/Layout.tsx`, `src/lib/roleUtils.ts`,
`src/pages/Verification.tsx`

### 5.3 Bug yang diperbaiki

**a) Tombol "Konfirmasi Selesai" tidak pernah muncul**
Endpoint detail membungkus data sebagai `{ tugas: {...} }`, tetapi panel
membaca `r.status` di level atas → selalu `undefined`. **Tanpa error apa
pun di console.** Panel sekarang memakai `t` dari `detail.tugas`.

**b) Blok "Penugasan Survey Lapangan" hilang untuk Kabid**
Gate `selectedFieldSurveyTasks.length > 0` dipasang untuk semua peran,
sehingga Kabid kehilangan form saat belum ada penugasan — padahal itu
satu-satunya pintu membuat penugasan pertama. Diperbaiki:
- **Kabid** → selalu tampil
- **Kasubid** → hanya tampil bila sudah ada penugasan (pemantau, tanpa form)

**c) Endpoint tanpa prefix `/api`** — 10 endpoint, fetch gagal tanpa jejak di log server.

**d) `showToast('pesan','success')`** — signature salah, melempar
`Cannot read properties of undefined (reading 'border')`.

**e) Menu hilang untuk Kasubid/Kabid** — `bidangPajakCanAccessPath()`
fail-closed; path `/penugasan-lapangan` belum bercabang.

### 5.4 Catatan penting
**Tiga dari empat bug di atas lolos `npx tsc --noEmit` bersih.**
Type check bukan bukti aplikasi jalan — verifikasi wajib di browser nyata
dan cek DB setelah aksi tulis.

---

## 6. PERUBAHAN LAIN (23 Sep)

| Commit | Isi |
|---|---|
| `0cb7ce7` | Field roda 2 & roda 4 pada form schema parkir |
| `993dc0f` | RBAC akun kepala OPD — read-only dashboard + command center |
| `722f7fa` | Jangan commit file backup password & `.bak` ke repo |
| `2126d09` | Isolasi wilayah I/II di level query data |
| `dc97123` | Guard penegakan berbasis matriks layanan |
| `774fcd1` | RBAC berbasis layanan — pemisahan tupoksi Kabid vs Kasubid |
| `38bceba` | Daftar role aparatur dari satu sumber + gate lintas wilayah |

---

## 7. PEMBERSIHAN REPO

- **30 file `.bak`** dihapus dari git index (fisik diarsipkan ke `/tmp/bak_arsip_20260924_153811/`)
- `.gitignore` diperketat: `*.bak`, `*.bak.*`, `*.bak-*`, `.env.backup`
- Verifikasi: `git ls-files | grep .bak` → **0 hasil**

---

## 8. YANG BELUM TUNTAS / PERLU KEPUTUSAN

1. **Akun Kasubid Wilayah I & II** — uji login kena HTTP 429 (rate limit),
   belum terbukti passwordnya. Perlu uji ulang dengan jeda, atau reset
   password oleh pengelola.

2. **Delegasi tupoksi `kabid_pengawas`** — sudah didaftarkan di matriks,
   tetapi apakah ia boleh `assign` lintas wilayah perlu konfirmasi. Saat
   ini domainnya `all` sehingga bisa menugaskan di Wilayah I dan II.

3. **`kasubid_pengembangan`** — sebelumnya bergrup `kabid` (punya `approve`
   + `assign`), sudah dikoreksi ke `kasubid`. Perlu konfirmasi apakah
   memang bukan pengambil keputusan otorisasi.

4. **Data uji tertinggal** — tugas #19–#34 di `service_task_assignments`
   masih ada (soft, status `CANCELLED`/`APPROVED`). Bisa dibersihkan
   kalau diinginkan.

5. **`petugas` akun id 56** — dipakai untuk pengujian sesuai arahan
   pengelola, jangan dinonaktifkan.

---

## 9. BERKAS PENDUKUNG

| Berkas | Isi |
|---|---|
| `mpad-operations/references/autoassign-petugas-20260924.md` | 6 pitfall alur lapangan |
| `mpad-operations/references/frontend-admin-pola-wajib-20260924.md` | Pola wajib FE + 4 jebakan |
| `docs/00_kebijakan_strategis/RENCANA_AUTO_ASSIGN_PETUGAS.md` | Rencana induk (di repo) |
| `/home/sipanda/backups/autoassign-aa1-20260924_143118/` | Backup sebelum perubahan |
| `/tmp/bak_arsip_20260924_153811/` | Arsip file `.bak` |

---

*Disusun 24 September 2026*
