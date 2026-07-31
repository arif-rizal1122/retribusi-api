# Struktur Retribusi Daerah — Halaman Layanan-Retribusi

> Dokumen acuan implementasi tabulasi Retribusi di halaman `/layanan-retribusi`.
> Disusun berdasarkan **Perda No. 1 Tahun 2024 tentang Pajak Daerah dan Retribusi Daerah Kota Baubau** dan **Pasal 88 ayat (3) & Pasal 87 ayat (1) UU No. 1 Tahun 2022 (UU HKPD)**.
> Sumber teks pasal: https://djpk.kemenkeu.go.id/wp-content/uploads/2022/01/Salinan-UU-Nomor-1-Tahun-2022.pdf

---

## 1. Struktur Retribusi Daerah — 3 Kelompok

Retribusi daerah diklasifikasikan menjadi **3 kelompok** (UU HKPD Pasal 86–89):

1. **Retribusi Jasa Umum** — pelayanan publik dasar yang disediakan Pemda.
2. **Retribusi Jasa Usaha** — penyediaan/pelayanan barang dan/atau jasa yang bersifat komersial.
3. **Retribusi Perizinan Tertentu** — pelayanan perizinan tertentu oleh Pemda.

---

## 2. Retribusi Jasa Umum — 4 Jenis Aktif di M-PAD

Hanya **4 jenis** yang diimplementasikan (nomor 4, 6, 7 dari daftar awal **dihapus**):

| No | Jasa | Perda | Kode DB |
|----|------|-------|---------|
| 1 | Pelayanan Kesehatan | Perda 16/2012 | — |
| 2 | Pelayanan Persampahan/Kebersihan | Perda 17/2012 | `RET-SMP` (198) |
| 3 | Pelayanan Parkir Tepi Jalan Umum | Perda 20/2012 jo. 2/2018 | `RET-PRK` (199) |
| 4 | Pelayanan Pasar | Perda 26/2012 jo. 8/2016 | — |

**Yang dihapus (tidak ditampilkan):**
- ~~Pengujian Kendaraan Bermotor~~ (Perda 24/2012 jo. 5/2018)
- ~~Tera/Tera Ulang~~ (Perda 28/2012)
- ~~Pengendalian Menara Telekomunikasi~~ (Perda 29/2012)

---

## 3. Retribusi Jasa Usaha — Objek Pasal 88 ayat (3) UU 1/2022

Jenis penyediaan/pelayanan barang dan/atau jasa yang menjadi objek Retribusi Jasa Usaha (Pasal 87 ayat (1) huruf b):

- **a.** Penyediaan tempat kegiatan usaha berupa pasar grosir, pertokoan, dan tempat kegiatan usaha lainnya.
- **b.** Penyediaan tempat pelelangan ikan, ternak, hasil bumi, dan hasil hutan termasuk fasilitas lainnya dalam lingkungan tempat pelelangan.
- **c.** Penyediaan tempat khusus parkir di luar badan jalan.
- **d.** Penyediaan tempat penginapan/pesanggrahan/vila.
- **e.** Pelayanan rumah pemotongan hewan ternak.
- **f.** Pelayanan jasa kepelabuhanan.
- **g.** Pelayanan tempat rekreasi, pariwisata, dan olahraga.
- ~~**h.** Pelayanan penyeberangan orang atau barang dengan menggunakan kendaraan di air~~ — **DITIADAKAN di Baubau** (tidak ada layanannya).
- **i.** Penjualan hasil produksi usaha Pemerintah Daerah.
- **j.** Pemanfaatan aset Daerah yang tidak mengganggu penyelenggaraan tugas dan fungsi OPD dan/atau optimalisasi aset Daerah dengan tidak mengubah status kepemilikan.

**Total objek Jasa Usaha yang diimplementasikan: 9** (a–g, i–j; huruf h ditiadakan).

### Pemetaan ke Perda Baubau

