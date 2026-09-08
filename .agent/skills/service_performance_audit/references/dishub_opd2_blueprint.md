# ⚓ DOKUMEN ANALISIS KOMPREHENSIF IMPLEMENTASI M-PAD V5
## KLUSTER RETRIBUSI PERHUBUNGAN: DINAS PERHUBUNGAN (DISHUB / OPD ID 2) KOTA BAUBAU
### *Berdasarkan Dokumen 1 (Master Blueprint & Framework 7-Langkah), Dokumen 2 (Platform, Aktor, RBAC & Smart Forms), Dokumen 3 (Agentic AI, Hermes & Leantime), serta Perda Kota Baubau No. 1 Tahun 2024*

---

## 1. Klasifikasi Layanan yang Diampu Dishub

Sesuai dengan pemetaan tupoksi M-PAD, Dinas Perhubungan (Dishub) Kota Baubau mengelola kluster **Retribusi Jasa Umum & Jasa Usaha Sektor Perhubungan** yang mencakup:

### A. Retribusi Pelayanan Parkir di Luar Badan Jalan / Tempat Khusus Parkir (TKP)
* **Objek Pengelolaan**: Pengelolaan titik parkir khusus aset Pemerintah Kota Baubau (kawasan pelabuhan, terminal, pelataran pasar, dan titik keramaian seperti area Pantai Kamali / Kota Mara).
* **Karakteristik**: Beroperasi dengan pembatasan Satuan Ruang Parkir (SRP), penugasan pos jaga / gate, dan penguncian koordinat poligon MapPicker GIS.

### B. Retribusi Pelayanan Parkir Tepi Jalan Umum & Early Warning System (EWS)
* **Objek Pengelolaan**: Titik parkir jalan kolektor/lokal resmi berdasarkan SK Walikota (Jl. Yos Sudarso, Jl. Sultan Hasanuddin, Pasar Sentral, dll.).
* **Pengawasan & EWS**: Pengawasan juru parkir resmi ber-QRIS Dinamis dan pendeteksian kepatuhan setoran secara real-time.

### C. Retribusi Jasa Kepelabuhanan (Milestone M32 PMO)
* **Objek Pengelolaan**: Tambat labuh perahu/kapal rakyat, pas masuk pelabuhan daerah, dan pemanfaatan fasilitas dermaga/tambatan milik Pemerintah Kota Baubau (Pelabuhan Jembatan Batu, Dermaga Batupoopi, dan fasilitas sandar sekitar Pelabuhan Murhum).

---

## 2. Implementasi Terhadap 4 Platform & Antarmuka Dishub (Dokumen 2)

Dalam arsitektur M-PAD V5, Dishub beroperasi dengan batasan ruang lingkup (*data scope*) terisolasi sesuai prinsip *Segregation of Duties (SoD)*:

### A. Admin Panel Web Dishub (`opd_id: 2` — `adminmpad.baubaukota.go.id`)
* **Akses Sidebar Terbatas**:
  Sistem secara otomatis mengisolasi navigasi menu Dishub pada:
  - `Dashboard Dishub` (`/dashboard`)
  - `Verifikasi & Validasi` (`/verification`)
  - `Wajib Retribusi` (`/taxpayers`)
  - `Billing & Tagihan` (`/bills`)
  - `Pencapaian Klasifikasi` (`/opd-sectors`)
  - `Parkir Dishub (EWS)` (`/parkir-dishub`)
  - `Reporting` (`/reports`)
* **Segregation of Duties (SoD)**:
  Operator Dishub bertindak sebagai **Verifikator OPD (Aktor 3)**. Memiliki wewenang memvalidasi SKRD/karcis parkir digital dan tagihan tambat labuh, namun **diblokir mutlak** dari akses rekening koran Kasda atau modifikasi tabel tarif Perda.

### B. Petugas Mobile App (Juru Parkir & Petugas Pelabuhan)
* Pencatatan nomor kendaraan masuk/keluar pada Tempat Khusus Parkir secara *offline-first*.
* Pencetakan struk karcis digital berbasis **QRIS Dinamis (SNAP BI)** seketika menggunakan printer thermal bluetooth.
* Verifikasi titik lokasi petugas dengan validasi **geotagging & anti-spoof GPS** (mencegah presensi palsu di luar radius titik tugas).

