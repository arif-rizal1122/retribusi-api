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

### 1.1 Latar Belakang
Pengembangan sistem layanan pemerintah yang efisien dan terintegrasi menjadi prioritas bagi Pemerintah Kota Baubau, khususnya dalam ranah pengelolaan Pendapatan Asli Daerah (PAD). Dalam rangka meningkatkan kualitas layanan publik serta mengamankan kas daerah, perencanaan arsitektur digital yang matang dan terkoordinasi sangat diperlukan. Secara historis, infrastruktur digital yang digunakan oleh Badan Pendapatan Daerah (BAPENDA) Kota Baubau beroperasi dalam ekosistem yang terfragmentasi (silo). Sistem-sistem seperti SIMPAD (Sistem Informasi Manajemen Pendapatan Daerah), SIMBPHTB, dan SIMPBB berdiri secara independen tanpa adanya sinkronisasi basis data hulu-ke-hilir. Hal ini memunculkan redudansi data, inefisiensi pelaporan, serta tingginya risiko kebocoran penerimaan daerah akibat rekonsiliasi pembayaran yang masih mengandalkan proses manual.

Beberapa faktor strategis yang mendasari kebutuhan akan pelaksanaan kegiatan perencanaan sistem terintegrasi ini adalah:
A. **Perkembangan Teknologi:** Transformasi digital memaksa birokrasi untuk beradaptasi dengan kecepatan layanan *real-time*. Peralihan dari arsitektur basis data relasional yang kaku menuju arsitektur *Unified Database* dengan dukungan skalabilitas *Metadata JSON* memungkinkan konsolidasi seluruh jenis pungutan.
B. **Integrasi Layanan Lintas Instansi (H2H):** Sistem penghubung layanan pajak harus mampu mengintegrasikan data wajib pajak dengan instansi vertikal maupun horizontal, termasuk konektivitas langsung dengan perbankan melalui *Open API* berstandar SNAP Bank Indonesia.
C. **Kepentingan Publik & Transparansi:** Layanan perpajakan yang efisien, transparan, dan nirsentuh (*cashless*) akan memberikan kenyamanan mutlak bagi masyarakat, yang pada gilirannya akan menstimulasi kepatuhan pembayaran (tax compliance).

### 1.2 Maksud dan Tujuan
Maksud dari penyusunan dokumen perencanaan ini adalah untuk memberikan pedoman teknis, metodologis, dan operasional (Cetak Biru / *Blueprint*) yang komprehensif bagi seluruh pemangku kepentingan (*stakeholders*) yang terlibat dalam siklus pengembangan perangkat lunak maupun tata kelola birokrasi di BAPENDA Baubau.

Tujuan utama dari pengembangan sistem ini meliputi:
1. **Penyatuan Ekosistem Data:** Menciptakan satu pangkalan data terpusat (*Unified Database*) untuk meniadakan redudansi data Wajib Pajak antar jenis pajak/retribusi, mempermudah validasi, dan mempercepat audit.
2. **Otomatisasi Penetapan dan Penagihan:** Mengimplementasikan *Just-In-Time (JIT) Billing Engine* yang mampu mengalkulasi tagihan dan denda secara dinamis (termasuk denda 2% per bulan untuk keterlambatan) tanpa membebani server secara statis di awal tahun.
3. **Digitalisasi Pelayanan Mandiri:** Mendorong kepatuhan Wajib Pajak melalui kemudahan akses pendaftaran mandiri (E-SPOPD) dan pembayaran berbagai kanal digital (QRIS, Virtual Account) secara *self-service*.
4. **Keamanan, Legalitas, dan Transparansi:** Mengintegrasikan Tanda Tangan Elektronik (TTE) tersertifikasi BSSN untuk penerbitan dokumen resmi, serta menyajikan data analitik PAD harian yang presisi kepada pimpinan daerah melalui *Executive Dashboard*.

### 1.3 Landasan Hukum
Penyelenggaraan dan perancangan arsitektur sistem ini dilandasi oleh peraturan perundang-undangan yang berlaku, guna memastikan kepatuhan hukum (*legal compliance*) dalam setiap transaksi elektronik yang terjadi:
1.  **Undang-Undang Nomor 11 Tahun 2008** tentang Informasi dan Transaksi Elektronik, sebagaimana telah diubah dengan Undang-Undang Nomor 1 Tahun 2024.
2.  **Undang-Undang Nomor 1 Tahun 2022** tentang Hubungan Keuangan Antara Pemerintah Pusat dan Pemerintahan Daerah (HKPD).
3.  **Peraturan Pemerintah Nomor 71 Tahun 2019** tentang Penyelenggaraan Sistem dan Transaksi Elektronik.
4.  **Peraturan Daerah (Perda) Kota Baubau Nomor 1 Tahun 2024** tentang Pajak Daerah dan Retribusi Daerah (PDRD).
5.  **Peraturan Wali Kota Baubau Nomor 58 Tahun 2024** tentang Tata Cara Pemungutan Pajak Daerah dan Retribusi Daerah.
6.  Standar Nasional Open API Pembayaran (SNAP) dari Bank Indonesia terkait interkoneksi sistem perbankan.

### 1.4 Ruang Lingkup dan Metodologi
Ruang lingkup pekerjaan perancangan ini mencakup desain arsitektur basis data, perancangan antarmuka pemrograman aplikasi (API), pembuatan topologi jaringan *cloud*, hingga pedoman mitigasi risiko siber.
Metodologi yang digunakan mengadopsi pendekatan *Agile System Development Life Cycle (SDLC)* yang dikombinasikan dengan observasi lapangan dan analisis kebutuhan:
*   **Pengumpulan Data:** Menganalisis *bottleneck* pada server eksisting SIMPAD, memetakan alur birokrasi manual, dan mengevaluasi spesifikasi perangkat keras/jaringan yang dimiliki oleh Pemkot Baubau.
*   **Perancangan Desain:** Menentukan tumpukan teknologi (*tech stack*) menggunakan *framework* Laravel 11, *database* MySQL/PostgreSQL, serta klasterisasi server menggunakan VPS Neo Cloud.
*   **Pengujian & Implementasi:** Menyusun skenario pengujian beban (*load testing*), uji penetrasi (*penetration testing*), serta rencana peluncuran bertahap (*phased rollout*).

---

## BAB II: KAJIAN TEORI

Perencanaan arsitektur perangkat lunak skala pemerintahan (*Enterprise/Government Scale*) membutuhkan pemahaman mendalam tentang teori-teori komputasi modern. Penerapan konsep yang tepat akan menentukan stabilitas, keamanan, dan usia pakai (*lifespan*) dari perangkat lunak yang dibangun.

