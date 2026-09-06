# Migration Strategy: Legacy 9pajak & PBB to M-PAD
## Strategi Modernisasi & Penyelarasan Data (Update April 2026)

Dokumen ini mendefinisikan pendekatan strategis untuk mengadopsi data dari sistem legacy "9pajak" (PHP) dan sistem "PBB" (SISMIOP) ke dalam ekosistem modern **M-PAD (retribusi-api)**.

---

## 1. Filosofi Inti: "Hybrid-Unified Architecture"

Misinya adalah memodernisasi sistem untuk skalabilitas dan transparansi jangka panjang sambil menjaga integritas data 100% dari sistem lama.

- **Unified Schema**: Menggabungkan 9 silo pajak daerah dan data PBB ke dalam satu hierarki `TaxObject`.
- **Transparent Logic**: Menggantikan logika perhitungan PHP/hardcoded dengan `FormulaParserService` yang dapat diaudit.
- **Legacy Compatibility**: Mendukung pengenal lama (NPWPD, NOP) bersamaan dengan modern UUID.

---

## 2. Pendekatan "Metadata-First"

Tipe pajak lama memiliki field khusus (contoh: `luas_hotel`, `panjang_reklame`, `kelas_bumi`). M-PAD menggunakan kolom **JSON Metadata** untuk menyimpan atribut dinamis ini tanpa menambah kolom fisik tabel.

| Legacy Table | Field | Target Mapping (M-PAD JSON) |
| :--- | :--- | :--- |
| `PATDA_HOTEL_DOC` | `CPM_JUMLAH_KAMAR` | `metadata -> jumlah_kamar` |
| `PATDA_REKLAME_DOC`| `CPM_UKURAN` | `metadata -> dimensi_reklame` |
| `PBB_SPPT` | `KD_KELAS_BUMI` | `metadata -> kelas_bumi` |

---

## 3. Siklus Hidup Migrasi (Migration Lifecycle)

### Fase A: Sinkronisasi Data Master (Active Objects)
Migrasi profil Wajib Pajak (`patda_wp`) dan Objek Pajak aktif dengan NPWPD/NOP sebagai pengait.

### Fase B: Piutang Berjalan (Pending Receivables)
Migrasi data SPTPD/SKPDKB yang belum lunas. Data ini diterjemahkan ke model `Bill` agar dapat dibayar melalui Payment Gateway M-PAD.

### Fase C: Arsip Historis (Read-Only)
Transaksi > 5 tahun tetap berada di database legacy. M-PAD menggunakan `LegacyAdapterService` untuk pencarian historis sesuai permintaan (On-Demand).

### Fase D: Integrasi PBB (April 2026)
Sinkronisasi real-time data PBB dari sistem eksternal (SISMIOP) via API/DB-Link. Data NOP dipetakan ke `TaxObject` dengan klasifikasi khusus PBB-P2.

---

## 4. Analisis "Formula Parity"

Untuk menjamin akurasi audit, sistem baru harus menghasilkan hasil perhitungan yang identik dengan sistem lama (Back-testing).

1. **Rule Mapping**: Memetakan logika perhitungan lama ke `CalculationFormula`.
2. **Back-testing**: Menjalankan `FormulaParserService` terhadap record historis.
3. **Approval**: Aktivasi formula baru setelah verifikasi ketepatan 100%.

---

## 5. Manfaat Jangka Panjang

- **Transparansi**: Rumus perhitungan dapat dibaca manusia, bukan tersembunyi di kode program.
- **Auditabilitas**: `AuditLog` mencatat setiap modifikasi pada record pajak.
- **Scalability**: Menambah jenis pajak/retribusi ke-11 atau ke-12 semudah menambah baris di `RetributionType`.

---
*Last modified: April 2026. Unified Migration Standard.*
