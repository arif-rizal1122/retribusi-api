# 🤖 AI Context Navigator & Efficiency Guide

Gunakan dokumen ini untuk memahami workspace secara cepat tanpa memboroskan token.

## 1. Shortcut Konteks (Peta Jalan)
Sebelum melakukan riset mendalam, periksa file-file berikut:
- **`docs/SYSTEM_OVERVIEW.md`**: Arsitektur, Domain, dan Alur Kerja (Wajib Baca).
- **`docs/INFRASTRUCTURE_NOTES.md`**: Detail VPS dan Maintenance (Hanya jika perlu akses server).
- **`docs/MITIGATION_GUIDE.md`**: Daftar error masa lalu dan solusi (Wajib jika ada bug 500/CORS).
- **`testing/`**: Gunakan script di sini untuk validasi, jangan buat script baru jika tidak perlu.

## 2. Fakta Cepat (Zero-Research Facts)
- **Framework:** Laravel 11 (API), React Vite (Frontend).
- **Environment:** 2 Env (Prod & Dev), 8 Domain Total.
- **Autentikasi:** Laravel Sanctum (Bearer Token).
- **Branding:** Nama resmi adalah **M-PAD** (Mitra PAD).
- **CORS Source of Truth:** Nginx handles `OPTIONS`, Laravel handles actual requests.

## 3. Aturan Token-Efficiency untuk AI
1. **Jangan List Directory Berulang:** Gunakan `find` atau `ls -R` hanya sekali di awal. Struktur `docs/` sudah tetap.
2. **Jangan Re-view File Besar:** Jika sudah membaca `routes/api.php` sekali, jangan dibaca lagi kecuali ada perubahan.
3. **Posisioral vs Named:** Jika mengedit middleware, gunakan positional arguments agar kompatibel dengan PHP VPS.
4. **Validasi Cepat:** Gunakan `curl -I` pada `/up` untuk cek status server tanpa membaca body response yang besar.

## 4. Struktur Output yang Disukai
- Gunakan tabel untuk perbandingan.
- Gunakan Mermaid untuk diagram alur.
- Berikan solusi teknis yang langsung bisa di-copy-paste (Format Bash/PHP).

---
*Guide khusus Agentic AI. Terakhir diperbarui: 26 Februari 2026.*
