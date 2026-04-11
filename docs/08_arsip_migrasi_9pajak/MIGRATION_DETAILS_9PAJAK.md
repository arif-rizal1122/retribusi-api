# 📘 Migration Bible: Detail Pola & Pemetaan Field (Legacy ➔ M-PAD)

Dokumen ini berfungsi sebagai acuan teknis bagi AI atau sistem integrasi dalam melaksanakan migrasi data hulu-ke-hilir untuk 92 tabel `sw_patda`.

---

## 🏗️ 1. Template: Subjek Pajak (Induk Identitas)
Digunakan untuk tabel: `PATDA_WP`.

### 📋 Pemetaan Field Detil
| Field Legacy (sw_patda) | Field Modern (M-PAD) | Transformasi Data |
| :--- | :--- | :--- |
| `CPM_NPWPD` | `npwpd` | Trim, Sanitasi (Hapus titik/strip). |
| `CPM_NAMA_WP` | `name` | UpperCaseWords (Normalisasi Nama). |
| `CPM_ALAMAT_WP` | `address` | Pembersihan spasi ganda & casing. |
| `CPM_NIK` | `nik` | Validasi 16 digit, default blank jika tidak valid. |
| `CPM_TELP` | `phone` | Normalisasi ke format +62 (WhatsApp). |
| `CPM_KDPOS` | `metadata->postal_code` | Masuk ke kolom JSON metadata. |

---

## 🏗️ 2. Template: Objek Pajak (Aset & Kapasitas)
Digunakan untuk tabel berakhiran `_PROFIL` (Hotel, Restoran, Reklame, dll).

### 📋 Pemetaan Field Umum
| Field Legacy | Field Modern | Transformasi / Logika |
| :--- | :--- | :--- |
| `CPM_NOP` | `nop` | ID unik Objek Pajak (Wajib Ada). |
| `CPM_NAMA_OP` | `name` | Nama Komersial Objek/Tempat Usaha. |
| `CPM_ALAMAT_OP` | `address` | Lokasi fisik objek. |
| `CPM_NPWPD` | `taxpayer_id` | Foreign Key ke tabel `taxpayers`. |
| `CPM_KECAMATAN_OP`| `zone_id` | Mapping ID via Tabel Master Wilayah (Kec). |
| `CPM_KELURAHAN_OP` | `zone_id` | Mapping ID via Tabel Master Wilayah (Kel). |

### 🛠️ Penyesuaian Field Khusus ke JSON Metadata (`tax_objects.metadata`)
*   **Restoran**: `CPM_JUMLAH_MEJA`, `CPM_JUMLAH_KURSI` ➔ disatukan dalam JSON.
*   **Hotel**: `CPM_JUMLAH_KAMAR`, `CPM_GOLONGAN` (Bintang).
*   **Reklame**: `CPM_LUAS_REKLAME`, `CPM_TEKS_REKLAME`, `CPM_LOKASI`.
*   **Mineral (MBLB)**: `CPM_JENIS_TAMBANG`, `CPM_LOKASI_TAMBANG`.

---

## 🏗️ 3. Template: Pelaporan & Transaksi (SPTPD)
Digunakan untuk tabel berakhiran `_DOC` dan `_DOC_ATR`.

### 📋 Pemetaan Field
| Field Legacy | Field Modern | Transformasi / Logika |
| :--- | :--- | :--- |
| `CPM_TOTAL_OMZET` | `turnover_amount` | Casting ke decimal (Precision: 15,2). |
| `CPM_TOTAL_PAJAK` | `tax_amount` | Casting ke decimal (10% standard). |
| `CPM_MASA_PAJAK` | `period` | Format: `Bulan-Tahun` (e.g. 03-2024). |
| `CPM_TGL_INPUT` | `created_at` | Konversi string 'dd/mm/yyyy hh:ii:ss' ke Timestamp. |
| `CPM_TRAN_STATUS` | `status` | Map: 1➔Draft, 2➔Pending, 5➔Approved. |
| `CPM_KETERANGAN` | `notes` | Penjelasan tambahan dari sistem lama. |

---

## 🏗️ 4. Template: Ketetapan & Piutang (SKPDKB / STPD)
Digunakan untuk tabel: `PATDA_SKPDKB`, `PATDA_STPD`.

### 📋 Pemetaan Field
| Field Legacy | Field Modern | Transformasi / Logika |
| :--- | :--- | :--- |
| `CPM_NO_SKPDKB` | `bill_number` | Nomor invoice/ketetapan resmi. |
| `CPM_KURANG_BAYAR`| `principal_amount` | Nominal pokok ketetapan. |
| `CPM_DENDA` | `penalty_amount` | Nominal sanksi administrasi. |
| `CPM_TOTAL_PAJAK` | `total_amount` | Total tagihan wajib bayar. |
| `CPM_TGL_JATUH_TEMPO`| `due_date` | Konversi ke format Date YYYY-MM-DD. |

---

## 📋 Inventori Rinci Migrasi (92 Tabel)

| Kelompok | Cakupan Tabel | Strategi Perubahan Data |
| :--- | :--- | :--- |
| **Identity** | `PATDA_WP`, `CENTRAL_USER` | **Smart Merge**: Menggunakan NPWPD & NIK untuk menghindari duplikasi profil. |
| **Core Objects**| `PATDA_*_PROFIL` (9 Tabel) | **Normalization**: Memisahkan entitas WP (Subjek) dari OP (Objek). |
| **History** | `PATDA_*_DOC` (9 Tabel) | **Filtering**: Hanya menarik data dengan `Total Omzet > 0`. |
| **Enforcement** | `PATDA_TEGURAN`, `PAKSA` | **Archiving**: Disimpan di tabel penindakan untuk profil risiko WP. |
| **Master Data** | `PATDA_MST_*`, `PATDA_REK_*`| **Linkage**: Menyambungkan relasi ID Wilayah & Kategori Pajak. |
| **Auth/Admin** | `CENTRAL_*` (14 Tabel) | **Mapping**: Menyesuaikan role lama (Operator/Supervisor) ke Role M-PAD. |

---

## 🚀 Logika Integrasi (The AI Guardrail)

1.  **Sanitasi**: Otomatis menghapus spasi di awal/akhir string dan melakukan normalisasi huruf besar/kecil.
2.  **Constraint Recovery**: Jika objek memiliki NPWPD yang tidak terdaftar, sistem akan mencari di `PATDA_WP` secara otomatis untuk membuat induknya terlebih dahulu.
3.  **JSON Folding**: Seluruh variabel teknis lama yang tidak memiliki kolom di M-PAD "dilipat" masuk ke kolom `metadata` agar tidak ada informasi yang hilang.
4.  **Audit Trail Preservation**: Menyimpan `CPM_ID` asli di kolom `notes` atau `metadata` untuk audit sungsang di masa depan.
