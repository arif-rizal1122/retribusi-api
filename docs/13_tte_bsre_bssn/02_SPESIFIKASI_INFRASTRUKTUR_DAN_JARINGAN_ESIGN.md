# 🖥️ SPESIFIKASI INFRASTRUKTUR & KEAMANAN SERVER ESIGN CLIENT
## Standarisasi Server Lokal Balai Sertifikasi Elektronik (BSrE - BSSN)
### Data Center Diskominfo Kota Baubau — Sistem M-PAD
*Rujukan Slide Resmi BSrE: [`03_spesifikasi_server_esign_client.jpeg`](./03_spesifikasi_server_esign_client.jpeg)*

---

## 1. Spesifikasi Perangkat Keras & Sistem Operasi

Berdasarkan slide presentasi teknis BSrE ([`03_spesifikasi_server_esign_client.jpeg`](./03_spesifikasi_server_esign_client.jpeg)), spesifikasi minimum server modul **Esign Client Service** yang wajib disediakan oleh Pemerintah Kota Baubau adalah:

| Komponen | Spesifikasi Minimum BSrE | Alokasi Infrastruktur Pemkot Baubau |
|:---|:---|:---|
| **Sistem Operasi** | Ubuntu 24 (Minimal Server) | Ubuntu 24.04 LTS Server 64-bit |
| **Processor (CPU)** | Minimum 4 Core vCPU | 4 Core Dedicated vCPU @ 2.4 GHz+ |
| **Memory (RAM)** | Minimum 8 GB | 8 GB Dedicated RAM |
| **Penyimpanan (HDD/SSD)** | Minimum 50 GB | 100 GB NVMe Enterprise Storage |
| **Port Akses Jaringan** | Port 80, 443, dan 22 | Terisolasi via Firewall UFW / VLAN Privat |

---

## 2. Topologi Jaringan & Prinsip Isolasi Server

### A. Catatan Kritis Keamanan dari BSrE:
> ⚠️ **ATURAN WAJIB DARI BSSN**:
> 1. Server Esign Client **TIDAK PERLU DAN TIDAK BOLEH DIBUKA KE PUBLIK (INTERNET)**.
> 2. Server aplikasi (`retribusi-api`) harus mengakses Server Esign Client secara **LOKAL (JARINGAN PRIVAT / LAN)**.

```mermaid
flowchart LR
    subgraph DMZ_PUBLIC ["Zona Publik (Internet Eksternal)"]
        Warga["Warga / WP / Petugas Lapangan"]
        Nginx["Reverse Proxy (Nginx HTTPS :443)"]
    end

    subgraph INTRANET ["Zona Privat (LAN / VLAN Data Center Pemkot)"]
        ApiServer["Server M-PAD Backend (retribusi-api)"]
        EsignServer["Server Esign Client (Ubuntu 24)<br/>IP Privat: 192.168.x.x<br/>Port: 80 / 443 Lokal"]
    end

    subgraph BSSN_CLOUD ["Cloud Infrastructure BSrE - BSSN"]
        BSRE_API["https://api-bsre.bssn.go.id"]
        BSRE_DEV["https://osd-dev.bssn.go.id"]
        BSRE_REG["https://registry-bsre.bssn.go.id"]
        BSRE_MAIL["https://mail.bssn.go.id"]
    end

    Warga -->|HTTPS Port 443| Nginx
    Nginx --> ApiServer
    ApiServer -->|HTTP/REST Lokal (Port 80/443)| EsignServer
    EsignServer -. Akses Ditolak Dari Publik .-x Warga
    EsignServer -->|Egress Port 443| BSRE_API
    EsignServer -->|Egress Port 443| BSRE_DEV
    EsignServer -->|Egress Port 443| BSRE_REG
    EsignServer -->|Egress Port 443| BSRE_MAIL
```

### B. Konfigurasi Port Jaringan
| Port | Protokol | Arah (Direction) | Sumber (Source) | Tujuan (Destination) | Fungsi |
|:---|:---|:---|:---|:---|:---|
| **22** | TCP (SSH) | Inbound | IP Bastion Tim IT Diskominfo | Server Esign Client | Akses pemeliharaan teknis internal |
| **80 / 443** | TCP (HTTP/S) | Inbound | IP Privat Server `retribusi-api` | Server Esign Client | Pemanggilan REST API penandatanganan |
| **443** | TCP (TLS) | Outbound (Egress) | Server Esign Client | Domain Whitelist BSSN | Pengiriman Hash Value & validasi CA |

Seluruh port inbound lainnya diblokir total pada level firewall perimeter router Diskominfo.
