# Rencana Implementasi: Auto-Assign Petugas Berbasis Layanan (Adil)

**Tanggal:** 24 September 2026 (revisi setelah keputusan pengelola)
**Status:** Rencana final — siap eksekusi
**Dasar keputusan:** Arahan pengelola (7 poin, 24 Sep 2026)
**Dasar hukum:** Perwali Baubau No. 8/2025, Perda No. 1/2024, Perwali No. 11/2025

---

## 0. Keputusan Pengelola (mengikat)

| # | Poin | Keputusan |
|---|---|---|
| 1 | Jumlah petugas per objek | **Satu objek = satu petugas** (PIC tunggal) |
| 2 | Petugas boleh menolak? | **Tidak.** Tidak ada tombol tolak. Dilaporkan lewat indikator "tidak responsif" ke Kabid |
| 3 | Tidak ada petugas kompeten | **Eskalasi otomatis ke Kabid** |
| 4 | Basis hitung beban | **Total lintas layanan** (bukan per layanan) |
| 5 | Urutan keadilan | **Frekuensi penugasan BULAN INI paling sedikit didahulukan**, seri → **acak** |
| 6 | Backfill data historis | **Frekuensi jumlah penugasan bulan ini saja** — bukan klaim kompetensi permanen |
| 7 | Jejak pergantian petugas | **Tabel audit terpisah**, bukan kolom notes |

---

## 1. Prinsip Dasar

Pembagian wilayah Kabid/Kasubid **BERBASIS JENIS LAYANAN/PAJAK, bukan lokasi geografis.**

| Wilayah | Domain | Klasifikasi |
|---|---|---|
| Wilayah I | `wilayah_1` | PBB-P2 (186), BPHTB (187), REKLAME (188), MBLB (189), WALET (190), OPSEN (191) |
| Wilayah II | `wilayah_2` | PBJT-PRK (172), PBJT-FOOD (192), PBJT-HTL (193), PBJT-HBR (194), PBJT-HBR-SP (195), PBJT-NON-PLN (196), AIR-TANAH (197), JU-PRK (205), RET-PRK (199) |

Pemetaan ada di `app/Support/TaxDomainHelper.php`. **Tidak ada tabel wilayah baru.** Wilayah tidak perlu disimpan di petugas: kalau petugas hanya di-assign ke layanan Wilayah II, dia otomatis tidak akan menerima objek Wilayah I.

---

## 2. Alur Kerja

```
[KABID] tetapkan master: petugas X pegang layanan {A, B, C}
   |
   v
[OBJEK MASUK] klasifikasi = B
   |
   v
[SISTEM] auto-assign:
     pool = petugas yang pegang B (dan aktif)
     saring: pembuat objek dikecualikan (anti self-assign)
     urutkan: frekuensi bulan ini terkecil  <-- UTAMA
              beban aktif terkecil          <-- penyaring kedua
              ACAK di antara yang seri      <-- anti "selalu orang sama"
   |                              \
   |                               -> tidak ada petugas kompeten?
   v                                    eskALASI ke antrian Kabid
[PETUGAS] kerjakan -> unggah dokumen
   |
   v
[KASUBID] cek & verifikasi dokumen -> teruskan
   |
   v
[KABID] konfirmasi / ganti petugas (jejak ke tabel audit)
```

Dua kendali Kabid:
1. **Master** — siapa pegang layanan apa (berlaku umum, sekali set)
2. **Per-objek** — ganti PIC bila perlu (pengecualian, alasan wajib)

---

## 3. Algoritma Keadilan (inti)

### 3.1 Kenapa frekuensi bulan ini, bukan sekadar beban aktif

Least-loaded biasa ("siapa yang sisa tugasnya paling sedikit") hanya melihat **kondisi sekarang**. Masalahnya: petugas yang cepat menyelesaikan tugas akan terus terlihat "lowong" dan dijejali tugas baru, sementara yang lambat terbebas. Akibatnya beban kumulatif jadi tidak seimbang.

Menghitung **frekuensi penugasan bulan ini** memperbaiki itu: sistem melihat riwayat, bukan hanya sisa pekerjaan. Yang sudah banyak ditugaskan bulan ini — meskipun sekarang lowong — tetap di belakang.

### 3.2 Urutan pengurutan

1. **Frekuensi bulan ini** (ASC) — indikator keadilan utama
2. **Beban aktif** (ASC) — penyaring kedua, mencegah penumpukan di hari yang sama
3. **Acak** di antara yang seri — mencegah selalu orang yang sama

