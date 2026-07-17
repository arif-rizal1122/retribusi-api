<div align="center">
  <img src="https://visibaubau4.vercel.app/assets/img/sdm/CV.%20Sarjana%20Komputer%20Indonesia.png" alt="CV. Sarjana Komputer Indonesia" width="250"/>
</div>

# BLUEPRINT PERANCANGAN PENGEMBANGAN SISTEM INFORMASI PENDAPATAN DAERAH TERINTEGRASI
**BADAN PENDAPATAN DAERAH (BAPENDA) KOTA BAUBAU**

## BAGIAN 1: KONSEP & KEBUTUHAN (TINGKAT BISNIS)

### BAB I: PENDAHULUAN
#### 1. Latar Belakang
Pengembangan sistem informasi pendapatan daerah yang baru diinisiasi untuk mengkonsolidasikan aplikasi yang sebelumnya terpisah secara silo (seperti SIMPAD, SIMBPHTB, dan SIMPBB) menjadi satu platform tunggal yang terintegrasi secara hulu-ke-hilir. Peralihan dari arsitektur database yang kaku menuju sistem Unified Database dengan Metadata JSON akan memberikan kelincahan tinggi serta visibilitas data yang terpusat bagi pimpinan daerah. Sistem ini dirancang untuk mengotomatisasi proses birokrasi, mengamankan pendapatan daerah, meminimalisasi kebocoran, dan meningkatkan transparansi.

#### 2. Tujuan & Sasaran
- **Konsolidasi Data**: Menggabungkan seluruh data wajib pajak dan objek pajak dalam satu basis data tunggal (Single Source of Truth).
- **Peningkatan Kepatuhan**: Mempermudah proses pembayaran dan pendaftaran melalui aplikasi mobile (self-service).
- **Transparansi**: Visibilitas penerimaan pendapatan daerah yang realtime melalui dashboard bagi pimpinan.
- **Keamanan**: Menerapkan standar keamanan perbankan (H2H) dan otentikasi Tanda Tangan Elektronik (TTE).

#### 3. Ruang Lingkup Sistem
Ruang lingkup proyek meliputi perancangan dan implementasi:
1. Backend (API) Berbasis Laravel.
2. Dashboard Admin Berbasis React.
3. Aplikasi Mobile Warga (PWA/React).
4. Aplikasi Mobile Petugas Lapangan (Android/iOS).

#### 4. Landasan Hukum
*   Undang-Undang No. 1 Tahun 2022 tentang Hubungan Keuangan Antara Pemerintah Pusat dan Daerah (HKPD).
*   Peraturan Daerah (Perda) Kota Baubau No. 1 Tahun 2024 tentang Pajak Daerah dan Retribusi Daerah (PDRD).
*   Peraturan Wali Kota Baubau No. 58 Tahun 2024 tentang Tata Cara Pemungutan PDRD.

### BAB II: ANALISIS KEBUTUHAN SISTEM
#### 1. Kebutuhan Fungsional (Functional Requirements)
- **Modul Admin (Dashboard Web)**:
  - Manajemen Master Data (Jenis Pajak, Tarif, Wajib Pajak).
  - Proses verifikasi pendaftaran dan penetapan.
  - Laporan analitik dan rekapitulasi penerimaan.
  - Integrasi TTE untuk dokumen SKPD/SKRD.
- **Modul Warga (Mobile/PWA)**:
  - Registrasi mandiri dan aktivasi akun.
  - Lapor SPT (Surat Pemberitahuan Pajak Daerah) mandiri.
  - Cek tagihan dan pembuatan kode bayar/QRIS.
  - Unduh dokumen E-SSPD/E-SKPD.
- **Modul Petugas (Mobile App)**:
  - Pelaksanaan Uji Petik (Spot Check) dengan GPS.
  - Perekaman temuan lapangan dan bukti foto.
  - Pembuatan tagihan di tempat.
- **Modul Backend (API Engine)**:
  - Formula Parser Engine untuk perhitungan denda otomatis (JIT Billing).
  - Webhook Endpoint untuk menerima konfirmasi pembayaran (H2H Bank).
  
#### 2. Kebutuhan Non-Fungsional (Non-Functional Requirements)
- **Availability**: 99.9% Uptime, didukung dengan failover.
- **Scalability**: Menggunakan load balancer untuk mengantisipasi lonjakan trafik saat jatuh tempo pembayaran.
- **Security**: Enkripsi password (bcrypt), JWT untuk API Auth, HMAC-SHA256 untuk H2H, TLS 1.2+ untuk komunikasi data.

---

## BAGIAN 2: DESAIN TEKNIS (TINGKAT SISTEM)

### BAB III: ARSITEKTUR & INFRASTRUKTUR SISTEM
Sistem dipecah menjadi 4 repositori terpisah agar scalable. Infrastruktur diletakkan pada VPS (Neo Cloud Bapenda) yang diproteksi WAF.

<div class="mermaid">
graph TD
    Client[Wajib Pajak / Petugas] -->|HTTPS| WAF[Web Application Firewall]
    Gateway[Gateway Bank / BNI / BPD] -->|TLS 1.2 / SNAP BI| WAF
    
    WAF --> LoadBalancer[Load Balancer / Nginx]
    
    subgraph VPS Neo Cloud Bapenda
        LoadBalancer --> Frontend[React Dashboard / PWA]
        LoadBalancer --> Backend[Laravel 11 Core API]
        
        Backend --> DB[(PostgreSQL Unified DB)]
        Backend --> Redis[(Redis Cache)]
    end
