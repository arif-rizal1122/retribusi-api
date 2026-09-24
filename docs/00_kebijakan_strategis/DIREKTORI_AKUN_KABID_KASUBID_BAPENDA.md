# Direktori & Matriks Akun Struktural Bapenda Kota Baubau
## Sistem M-PAD (Manajemen Pendapatan Asli Daerah)

**Sumber Data:** Server Produksi M-PAD (`157.10.252.74` / `apimpad.baubaukota.go.id`)  
**Sinkronisasi Lokal:** 24 September 2026  
**Dasar Hukum:**
* Perwali Kota Baubau No. 8 Tahun 2025 (Struktur Organisasi & Tata Kerja Bapenda)
* Perda Kota Baubau No. 1 Tahun 2024 (Pajak Daerah & Retribusi Daerah)

---

## 1. Ringkasan Akun Struktural Bapenda

Seluruh data akun pejabat dan aparatur Bapenda berikut ditarik langsung dari tabel `users` database produksi dan telah sinkron 100% dengan database lokal (`retribusi`):

| ID | Nama Pejabat / Akun | Email Resmi | Role Sistem | Domain Wilayah | Siklus & Tupoksi Utama |
| :---: | :--- | :--- | :--- | :---: | :--- |
| **289** | **Muhammad Massad, SE., M.Si** | `kepala.bapenda@baubaukota.go.id` | `kepala_bapenda` | `all` | **Pimpinan Bapenda**: Otorisasi akhir kebijakan, persetujuan lintas bidang, dispensasi/penghapusan denda kota |
| **298** | **Kabid Pendapatan Wilayah I (Ibu Ochi)** | `kabid_pajak@mpad.online` | `kabid_wilayah_1` | `wilayah_1` | **Otorisasi L3 & Penugasan**: PBB-P2, BPHTB, Reklame, MBLB, Sarang Walet, Opsen PKB/BBNKB |
| **302** | **Kabid Pendapatan Wilayah II** | `kabid_wilayah2@mpad.online` | `kabid_wilayah_2` | `wilayah_2` | **Otorisasi L3 & Penugasan**: PBJT (Makan-Minum, Listrik, Hotel, Parkir, Hiburan), Air Tanah, Retribusi Aset |
| **23** | **Kabid Pengawas** | `kabid@retribusi.id` | `kabid_pengawas` | `all` | **Pengawasan Daerah**: Wasdal kepatuhan, pemeriksaan uji petik, dan penegakan perda |
| **303** | **Kasubid Pendataan & Penetapan Wil. I** | `kasubid.pendataan.w1@mpad.online` | `kasubid_pendataan_wil1` | `wilayah_1` | **L2 Hulu (Wil I)**: Verifikasi berkas pendaftaran, validasi NOPD/spasial, kalkulasi SKPD/SKRD |
| **304** | **Kasubid Penagihan & Pelaporan Wil. I** | `kasubid.penagihan.w1@mpad.online` | `kasubid_penagihan_wil1` | `wilayah_1` | **L2 Hilir (Wil I)**: Monitoring pembayaran, piutang, verifikasi setoran, surat teguran |
| **305** | **Kasubid Pendataan & Penetapan Wil. II** | `kasubid.pendataan.w2@mpad.online` | `kasubid_pendataan_wil2` | `wilayah_2` | **L2 Hulu (Wil II)**: Verifikasi pendaftaran PBJT, validasi omzet usaha, verifikasi laporan mandiri SPTPD |
| **306** | **Kasubid Penagihan & Pelaporan Wil. II** | `kasubid.penagihan.w2@mpad.online` | `kasubid_penagihan_wil2` | `wilayah_2` | **L2 Hilir (Wil II)**: Monitoring setoran harian PBJT, piutang, STPD, rekonsiliasi penerimaan |
| **297** | **Kasubid Pendataan (Ibu Salma)** | `kasubid_pendataan@mpad.online` | `kasubid_pendataan` | `all` | **Checker L2 Lintas Bidang**: Verifikasi data PBB-P2 & integrasi SIMPBB |
| **307** | **Kasubid Koordinasi & Pengembangan** | `kasubid.pengembangan@mpad.online` | `kasubid_pengembangan` | `all` | **Teknis & Evaluasi**: Integrasi perbankan H2H, simulator tarif, pemetaan potensi objek baru |
| **24** | **Kasubid Pengawas** | `kasubid@retribusi.id` | `kasubid_pengawas` | `all` | **Pengawasan Lapangan**: Wasdal operasional petugas dan kepatuhan wajib pajak |
| **220** | **Nurmala** | `kasubidpelayanan@mpad.online` | `petugas` | — | **Pelayanan Operasional Bapenda** |

---

## 2. Rincian Portofolio & Pembagian Wilayah

### Wilayah I (Fokus: Berbasis Objek, Dokumen, dan Alas Hak)
* **Kepala Bidang:** Kabid Wilayah I (`id: 298`, `kabid_wilayah_1`)
* **Pengawas Teknis L2:**
  * Hulu: `kasubid_pendataan_wil1` (`id: 303`)
  * Hilir: `kasubid_penagihan_wil1` (`id: 304`)
