<div align="center">
  <img src="https://visibaubau4.vercel.app/assets/img/sdm/CV.%20Sarjana%20Komputer%20Indonesia.png" alt="CV. Sarjana Komputer Indonesia" width="250"/>
</div>

# DOKUMEN PERENCANAAN PENGEMBANGAN SISTEM INFORMASI PENDAPATAN DAERAH TERINTEGRASI
**BADAN PENDAPATAN DAERAH (BAPENDA) KOTA BAUBAU**

## BAB I: PENDAHULUAN & LANDASAN HUKUM

### 1. Latar Belakang
Pengembangan sistem informasi pendapatan daerah yang baru diinisiasi untuk mengkonsolidasikan aplikasi yang sebelumnya terpisah secara silo (seperti SIMPAD, SIMBPHTB, dan SIMPBB) menjadi satu platform tunggal yang terintegrasi secara hulu-ke-hilir. Peralihan dari arsitektur database yang kaku menuju sistem Unified Database dengan Metadata JSON akan memberikan kelincahan tinggi serta visibilitas data yang terpusat bagi pimpinan daerah. Sistem ini dirancang untuk mengotomatisasi proses birokrasi, mengamankan pendapatan daerah, meminimalisasi kebocoran, dan meningkatkan transparansi.

### 2. Landasan Hukum
Pengembangan aplikasi ini mematuhi kerangka regulasi terbaru:
*   Undang-Undang No. 1 Tahun 2022 tentang Hubungan Keuangan Antara Pemerintah Pusat dan Daerah (HKPD).
*   Peraturan Daerah (Perda) Kota Baubau No. 1 Tahun 2024 tentang Pajak Daerah dan Retribusi Daerah (PDRD).
*   Peraturan Wali Kota Baubau No. 58 Tahun 2024 tentang Tata Cara Pemungutan PDRD.

## BAB II: ARSITEKTUR & EKOSISTEM SISTEM

<div class="mermaid">
flowchart TD
    %% Styling
    classDef cloud fill:#e3f2fd,stroke:#1565c0,stroke-width:2px,color:#0d47a1,rx:5px,ry:5px
    classDef server fill:#fff3e0,stroke:#e65100,stroke-width:2px,color:#e65100,rx:5px,ry:5px
    classDef db fill:#e8f5e9,stroke:#2e7d32,stroke-width:2px,color:#1b5e20,rx:5px,ry:5px
    classDef firewall fill:#ffebee,stroke:#c62828,stroke-width:2px,color:#b71c1c,rx:5px,ry:5px
    
    style Publik fill:#f8f9fa,stroke:#ccc,stroke-width:1px,stroke-dasharray: 5 5
    style Keamanan fill:#f8f9fa,stroke:#ccc,stroke-width:1px,stroke-dasharray: 5 5
    style VPS fill:#f8f9fa,stroke:#ccc,stroke-width:1px,stroke-dasharray: 5 5
    style DataCenter fill:#f8f9fa,stroke:#ccc,stroke-width:1px,stroke-dasharray: 5 5

    subgraph Publik ["🌐 Area Publik"]
        Client["📱 Kanal Perbankan & Warga"]:::cloud
    end

    subgraph Keamanan ["🛡️ Keamanan & Load Balancing"]
        WAF["🧱 Web Application Firewall (WAF)"]:::firewall
    end

    subgraph VPS ["☁️ Neo Cloud VPS Bapenda"]
        App["⚙️ API Gateway & Laravel Backend"]:::server
    end

    subgraph DataCenter ["💾 Data Center"]
        DB["🗄️ Unified MySQL/PostgreSQL DB"]:::db
        Redis["⚡ Redis In-Memory Cache"]:::db
    end

    Client -->|HTTPS / TLS 1.2+| WAF
    WAF -->|Port 443| App
    App -->|Read/Write| DB
    App -->|Cache| Redis
</div>

**Deskripsi Arsitektur Topologi Jaringan:**
Arsitektur sistem dibangun dengan pendekatan *N-Tier Architecture* yang mendisagregasi lapisan antarmuka (Presentasi), logika bisnis (Aplikasi), dan penyimpanan data (Basis Data). Kanal akses yang berasal dari entitas eksternal seperti *Payment Gateway* Perbankan maupun aplikasi pengguna (Wajib Pajak) dialirkan melalui protokol aman (HTTPS/TLS 1.2+). Lapisan pertama dari sistem perimeter dipertahankan oleh *Web Application Firewall* (WAF) yang bertugas menyaring anomali lalu lintas data, mencegah *SQL Injection*, serta melakukan *Load Balancing* untuk mendistribusikan beban secara merata. Pada zona inti (Neo Cloud VPS Bapenda), sebuah *API Gateway* berbasis kerangka kerja Laravel berfungsi sebagai orkestrator layanan (mikro-monolitik) yang menjembatani transaksi antara *client* dan *Unified Database*. Penggunaan *Redis In-Memory Cache* diterapkan guna mengoptimalkan latensi *query* repetitif, sehingga sistem mencapai tingkat ketersediaan (*High Availability*) yang optimal.



