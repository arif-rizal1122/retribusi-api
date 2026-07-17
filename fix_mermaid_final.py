import re

md_file = '/Users/pondokit/Herd/retribusi-api/docs/Dokumen_Perencanaan_Sistem_Pendapatan_Baubau.md'
html_file = '/Users/pondokit/Herd/visibaubau-4.0/old/dokumen/Dokumen_Perencanaan_Sistem_Pendapatan_Baubau.html'

diagrams = [
"""<div class="mermaid" style="max-width: 800px; margin: 20px auto;">
flowchart TD
    classDef cloud fill:#e3f2fd,stroke:#1565c0,stroke-width:2px,color:#0d47a1
    classDef server fill:#fff3e0,stroke:#e65100,stroke-width:2px,color:#e65100
    classDef db fill:#e8f5e9,stroke:#2e7d32,stroke-width:2px,color:#1b5e20
    classDef firewall fill:#ffebee,stroke:#c62828,stroke-width:2px,color:#b71c1c
    classDef network fill:#f3e5f5,stroke:#303f9f,stroke-width:2px,color:#1a237e

    subgraph AreaPublik[KANAL INTERNET PUBLIK DAN INTRANET]
        Warga[Wajib Pajak Browser dan Mobile Apps]:::cloud
        Perbankan[Sistem Core Banking Mitra]:::cloud
        Petugas[Intranet VPN Bapenda]:::cloud
    end

    subgraph AreaKeamanan[PERIMETER KEAMANAN SIBER DMZ]
        WAF[Web Application Firewall L7 Anti DDoS]:::firewall
        LB[L4 L7 API Load Balancer]:::network
    end

    subgraph AreaVPS[KLASTER APLIKASI MICROSERVICES VPS]
        Node1[App Server Node 01 Laravel]:::server
        Node2[App Server Node 02 Auto Scaling]:::server
    end

    subgraph AreaDB[DATA CENTER DAN PERSISTENSI]
        DBMaster[Database Master MySQL PostgreSQL]:::db
        Redis[Redis Cache Server Session Store]:::db
        ObjectStorage[S3 Object Storage PDF Media]:::db
    end

    Warga -->|HTTPS TLS 1.3| WAF
    Perbankan -->|IPSEC VPN Kriptografi| WAF
    Petugas -->|HTTPS Private VPN| WAF

    WAF -->|Traffic Bersih| LB
    LB -->|Distribusi 50 Persen| Node1
    LB -->|Distribusi 50 Persen| Node2

    Node1 -->|Read Write ORM| DBMaster
    Node1 -->|Set Get Cache| Redis
    Node1 -->|Signed URL| ObjectStorage

    Node2 -->|Read Write ORM| DBMaster
    Node2 -->|Set Get Cache| Redis
    Node2 -->|Signed URL| ObjectStorage
</div>""",

"""<div class="mermaid" style="max-width: 800px; margin: 20px auto;">
flowchart TD
    classDef step fill:#e3f2fd,stroke:#1565c0,stroke-width:2px,color:#0d47a1
    classDef decision fill:#fff3e0,stroke:#e65100,stroke-width:2px,color:#e65100
    classDef endnode fill:#e8f5e9,stroke:#2e7d32,stroke-width:2px,color:#1b5e20

    A[Wajib Pajak Buka Aplikasi]:::step
    B(Input NIK dan NPWP):::step
    C{Sistem Validasi Dukcapil dan DJP}:::decision
    D[Notifikasi Error Data Tidak Valid]:::step
    E(Tentukan Titik Peta Google Maps):::step
    F(Unggah Foto Usaha ke S3 Storage):::step
    G(Isi Detail Objek Pajak):::step
    H(Submit E-SPOPD):::step
    I[Petugas Bapenda Verifikasi Lapangan]:::step
    J{Kesesuaian Data}:::decision
    K[Tolak dan Kembalikan Revisi]:::step
    L[Terbitkan NPWPD dan SKPD Elektronik]:::endnode

    A --> B
    B --> C
    C -->|Gagal| D
    C -->|Valid| E
    E --> F
    F --> G
    G --> H
    H --> I
    I --> J
    J -->|Tidak Sesuai| K
    J -->|Sesuai| L
</div>""",

"""<div class="mermaid" style="max-width: 800px; margin: 20px auto;">
flowchart TD
    classDef step fill:#e3f2fd,stroke:#1565c0,stroke-width:2px,color:#0d47a1
    classDef server fill:#fff3e0,stroke:#e65100,stroke-width:2px,color:#e65100
    classDef db fill:#e8f5e9,stroke:#2e7d32,stroke-width:2px,color:#1b5e20
    classDef decision fill:#f3e5f5,stroke:#303f9f,stroke-width:2px,color:#1a237e

    WP[Wajib Pajak]:::step
    MB(JIT Billing Engine Aktif):::step
    App[App Server Laravel]:::server
    Redis(Kalkulasi Tagihan Denda):::db
    DB(Kunci Transaksi Idempotency):::db
    API[Push API Create VA QRIS SNAP]:::server
    Bank[Core Banking Bank Mitra]:::server
    Tampil(Tampilkan Kode Bayar QR ke WP):::step
    WP2{WP Membayar di ATM MB}:::decision
    Bayar(Saldo WP Dipotong):::step
    API2[Webhook Real-time Payment SNAP]:::server
    App2[App Server Update Status Lunas]:::server
    GenPDF(Generate TTE Bukti Lunas PDF):::step
    Bank2[Mitigasi Selesai Rekonsiliasi]:::server

    WP -->|Pilih Bayar di mPaD| MB
    MB --> App
    App --> Redis
    Redis --> DB
    DB --> API
    API --> Bank
    Bank -->|Response VA Number QR| App
    App --> Tampil
    Tampil --> WP2
    WP2 -->|Selesai Bayar| Bayar
    Bayar --> API2
    API2 --> App2
    App2 --> GenPDF
    App2 -->|API Response 200 OK| Bank2
</div>""",

"""<div class="mermaid" style="max-width: 800px; margin: 20px auto;">
flowchart LR
    classDef check fill:#e8f5e9,stroke:#2e7d32,stroke-width:2px,color:#1b5e20
    classDef backup fill:#fff3e0,stroke:#e65100,stroke-width:2px,color:#e65100
    classDef update fill:#e3f2fd,stroke:#1565c0,stroke-width:2px,color:#0d47a1

    A(Pengecekan Harian Radar Log Error):::check
    B(Monitoring Lonjakan CPU):::check
    C(Validasi API Handshake Bank):::check
    D(Replikasi Database Mingguan):::backup
    E(Flushing Cache Redis):::backup
    F(Update Security Patch Bulanan):::update
    G(Perpanjangan Sertifikat SSL):::update

    A --> B
    B --> C
    C --> D
    D --> E
    E --> F
    F --> G
</div>"""
]

for file_path in [md_file, html_file]:
    with open(file_path, 'r') as f:
        content = f.read()

    # Extract all mermaid blocks
    blocks = re.findall(r'<div class="mermaid".*?</div>', content, re.DOTALL)
    
    if len(blocks) == 4:
        for i in range(4):
            content = content.replace(blocks[i], diagrams[i])
        
        with open(file_path, 'w') as f:
            f.write(content)
        print(f"Replaced 4 diagrams in {file_path}")
    else:
        print(f"Found {len(blocks)} blocks in {file_path}, expected 4.")

