<div align="center">
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
1.  **Undang-Undang Nomor 1 Tahun 2022** tentang Hubungan Keuangan Antara Pemerintah Pusat dan Pemerintahan Daerah (HKPD).
2.  **Peraturan Daerah (Perda) Kota Baubau Nomor 1 Tahun 2024** tentang Pajak Daerah dan Retribusi Daerah (PDRD).
3.  **Peraturan Wali Kota Baubau Nomor 58 Tahun 2024** tentang Tata Cara Pemungutan Pajak Daerah dan Retribusi Daerah.
4.  Standar Nasional Open API Pembayaran (SNAP) dari Bank Indonesia terkait interkoneksi sistem perbankan.

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