Sistem ini akan dipecah ke dalam 4 (empat) repositori platform utama agar beban kerja lebih efisien dan terukur:
1.  **Backend (API):** Dibangun menggunakan framework Laravel 11 dan database MySQL/PostgreSQL. Komponen ini menjadi pusat logika bisnis yang melayani Formula Parser Service untuk kalkulasi dinamis dan Billing Engine berbasis Just-In-Time (JIT).
2.  **Dashboard Admin:** Antarmuka berbasis React (Vite) yang berfungsi sebagai pusat komando pengelola BAPENDA untuk memantau master data, zonasi, validasi, dan pelaporan.
3.  **Aplikasi Mobile Warga:** Aplikasi portal layanan mandiri (Progressive Web App/React) bagi Wajib Pajak untuk melakukan pendaftaran, pengecekan tagihan, inkuiri E-SPPT PBB, dan pembayaran cashless.
4.  **Aplikasi Petugas Lapangan:** Aplikasi mobile (Android/iOS) khusus untuk petugas pemungut retribusi yang dilengkapi dengan fitur GPS, live tracking, uji petik, dan pembuatan tagihan di lokasi.

## BAB III: STANDAR KINERJA & MODUL UTAMA

Sistem ini dirancang untuk mengawal 4 (empat) siklus pemungutan BAPENDA secara terpadu (End-to-End):

### 1. Pendaftaran (Registration)
*   Digitalisasi E-SPOPD dan SPTPD untuk pendaftaran 9 jenis Pajak Barang dan Jasa Tertentu (PBJT) dan Retribusi.
*   Pembuatan NPWPD (Nomor Pokok Wajib Pajak Daerah) secara otomatis ketika pendaftaran diverifikasi.

### 2. Pendataan & Pengawasan (Assessment & Surveillance)
*   **GIS & Peta Spasial:** Peta digital untuk memvisualisasikan Heatmap objek pajak. Area penunggak akan ditandai merah, dan yang patuh ditandai hijau.
*   **Uji Petik (Spot Check):** Pengamatan lapangan jam-per-jam untuk memvalidasi omzet Wajib Pajak riil, dilengkapi fitur GPS Tracking petugas untuk mencegah manipulasi.

### 3. Penetapan (Billing Engine & TTE)

<div class="mermaid">
graph TD
    subgraph "Logika Aplikasi Core (Backend)"
        BS[Modul Billing JIT]
        FPS[Formula Parser Dinamis]
        PCS[Kalkulator Denda 2%]
    end

    subgraph "Antarmuka Pengguna & Manajemen"
        A_VER[Verifikasi Data]
        A_AUDIT[Audit Pembayaran]
    end

    subgraph "Perangkat Lapangan"
        P_SCAN[Pemindai QR Code]
        P_PAY[Pencatatan Lapangan]
    end
    
    A_VER --> BS
    P_SCAN --> BS
    BS --> FPS
    BS --> PCS
</div>




*   **JIT (Just-In-Time) Billing:** Tagihan dikalkulasi secara dinamis saat sistem melakukan inquiry. Dilengkapi Penalty Engine yang otomatis menyematkan denda 2% per bulan untuk keterlambatan pembayaran.
*   **E-Document ber-TTE:** Penerbitan berkas resmi seperti SKPD, SKRD, SSPD, dan Surat Paksa dalam bentuk PDF yang disahkan menggunakan QR Code Tanda Tangan Elektronik (TTE) tersertifikasi dari BSrE.

### 4. Pembayaran (Payment Gateway H2H) & Penagihan

<div class="mermaid">
sequenceDiagram
    autonumber
    participant WP as Wajib Pajak
    participant Bank as Open API Bank
    participant Bapenda as API Bapenda

    WP->>Bank: Memasukkan Kode Bayar/Billing
    Bank->>Bapenda: POST /api/inquiry (Cek Tagihan)
    Bapenda-->>Bank: 200 OK (Rincian Tagihan & WP)
    Bank-->>WP: Menampilkan Nominal Tagihan

    WP->>Bank: Otorisasi Pembayaran (PIN)
    Bank->>Bank: Proses Mutasi Debet Rekening
    Bank->>Bapenda: POST /api/payment (Pelunasan)
    Bapenda->>Bapenda: Update Status "LUNAS", Generate NTPD
    Bapenda-->>Bank: 200 OK (NTPD & Konfirmasi)
    Bank-->>WP: Menerbitkan Bukti Bayar Sah
