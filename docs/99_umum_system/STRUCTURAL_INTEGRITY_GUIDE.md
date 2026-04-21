# 🏛️ MPAD Structural & Hierarchical Integrity Guide
**Version**: 2.0 (Wilayah-Centric)
**Status**: ACTIVE PROTOCOL

## 1. Arsitektur Hirarki 4-Tingkat
Sistem MPAD (Retribusi) Kota Baubau dibangun di atas 4 tingkatan hirarki yang **TIDAK BOLEH** diubah strukturnya guna menjaga konsistensi pelaporan keuangan (SIPD/BPK).

| Level | Nama | Keterangan | Aturan Mutlak |
| :--- | :--- | :--- | :--- |
| **L1** | **Wilayah** | Pembagian administratif utama. | Hanya ada 2: **Wilayah I** & **Wilayah II**. |
| **L2** | **OPD** | Organisasi Perangkat Daerah. | Terikat pada Wilayah & Jenis Pajak. |
| **L3** | **Klasifikasi** | Jenis Pajak/Retribusi (PBJT, Parkir, dll). | **JANGAN DIHAPUS**. Gunakan mapping relasi. |
| **L4** | **Tarif (Rates)** | Nominal atau persentase tarif. | Berdasarkan Zonasi/Kelas Jalan. |

## 2. Aturan Relasi Database (Wilayah-Centric)
Aplikasi ini beralih dari model "Jenis Pajak Global" ke model "Wilayah-Centric".
- **Jenis Pajak (Types)**: Sekarang hanya direpresentasikan oleh dua entitas utama di tabel `retribution_types`:
  1. `Wilayah I` (Kecamatan: Wolio, Murhum, Betoambari, Batupoaro)
  2. `Wilayah II` (Kecamatan: Kokalukuna, Sorawolio, Lea-Lea, Bungi)
- **Mapping Klasifikasi**: Seluruh klasifikasi pajak yang ada (Pajak Hotel, Restoran, Parkir, dll) harus dikaitkan ke salah satu dari dua Wilayah tersebut.
- **Data Persistence**: Menghapus klasifikasi akan memutus relasi pada tabel `bills` dan `payments` historis. **Hanya lakukan penonaktifan (is_active = 0) atau relinking, bukan deletion.**

## 3. Protokol Keamanan Data
Setiap operasi yang bersifat destruktif terhadap database (`truncate`, `drop`, `delete` tanpa soft-delete) wajib:
1. Menampilkan peringatan: `⚠️ PERINGATAN: Operasi ini akan menghapus data historis penagihan!`
2. Memerlukan konfirmasi eksplisit dari user/agen.

## 4. Pipeline Sinkronisasi & Testing
Urutan sinkronisasi yang sah:
1. **LOCAL**: Run `qa7_ultimate_mpad_audit.php` & `run_role_e2e_test.php`.
2. **DEV**: Sinkronkan Local ke Dev branch. Jalankan testing integrasi.
3. **STAGING**: Sinkronkan Dev/Local ke Staging. Lakukan User Acceptance Test (UAT).
4. **PRODUCTION**: 
   - Sinkronkan kode sumber.
   - **DATABASE**: Jangan lakukan sinkronisasi data/migrasi destruktif ke Production sebelum verifikasi Staging selesai 100%.

---
*Dokumen ini merupakan Source of Truth untuk pengembangan struktural MPAD.*