### 2.1 Teori Arsitektur Perangkat Lunak (N-Tier Architecture)
Model N-Tier (Multi-Lapis) adalah paradigma rekayasa perangkat lunak yang secara logis maupun fisik memisahkan aplikasi ke dalam beberapa lapisan fungsional yang berbeda. Pada sistem pendapatan Baubau, diterapkan arsitektur 3-Tier:
1.  **Presentation Tier:** Berupa aplikasi *Front-End* berbasis React.js (untuk Admin) dan *Progressive Web App/Mobile* (untuk Warga dan Petugas). Lapis ini tidak memiliki akses langsung ke basis data.
2.  **Application Tier (Logic):** Diperankan oleh *API Gateway* tersentralisasi berbasis Laravel 11. Di sinilah *Formula Parser*, *Penalty Engine*, dan *JIT Billing* dieksekusi.
3.  **Data Tier:** Infrastruktur pangkalan data (MySQL/PostgreSQL) yang dikombinasikan dengan *In-Memory Cache* (Redis) untuk menampung data secara persisten dan cepat.

Pemilihan N-Tier memungkinkan sistem diperbarui secara terisolasi. Jika terjadi lonjakan pengunjung pada aplikasi Warga, *server frontend* dapat ditingkatkan kapasitasnya (*scaled up*) tanpa harus merestart *server database*.

### 2.2 Teori Komputasi Terdistribusi dan *Load Balancing*
Dalam mengelola jutaan *request* API dari kanal perbankan, *Load Balancing* bertindak sebagai polisi lalu lintas yang mendistribusikan beban jaringan di antara sekumpulan server (*server farm*). Dengan algoritma *Round Robin* atau *Least Connections*, *Load Balancer* memastikan tidak ada satu *server node* pun yang mengalami kelebihan beban operasional, sehingga *downtime* (waktu henti) dapat ditekan hingga mendekati angka nol (*Zero Downtime*).

### 2.3 Teori Kriptografi dan Keamanan Transaksi
Transaksi finansial lintas server (*Host-to-Host*) membutuhkan tingkat ketelitian kriptografi kelas perbankan. Teori yang diimplementasikan meliputi:
*   **Asymmetric Cryptography (RSA-2048):** Penggunaan kunci publik dan kunci privat untuk memastikan bahwa hanya server Bank dan server BAPENDA yang dapat mendekripsi *payload* pembayaran.
*   **Hash-based Message Authentication Code (HMAC):** Mekanisme *digital signature* menggunakan SHA-256 yang menempel pada *Header* HTTP. Jika ada peretas (Man-in-the-Middle) yang mencegat dan merubah isi nominal pembayaran walau hanya satu digit, verifikasi HMAC di sisi *receiver* akan otomatis gagal.

### 2.4 Teori Basis Data Terpadu (*Unified Database*)
Konsep tradisional mendiktekan pembuatan tabel terpisah untuk setiap jenis pajak (silo). Teori *Unified Database* mendekonstruksi ini dengan menyatukan entitas inti (misal: Entitas Wajib Pajak) ke dalam satu tabel utama *Master_WP*, yang terhubung dengan tabel *Sub_Objek_Pajak* melalui skema JSON (JavaScript Object Notation). Struktur JSON memungkinkan atribut pajak bervariasi—misalnya Pajak Reklame memiliki variabel ukuran PxL, sedangkan Pajak Hotel memiliki variabel Kelas Kamar—tanpa harus merusak arsitektur *schema* tabel relasional (*schema-less flexibility*).

---

## BAB III: ARSITEKTUR DAN EKOSISTEM SISTEM

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

    subgraph Publik ["🌐 Area Publik / Pengguna Akhir"]
        ClientWarga["📱 Aplikasi Warga & E-SPOPD"]:::cloud
        ClientBank["🏦 Sistem Perbankan (H2H)"]:::cloud
        ClientPetugas["📸 Aplikasi Mobile Petugas"]:::cloud
    end

    subgraph Keamanan ["🛡️ Layer Keamanan & Perimeter"]
        WAF["🧱 Web Application Firewall (WAF) & Load Balancer"]:::firewall
    end

    subgraph VPS ["☁️ Infrastruktur Neo Cloud VPS Bapenda"]
        AppNode1["⚙️ API Gateway (Node 1)"]:::server
        AppNode2["⚙️ API Gateway (Node 2 - Failover)"]:::server
    end

    subgraph DataCenter ["💾 Data Center Terpusat"]
        DB["🗄️ Unified Relational DB"]:::db
        Redis["⚡ Redis In-Memory Cache"]:::db
        Storage["📁 Cloud Object Storage (PDF TTE, Foto)"]:::db
    end

    ClientWarga -->|HTTPS| WAF
    ClientBank -->|Mutual TLS| WAF
    ClientPetugas -->|HTTPS| WAF
    
    WAF --> AppNode1
    WAF -.->|Failover| AppNode2
    
    AppNode1 <-->|Query & Write| DB
    AppNode1 <-->|Cache Session| Redis
    AppNode1 <-->|Simpan Berkas| Storage
    
    AppNode2 <--> DB
</div>

### 3.1 Deskripsi Topologi Jaringan & Infrastruktur
Arsitektur sistem dibangun dengan pendekatan tangguh yang mampu menangani transaksi tingkat tinggi dengan spesifikasi sebagai berikut:
1.  **Layer Publik:** Seluruh interaksi dari Wajib Pajak, Petugas Bapenda, dan mesin perbankan dialirkan melalui jalur internet publik dengan perlindungan enkripsi Transport Layer Security (TLS 1.2+).
2.  **Layer Perimeter WAF:** Sebagai garda terdepan, layanan mitigasi awan (seperti Cloudflare atau f5) bertugas mendeteksi pola anomali. IP luar negeri dapat diblokir *by-default* untuk mencegah serangan *brute-force* skala internasional, menyisakan hanya akses domestik dan IP statis Bank yang terdaftar (*whitelisted*).
3.  **Layer Aplikasi (Neo Cloud VPS):** Menggunakan instans peladen virtual bertenaga tinggi (minimal 8 Core vCPU, 16 GB RAM). Modul API Laravel bertindak sebagai orkestrator yang mengurai permintaan JSON. Jika *Node 1* mengalami gangguan (*crash*), *Load Balancer* akan otomatis membelokkan trafik ke *Node 2*.
4.  **Layer Pangkalan Data:** Mengusung konsep pemisahan komputasi (*Compute*) dan penyimpanan (*Storage*). Redis difungsikan untuk melayani pembacaan data yang berulang secara instan (seperti cek profil Wajib Pajak), menghemat konsumsi CPU pada database utama.

---

## BAB IV: SPESIFIKASI MODUL DAN PROSES BISNIS

Spesifikasi fungsional sistem dibagi ke dalam empat ekosistem modul platform yang masing-masing melayani aktor spesifik.

### 4.1 Modul Pendaftaran dan Validasi Basis Data
Fase registrasi merupakan gerbang awal untuk membentuk identitas digital Wajib Pajak. 
*   **Alur Warga:** Melalui portal *E-SPOPD*, warga dapat mengisi formulir pendataan objek baru dengan melampirkan berkas KTP dan foto lokasi usaha.
*   **Alur Admin:** Petugas Bapenda di *Dashboard Web* melakukan validasi silang (verifikasi dokumen). Sistem kemudian mengeksekusi algoritma *auto-increment* sektoral untuk mencetak Nomor Pokok Wajib Pajak Daerah (NPWPD) yang unik.

