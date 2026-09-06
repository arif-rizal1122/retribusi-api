# FRAMEWORK 7-LANGKAH AUDIT & EVALUASI LAYANAN M-PAD
## Siklus Evaluasi Kinerja Layanan & Pemahaman OPD Terintegrasi (7-Step Service Intelligence Cycle)
**Pemerintah Kota Baubau | Badan Pendapatan Daerah & OPD Pengampu Retribusi**

---

## 1. Latar Belakang & Urgensi

Dalam tata kelola transformasi digital sektor publik, keberhasilan sistem pemerintahan berbasis elektronik (SPBE) tidak cukup diukur dari penyelesaian penulisan kode (*software development output*), melainkan harus diukur dari **dampak nyata tata kelola, efisiensi pelayanan, kepatuhan fiskal, dan kepuasan masyarakat (*public service outcome*)**.

Dokumen ini memformalkan **Framework 7-Langkah Audit & Evaluasi Layanan M-PAD** yang memadukan kepatuhan hukum, integritas teknis, pemisahan tugas kewenangan (Segregation of Duties), pengamanan perbankan, inovasi rekayasa sistem, serta **dua tahapan evaluasi pasca-implementasi (Feedback Loop & Kinerja OPD)**.

---

## 2. Diagram Alur Siklus 7-Langkah (7-Step Service Intelligence Cycle)

```
[1. Telaah Regulasi & Kepatuhan] ──> [2. Pemetaan Kode/DB Eksisting] ──> [3. Penyelarasan Tupoksi/RBAC]
                                                                                  │
[6. Analisis Aduan & Sentimen Warga] <── [5. Desain Fitur Best-Practice] <── [4. Validasi H2H & Aliran RKUD]
         │
         ▼
[7. Monitoring SLA & Realisasi PAD] ──> (Iterasi Rekomendasi Kebijakan & Optimasi Fitur)
```

---

## 3. Rincian Metodologi 7-Langkah

### Tahap 1: Top-Down Compliance Audit (Kepatuhan Regulasi & Formula)
* **Fokus**: Memastikan kepatuhan dasar hukum terhadap UU No. 1/2022 (HKPD), Perda Kota Baubau No. 1/2024, serta peraturan wali kota terkait.
* **Aksi Konkret**:
  1. Validasi batas atas tarif PBJT (10% umum, 40%-75% hiburan khusus).
  2. Standarisasi tarif retribusi jasa usaha dan jasa umum berbasis luas, frekuensi, atau unit volume terukur.
  3. Mencegah tumpang-tindih objek pajak daerah dengan objek PPN pusat.

### Tahap 2: As-Is Codebase & Schema Mapping (Audit Kesiapan Teknis)
* **Fokus**: Verifikasi kesiapan riil kode program, skema relasi basis data, dan endpoint API backend/frontend.
* **Aksi Konkret**:
  1. Audit skema database di `retribusi-api` (tabel, foreign keys, constraints, dan index N+1).
  2. Audit mutator dan snapshot nominal pada model Eloquent untuk menjamin integritas audit trail finansial.
  3. Penyelarasan tipe data (TypeScript interfaces) di `retribusi-admin`, `retribusi-mobile`, dan `retribusi-petugas`.

### Tahap 3: SOTK & RBAC Alignment (Pemisahan Kewenangan Tegas / SoD)
* **Fokus**: Membagi peran secara tegas antara BAPENDA sebagai *Revenue Controller* dan 18 OPD Teknis sebagai *Technical Profiler & Verifier*.
* **Aksi Konkret**:
  1. **Bapenda (Wasit Fiskal)**: Mengontrol arus kasda, review E-SPTPD, deteksi anomali omzet (*Red Channel*), penerbitan SKRD/SKPDKB, dan rekonsiliasi Bank Sultra.
  2. **18 OPD Teknis (Verifikator Lapangan)**: Memverifikasi profil fisik, kelayakan izin, dan volume sarana tanpa akses memungut atau memanipulasi kas tunai.

