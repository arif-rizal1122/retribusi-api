<div align="center">
  <img src="https://visibaubau4.vercel.app/assets/img/sdm/CV.%20Sarjana%20Komputer%20Indonesia.png" alt="CV. Sarjana Komputer Indonesia" width="250"/>
</div>

# DOKUMEN PERENCANAAN (LAPORAN AKHIR) PENGEMBANGAN SISTEM INFORMASI PENDAPATAN DAERAH TERINTEGRASI
**BADAN PENDAPATAN DAERAH (BAPENDA) KOTA BAUBAU**

---

## BAB I: PENDAHULUAN

### 1.1 Latar Belakang
Pengembangan sistem layanan pemerintah yang efisien, transparan, dan terintegrasi menjadi prioritas absolut bagi Pemerintah Kota Baubau, khususnya dalam ranah krusial pengelolaan Pendapatan Asli Daerah (PAD). Dalam rangka meningkatkan kualitas layanan publik, mencegah kebocoran kas daerah, dan mengakselerasi proses birokrasi, perencanaan arsitektur digital yang matang dan terkoordinasi sangat diperlukan. 

Pada tahun 2023 dan 2024, audit internal dan evaluasi digitalisasi mengidentifikasi bahwa ekosistem lama sudah tidak mampu lagi menampung beban transaksi dinamis dan tuntutan pembayaran digital warga. Kondisi ini mengharuskan adanya perombakan total dari sistem monolitik menuju arsitektur berorientasi layanan (*Service-Oriented Architecture*) yang gesit, menggunakan teknologi *Unified Database* dan konektivitas API tingkat perbankan.

### 1.2 Rencana Kerja Perencanaan
Ruang lingkup pekerjaan perencanaan kegiatan ini dipecah ke dalam 3 tahap fundamental untuk menjamin transisi digital yang terukur:

**A. Tahap 1: Studi dan Analisis Kelayakan**
1. Menganalisis kondisi basis data SIMPAD lama, mengekstraksi skema tabel, dan mengidentifikasi anomali data (pembersihan data).
2. Melakukan survei lapangan terhadap perangkat keras (*server eksisting*), kapasitas jaringan internet di kantor Bapenda, serta kesiapan literasi digital SDM operasional.
3. Menganalisis kebutuhan layanan antara Bapenda dengan Bank Mitra (Bank Sultra, BNI, Mandiri) untuk sinkronisasi format *Host-to-Host* (H2H).

**B. Tahap 2: Perencanaan Teknis & Arsitektur**
1. Merancang desain topologi *cloud server* (VPS) dan *Virtual Private Network* (VPN) yang sesuai dengan protokol keamanan transaksi finansial.
2. Menentukan tumpukan teknologi (*Tech-Stack*) baik dari sisi *Framework* aplikasi (Laravel 11, React), pangkalan data (MySQL, Redis), maupun perangkat keamanan (Web Application Firewall).
3. Menyusun rencana tahapan *deployment*, dari versi *Alpha, Beta*, hingga penyebaran masif (Gelar Peluncuran).

**C. Tahap 3: Pelaporan dan Dokumentasi**
1. Menyusun Dokumen Laporan Akhir (Cetak Biru) ini sebagai panduan teknis bagi seluruh *developer* dan *stakeholders*.
2. Mempresentasikan hasil perancangan topologi kepada Kepala Badan Pendapatan Daerah dan tim teknis Diskominfo Kota Baubau guna mencapai persetujuan eksekusi.

### 1.3 Dasar Hukum
Penyelenggaraan perancangan arsitektur sistem pengelolaan uang negara/daerah wajib dilandasi kepatuhan hukum (*legal compliance*) yang ketat:
1.  **Undang-Undang Nomor 11 Tahun 2008** tentang Informasi dan Transaksi Elektronik, sebagaimana telah diubah dengan Undang-Undang Nomor 1 Tahun 2024.
2.  **Undang-Undang Nomor 1 Tahun 2022** tentang Hubungan Keuangan Antara Pemerintah Pusat dan Pemerintahan Daerah (HKPD).
3.  **Undang-Undang Nomor 14 Tahun 2008** tentang Keterbukaan Informasi Publik.
4.  **Peraturan Pemerintah Nomor 71 Tahun 2019** tentang Penyelenggaraan Sistem dan Transaksi Elektronik.
5.  **Peraturan Daerah (Perda) Kota Baubau Nomor 1 Tahun 2024** tentang Pajak Daerah dan Retribusi Daerah (PDRD).
6.  **Peraturan Wali Kota Baubau Nomor 58 Tahun 2024** tentang Tata Cara Pemungutan Pajak Daerah dan Retribusi Daerah.
7.  Pedoman Standar Nasional Open API Pembayaran (SNAP) Bank Indonesia.

