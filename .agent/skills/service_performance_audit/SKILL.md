---
name: Service Performance Intelligence & Integrated OPD Audit
description: Framework 7-Langkah Komprehensif untuk Audit Kepatuhan, Evaluasi Kinerja Layanan OPD, Sentimen Pengguna (User-Centric Feedback), dan Monitoring SLA & Revenue Variance di Ekosistem M-PAD Kota Baubau.
---

# 📊 Service Performance Intelligence & Integrated OPD Audit
## Framework 7-Langkah Audit & Evaluasi Kinerja Layanan Daerah Terintegrasi (7-Step Service Intelligence Cycle)

Skill ini mendefinisikan metodologi baku dan siklus evaluasi berkelanjutan (*continuous evaluation cycle*) untuk mengukur tidak hanya apakah modul digitalisasi selesai dikodekan (*output*), melainkan bagaimana dampak tata kelola, kepatuhan fiskal, efisiensi aparatur, dan kepuasan masyarakat (*outcome*).

---

## 🔄 Siklus 7-Langkah Berkelanjutan (7-Step Service Intelligence Cycle)

```text
[1. Telaah Regulasi] ──> [2. Pemetaan Kode/DB Eksisting] ──> [3. Penyelarasan Tupoksi/RBAC]
                                                                        │
[6. Analisis Aduan/UI] <── [5. Desain Fitur Best-Practice] <── [4. Validasi H2H & Aliran RKUD]
         │
         ▼
[7. Monitoring SLA & Realisasi PAD] ──> (Iterasi Optimasi Regulasi & Engineering Baru)
```

---

## 📋 Rincian 7 Tahap Implementasi & Audit

### 1. Top-Down Compliance Audit (Kepatuhan Hukum & Formula)
- **Tujuan**: Memastikan setiap rumus hitung, tarif, dan dasar pengenaan pajak/retribusi memiliki landasan hukum yang sah.
- **Instrumen Acuan**:
  - UU No. 1 Tahun 2022 (HKPD).
  - Perda Kota Baubau No. 1 Tahun 2024 tentang Pajak Daerah dan Retribusi Daerah.
  - Perwali Baubau terkait PBJT, Reklame, PBB-P2, dan Tata Kelola Retribusi Jasa Umum/Usaha.
- **Checklist Verifikasi**:
  - [ ] Tarif PBJT Makanan/Minuman, Perhotelan, Kesenian/Hiburan maksimum 10% (kecuali diskotek, karaoke, klab malam, bar, mandi uap/spa sebesar 40%-75%).
  - [ ] Retribusi Jasa Usaha menggunakan struktur pengali berbasis luas, zona lokasi, atau frekuensi pemakaian yang terukur.
  - [ ] Tidak ada pengenaan dobel antara PPN pusat dan PBJT daerah.

### 2. As-Is Codebase & Schema Mapping (Kesiapan Teknis & Arsitektur)
- **Tujuan**: Memeriksa kesiapan riil kode program di `retribusi-api`, `retribusi-admin`, `retribusi-petugas`, dan `retribusi-mobile`.
- **Checklist Verifikasi**:
  - [ ] **Database**: Cek tabel, migration, relasi foreign key, dan indeksasi query untuk performa tinggi.
  - [ ] **Model Eloquent**: Verifikasi `casts`, `fillable`, dan mutator finansial (rupiah, snapshot nominal).
  - [ ] **Route & Controller**: Pastikan validasi form request ketat, sanitasi input, dan idempotent transaction handling.
  - [ ] **Frontend Handshake**: Periksa keselarasan tipe data TypeScript (`interface`), payload request, dan penanganan status error/loading.

### 3. SOTK & RBAC Alignment (Pemisahan Tupoksi & Segregation of Duties)
- **Tujuan**: Menegakkan prinsip *Segregation of Duties (SoD)* secara ketat antara BAPENDA dan 18 OPD Teknis.
- **Prinsip Utama**:
  - **BAPENDA (Otoritas Fiskal / Wasit)**: Memegang kendali tunggal atas arus kas (*cashflow controller*), review E-SPTPD mandiri, verifikasi anomali (*Red Channel*), penerbitan SKRD/SKPDKB, dan rekonsiliasi Kas Daerah Bank Sultra.
  - **OPD Teknis (18 Dinas Terkait / Pembina Lapangan)**: Memverifikasi profil fisik, kelayakan teknis, volume objek, dan rekomendasi operasional tanpa memiliki akses atau kewenangan memungut/memanipulasi kasda tunai.
- **Checklist Verifikasi**:
  - [ ] Middleware hak akses memblokir akun OPD teknis dari endpoint finansial/settlement.
  - [ ] Route guard frontend menyembunyikan menu laporan mutasi kas dari akun non-Bapenda.

### 4. H2H Flow & Financial Safety Validation (Integritas Aliran RKUD)
- **Tujuan**: Menjamin keamanan transmisi data perbankan dan transaksi instan non-tunai.
- **Kanal Transaksi**:
  - Host-to-Host (H2H) Bank Sultra.
  - QRIS Dinamis (ASPI / BI QRIS Standar).
  - Virtual Account (VA) H2H.