### 4.2 Modul Pengawasan dan Uji Petik Spasial (GIS)
Menjawab kendala penagihan lapangan, Aplikasi *Mobile* Petugas dilengkapi sistem telemetri:
*   **Tracking Geografis:** Petugas yang mendatangi restoran untuk melakukan *Uji Petik* omzet harian wajib mengaktifkan modul pelacak. *Backend* memvalidasi apakah koordinat GPS perangkat *smartphone* sinkron dengan koordinat NPWPD di pangkalan data (toleransi radius 50 meter).
*   **Visualisasi Heatmap:** Pemimpin Bapenda memantau wilayah dengan tingkat tunggakan tertinggi melalui panel peta interaktif, mengubah *spreadsheet* mati menjadi intelegensi geospasial hidup.

### 4.3 Modul Penetapan (Billing Engine) dan Legalitas Dokumen TTE

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
    BS -->|Hitung Pokok Pajak| FPS
    BS -->|Validasi Keterlambatan| PCS
</div>

Modul paling revolusioner dalam platform ini adalah implementasi *Just-In-Time (JIT) Billing Engine*.
*   **JIT Mechanism:** Alih-alih melakukan generasi jutaan baris tagihan pada 1 Januari, mesin *JIT* menghitung nilai hutang tepat pada detik di mana *Bank* melakukan *Inquiry*.
*   **Penalty Engine:** Keterlambatan pelunasan akan dievaluasi secara otomatis setiap bulan, menyuntikkan denda komulatif sebesar 2% per bulan sesuai Perda tanpa perlu sentuhan *admin*.
*   **Penerbitan SKPD & TTE:** Setelah angka dikunci, server membuat fail PDF (Surat Ketetapan Pajak Daerah) dan berkomunikasi dengan server BSrE BSSN untuk membubuhkan stempel Tanda Tangan Elektronik (Sertifikat Digital) berbentuk QR Code. Dokumen ini sah dan memiliki kekuatan hukum penuh di mata pengadilan pajak.

### 4.4 Modul Integrasi Gateway Pembayaran (Host-to-Host)

<div class="mermaid">
sequenceDiagram
    autonumber
    participant WP as Wajib Pajak
    participant Bank as Open API Perbankan
    participant Bapenda as API Core Bapenda

    WP->>Bank: Memasukkan NPWPD/Kode Bayar (Via ATM/Teller/Mobile)
    Bank->>Bapenda: POST /api/v1/inquiry (Cek Validitas Tagihan)
    Bapenda-->>Bank: 200 OK (Response: Detail Wajib Pajak, Pokok, Denda)
    Bank-->>WP: Menampilkan Layar Konfirmasi Nominal

    WP->>Bank: Otorisasi Pembayaran (Konfirmasi PIN)
    Bank->>Bank: Mutasi Debet Rekening Nasabah
    Bank->>Bapenda: POST /api/v1/payment (Notifikasi Pelunasan)
    Bapenda->>Bapenda: Validasi HMAC Signature, Update Status "LUNAS", Generate NTPD
    Bapenda-->>Bank: 200 OK (NTPD Disahkan)
    Bank-->>WP: Menerbitkan Bukti Bayar / Struk (NTPD Tertera)
</div>

Lompatan kuantum menuju *Smart City* diwujudkan melalui konektivitas antar-mesin (*Host-to-Host*) dengan jaringan perbankan (Bank Sultra, Mandiri, BNI). 
Seluruh jembatan komunikasi dienkapsulasi menggunakan Standar Nasional Open API Pembayaran (SNAP) Bank Indonesia. Paradigma komunikasi asinkron antara siklus *Inquiry* (pengecekan saldo) dan *Payment* (pemotongan/notifikasi webhook) menyingkirkan jeda pelaporan yang sebelumnya memakan waktu berhari-hari menjadi instan (*T+0 Real-Time Reconciliation*). Wajib Pajak dimanjakan oleh dukungan omni-kanal: mulai dari pembayaran tunai lewat *Teller*, transfer *Virtual Account*, minimarket, hingga metode QRIS Dinamis.

---

## BAB V: STANDAR KEAMANAN SISTEM

Sistem perpajakan daerah mengelola pangkalan data privasi jutaan entitas demografis, sekaligus menjadi muara perputaran agregat finansial puluhan miliar rupiah per tahun. Kepatuhan terhadap kaidah ketahanan siber (*cyber resilience*) bukan sekadar opsi, melainkan keharusan absolut.

### 5.1 Kriptografi Transaksi Bank (HMAC & Asymmetric)
Sebagai garis pertahanan utama dalam menjaga legitimasi data transaksi *Host-to-Host*, setiap *payload* JSON yang dipertukarkan antara *Payment Gateway* Bank dan server BAPENDA diproteksi dengan tanda tangan digital berlapis:
1.  **HMAC-SHA256:** String otentikasi dihasilkan dari gabungan parameter khusus (Tanggal, Nominal, Kode Bank) yang di-*hash* menggunakan *Secret Key*. Algoritma ini menjamin integritas paket data agar mustahil untuk dimanipulasi di tengah jalan (*Man-in-the-Middle Attack*).
2.  **RSA-2048 Encryption:** Dalam skenario pertukaran data yang sangat krusial, kunci asimetris digunakan. Bank menyandikan (*encrypt*) data dengan *Public Key* Pemkot Baubau, sehingga secara matematis hanya *Private Key* Bapenda yang bisa membuka gembok data tersebut.

### 5.2 Kendali Akses Berbasis Geografi (Geo-Blocking & IP Whitelisting)
Titik akses administratif (*Admin Dashboard*) dan jalur penyelesaian setoran API dikonfigurasi dalam lingkungan kedap udara:
*   Mekanisme *IP Whitelisting* memastikan *Router Firewall* hanya menerima instruksi pembayaran dari deretan alamat IP terdaftar milik Bank Mitra. Akses dari IP anonim, apalagi yang berasal dari teritori luar Indonesia (Geo-IP Blocking), akan langsung dilempar ke keranjang sampah jaringan (*Drop* / *Blackhole*).

### 5.3 Otorisasi Sesi Pengguna Berbasis Token (JWT)
Untuk memitigasi pencurian identitas dan *Session Hijacking*, arsitektur penanganan sesi tidak menggunakan metode usang berupa tabel persisten di basis data. Sistem mengadopsi JSON Web Tokens (JWT) bersertifikasi:
*   Setiap petugas/admin yang masuk (*login*) dibekali sepotong Token yang telah dienkripsi dengan *secret base64*. Token ini *stateless*, artinya server tidak perlu menanyakan ke *database* berulang kali. 
*   Token dilengkapi dengan *Time-To-Live* (TTL) atau umur pendek (misal: kadaluarsa tiap 1 jam), memaksa mekanisme *Silent Refresh* di latar belakang. Bila gawai pengguna diretas, token akan basi dengan sendirinya, menetralisir bahaya serangan lanjutan.

