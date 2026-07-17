# Topologi & Arsitektur Jaringan (M-PAD Bapenda)

## Arsitektur Aplikasi
Aplikasi M-PAD (Pajak Daerah) dibangun menggunakan arsitektur **Monolithic (MVC)**.
Backend: PHP Laravel 11.
Database: PostgreSQL / MySQL.
Web Server: Nginx.

## Topologi Jaringan
```mermaid
graph TD
    Client[BNI Gateway] -->|HTTPS / TLS 1.2+| WAF[Web Application Firewall / Load Balancer]
    WAF -->|Port 443| Server[VPS Neo Cloud Bapenda]
    
    subgraph Neo Cloud VPS
        Server --> App[M-PAD Laravel Backend]
        App --> DB[(Database Master)]
    end
```