### 1.4 Ruang Lingkup
**1.4.1 Ruang Lingkup Lokasi**
Penyelenggaraan sistem ini difokuskan di **Kantor Badan Pendapatan Daerah Kota Baubau** sebagai pusat kendali (*Command Center*). Namun demikian, jangkauan layanan aplikasi akan mengikat seluruh:
*   Tempat Usaha (Hotel, Restoran, Tambang MBLB, dll) di seluruh kecamatan dan kelurahan di wilayah administratif Kota Baubau untuk keperluan perpajakan.
*   Infrastruktur Perbankan (Bank Pembangunan Daerah / BPD Sultra) selaku penyedia kas daerah.
*   Perangkat bergerak (*smartphone*) milik warga untuk pendaftaran E-SPOPD dari mana saja.

**1.4.2 Ruang Lingkup Substansi**
Sistem ini meliputi pendaftaran subjek dan objek pajak, penetapan nilai pungutan (*billing*), kalkulasi denda keterlambatan secara matematis, validasi pembayaran nirsentuh lewat API, serta penerbitan ketetapan hukum berupa SKPD berbasis Tanda Tangan Elektronik Tersertifikasi BSrE BSSN.

---

## BAB II: KAJIAN TEORI

### 2.1 Teori Arsitektur Sistem Informasi
A. **N-Tier Architecture (Arsitektur Multi-Lapis):** Memisahkan sistem ke dalam lapisan terisolasi. Lapisan Presentasi (*Front-end*), Lapisan Aplikasi (*Backend/Logic*), dan Lapisan Data (*Database*). Keunggulannya adalah fleksibilitas skalabilitas; bila pengguna membludak, beban *frontend* dapat diperbesar tanpa harus menyentuh *database*.
B. **Service-Oriented Architecture (SOA):** Menggunakan API (Antarmuka Pemrograman Aplikasi) berarsitektur RESTful. Setiap fungsi bisnis (cek tagihan, bayar tagihan, daftar warga) diubah menjadi sebuah layanan mikro yang dapat dipanggil oleh aplikasi web, aplikasi *mobile*, hingga aplikasi pihak ketiga (Bank).

### 2.2 Teori Basis Data Modern
A. **Unified Database & JSON Schema-less:** Relasi antar tabel yang terlalu banyak (puluhan tabel spesifik untuk tiap jenis pajak) akan memperlambat waktu pemanggilan (*query time*) secara signifikan. Teori JSON Metadata dalam Relational Database memungkinkan sistem menyimpan variabel bebas tanpa batas di dalam satu *field* tunggal. Restoran menyimpan `jumlah_kursi`, sementara PBB menyimpan `luas_tanah` dalam format *key-value* di satu entitas tabel *Objek_Pajak*.
B. **In-Memory Caching (Redis):** Operasi pencarian data ke *Harddisk/SSD* pada MySQL/PostgreSQL memakan waktu beberapa milidetik. Dalam frekuensi jutaan permintaan, ini akan menghancurkan CPU peladen. Redis menampung data yang sering dicari secara temporer langsung ke dalam RAM, mengubah latensi dari hitungan milidetik menjadi mikrodetik.

### 2.3 Teori Kriptografi dan Keamanan Siber
A. **Asymmetric Encryption (RSA-2048):** Kriptografi kunci publik/privat memastikan data finansial *payload* transaksi hanya bisa dibongkar oleh server pemerintah kota, mencegah intersepsi dari agen *man-in-the-middle*.
B. **Hash-based Message Authentication Code (HMAC-SHA256):** Algoritma *hashing* yang meleburkan kombinasi *payload* data dengan *Secret Key*. HMAC menempel pada *Header Request*. Jika ada satu bit karakter (misal angka tagihan dari Rp1.000.000 diubah menjadi Rp10.000 oleh *hacker*), HMAC akan rusak dan server akan menolak permintaan pembayaran.
C. **Distributed Denial of Service (DDoS) Mitigation & WAF:** Web Application Firewall memfilter pola *request* yang mencurigakan (seperti percobaan *login* massal dari bot). Pola trafik disaring berdasarkan heuristik *IP Intelligence*.

---

## BAB III: ANALISIS STUDI SISTEM SEBELUMNYA

Sebelum melangkah pada arsitektur baru, perancangan blueprint ini dilandasi oleh studi mendalam (*Reverse Engineering*) terhadap sistem warisan (*legacy system*) yang selama bertahun-tahun digunakan oleh Bapenda Kota Baubau. Observasi pada *source code* dan struktur direktori aplikasi lama mengungkapkan sejumlah temuan kritis yang menjadi landasan filosofis perombakan sistem.

