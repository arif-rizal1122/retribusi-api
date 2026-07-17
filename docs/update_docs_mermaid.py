import re
import os

# 1. Read Markdown file
md_file = '/Users/pondokit/Herd/retribusi-api/docs/Dokumen_Perencanaan_Sistem_Pendapatan_Baubau.md'
with open(md_file, 'r') as f:
    md_content = f.read()

# The mermaid codes to inject
topologi = """
<div class="mermaid">
graph TD
    Client[BNI Gateway] -->|HTTPS / TLS 1.2+| WAF[Web Application Firewall / Load Balancer]
    WAF -->|Port 443| Server[VPS Neo Cloud Bapenda]
    
    subgraph Neo Cloud VPS
        Server --> App[M-PAD Laravel Backend]
        App --> DB[(Database Master)]
    end
</div>
"""

h2h = """
<div class="mermaid">
sequenceDiagram
    participant Nasabah
    participant Bank as Sistem Bank
    participant mPaD as Sistem mPaD (Bapenda)

    Note over Nasabah,mPaD: 1. Proses Inquiry (Cek Tagihan)
    Nasabah->>Bank: Input Nomor Bayar/Tagihan
    Bank->>mPaD: POST /api/v1/bank/inquiry
    mPaD-->>Bank: Response: Detail Tagihan & Nominal
    Bank-->>Nasabah: Tampilkan Rincian Tagihan

    Note over Nasabah,mPaD: 2. Proses Payment (Pelunasan)
    Nasabah->>Bank: Konfirmasi Pembayaran & PIN
    Bank->>Bank: Debet Saldo Nasabah
    Bank->>mPaD: POST /api/v1/bank/payment
    mPaD->>mPaD: Update Status "LUNAS" & Generate NTPD
    mPaD-->>Bank: Response: Sukses + NTPD
    Bank-->>Nasabah: Cetak Struk / Resi Pembayaran
</div>
"""

invoicing = """
<div class="mermaid">
graph TD
    subgraph "CORE API ENGINE (Laravel Backend)"
        BS[Billing Service - JIT Engine]
        FPS[Formula Parser - Tariff Logic]
        PCS[Penalty Engine - 1-2% Calc]
        DS[Dunning Engine - Teguran 1 & 2]
    end

    subgraph "ADMIN DASHBOARD (Web)"
        A_VER[Verifikasi Objek & Laporan]
        A_AUDIT[Audit Anomali & Piutang]
        A_DUN[Approve/Kirim Teguran 1 & 2]
    end

    subgraph "PETUGAS APP (Mobile)"
        P_SCAN[QR Scanner Penagihan]
        P_SURV[Daftar Surveillance WP Bandel]
        P_PAY[Record Pembayaran Lapangan]
    end
    
    A_VER --> BS
    P_SCAN --> BS
    BS --> FPS
</div>
"""

# Insert topologi under BAB II
md_content = md_content.replace(
    "## BAB II: ARSITEKTUR & EKOSISTEM SISTEM\n",
    f"## BAB II: ARSITEKTUR & EKOSISTEM SISTEM\n\n{topologi}\n"
)

# Insert invoicing under 3. Penetapan (Billing Engine & TTE)
md_content = md_content.replace(
    "### 3. Penetapan (Billing Engine & TTE)\n",
    f"### 3. Penetapan (Billing Engine & TTE)\n\n{invoicing}\n"
)

# Insert H2H under 4. Pembayaran (Payment Gateway H2H) & Penagihan
md_content = md_content.replace(
    "### 4. Pembayaran (Payment Gateway H2H) & Penagihan\n",
    f"### 4. Pembayaran (Payment Gateway H2H) & Penagihan\n\n{h2h}\n"
)

# Write back to MD
with open(md_file, 'w') as f:
    f.write(md_content)

# 2. Update HTML
html_file = '/Users/pondokit/Herd/visibaubau-4.0/old/dokumen/Dokumen_Perencanaan_Sistem_Pendapatan_Baubau.html'
with open(html_file, 'r') as f:
    html_content = f.read()

# We need to inject mermaid.js script into the head
mermaid_script = '<script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>\n    <script>mermaid.initialize({startOnLoad:true});</script>\n</head>'
if 'mermaid.min.js' not in html_content:
    html_content = html_content.replace('</head>', mermaid_script)

html_content = html_content.replace(
    "<h2>BAB II: ARSITEKTUR &amp; EKOSISTEM SISTEM</h2>\n",
    f"<h2>BAB II: ARSITEKTUR &amp; EKOSISTEM SISTEM</h2>\n{topologi}\n"
)
html_content = html_content.replace(
    "<h3>3. Penetapan (Billing Engine &amp; TTE)</h3>\n",
    f"<h3>3. Penetapan (Billing Engine &amp; TTE)</h3>\n{invoicing}\n"
)
html_content = html_content.replace(
    "<h3>4. Pembayaran (Payment Gateway H2H) &amp; Penagihan</h3>\n",
    f"<h3>4. Pembayaran (Payment Gateway H2H) &amp; Penagihan</h3>\n{h2h}\n"
)

with open(html_file, 'w') as f:
    f.write(html_content)

print("Updated with Mermaid diagrams successfully")