</div>




*   **Integrasi H2H Perbankan:** Sistem terhubung dengan Bank Pembangunan Daerah (BPD Sultra) serta bank nasional (Mandiri, BNI, BRI) melalui standar Open API dan SNAP BI.
*   **Omni-Channel Payment:** Menyediakan kanal pembayaran Virtual Account (VA) dinamis dan QRIS dinamis untuk memastikan rekonsiliasi seketika (H+0) tanpa delay pencatatan.
*   **Penagihan Otomatis:** Generate Surat Teguran dan Surat Paksa Pelaksanaan Penyitaan (SPMP) apabila Wajib Pajak mengabaikan tagihan.

## BAB IV: RENCANA IMPLEMENTASI (ROADMAP)

Tahapan implementasi sistem dikembangkan secara bertahap dan dieksekusi dengan jadwal mikro (agile):

### A. Roadmap Mikro Pembangunan Sistem:
*   **Fase 1 (Minggu 1-2) - Fondasi:** Setup skema database, pengisian Master Data Jenis Retribusi/Pajak, serta pengaturan Autentikasi Pengguna.
*   **Fase 2 (Minggu 3-4) - Fitur Inti:** Pendaftaran (SPOPD), Workflow Verifikasi, Kalkulasi Tagihan (Billing), dan Formula Engine.
*   **Fase 3 (Minggu 5-6) - Pembayaran & Pelaporan:** Integrasi Payment Gateway, Laporan harian/bulanan, dan pembuatan Dashboard Analytics.
*   **Fase 4 (Minggu 7-8) - Frontend Mobile:** Peluncuran aplikasi portal warga untuk pengecekan tagihan, serta aplikasi petugas untuk verifikasi dan sinkronisasi data lapangan.

### B. Rencana Ekspansi Jangka Panjang:
*   **Tahap Integrasi Basis Data:** Penyatuan database PBB-P2 dan BPHTB dengan sistem pajak daerah lainnya (PBJT).
*   **Tahap Ekosistem Nasional:** Integrasi dengan NIK Dukcapil, data Pertanahan (BPN), serta izin usaha dari DPMPTSP (OSS-RBA).
*   **Tahap Optimalisasi Cerdas:** Memanfaatkan kecerdasan buatan (Data Mining) untuk menggali potensi silang dan mendeteksi anomali setoran secara cerdas.

## BAB V: RENCANA ANGGARAN BIAYA (RAB) SOFTWARE

Berdasarkan penawaran implementasi dari CV Sarjana Komputer Indonesia, berikut adalah rincian anggaran yang dialokasikan untuk pengembangan 3 platform utama (Web Admin BAPENDA, Aplikasi Mobile Petugas, dan Aplikasi Mobile Masyarakat), serta integrasi pembayaran digital dan dokumentasi:

| No | Uraian / Kegiatan | Biaya |
|:---:|---|---:|
| **A** | **Platform Admin BAPENDA (Web Dashboard)** | |
| 1 | Dashboard Manajemen Data & Pengguna | Rp 30.000.000 |
| 2 | Modul Penetapan & Penagihan Pajak/Retribusi | Rp 25.000.000 |
| 3 | Modul Laporan & Dashboard Monitoring PAD | Rp 22.500.000 |
| **B** | **Platform Petugas (Aplikasi Mobile)** | |
| 4 | Aplikasi Petugas Lapangan (Pendataan & Verifikasi) | Rp 35.000.000 |
| **C** | **Platform Masyarakat (Aplikasi Mobile)** | |
| 5 | Aplikasi Mobile Wajib Pajak (Pembayaran & Informasi) | Rp 30.000.000 |
| 6 | Integrasi Pembayaran Digital (QRIS/VA/E-Wallet) | Rp 20.000.000 |
| **D** | **Infrastruktur & Dokumentasi** | |
| 7 | Setup Server & Deployment | Rp 15.000.000 |
| 8 | User Guide & Dokumentasi | Rp 0 |
| | **Subtotal** | **Rp 177.500.000** |
| | **PPN (12%)** | **Rp 21.300.000** |
| | **Total Biaya** | **Rp 198.800.000** |

