# 🏛️ MEGA DOCUMENTATION: 9pajak (Legacy SW_PATDA)

Dokumen ini adalah panduan referensi teknis untuk sistem warisan **9pajak** (secara internal dikenal sebagai **SW_PATDA**) yang merupakan sumber data utama bagi sistem M-PAD.

## 1. Filosofi & Nomenklatur
Sistem 9pajak menggunakan konvensi penamaan kolom yang sangat ketat untuk membedakan data operasional dari metadata sistem.

### Awalan `CPM_`
Hampir seluruh kolom di 9pajak menggunakan awalan **`CPM_`** yang merupakan singkatan dari **Catatan Pajak Masa**. 
*   **Contoh**: `CPM_NPWPD`, `CPM_NAMA_WP`, `CPM_TOTAL_PAJAK`.

### Struktur Tabel Per Modul
Berbeda dengan M-PAD yang menggunakan tabel terpadu (`tax_objects`), 9pajak memisahkan setiap jenis pajak ke dalam minimal tiga tabel utama:
1.  **`PATDA_[JENIS]_PROFIL`**: Data Master Objek Pajak (Alamat, NOP, Nama Toko).
2.  **`PATDA_[JENIS]_DOC`**: Data Transaksional / SPTPD (Omzet, Masa Pajak, Total Pajak).
3.  **`PATDA_[JENIS]_DOC_TRANMAIN`**: Log Status & Alur Kerja (Siapa yang setuju, kapan diproses).

---

## 2. Transformasi Hirarki (Jenis ke Klasifikasi)
Salah satu perbedaan arsitektural terbesar adalah bagaimana jenis beban dikategorikan. 

*   **9pajak (SW_PATDA)**: Menggunakan struktur **Flat**. Setiap beban adalah "Jenis Pajak" (Top Level).
*   **M-PAD (Modern)**: Menggunakan struktur **Wilayah-Centric**. Baris-baris data dari `PATDA_JENIS_PAJAK` (Legacy) kini diturunkan menjadi **Klasifikasi Pajak (Level 2)** di bawah Portofolio Wilayah (Level 1).

### Tabel Pemetaan Hirarki & Portofolio
| 9pajak (Jenis) | ID (TIPE) | M-PAD (Klasifikasi Pajak) | Portofolio (Wilayah) |
| :--- | :--- | :--- | :--- |
| **Hotel** | 4 | PBJT - Hotel | **Wilayah II** (Konsumsi) |
| **Restoran** | 5 | PBJT - Makan dan Minum | **Wilayah II** (Konsumsi) |
| **Hiburan** | 6 | PBJT - Kesenian dan Hiburan | **Wilayah II** (Konsumsi) |
| **Reklame** | 7 | Pajak Reklame | **Wilayah I** (Aset) |
| **Penerangan Jalan**| 8 | PBJT - Tenaga Listrik | **Wilayah II** (Konsumsi) |
| **Mineral/Galian** | 9 | Pajak MBLB | **Wilayah I** (Aset) |
| **Parkir** | 10 | PBJT - Jasa Parkir | **Wilayah II** (Konsumsi) |
| **Air Bawah Tanah** | 11 | Pajak Air Tanah | **Wilayah II** (Konsumsi) |
| **Sarang Walet** | 12 | Pajak Sarang Burung Walet | **Wilayah I** (Aset) |

---

## 3. Pemetaan Status (V-Tax Parity)
Status transaksi dalam 9pajak disimpan dalam kolom `CPM_TRAN_STATUS` di tabel `TRANMAIN`. Berikut adalah pemetaannya ke status M-PAD:

| Nilai (9pajak) | Status M-PAD | Deskripsi |
| :--- | :--- | :--- |
| **0** | `draft` | Draft oleh Wajib Pajak. |
| **1** | `proses` | Menunggu verifikasi petugas. |
| **2** | `disetujui` | Valid & Terbit tagihan/pembayaran. |
| **3** | `ditolak` | Ditolak oleh petugas. |

