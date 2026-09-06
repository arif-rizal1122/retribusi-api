# Cetak Biru Transformasi: M-PAD Menuju Sultra Connect
**Integrasi Satu Data Fiskal & Pendapatan Regional Sulawesi Tenggara**

---

## 1. Visi Strategis: Dari Embrio Lokal ke Backbone Provinsi
M-PAD (*Management Information Tax, Retribution, and Asset Pendapatan Asli Daerah*) yang beroperasi di Kota Baubau merupakan embrio yang teruji di tingkat operasional. Transformasi menuju **Sultra Connect** bukan sekadar mereplikasi aplikasi, melainkan membangun **Satu Data Fiskal Regional** yang menjembatani seluruh Pemerintah Daerah se-Sulawesi Tenggara.

---

## 2. Empat Pilar Strategis Sultra Connect

### A. Standardisasi Tata Kelola Pendapatan (White-Label Platform)
* **Tantangan:** Fragmentasi sistem antar daerah (Kabupaten A menggunakan sistem X, Kabupaten B sistem Y).
* **Solusi Sultra Connect:** Mengadopsi M-PAD sebagai platform *white-label* terstandarisasi.
* **Dampak:** Memangkas biaya APBD 17 kabupaten/kota. Daerah cukup menyesuaikan parameter tarif pajak/retribusi sesuai Perda masing-masing tanpa membangun aplikasi dari nol.

### B. Konsolidasi Fiskal & Dashboard Gubernur
* **Tantangan:** Kesulitan pemantauan kapasitas fiskal riil daerah secara real-time untuk pembagian DBH atau bantuan keuangan provinsi.
* **Solusi Sultra Connect:** Bertindak sebagai *macro-data hub*. Data transaksi dan realisasi PAD 17 kabupaten/kota mengalir ke Dashboard Fiskal Eksekutif Provinsi.
* **Dampak:** Gubernur dan tim TP2DD memiliki basis data presisi untuk proyeksi fiskal 3-5 tahun dan alokasi insentif pembangunan.

### C. Integrasi Pajak Sektoral Maritim & Komoditas (Lintas Batas)
* **Tantangan:** Potensi kebocoran pajak pada komoditas lintas wilayah (maritim, logistik tambang, distribusi bahan bakar kapal/MFO).
* **Solusi Sultra Connect:** Integrasi H2H dengan data pelabuhan (Syahbandar/Inaportnet), bea cukai, dan data pertambangan.
* **Dampak:** Mengamankan potensi PDRD sektor maritim dan PBBKB untuk kapal di atas 30 DWT secara adil antara kabupaten penghasil dan provinsi.

### D. Layanan Tunggal Warga (Single Sign-On se-Sultra)
* **Tantangan:** Pelaku usaha yang memiliki aset di beberapa kabupaten/kota harus mengelola akun terpisah di tiap daerah.
* **Solusi Sultra Connect:** Super Apps publik dengan *Single Sign-On* (SSO). Satu akun dapat mengelola dan membayar PBB, PBJT, retribusi aset di Baubau, Buton, Kendari, maupun Kolaka.
* **Dampak:** Peningkatan signifikan pada Indeks Kemudahan Berusaha (*Ease of Doing Business*) di Sulawesi Tenggara.

---

## 3. Benchmark Sistem Mapan Nasional
Sebagai tolok ukur kesiapan arsitektur:

| Sistem Benchmark | Instansi | Pelajaran Kunci untuk Sultra Connect |
| :--- | :--- | :--- |
| **Sapawarga** | Pemprov Jawa Barat | Model *Super Apps* warga berbasis SSO fiskal yang menghubungkan Bapenda Provinsi, BPD, dan instansi vertikal. |
| **ETax & Dashboard Pajak** | Bappenda DKI Jakarta | Standar tertinggi integrasi hulu H2H dengan BPN dan Dukcapil, serta pemantauan data transaksi usaha real-time. |
| **Simpada** | Jawa Tengah & Jawa Timur | Pola distribusi *White-Label* terstandarisasi yang didukung penuh oleh Bank BPD daerah (Bank Sultra). |
| **Inaportnet** | Kementerian Perhubungan | Pemanfaatan API pelabuhan & syahbandar untuk menakar tonase kapal dan konsumsi bahan bakar maritim. |

---

## 4. Tiga Langkah Hulu Persiapan Arsitektur
1. **Regulasi Payung Hukum (Pergub/SK Bersama):** Regulasi pembagian dan interkoneksi data (*API data-sharing*) antar-daerah dan antar-instansi vertikal.
2. **Infrastruktur Cloud Aman (PDN / Cloud Pemprov):** Penempatan sistem pada Pusat Data Nasional (PDN) atau cloud provinsi berstandar keamanan ISO 27001 dan kepatuhan BSSN.
3. **Arsitektur Open API & Bridge Procedure:** Memastikan *core-engine* M-PAD mengadopsi standar Open API agar integrasi H2H dengan Bank Sultra, BPN, dan Korlantas/Samsat berjalan mulus.