- **Checklist Verifikasi**:
  - [ ] Validasi Signature (HMAC SHA-256 / Asymmetric RSA) pada setiap webhook callback bank.
  - [ ] Idempotency key untuk mencegah double-crediting atau double-billing.
  - [ ] Snapshot nominal terkunci (*locked billing amount*) — frontend tidak boleh menjadi sumber kebenaran nominal.

### 5. Best-Practice Engineering & Anti-Fraud Design
- **Tujuan**: Mengimplementasikan arsitektur proteksi kecurangan dan kemudahan transaksi modern.
- **Fitur Unggulan**:
  - **Automatic Fund Transfer (AFT) / Split Payment**: Pemisahan otomatis porsi bagi hasil atau biaya transaksi saat dana masuk.
  - **E-Mandate & TTE BSrE**: Penandatanganan digital dokumen resmi SKRD, SKPDKB, dan Izin menggunakan sertifikat elektronik tersertifikasi.
  - **Red Channel Alerting**: Deteksi anomali omzet yang turun drastis (>40% dari rata-rata historis atau >35% di bawah hasil uji petik) untuk memicu audit lapangan otomatis.

### 6. User-Centric Sentiment Audit (Feedback Loops & Kualitas Pengalaman)
- **Tujuan**: Mengukur *friction* (hambatan) nyata yang dialami wajib pajak dan petugas di lapangan berdasarkan **Matriks Indikator Penilaian dan Pengaduan M-PAD**.
- **Komponen Penilaian 3-Kategori**:
  1. **Kinerja Aplikasi**:
     - Kemudahan dan kecepatan kalkulator bisnis (Rating Bintang 1-5).
     - Stabilitas mobile app (frekuensi error, force close, lagging pada scanner kamera).
  2. **Kanal Bank & Pembayaran**:
     - Kecepatan penerbitan dan pemindaian QRIS Dinamis.
     - Akurasi status pembayaran (kasus saldo terpotong namun tagihan belum berstatus lunas).
     - Log latensi webhook notifikasi Bank Sultra (<3 detik).
  3. **Integritas Pelayanan OPD**:
     - Keramahan dan profesionalisme petugas lapangan saat verifikasi atau surveillance.
     - Deteksi aduan dugaan pungutan liar (pungli) di luar nominal resmi sistem.
     - Tingkat keterlambatan verifikasi berkas perizinan/teknis (>24 jam).
- **Mekanisme Respon**:
  - Aduan bintang 1-2 otomatis masuk ke inbox pengawas inspektorat/Bapenda untuk tindak lanjut cepat.

### 7. SLA Monitoring & Revenue Variance Analytics (Evaluasi Kinerja OPD & PAD)
- **Tujuan**: Menghubungkan metrik operasional harian aparatur OPD dengan realisasi target pendapatan daerah.
- **Indikator Kinerja Utama (KPI)**:
  - **Service Level Agreement (SLA) Responsivitas**:
    $$\text{SLA OPD} = \text{Rata-rata Waktu Verifikasi (Pengajuan Baru } \to \text{ Terbit Rekomendasi/Aktif)}$$
    - *Target Standar*: $\le 24 \text{ jam}$ kerja.
    - *Flagging Warning*: Jika antrean pending $\ge 48 \text{ jam}$, sistem menandai warna merah pada dashboard eksekutif Wali Kota/Sekda.
  - **Revenue Variance (Selisih Potensi vs Realisasi)**:
    $$\Delta \text{Variance} = \text{Realisasi Setoran RKUD} - \text{Potensi Estimasi Sistem}$$
    - Mengidentifikasi celah *under-reporting* wajib pajak atau kelemahan intensifikasi oleh OPD pengampu.
  - **Rasio Partisipasi Digital**:
    Persentase wajib retribusi di bawah naungan OPD yang sudah aktif bertransaksi non-tunai (QRIS/VA) vs pembayaran manual.

---

## 🎯 Panduan Implementasi dalam Task / Audit Agent

Ketika diinstruksikan untuk mengaudit, menganalisis, atau mengembangkan modul baru pada layanan OPD di M-PAD:
1. **Lakukan Audit Langkah 1-3** untuk memverifikasi dasar hukum, kode yang ada, dan batasan hak akses role.
2. **Uji Keamanan Finansial (Langkah 4-5)** untuk memastikan tidak ada celah manipulasi nominal atau kebocoran kasda.
3. **Integrasikan Instrumen Feedback (Langkah 6)** dengan memastikan modul memiliki form ulasan/aduan yang terhubung ke indikator kepuasan.
4. **Sajikan Metrik Evaluasi (Langkah 7)** pada dashboard pimpinan (SLA OPD & gap realisasi anggaran) sehingga kinerja instansi dapat dipantau secara transparan dan akuntabel.
