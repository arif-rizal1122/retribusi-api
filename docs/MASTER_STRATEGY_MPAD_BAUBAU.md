# 📑 BLUEPRINT STRATEGIS: AKSELERASI M-PAD BAUBAU (NIK & SMART TAX)

## 1. Visi Utama: Transformasi Pajak Proaktif
M-PAD (Modernisasi Pajak Daerah) Kota BauBau bukan sekadar alat digitalisasi, melainkan sebuah ekosistem yang mengubah hubungan antara Pemerintah dan Wajib Pajak (WP). 
*   **Target**: Meningkatkan PAD melalui kemudahan akses dan transparansi.
*   **Inti Strategi**: NIK sebagai jangkar data (*Single Identity*) dan No HP sebagai kanal interaksi (*Communication Hub*).

---

## 2. Pilar I: Ekosistem NIK-Centric (Data Interoperability)
Menjadikan NIK sebagai satu-satunya kunci akses (SSO) untuk meminimalisir redundansi data dan meningkatkan akurasi objek pajak.

### A. Mekanisme Integrasi Lintas Instansi
| Objek Pajak | Jembatan Data (Bridge) | Output Sistem |
| :--- | :--- | :--- |
| **PBB-P2** | NIK ↔ NOP ↔ NIB | Penarikan otomatis luas tanah/bangunan dari data BPN. |
| **BPHTB** | NIK ↔ Sertifikat | Validasi otomatis kepemilikan aset saat transaksi. |
| **Pajak Usaha** | NIK ↔ Data OSS | Aktivasi NPWPD instan tanpa formulir fisik. |
| **Pajak Kendaraan** | NIK ↔ STNK | Query tagihan langsung dari basis data Korlantas. |

### B. Single Identity Government (SIG)
NIK berfungsi sebagai *Master Key* yang memungkinkan pemerintah melakukan *Profiling* WP secara komprehensif, mendeteksi potensi pajak yang belum tergali (misal: kepemilikan kendaraan mewah yang belum terdaftar PBB-nya).

---

## 3. Pilar II: Smart Tax Interaction (Kanal No HP Aktif)
Mengadopsi model *Conversational Tax*—di mana pajak dibayar semudah mengirim pesan instan.

### A. Hierarki Kepercayaan (Security Tiers)
Sistem menggunakan data registrasi SIM berbasis NIK dari Kemdigi untuk membangun "High-Trust Channel":
1.  **Level 1 (NIK Validation)**: Memastikan NIK ada dan hidup (Dukcapil).
2.  **Level 2 (Tax Identity)**: Sinkronisasi NIK sebagai NPWP (Coretax DJP).
3.  **Level 3 (Mobile Verified)**: Mengunci No HP yang terdaftar secara resmi atas NIK tersebut.
4.  **Level 4 (Transactional OTP)**: Pengiriman sandi sekali pakai untuk konfirmasi pembayaran di atas ambang batas.

### B. Kanal Interaksi & User Experience
*   **WhatsApp Business API (Chatbot)**: Layanan mandiri 24/7. WP dapat mengetik "Cek Tagihan" dan sistem langsung membalas dengan nominal rincian.
*   **Push Notification Billing**: Sistem mengirimkan invoice digital (PDF) langsung ke WA WP 14 hari sebelum jatuh tempo.
*   **Digital Wallet Integration**: Link pembayaran yang dikirim mendukung pembayaran satu klik via QRIS, VA, atau E-Wallet terverifikasi.

---

## 4. Pilar III: Pengawasan & Penegakan Hukum (Supervision)
Menggunakan data NIK untuk meningkatkan kepatuhan melalui pengawasan berbasis data (*Data-Driven Enforcement*).

*   **Tax Clearance System**: Integrasi NIK dengan layanan publik lainnya. WP yang memiliki tunggakan pajak (terdeteksi via NIK) akan diminta melunasi sebelum mengakses layanan tertentu (misal: perpanjangan izin usaha).
*   **Deteksi Aset Tersembunyi**: Pemadanan data konsumsi (Listrik/Air) dengan kepemilikan aset berbasis NIK untuk menemukan objek pajak baru.
*   **Digital Audit Trail**: Setiap interaksi WP via No HP tercatat secara permanen, memudahkan proses audit dan meminimalisir sengketa pajak.

---

## 5. Skenario Interaksi: Objek Pajak Reklame (End-to-End)
1.  **Reminder**: Sistem push WA ke pemilik reklame: *"Bpk. Ahmad, Pajak Videotron Anda jatuh tempo. Nominal: Rp 12.500.000."*
2.  **Self-Service**: WP membalas *"INFO"*. Bot mengirimkan detail (ukuran, lokasi, foto objek).
3.  **Objection**: WP merasa ukuran salah, balas *"KEBERATAN"*. Sistem membekukan tagihan dan menjadwalkan kunjungan petugas.
4.  **Verification**: Petugas datang, input data di aplikasi **Retribusi-Petugas**, tagihan diperbarui secara otomatis.
5.  **Settlement**: WP menerima revisi nominal, konfirmasi via OTP, bayar via Link.
6.  **Legacy**: Sertifikat Lunas (SSP) dikirim dalam hitungan detik via WA dengan TTE resmi.

---

## 6. Matriks Kesiapan & Peta Jalan (Roadmap)

### Fase 1: Short-Term (Quick Wins) - 1-3 Bulan
*   **Update `IdentityValidationService`**: Transisi dari Mock ke API Dukcapil riil.
*   **WhatsApp Webhook**: Implementasi *Auto-Reply* dasar untuk inkuiri tagihan.
*   **NPWPD simplification**: Penghapusan batasan karakter input sesuai kebutuhan lapangan.

### Fase 2: Medium-Term (Integration) - 4-9 Bulan
*   **H2H BPN & Korlantas**: Penarikan data aset otomatis berbasis NIK.
*   **Full TTE Automation**: Integrasi total `TTEService` pada setiap output dokumen tagihan/lunas.
*   **Multi-Channel Payment**: Aktivasi H2H Payment Gateway untuk sinkronisasi lunas seketika.

### Fase 3: Long-Term (Intelligence) - 12+ Bulan
*   **AI Chatbot Advisor**: Bot yang mampu memberikan konsultasi perpajakan daerah secara cerdas.
*   **Predictive Analytics**: Dashboard yang memprediksi realisasi PAD berdasarkan tren perilaku bayar WP per wilayah.

---
**Referensi Dokumen Konsolidasi:**
*   *Resolusi M-PAD BauBau.docx*
*   *plus no hp utk media PAD.docx*
*   *Audit Sistem M-PAD April 2026*