### C. Mobile Warga / Wajib Retribusi (PWA — `mpad.baubaukota.go.id`)
* Pemilik kapal/agen pelayaran dapat mengajukan permohonan jasa kepelabuhanan dan perpanjangan izin tambat secara mandiri via **Smart Forms** tanpa tatap muka di kantor Dishub.
* Auto-pull data identitas dan notifikasi kode bayar VA/QRIS secara instan.

### D. Core Engine (`api.mpad.baubaukota.go.id`)
* RESTful API backend dengan isolasi data `opd_id = 2`, penghitungan denda otomatis, dan integrasi webhook perbankan.

---

## 3. Penyelarasan Framework 7-Langkah (Dokumen 1)

Tata kelola Dishub di dalam M-PAD mengikuti siklus terpadu:

* **Langkah 1 (Tarif Deterministik)**:
  Tarif retribusi parkir (roda dua Rp 2.000, roda empat Rp 3.000 / Rp 5.000, truk) dan tarif tambat kapal dikunci langsung di Core Engine database sesuai lampiran **Perda No. 1/2024**. Petugas lapangan tidak dapat mengubah nominal pungutan secara manual dari UI aplikasi.
* **Langkah 2 & 4 (SNAP BI & Non-Tunai)**:
  Menghilangkan kebocoran retribusi tunai di loket pelabuhan/pos parkir dengan menerapkan pembayaran langsung via QRIS Dinamis (MPM/CPM) dan Virtual Account multibank (Bank Sultra, Mandiri, BRI, BNI).
* **Langkah 5 (Rekonsiliasi Kasda T+0)**:
  Setiap setoran parkir atau tambat kapal yang dibayar via QRIS/VA otomatis mengunci data piutang dengan mekanisme `lockForUpdate()`, dan dana langsung masuk ke Kas Daerah (RKUD) Baubau pada hari yang sama (T+0).
* **Langkah 6 (Investigasi Anomali Red Channel / EWS Parkir)**:
  Menu khusus Parkir Dishub (EWS) memonitor rasio potensi setoran harian tiap titik parkir. Jika volume kendaraan normal namun setoran harian **merosot > 35%**, sistem otomatis menandai titik tersebut sebagai **Red Channel** untuk diterbitkan **Surat Tugas Uji Petik** bagi surveyor/tim Wasdal Dishub.

---

## 4. Integrasi Agentic AI & PMO Leantime (Dokumen 3)

### A. Google Gemini Sparks (Briefing PAD Dishub)
* Merangkum performa harian titik-titik parkir dan pelabuhan setiap pagi pukul **07.30 WITA** ke **Dashboard Eksekutif** dan kanal Kepala Dinas Perhubungan (realisasi vs target harian, titik parkir yang tidak aktif menyetor/dormant, dan durasi SLA verifikasi izin labuh).

### B. Hermes SRE Watchdog (DeepSeek-V4-Flash)
* Menjaga ketersediaan antrean sinkronisasi transaksi mobile juru parkir di lapangan agar API tidak mengalami *bottleneck* saat jam puncak arus transportasi kapal/pasar (*peak hours*).
* Menangani kegagalan koneksi di dermaga dengan *offline queue buffering* dan auto-restart worker antrean.

### C. Leantime PMO (`leantime.sarjanakomputer.id`)
* Layanan Dishub dikawal di bawah **Milestone 32 (M32 - Kluster Retribusi Kepelabuhanan & Parkir)**, mengaudit ketercapaian:
  1. Penyelarasan form digital (Smart Forms).
  2. Uji coba alat lapangan juru parkir (Mobile App Android & printer thermal).
  3. Integrasi webhook SNAP BI perbankan (Zero Balance Gap).

---

## 5. Rekomendasi Rencana Aksi Teknis untuk Dishub

1. **Ganti Kredensial Default**: Operator admin Dishub wajib segera melakukan pembaruan password akun `admin.dishub@baubaukota.go.id` pada saat login pertama.
2. **Pemetaan Objek Fisik (MapPicker GIS)**: Petugas Dishub bersama surveyor memetakan koordinat poligon seluruh Tempat Khusus Parkir (TKP) dan dermaga kapal rakyat di peta digital M-PAD untuk validasi ZNT dan zonasi retribusi.
3. **Pemberlakuan Karcis/Ketetapan QRIS**: Menghentikan peredaran karcis manual secara bertahap dan menggantinya dengan verifikasi digital via *Petugas Mobile App* guna menjamin kepatuhan *Zero Leakage*.
