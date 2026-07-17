import re

html_file = '/Users/pondokit/Herd/visibaubau-4.0/old/dokumen/Dokumen_Perencanaan_Sistem_Pendapatan_Baubau.html'
md_file = '/Users/pondokit/Herd/retribusi-api/docs/Dokumen_Perencanaan_Sistem_Pendapatan_Baubau.md'

with open(html_file, 'r') as f:
    html_content = f.read()

with open(md_file, 'r') as f:
    md_content = f.read()

bad_mermaid = r'<div class="mermaid">\s*architecture-beta.*?</div>'

good_mermaid = """<div class="mermaid">
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
</div>"""

html_content = re.sub(bad_mermaid, good_mermaid, html_content, flags=re.DOTALL)
md_content = re.sub(bad_mermaid, good_mermaid, md_content, flags=re.DOTALL)

with open(html_file, 'w') as f:
    f.write(html_content)

with open(md_file, 'w') as f:
    f.write(md_content)

print("Mermaid fixed")
