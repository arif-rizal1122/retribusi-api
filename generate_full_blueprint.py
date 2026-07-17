import re

md_file = '/Users/pondokit/Herd/retribusi-api/docs/Dokumen_Perencanaan_Sistem_Pendapatan_Baubau.md'
html_file = '/Users/pondokit/Herd/visibaubau-4.0/old/dokumen/Dokumen_Perencanaan_Sistem_Pendapatan_Baubau.html'

markdown_content = """<div align="center">
  <img src="https://visibaubau4.vercel.app/assets/img/sdm/CV.%20Sarjana%20Komputer%20Indonesia.png" alt="CV. Sarjana Komputer Indonesia" width="250"/>
</div>

# DOKUMEN PERENCANAAN PENGEMBANGAN SISTEM INFORMASI PENDAPATAN DAERAH TERINTEGRASI
**BADAN PENDAPATAN DAERAH (BAPENDA) KOTA BAUBAU**

---

## BAB I: PENDAHULUAN

### 1. Latar Belakang
Pengelolaan Pendapatan Asli Daerah (PAD) yang optimal membutuhkan instrumen teknologi informasi yang andal, transparan, dan terintegrasi. Secara historis, infrastruktur digital yang digunakan oleh Badan Pendapatan Daerah (BAPENDA) Kota Baubau beroperasi dalam ekosistem yang terfragmentasi (silo). Sistem-sistem seperti SIMPAD (Sistem Informasi Manajemen Pendapatan Daerah), SIMBPHTB, dan SIMPBB berdiri secara independen tanpa adanya sinkronisasi basis data hulu-ke-hilir. Hal ini memunculkan redudansi data, inefisiensi pelaporan, serta tingginya risiko kebocoran penerimaan daerah akibat rekonsiliasi pembayaran yang masih mengandalkan proses manual.

Untuk mengatasi tantangan tersebut, pengembangan Sistem Informasi Pendapatan Daerah Terintegrasi diinisiasi. Inovasi utama dari pengembangan ini adalah peralihan dari arsitektur basis data relasional yang kaku menuju arsitektur *Unified Database* dengan dukungan skalabilitas *Metadata JSON*. Pendekatan ini tidak hanya mengonsolidasikan seluruh jenis pungutan pajak dan retribusi ke dalam satu *Single Source of Truth*, namun juga memberikan kelincahan (*agility*) bagi pimpinan daerah dalam mengambil keputusan strategis berbasis data aktual (*real-time dashboard*). Sistem ini secara khusus dirancang untuk mengotomatisasi proses birokrasi mulai dari pendaftaran Wajib Pajak, penetapan tagihan secara instan, hingga rekonsiliasi pembayaran nirsentuh (*cashless*) melalui gerbang pembayaran (*payment gateway*).

### 2. Maksud dan Tujuan
Maksud dari penyusunan dokumen perencanaan ini adalah untuk memberikan pedoman teknis (Cetak Biru / *Blueprint*) yang komprehensif bagi seluruh pemangku kepentingan (*stakeholders*) yang terlibat dalam siklus pengembangan perangkat lunak. 

Tujuan utama dari pengembangan sistem ini meliputi:
1. **Penyatuan Ekosistem Data:** Menciptakan satu pangkalan data terpusat (*Unified Database*) untuk meniadakan redudansi data Wajib Pajak antar jenis pajak/retribusi.
2. **Otomatisasi Penetapan dan Penagihan:** Mengimplementasikan *Just-In-Time (JIT) Billing Engine* yang mampu mengalkulasi tagihan dan denda secara dinamis (termasuk denda 2% per bulan untuk keterlambatan).
3. **Digitalisasi Pelayanan:** Mendorong kepatuhan Wajib Pajak melalui kemudahan akses pendaftaran mandiri (E-SPOPD) dan pembayaran berbagai kanal digital (QRIS, Virtual Account) secara mandiri (*self-service*).
4. **Keamanan dan Transparansi:** Mengintegrasikan Tanda Tangan Elektronik (TTE) tersertifikasi untuk penerbitan dokumen resmi, serta menyajikan data analitik PAD harian yang presisi kepada pimpinan daerah.

### 3. Landasan Hukum
Arsitektur dan alur bisnis pada aplikasi ini dirancang sedemikian rupa agar sepenuhnya patuh terhadap kerangka regulasi dan perundang-undangan terbaru yang berlaku, antara lain:
*   **Undang-Undang Nomor 1 Tahun 2022** tentang Hubungan Keuangan Antara Pemerintah Pusat dan Pemerintahan Daerah (HKPD).
*   **Peraturan Daerah (Perda) Kota Baubau Nomor 1 Tahun 2024** tentang Pajak Daerah dan Retribusi Daerah (PDRD).
*   **Peraturan Wali Kota Baubau Nomor 58 Tahun 2024** tentang Tata Cara Pemungutan Pajak Daerah dan Retribusi Daerah.
*   Standar Nasional Open API Pembayaran (SNAP) dari Bank Indonesia terkait interkoneksi sistem perbankan.

---

## BAB II: ARSITEKTUR DAN EKOSISTEM SISTEM

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
Arsitektur sistem dibangun dengan menggunakan pendekatan *N-Tier Architecture* (Arsitektur Multi-Lapis) yang secara ketat mendisagregasi lapisan antarmuka pengguna (Presentasi), logika bisnis (Aplikasi), dan lapisan penyimpanan data (Basis Data). Desain ini memastikan bahwa setiap komponen dapat dikembangkan, diperbarui, dan diskalakan secara independen tanpa mengganggu stabilitas modul lainnya. 

Kanal akses yang berasal dari entitas eksternal—baik itu *Payment Gateway* perbankan maupun aplikasi pengguna yang diakses oleh Wajib Pajak—diwajibkan melewati jalur protokol terenkripsi (HTTPS/TLS 1.2+). Sebagai lapis pertama dari pertahanan sistem perimeter, *Web Application Firewall* (WAF) diimplementasikan untuk melakukan mitigasi risiko secara proaktif. WAF bertugas memfilter anomali lalu lintas data, menangkal serangan *Distributed Denial of Service* (DDoS), mencegah penetrasi *SQL Injection* maupun *Cross-Site Scripting* (XSS), dan sekaligus beroperasi sebagai *Load Balancer* untuk mendistribusikan beban trafik akses agar merata.

Memasuki zona inti yang di-*host* pada infrastruktur *Neo Cloud VPS Bapenda*, sistem digerakkan oleh *API Gateway* tersentralisasi yang dibangun di atas kerangka kerja (*framework*) Laravel. Backend ini berfungsi sebagai *orchestrator* mikrolayanan yang mengamankan rute transaksi dan memvalidasi permintaan (*request*) antara *client* dan kluster *Unified Database*. Untuk menekan beban pembacaan data di pangkalan data utama dan mengatasi masalah latensi *query* repetitif, sistem diinjeksi dengan teknologi *Redis In-Memory Cache*. Konfigurasi arsitektural komprehensif ini digagas untuk mencapai *High Availability* (Ketersediaan Tinggi) dan *Zero-Downtime Deployment* guna melayani transaksi setoran daerah selama 24 jam nonstop.

---

## BAB III: SPESIFIKASI MODUL DAN PROSES BISNIS

Guna memastikan setiap aspek pemungutan pajak daerah berjalan sesuai kaidah tata kelola pemerintahan yang baik (*Good Corporate Governance*), spesifikasi fungsional sistem dibagi ke dalam empat ekosistem platform yang saling terintegrasi: Modul Web Admin (Pusat Komando), Modul Aplikasi Petugas (Operasional Lapangan), Modul Aplikasi Warga (Layanan Mandiri), dan Modul Logika Inti (*Core Backend Engine*).

### 1. Pendaftaran dan Validasi Basis Data
Fase registrasi merupakan gerbang awal dari validasi data Wajib Pajak. Pada modul ini, sistem menerapkan alur digitalisasi untuk pendaftaran Surat Pemberitahuan Objek Pajak Daerah (E-SPOPD) dan Surat Pemberitahuan Pajak Daerah (SPTPD). Melalui *portal mandiri*, masyarakat dapat menginput data perpajakan mereka yang secara otomatis akan divalidasi silang.
Penomoran administrasi seperti Nomor Pokok Wajib Pajak Daerah (NPWPD) dihasilkan secara terprogram menggunakan algoritma *auto-increment* regional segera setelah petugas seksi pendaftaran memberikan persetujuan (verifikasi) melalui *Web Admin*. Logika validasi ini memastikan tidak ada data ganda (*duplicate entry*) yang mengotori *master data* pemerintah kota.

### 2. Modul Uji Petik dan Pengawasan (*Surveillance*)
Modul aplikasi petugas lapangan dilengkapi dengan teknologi Sistem Informasi Geografis (GIS) dan pelacakan spasial yang dirancang untuk mencegah terjadinya pelaporan omzet fiktif. Saat petugas mendatangi lokasi usaha, sistem akan memvalidasi posisi petugas berdasarkan koordinat *Global Positioning System* (GPS) sebelum mengizinkan proses *input* laporan Uji Petik harian.
Lebih lanjut, *dashboard* pimpinan akan menyajikan peta *heatmap* visual. Wilayah atau titik usaha yang terindikasi sebagai penunggak akan ditandai dengan zona merah, sedangkan wajib pajak yang patuh (*compliant*) ditandai dengan warna hijau. Pendekatan spasial ini sangat krusial bagi Bidang Pengawasan untuk menentukan arah strategi penagihan *door-to-door*.

### 3. Modul Penetapan (*Billing Engine*) dan Tanda Tangan Elektronik

<div class="mermaid">
flowchart TD
    %% Styling
    classDef core fill:#e8eaf6,stroke:#3f51b5,stroke-width:2px,color:#1a237e,rx:8px,ry:8px
    classDef dash fill:#e0f7fa,stroke:#00838f,stroke-width:2px,color:#004d40,rx:8px,ry:8px
    classDef app fill:#fbe9e7,stroke:#d84315,stroke-width:2px,color:#bf360c,rx:8px,ry:8px

    subgraph CoreBackend ["⚙️ Logika Aplikasi Core (Backend)"]
        BS["🧮 Modul Billing JIT"]:::core
        FPS["📜 Formula Parser Dinamis"]:::core
        PCS["⏱️ Kalkulator Denda 2%"]:::core
    end

    subgraph AdminDashboard ["💻 Antarmuka Web Bapenda"]
        A_VER["✅ Verifikasi Data SPTPD"]:::dash
        A_AUDIT["📊 Audit & Rekonsiliasi"]:::dash
    end

    subgraph PetugasApp ["📱 Perangkat Mobile Lapangan"]
        P_SCAN["📷 Pemindai QR Penagihan"]:::app
        P_PAY["💳 Input Setoran Tunai"]:::app
    end
    
    A_VER -->|Trigger Penetapan| BS
    P_SCAN -->|Inquiry Tunggakan| BS
    BS -->|Hitung Pokok| FPS
    BS -->|Validasi Telat Bayar| PCS
</div>

*Billing Engine* beroperasi dengan mekanisme *Just-In-Time* (JIT). Berbeda dengan sistem lawas di mana tagihan di-generate di awal tahun (yang rentan membebani pangkalan data), JIT Billing menghitung besaran nilai tagihan secara dinamis tepat pada milidetik ketika ada interogasi (*inquiry*) ke server. Modul ini terintegrasi erat dengan *Penalty Engine* yang otomatis menyematkan bunga denda progresif sesuai peraturan perundang-undangan tanpa intervensi manusia.
Dari sisi legalitas dokumen, seluruh produk akhir ketetapan seperti Surat Ketetapan Pajak Daerah (SKPD) maupun Surat Tagihan Pajak Daerah (STPD) di-generate dalam bentuk fail PDF yang sah secara hukum berkat pembubuhan kode QR Tanda Tangan Elektronik (TTE) yang diverifikasi oleh Balai Sertifikasi Elektronik (BSrE) BSSN.

### 4. Integrasi Gateway Pembayaran (Host-to-Host)

<div class="mermaid">
sequenceDiagram
    autonumber
    participant WP as Wajib Pajak
    participant Bank as Open API Bank
    participant Bapenda as API Bapenda

    WP->>Bank: Memasukkan Kode Bayar/Billing
    Bank->>Bapenda: POST /api/inquiry (Cek Tagihan)
    Bapenda-->>Bank: 200 OK (Rincian Tagihan & Identitas)
    Bank-->>WP: Menampilkan Detail Rincian

    WP->>Bank: Otorisasi Pembayaran (Konfirmasi PIN)
    Bank->>Bank: Mutasi Debet Rekening Nasabah
    Bank->>Bapenda: POST /api/payment (Konfirmasi Pelunasan)
    Bapenda->>Bapenda: Update Status "LUNAS", Generate NTPD
    Bapenda-->>Bank: 200 OK (NTPD Disahkan)
    Bank-->>WP: Menerbitkan Bukti Bayar / Struk
</div>

Sebagai bentuk konkret digitalisasi Pendapatan Asli Daerah, platform menyediakan interkonektivitas antar-server (*Host-to-Host*) dengan Bank Pembangunan Daerah (Bank Sultra) dan institusi perbankan nasional lainnya. Arsitektur jembatan API (*Application Programming Interface*) dibangun di atas kepatuhan *Standar Nasional Open API Pembayaran* (SNAP) Bank Indonesia. Melalui rute integrasi omni-kanal ini, Wajib Pajak dimanjakan dengan kebebasan memilih metode bayar, mulai dari *Virtual Account* (VA) yang dikonfigurasi dinamis, hingga *Quick Response Code Indonesian Standard* (QRIS).
Arsitektur transaksi asinkron di atas membedakan proses *Inquiry* (pengecekan jumlah tagihan dan denda yang dikalkulasi mesin JIT) dan proses *Payment* (pemotongan saldo yang diakhiri penyampaian *webhook* lunas ke Bapenda). Hal ini menjamin nihilnya jeda waktu perpindahan data, serta menjadikan proses rekonsiliasi harian menjadi *H+0* tanpa penundaan.

---

## BAB IV: STANDAR KEAMANAN SISTEM

Sistem perpajakan daerah menyimpan data demografi warga dan menangani lalu lintas transaksi bernilai miliaran rupiah setiap harinya. Oleh karena itu, postur keamanan sibernetika (*cybersecurity posture*) menjadi tulang punggung keberlangsungan layanan. Spesifikasi mitigasi mencakup:

1. **Komunikasi Kriptografi Host-to-Host:** 
   Seluruh pertukaran data keuangan (JSON *payload*) yang mengalir antara server BAPENDA dan server Perbankan diwajibkan untuk dienkripsi. *Header request* dilindungi dengan *Signature* berbasis *Hash-based Message Authentication Code* menggunakan algoritma SHA-256 (HMAC-SHA256). Penggunaan Kunci Asimetris (RSA-2048) ditambahkan guna memastikan integritas pesan dan melakukan mekanisme *non-repudiation* (anti-sangkalan).
2. **Kendali Akses Geografis dan Jaringan (IP Whitelisting):** 
   Titik akhir (*endpoint*) API kritis seperti proses transaksi pelunasan (*payment webhook*) bersifat rahasia dan tidak terekspos di jaringan bebas. Hanya *Internet Protocol* (IP) statis terdaftar yang sepenuhnya dikontrol oleh Bank Mitra dan internal infrastruktur pemerintah kota yang mendapatkan izin akses. Permintaan dari IP anonim akan secara otomatis digugurkan di level *firewall*.
3. **Autentikasi Terdistribusi berbasis Token:** 
   Sesi komunikasi pada aplikasi *dashboard web* maupun perangkat *mobile* tidak menggunakan struktur basis data statis yang usang, melainkan mengandalkan JSON Web Token (JWT). Token ini dirancang *stateless* dan dilengkapi *expiration time* berdurasi pendek, yang memaksa penyegaran sesi (*token refresh*) berkala guna menutup ruang eksploitasi oleh pihak peretas.

---

## BAB V: RENCANA IMPLEMENTASI DAN PENGUJIAN (ROADMAP)

Siklus hidup pengembangan (*System Development Life Cycle*) akan diselenggarakan menggunakan metodologi hibrida, mengedepankan presisi analisis di awal dan *agile* di fase pengerjaan. Cetak biru pelaksanaan dirancang menjadi beberapa tahapan esensial:

### A. Fase Konstruksi Inti dan Pematangan
*   **Sprint Arsitektur & Pangkalan Data:** Merancang dan melakukan normalisasi skema *Unified Database*. Pendefinisian relasi antara entitas Wajib Pajak, Objek Pajak, dan Histori Tagihan (SKPD).
*   **Sprint Logika Bisnis:** Pembuatan modul Registrasi WP (E-SPOPD), konfigurasi *Formula Parser* per jenis pajak (misalnya perbedaan formula Hotel vs Restoran vs MBLB), serta pengujian fungsi *JIT Billing*.

### B. Fase Integrasi Eksternal (SIT)
*   **Pengujian Sandbox Perbankan:** Menyelesaikan administrasi kunci kriptografi Bank, dan melakukan *System Integration Testing* (SIT) melalui server bohongan (*sandbox*) perbankan untuk memvalidasi alur *Inquiry* dan *Payment*.
*   **Integrasi TTE BSrE BSSN:** Mengembangkan jembatan komunikasi (*bridge*) menuju server Balai Sertifikasi Elektronik untuk otomatisasi penerbitan spesimen SKPD digital berbasis stempel QR.

### C. Fase Validasi Kualitas (UAT) dan Go-Live
*   **User Acceptance Testing (UAT):** Proses peragaan purwarupa sistem (*mock-up testing*) secara komprehensif bersama pemangku jabatan terkait di internal BAPENDA. Menguji beban fungsional dan pelaporan.
*   **Phased Rollout:** Tahapan pelepasan (*deployment*) secara gradual. Dimulai dengan rilis *Dashboard Admin* sebagai fondasi awal, disusul peluncuran *Aplikasi Petugas Mobile*, dan diakhiri dengan rilis *Portal Mobile Warga* di pasar aplikasi publik (PlayStore).

---

## BAB VI: PENUTUP

Dokumen Blueprint Arsitektur Sistem Informasi Pendapatan Daerah ini disusun bukan sekadar sebagai pemenuhan formalitas perancangan teknologi, melainkan sebagai sebuah pilar fundamental transformasi kelembagaan digital di lingkungan Badan Pendapatan Daerah (BAPENDA) Kota Baubau. Ketiadaan struktur arsitektur yang kokoh seringkali berujung pada investasi infrastruktur IT yang gagal mengimbangi laju dinamis peraturan daerah, sehingga menghambat optimalisasi penerimaan kas daerah.

Dengan dianutnya filosofi *Unified Database* yang fleksibel, ditambah pengawalan berlapis dari sisi *Cyber Security* perbankan (H2H SNAP BI), pemerintah daerah kini disokong oleh ekosistem yang mapan, otonom, dan *future-proof* (tahan banting di masa depan). Digitalisasi komprehensif ini secara nyata akan menutup semua keran celah kebocoran fiskal yang diakibatkan oleh human-error, memaksimalkan target *collection rate* wajib pajak secara radikal, serta mewujudkan transparansi akuntabilitas publik menuju tatakelola pemerintahan yang benar-benar bersih dan responsif.
"""

