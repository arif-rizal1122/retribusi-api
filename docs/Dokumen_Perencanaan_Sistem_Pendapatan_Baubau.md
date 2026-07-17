<div align="center">
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