Pengacakan memakai `random_int()`. Tanpa ini, dua petugas seri akan selalu dipilih berdasarkan urutan ID, dan hasilnya bisa diprediksi/diketahui pihak yang ingin menyiasati distribusi.

### 3.2.1 Nilai 0 WAJIB ditampilkan (arahan pengelola 24 Sep 2026)

Petugas dengan **frekuensi 0 tetap ditampilkan dengan nilai 0** — tidak dikosongkan, tidak disembunyikan.

Alasan: nilai 0 berarti "belum pernah ditugaskan bulan ini", yaitu **petugas paling jarang** — dan justru merekalah yang **didahulukan** pada penugasan berikutnya. Kalau dikosongkan atau barisnya dihilangkan, Kabid justru kehilangan informasi paling relevan untuk menilai keadilan distribusi.

Konsekuensi teknis:
- `frekuensiBulanIni()` mengisi `?? 0` untuk setiap petugas di daftar — tidak pernah menghilangkan key.
- `daftarPetugasDenganBeban()` menyertakan `frekuensi_bulan_ini: 0` secara eksplisit per baris.
- Field `prioritas: true` ditandai untuk petugas dengan frekuensi terkecil — jadi Kabid langsung melihat siapa yang harus didahulukan.
- Urutan daftar: frekuensi ASC → beban ASC → user_id ASC (yang paling jarang di paling atas).

### 3.3 Di mana frekuensi diambil

Tabel `service_task_assignment_logs` — event `ASSIGNED` dan `REASSIGNED`, dibatasi `created_at >= awal bulan`. Satu query GROUP BY (bukan N+1).

Kenapa dari tabel audit, bukan dari `service_task_assignments`: tabel penugasan hanya menyimpan kondisi terkini, sedangkan audit menyimpan **seluruh peristiwa**. Kalau penugasan dialihkan, tabel penugasan kehilangan jejak PIC awal — audit tidak.

### 3.4 Beban lintas layanan

Petugas yang memegang Reklame + PBB-P2 dihitung **total** tugasnya (5 Reklame + 3 PBB = 8), dibandingkan petugas lain lintas layanan yang sama. Bukan 5 dibanding petugas Reklame lain dan 3 dibanding petugas PBB lain.

Alasan: yang dibagi rata adalah **beban orang**. Kalau per layanan, petugas yang memegang 3 layanan bisa menerima tugas "adil" dari masing-masing layanan, tetapi totalnya 3x lipat petugas yang memegang 1 layanan.

---

## 4. Skema Data

### 4.1 `user_retribution_assignments` — master Kabid

Kolom existing: `id, user_id, retribution_type_id, retribution_classification_id, created_at, updated_at`

27 baris lama hampir semua hanya isi `retribution_type_id` (16=Pajak, 17=Retribusi Jasa Umum). Level type terlalu luas (type 16 mencakup SEMUA pajak sekaligus), jadi tidak bisa dipakai untuk pembagian per jenis pajak. **27 baris lama TIDAK dihapus** — dibiarkan utuh sebagai riwayat.

Kolom tambahan (migrasi aditif, semua nullable/default):

| Kolom | Fungsi |
|---|---|
| `assigned_by_user_id` | siapa (Kabid) yang menetapkan |
| `is_active` | nonaktifkan tanpa hapus |
| `revoked_at` | kapan dicabut |

Index: `(user_id, is_active)`, `(retribution_classification_id, is_active)`.

### 4.2 `service_task_assignments` — penugasan objek