---

## 3. Rosetta Stone: Pemetaan Kolom (9pajak -> M-PAD)
Digunakan oleh `SimpadKoneksiService` untuk memigrasikan data secara otomatis.

### A. Wajib Pajak (`PATDA_WP`)
| Kolom 9pajak | Properti M-PAD | Keterangan |
| :--- | :--- | :--- |
| `CPM_NPWPD` | `npwpd` | Identifier Utama. |
| `CPM_NAMA_WP` | `name` | Nama wajib pajak. |
| `CPM_ALAMAT_WP` | `address` | Alamat lengkap. |
| `CPM_TELEPON_WP`| `phone` | |
| `CPM_EMAIL_WP` | `email` | |

### B. Objek Pajak (`_PROFIL`)
| Kolom 9pajak | Properti M-PAD | Mapping Metadata (JSON) |
| :--- | :--- | :--- |
| `CPM_NOP` | `nop` | |
| `CPM_NAMA_OP` | `name` | Nama Toko/Restoran/Hotel. |
| `CPM_ALAMAT_OP` | `address` | Lokasi objek. |
| `CPM_LATITUDE` | `latitude` | |
| `CPM_LONGITUDE` | `longitude` | |
| `CPM_JUMLAH_KAMAR`| - | `metadata->jumlah_kamar` (Hotel) |
| `CPM_KAPASITAS_KURSI`| - | `metadata->kapasitas_kursi` (Restoran)|

---

## 4. Hubungan Antar Tabel (Entity Logic)
Untuk mengambil data lengkap satu laporan pajak, sistem melakukan join sebagai berikut:
```sql
SELECT * FROM PATDA_HOTEL_DOC doc
JOIN PATDA_HOTEL_PROFIL prof ON doc.CPM_ID_PROFIL = prof.CPM_ID
JOIN PATDA_WP wp ON prof.CPM_NPWPD = wp.CPM_NPWPD
JOIN PATDA_HOTEL_DOC_TRANMAIN tran ON doc.CPM_ID = tran.CPM_TRAN_HOTEL_ID
WHERE doc.CPM_ID = 'xyz';
```

---

## 4. Spesifikasi Field & Formulir (Per Jenis Pajak)
Setiap jenis pajak memiliki atribut unik yang menentukan dasar pengenaan pajak (DPP). Berikut adalah rincian fungsional kolom-kolom kunci di 9pajak.

### 🏨 1. PBJT - Jasa Perhotelan (`PATDA_HOTEL`)
*   **Master (Profil)**: `CPM_NPWPD`, `CPM_NAMA_OP`, `CPM_ALAMAT_OP`, `CPM_REKENING`, `CPM_DEVICE_ID` (ID Alat Tapping).
*   **Transaksi (Doc)**: `CPM_TOTAL_OMZET`, `CPM_DPP`, `CPM_TARIF_PAJAK` (10%), `CPM_TOTAL_PAJAK`.
*   **Field Unik**: `CPM_JUMLAH_KAMAR`, `CPM_GOLONGAN`.

### 🍴 2. PBJT - Jasa Makan dan Minum (`PATDA_RESTORAN`)
*   **Master (Profil)**: `CPM_NPWPD`, `CPM_NAMA_OP`, `CPM_ALAMAT_OP`, `CPM_DEVICE_ID`.
*   **Transaksi (Doc)**: `CPM_TOTAL_OMZET`, `CPM_TARIF_PAJAK` (10%), `CPM_TOTAL_PAJAK`.
*   **Field Unik**: `CPM_KAPASITAS_KURSI`, `CPM_KAPASITAS_MEJA`, `LYTO_GOL`.