---

## BAB VI: RENCANA IMPLEMENTASI DAN PENGUJIAN

Perjalanan menuju penyebaran (*deployment*) paripurna memerlukan pedoman manajerial yang pragmatis, yang digerakkan melalui bingkai kerja hibrida antara presisi desain air-terjun (*Waterfall*) dan kecepatan operasional gesit (*Agile*). 

### 6.1 Fase Konstruksi Inti (Sprint Development)
Fase fondasional difokuskan pada perakitan tulang punggung sistem:
*   **Bulan Pertama - Perancangan Data & Unified DB:** Menganalisis skema warisan lama (SIMPAD/SIMPBB), membangun pemetaan ERD (*Entity Relationship Diagram*) baru yang menampung multi-variabel pajak ke dalam format JSON *metadata*, dan memvalidasi koneksi server.
*   **Bulan Kedua - Mesin Logika (Billing & Penalty):** Proses pemrograman modul *backend* secara intensif. Pemasangan *Formula Parser* yang sanggup menerjemahkan hitungan pajak beragam objek secara adaptif, dikalibrasi ketat terhadap Perda Kota Baubau terbaru. 

### 6.2 Fase Integrasi Eksternal dan Audit Keamanan
Setelah fondasi komputasi berdiri, sistem dicolokkan ke dunia luar:
*   **Bulan Ketiga - SIT (System Integration Testing) Sandbox Bank:** Interkoneksi virtual dilakukan antara server pengembangan Pemkot Baubau dan server uji coba perbankan. Mengonfirmasi apakah proses HMAC *signature*, respons kode HTTP 200/400, dan pencetakan struk digital lulus uji coba presisi matematis tanpa *bug*.
*   **Integrasi Otomatis TTE (BSrE BSSN):** Penyambungan jembatan API ke mesin sandi milik negara untuk menata penandatanganan SKPD elektronik secara massal tanpa penundaan antrean *server*.
*   **Audit Penetrasi (Pen-Test):** Mensimulasikan serangan terstruktur ke *port* terbuka guna menutupi celah *Vulnerabilities* (kerentanan siber) tingkat tinggi yang berpotensi menjadi bumerang reputasi.

### 6.3 Fase Validasi (UAT) dan Gelar Peluncuran (Go-Live)
*   **Bulan Keempat - UAT (User Acceptance Testing):** Sesi pelatihan (*Transfer of Knowledge*) dan uji skenario harian secara langsung bersama aparatur penagihan Bapenda, petugas operator, dan eksekutif. Merombak navigasi UI/UX bila ditemukan ketidaknyamanan interaksi (*friction*).
*   **Peluncuran Tersusun (Phased Rollout):** Perilisan dikelola dalam tahapan aman (Soft-Launch). Dimulai dengan operasional *Web Dashboard Admin* di kantor Bapenda. Di minggu berikutnya peluncuran *Aplikasi Mobile Petugas*, sebelum akhirnya puncak penyerahan aplikasi layanan mandiri ke *PlayStore* (Google Android) untuk bisa dinikmati masif oleh warga Baubau.

---

## BAB VII: PENUTUP

Dokumen Perencanaan (Blueprint) Arsitektur Sistem Informasi Pendapatan Daerah ini dihidupkan dengan roh transformasi: menjauh dari sistem pelaporan yang usang, menuju instrumen kontrol pemerintahan berkaliber korporasi (*enterprise-grade*). Perancangan yang holistik—melibatkan perombakan topologi pangkalan data tunggal (*Unified*), rekayasa komputasi performa tinggi (*Load Balancing* & *Redis Cache*), hingga penjahitan lapisan pertahanan kriptografi militer (HMAC SNAP BI)—menggarisbawahi komitmen tanpa kompromi dari Pemerintah Kota Baubau untuk mengamankan dan mengoptimalisasi Pendapatan Asli Daerah (PAD).