with open(md_file, 'w') as f:
    f.write(markdown_content)

# HTML formatting wrapper
html_template = """<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumen Perencanaan Sistem Pendapatan Baubau</title>
    <link rel="stylesheet" href="../assets/css/bootstrap-5.0.0-alpha-2.min.css" />
    <link rel="stylesheet" href="../assets/css/LineIcons.2.0.css"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Garamond:wght@700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #e0e0e0;
            font-family: 'Roboto', sans-serif;
            color: #333;
        }
        .page {
            width: 21cm;
            min-height: 29.7cm;
            padding: 1.5cm;
            margin: 1.5cm auto;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            position: relative;
            page-break-after: always;
        }
        .page-cover {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .page-cover .logo {
            width: 120px;
            margin-bottom: 30px;
        }
        .page-cover h1 {
            font-family: 'Garamond', serif;
            font-size: 2.8rem;
            color: #1a237e;
            margin-bottom: 15px;
            text-transform: uppercase;
        }
        .page-cover h2 {
            font-size: 1.4rem;
            color: #555;
            font-weight: 400;
        }
        .page-footer {
            position: absolute;
            bottom: 1cm;
            left: 1.5cm;
            right: 1.5cm;
            text-align: center;
            font-size: 9pt;
            color: #999;
        }
        h1, h2, h3, h4 {
            font-weight: 700;
            color: #1a237e;
            margin-top: 20px;
            margin-bottom: 12px;
            border-bottom: 2px solid #dfe3ee;
            padding-bottom: 6px;
        }
        h1 { font-size: 22pt; }
        h2 { font-size: 18pt; }
        h3 { font-size: 15pt; border-bottom: none; margin-top: 25px; }
        p, li {
            font-size: 11pt;
            line-height: 1.6;
            text-align: justify;
            margin-bottom: 12px;
        }
        ul, ol {
            padding-left: 20px;
            margin-bottom: 15px;
        }
        @media print {
            body { background: white; }
            .page {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 1.5cm;
                box-shadow: none;
                border: none;
                page-break-after: always;
            }
            .print-fab { display: none; }
        }
    
        .mermaid {
            text-align: center;
            margin: 25px auto;
        }
        .mermaid svg {
            max-width: 90% !important;
            height: auto !important;
        }
        .print-fab { position: fixed; bottom: 40px; right: 40px; width: 60px; height: 60px; background-color: #3B5998; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); cursor: pointer; z-index: 1000; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
    <script>mermaid.initialize({startOnLoad:true});</script>
</head>
<body>
    <div class="page page-cover">
        <img src="https://visibaubau4.vercel.app/assets/img/sdm/CV.%20Sarjana%20Komputer%20Indonesia.png" alt="CV Sarjana Komputer Indonesia" class="logo">
        <h1>DOKUMEN PERENCANAAN PENGEMBANGAN SISTEM INFORMASI PENDAPATAN DAERAH TERINTEGRASI</h1>
        <h2>BADAN PENDAPATAN DAERAH (BAPENDA) KOTA BAUBAU</h2>
        <div class="page-footer">Blueprint Teknis Sistem Informasi Pendapatan Daerah Kota Baubau</div>
    </div>
    <div class="page">
"""