### 3.1 Arsitektur Monolitik dan Keterpecahan Kode (Silo)
Pemeriksaan pada struktur sistem lama (`/old`) memperlihatkan bahwa aplikasi dibangun tidak menggunakan kerangka kerja MVC (*Model-View-Controller*) modern, melainkan menggunakan fondasi **PHP Native** murni (`index.php`, `main.php`, `login.php`). Lebih memprihatinkan lagi, manajemen jenis pajak dipisahkan secara fisik ke dalam direktori aplikasi yang sama sekali berbeda:
1. **Direktori `/9pajak`:** Menangani 9 jenis Pajak Daerah secara eksklusif.
2. **Direktori `/bphtb`:** Menangani Bea Perolehan Hak atas Tanah dan Bangunan pada antarmuka dan pangkalan data yang terpisah.

Keterpecahan fisik (*silo*) ini bermuara pada mimpi buruk administratif: bila Wajib Pajak A memiliki restoran (Pajak Restoran) dan baru saja membeli ruko (BPHTB), data Wajib Pajak A akan dientri berulang kali di dua *database* berbeda, menciptakan *Redudansi Data Massal*.

### 3.2 Tumpukan Teknologi Usang (Tech-Stack Obsolescence)
Dari hasil bedah kode, sistem antarmuka (*front-end*) lama mengandalkan pustaka (*library*) yang sangat kuno:
* Penggunaan **jQuery versi 1.4.2** (dirilis tahun 2010) dan **Ext-Core JS**. Pustaka usang ini tidak lagi mendapatkan pembaruan keamanan, menjadikannya sarang kerentanan eksploitasi peretas tingkat pemula.
* Antarmuka sama sekali tidak *responsive* (tidak ramah pengguna *smartphone*), sehingga menyulitkan wajib pajak maupun pimpinan yang ingin memantau *dashboard* dari gawai.

### 3.3 Bom Waktu Basis Data (*Monolithic Bottleneck*)
Analisis terhadap fail ekstraksi basis data (`sw_patda_backup.sql`) berukuran 108 Megabytes memperlihatkan desain relasional yang kaku. Seluruh histori, log, dan ketetapan dimasukkan tanpa teknik pemisahan arsip (*archiving*) maupun *caching*. Pada saat jatuh tempo pembayaran massal, antrean *query SQL* pada mesin aplikasi tunggal akan menyebabkan peladen lumpuh sementara (*timeout*), yang sering disalahartikan sebagai "jaringan bank yang lambat".

Berdasarkan studi sistem sebelumnya inilah, arsitektur baru didesain menggunakan **Laravel 11, React.js**, **Unified Database**, dan teknologi **Redis In-Memory Cache** untuk memastikan sejarah kelam infrastruktur IT Bapenda Baubau dikubur secara permanen.

---

## BAB IV: ARSITEKTUR DAN TOPOLOGI INFRASTRUKTUR

Arsitektur jaringan internet dan peladen (*server*) memegang peranan vital untuk menjamin waktu aktif (*uptime*) aplikasi pemerintahan mencapai 99.9%.

### 4.1 Skema Topologi Infrastruktur Cloud

