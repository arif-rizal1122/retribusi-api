# 📌 STABLE BASELINE COMMIT IDS

Dokumen ini mencatat ID Commit GitHub yang telah diverifikasi **stabil** dan operasional di lingkungan produksi (VPS & Netlify). Gunakan ID ini sebagai referensi utama jika terjadi regresi atau saat melakukan inisialisasi lingkungan baru.

---

## 🚀 Baseline Saat Ini (Maret 2026)

| Repository | Stable Commit ID | Lingkungan | Tanggal Verifikasi | Status |
| :--- | :--- | :--- | :--- | :--- |
| **retribusi-api** | `7b7388c` | **VPS (api.sipanda.online)** | 02 Maret 2026 | ✅ Tested (Seeder & Logs OK) |
| **retribusi-admin** | `169111d` | **Netlify (admin.sipanda.online)** | 02 Maret 2026 | ✅ Published |
| **retribusi-mobile**| `f0d5fbb` | **Netlify (sipanda.online)** | 02 Maret 2026 | ✅ Published |
| **retribusi-petugas**| `647140e` | **Netlify (via Netlify)** | 02 Maret 2026 | ✅ Published |

---

## 🛠️ Riwayat Verifikasi (Audit Trail)

### 1. retribusi-api (`7b7388c`)
- **Metode**: `TestingScenarioSeeder` dijalankan di VPS.
- **Hasil**: 
    - Database integrity: PASSED (Budi & Ani created).
    - Error Logs: CLEAN (Zero "500 Internal Server Error").
    - Connectivity: PASSED (CORS & Health Check `/up`).
- **Catatan**: Commit ini 1 level di belakang `main` (`abd075c`), namun dipastikan stabil untuk operasional saat ini.

### 2. Frontend Components
- **Metode**: PWA Accessibility check & Production readiness script.
- **Hasil**: Smoke test pada domain produksi menunjukkan sistem dapat diakses dan berfungsi sebagaimana mestinya.

---

## ⚠️ Instruksi untuk AI Agent
Jika Anda diminta melakukan perubahan besar (refactoring/migrasi), **pastikan Anda mencatat ID ini** sebagai titik balik (rollback point). Jangan melakukan `git pull` secara membabi buta di VPS jika versi stabil yang dicatat di sini belum diperbarui.

---
*Terakhir diperbarui: 02 Maret 2026 oleh AI Antigravity.*