## BAB VI: PENUTUP

Sistem Informasi Pendapatan Daerah terpadu ini merupakan instrumen strategis untuk mewujudkan efisiensi dan transparansi. Melalui integrasi penuh Single Sign-On dan integrasi sistem perbankan (Cashless), tingkat kebocoran PAD dapat diminimalisasi secara drastis, sehingga target rasio kepatuhan pajak daerah dapat terus ditingkatkan menuju kemandirian fiskal Pemerintah Kota Baubau.

---

## LAMPIRAN: KESIMPULAN & REKOMENDASI LANGKAH STRATEGIS

Metode E-Katalog sangat mungkin digunakan untuk pengadaan Sistem Digital Bapenda. Namun, disarankan untuk menggeser semua agenda uji coba sistem (Host-to-Host dan perizinan) ke waktu setelah kontrak E-Katalog ditandatangani. Pemanfaatan bulan Mei s/d September sebelum Perubahan Anggaran difokuskan pada pematangan dokumen Detail Engineering Design (DED) sistem, koordinasi kebijakan antar-lembaga untuk perizinan, dan penyusunan draf Kerangka Acuan Kerja (KAK) pengadaan.

### 1. Fase Pematangan & Pra-Pengadaan (Mei – September)
Fase ini difokuskan pada penguatan legalitas, penyusunan dokumen teknis, dan persiapan lelang sebelum APBD Perubahan (Oktober) disahkan.
*   **Penyusunan DED (Detail Engineering Design):** Mematangkan cetak biru arsitektur sistem. DED harus merincikan 4 komponen utama (Backend API Laravel, Dashboard Admin React, PWA Mobile Warga, dan Mobile Petugas), serta skema keamanan standar perbankan seperti IP Whitelisting dan enkripsi Signature HMAC-SHA256.
*   **Penyusunan Draf KAK (Kerangka Acuan Kerja):** Menetapkan spesifikasi teknis dan ruang lingkup yang mengunci kebutuhan spesifik daerah, seperti fitur Just-In-Time (JIT) Billing untuk kalkulasi denda real-time, Penalty Engine, modul Uji Petik (Spot Check), dan generator dokumen ber-TTE terintegrasi BSrE.
*   **Pematangan Koordinasi Lintas Lembaga (MoU/PKS):** Karena uji coba sistem digeser, waktu ini dimanfaatkan untuk mengurus aspek legal dan Perjanjian Kerja Sama (PKS) non-teknis. Ini meliputi kesepakatan sharing data dengan Dukcapil (untuk validasi NIK), DPMPTSP (untuk NIB), dan Bank RKUD (Bank Sultra/Himbara) untuk persiapan integrasi Host-to-Host (H2H).

### 2. Fase Pengadaan & Perubahan Anggaran (Oktober)
Pada tahap ini dokumen spesifikasi sudah siap saji.
*   **Tender LPSE / Seleksi Jasa Konsultansi Badan Usaha:** Mengingat arsitektur sistem ini sangat spesifik (custom-built) dengan kebutuhan pengolahan data terpusat (Single Data Source), auto-klasifikasi puluhan jenis retribusi, dan Driver-Based Payment Gateway multi-bank, metode lelang LPSE lebih direkomendasikan jika produk E-Katalog tidak mampu mengakomodasi spesifikasi custom tersebut.

### 3. Fase Implementasi & Uji Coba Teknis (Pasca Penandatanganan Kontrak)
Setelah kontrak LPSE/E-Katalog diterbitkan, pihak vendor/pengembang memiliki landasan hukum (legal standing) yang kuat untuk memulai eksekusi.
*   **Uji Coba Host-to-Host (H2H) Perbankan:** Pihak Bank biasanya mewajibkan adanya kontrak resmi sebelum memberikan akses Sandbox (lingkungan uji coba), dokumentasi API (seperti standar SNAP BI), dan Client ID / Secret Key. Uji coba inquiry NIK/Kode Bayar dan sinkronisasi pembayaran real-time dieksekusi di fase ini.
*   **Integrasi TTE & Pengujian End-to-End:** Menguji penerbitan E-SKPD dan E-SSPD yang langsung dibubuhi stempel QR Code (TTE) setelah bank mengirimkan webhook notifikasi pembayaran sukses.

*Catatan: Keputusan untuk menunda uji coba hingga vendor resmi terpilih akan mencegah pemborosan waktu kerja (resource drain) dari tim IT internal Bapenda maupun pihak Bank mitra pada fase di mana anggaran belum pasti.*
