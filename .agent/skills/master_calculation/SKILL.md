---
name: Master Calculation & Hierarchy Specialist
description: Panduan komprehensif mengenai 4 tingkatan hirarki sistem M-PAD, tujuan masing-masing level, serta seluruh jenis pajak/retribusi beserta rumusnya.
---

# Master Calculation & Hierarchy Specialist

Skill ini adalah sumber kebenaran tunggal untuk memahamami struktur data, alur bisnis, dan logika perhitungan di ekosistem M-PAD (Pajak dan Retribusi Daerah Kota Baubau).

## 1. Empat (4) Hirarki Data M-PAD
Sistem ini dibangun di atas 4 pilar hirarki yang saling terhubung:

| Level | Model | Deskripsi & Tujuan |
| :--- | :--- | :--- |
| **1. Jenis** | `RetributionType` | Pengelompokan besar (Pajak vs Retribusi). Menentukan jenis dokumen ketetapan (SKPD untuk Pajak, SKRD untuk Retribusi) dan kode rekening utama. |
| **2. Klasifikasi** | `RetributionClassification` | **Otak Perhitungan**. Di sini didefinisikan `calculation_formula` (rumus) dan `form_schema` (metadata input). Setiap klasifikasi merujuk pada regulasi spesifik (e.g., UU HKPD, Perwali). |
| **3. Objek** | `TaxObject` | Unit fisik/aktivitas yang dikenakan beban. Memiliki NOP/NOPD unik. Menyimpan data spasial (koordinat) dan metadata spesifik (e.g., Luas Bangunan, Sisi Reklame). |
| **4. Penagihan** | `Bill` | Produk akhir (Piutang). Dokumen legal yang menentukan nominal yang harus dibayar WP untuk periode tertentu, termasuk denda jika terlambat. |

---

## 2. Katalog Pajak & Retribusi (Logic & Formula)

Berikut adalah daftar jenis pajak dan retribusi yang aktif beserta logika perhitungannya:

### A. Pajak Barang dan Jasa Tertentu (PBJT)
Mengikuti tarif standar **10%** dari Omzet/Tagihan.
- **Makan dan Minum**: `omzet * 0.10`
- **Tenaga Listrik**: `tagihan * 0.10`
- **Jasa Perhotelan**: `omzet * 0.10`
- **Jasa Parkir**: `omzet * 0.10`
- **Jasa Kesenian dan Hiburan**: `omzet * 0.10`

### B. Pajak Spesifik (Regulasi Khusus)
- **Pajak Reklame (25%)**: 
  `Total = ((njopr_satuan + nspr_satuan) * IF(panjang * lebar < 1, 1, panjang * lebar) * jumlah_sisi) * 0.25 * jumlah_unit * durasi`
- **PBB-P2 (0.3%)**:
  `Total = (NJOP - NJOPTKP) * 0.003`
- **Pajak MBLB (15%)**:
  `Total = (Volume * Harga) * 0.15`
- **BPHTB (5%)**:
  `Total = (NPOP - NPOPTKP) * 0.05`
- **Pajak Air Tanah (20%)**:
  `Total = (Volume * HDA) * 0.20`

### C. Retribusi Jasa & Perizinan
- **Persampahan**: Berbasis `tarif_flat`.
- **PKD (Kios Pasar)**: Berbasis `tarif_kios`.
- **PBG (Building Permit)**: `luas * indeks`.

---

## 3. Logika Denda & Sanksi (Penalty Logic)
Jika melewati `due_date`, sistem secara otomatis menerapkan denda:
- **Denda Pajak**: 1% - 2% per bulan (maksimal 24 bulan) tergantung regulasi yang diatur di `FormulaParserService`.
- **Denda Retribusi**: Sanksi administratif flat atau persentase sesuai jenis retribusinya.

## 4. Cara Kerja Formula Parser
Sistem menggunakan `FormulaParserService` untuk mengevaluasi rumus secara dinamis.
- Mendukung logika kondisional: `IF(condition, true_val, false_val)`.
- Mendukung perbandingan string: `IF(produk == "rokok", 1.1, 1.0)`.
- Variabel diambil secara dinamis dari tabel `tax_objects.metadata` atau input manual saat penagihan.