### Tahap 4: H2H Flow & Financial Safety Validation (Validasi Aliran Kasda)
* **Fokus**: Menjamin keamanan transaksi non-tunai langsung ke Rekening Kas Umum Daerah (RKUD).
* **Aksi Konkret**:
  1. Validasi enkripsi dan *digital signature* (HMAC SHA-256 / Asymmetric RSA) pada setiap webhook callback bank.
  2. Idempotency handling untuk mencegah *double-crediting* atau selisih pembukuan.
  3. Penguncian nominal pada backend (*backend as single source of truth*).

### Tahap 5: Best-Practice Engineering & Anti-Fraud Design
* **Fokus**: Merancang fitur keamanan dan otomasi transaksi modern.
* **Aksi Konkret**:
  1. **Split-Payment / Automatic Fund Transfer (AFT)**: Otomasi pembagian hak penerimaan saat dana masuk.
  2. **E-Mandate & TTE BSrE**: Penerbitan SKRD dan tanda bukti sah menggunakan sertifikat elektronik tersertifikasi.
  3. **Red Channel Alert**: Otomasi peringatan jika pelaporan omzet mandiri anjlok >40% dari tren historis atau >35% di bawah estimasi uji petik lapangan.

### Tahap 6: User-Centric Sentiment Audit (Evaluasi Kualitatif & Aduan Warga)
* **Fokus**: Menyerap data dari modul **Matriks Indikator Penilaian dan Pengaduan M-PAD** (`Matriks Indikator Penilaian dan Pengaduan M-PAD.docx`) untuk mendeteksi *friction* dan hambatan layanan di masyarakat.
* **Komponen 3-Kategori**:
  1. *Kinerja Aplikasi*: Penilaian kecepatan kalkulator bisnis (Rating Bintang 1-5) dan kestabilan sistem (bebas force-close/crash).
  2. *Kanal Bank*: Kecepatan respons pemindaian QRIS Dinamis dan keakuratan status bayar (deteksi gagal potong/pending webhook).
  3. *Pelayanan OPD*: Keramahan petugas lapangan, integritas verifikator, serta deteksi aduan pungli dan keterlambatan verifikasi >24 jam.

### Tahap 7: SLA Monitoring & Revenue Variance Analytics (Evaluasi Kinerja OPD & Target PAD)
* **Fokus**: Menghubungkan kecepatan operasional ASN di masing-masing OPD dengan realisasi target anggaran kas daerah.
* **Metrik Utama**:
  1. *Service Level Agreement (SLA) Responsivitas*:
     $$\text{SLA OPD} = \text{Waktu rata-rata proses pengajuan baru dari status 'Pending' menjadi 'Aktif/Terbit Rekomendasi'}$$
     Jika melebihi 48 jam, ditandai peringatan merah pada dashboard pimpinan.
  2. *Revenue Variance*:
     $$\Delta \text{Variance} = \text{Realisasi Setoran Riil RKUD} - \text{Potensi Estimasi Kalkulator Sistem}$$
     Mengukur efektivitas OPD dalam mengkonversi potensi terdata menjadi setoran riil.
  3. *Tingkat Digitalisasi Sektor*:
     Rasio wajib retribusi di bawah binaan OPD yang telah beralih 100% ke pembayaran non-tunai.

---

## 4. Siklus Berkelanjutan (Continuous Improvement Loop)

Hasil dari Tahap 6 (Aduan & Sentimen) dan Tahap 7 (SLA & Variance) secara berkala dianalisis untuk menghasilkan:
1. **Rekomendasi Revisi Regulasi**: Jika formula tarif terbukti terlalu memberatkan atau tidak realistis.
2. **Peningkatan Engineering/UX**: Jika aduan menunjukkan kegagalan transaksi atau kesulitan pengisian formulir warga.
3. **Penyegaran Pembinaan OPD**: Jika SLA verifikasi salah satu OPD terlambat atau terjadi varians pendapatan negatif yang signifikan.