Dengan meminggirkan campur tangan manusia dalam proses pencatatan, kalkulasi denda, dan verifikasi pelunasan H2H, kebocoran fiskal yang bersifat sistemik kini berhasil dimitigasi sejak hulu. Ekosistem digital revolusioner ini bukan saja sekadar menjawab rintangan konektivitas masa kini, melainkan disiapkan sebagai jembatan *Future-Proof* yang luwes untuk menopang kemajuan konstelasi layanan *Smart City* Kota Baubau pada era masa depan. Visi besar pelayanan yang bersih, terukur, dan bermartabat, telah diletakkan batu pertamanya di sini.
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
        body { background: #e0e0e0; font-family: 'Roboto', sans-serif; color: #333; }
        .page { width: 21cm; min-height: 29.7cm; padding: 1.5cm; margin: 1.5cm auto; background: white; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); position: relative; page-break-after: always; }
        .page-cover { display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; }
        .page-cover .logo { width: 120px; margin-bottom: 30px; }
        .page-cover h1 { font-family: 'Garamond', serif; font-size: 2.8rem; color: #1a237e; margin-bottom: 15px; text-transform: uppercase; }
        .page-cover h2 { font-size: 1.4rem; color: #555; font-weight: 400; }
        .page-footer { position: absolute; bottom: 1cm; left: 1.5cm; right: 1.5cm; text-align: center; font-size: 9pt; color: #999; }
        h1, h2, h3, h4 { font-weight: 700; color: #1a237e; margin-top: 25px; margin-bottom: 12px; }
        h2 { font-size: 18pt; border-bottom: 2px solid #dfe3ee; padding-bottom: 6px; }
        h3 { font-size: 14pt; color: #283593; margin-top: 20px; }
        p, li { font-size: 11pt; line-height: 1.7; text-align: justify; margin-bottom: 15px; }
        ul, ol { padding-left: 25px; margin-bottom: 20px; }
        strong { color: #212121; }
        @media print {
            body { background: white; }
            .page { width: auto; min-height: auto; margin: 0; padding: 1.5cm; box-shadow: none; border: none; page-break-after: always; }
            .print-fab { display: none; }
        }
        .mermaid { text-align: center; margin: 30px auto; }
        .mermaid svg { max-width: 90% !important; height: auto !important; }
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

<h3>1.1 Latar Belakang</h3>
<p>Pengembangan sistem layanan pemerintah yang efisien dan terintegrasi menjadi prioritas bagi Pemerintah Kota Baubau, khususnya dalam ranah pengelolaan Pendapatan Asli Daerah (PAD). Dalam rangka meningkatkan kualitas layanan publik serta mengamankan kas daerah, perencanaan arsitektur digital yang matang dan terkoordinasi sangat diperlukan. Secara historis, infrastruktur digital yang digunakan oleh Badan Pendapatan Daerah (BAPENDA) Kota Baubau beroperasi dalam ekosistem yang terfragmentasi (silo). Sistem-sistem seperti SIMPAD (Sistem Informasi Manajemen Pendapatan Daerah), SIMBPHTB, dan SIMPBB berdiri secara independen tanpa adanya sinkronisasi basis data hulu-ke-hilir. Hal ini memunculkan redudansi data, inefisiensi pelaporan, serta tingginya risiko kebocoran penerimaan daerah akibat rekonsiliasi pembayaran yang masih mengandalkan proses manual.</p>
<p>Beberapa faktor strategis yang mendasari kebutuhan akan pelaksanaan kegiatan perencanaan sistem terintegrasi ini adalah:</p>
<ul>
    <li><strong>A. Perkembangan Teknologi:</strong> Transformasi digital memaksa birokrasi untuk beradaptasi dengan kecepatan layanan <em>real-time</em>. Peralihan dari arsitektur basis data relasional yang kaku menuju arsitektur <em>Unified Database</em> dengan dukungan skalabilitas <em>Metadata JSON</em> memungkinkan konsolidasi seluruh jenis pungutan.</li>
    <li><strong>B. Integrasi Layanan Lintas Instansi (H2H):</strong> Sistem penghubung layanan pajak harus mampu mengintegrasikan data wajib pajak dengan instansi vertikal maupun horizontal, termasuk konektivitas langsung dengan perbankan melalui <em>Open API</em> berstandar SNAP Bank Indonesia.</li>
    <li><strong>C. Kepentingan Publik & Transparansi:</strong> Layanan perpajakan yang efisien, transparan, dan nirsentuh (<em>cashless</em>) akan memberikan kenyamanan mutlak bagi masyarakat, yang pada gilirannya akan menstimulasi kepatuhan pembayaran (tax compliance).</li>
</ul>

<h3>1.2 Maksud dan Tujuan</h3>
<p>Maksud dari penyusunan dokumen perencanaan ini adalah untuk memberikan pedoman teknis, metodologis, dan operasional (Cetak Biru / <em>Blueprint</em>) yang komprehensif bagi seluruh pemangku kepentingan (<em>stakeholders</em>) yang terlibat dalam siklus pengembangan perangkat lunak maupun tata kelola birokrasi di BAPENDA Baubau.</p>
<p>Tujuan utama dari pengembangan sistem ini meliputi:</p>
<ol>
    <li><strong>Penyatuan Ekosistem Data:</strong> Menciptakan satu pangkalan data terpusat (<em>Unified Database</em>) untuk meniadakan redudansi data Wajib Pajak antar jenis pajak/retribusi, mempermudah validasi, dan mempercepat audit.</li>
    <li><strong>Otomatisasi Penetapan dan Penagihan:</strong> Mengimplementasikan <em>Just-In-Time (JIT) Billing Engine</em> yang mampu mengalkulasi tagihan dan denda secara dinamis (termasuk denda 2% per bulan untuk keterlambatan) tanpa membebani server secara statis di awal tahun.</li>
    <li><strong>Digitalisasi Pelayanan Mandiri:</strong> Mendorong kepatuhan Wajib Pajak melalui kemudahan akses pendaftaran mandiri (E-SPOPD) dan pembayaran berbagai kanal digital (QRIS, Virtual Account) secara <em>self-service</em>.</li>
    <li><strong>Keamanan, Legalitas, dan Transparansi:</strong> Mengintegrasikan Tanda Tangan Elektronik (TTE) tersertifikasi BSSN untuk penerbitan dokumen resmi, serta menyajikan data analitik PAD harian yang presisi kepada pimpinan daerah melalui <em>Executive Dashboard</em>.</li>
</ol>

<h3>1.3 Landasan Hukum</h3>
<p>Penyelenggaraan dan perancangan arsitektur sistem ini dilandasi oleh peraturan perundang-undangan yang berlaku, guna memastikan kepatuhan hukum (<em>legal compliance</em>) dalam setiap transaksi elektronik yang terjadi:</p>
<ol>
    <li><strong>Undang-Undang Nomor 11 Tahun 2008</strong> tentang Informasi dan Transaksi Elektronik, sebagaimana telah diubah dengan Undang-Undang Nomor 1 Tahun 2024.</li>
    <li><strong>Undang-Undang Nomor 1 Tahun 2022</strong> tentang Hubungan Keuangan Antara Pemerintah Pusat dan Pemerintahan Daerah (HKPD).</li>
    <li><strong>Peraturan Pemerintah Nomor 71 Tahun 2019</strong> tentang Penyelenggaraan Sistem dan Transaksi Elektronik.</li>
    <li><strong>Peraturan Daerah (Perda) Kota Baubau Nomor 1 Tahun 2024</strong> tentang Pajak Daerah dan Retribusi Daerah (PDRD).</li>
    <li><strong>Peraturan Wali Kota Baubau Nomor 58 Tahun 2024</strong> tentang Tata Cara Pemungutan Pajak Daerah dan Retribusi Daerah.</li>
    <li>Standar Nasional Open API Pembayaran (SNAP) dari Bank Indonesia terkait interkoneksi sistem perbankan.</li>
</ol>

<h3>1.4 Ruang Lingkup dan Metodologi</h3>
<p>Ruang lingkup pekerjaan perancangan ini mencakup desain arsitektur basis data, perancangan antarmuka pemrograman aplikasi (API), pembuatan topologi jaringan <em>cloud</em>, hingga pedoman mitigasi risiko siber.<br/>
Metodologi yang digunakan mengadopsi pendekatan <em>Agile System Development Life Cycle (SDLC)</em> yang dikombinasikan dengan observasi lapangan dan analisis kebutuhan:</p>
<ul>
    <li><strong>Pengumpulan Data:</strong> Menganalisis <em>bottleneck</em> pada server eksisting SIMPAD, memetakan alur birokrasi manual, dan mengevaluasi spesifikasi perangkat keras/jaringan yang dimiliki oleh Pemkot Baubau.</li>
    <li><strong>Perancangan Desain:</strong> Menentukan tumpukan teknologi (<em>tech stack</em>) menggunakan <em>framework</em> Laravel 11, <em>database</em> MySQL/PostgreSQL, serta klasterisasi server menggunakan VPS Neo Cloud.</li>
    <li><strong>Pengujian & Implementasi:</strong> Menyusun skenario pengujian beban (<em>load testing</em>), uji penetrasi (<em>penetration testing</em>), serta rencana peluncuran bertahap (<em>phased rollout</em>).</li>
</ul>

<h2>BAB II: KAJIAN TEORI</h2>
<p>Perencanaan arsitektur perangkat lunak skala pemerintahan (<em>Enterprise/Government Scale</em>) membutuhkan pemahaman mendalam tentang teori-teori komputasi modern. Penerapan konsep yang tepat akan menentukan stabilitas, keamanan, dan usia pakai (<em>lifespan</em>) dari perangkat lunak yang dibangun.</p>

<h3>2.1 Teori Arsitektur Perangkat Lunak (N-Tier Architecture)</h3>
<p>Model N-Tier (Multi-Lapis) adalah paradigma rekayasa perangkat lunak yang secara logis maupun fisik memisahkan aplikasi ke dalam beberapa lapisan fungsional yang berbeda. Pada sistem pendapatan Baubau, diterapkan arsitektur 3-Tier:</p>
<ol>
    <li><strong>Presentation Tier:</strong> Berupa aplikasi <em>Front-End</em> berbasis React.js (untuk Admin) dan <em>Progressive Web App/Mobile</em> (untuk Warga dan Petugas). Lapis ini tidak memiliki akses langsung ke basis data.</li>
    <li><strong>Application Tier (Logic):</strong> Diperankan oleh <em>API Gateway</em> tersentralisasi berbasis Laravel 11. Di sinilah <em>Formula Parser</em>, <em>Penalty Engine</em>, dan <em>JIT Billing</em> dieksekusi.</li>
    <li><strong>Data Tier:</strong> Infrastruktur pangkalan data (MySQL/PostgreSQL) yang dikombinasikan dengan <em>In-Memory Cache</em> (Redis) untuk menampung data secara persisten dan cepat.</li>
</ol>
<p>Pemilihan N-Tier memungkinkan sistem diperbarui secara terisolasi. Jika terjadi lonjakan pengunjung pada aplikasi Warga, <em>server frontend</em> dapat ditingkatkan kapasitasnya (<em>scaled up</em>) tanpa harus merestart <em>server database</em>.</p>

<h3>2.2 Teori Komputasi Terdistribusi dan <em>Load Balancing</em></h3>
<p>Dalam mengelola jutaan <em>request</em> API dari kanal perbankan, <em>Load Balancing</em> bertindak sebagai polisi lalu lintas yang mendistribusikan beban jaringan di antara sekumpulan server (<em>server farm</em>). Dengan algoritma <em>Round Robin</em> atau <em>Least Connections</em>, <em>Load Balancer</em> memastikan tidak ada satu <em>server node</em> pun yang mengalami kelebihan beban operasional, sehingga <em>downtime</em> (waktu henti) dapat ditekan hingga mendekati angka nol (<em>Zero Downtime</em>).</p>

<h3>2.3 Teori Kriptografi dan Keamanan Transaksi</h3>
<p>Transaksi finansial lintas server (<em>Host-to-Host</em>) membutuhkan tingkat ketelitian kriptografi kelas perbankan. Teori yang diimplementasikan meliputi:</p>
<ul>
    <li><strong>Asymmetric Cryptography (RSA-2048):</strong> Penggunaan kunci publik dan kunci privat untuk memastikan bahwa hanya server Bank dan server BAPENDA yang dapat mendekripsi <em>payload</em> pembayaran.</li>
    <li><strong>Hash-based Message Authentication Code (HMAC):</strong> Mekanisme <em>digital signature</em> menggunakan SHA-256 yang menempel pada <em>Header</em> HTTP. Jika ada peretas (Man-in-the-Middle) yang mencegat dan merubah isi nominal pembayaran walau hanya satu digit, verifikasi HMAC di sisi <em>receiver</em> akan otomatis gagal.</li>
</ul>

<h3>2.4 Teori Basis Data Terpadu (<em>Unified Database</em>)</h3>
<p>Konsep tradisional mendiktekan pembuatan tabel terpisah untuk setiap jenis pajak (silo). Teori <em>Unified Database</em> mendekonstruksi ini dengan menyatukan entitas inti (misal: Entitas Wajib Pajak) ke dalam satu tabel utama <em>Master_WP</em>, yang terhubung dengan tabel <em>Sub_Objek_Pajak</em> melalui skema JSON (JavaScript Object Notation). Struktur JSON memungkinkan atribut pajak bervariasi—misalnya Pajak Reklame memiliki variabel ukuran PxL, sedangkan Pajak Hotel memiliki variabel Kelas Kamar—tanpa harus merusak arsitektur <em>schema</em> tabel relasional (<em>schema-less flexibility</em>).</p>

<h2>BAB III: ARSITEKTUR DAN EKOSISTEM SISTEM</h2>

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

    subgraph Publik ["🌐 Area Publik / Pengguna Akhir"]
        ClientWarga["📱 Aplikasi Warga & E-SPOPD"]:::cloud
        ClientBank["🏦 Sistem Perbankan (H2H)"]:::cloud
        ClientPetugas["📸 Aplikasi Mobile Petugas"]:::cloud
    end

    subgraph Keamanan ["🛡️ Layer Keamanan & Perimeter"]
        WAF["🧱 Web Application Firewall (WAF) & Load Balancer"]:::firewall
    end

    subgraph VPS ["☁️ Infrastruktur Neo Cloud VPS Bapenda"]
        AppNode1["⚙️ API Gateway (Node 1)"]:::server
        AppNode2["⚙️ API Gateway (Node 2 - Failover)"]:::server
    end

    subgraph DataCenter ["💾 Data Center Terpusat"]
        DB["🗄️ Unified Relational DB"]:::db
        Redis["⚡ Redis In-Memory Cache"]:::db
        Storage["📁 Cloud Object Storage (PDF TTE, Foto)"]:::db
    end

    ClientWarga -->|HTTPS| WAF
    ClientBank -->|Mutual TLS| WAF
    ClientPetugas -->|HTTPS| WAF
    
    WAF --> AppNode1
    WAF -.->|Failover| AppNode2
    
    AppNode1 <-->|Query & Write| DB
    AppNode1 <-->|Cache Session| Redis
    AppNode1 <-->|Simpan Berkas| Storage
    
    AppNode2 <--> DB
</div>

<h3>3.1 Deskripsi Topologi Jaringan & Infrastruktur</h3>
<p>Arsitektur sistem dibangun dengan pendekatan tangguh yang mampu menangani transaksi tingkat tinggi dengan spesifikasi sebagai berikut:</p>
<ol>
    <li><strong>Layer Publik:</strong> Seluruh interaksi dari Wajib Pajak, Petugas Bapenda, dan mesin perbankan dialirkan melalui jalur internet publik dengan perlindungan enkripsi Transport Layer Security (TLS 1.2+).</li>
    <li><strong>Layer Perimeter WAF:</strong> Sebagai garda terdepan, layanan mitigasi awan bertugas mendeteksi pola anomali. IP luar negeri dapat diblokir <em>by-default</em> untuk mencegah serangan <em>brute-force</em> skala internasional, menyisakan hanya akses domestik dan IP statis Bank yang terdaftar (<em>whitelisted</em>).</li>
    <li><strong>Layer Aplikasi (Neo Cloud VPS):</strong> Menggunakan instans peladen virtual bertenaga tinggi (minimal 8 Core vCPU, 16 GB RAM). Modul API Laravel bertindak sebagai orkestrator yang mengurai permintaan JSON. Jika <em>Node 1</em> mengalami gangguan (<em>crash</em>), <em>Load Balancer</em> akan otomatis membelokkan trafik ke <em>Node 2</em>.</li>
    <li><strong>Layer Pangkalan Data:</strong> Mengusung konsep pemisahan komputasi (<em>Compute</em>) dan penyimpanan (<em>Storage</em>). Redis difungsikan untuk melayani pembacaan data yang berulang secara instan (seperti cek profil Wajib Pajak), menghemat konsumsi CPU pada database utama.</li>
</ol>

<h2>BAB IV: SPESIFIKASI MODUL DAN PROSES BISNIS</h2>
<p>Spesifikasi fungsional sistem dibagi ke dalam empat ekosistem modul platform yang masing-masing melayani aktor spesifik.</p>

<h3>4.1 Modul Pendaftaran dan Validasi Basis Data</h3>
<p>Fase registrasi merupakan gerbang awal untuk membentuk identitas digital Wajib Pajak.</p>
<ul>
    <li><strong>Alur Warga:</strong> Melalui portal <em>E-SPOPD</em>, warga dapat mengisi formulir pendataan objek baru dengan melampirkan berkas KTP dan foto lokasi usaha.</li>
    <li><strong>Alur Admin:</strong> Petugas Bapenda di <em>Dashboard Web</em> melakukan validasi silang (verifikasi dokumen). Sistem kemudian mengeksekusi algoritma <em>auto-increment</em> sektoral untuk mencetak Nomor Pokok Wajib Pajak Daerah (NPWPD) yang unik.</li>
</ul>

<h3>4.2 Modul Pengawasan dan Uji Petik Spasial (GIS)</h3>
<p>Menjawab kendala penagihan lapangan, Aplikasi <em>Mobile</em> Petugas dilengkapi sistem telemetri:</p>
<ul>
    <li><strong>Tracking Geografis:</strong> Petugas yang mendatangi restoran untuk melakukan <em>Uji Petik</em> omzet harian wajib mengaktifkan modul pelacak. <em>Backend</em> memvalidasi apakah koordinat GPS perangkat <em>smartphone</em> sinkron dengan koordinat NPWPD di pangkalan data (toleransi radius 50 meter).</li>
    <li><strong>Visualisasi Heatmap:</strong> Pemimpin Bapenda memantau wilayah dengan tingkat tunggakan tertinggi melalui panel peta interaktif, mengubah <em>spreadsheet</em> mati menjadi intelegensi geospasial hidup.</li>
</ul>

<h3>4.3 Modul Penetapan (Billing Engine) dan Legalitas Dokumen TTE</h3>

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
    BS -->|Hitung Pokok Pajak| FPS
    BS -->|Validasi Keterlambatan| PCS
</div>

<p>Modul paling revolusioner dalam platform ini adalah implementasi <em>Just-In-Time (JIT) Billing Engine</em>.</p>
<ul>
    <li><strong>JIT Mechanism:</strong> Alih-alih melakukan generasi jutaan baris tagihan pada 1 Januari, mesin <em>JIT</em> menghitung nilai hutang tepat pada detik di mana <em>Bank</em> melakukan <em>Inquiry</em>.</li>
    <li><strong>Penalty Engine:</strong> Keterlambatan pelunasan akan dievaluasi secara otomatis setiap bulan, menyuntikkan denda komulatif sebesar 2% per bulan sesuai Perda tanpa perlu sentuhan <em>admin</em>.</li>
    <li><strong>Penerbitan SKPD & TTE:</strong> Setelah angka dikunci, server membuat fail PDF (Surat Ketetapan Pajak Daerah) dan berkomunikasi dengan server BSrE BSSN untuk membubuhkan stempel Tanda Tangan Elektronik (Sertifikat Digital) berbentuk QR Code. Dokumen ini sah dan memiliki kekuatan hukum penuh di mata pengadilan pajak.</li>
</ul>

<h3>4.4 Modul Integrasi Gateway Pembayaran (Host-to-Host)</h3>

<div class="mermaid">
sequenceDiagram
    autonumber
    participant WP as Wajib Pajak
    participant Bank as Open API Perbankan
    participant Bapenda as API Core Bapenda

    WP->>Bank: Memasukkan NPWPD/Kode Bayar (Via ATM/Teller/Mobile)
    Bank->>Bapenda: POST /api/v1/inquiry (Cek Validitas Tagihan)
    Bapenda-->>Bank: 200 OK (Response: Detail Wajib Pajak, Pokok, Denda)
    Bank-->>WP: Menampilkan Layar Konfirmasi Nominal

    WP->>Bank: Otorisasi Pembayaran (Konfirmasi PIN)
    Bank->>Bank: Mutasi Debet Rekening Nasabah
    Bank->>Bapenda: POST /api/v1/payment (Notifikasi Pelunasan)
    Bapenda->>Bapenda: Validasi HMAC Signature, Update Status "LUNAS", Generate NTPD
    Bapenda-->>Bank: 200 OK (NTPD Disahkan)
    Bank-->>WP: Menerbitkan Bukti Bayar / Struk (NTPD Tertera)
</div>

<p>Lompatan kuantum menuju <em>Smart City</em> diwujudkan melalui konektivitas antar-mesin (<em>Host-to-Host</em>) dengan jaringan perbankan (Bank Sultra, Mandiri, BNI).<br/>
Seluruh jembatan komunikasi dienkapsulasi menggunakan Standar Nasional Open API Pembayaran (SNAP) Bank Indonesia. Paradigma komunikasi asinkron antara siklus <em>Inquiry</em> (pengecekan saldo) dan <em>Payment</em> (pemotongan/notifikasi webhook) menyingkirkan jeda pelaporan yang sebelumnya memakan waktu berhari-hari menjadi instan (<em>T+0 Real-Time Reconciliation</em>). Wajib Pajak dimanjakan oleh dukungan omni-kanal: mulai dari pembayaran tunai lewat <em>Teller</em>, transfer <em>Virtual Account</em>, minimarket, hingga metode QRIS Dinamis.</p>

<h2>BAB V: STANDAR KEAMANAN SISTEM</h2>
<p>Sistem perpajakan daerah mengelola pangkalan data privasi jutaan entitas demografis, sekaligus menjadi muara perputaran agregat finansial puluhan miliar rupiah per tahun. Kepatuhan terhadap kaidah ketahanan siber (<em>cyber resilience</em>) bukan sekadar opsi, melainkan keharusan absolut.</p>

<h3>5.1 Kriptografi Transaksi Bank (HMAC & Asymmetric)</h3>
<p>Sebagai garis pertahanan utama dalam menjaga legitimasi data transaksi <em>Host-to-Host</em>, setiap <em>payload</em> JSON yang dipertukarkan antara <em>Payment Gateway</em> Bank dan server BAPENDA diproteksi dengan tanda tangan digital berlapis:</p>
<ol>
    <li><strong>HMAC-SHA256:</strong> String otentikasi dihasilkan dari gabungan parameter khusus (Tanggal, Nominal, Kode Bank) yang di-<em>hash</em> menggunakan <em>Secret Key</em>. Algoritma ini menjamin integritas paket data agar mustahil untuk dimanipulasi di tengah jalan (<em>Man-in-the-Middle Attack</em>).</li>
    <li><strong>RSA-2048 Encryption:</strong> Dalam skenario pertukaran data yang sangat krusial, kunci asimetris digunakan. Bank menyandikan (<em>encrypt</em>) data dengan <em>Public Key</em> Pemkot Baubau, sehingga secara matematis hanya <em>Private Key</em> Bapenda yang bisa membuka gembok data tersebut.</li>
</ol>

<h3>5.2 Kendali Akses Berbasis Geografi (Geo-Blocking & IP Whitelisting)</h3>
<p>Titik akses administratif (<em>Admin Dashboard</em>) dan jalur penyelesaian setoran API dikonfigurasi dalam lingkungan kedap udara:</p>
<ul>
    <li>Mekanisme <em>IP Whitelisting</em> memastikan <em>Router Firewall</em> hanya menerima instruksi pembayaran dari deretan alamat IP terdaftar milik Bank Mitra. Akses dari IP anonim, apalagi yang berasal dari teritori luar Indonesia (Geo-IP Blocking), akan langsung dilempar ke keranjang sampah jaringan (<em>Drop</em> / <em>Blackhole</em>).</li>
</ul>

<h3>5.3 Otorisasi Sesi Pengguna Berbasis Token (JWT)</h3>
<p>Untuk memitigasi pencurian identitas dan <em>Session Hijacking</em>, arsitektur penanganan sesi tidak menggunakan metode usang berupa tabel persisten di basis data. Sistem mengadopsi JSON Web Tokens (JWT) bersertifikasi:</p>
<ul>
    <li>Setiap petugas/admin yang masuk (<em>login</em>) dibekali sepotong Token yang telah dienkripsi dengan <em>secret base64</em>. Token ini <em>stateless</em>, artinya server tidak perlu menanyakan ke <em>database</em> berulang kali.</li>
    <li>Token dilengkapi dengan <em>Time-To-Live</em> (TTL) atau umur pendek (misal: kadaluarsa tiap 1 jam), memaksa mekanisme <em>Silent Refresh</em> di latar belakang. Bila gawai pengguna diretas, token akan basi dengan sendirinya, menetralisir bahaya serangan lanjutan.</li>
</ul>

<h2>BAB VI: RENCANA IMPLEMENTASI DAN PENGUJIAN</h2>
<p>Perjalanan menuju penyebaran (<em>deployment</em>) paripurna memerlukan pedoman manajerial yang pragmatis, yang digerakkan melalui bingkai kerja hibrida antara presisi desain air-terjun (<em>Waterfall</em>) dan kecepatan operasional gesit (<em>Agile</em>).</p>

<h3>6.1 Fase Konstruksi Inti (Sprint Development)</h3>
<p>Fase fondasional difokuskan pada perakitan tulang punggung sistem:</p>
<ul>
    <li><strong>Bulan Pertama - Perancangan Data & Unified DB:</strong> Menganalisis skema warisan lama (SIMPAD/SIMPBB), membangun pemetaan ERD (<em>Entity Relationship Diagram</em>) baru yang menampung multi-variabel pajak ke dalam format JSON <em>metadata</em>, dan memvalidasi koneksi server.</li>
    <li><strong>Bulan Kedua - Mesin Logika (Billing & Penalty):</strong> Proses pemrograman modul <em>backend</em> secara intensif. Pemasangan <em>Formula Parser</em> yang sanggup menerjemahkan hitungan pajak beragam objek secara adaptif, dikalibrasi ketat terhadap Perda Kota Baubau terbaru.</li>
</ul>

<h3>6.2 Fase Integrasi Eksternal dan Audit Keamanan</h3>
<p>Setelah fondasi komputasi berdiri, sistem dicolokkan ke dunia luar:</p>
<ul>
    <li><strong>Bulan Ketiga - SIT (System Integration Testing) Sandbox Bank:</strong> Interkoneksi virtual dilakukan antara server pengembangan Pemkot Baubau dan server uji coba perbankan. Mengonfirmasi apakah proses HMAC <em>signature</em>, respons kode HTTP 200/400, dan pencetakan struk digital lulus uji coba presisi matematis tanpa <em>bug</em>.</li>
    <li><strong>Integrasi Otomatis TTE (BSrE BSSN):</strong> Penyambungan jembatan API ke mesin sandi milik negara untuk menata penandatanganan SKPD elektronik secara massal tanpa penundaan antrean <em>server</em>.</li>
    <li><strong>Audit Penetrasi (Pen-Test):</strong> Mensimulasikan serangan terstruktur ke <em>port</em> terbuka guna menutupi celah <em>Vulnerabilities</em> (kerentanan siber) tingkat tinggi yang berpotensi menjadi bumerang reputasi.</li>
</ul>

<h3>6.3 Fase Validasi (UAT) dan Gelar Peluncuran (Go-Live)</h3>
<ul>
    <li><strong>Bulan Keempat - UAT (User Acceptance Testing):</strong> Sesi pelatihan (<em>Transfer of Knowledge</em>) dan uji skenario harian secara langsung bersama aparatur penagihan Bapenda, petugas operator, dan eksekutif. Merombak navigasi UI/UX bila ditemukan ketidaknyamanan interaksi (<em>friction</em>).</li>
    <li><strong>Peluncuran Tersusun (Phased Rollout):</strong> Perilisan dikelola dalam tahapan aman (Soft-Launch). Dimulai dengan operasional <em>Web Dashboard Admin</em> di kantor Bapenda. Di minggu berikutnya peluncuran <em>Aplikasi Mobile Petugas</em>, sebelum akhirnya puncak penyerahan aplikasi layanan mandiri ke <em>PlayStore</em> (Google Android) untuk bisa dinikmati masif oleh warga Baubau.</li>
</ul>

<h2>BAB VII: PENUTUP</h2>
<p>Dokumen Perencanaan (Blueprint) Arsitektur Sistem Informasi Pendapatan Daerah ini dihidupkan dengan roh transformasi: menjauh dari sistem pelaporan yang usang, menuju instrumen kontrol pemerintahan berkaliber korporasi (<em>enterprise-grade</em>). Perancangan yang holistik—melibatkan perombakan topologi pangkalan data tunggal (<em>Unified</em>), rekayasa komputasi performa tinggi (<em>Load Balancing</em> & <em>Redis Cache</em>), hingga penjahitan lapisan pertahanan kriptografi militer (HMAC SNAP BI)—menggarisbawahi komitmen tanpa kompromi dari Pemerintah Kota Baubau untuk mengamankan dan mengoptimalisasi Pendapatan Asli Daerah (PAD).</p>
<p>Dengan meminggirkan campur tangan manusia dalam proses pencatatan, kalkulasi denda, dan verifikasi pelunasan H2H, kebocoran fiskal yang bersifat sistemik kini berhasil dimitigasi sejak hulu. Ekosistem digital revolusioner ini bukan saja sekadar menjawab rintangan konektivitas masa kini, melainkan disiapkan sebagai jembatan <em>Future-Proof</em> yang luwes untuk menopang kemajuan konstelasi layanan <em>Smart City</em> Kota Baubau pada era masa depan. Visi besar pelayanan yang bersih, terukur, dan bermartabat, telah diletakkan batu pertamanya di sini.</p>

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

print("Massive expansion complete for both files.")