* **Daftar Klasifikasi Pajak:**
  | ID | Kode Klasifikasi | Nama Jenis Pajak | Karakteristik Penetapan |
  | :---: | :--- | :--- | :--- |
  | **186** | `PBB-P2` | Pajak Bumi dan Bangunan P2 | Official Assessment (Ketetapan SPPT) |
  | **187** | `BPHTB` | Bea Perolehan Hak atas Tanah & Bangunan | Self Assessment via Notaris / PPAT H2H |
  | **188** | `REKLAME` | Pajak Reklame | Official Assessment (SKPD Masa/Tetap) |
  | **189** | `MBLB` | Pajak Mineral Bukan Logam & Batuan | Official / Self Assessment Berdasarkan Tonase |
  | **190** | `WALET` | Pajak Sarang Burung Walet | Penetapan berdasarkan hasil panen |
  | **191** | `OPSEN-PKB` / `OPSEN-BBNKB` | Opsen Pajak Kendaraan Bermotor | Bagi hasil Samsat Provinsi |

---

### Wilayah II (Fokus: Berbasis Transaksi, Omzet, dan Jasa Usaha)
* **Kepala Bidang:** Kabid Wilayah II (`id: 302`, `kabid_wilayah_2`)
* **Pengawas Teknis L2:**
  * Hulu: `kasubid_pendataan_wil2` (`id: 305`)
  * Hilir: `kasubid_penagihan_wil2` (`id: 306`)
* **Daftar Klasifikasi Pajak:**
  | ID | Kode Klasifikasi | Nama Jenis Pajak | Karakteristik Penetapan |
  | :---: | :--- | :--- | :--- |
  | **172** | `PBJT-PRK` | PBJT — Jasa Parkir | Berbasis Omzet / Tarif Parkir Khusus |
  | **192** | `PBJT-FOOD` | PBJT — Makanan dan Minuman (Restoran/Kafe) | Self Assessment SPTPD (10% Omzet) |
  | **193** | `PBJT-HTL` | PBJT — Jasa Perhotelan | Self Assessment SPTPD (10% Omzet) |
  | **194** | `PBJT-HBR` | PBJT — Kesenian & Hiburan | Self Assessment (Bioskop, Karaoke, dll) |
  | **195** | *(Khusus)* | Hiburan Malam Khusus | Penetapan Tarif Khusus Perda 1/2024 |
  | **196** | `PBJT-LISTRIK` | PBJT — Tenaga Listrik (Non-PLN) | Berbasis KWH Produksi Mandiri |
  | **197** | `AIR-TANAH` | Pajak Air Tanah | Berbasis Volume Meteran Kubikasi |

---

## 3. Matriks Hak Akses & Gate Otorisasi (RBAC)

Sistem M-PAD menerapkan prinsip **Segregation of Duties (Pemisahan Wewenang Maker-Checker-Approver)**:

```
[WP / Petugas Lapangan]  ──(L1: Ajukan Hasil/Data)──>  
[Kasubid Wilayah]        ──(L2: Verifikasi & Rekomendasi)──>  
[Kabid Wilayah]          ──(L3: Otorisasi & Penetapan SKPD)──>  
[Kepala Bapenda]         ──(L4: Kebijakan Khusus / Pembebasan Denda)
```

### Matriks Wewenang Aksi (`EnsureServiceAction`):

| Role Aparatur | Action `view` | Action `verify` (L2) | Action `assign` (Petugas) | Action `approve` (L3) |
| :--- | :---: | :---: | :---: | :---: |
| `kepala_bapenda` | ✅ Seluruh Wilayah | ✅ Seluruh Wilayah | ✅ Seluruh Wilayah | ✅ L3 & L4 |
| `kabid_wilayah_1` | ✅ Wilayah I Saja | ❌ | ✅ Wilayah I Saja | ✅ Wilayah I Saja |
| `kabid_wilayah_2` | ✅ Wilayah II Saja | ❌ | ✅ Wilayah II Saja | ✅ Wilayah II Saja |
| `kabid_pengawas` | ✅ Seluruh Wilayah | ❌ | ❌ (Hanya Pengawasan) | ❌ |
| `kasubid_pendataan_wil1` | ✅ Wilayah I Saja | ✅ L2 Hulu Wil I | ❌ | ❌ |
| `kasubid_penagihan_wil1` | ✅ Wilayah I Saja | ✅ L2 Hilir Wil I | ❌ | ❌ |
| `kasubid_pendataan_wil2` | ✅ Wilayah II Saja | ✅ L2 Hulu Wil II | ❌ | ❌ |
| `kasubid_penagihan_wil2` | ✅ Wilayah II Saja | ✅ L2 Hilir Wil II | ❌ | ❌ |
| `kasubid_pengembangan` | ✅ Seluruh Wilayah | ✅ Telaah Teknis | ❌ | ❌ |

---

## 4. Keamanan & Prosedur Autentikasi

1. **Isolasi Query di Tingkat Database (`TaxDomainHelper::applyDomainFilter`)**:
   Pengguna dengan role Wilayah I otomatis dibatasi datanya oleh sistem sehingga tidak dapat membaca transaksi Wilayah II melalui manipulasi parameter API.
2. **Fail-Closed Gate**:
   Middleware `EnsureServiceAction` menolak akses pengguna di luar matriks dengan response `HTTP 403 Forbidden`.
3. **Password Default**:
   Akun Kasubid Wilayah yang dibuat secara sistemik memiliki hash awal `password123`. Untuk pengujian lokal, akun dapat langsung diautentikasi dengan kredensial tersebut.
