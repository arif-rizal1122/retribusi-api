# 🧠 MASTER AI GUIDE & PROMPT ANATOMY

Dokumen ini adalah **titik masuk tunggal** bagi setiap AI Agent. Ikuti protokol ini agar pengerjaan efisien, hemat token, dan konsisten dengan arsitektur M-PAD.

---

## 🧭 1. Protokol Pembacaan Kondisional (Context Routing)
AI Agent **TIDAK BOLEH** melakukan riset buta. Gunakan tabel ini untuk menentukan apa yang harus dibaca:

| Kondisi / Tugas | File yang WAJIB Dibaca | Alasan |
| :--- | :--- | :--- |
| **Awal Percakapan** | `docs/SYSTEM_OVERVIEW.md` | Untuk paham Arsitektur, Git-Flow, & Domain. |
| **Terjadi Error 500/CORS** | `docs/MITIGATION_GUIDE.md` | Daftar bug historis & fix yang sudah ada. |
| **Akses VPS / Maintenance** | `docs/INFRASTRUCTURE_NOTES.md`| Detail IP, Paths, & Password hints. |
| **Testing / Verifikasi** | `docs/TESTING_GUIDE.md` | Daftar akun demo & cara interpretasi hasil. |
| **Baseline Stabilitas** | `docs/STABLE_BASELINE.md` | ID Commit GitHub yang sudah diverifikasi aman. |
| **Update Security** | `docs/testing-reports/VULNERABILITY_ANALYSIS.md` | Memahami audit keamanan terakhir & audit IDOR. |


---

## 🧬 2. Enhanced Prompt Anatomy (Skema Acuan)
Gunakan skema ini dalam setiap perencanaan/implementasi untuk menghindari pengulangan instruksi:

### A. Context Injection (Input)
AI harus selalu mengasumsikan variabel berikut:
- **Project Name:** M-PAD (Mitra PAD).
- **Environment:** 8 Domain (Prod: `*.sipanda.online`, Dev: `*-dev.sipanda.online`).
- **Tech Stack:** Laravel 11 (API), React Vite (Frontend), GitHub Actions (CI/CD).
- **Owner:** `www-data` di VPS (Jangan gunakan `sipanda` untuk `artisan optimize`).

### B. Execution Schema (Logic)
Gunakan format ini saat memberikan solusi atau kode:
1. **Constraint Check:** Apakah solusi ini kompatibel dengan PHP VPS? (Gunakan *Positional Arguments*).
2. **Contextual Continuity:** Apakah rute/logic ini sudah ada di `routes/api.php`?
3. **Atomic Changes:** Edit hanya blok kode yang relevan (Minimalisir `replace_file_content`).
4. **Zero-Waste Verif:** Gunakan `curl -I` untuk sanity check.

---

## 🛠️ 3. Task Dispatcher (Pembagi Tugas)
Jika user memberikan tugas kompleks, AI harus membagi ke dalam kategori:

1. **Infrastruktur:** Masuk ke `docs/INFRASTRUCTURE_NOTES.md`.
2. **Logika API:** Masuk ke `routes/api.php` & `app/Http/Controllers/`.
3. **Frontend Branding:** Masuk ke `retribusi-mobile/src/` (Nama: M-PAD).
4. **Security Audit:** Jalankan `testing/test_penetration.sh`.

---

## 🚫 4. Aturan Keselamatan (The Never-Do's)
- **DILARANG** meng-hardcode password/secrets di dokumentasi atau kode.
- **DILARANG** merubah CORS pada Nginx tanpa mendaftarkan domain secara eksplisit.
- **DILARANG** mengosongkan folder `storage` atau `bootstrap/cache` di server.

---
> [!IMPORTANT]
> **TOKEN SAVER:** Jika Anda sudah membaca file dalam sesi ini, **Gunakan Ingatan Anda**. Jangan panggil `view_file` berulang kali untuk file dokumen yang sama kecuali ada perubahan fisik pada file tersebut.

*Protocol Version: 2.0 (High Efficiency). Terakhir diperbarui: 26 Februari 2026.*