html_body = """
<h2>BAB I: PENDAHULUAN</h2>
<h3>1. Latar Belakang</h3>
<p>Pengelolaan Pendapatan Asli Daerah (PAD) yang optimal membutuhkan instrumen teknologi informasi yang andal, transparan, dan terintegrasi. Secara historis, infrastruktur digital yang digunakan oleh Badan Pendapatan Daerah (BAPENDA) Kota Baubau beroperasi dalam ekosistem yang terfragmentasi (silo). Sistem-sistem seperti SIMPAD (Sistem Informasi Manajemen Pendapatan Daerah), SIMBPHTB, dan SIMPBB berdiri secara independen tanpa adanya sinkronisasi basis data hulu-ke-hilir. Hal ini memunculkan redudansi data, inefisiensi pelaporan, serta tingginya risiko kebocoran penerimaan daerah akibat rekonsiliasi pembayaran yang masih mengandalkan proses manual.</p>
<p>Untuk mengatasi tantangan tersebut, pengembangan Sistem Informasi Pendapatan Daerah Terintegrasi diinisiasi. Inovasi utama dari pengembangan ini adalah peralihan dari arsitektur basis data relasional yang kaku menuju arsitektur <em>Unified Database</em> dengan dukungan skalabilitas <em>Metadata JSON</em>. Pendekatan ini tidak hanya mengonsolidasikan seluruh jenis pungutan pajak dan retribusi ke dalam satu <em>Single Source of Truth</em>, namun juga memberikan kelincahan (<em>agility</em>) bagi pimpinan daerah dalam mengambil keputusan strategis berbasis data aktual (<em>real-time dashboard</em>). Sistem ini secara khusus dirancang untuk mengotomatisasi proses birokrasi mulai dari pendaftaran Wajib Pajak, penetapan tagihan secara instan, hingga rekonsiliasi pembayaran nirsentuh (<em>cashless</em>) melalui gerbang pembayaran (<em>payment gateway</em>).</p>

<h3>2. Maksud dan Tujuan</h3>
<p>Maksud dari penyusunan dokumen perencanaan ini adalah untuk memberikan pedoman teknis (Cetak Biru / <em>Blueprint</em>) yang komprehensif bagi seluruh pemangku kepentingan (<em>stakeholders</em>) yang terlibat dalam siklus pengembangan perangkat lunak.</p>
<p>Tujuan utama dari pengembangan sistem ini meliputi:</p>
<ol>
<li><strong>Penyatuan Ekosistem Data:</strong> Menciptakan satu pangkalan data terpusat (<em>Unified Database</em>) untuk meniadakan redudansi data Wajib Pajak antar jenis pajak/retribusi.</li>
<li><strong>Otomatisasi Penetapan dan Penagihan:</strong> Mengimplementasikan <em>Just-In-Time (JIT) Billing Engine</em> yang mampu mengalkulasi tagihan dan denda secara dinamis (termasuk denda 2% per bulan untuk keterlambatan).</li>
<li><strong>Digitalisasi Pelayanan:</strong> Mendorong kepatuhan Wajib Pajak melalui kemudahan akses pendaftaran mandiri (E-SPOPD) dan pembayaran berbagai kanal digital (QRIS, Virtual Account) secara mandiri (<em>self-service</em>).</li>
<li><strong>Keamanan dan Transparansi:</strong> Mengintegrasikan Tanda Tangan Elektronik (TTE) tersertifikasi untuk penerbitan dokumen resmi, serta menyajikan data analitik PAD harian yang presisi kepada pimpinan daerah.</li>
</ol>

<h3>3. Landasan Hukum</h3>
<p>Arsitektur dan alur bisnis pada aplikasi ini dirancang sedemikian rupa agar sepenuhnya patuh terhadap kerangka regulasi dan perundang-undangan terbaru yang berlaku, antara lain:</p>
<ul>
<li><strong>Undang-Undang Nomor 1 Tahun 2022</strong> tentang Hubungan Keuangan Antara Pemerintah Pusat dan Pemerintahan Daerah (HKPD).</li>
<li><strong>Peraturan Daerah (Perda) Kota Baubau Nomor 1 Tahun 2024</strong> tentang Pajak Daerah dan Retribusi Daerah (PDRD).</li>
<li><strong>Peraturan Wali Kota Baubau Nomor 58 Tahun 2024</strong> tentang Tata Cara Pemungutan Pajak Daerah dan Retribusi Daerah.</li>
<li>Standar Nasional Open API Pembayaran (SNAP) dari Bank Indonesia terkait interkoneksi sistem perbankan.</li>
</ul>

<h2>BAB II: ARSITEKTUR DAN EKOSISTEM SISTEM</h2>

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

<p><strong>Deskripsi Arsitektur Topologi Jaringan:</strong><br>
Arsitektur sistem dibangun dengan menggunakan pendekatan <em>N-Tier Architecture</em> (Arsitektur Multi-Lapis) yang secara ketat mendisagregasi lapisan antarmuka pengguna (Presentasi), logika bisnis (Aplikasi), dan lapisan penyimpanan data (Basis Data). Desain ini memastikan bahwa setiap komponen dapat dikembangkan, diperbarui, dan diskalakan secara independen tanpa mengganggu stabilitas modul lainnya.</p>
<p>Kanal akses yang berasal dari entitas eksternal—baik itu <em>Payment Gateway</em> perbankan maupun aplikasi pengguna yang diakses oleh Wajib Pajak—diwajibkan melewati jalur protokol terenkripsi (HTTPS/TLS 1.2+). Sebagai lapis pertama dari pertahanan sistem perimeter, <em>Web Application Firewall</em> (WAF) diimplementasikan untuk melakukan mitigasi risiko secara proaktif. WAF bertugas memfilter anomali lalu lintas data, menangkal serangan <em>Distributed Denial of Service</em> (DDoS), mencegah penetrasi <em>SQL Injection</em> maupun <em>Cross-Site Scripting</em> (XSS), dan sekaligus beroperasi sebagai <em>Load Balancer</em> untuk mendistribusikan beban trafik akses agar merata.</p>
<p>Memasuki zona inti yang di-<em>host</em> pada infrastruktur <em>Neo Cloud VPS Bapenda</em>, sistem digerakkan oleh <em>API Gateway</em> tersentralisasi yang dibangun di atas kerangka kerja (<em>framework</em>) Laravel. Backend ini berfungsi sebagai <em>orchestrator</em> mikrolayanan yang mengamankan rute transaksi dan memvalidasi permintaan (<em>request</em>) antara <em>client</em> dan kluster <em>Unified Database</em>. Untuk menekan beban pembacaan data di pangkalan data utama dan mengatasi masalah latensi <em>query</em> repetitif, sistem diinjeksi dengan teknologi <em>Redis In-Memory Cache</em>. Konfigurasi arsitektural komprehensif ini digagas untuk mencapai <em>High Availability</em> (Ketersediaan Tinggi) dan <em>Zero-Downtime Deployment</em> guna melayani transaksi setoran daerah selama 24 jam nonstop.</p>

<h2>BAB III: SPESIFIKASI MODUL DAN PROSES BISNIS</h2>
<p>Guna memastikan setiap aspek pemungutan pajak daerah berjalan sesuai kaidah tata kelola pemerintahan yang baik (<em>Good Corporate Governance</em>), spesifikasi fungsional sistem dibagi ke dalam empat ekosistem platform yang saling terintegrasi: Modul Web Admin (Pusat Komando), Modul Aplikasi Petugas (Operasional Lapangan), Modul Aplikasi Warga (Layanan Mandiri), dan Modul Logika Inti (<em>Core Backend Engine</em>).</p>

<h3>1. Pendaftaran dan Validasi Basis Data</h3>
<p>Fase registrasi merupakan gerbang awal dari validasi data Wajib Pajak. Pada modul ini, sistem menerapkan alur digitalisasi untuk pendaftaran Surat Pemberitahuan Objek Pajak Daerah (E-SPOPD) dan Surat Pemberitahuan Pajak Daerah (SPTPD). Melalui <em>portal mandiri</em>, masyarakat dapat menginput data perpajakan mereka yang secara otomatis akan divalidasi silang.<br>
Penomoran administrasi seperti Nomor Pokok Wajib Pajak Daerah (NPWPD) dihasilkan secara terprogram menggunakan algoritma <em>auto-increment</em> regional segera setelah petugas seksi pendaftaran memberikan persetujuan (verifikasi) melalui <em>Web Admin</em>. Logika validasi ini memastikan tidak ada data ganda (<em>duplicate entry</em>) yang mengotori <em>master data</em> pemerintah kota.</p>

<h3>2. Modul Uji Petik dan Pengawasan (<em>Surveillance</em>)</h3>
<p>Modul aplikasi petugas lapangan dilengkapi dengan teknologi Sistem Informasi Geografis (GIS) dan pelacakan spasial yang dirancang untuk mencegah terjadinya pelaporan omzet fiktif. Saat petugas mendatangi lokasi usaha, sistem akan memvalidasi posisi petugas berdasarkan koordinat <em>Global Positioning System</em> (GPS) sebelum mengizinkan proses <em>input</em> laporan Uji Petik harian.<br>
Lebih lanjut, <em>dashboard</em> pimpinan akan menyajikan peta <em>heatmap</em> visual. Wilayah atau titik usaha yang terindikasi sebagai penunggak akan ditandai dengan zona merah, sedangkan wajib pajak yang patuh (<em>compliant</em>) ditandai dengan warna hijau. Pendekatan spasial ini sangat krusial bagi Bidang Pengawasan untuk menentukan arah strategi penagihan <em>door-to-door</em>.</p>

<h3>3. Modul Penetapan (<em>Billing Engine</em>) dan Tanda Tangan Elektronik</h3>

<div class="mermaid">
flowchart TD
    %% Styling
    classDef core fill:#e8eaf6,stroke:#3f51b5,stroke-width:2px,color:#1a237e,rx:8px,ry:8px
    classDef dash fill:#e0f7fa,stroke:#00838f,stroke-width:2px,color:#004d40,rx:8px,ry:8px
    classDef app fill:#fbe9e7,stroke:#d84315,stroke-width:2px,color:#bf360c,rx:8px,ry:8px

    subgraph CoreBackend ["⚙️ Logika Aplikasi Core (Backend)"]
        BS["🧮 Modul Billing JIT"]:::core
        FPS["📜 Formula Parser Dinamis"]:::core
        PCS["⏱️ Kalkulator Denda 2%"]:::core
    end

    subgraph AdminDashboard ["💻 Antarmuka Web Bapenda"]
        A_VER["✅ Verifikasi Data SPTPD"]:::dash
        A_AUDIT["📊 Audit & Rekonsiliasi"]:::dash
    end

    subgraph PetugasApp ["📱 Perangkat Mobile Lapangan"]
        P_SCAN["📷 Pemindai QR Penagihan"]:::app
        P_PAY["💳 Input Setoran Tunai"]:::app
    end
    
    A_VER -->|Trigger Penetapan| BS
    P_SCAN -->|Inquiry Tunggakan| BS
    BS -->|Hitung Pokok| FPS
    BS -->|Validasi Telat Bayar| PCS
</div>

<p><em>Billing Engine</em> beroperasi dengan mekanisme <em>Just-In-Time</em> (JIT). Berbeda dengan sistem lawas di mana tagihan di-generate di awal tahun (yang rentan membebani pangkalan data), JIT Billing menghitung besaran nilai tagihan secara dinamis tepat pada milidetik ketika ada interogasi (<em>inquiry</em>) ke server. Modul ini terintegrasi erat dengan <em>Penalty Engine</em> yang otomatis menyematkan bunga denda progresif sesuai peraturan perundang-undangan tanpa intervensi manusia.<br>
Dari sisi legalitas dokumen, seluruh produk akhir ketetapan seperti Surat Ketetapan Pajak Daerah (SKPD) maupun Surat Tagihan Pajak Daerah (STPD) di-generate dalam bentuk fail PDF yang sah secara hukum berkat pembubuhan kode QR Tanda Tangan Elektronik (TTE) yang diverifikasi oleh Balai Sertifikasi Elektronik (BSrE) BSSN.</p>

<h3>4. Integrasi Gateway Pembayaran (Host-to-Host)</h3>

<div class="mermaid">
sequenceDiagram
    autonumber
    participant WP as Wajib Pajak
    participant Bank as Open API Bank
    participant Bapenda as API Bapenda

    WP->>Bank: Memasukkan Kode Bayar/Billing
    Bank->>Bapenda: POST /api/inquiry (Cek Tagihan)
    Bapenda-->>Bank: 200 OK (Rincian Tagihan & Identitas)
    Bank-->>WP: Menampilkan Detail Rincian

    WP->>Bank: Otorisasi Pembayaran (Konfirmasi PIN)
    Bank->>Bank: Mutasi Debet Rekening Nasabah
    Bank->>Bapenda: POST /api/payment (Konfirmasi Pelunasan)
    Bapenda->>Bapenda: Update Status "LUNAS", Generate NTPD
    Bapenda-->>Bank: 200 OK (NTPD Disahkan)
    Bank-->>WP: Menerbitkan Bukti Bayar / Struk
</div>

<p>Sebagai bentuk konkret digitalisasi Pendapatan Asli Daerah, platform menyediakan interkonektivitas antar-server (<em>Host-to-Host</em>) dengan Bank Pembangunan Daerah (Bank Sultra) dan institusi perbankan nasional lainnya. Arsitektur jembatan API (<em>Application Programming Interface</em>) dibangun di atas kepatuhan <em>Standar Nasional Open API Pembayaran</em> (SNAP) Bank Indonesia. Melalui rute integrasi omni-kanal ini, Wajib Pajak dimanjakan dengan kebebasan memilih metode bayar, mulai dari <em>Virtual Account</em> (VA) yang dikonfigurasi dinamis, hingga <em>Quick Response Code Indonesian Standard</em> (QRIS).<br>
Arsitektur transaksi asinkron di atas membedakan proses <em>Inquiry</em> (pengecekan jumlah tagihan dan denda yang dikalkulasi mesin JIT) dan proses <em>Payment</em> (pemotongan saldo yang diakhiri penyampaian <em>webhook</em> lunas ke Bapenda). Hal ini menjamin nihilnya jeda waktu perpindahan data, serta menjadikan proses rekonsiliasi harian menjadi <em>H+0</em> tanpa penundaan.</p>

<h2>BAB IV: STANDAR KEAMANAN SISTEM</h2>
<p>Sistem perpajakan daerah menyimpan data demografi warga dan menangani lalu lintas transaksi bernilai miliaran rupiah setiap harinya. Oleh karena itu, postur keamanan sibernetika (<em>cybersecurity posture</em>) menjadi tulang punggung keberlangsungan layanan. Spesifikasi mitigasi mencakup:</p>
<ol>
<li><strong>Komunikasi Kriptografi Host-to-Host:</strong><br>Seluruh pertukaran data keuangan (JSON <em>payload</em>) yang mengalir antara server BAPENDA dan server Perbankan diwajibkan untuk dienkripsi. <em>Header request</em> dilindungi dengan <em>Signature</em> berbasis <em>Hash-based Message Authentication Code</em> menggunakan algoritma SHA-256 (HMAC-SHA256). Penggunaan Kunci Asimetris (RSA-2048) ditambahkan guna memastikan integritas pesan dan melakukan mekanisme <em>non-repudiation</em> (anti-sangkalan).</li>
<li><strong>Kendali Akses Geografis dan Jaringan (IP Whitelisting):</strong><br>Titik akhir (<em>endpoint</em>) API kritis seperti proses transaksi pelunasan (<em>payment webhook</em>) bersifat rahasia dan tidak terekspos di jaringan bebas. Hanya <em>Internet Protocol</em> (IP) statis terdaftar yang sepenuhnya dikontrol oleh Bank Mitra dan internal infrastruktur pemerintah kota yang mendapatkan izin akses. Permintaan dari IP anonim akan secara otomatis digugurkan di level <em>firewall</em>.</li>
<li><strong>Autentikasi Terdistribusi berbasis Token:</strong><br>Sesi komunikasi pada aplikasi <em>dashboard web</em> maupun perangkat <em>mobile</em> tidak menggunakan struktur basis data statis yang usang, melainkan mengandalkan JSON Web Token (JWT). Token ini dirancang <em>stateless</em> dan dilengkapi <em>expiration time</em> berdurasi pendek, yang memaksa penyegaran sesi (<em>token refresh</em>) berkala guna menutup ruang eksploitasi oleh pihak peretas.</li>
</ol>

<h2>BAB V: RENCANA IMPLEMENTASI DAN PENGUJIAN (ROADMAP)</h2>
<p>Siklus hidup pengembangan (<em>System Development Life Cycle</em>) akan diselenggarakan menggunakan metodologi hibrida, mengedepankan presisi analisis di awal dan <em>agile</em> di fase pengerjaan. Cetak biru pelaksanaan dirancang menjadi beberapa tahapan esensial:</p>
<h3>A. Fase Konstruksi Inti dan Pematangan</h3>
<ul>
<li><strong>Sprint Arsitektur & Pangkalan Data:</strong> Merancang dan melakukan normalisasi skema <em>Unified Database</em>. Pendefinisian relasi antara entitas Wajib Pajak, Objek Pajak, dan Histori Tagihan (SKPD).</li>
<li><strong>Sprint Logika Bisnis:</strong> Pembuatan modul Registrasi WP (E-SPOPD), konfigurasi <em>Formula Parser</em> per jenis pajak (misalnya perbedaan formula Hotel vs Restoran vs MBLB), serta pengujian fungsi <em>JIT Billing</em>.</li>
</ul>
<h3>B. Fase Integrasi Eksternal (SIT)</h3>
<ul>
<li><strong>Pengujian Sandbox Perbankan:</strong> Menyelesaikan administrasi kunci kriptografi Bank, dan melakukan <em>System Integration Testing</em> (SIT) melalui server bohongan (<em>sandbox</em>) perbankan untuk memvalidasi alur <em>Inquiry</em> dan <em>Payment</em>.</li>
<li><strong>Integrasi TTE BSrE BSSN:</strong> Mengembangkan jembatan komunikasi (<em>bridge</em>) menuju server Balai Sertifikasi Elektronik untuk otomatisasi penerbitan spesimen SKPD digital berbasis stempel QR.</li>
</ul>
<h3>C. Fase Validasi Kualitas (UAT) dan Go-Live</h3>
<ul>
<li><strong>User Acceptance Testing (UAT):</strong> Proses peragaan purwarupa sistem (<em>mock-up testing</em>) secara komprehensif bersama pemangku jabatan terkait di internal BAPENDA. Menguji beban fungsional dan pelaporan.</li>
<li><strong>Phased Rollout:</strong> Tahapan pelepasan (<em>deployment</em>) secara gradual. Dimulai dengan rilis <em>Dashboard Admin</em> sebagai fondasi awal, disusul peluncuran <em>Aplikasi Petugas Mobile</em>, dan diakhiri dengan rilis <em>Portal Mobile Warga</em> di pasar aplikasi publik (PlayStore).</li>
</ul>

<h2>BAB VI: PENUTUP</h2>
<p>Dokumen Blueprint Arsitektur Sistem Informasi Pendapatan Daerah ini disusun bukan sekadar sebagai pemenuhan formalitas perancangan teknologi, melainkan sebagai sebuah pilar fundamental transformasi kelembagaan digital di lingkungan Badan Pendapatan Daerah (BAPENDA) Kota Baubau. Ketiadaan struktur arsitektur yang kokoh seringkali berujung pada investasi infrastruktur IT yang gagal mengimbangi laju dinamis peraturan daerah, sehingga menghambat optimalisasi penerimaan kas daerah.</p>
<p>Dengan dianutnya filosofi <em>Unified Database</em> yang fleksibel, ditambah pengawalan berlapis dari sisi <em>Cyber Security</em> perbankan (H2H SNAP BI), pemerintah daerah kini disokong oleh ekosistem yang mapan, otonom, dan <em>future-proof</em> (tahan banting di masa depan). Digitalisasi komprehensif ini secara nyata akan menutup semua keran celah kebocoran fiskal yang diakibatkan oleh <em>human-error</em>, memaksimalkan target <em>collection rate</em> wajib pajak secara radikal, serta mewujudkan transparansi akuntabilitas publik menuju tatakelola pemerintahan yang benar-benar bersih dan responsif.</p>

        <div class="page-footer">Blueprint Teknis Sistem Informasi Pendapatan Daerah Kota Baubau</div>
    </div>
    
    <div class="print-fab" onclick="window.print()">
        <i class="lni lni-printer"></i>
    </div>
</body>
</html>
"""

with open(html_file, 'w') as f:
    f.write(html_template + html_body)

print("Done generating highly narrative blueprint and writing to both files.")
