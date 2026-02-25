# 🏛️ M-PAD API (Backend)

Backend resmi untuk sistem **M-PAD (Manajemen Integrasi Tax, Retribusi, dan Aset Daerah)** Kota Bau-Bau, dibangun menggunakan Laravel 11.

## 🚀 Fitur Utama
- **RESTful API**: Integrasi untuk Mobile PWA, Admin Web, dan Petugas App.
- **Zonasi & Billing**: Logika perhitungan pajak dan retribusi berbasis wilayah (Zonasi).
- **Security**: Pengaturan CORS ketat & integrasi Laravel Sanctum untuk autentikasi.
- **Monitoring**: Integrasi dengan Sentry untuk pelaporan error real-time.

## 🛠️ Persyaratan Sistem
- PHP >= 8.2
- Composer
- MySQL/PostgreSQL
- Nginx (untuk deployment VPS)

## 🧪 Testing Suite
Aplikasi ini dilengkapi dengan suite pengujian otomatis yang komprehensif di folder `testing/`:

1. **`test_production_ready.sh`**: Uji kelayakan produksi (CORS, SSL, Health Check).
2. **`test_api_crud.sh`**: Uji siklus hidup data (Create, Read, Update, Delete) untuk Zone, Taxpayer, dll.
3. **`test_penetration.sh`**: Audit keamanan (OWASP Top 10, SQLi, XSS, IDOR).

Cara menjalankan tes:
```bash
cd testing
./test_production_ready.sh
```

## 📚 Dokumentasi & Konteks Sistem
Seluruh informasi arsitektur, panduan infrastruktur, dan hasil pengujian tersedia di folder [**docs/**](./docs/):

- [**System Overview**](./docs/SYSTEM_OVERVIEW.md): Arsitektur, Git-Flow, dan Alur Data.
- [**Infrastructure Notes**](./docs/INFRASTRUCTURE_NOTES.md): Detail VPS, Path Server, dan Panduan Maintenance.
- [**Mitigation Guide**](./docs/MITIGATION_GUIDE.md): Solusi eror masa lalu (500, CORS, Permission) & Log Keputusan.
- [**AI Navigator**](./docs/AI_CONTEXT.md): Panduan efisiensi token dan konteks cepat untuk AI Agent.
- [**Testing Reports**](./docs/testing-reports/): Hasil audit keamanan dan validasi CRUD terbaru.

## 👥 Contributors
- [muhdanfyan](https://github.com/muhdanfyan)
- [arif-rizal1122](https://github.com/arif-rizal1122)