<div class="mermaid">
flowchart TD
    %% Styling
    classDef cloud fill:#e3f2fd,stroke:#1565c0,stroke-width:2px,color:#0d47a1,rx:5px,ry:5px
    classDef server fill:#fff3e0,stroke:#e65100,stroke-width:2px,color:#e65100,rx:5px,ry:5px
    classDef db fill:#e8f5e9,stroke:#2e7d32,stroke-width:2px,color:#1b5e20,rx:5px,ry:5px
    classDef firewall fill:#ffebee,stroke:#c62828,stroke-width:2px,color:#b71c1c,rx:5px,ry:5px
    classDef network fill:#f3e5f5,stroke:#303f9f,stroke-width:2px,color:#1a237e,rx:5px,ry:5px
    
    style AreaPublik fill:#fafafa,stroke:#999,stroke-width:2px,stroke-dasharray: 5 5
    style AreaKeamanan fill:#fce4ec,stroke:#880e4f,stroke-width:2px,stroke-dasharray: 5 5
    style AreaVPS fill:#fff8e1,stroke:#f57f17,stroke-width:2px,stroke-dasharray: 5 5
    style AreaDB fill:#e8f5e9,stroke:#2e7d32,stroke-width:2px,stroke-dasharray: 5 5

    subgraph AreaPublik ["🌐 KANAL INTERNET PUBLIK & INTRANET"]
        Warga["📱 Wajib Pajak (Browser/Mobile)"]:::cloud
        Perbankan["🏦 Sistem Bank Mitra (BPD, Himbara)"]:::cloud
        Petugas["💻 Intranet Bapenda & Pemkot"]:::cloud
    end

    subgraph AreaKeamanan ["🛡️ PERIMETER KEAMANAN SIBER"]
        WAF["🧱 Web Application Firewall (WAF) <br> Anti-DDoS & SSL Termination"]:::firewall
        LB["⚖️ API Load Balancer (Nginx/HAProxy)"]:::network
    end

    subgraph AreaVPS ["☁️ KLASTER APLIKASI (NEO CLOUD VPS)"]
        Node1["⚙️ App Server Node 01 <br> (Laravel 11, PHP 8.3)"]:::server
        Node2["⚙️ App Server Node 02 <br> (Standby / Auto-Scaling)"]:::server
    end

    subgraph AreaDB ["💾 DATA CENTER & PERSISTENSI"]
        DBMaster["🗄️ Database Master <br> (MySQL/PostgreSQL)"]:::db
        DBReplica["🗄️ Database Replikasi <br> (Read-Only & Backup)"]:::db
        Redis["⚡ Redis Cache Server <br> (Session & Query Cache)"]:::db
        ObjectStorage["📁 S3 Object Storage <br> (Penyimpanan Dokumen PDF TTE)"]:::db
    end

    Warga -->|HTTPS TLS 1.3| WAF
    Perbankan -->|IPSEC VPN / mTLS| WAF
    Petugas -->|HTTPS| WAF

    WAF --> LB
    LB -->|Traffic 50%| Node1
    LB -->|Traffic 50%| Node2

    Node1 <-->|Write| DBMaster
    Node1 <-->|Read| DBReplica
    Node1 <--> Redis
    Node1 <--> ObjectStorage

    Node2 <-->|Write| DBMaster
    Node2 <-->|Read| DBReplica
    Node2 <--> Redis
    Node2 <--> ObjectStorage

    DBMaster -.->|Sync Real-time| DBReplica
</div>

### 4.2 Analisis Arsitektur Topologi
Topologi di atas dirancang untuk memberikan Ketersediaan Tinggi (*High Availability*) dengan mereduksi *Single Point of Failure* (Titik Kegagalan Tunggal):
1. **Pemutusan Koneksi Langsung:** Wajib pajak maupun perbankan *tidak pernah* diizinkan menyentuh server aplikasi secara langsung. Semua sambungan di-intersepsi oleh WAF yang melakukan inspeksi paket data (*Deep Packet Inspection*). Paket yang mengandung kode injeksi SQL (*SQL Injection payload*) langsung dihancurkan.
2. **Horizontal Scaling:** Di dalam klaster komputasi, beban kerja aplikasi didistribusikan oleh *Load Balancer* ke beberapa *node* server aplikasi. Apabila terjadi fenomena *traffic spike* di tenggat waktu pembayaran pajak, sistem dapat menduplikasi *node* secara horizontal untuk mengimbangi beban pemrosesan (*Auto-Scaling*).
3. **Replikasi Basis Data:** Penulisan data (pelunasan bayar, daftar akun) diarahkan pada *Database Master*, sementara pembacaan laporan eksekutif atau cetak struk diarahkan ke *Database Replikasi*. Hal ini mengamankan integritas transaksional dari beban *query report* yang berat.

---

## BAB V: ANALISIS SPESIFIKASI PROSES BISNIS

Agar selaras dengan tata tertib administrasi daerah, sistem memecah alur birokrasi menjadi beberapa subsistem elektronik otomatis.

### 5.1 Modul Registrasi Wajib Pajak Elektronik (E-SPOPD)
Mengakhiri era formulir kertas, layanan *Electronic - Surat Pemberitahuan Objek Pajak Daerah* (E-SPOPD) memberikan otonomi bagi warga.
*   Wajib Pajak menginput NIK, NPWP, Titik Koordinat Usaha, serta mengunggah swafoto lokasi kedai/restoran/hotel dari gawai mereka.
*   Dokumen tersimpan di *Cloud Object Storage* sebagai aset digital tanpa memberatkan memori *database* utama.
*   Admin Verifikator memeriksa silang (*cross-checking*) foto dan koordinat dengan peta *Google Maps API*. Jika sah, algoritma memproduksi Nomor Pokok Wajib Pajak Daerah (NPWPD) terenkripsi secara serentak.