| Objek | Deskripsi | Perda Terkait |
|-------|-----------|---------------|
| a | Tempat kegiatan usaha (pasar grosir, pertokoan) | Perda 26/2012 (pasar) / Perda 1/2024 |
| b | Tempat pelelangan | — |
| c | Tempat khusus parkir (luar badan jalan) | Perda 21/2012 jo. 3/2018 |
| d | Penginapan/pesanggrahan/vila | — |
| e | Rumah pemotongan hewan | Perda 31/2012 |
| f | Jasa kepelabuhanan | Perda 25/2012 jo. 6/2018 |
| g | Rekreasi, pariwisata, olahraga | Perda 32/2012 jo. 5/2020 |
| ~~h~~ | ~~Penyeberangan air~~ | **Ditiadakan** |
| i | Penjualan hasil produksi Pemda | — |
| j | Pemanfaatan aset daerah | Perda 30/2012 jo. Perwal 19/2016 |

---

## 4. Retribusi Perizinan Tertentu — Objek Pasal 87 ayat (1) huruf c

Jenis pelayanan pemberian izin yang menjadi objek Retribusi Perizinan Tertentu:

- **a.** Persetujuan bangunan gedung (PBG).
- **b.** Penggunaan tenaga kerja asing (TKA).

**Total objek Perizinan Tertentu yang diimplementasikan: 2** (a–b).

### Perubahan & Catatan

- **Izin Gangguan** (Perda 34/2012) — ❌ **TIDAK BERLAKU** (terhapus, tidak diimplementasikan).
- **Izin Trayek** (Perda 23/2012) — ❌ **TIDAK BERLAKU** (tidak diimplementasikan).
- **Izin Usaha Perikanan** (Perda 35/2012) — 🔄 **GANTI NAMA** (menyesuaikan regulasi terbaru; verifikasi penamaan final).
- **IMB** — digantikan konsep **Persetujuan Bangunan Gedung (PBG)** sesuai objek (a).

---

## 5. Struktur Tabulasi Halaman Layanan-Retribusi

```
Tab: [Pajak] [Retribusi] [Aset lainnya]
             │
             ├─ 🔵 Jasa Umum (4)
             │   • Pelayanan Kesehatan
             │   • Pelayanan Persampahan/Kebersihan
             │   • Pelayanan Parkir Tepi Jalan Umum
             │   • Pelayanan Pasar
             │
             ├─ 🟢 Jasa Usaha (9 — Pasal 88(3) UU 1/2022)
             │   • Tempat Kegiatan Usaha (a)
             │   • Tempat Pelelangan (b)
             │   • Tempat Khusus Parkir (c)
             │   • Penginapan/Pesanggrahan/Vila (d)
             │   • Rumah Pemotongan Hewan (e)
             │   • Jasa Kepelabuhanan (f)
             │   • Rekreasi, Pariwisata & Olahraga (g)
             │   • Penjualan Hasil Produksi Pemda (i)
             │   • Pemanfaatan Aset Daerah (j)
             │
             └─ 🟠 Perizinan Tertentu (2 — Pasal 87(1)c)
                 • Persetujuan Bangunan Gedung (PBG)
                 • Penggunaan Tenaga Kerja Asing (TKA)
```

### Catatan Implementasi

1. **Tab Pajak** — tidak berubah, tetap seperti biasa (PBB-P2, BPHTB, Pajak Reklame, MBLB, Walet, Opsen, PBJT, Air Tanah, dll).
2. **Tab Retribusi** — 3 sub-grup: Jasa Umum (4), Jasa Usaha (9), Perizinan Tertentu (2).
3. **Tab Aset lainnya** — layanan terkait aset daerah.
4. **Grouping backend**: service `category` diarahkan ke nilai: `pajak`, `jasa_umum`, `jasa_usaha`, `perizinan`, `aset` — frontend cukup render grouping.
5. **Jasa Usaha huruf h (penyeberangan air)**: dipastikan tidak ada di data/klasifikasi aktif M-PAD.

---

*Dokumen ini bagian dari harmonisasi regulasi Perda 1/2024 & UU 1/2022 (HKPD). Perbarui jika ada perubahan peraturan atau keputusan pengelola.*
