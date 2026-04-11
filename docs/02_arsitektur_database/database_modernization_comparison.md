# Analisis Mendalam Modernisasi Database: Legacy 9pajak vs. Modern M-PAD

Dokumen ini menyajikan perbandingan teknis komprehensif antara arsitektur database lama yang bersifat kaku dengan sistem M-PAD yang fleksibel dan transparan.

---

## I. Ringkasan Poin Utama (Executive Summary)

1.  **Peralihan dari Silo ke Unified:** Menghapus sekat-sekat tabel per jenis pajak menjadi satu database terpadu.
2.  **Fleksibilitas Tanpa Migrasi:** Penggunaan **Metadata JSON** memungkinkan perubahan formulir tanpa mengubah struktur database.
3.  **Transparansi Rumus (Dynamic Coding):** Logika perhitungan pajak dipindahkan dari kode program (PHP) ke database yang bisa diaudit (*Formula Parser*).
4.  **Integritas & Akuntabilitas:** Rekam jejak perubahan (*Audit Trail*) yang sangat detail dan dukungan dokumen digital (TTE).
5.  **Kesiapan GIS:** Integrasi koordinat spasial di level inti data untuk visualisasi potensi pajak di peta.

---

## II. Penjabaran Lengkap: Masalah pada Arsitektur Lama (Legacy Rigidity)

Sistem lama dirancang menggunakan pola **"Table-per-Tax-Type"**, yang mengakibatkan beberapa masalah fundamental:

### 1. Data Silo (Sekat Data)
Tiap jenis pajak (Hotel, Restoran, Reklame) memiliki "kamar" database-nya sendiri.
- **Dampaknya:** Sangat sulit bagi pimpinan untuk mendapatkan pandangan tunggal (*Single View*) mengenai profil pajak seorang warga. Data WP sering terduplikasi dan tidak konsisten antar tabel.

### 2. Schema Rigidity (Kekakuan Struktur)
Setiap field seperti `jumlah_kamar` atau `panjang_reklame` adalah kolom fisik permanen.
- **Dampaknya:** Setiap ada update kebijakan atau tambahan data pendataan, sistem harus menjalani proses **Database Migration** yang berisiko tinggi terhadap kehilangan data dan membutuhkan *downtime* layanan.

### 3. Hardcoded Business Logic (Logika Terkunci)
Rumus perhitungan pajak "tertanam" di dalam bahasa pemrograman PHP.
- **Dampaknya:** Auditor atau admin pajak tidak bisa memverifikasi rumus tersebut tanpa bantuan programmer. Transparansi sangat rendah karena aturan bisnis tidak bisa dilihat langsung dari sistem.

---

## III. Penjabaran Lengkap: Keunggulan Arsitektur M-PAD (Modern Flexibility)

Sistem BARU dirancang dengan prinsip **"Logic-Data Separation"** yang memberikan kelincahan tinggi:

### 1. Unified Database Schema & Relational Integrity
M-PAD menggunakan tabel inti `tax_objects` yang menampung semua jenis objek pajak.
- **Analisis:** Hubungan antara Wajib Pajak (`taxpayers`), Objek (`tax_objects`), Tagihan (`bills`), dan Pembayaran (`payments`) sangat terpusat. Hal ini menjamin **Referential Integrity** (tidak ada data yatim piatu atau duplikasi profil).

### 2. Metadata JSON & Form Schema Analysis
M-PAD mengadopsi kolom `metadata` dengan format JSON.
- **Analisis:** Kita bisa menyimpan atribut apa pun tanpa menambah kolom tabel. Ditambah lagi dengan **`form_schema`** di tabel `RetributionClassification`, sistem bisa menghasilkan formulir input secara dinamis berdasarkan jenis pajaknya. Ini adalah solusi untuk **Scalability** (kemampuan berkembang) jangka panjang.

### 3. Formula Parser Engine (Smart Assessment)
Logika perhitungan dipindahkan ke database dalam kolom `calculation_formula`.
- **Analisis:** `FormulaParserService` mengevaluasi rumus ini secara dinamis (*Dynamic Evaluation*). Admin bisa mengubah tarif atau rumus secepat kilat saat Perwali baru terbit, tanpa harus menyentuh kode program aplikasi sama sekali.

### 4. Audit Trail & Snapshot Integrity
Setiap perubahan data divalidasi dan dicatat dalam tabel `audit_logs`.
- **Analisis:** Sistem mencatat `old_values` (nilai lama) dan `new_values` (nilai baru), beserta `ip_address` dan `user_agent`. Ini memberikan transparansi total kepada Inspektorat atau BPK dalam melacak setiap aktivitas perubahan data keuangan.

### 5. GIS-Centric Data Model
V-Tax Parity mengharuskan data objek pajak memiliki dimensi geografis.
- **Analisis:** Database mendukung penyimpanan `latitude` dan `longitude` secara asli. Hal ini memungkinkan visualisasi **Heatmap** potensi pajak dan membantu petugas lapangan melakukan verifikasi fisik secara presisi menggunakan peta.

---

## IV. Istilah-Istilah Penting (Glossary)

| Istilah | Penjelasan |
| :--- | :--- |
| **Data Silo** | Kondisi di mana data terisolasi dalam tabel-tabel terpisah yang tidak saling terintegrasi. |
| **Schema Migration** | Proses pengubahan struktur tabel database (tambah/hapus kolom). |
| **Metadata JSON** | Format penyimpanan data dinamis dalam satu kolom yang bisa menampung banyak atribut sekaligus. |
| **Formula Parser** | Mesin cerdas yang membaca rumus teks dan mengubahnya menjadi hasil perhitungan angka. |
| **Audit Trail** | Rekam jejak kronologis yang membuktikan urutan aktivitas pada suatu data. |
| **Referential Integrity** | Konsistensi data antar tabel yang saling terhubung (tidak ada data yang hilang kaitannya). |
| **Scalability** | Kemampuan sebuah sistem untuk menangani pertumbuhan data dan fitur tanpa penurunan performa. |

---

> [!IMPORTANT]
> Modernisasi ini mengubah paradigma dari **"Sistem Statis"** menjadi **"Sistem Cerdas"**. Dengan M-PAD, database bukan lagi sekadar tempat penyimpanan, melainkan **Aset Strategis** yang bisa memberikan wawasan keputusan langsung bagi pimpinan daerah.