### 5.2 Modul Pengawasan Geospasial (Surveillance / Uji Petik)
Kelemahan terbesar Bapenda daerah adalah kurangnya instrumen penegakan pelaporan omzet.
*   **Mobile Tracking:** Aplikasi android petugas pajak dilengkapi limitasi pagar maya (*Geo-Fencing*). Petugas tidak dapat mengirimkan laporan 'Uji Petik' omzet restoran A bila GPS *smartphone*-nya terdeteksi berada di restoran B atau di rumah. Fitur ini menghilangkan fenomena laporan fiktif aparat lapangan.
*   **Executive Dashboard Heatmap:** Setiap wilayah kecamatan dilukiskan di layar monitor pimpinan menggunakan saturasi gradasi warna (Panas/Dingin). Wilayah yang kontribusi pajaknya stagnan, namun data PLN/PDAM menunjukkan aktivitas tinggi, akan ditandai berisiko kebocoran tinggi (Merah).

### 5.3 Modul Penetapan Pajak JIT & Integrasi Tanda Tangan Elektronik (TTE)

<div class="mermaid">
graph TD
    %% Styling
    classDef logic fill:#e0f2f1,stroke:#1565c0,stroke-width:2px,color:#004d40,rx:5px,ry:5px
    classDef engine fill:#fff8e1,stroke:#ff8f00,stroke-width:2px,color:#bf360c,rx:5px,ry:5px
    classDef output fill:#fce4ec,stroke:#c2185b,stroke-width:2px,color:#880e4f,rx:5px,ry:5px

    A[Permintaan Inquiry dari Bank / Wajib Pajak]:::logic --> B(Just-In-Time Billing Engine):::engine
    B --> C{Pengecekan Variabel Metadata JSON}:::logic
    C -->|Tarif Restoran 10%| D[Kalkulator Pokok Restoran]:::engine
    C -->|Tarif Hotel 10%| E[Kalkulator Pokok Hotel]:::engine
    C -->|Tarif Reklame per Meter| F[Kalkulator Pokok Reklame]:::engine
    
    D & E & F --> G(Penalty Engine / Denda Keterlambatan):::engine
    G --> H{Apakah Jatuh Tempo Terlewat?}:::logic
    H -->|Ya| I[Hitung Denda 2% Per Bulan Kumulatif]:::engine
    H -->|Tidak| J[Denda Rp0]:::engine
    
    I & J --> K(Total Tagihan Terkunci):::output
    K --> L[Generate Berkas SKPD PDF]:::output
    L --> M[Lempar ke BSrE untuk Pembubuhan TTE Stempel QR Code]:::output
</div>

Sistem meninggalkan metodologi warisan yang menghitung dan mencetak tagihan SPPT secara kaku di awal tahun kalender.
*   **Just-In-Time (JIT) Engine:** Mesin komputasi beroperasi dinamis. Ketika ada inisiasi permintaan besaran tagihan, barulah algoritma menjalankan fungsi pemangkasan masa aktif *range* pajak dan menghasilkan nilai. Ini mereduksi ukuran basis data dari puluhan *gigabytes* perhitungan *cache* statis menjadi kalkulasi langsung.
*   **Penalty Engine:** Keterlambatan pelunasan akan dievaluasi secara otomatis setiap bulan, menyuntikkan denda komulatif sebesar 2% per bulan sesuai Perda tanpa perlu sentuhan *admin*.
*   **Penerbitan SKPD & TTE:** Setelah angka dikunci, server membuat fail PDF (Surat Ketetapan Pajak Daerah) dan berkomunikasi dengan server BSrE BSSN untuk membubuhkan stempel Tanda Tangan Elektronik (Sertifikat Digital) berbentuk QR Code. Dokumen ini sah dan memiliki kekuatan hukum penuh di mata pengadilan pajak.

### 5.4 Modul Integrasi Gateway Pembayaran (Host-to-Host)

Arus transaksi ditangani melalui protokol asinkron berstandar SNAP (*Standar Nasional Open API Pembayaran*).

