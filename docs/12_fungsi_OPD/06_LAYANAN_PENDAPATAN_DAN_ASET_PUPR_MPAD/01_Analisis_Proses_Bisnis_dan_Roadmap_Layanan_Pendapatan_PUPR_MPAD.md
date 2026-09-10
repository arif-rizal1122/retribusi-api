ANALISIS PROSES BISNIS, ARSITEKTUR INTEROPERABILITAS PLATFORM (SIMBG & SIPD-RI), DAN ROADMAP LAYANAN PENDAPATAN DINAS PUPR (M-PAD V5)

METADATA DOKUMEN & KONTROL:

Nomor Registrasi: BAPENDA-PUPR/MPAD-V5/ROADMAP/2026/001

Entitas Pengampu: Dinas Pekerjaan Umum dan Penataan Ruang (PUPR) Kota Baubau (OPD ID 3)

Koordinator Fiskal: Badan Pendapatan Daerah (Bapenda) Kota Baubau

Landasan Hukum: UU No. 1 Tahun 2022 (HKPD), PP No. 35 Tahun 2023, Perda Kota Baubau No. 1 Tahun 2024

Versi Sistem: M-PAD V5.4.0 Distributed Enterprise Engine

Status: Approved Baseline Architecture

BAB I. PENDAHULUAN & LANDASAN HUKUM YURIDIS

Transformasi digital penerimaan daerah pada sektor Pekerjaan Umum dan Penataan Ruang (PUPR) merupakan pilar strategis dalam optimalisasi Pendapatan Asli Daerah (PAD) Kota Baubau. Sesuai dengan kerangka tata kelola M-PAD V5, seluruh proses layanan pendapatan wajib memenuhi standar kepatuhan regulasi nasional dan daerah tanpa mengorbankan kepatuhan teknis konstruksi.

1.1 Kerangka Hukum Pengikat

UU No. 1 Tahun 2022 tentang Hubungan Keuangan antara Pemerintah Pusat dan Pemerintahan Daerah (HKPD): Mengklasifikasikan kembali jenis retribusi daerah ke dalam tiga rumpun utama (Retribusi Jasa Umum, Retribusi Jasa Usaha, dan Retribusi Perizinan Tertentu) serta melarang pemungutan di luar ketentuan yang sah.

PP No. 35 Tahun 2023 tentang Ketentuan Umum Pajak Daerah dan Retribusi Daerah (KUPDRD): Mengatur tata cara pemungutan, penetapan SKRD, penggunaan dokumen elektronik berkekuatan hukum, dan tanda tangan elektronik tersertifikasi (TTE).

PP No. 16 Tahun 2021 tentang Peraturan Pelaksanaan UU No. 28 Tahun 2002 tentang Bangunan Gedung: Mengamanatkan penyelenggaraan Persetujuan Bangunan Gedung (PBG) dan Sertifikat Laik Fungsi (SLF) melalui sistem nasional terpusat (SIMBG).

Peraturan Daerah Kota Baubau No. 1 Tahun 2024 tentang Pajak Daerah dan Retribusi Daerah: Menetapkan nomenklatur objek retribusi, formula tarif teknis, dan ketentuan sanksi denda 1% per bulan.

BAB II. PEMETAAN PORTOFOLIO LAYANAN PENDAPATAN DINAS PUPR

Berdasarkan Perda No. 1 Tahun 2024 dan analisis tugas dan fungsi teknis Dinas PUPR, portofolio layanan penerimaan terbagi menjadi dua pilar layanan:

No

Kategori Layanan

Dasar Regulasi

Unit Pengampu Internal PUPR

Objek Retribusi & Output Layanan

1

Retribusi Persetujuan Bangunan Gedung (PBG) & SLF (Perizinan Tertentu)

Pasal 84 ayat (1) & Perwali Standar Harga Satuan Tertinggi (SHST)

Bidang Cipta Karya / Tata Bangunan & TPA/TPT

Pemeriksaan teknis gambar arsitektur, struktur, MEP; penetapan indeks fungsi, kompleksitas, ketinggian; penerbitan Rekomendasi Teknis (Rekomtek) PBG/SLF.

2

Retribusi Sewa Alat Berat & Mesin Konstruksi (Jasa Usaha / BMD)

Pasal 84 ayat (1) huruf i & Lampiran Tarif BMD Perda 1/2024

UPTD Peralatan & Workshop (Pool Alat Berat)

