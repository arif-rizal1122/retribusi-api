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

## 📦 Deployment
Sinkronisasi otomatis melalui GitHub Actions ke VPS:
- **Production**: Push ke branch `main` → `api.sipanda.online`
- **Development**: Push ke branch `dev` → `api-dev.sipanda.online`

## 👥 Contributors
- [muhdanfyan](https://github.com/muhdanfyan)
- [arif-rizal1122](https://github.com/arif-rizal1122)