<div class="mermaid">
sequenceDiagram
    autonumber
    participant WP as Nasabah / Wajib Pajak
    participant Bank as Core Banking System
    participant WAF as Perimeter Bapenda (WAF)
    participant API as API Core Backend (Laravel)

    WP->>Bank: Input ID Billing (NPWPD) di Kanal M-Banking/Teller
    Bank->>WAF: POST /v1/inquiry (Kirim Payload JSON + HMAC)
    WAF->>API: Forward Payload Valid
    API->>API: Eksekusi JIT Billing Engine
    API-->>Bank: Response 200 OK (Nama WP, Rincian, Denda, Total)
    Bank-->>WP: Display Konfirmasi Pembayaran di Layar Gawai

    WP->>Bank: Otorisasi PIN / Passcode
    Bank->>Bank: Debet Mutasi Rekening Nasabah
    Bank->>WAF: POST /v1/payment (Webhook Pelunasan + HMAC Baru)
    WAF->>API: Forward Webhook Payment
    API->>API: Validasi Nominal Sinkron
    API->>API: Update Status Transaksi -> "LUNAS"
    API->>API: Generate Nomor Tanda Penerimaan Daerah (NTPD)
    API-->>Bank: Response 200 OK (NTPD Konfirmasi Sukses)
    Bank-->>WP: Cetak Struk Bukti Bayar / Resi Digital yang Mengandung NTPD
</div>

**Konsep Asinkron T+0:** Uang yang dipotong oleh *Core Banking System* tereskalasi dalam milidetik dan terekam di layar komputer admin penagihan Bapenda pada saat yang bersamaan (H+0). Modul rekonsiliasi secara ajaib mempertemukan angka pada mutasi rekening koran pemerintah daerah dengan catatan di sistem, tanpa perlu rekapitulasi data lembur mingguan oleh *teller* kantor.

---

## BAB VI: STANDAR KEAMANAN SISTEM

Sistem perpajakan daerah mengelola pangkalan data privasi jutaan entitas demografis, sekaligus menjadi muara perputaran agregat finansial puluhan miliar rupiah per tahun. Kepatuhan terhadap kaidah ketahanan siber (*cyber resilience*) bukan sekadar opsi, melainkan keharusan absolut.

### 6.1 Kriptografi Transaksi Bank (HMAC & Asymmetric)
Sebagai garis pertahanan utama dalam menjaga legitimasi data transaksi *Host-to-Host*, setiap *payload* JSON yang dipertukarkan antara *Payment Gateway* Bank dan server BAPENDA diproteksi dengan tanda tangan digital berlapis:
1.  **HMAC-SHA256:** String otentikasi dihasilkan dari gabungan parameter khusus (Tanggal, Nominal, Kode Bank) yang di-*hash* menggunakan *Secret Key*. Algoritma ini menjamin integritas paket data agar mustahil untuk dimanipulasi di tengah jalan (*Man-in-the-Middle Attack*).
2.  **RSA-2048 Encryption:** Dalam skenario pertukaran data yang sangat krusial, kunci asimetris digunakan. Bank menyandikan (*encrypt*) data dengan *Public Key* Pemkot Baubau, sehingga secara matematis hanya *Private Key* Bapenda yang bisa membuka gembok data tersebut.

### 6.2 Kendali Akses Berbasis Geografi (Geo-Blocking & IP Whitelisting)
Titik akses administratif (*Admin Dashboard*) dan jalur penyelesaian setoran API dikonfigurasi dalam lingkungan kedap udara:
*   Mekanisme *IP Whitelisting* memastikan *Router Firewall* hanya menerima instruksi pembayaran dari deretan alamat IP terdaftar milik Bank Mitra. Akses dari IP anonim, apalagi yang berasal dari teritori luar Indonesia (Geo-IP Blocking), akan langsung dilempar ke keranjang sampah jaringan (*Drop* / *Blackhole*).

### 6.3 Otorisasi Sesi Pengguna Berbasis Token (JWT)
Untuk memitigasi pencurian identitas dan *Session Hijacking*, arsitektur penanganan sesi tidak menggunakan metode usang berupa tabel persisten di basis data. Sistem mengadopsi JSON Web Tokens (JWT) bersertifikasi:
*   Setiap petugas/admin yang masuk (*login*) dibekali sepotong Token yang telah dienkripsi dengan *secret base64*. Token ini *stateless*, artinya server tidak perlu menanyakan ke *database* berulang kali. 
*   Token dilengkapi dengan *Time-To-Live* (TTL) atau umur pendek (misal: kadaluarsa tiap 1 jam), memaksa mekanisme *Silent Refresh* di latar belakang. Bila gawai pengguna diretas, token akan basi dengan sendirinya, menetralisir bahaya serangan lanjutan.

---

## BAB VII: RENCANA KERJA, JADWAL & TIM PENGEMBANG

Keberhasilan penjahitan ekosistem berskala kolosal ini bergantung mutlak pada orkestrasi sumber daya manusia (SDM) dan pemilihan peralatan operasional yang solid.

