import markdown

md_file = '/Users/pondokit/Herd/retribusi-api/docs/Dokumen_Perencanaan_Sistem_Pendapatan_Baubau.md'
html_file = '/Users/pondokit/Herd/visibaubau-4.0/old/dokumen/Dokumen_Perencanaan_Sistem_Pendapatan_Baubau.html'

md_content = """<div align="center">
  <img src="https://visibaubau4.vercel.app/assets/img/sdm/CV.%20Sarjana%20Komputer%20Indonesia.png" alt="CV. Sarjana Komputer Indonesia" width="250"/>
</div>

# DOKUMEN PERENCANAAN (LAPORAN AKHIR) PENGEMBANGAN SISTEM INFORMASI PENDAPATAN DAERAH TERINTEGRASI
**BADAN PENDAPATAN DAERAH (BAPENDA) KOTA BAUBAU**

---

## BAB I: PENDAHULUAN

### 1.1 Latar Belakang
Pengembangan sistem layanan pemerintah yang efisien, transparan, dan terintegrasi menjadi prioritas absolut bagi Pemerintah Kota Baubau, khususnya dalam ranah krusial pengelolaan Pendapatan Asli Daerah (PAD). Pada tahun 2023 dan 2024, audit internal dan evaluasi digitalisasi mengidentifikasi bahwa ekosistem lama sudah tidak mampu lagi menampung beban transaksi dinamis dan tuntutan pembayaran digital warga. Kondisi ini mengharuskan adanya perombakan total dari sistem monolitik menuju arsitektur berorientasi layanan (*Service-Oriented Architecture*) yang gesit, menggunakan teknologi *Unified Database* dan konektivitas API tingkat perbankan.

### 1.2 Rencana Kerja Perencanaan
Ruang lingkup pekerjaan perencanaan kegiatan ini dipecah ke dalam 3 tahap fundamental untuk menjamin transisi digital yang terukur:
1. **Tahap 1: Studi dan Analisis Kelayakan:** Menganalisis kondisi basis data SIMPAD lama, mengekstraksi skema tabel, dan mengidentifikasi anomali data (pembersihan data).
2. **Tahap 2: Perencanaan Teknis & Arsitektur:** Merancang desain topologi *cloud server* (VPS) dan *Virtual Private Network* (VPN) yang sesuai dengan protokol keamanan transaksi finansial.
3. **Tahap 3: Pelaporan dan Dokumentasi:** Menyusun Dokumen Laporan Akhir (Cetak Biru) ini sebagai panduan teknis bagi seluruh *developer* dan *stakeholders*.

### 1.3 Dasar Hukum
Penyelenggaraan perancangan arsitektur sistem pengelolaan uang negara/daerah wajib dilandasi kepatuhan hukum (*legal compliance*) yang ketat:
1. Undang-Undang Nomor 11 Tahun 2008 tentang Informasi dan Transaksi Elektronik.
2. Undang-Undang Nomor 1 Tahun 2022 tentang Hubungan Keuangan Antara Pemerintah Pusat dan Pemerintahan Daerah.
3. Peraturan Daerah (Perda) Kota Baubau Nomor 1 Tahun 2024 tentang Pajak Daerah dan Retribusi Daerah (PDRD).
4. Pedoman Standar Nasional Open API Pembayaran (SNAP) Bank Indonesia.

### 1.4 Ruang Lingkup
Penyelenggaraan sistem ini difokuskan di **Kantor Badan Pendapatan Daerah Kota Baubau**. Sistem ini meliputi pendaftaran subjek dan objek pajak, penetapan nilai pungutan (*billing*), kalkulasi denda keterlambatan, validasi pembayaran nirsentuh, serta penerbitan ketetapan hukum berbasis Tanda Tangan Elektronik.

### 1.5 Pendekatan dan Metodologi
Pendekatan yang digunakan adalah **Agile System Development Life Cycle (SDLC)** dengan metode partisipatif. Metode *Agile* memungkinkan umpan balik berkelanjutan (*continuous feedback*) dari pimpinan Bapenda pada setiap iterasi (*sprint*) pengembangan.

---

## BAB II: KAJIAN TEORI

### 2.1 Teori Arsitektur Sistem Informasi
*   **N-Tier Architecture:** Memisahkan sistem ke dalam lapisan terisolasi (Presentasi, Aplikasi, dan Data) yang memberikan fleksibilitas skalabilitas tinggi.
*   **Service-Oriented Architecture (SOA):** Menggunakan API RESTful di mana setiap fungsi bisnis diubah menjadi sebuah layanan mikro.

### 2.2 Teori Basis Data Modern
*   **Unified Database:** Teori JSON Metadata dalam Relational Database memungkinkan sistem menyimpan variabel bebas tanpa batas di dalam satu *field* tunggal, memangkas beban pemanggilan kueri tabel yang berlebihan.
*   **In-Memory Caching (Redis):** Menampung data yang sering dicari secara temporer langsung ke dalam RAM, mengubah latensi menjadi mikrodek.

### 2.3 Teori Kriptografi dan Keamanan Siber
*   **Asymmetric Encryption (RSA-2048):** Kriptografi kunci publik/privat memastikan data finansial hanya bisa dibongkar oleh peladen tujuan.
*   **Hash-based Message Authentication Code (HMAC-SHA256):** Algoritma yang menjamin integritas paket data agar mustahil untuk dimanipulasi (*Man-in-the-Middle Attack*).

---

## BAB III: GAMBARAN SISTEM EKSISTING (STUDI SEBELUMNYA)

Sebelum melangkah pada arsitektur baru, perancangan blueprint ini dilandasi oleh studi mendalam terhadap sistem warisan (*legacy system*) yang selama bertahun-tahun digunakan oleh Bapenda Kota Baubau. Observasi pada struktur direktori aplikasi lama (/old) mengungkapkan temuan kritis:

### 3.1 Arsitektur Monolitik dan Keterpecahan Kode (Silo)
Sistem lama dibangun menggunakan fondasi **PHP Native** murni tanpa MVC. Manajemen jenis pajak dipisahkan secara fisik ke dalam dua direktori yang sama sekali berbeda:
1. **Direktori `/9pajak`:** Menangani 9 jenis Pajak Daerah secara eksklusif.
2. **Direktori `/bphtb`:** Menangani BPHTB pada antarmuka dan pangkalan data yang terpisah.
Kondisi silo ini menciptakan Redudansi Data Massal di mana satu entitas warga wajib diinput berulang kali.

### 3.2 Tumpukan Teknologi Usang (Tech-Stack Obsolescence)
Sistem antarmuka lama mengandalkan pustaka kuno seperti **jQuery versi 1.4.2** dan **Ext-Core JS**. Pustaka ini tidak lagi mendapatkan pembaruan keamanan, menjadikannya sarang kerentanan eksploitasi, serta tidak ramah pengguna layar gawai (*non-responsive*).

### 3.3 Bom Waktu Basis Data (*Monolithic Bottleneck*)
Basis data relasional yang kaku menyebabkan antrean kueri menumpuk pada satu peladen saat jatuh tempo massal, berujung pada kelumpuhan sistem (*timeout*). Fakta ini menjadi landasan migrasi ke teknologi *Unified Database* dan *Laravel 11*.

---

## BAB IV: ANALISIS KEBUTUHAN DAN ARSITEKTUR SISTEM

### 4.1 Skema Topologi Infrastruktur Cloud

<div class="mermaid">
flowchart TD
    classDef cloud fill:#e3f2fd,stroke:#1565c0,stroke-width:2px,color:#0d47a1,rx:5px,ry:5px
    classDef server fill:#fff3e0,stroke:#e65100,stroke-width:2px,color:#e65100,rx:5px,ry:5px
    classDef db fill:#e8f5e9,stroke:#2e7d32,stroke-width:2px,color:#1b5e20,rx:5px,ry:5px
    classDef firewall fill:#ffebee,stroke:#c62828,stroke-width:2px,color:#b71c1c,rx:5px,ry:5px
    classDef network fill:#f3e5f5,stroke:#303f9f,stroke-width:2px,color:#1a237e,rx:5px,ry:5px

    subgraph AreaPublik ["🌐 KANAL INTERNET PUBLIK"]
        Warga["📱 Wajib Pajak"]:::cloud
        Perbankan["🏦 Sistem Bank"]:::cloud
    end

    subgraph AreaKeamanan ["🛡️ PERIMETER KEAMANAN"]
        WAF["🧱 Web App Firewall (WAF)"]:::firewall
        LB["⚖️ Load Balancer"]:::network
    end

    subgraph AreaVPS ["☁️ KLASTER APLIKASI (VPS)"]
        Node1["⚙️ App Server Node 01"]:::server
        Node2["⚙️ App Server Node 02"]:::server
    end

    subgraph AreaDB ["💾 DATA CENTER"]
        DBMaster["🗄️ Database Master"]:::db
        Redis["⚡ Redis Cache"]:::db
    end

    Warga -->|HTTPS| WAF
    Perbankan -->|IPSEC VPN| WAF
    WAF --> LB
    LB --> Node1
    LB --> Node2
    Node1 <--> DBMaster
    Node1 <--> Redis
    Node2 <--> DBMaster
    Node2 <--> Redis
</div>

### 4.2 Spesifikasi Proses Bisnis
1.  **Modul Registrasi E-SPOPD:** Wajib Pajak menginput NIK, NPWP, dan Titik Koordinat Usaha secara mandiri lewat gawai tanpa formulir kertas.
2.  **Modul Penetapan JIT (Just-In-Time):** Algoritma dinamis mengeksekusi perhitungan denda 2% per bulan secara kumulatif langsung saat Wajib Pajak meminta nomor tagihan (*inquiry*), memangkas *cache* data statis bulanan.
3.  **Tanda Tangan Elektronik (TTE):** Sistem mencetak berkas Ketetapan PDF yang dibubuhi sertifikat digital BSrE BSSN secara otomatis.
4.  **Integrasi Host-to-Host (H2H):** Menerapkan standar asinkron T+0 BI SNAP. Uang yang dipotong di Bank tereskalasi dalam milidetik ke *dashboard* kas Bapenda tanpa campur tangan rekapitulasi manual staf.

### 4.3 Standar Keamanan Sistem
*   **Geo-Blocking & IP Whitelisting:** Titik akses pembayaran dikunci hanya untuk IP milik Bank Sultra/Mandiri, mencegah injeksi bot anonim.
*   **Otorisasi Sesi JWT:** Sesi admin dikunci menggunakan JSON Web Token (*stateless*) bertenaga pendek, mengeliminasi risiko pembajakan sesi persisten.

---

## BAB V: RENCANA KERJA DAN TIM PENGEMBANG

### 5.1 Susunan Personil Inti
Pengembangan arsitektur digital diserahkan kepada tim ahli *Enterprise Level*:
1.  **Project Manager (1 Orang):** Menjembatani *sprint* teknis dengan kebutuhan birokrasi regulasi pimpinan Bapenda.
2.  **System Analyst & Database Architect (1 Orang):** Merancang skema relasional entitas, Normalisasi, serta memetakan matriks struktur *Unified Metadata*.
3.  **Senior Backend Engineer (2 Orang):** Spesialis *framework* Laravel 11. Mengintegrasikan abstraksi API RESTful, JWT, dan Kriptografi Asimetris.
4.  **Frontend/Mobile Developer (2 Orang):** Spesialis kerangka antarmuka (React.js/Flutter).
5.  **Quality Assurance (1 Orang):** Petugas khusus pengujian beban otomatis (JMeter) dan pencari celah kerentanan (Pen-Test).

### 5.2 Spesifikasi Kebutuhan Infrastruktur (Server)

| Kategori Komponen | Spesifikasi Rekomendasi (Minimal) | Deskripsi Utilitas |
| :--- | :--- | :--- |
| **Virtual Private Server (VPS)** | Neo Cloud / GCP / AWS. vCPU: 8 Cores, RAM: 16 GB, SSD: 250 GB. | Menjalankan *Web Server* Nginx, PHP 8.3, dan Daemon API. |
| **Database Server** | Dedicated Instance (vCPU: 4 Core, RAM 8GB), MySQL 8.0+. | Disarankan terpisah (*decoupled*) dari mesin VPS aplikasi. |
| **Cache Server** | Redis In-Memory Datastore. | Menyimpan data sesi (*tokens*) demi efisiensi latensi. |
| **Keamanan Perimeter** | Web Application Firewall (WAF). | Pencegahan DDoS dan *bot-protection*. |
| **Object Storage** | S3-Compatible Storage (AWS S3/MinIO). | Menyimpan foto objek dan PDF SKPD ber-TTE. |

---

## BAB VI: INDIKASI PROGRAM

### 6.1 Jadwal Pelaksanaan Konstruksi (Timeline)

| Tahapan Fase & Aktivitas Proyek | Bulan 1 | Bulan 2 | Bulan 3 | Bulan 4 |
| :--- | :---: | :---: | :---: | :---: |
| **FASE A: ANALISIS & DESAIN** | | | | |
| 1. *Reverse Engineering Legacy* & Desain UI | ▉ | ▉ | | |
| 2. Normalisasi *Unified Database* | | ▉ | | |
| **FASE B: CODING & INTEGRASI BACKEND** | | | | |
| 1. Modul JIT Billing, Denda & Autentikasi | | ▉ | ▉ | |
| 2. *Endpoint API* H2H Kriptografi SNAP BI | | | ▉ | ▉ |
| **FASE C: PENGUJIAN & PELUNCURAN** | | | | |
| 1. *Sandbox Testing* Perbankan & Uji Beban | | | | ▉ |
| 2. *User Acceptance Testing* & *Soft Launch* | | | | ▉ |

### 6.2 Indikasi Perawatan Berkelanjutan (Maintenance)
*   **Harian:** Pengecekan stabilitas koneksi API H2H Bank, pemantauan utilisasi CPU VPS.
*   **Mingguan:** *Backup* basis data ke lokasi geografis sekunder (*off-site disaster recovery*).
*   **Bulanan:** *Patching* pembaruan Laravel untuk menangkal *Zero-Day Exploit*, validasi masa aktif sertifikat SSL dan WAF.

---

## BAB VII: KESIMPULAN

Dokumen Perencanaan (Blueprint) Arsitektur Sistem Informasi Pendapatan Daerah ini merupakan fondasi restrukturisasi absolut instrumen pemerintahan dari komputasi warisan (*PHP Native*) menuju *Enterprise-Grade Platform*. Melalui *Reverse Engineering* pada sistem *legacy* (SIMPAD lama di direktori `/old`), terbukti bahwa inkonsistensi data massal yang ditimbulkan dari desain *silo* antara Pajak dan BPHTB harus segera ditangani menggunakan *Unified Database*.

Penyediaan arsitektur N-Tier dengan penyaring *Web Application Firewall* (WAF) akan menggaransi ketersediaan tinggi (*High Availability*). Keterlibatan kalkulator dinamis *Just-In-Time* (JIT) dan protokol Asinkron H2H menihilkan kebutuhan rekapitulasi data lembur bulanan. Ekosistem revolusioner ini secara sadar dibangun sebagai infrastruktur mesin masa depan (*Future-Proof*), yang menopang fondasi transparansi perbendaharaan, memblokir celah kebocoran fiskal, dan memahat nama Kota Baubau di barisan terdepan pelayanan *Smart City* nasional.

"""

