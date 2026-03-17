---
name: Regulatory Compliance & Tax Logic
description: Skill khusus untuk memastikan seluruh logika bisnis, kalkulasi pajak, dan tenggat waktu sesuai dengan Perwali Kota Baubau (Perwali 58/2024).
---

# Instruksi Kepatuhan Regulasi (Source of Truth)
Gunakan skill ini sebagai acuan hukum utama setiap kali memodifikasi logika perhitungan pajak, denda, atau siklus pelaporan di sistem MPAD.

## 🏛️ Dasar Hukum Utama
Seluruh implementasi wajib merujuk pada:
1.  **`docs/03-perwali-pdrd-summary.md`**: Ringkasan tata cara pemungutan PDRD.
2.  **`API_PBB_BAUBAU_2026.md`**: Skema integrasi dan kebijakan PBB terbaru.
3.  **`testing/07-Formula-Jenis-Pajak.md`**: Checklist validasi formula matematis.

## 📊 Standar Tarif Pajak (Perwali 58/2024)
Pastikan konstanta tarif di kode program sesuai dengan ketentuan:
- **Pajak Hotel & Restoran:** 10%.
- **Pajak Parkir:** 30%.
- **Pajak Air Tanah:** 20%.
- **PBJT Hiburan Malam (Diskotik/Karaoke/Klub):** 40%.
- **Pajak Reklame:** 25%.
- **BPHTB:** 5% (dengan NPOPTKP yang berlaku).

## ⏳ Kebijakan Waktu & Denda
- **Jatuh Tempo Pembayaran:** 15 hari setelah saat terutang (atau sesuai SKPD).
- **Batas Pelaporan (Self Assessment):** Tanggal 15 bulan berikutnya.
- **Sanksi Keterlambatan:** Bunga 2% per bulan (Maksimal 24 bulan).

## 🧮 Aturan PBB (Pajak Bumi & Bangunan)
- **Dasar Hukum:** `API_PBB_BAUBAU_2026.md` (Integrasi Bapenda 2026).
- **NJOPTKP:** Default Rp 10.000.000 sebagai pengurang dasar pengenaan.
- **Inquiry:** Selalu validasi status bayar ("BLM BAYAR" vs "LUNAS") berdasarkan response JSON body, bukan hanya HTTP status code.
- **Bukti Pembayaran (Receipt):** 
  - Wajib mencantumkan **NTPD** (Nomor Transaksi Penerimaan Daerah) sebagai bukti sah dari Bapenda.
  - Receipt Thermal (Petugas) harus mencakup: NOP, Tahun, Nama WP, NTPD, Pokok, Denda, dan Total.
- **SPOP/LSPOP Sync:** Data objek pajak (NOP) di sistem MPAD harus disinkronkan secara berkala/massal dari Admin untuk menjaga akurasi status piutang.

## 🛡️ Aturan Modifikasi Kode
1. **Dilarang keras** mengubah formula kalkulasi di `app/Services/` tanpa memverifikasi ulang angka-angka di atas.
2. Setiap perubahan logika regulasi harus diverifikasi menggunakan skill **`Staging Domain Testing (Comprehensive)`** untuk memastikan tidak ada degradasi perhitungan.
3. Semua pesan error terkait penolakan (misal: denda atau keterlambatan) harus menggunakan bahasa yang sopan dan merujuk pada ketentuan regulasi yang berlaku.