### 🎬 3. PBJT - Jasa Kesenian dan Hiburan (`PATDA_HIBURAN`)
*   **Master (Profil)**: `CPM_NPWPD`, `CPM_NAMA_OP`, `CPM_ALAMAT_OP`.
*   **Transaksi (Doc)**: `CPM_TOTAL_OMZET`, `CPM_TARIF_PAJAK` (10-75%), `CPM_TOTAL_PAJAK`.

### 🚘 4. PBJT - Jasa Parkir (`PATDA_PARKIR`)
*   **Master (Profil)**: `CPM_NPWPD`, `CPM_NAMA_OP`, `CPM_ALAMAT_OP`.
*   **Transaksi (Doc)**: `CPM_TOTAL_OMZET`, `CPM_TARIF_PAJAK` (10%), `CPM_TOTAL_PAJAK`.

### ⚡ 5. PBJT - Tenaga Listrik (`PATDA_JALAN`)
*   **Master (Profil)**: `CPM_NPWPD`, `CPM_NAMA_OP`, `CPM_REKENING`.
*   **Transaksi (Doc)**: `CPM_TOTAL_KWH`, `CPM_HARGA_DASAR`, `CPM_TOTAL_PAJAK`.

### 🖼️ 6. Pajak Reklame (`PATDA_REKLAME`)
*   **Master (Profil)**: `CPM_NPWPD`, `CPM_NAMA_OP`, `CPM_ALAMAT_OP`.
*   **Transaksi (Doc)**: `CPM_ATR_JUDUL`, `CPM_ATR_LOKASI`.
*   **Field Fisik (ATR)**: `CPM_ATR_LEBAR`, `CPM_ATR_TINGGI`, `CPM_ATR_MUKA` (Sisi), `CPM_ATR_JUMLAH` (Unit).
*   **Field Perhitungan**: `CPM_ATR_NJOP`, `CPM_ATR_NILAI_STRATEGIS`, `CPM_ATR_KAWASAN`.

### 💧 7. Pajak Air Tanah (`PATDA_AIRBAWAHTANAH`)
*   **Master (Profil)**: `CPM_NPWPD`, `CPM_NAMA_OP`, `CPM_ALAMAT_OP`.
*   **Transaksi (Doc)**: `CPM_VOLUME_AIR` (M3), `CPM_HARGA` (NPA), `CPM_TOTAL_PAJAK` (20%).
*   **Field Unik**: `CPM_LOKASI_SUMBER_AIR`, `CPM_KUALITAS_AIR`, `CPM_TINGKAT_KERUSAKAN`.

### 🪨 8. Pajak MBLB / Galian C (`PATDA_MINERAL`)
*   **Master (Profil)**: `CPM_NPWPD`, `CPM_NAMA_OP`, `CPM_TRUCK_ID`.
*   **Transaksi (Doc)**: `CPM_ATR_VOLUME` (Ton/M3), `CPM_ATR_HARGA`, `CPM_TOTAL_PAJAK` (15-20%).

### 🏠 9. Pajak Sarang Burung Walet (`PATDA_WALET`)
*   **Master (Profil)**: `CPM_NPWPD`, `CPM_NAMA_OP`, `CPM_ALAMAT_OP`.
*   **Transaksi (Doc)**: `CPM_ATR_JUMLAH_KG` (Volume), `CPM_HARGA_DASAR`, `CPM_TOTAL_PAJAK` (10%).

---

## 5. Catatan Pemeliharaan (Maintenance)
*   **Database**: Terletak di koneksi `mysql_legacy`.
*   **Versi PHP**: 9pajak telah dimigrasikan ke **PHP 8.3** agar dapat berjalan di lingkungan VPS yang sama dengan M-PAD.
*   **Integritas**: Kolom `CPM_VERSION` di tabel `_DOC` menandakan riwayat revisi laporan oleh WP.

> [!WARNING]
> Jangan melakukan modifikasi langsung pada skema tabel 9pajak (PATDA_*) karena hal ini akan merusak logika sinkronisasi `SimpadKoneksi`. Data harus dianggap **Read-Only** dari perspektif M-PAD.