with open(md_file, 'w') as f:
    f.write(md_content)

html_template = """<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Akhir: Perencanaan Sistem Pendapatan Baubau</title>
    <link rel="stylesheet" href="../assets/css/bootstrap-5.0.0-alpha-2.min.css" />
    <link rel="stylesheet" href="../assets/css/LineIcons.2.0.css"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Garamond:wght@700&display=swap" rel="stylesheet">
    <style>
        body { background: #e0e0e0; font-family: 'Roboto', sans-serif; color: #333; line-height: 1.8; }
        .page { width: 21cm; min-height: 29.7cm; padding: 2cm; margin: 1.5cm auto; background: white; box-shadow: 0 0 15px rgba(0, 0, 0, 0.1); position: relative; page-break-after: always; }
        .page-cover { display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; }
        .page-cover .logo { width: 140px; margin-bottom: 40px; }
        .page-cover h1 { font-family: 'Garamond', serif; font-size: 2.8rem; color: #1a237e; margin-bottom: 20px; text-transform: uppercase; font-weight: 700; }
        .page-cover h2 { font-size: 1.5rem; color: #444; font-weight: 500; letter-spacing: 1px; }
        .page-footer { position: absolute; bottom: 1.5cm; left: 2cm; right: 2cm; text-align: center; font-size: 9pt; color: #888; border-top: 1px solid #eee; padding-top: 10px; }
        h1, h2, h3, h4 { color: #1a237e; font-weight: 700; margin-top: 30px; margin-bottom: 15px; }
        h2 { font-size: 18pt; border-bottom: 3px solid #e8eaf6; padding-bottom: 8px; margin-top: 40px; text-transform: uppercase; }
        h3 { font-size: 14pt; color: #283593; margin-top: 25px; }
        p, li { font-size: 11pt; text-align: justify; margin-bottom: 15px; color: #424242; }
        ul, ol { padding-left: 25px; margin-bottom: 25px; }
        strong { color: #212121; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 30px; font-size: 10pt; }
        th, td { border: 1px solid #cfd8dc; padding: 12px; text-align: left; }
        th { background-color: #f5f5f5; color: #1a237e; font-weight: 700; text-align: center; }
        tr:nth-child(even) { background-color: #fafafa; }
        @media print {
            body { background: white; }
            .page { width: auto; min-height: auto; margin: 0; padding: 2cm; box-shadow: none; border: none; page-break-after: always; }
            .print-fab { display: none !important; }
        }
        .mermaid { text-align: center; margin: 40px auto; background-color: #fafafa; padding: 20px; border-radius: 8px; border: 1px solid #eeeeee; }
        .mermaid svg { max-width: 95% !important; height: auto !important; }
        .print-fab { position: fixed; bottom: 40px; right: 40px; width: 65px; height: 65px; background-color: #1a237e; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; box-shadow: 0 6px 20px rgba(26,35,126,0.3); cursor: pointer; z-index: 1000; transition: transform 0.2s; }
        .print-fab:hover { transform: scale(1.1); background-color: #283593; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
    <script>mermaid.initialize({startOnLoad:true});</script>
</head>
<body>
    <div class="page page-cover">
        <img src="https://visibaubau4.vercel.app/assets/img/sdm/CV.%20Sarjana%20Komputer%20Indonesia.png" alt="CV Sarjana Komputer Indonesia" class="logo">
        <h1>DOKUMEN PERENCANAAN LAPORAN AKHIR PENGEMBANGAN SISTEM INFORMASI PENDAPATAN DAERAH TERINTEGRASI</h1>
        <h2>BADAN PENDAPATAN DAERAH (BAPENDA) KOTA BAUBAU</h2>
        <div class="page-footer">Blueprint Teknis Sistem Informasi Pendapatan Daerah Kota Baubau | Halaman Dokumen Laporan Akhir</div>
    </div>
    <div class="page">
"""

html_parsed = markdown.markdown(md_content, extensions=['tables'])

html_parsed = html_parsed.replace('<p><div class="mermaid">', '<div class="mermaid">')
html_parsed = html_parsed.replace('</div></p>', '</div>')

with open(html_file, 'w') as f:
    f.write(html_template + html_parsed + """
        <div class="page-footer">Blueprint Teknis Sistem Informasi Pendapatan Daerah Kota Baubau | Halaman Dokumen Laporan Akhir</div>
    </div>
    
    <div class="print-fab" onclick="window.print()">
        <i class="lni lni-printer"></i>
    </div>
</body>
</html>
""")