</div>

### BAB IV: DESAIN BASIS DATA & STRUKTUR DATA
Sistem menggunakan pendekatan **Unified Database**. Metadata fleksibel disimpan dalam format JSON untuk mengakomodir variasi parameter setiap jenis retribusi/pajak tanpa mengubah skema tabel (Schemaless Extensibility).

<div class="mermaid">
erDiagram
    WAJIB_PAJAK ||--o{ OBJEK_PAJAK : memiliki
    OBJEK_PAJAK ||--o{ TAGIHAN : menghasilkan
    TAGIHAN ||--o{ PEMBAYARAN : dilunasi_oleh
    
    WAJIB_PAJAK {
        string npwpd PK
        string nama
        string nik
        string kontak
    }
    
    OBJEK_PAJAK {
        string id_objek PK
        string npwpd FK
        string jenis_pajak
        json metadata
        point koordinat
    }
    
    TAGIHAN {
        string kode_bayar PK
        string id_objek FK
        decimal pokok
        decimal denda
        string status
    }
    
    PEMBAYARAN {
        string ntpn PK
        string kode_bayar FK
        datetime tgl_bayar
        string channel_bank
    }
</div>

### BAB V: DESAIN INTEGRASI & PAYMENT GATEWAY (H2H)
Sistem M-PAD berperan sebagai **Biller** yang menyediakan API bagi Bank (Switching). Alur integrasi mengikuti standar Open API SNAP BI.

#### Alur Inquiry & Payment H2H
<div class="mermaid">
sequenceDiagram
    participant Warga
    participant Bank
    participant Biller_API as M-PAD API

    Note over Warga,Biller_API: 1. Proses Inquiry (Cek Tagihan)
    Warga->>Bank: Input Kode Bayar
    Bank->>Biller_API: POST /api/v1/h2h/inquiry (HMAC Secured)
    Biller_API-->>Bank: Response: Detail Tagihan, Nama, Total
    Bank-->>Warga: Tampilkan Rincian Tagihan

    Note over Warga,Biller_API: 2. Proses Payment (Pelunasan)
    Warga->>Bank: Konfirmasi Bayar & PIN
    Bank->>Bank: Debet Rekening
    Bank->>Biller_API: POST /api/v1/h2h/payment
    Biller_API->>Biller_API: Update Tagihan Lunas & Generate NTPD
    Biller_API-->>Bank: Response: Sukses + NTPD
    Bank-->>Warga: Cetak Struk
</div>

---

## BAGIAN 3: EKSEKUSI & TATA KELOLA (TINGKAT OPERASIONAL)

### BAB VI: STANDAR KEAMANAN & MANAJEMEN RISIKO
Keamanan adalah prioritas mutlak terutama pada alur pembayaran dan TTE.
- **Autentikasi API**: Menggunakan JWT Token untuk internal app, dan Signature HMAC-SHA256 dipadu Asymmetric Key (RSA) untuk komunikasi H2H antar Server.
- **IP Whitelisting**: H2H API hanya bisa diakses oleh IP Address statis milik institusi Perbankan yang telah didaftarkan.
- **Rate Limiting**: Mencegah serangan DDoS / Brute Force pada endpoint login dan inquiry.

### BAB VII: RENCANA PENGUJIAN & PELUNCURAN
Peluncuran akan menggunakan strategi *Phased Rollout*.
- **Tahap 1 (SIT - System Integration Test)**: Pengujian modul kalkulasi JIT Billing dan Formula Engine.
- **Tahap 2 (H2H Sandbox)**: Uji coba koneksi inquiry & payment dengan server Bank (UAT perbankan).
- **Tahap 3 (Bimtek & UAT Admin)**: Pelatihan user dan admin Bapenda serta petugas lapangan.
- **Tahap 4 (Go-Live)**: Rilis portal warga secara publik dan pengalihan transaksi secara resmi.

### BAB VIII: RENCANA IMPLEMENTASI (ROADMAP)
Roadmap terbagi dalam 4 Fase Utama (Sprint @ 2 Minggu):
- **Sprint 1 (Fondasi)**: Setup Repo, Database, Autentikasi, dan Master Data.
- **Sprint 2 (Core Engine)**: Modul Pendaftaran, JIT Billing, dan Formula Parser.
- **Sprint 3 (H2H & TTE)**: Integrasi Bank Gateway dan API BSrE (E-Document).
- **Sprint 4 (Frontend)**: Dashboard Web, PWA Mobile Warga, Aplikasi Petugas, dan Go-Live.

### BAB IX: RENCANA ANGGARAN BIAYA (RAB) SOFTWARE
Berdasarkan penawaran implementasi dari CV Sarjana Komputer Indonesia:

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

### BAB X: PENUTUP & REKOMENDASI STRATEGIS
Sistem Informasi Pendapatan Daerah terpadu ini merupakan instrumen strategis untuk mewujudkan efisiensi dan transparansi PAD.
**Rekomendasi Strategis**: 
Disarankan untuk memprioritaskan Perjanjian Kerja Sama (PKS) dan pengurusan administrasi akses TTE BSrE serta kredensial H2H Bank pada Fase Awal (Sprint 1), dikarenakan proses birokrasi perizinan pihak ketiga cenderung memakan waktu lebih panjang daripada proses *development* sistem itu sendiri.