Pemanfaatan armada alat berat (Excavator, Wheel Loader, Tandem Roller/Bomag, Dump Truck, Tronton Self-Loader, Motor Grader, Asphalt Sprayer). Output: SIPAB & BAST.

3

Retribusi Laboratorium Pengujian Konstruksi (Jasa Usaha)

Pasal 84 ayat (1) huruf i & Tarif Pengujian Lab

UPTD Laboratorium Pengujian Bahan Konstruksi

Uji Kuat Tekan Beton (Kubus/Silinder), Uji Tarik Baja Tulangan, Uji Tanah (Sondir, Triaxial, Atterberg), Uji Aspal (Ekstraksi & Marshall). Output: Laporan Hasil Uji (LHU) resmi.

4

Retribusi Pemanfaatan Ruang Milik Jalan (RUMIJA) & Utilitas (Jasa Usaha)

Pasal 84 ayat (1) huruf i & Ketentuan Teknis Bina Marga

Bidang Bina Marga & Tata Ruang

Pemanfaatan bahu/ruang jalan untuk penempatan jaringan utilitas kabel fiber optik, pipa air minum/gas, tiang utilitas, jembatan penyeberangan, dan titik reklame jalan. Output: Izin Teknis RUMIJA.

BAB III. LANSKAP INTEGRASI PLATFORM: DAERAH VS NASIONAL (TERPUSAT)

Pengelolaan pendapatan PUPR memiliki kompleksitas unik karena bergantung pada platform nasional mandatory dari Kementerian teknis pusat. M-PAD V5 menjembatani keterhubungan platform terpusat dengan ekosistem fiskal daerah:

Platform

Otoritas Pemilik

Karakteristik & Batasan

Strategi Integrasi dalam M-PAD V5

SIMBG (simbg.pu.go.id)

Kementerian PUPR (Nasional)

Platform wajib permohonan PBG/SLF. Memiliki engine hitung retribusi teknis, namun kanal pembayaran daerah sering terisolasi atau manual upload bukti transfer.

Bridge API / Webhook Interoperability: M-PAD menarik (PULL) nilai tagihan SKRD PBG dari SIMBG via API/Batch Service -> M-PAD menerbitkan Virtual Account SNAP & QRIS Dinamis Bank Sultra -> Setelah lunas, M-PAD mendorong (PUSH callback) status LUNAS + NTPD + e-SSRD ke SIMBG sehingga dokumen izin resmi terbit otomatis di DPMPTSP.

SIPD-RI (sipd-ri.kemendagri.go.id)

Kemendagri (Nasional)

Platform nasional penganggaran dan penatausahaan keuangan daerah. Pencatatan transaksi pendapatan harus terpetakan ke Bagan Akun Standar (BAS).

Automated Accounting Mapping: M-PAD memetakan setiap rupiah penerimaan PUPR (Akun 4.1.02.01.xx untuk Jasa Umum/PBG, Akun 4.1.02.02.xx untuk Sewa Aset) secara real-time dan menghasilkan rekapitulasi BKU yang siap diimpor/disinkronkan ke SIPD-RI Akuntansi.

OSS-RBA (oss.go.id)

Kementerian Investasi / BKPM

Pintu masuk izin berusaha nasional (NIB & KBLI). Terhubung ke SIMBG untuk perizinan gedung usaha.

M-PAD menyimpan NIB pemohon PBG/sewa aset sebagai basis data kepatuhan pajak (KSWP) dan profiling kegiatan ekonomi.

SIAK Terpusat & IKD

Ditjen Dukcapil Kemendagri

Validasi identitas kependudukan Warga Negara Indonesia.

Auto-fill identitas pemohon di Mobile Warga dan verifikasi NIK peminjam aset secara real-time via VPN aman.

M-PAD Core & SNAP BI

Pemerintah Kota Baubau (Bapenda)

Super App Pendapatan Daerah, Payment Gateway SNAP BI, Direct RKUD Bank Sultra, Dashboard Monitoring Kasda.

Bertindak sebagai master engine transaksi fiskal, manajemen aset (JU-ASET), verifikasi lapangan mobile petugas, dan rekonsiliasi kasda H+0.

BAB IV. KERANGKA ROADMAP PEMBUATAN PELAYANAN PENDAPATAN PUPR (FRAMEWORK 7-LANGKAH)

Implementasi layanan pendapatan PUPR dieksekusi melalui 4 Fase Terukur yang diadaptasi dari Framework 7-Langkah Tata Kelola M-PAD V5:

Fase 0: Fondasi Regulasi, Normalisasi Master Data, & Demarkasi Kelembagaan (Durasi: 2 Minggu)