### 7.1 Spesifikasi Susunan Personil Inti
Pengembangan arsitektur digital ini wajib diserahkan kepada tim ahli dengan klasifikasi jam terbang *Enterprise Level*.
A. **Project Manager (1 Orang):** Memegang sertifikasi manajemen proyek (misal: PMP atau setara). Bertanggung jawab menjembatani iterasi (*sprint*) teknis dengan kebutuhan birokrasi regulasi pimpinan Bapenda. Minimal 5 tahun pengalaman pengadaan *e-government*.
B. **System Analyst & Database Architect (1 Orang):** Merancang skema relasional antar entitas, menentukan formula Normalisasi *Database*, serta memetakan matriks struktur *Unified Metadata*. Mengawasi rancangan DFD (*Data Flow Diagram*) dan mitigasi asinkron H2H.
C. **Senior Backend Engineer (2 Orang):** Spesialis *framework* PHP modern (Laravel 11). Menulis blok kode fungsional, membuat abstraksi API RESTful, mengintegrasikan JWT dan Kriptografi Asimetris. Penguasaan bahasa struktural dan kontrol versi (*Git*) tingkat lanjut.
D. **Frontend & Mobile App Developer (2 Orang):** Spesialis kerangka antarmuka (React.js/Vue.js untuk Web, Flutter/React Native untuk *Mobile*). Menerjemahkan *wireframe* menjadi tampilan (*User Interface*) fungsional yang responsif terhadap ukuran gawai dan layar monitor besar.
E. **Quality Assurance / Security Auditor (1 Orang):** Petugas khusus yang akan mencoba menembus celah sistem (Pen-Test), meretas *endpoint* API (memastikan WAF bekerja), dan membuat instrumen pengujian beban otomatis (menggunakan *JMeter/Locust*) membanjiri sistem dengan 10.000 *request* per detik untuk menguji kejatuhan peladen.

### 7.2 Spesifikasi Kebutuhan Perangkat / Infrastruktur (Server)

Berbeda dengan proyek jaringan fisik yang membutuhkan kabel optik *fusion splicer*, proyek *software e-government* membutuhkan kapabilitas komputasi Awan (Cloud Computing) dengan ketangguhan tingkat menengah-atas.

| Kategori Komponen | Spesifikasi Rekomendasi (Minimal) | Deskripsi Utilitas |
| :--- | :--- | :--- |
| **Virtual Private Server (VPS)** | Neo Cloud / GCP / AWS. vCPU: 8 Cores, RAM: 16 GB, SSD/NVMe: 250 GB. Sistem Operasi: Ubuntu 24.04 LTS Server. | Menjalankan *Web Server* Nginx, PHP-FPM, Supervisor untuk pemrosesan tugas latar belakang (*Queue/Jobs*), dan Daemon API. |
| **Database Server (Managed)** | Dedicated Instance (vCPU: 4 Core, RAM 8GB), MySQL 8.0+ atau PostgreSQL 16+. Storage 100 GB. | Mesin relasional. Disarankan terpisah (*decoupled*) dari mesin aplikasi VPS demi jaminan integritas penulisan dan replikasi data. |
| **Cache Server** | Redis In-Memory Datastore. | Mengatur JSON Web Token (*session states*) pengguna, dan menyimpan perhitungan tagihan statis dalam memori kilat untuk efisiensi latensi. |
| **Keamanan Perimeter** | Web Application Firewall (WAF) dari Cloudflare Enterprise / F5 Networks. | Menyediakan sertifikat SSL/TLS Otomatis, pencegahan DDoS, *bot-protection*, serta inspeksi injeksi sintaks basis data berbahaya. |
| **Cloud Object Storage** | S3-Compatible Storage (AWS S3, MinIO). Kapasitas awal 500GB, *Auto-scaling*. | Gudang file tak berbatas untuk menyimpan jepretan foto objek dari aplikasi *mobile* petugas dan arsip file SKPD PDF ber-TTE tanpa menghabiskan disk VPS. |

### 7.3 Tabel Jadwal Pelaksanaan Konstruksi (Timeline)

Siklus hidup pengembangan (SDLC) dikalibrasi ketat ke dalam termin waktu triwulanan (*Quarterly*). Implementasi diurai berbasis pencapaian tenggat waktu terukur.