Sudah ada (migrasi `2026_09_23_200000`), kosong, struktur mencukupi. Dipakai apa adanya. Satu baris = satu PIC (= satu objek, keputusan #1).

### 4.3 `service_task_assignment_logs` — TABEL AUDIT (baru)

Keputusan #7: jejak di tabel terpisah, bukan `notes`.

| Kolom | Fungsi |
|---|---|
| `assignment_id` | -> service_task_assignments.id |
| `event` | ASSIGNED, REASSIGNED, CONFIRMED, VERIFIED, FORWARDED, ESCALATED, COMPLETED |
| `actor_user_id` | siapa yang melakukan |
| `from_user_id` / `to_user_id` | PIC sebelum/sesudah (REASSIGNED) |
| `stage_from` / `stage_to` | perpindahan tahap |
| `status_from` / `status_to` | perpindahan status |
| `reason` | alasan (WAJIB untuk REASSIGNED) |
| `meta` (json) | data tambahan (frekuensi, beban, klasifikasi, domain) |

Baris audit **tidak pernah dihapus**. Ini juga jadi sumber hitung frekuensi bulanan.

---

## 5. Petugas Tidak Bisa Menolak (keputusan #2)

Tidak ada endpoint "tolak tugas" dan tidak ada kolom penolakan atas permintaan petugas.

**Usulan best practice Aiman:**

Penolakan tugas biasanya menandakan salah satu dari dua hal: **(a)** beban petugas tidak wajar, atau **(b)** kompetensi tidak cocok. Keduanya ranah Kabid, bukan petugas — jadi diselesaikan lewat jalur pengawasan, bukan tombol tolak.

Yang disediakan sebagai gantinya:

1. **Indikator "tidak responsif"** — muncul di antrian Kabid kalau penugasan melewati `sla_deadline` (kolom sudah ada) tanpa perubahan status. Kabid lalu ganti petugas.
2. **Jalur eskalasi otomatis** — kolom `escalation_alert_at` dan `escalated_at` sudah ada di skema.
3. **Catatan petugas** (bukan penolakan) — kolom `notes` di `service_task_assignments` untuk kendala lapangan ("lokasi tidak ditemukan", "pemilik tidak di tempat"). Ini informasi, bukan penolakan.

Kalau nanti terbukti petugas tetap perlu jalur resmi menolak (misal benturan kepentingan), tambahkan event `DECLINED` di tabel audit + wajib alasan, dan penugasan otomatis kembali ke antrian Kabid. Belum diimplementasikan sekarang.

---

## 6. Tidak Ada Petugas Kompeten (keputusan #3)

`AutoAssignService::pilihPetugas()` mengembalikan `butuh_eskalasi = true` dan `Log::warning`. **Tidak gagal senyap.**

Di UI: objek masuk **antrian Kabid** bertanda "Butuh penetapan petugas" dengan keterangan klasifikasinya. Kabid lalu:
- menetapkan petugas ke layanan itu (master), lalu auto-assign dijalankan ulang, atau
- menugaskan manual langsung ke petugas tertentu.

Tidak ada objek yang menggantung tanpa terlihat.

---

## 7. Backfill (keputusan #6)

Sesuai arahan Bang: backfill **HANYA frekuensi jumlah penugasan bulan ini** — bukan klaim kompetensi.

Kenapa ini lebih benar daripada usulan Aiman sebelumnya (yang menganggap petugas kompeten suatu layanan karena pernah mengerjakan objeknya): 72 baris `petugas_tasks` **semuanya masih `pending`** — belum ada bukti pekerjaan selesai, jadi "pernah ditugaskan" tidak sama dengan "kompeten". Menyimpulkan kompetensi dari data itu berisiko.

Rencana eksekusi:
1. Hitung frekuensi penugasan bulan ini per petugas dari `petugas_tasks` (`created_at` bulan berjalan).
2. Tulis sebagai baris `ASSIGNED` di `service_task_assignment_logs` (dengan `meta.sumber = 'backfill_petugas_tasks'`), supaya ikut diperhitungkan algoritma keadilan.
3. **Kompetensi (master petugas x layanan) TETAP ditetapkan Kabid secara manual** — 13 klasifikasi masih terjangkau dikerjakan manual.
4. Sebelum dijalankan: **butuh persetujuan pengelola** karena menyentuh data historis.

---

## 8. Endpoint API

Semua endpoint WAJIB memakai guard yang sudah ada: `tax_domain` (`EnsureTaxDomain`, fail-closed 403), `EnsureRole`, `EnsureOpdScope`, `EnsureAdmin`.

### 8.1 Kabid — master penugasan layanan

| Method | Route | Fungsi |
|---|---|---|
| GET | `/api/penugasan-petugas/petugas` | Daftar petugas + layanan yang dipegang + frekuensi bulan ini |
| GET | `/api/penugasan-petugas/layanan` | Daftar klasifikasi dalam domain Kabid |
| POST | `/api/penugasan-petugas/tetapkan` | Bulk: satu petugas ↔ banyak klasifikasi (idempotent) |
| POST | `/api/penugasan-petugas/cabut` | Soft nonaktifkan (`is_active=false` + `revoked_at`) |

Validasi `tetapkan`: klasifikasi yang dikirim **harus** ada di `classification_ids` domain Kabid → kalau tidak, 403. Mencegah Kabid Wilayah II menetapkan petugas untuk layanan Wilayah I.

Idempotent: kirim ulang daftar sama tidak menggandakan baris. Baris `is_active=false` diaktifkan kembali (bukan insert baru).

### 8.2 Kabid — per objek

| Method | Route | Fungsi |
|---|---|---|
| GET | `/api/penugasan-objek` | Daftar penugasan dalam domain (+ penanda eskalasi) |
| POST | `/api/penugasan-objek/{id}/konfirmasi` | Konfirmasi penugasan |
| POST | `/api/penugasan-objek/{id}/ganti-petugas` | Ganti PIC, **alasan wajib**, jejak ke tabel audit |
| GET | `/api/penugasan-objek/{id}/riwayat` | Riwayat lengkap dari tabel audit |

### 8.3 Kasubid

| Method | Route | Fungsi |
|---|---|---|
| GET | `/api/antrian-penugasan` | Penugasan masuk di domain-nya |
| POST | `/api/antrian-penugasan/disposisi` | Tugaskan petugas (pemicu auto-assign) |
| POST | `/api/antrian-penugasan/{id}/verifikasi` | Verifikasi dokumen hasil kerja petugas |
| POST | `/api/antrian-penugasan/{id}/teruskan` | Teruskan ke Kabid |

### 8.4 Pemicu auto-assign

Jalankan **saat Kasubid memverifikasi L1 dan memutuskan perlu survei lapangan** — bukan saat objek didaftarkan.

Alasan: objek baru belum tentu valid, sehingga menugaskan petugas sebelum verifikasi dokumen berpotensi membuang waktu lapangan. Kasubid memang pemegang tupoksi "Disposisi tugas survei" (dokumen tupoksi 4.1 butir 4). Endpoint manual tetap disediakan untuk fleksibilitas.

---

## 9. Pembagian UI

| Aktor | Kebutuhan |
|---|---|
| **Kabid** | "Penugasan Petugas": pilih petugas → centang layanan (multi) + lihat frekuensi bulan ini per petugas. "Penugasan Objek": daftar + konfirmasi + ganti petugas + riwayat. Penanda "butuh penetapan". |
| **Kasubid** | "Antrian Penugasan": tugas masuk, tombol disposisi/verifikasi/teruskan. |
| **Petugas** | "Tugas Saya": daftar tugas + unggah dokumen + catatan kendala (bukan tolak). |

Halaman FE perlu ditelusuri di `/home/sipanda/retribusi-mobile/src`.

---

## 10. Urutan Implementasi

| Fase | Isi | Downtime | Risiko |
|---|---|---|---|
| 1 | Migrasi: 3 kolom di `user_retribution_assignments` + tabel `service_task_assignment_logs` | Tidak | Rendah — aditif, nullable/default |
| 2 | `AutoAssignService` (frekuensi + beban + acak) + uji unit | Tidak | Rendah — kode baru, belum dipanggil |
| 3 | Endpoint Kabid master penugasan + UI | Tidak | Sedang — mulai mengubah data |
| 4 | Pemicu auto-assign saat disposisi Kasubid | Tidak | Sedang — mulai membuat penugasan nyata |
| 5 | Endpoint Kasubid (verifikasi, teruskan) | Tidak | Sedang |
| 6 | Endpoint Kabid (konfirmasi, ganti petugas, riwayat) + UI | Tidak | Sedang |
| 7 | Backfill frekuensi bulan ini (setelah persetujuan) | Tidak | Terkandung keputusan |

Deploy FE butuh rebuild (`npm run build` → rsync ke `/var/www/retribusi-mobile`) yang mengubah bundle production — sebaiknya di luar jam layanan.

---

## 11. Yang TIDAK Dilakukan

- ❌ Tidak membuat tabel wilayah baru
- ❌ Tidak membagi wilayah berdasarkan kecamatan / lokasi geografis
- ❌ Tidak memakai zona `KKS-Z1/Z2/Z3` sebagai peta wilayah
- ❌ Tidak menghapus 27 baris `user_retribution_assignments` lama
- ❌ Tidak mengubah skema `petugas_tasks`
- ❌ Tidak menghapus/menonaktifkan akun petugas mana pun
- ❌ Tidak menyediakan tombol "tolak tugas" untuk petugas
- ❌ Tidak menganggap "pernah ditugaskan" = "kompeten" saat backfill