Langkah 1 (Audit Regulasi): Harmonisasi naskah Perda No. 1/2024 terhadap parameter komputasi retribusi PBG (indeks terintegrasi x luas x SHST) dan tabel tarif sewa alat berat (tarif per jam/hari/km). Kunci tabel tarif pada database berstatus read-only.

Langkah 2 (Normalisasi Data Aset): Inventarisasi 13+ unit alat berat PUPR (Excavator PC-200, Wheel Loader, Tandem Roller, Dump Truck, dll.), penetapan kode barang BMD, nomor polisi, dan status kelaikan operasi (Operasional / Rusak / Maintenance).

Langkah 3 (Pemisahan Kelembagaan & RBAC): Penetapan batas wewenang tegas antara Bapenda (Pengelola Kasda & Billing), DPMPTSP (Penerbit Izin PBG TTE), Admin Teknis PUPR (Pemeriksa Gambar & Pengkaji Teknis), dan Admin Khusus Aset PUPR (Pengelola Unit & Workshop).

Fase 1: MVP Layanan Inti & Integrasi Pembayaran Direct RKUD (Durasi: 3 Minggu)

Langkah 4 (Finansial & Payment Gateway): Penerbitan kode billing otomatis, Virtual Account SNAP Bank Sultra, dan QRIS Dinamis untuk retribusi PBG dan sewa alat berat. Penerapan 100% Direct RKUD tanpa penampungan perantara.

Penyediaan Layanan di M-PAD:

Citizen Portal (mpad.baubaukota.go.id): Modul permohonan sewa alat berat mandiri (katalog alat, cek ketersediaan, booking tanggal).

Inquiry & Pembayaran PBG Mandiri: Pemohon memasukkan nomor registrasi SIMBG -> sistem menampilkan rincian tagihan -> bayar via QRIS/VA -> terbit bukti e-SSRD.

Fase 2: Otomasi Interoperabilitas SIMBG & Ekosistem Mobile Petugas Aset (Durasi: 3 Minggu)

Langkah 5 (Rekayasa Smart Forms & Validasi Lapangan): Rilis modul aplikasi Petugas Mobile App (petugasmpad.baubaukota.go.id) untuk:

Petugas Lapangan/Operator: Checklist fisik pra-operasi dan pasca-operasi alat berat (hour meter, BBM, hidrolik, foto geotagged).

Surveyor Teknis PUPR: Geotagging batas sempadan bangunan dan inspeksi lapangan PBG.

Otomasi Callback SIMBG: Webhook real-time dari M-PAD ke SIMBG untuk mengubah status permohonan menjadi "Lunas Pembayaran Retribusi" seketika saat kas masuk ke Kasda Bank Sultra.

Penerbitan SIPAB Elektronik: Admin Aset PUPR menerbitkan Surat Izin Pemakaian Alat Berat ber-TTE BSrE setelah verifikasi pembayaran lunas.

Fase 3: Smart Spasial GIS, IoT Fleet Tracking, & Penatausahaan Terpadu (Durasi: 4 Minggu)

Langkah 6 (Continuous Auditing & Monitoring): Peta spasial GIS di Admin Panel untuk memantau sebaran pemanfaatan RUMIJA (kabel FO, pipa) dan lokasi kerja alat berat secara real-time via koordinat GPS.

Langkah 7 (Rekonsiliasi Akuntansi SIPD-RI): Sinkronisasi data realisasi pendapatan PUPR harian ke format jurnal standar SIPD-RI Kemendagri. Evaluasi berkala deviasi target vs realisasi retribusi PUPR.

BAB V. PROTOKOL KEAMANAN FINANSIAL & INTEGRITAS DATA

Zero Revenue Leakage: Pembayaran wajib retribusi disetorkan langsung ke RKUD Bank Sultra tanpa melewati rekening perantara (anti-escrow).

Enkripsi Kriptografis SNAP BI: Pertukaran data callback pembayaran diverifikasi menggunakan tanda tangan digital HMAC-SHA256. Notifikasi palsu ditolak seketika.

Idempotency & Concurrency Locking: Menjamin satu kode billing tidak dapat dibayar atau dicatat ganda melalui mekanisme database pessimistic locking (lockForUpdate) dan X-Idempotency-Key unik.

Immutable Audit Trail: Setiap aksi persetujuan alat berat, perubahan status unit, dan penetapan retribusi dicatat secara permanen pada tabel audit_logs database PostgreSQL dengan trigger anti-manipulasi.