| Tahapan Fase & Aktivitas Proyek | Bulan 1 | Bulan 2 | Bulan 3 | Bulan 4 | Bulan 5 |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **FASE A: ANALISIS & DESAIN** | | | | | |
| 1. *Requirement Gathering* & Reverse Engineering Legacy | ▉ | | | | |
| 2. Pembuatan Desain UI/UX & *Mockup* | ▉ | ▉ | | | |
| 3. Normalisasi *Unified Database* & Topologi Server | | ▉ | | | |
| **FASE B: CODING & INTEGRASI BACKEND** | | | | | |
| 1. Modul Registrasi E-SPOPD & Autentikasi JWT | | ▉ | ▉ | | |
| 2. Perakitan *JIT Billing Engine* & *Penalty Logic* | | | ▉ | ▉ | |
| 3. Rekayasa *Endpoint API* H2H Kriptografi SNAP BI | | | ▉ | ▉ | |
| **FASE C: INTEGRASI EKSTERNAL & PENGUJIAN** | | | | | |
| 1. *Sandbox Testing* Perbankan (Bank Sultra/Mandiri) | | | | ▉ | |
| 2. Integrasi Gateway TTE BSrE (Pembuatan PDF Stempel) | | | | ▉ | ▉ |
| 3. Uji Penetrasi (*Pen-Test*) & Uji Beban (*Stress-Test*) | | | | | ▉ |
| **FASE D: UAT & PELUNCURAN (GO-LIVE)** | | | | | |
| 1. *User Acceptance Testing* (Simulasi Internal Bapenda) | | | | | ▉ |
| 2. Serah Terima (*Handover*), Bimbingan Teknis SDM | | | | | ▉ |
| 3. Pelepasan ke Publik (*Phased Rollout Soft-Launch*) | | | | | ▉ |

### 7.4 Indikasi Perawatan Berkelanjutan (Maintanance)
Platform perangkat lunak setara organisme hidup yang memerlukan monitor berkelanjutan. Siklus perawatan rutin melibatkan:
*   **Harian:** Pengecekan stabilitas koneksi API H2H Bank, inspeksi log kesalahan HTTP 500, dan pemantauan utilisasi CPU VPS.
*   **Mingguan:** *Backup* basis data ke lokasi geografis sekunder (*off-site disaster recovery*), *flushing cache* memori usang.
*   **Bulanan:** *Patching* (tambalan) pembaruan modul *library open-source* Laravel untuk menangkal *Zero-Day Exploit*, validasi masa aktif sertifikat TTE BSrE dan lisensi Domain/WAF.

---

## BAB VIII: KESIMPULAN

Dokumen Perencanaan (Blueprint) Arsitektur Sistem Informasi Pendapatan Daerah ini dihidupkan dengan roh fundamental birokrasi cerdas (*smart-governance*). Ini bukan perihal modernisasi visual aplikasi semata, melainkan sebuah restrukturisasi absolut instrumen pemerintahan dari hierarki komputasi primitif menuju berkaliber korporasi modern (*Enterprise-Grade Platform*).

**Kebutuhan Mendasar Sistem:** Ketiadaan ekosistem yang terajut rapi membuat Pendapatan Asli Daerah (PAD) rentan mengalami stagnasi pelaporan dan inefisiensi pengawasan (*surveillance*). Melalui *Reverse Engineering* pada sistem *legacy* (SIMPAD PHP Native lama), ditemukan banyak redudansi entri data dan celah keamanan pustaka kuno. Dibutuhkan infrastruktur digital yang mampu menjahit modul Pendaftaran, Penetapan JIT, hingga Pembayaran Nirsentuh dalam satu helaan napas pangkalan data tunggal (*Unified Single Source of Truth*).

**Solusi Teknologis Canggih:** Aplikasi dirakit dengan fondasi *Agile*, didukung kekuatan N-Tier Architecture, kehebatan algoritma pemecah beban (*Load Balancing*), serta jembatan koneksi kriptografis kelas perbankan (HMAC Asymmetric RSA-2048) mengacu pada standar SNAP Bank Indonesia. Modul pengawasan lapangan dipersenjatai peta radar spasial (*GIS Heatmap*) untuk mereduksi pelaporan omzet ilegal secara geografis.

**Dampak Positif & Rekomendasi:** 
Dengan meminggirkan campur tangan manusia dalam proses sinkronisasi angka pelunasan bayar, rekonsiliasi manual berhari-hari kini terpotong drastis menjadi seperseribu detik (*Real-Time T+0*). Desain infrastruktur anti-roboh (*High Availability*) ini meminimalisir celah hilangnya integritas fiskal di tingkatan paling dini. Pemerintah Kota Baubau, khususnya Bapenda, sangat disarankan untuk menetapkan standarisasi kompetensi SDM (melalui *Transfer of Knowledge*) guna mendampingi ekosistem baru ini. Ekosistem revolusioner ini secara sadar dibangun sebagai infrastruktur mesin masa depan (*Future-Proof*), yang menopang fondasi transparansi perbendaharaan, merevolusi peningkatan kapasitas fiskal, dan memahat nama Kota Baubau di barisan terdepan konstelasi pelayanan *Smart City* nasional.

