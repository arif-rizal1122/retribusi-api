import re

html_file = '/Users/pondokit/Herd/visibaubau-4.0/old/dokumen/Dokumen_Perencanaan_Sistem_Pendapatan_Baubau.html'
md_file = '/Users/pondokit/Herd/retribusi-api/docs/Dokumen_Perencanaan_Sistem_Pendapatan_Baubau.md'

with open(html_file, 'r') as f:
    html_content = f.read()

with open(md_file, 'r') as f:
    md_content = f.read()

# 1. Clean existing mermaid divs (both standard and with inline styles)
html_content = re.sub(r'<div class="mermaid"[^>]*>.*?</div>', '', html_content, flags=re.DOTALL)
md_content = re.sub(r'<div class="mermaid"[^>]*>.*?</div>', '', md_content, flags=re.DOTALL)

# 2. Define new, much better Mermaid diagrams and academic descriptions
topologi_md = """
<div class="mermaid">
architecture-beta
    group public(cloud)[Area Publik]
    group waf(cloud)[Keamanan & Load Balancing]
    group vps(server)[Neo Cloud VPS Bapenda]
    group database(database)[Data Center]
    
    service client(internet)[Kanal Perbankan & Warga] in public
    service firewall(firewall)[Web Application Firewall] in waf
    service backend(server)[API Gateway & Laravel Backend] in vps
    service db(database)[Unified MySQL/PostgreSQL DB] in database
    service redis(database)[Redis In-Memory Cache] in database
    
    client:R --> L:firewall
    firewall:R --> L:backend
    backend:R --> L:db
    backend:T --> B:redis
</div>

**Deskripsi Arsitektur Topologi Jaringan:**
Arsitektur sistem dibangun dengan pendekatan *N-Tier Architecture* yang mendisagregasi lapisan antarmuka (Presentasi), logika bisnis (Aplikasi), dan penyimpanan data (Basis Data). Kanal akses yang berasal dari entitas eksternal seperti *Payment Gateway* Perbankan maupun aplikasi pengguna (Wajib Pajak) dialirkan melalui protokol aman (HTTPS/TLS 1.2+). Lapisan pertama dari sistem perimeter dipertahankan oleh *Web Application Firewall* (WAF) yang bertugas menyaring anomali lalu lintas data, mencegah *SQL Injection*, serta melakukan *Load Balancing* untuk mendistribusikan beban secara merata. Pada zona inti (Neo Cloud VPS Bapenda), sebuah *API Gateway* berbasis kerangka kerja Laravel berfungsi sebagai orkestrator layanan (mikro-monolitik) yang menjembatani transaksi antara *client* dan *Unified Database*. Penggunaan *Redis In-Memory Cache* diterapkan guna mengoptimalkan latensi *query* repetitif, sehingga sistem mencapai tingkat ketersediaan (*High Availability*) yang optimal.
"""

topologi_html = """
<div class="mermaid">
architecture-beta
    group public(cloud)[Area Publik]
    group waf(cloud)[Keamanan & Load Balancing]
    group vps(server)[Neo Cloud VPS Bapenda]
    group database(database)[Data Center]
    
    service client(internet)[Kanal Perbankan & Warga] in public
    service firewall(firewall)[Web Application Firewall] in waf
    service backend(server)[API Gateway & Laravel Backend] in vps
    service db(database)[Unified MySQL/PostgreSQL DB] in database
    service redis(database)[Redis Cache] in database
    
    client:R --> L:firewall
    firewall:R --> L:backend
    backend:R --> L:db
    backend:T --> B:redis
</div>

<p style="text-align: justify;"><strong>Deskripsi Arsitektur Topologi Jaringan:</strong><br/>
Arsitektur sistem dibangun dengan pendekatan <em>N-Tier Architecture</em> yang mendisagregasi lapisan antarmuka (Presentasi), logika bisnis (Aplikasi), dan penyimpanan data (Basis Data). Kanal akses yang berasal dari entitas eksternal seperti <em>Payment Gateway</em> Perbankan maupun aplikasi pengguna (Wajib Pajak) dialirkan melalui protokol aman (HTTPS/TLS 1.2+). Lapisan pertama dari sistem perimeter dipertahankan oleh <em>Web Application Firewall</em> (WAF) yang bertugas menyaring anomali lalu lintas data, mencegah <em>SQL Injection</em>, serta melakukan <em>Load Balancing</em> untuk mendistribusikan beban secara merata. Pada zona inti (Neo Cloud VPS Bapenda), sebuah <em>API Gateway</em> berbasis kerangka kerja Laravel berfungsi sebagai orkestrator layanan yang menjembatani transaksi antara <em>client</em> dan <em>Unified Database</em>. Penggunaan <em>Redis In-Memory Cache</em> diterapkan guna mengoptimalkan latensi <em>query</em> repetitif, sehingga sistem mencapai tingkat ketersediaan (<em>High Availability</em>) yang optimal.</p>
"""

invoicing_md = """
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
"""

invoicing_html = """
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
"""

h2h_md = """
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
"""

h2h_html = """
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
"""

# HTML Injections
html_content = html_content.replace(
    "<h2>BAB II: ARSITEKTUR &amp; EKOSISTEM SISTEM</h2>",
    f"<h2>BAB II: ARSITEKTUR &amp; EKOSISTEM SISTEM</h2>\n{topologi_html}\n"
)
html_content = html_content.replace(
    "<h3>3. Penetapan (Billing Engine &amp; TTE)</h3>",
    f"<h3>3. Penetapan (Billing Engine &amp; TTE)</h3>\n{invoicing_html}\n"
)
html_content = html_content.replace(
    "<h3>4. Pembayaran (Payment Gateway H2H) &amp; Penagihan</h3>",
    f"<h3>4. Pembayaran (Payment Gateway H2H) &amp; Penagihan</h3>\n{h2h_html}\n"
)
html_content = html_content.replace("\n\n\n", "\n")

# MD Injections
md_content = md_content.replace(
    "## BAB II: ARSITEKTUR & EKOSISTEM SISTEM",
    f"## BAB II: ARSITEKTUR & EKOSISTEM SISTEM\n{topologi_md}\n"
)
md_content = md_content.replace(
    "### 3. Penetapan (Billing Engine & TTE)",
    f"### 3. Penetapan (Billing Engine & TTE)\n{invoicing_md}\n"
)
md_content = md_content.replace(
    "### 4. Pembayaran (Payment Gateway H2H) & Penagihan",
    f"### 4. Pembayaran (Payment Gateway H2H) & Penagihan\n{h2h_md}\n"
)
md_content = md_content.replace("\n\n\n", "\n")

with open(html_file, 'w') as f:
    f.write(html_content)

with open(md_file, 'w') as f:
    f.write(md_content)

print("Duplicates removed and sophisticated thesis-style architecture added.")
