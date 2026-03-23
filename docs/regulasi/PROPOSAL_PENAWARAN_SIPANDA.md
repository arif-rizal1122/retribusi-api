# PROPOSAL PENAWARAN KERJA SAMA
## PENGEMBANGAN MITRA PAD (M-PAD) Management Information Tax, Retribution, and Assets
### KOTA BAUBAU

---

**Kepada Yth.**  
**Kepala Badan Pendapatan Daerah (BAPENDA)**  
**Kota Baubau**  
**Di Tempat**

---

### 1. PENDAHULUAN

**CV. SARJANA KOMPUTER INDONESIA** dengan bangga mengajukan proposal penawaran untuk pengembangan dan implementasi **MITRA PAD (M-PAD) Management Information Tax, Retribution, and Assets**.

Sistem ini dirancang sebagai solusi digital terintegrasi untuk memodernisasi tata kelola pendapatan daerah Kota Baubau, meningkatkan transparansi, serta mengoptimalkan potensi Pendapatan Asli Daerah (PAD) melalui teknologi terkini.

Proposal ini mencakup pengembangan sisi *Backend (API)*, *Dashboard Administrator*, dan *Aplikasi Lapangan untuk Petugas*, yang saling terhubung secara *real-time*.

---

### 2. LINGKUP PEKERJAAN (SCOPE OF WORK)

Berdasarkan analisis kebutuhan, solusi yang kami tawarkan mencakup tiga komponen utama sistem yang saling terintegrasi:

#### A. Backend System & RESTful API (M-PAD API)
Pusat pengolahan data dan logika bisnis yang aman dan handal.
*   **Teknologi:** Laravel 11, MySQL/PostgreSQL.
*   **Fitur Utama:**
    *   **Zonasi & Billing Engine:** Logika otomatis perhitungan pajak dan retribusi berdasarkan wilayah (zona) dan parameter tarif yang dinamis.
    *   **Keamanan Tingkat Lanjut:** Implementasi Laravel Sanctum untuk autentikasi token ganda, pengaturan CORS ketat, dan perlindungan data sensitif.
    *   **Monitoring Real-time:** Integrasi dengan Sentry untuk pemantauan kesehatan sistem dan pelaporan *error* secara otomatis.
    *   **Testing Suite Komprehensif:** Termasuk uji kelayakan produksi, uji penetrasi keamanan (Security Penetration Testing), dan uji beban sistem.

#### B. Dashboard Administrator (M-PAD Admin)
Pusat komando bagi BAPENDA untuk pengelolaan data, monitoring, dan pelaporan.
*   **Teknologi:** React, Vite, Tailwind CSS.
*   **Fitur Utama:**
    *   **Manajemen Pengguna (RBAC):** Pengaturan hak akses bertingkat untuk Admin, Verifikator, dan Viewer.
    *   **Manajemen Data Wajib Pajak (Master Data):** Fitur lengkap (CRUD) untuk pendataan Wajib Pajak, termasuk validasi NIK dan kelengkapan berkas digital.
    *   **Visualisasi Data & Peta Potensi:** Tampilan *dashboard* eksekutif untuk memantau realisasi penerimaan dan sebaran potensi pajak di peta digital.
    *   **Pelaporan & Rekonsiliasi:** Pembuatan laporan pendapatan harian, bulanan, dan tahunan yang akurat dan dapat diekspor.

#### C. Aplikasi Lapangan Petugas (M-PAD Petugas)
Aplikasi bergerak (Mobile Web/PWA) untuk memudahkan petugas lapangan dalam bekerja.
*   **Teknologi:** React, Vite, Tailwind CSS (Progressive Web App).
*   **Fitur Utama:**
    *   **Verifikasi Lapangan:** Kemudahan validasi data objek pajak langsung di lokasi.
    *   **Input Data Potensi:** Formulir digital untuk perekaman data potensi baru secara *real-time* dari lapangan.
    *   **Monitoring Kinerja:** Petugas dapat melihat capaian kinerja pribadi, total wajib retribusi yang didata, dan penerimaan harian.
    *   **Peta Kerja:** Navigasi berbasis peta untuk melihat titik-titik potensi di wilayah tugas masing-masing.

---

### 3. METODOLOGI & JAMINAN KUALITAS

Kami menerapkan metodologi pengembangan modern dengan standar industri tinggi:
*   **Dokumentasi Lengkap:** Menyertakan *System Overview*, Panduan Infrastruktur, Panduan Mitigasi, dan *User Guide* yang komprehensif.
*   **Pengujian Terstandar:** Setiap modul melalui tahap *Unit Testing*, *Integration Testing*, dan *User Acceptance Testing (UAT)* sesuai *checklist* produksi.
*   **Skalabilitas:** Arsitektur sistem dirancang untuk menangani lonjakan data dan pengguna di masa depan tanpa kendala berarti.

---

### 4. RENCANA ANGGARAN BIAYA (RAB)

Berikut adalah rincian penawaran biaya untuk paket pengembangan sistem MITRA PAD (M-PAD) (tidak termasuk aplikasi Warga/Mobile untuk umum, yang akan diajukan dalam skema kerja sama terpisah):

| No | Kategori & Deskripsi Pekerjaan | Total Biaya (IDR) |
|:--:|:---|:---:|
| 1 | **Pengembangan Aplikasi & Pengujian**<br>*(Modul API, Dashboard React, PWA Petugas & Warga, Fitur Kustom, UI/UX, dan Dokumentasi Teknis)* | Rp 140.000.000 |
| 2 | **Pendampingan Strategis (1 Tahun)**<br>*(Dukungan teknis, monitoring evaluasi, dan konsultasi penyesuaian regulasi)* | Rp 15.000.000 |
| | **Subtotal** | **Rp 155.000.000** |
| | **PPN (12%)** | **Rp 18.600.000** |
| | **TOTAL ANGGARAN** | **Rp 173.600.000** |

---

### 5. PENUTUP

Demikian proposal penawaran ini kami sampaikan. Besar harapan kami untuk dapat bermitra dengan BAPENDA Kota Baubau dalam mewujudkan digitalisasi pendapatan daerah yang transparan, akuntabel, dan efisien.

Kami siap untuk melakukan presentasi teknis lebih lanjut dan mendiskusikan detail kebutuhan Bapak/Ibu.

Hormat Kami,

**CV. SARJANA KOMPUTER INDONESIA**
