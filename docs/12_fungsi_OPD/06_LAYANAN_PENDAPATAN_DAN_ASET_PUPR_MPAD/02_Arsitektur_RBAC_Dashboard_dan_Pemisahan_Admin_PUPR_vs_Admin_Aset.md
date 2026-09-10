TATA KELOLA RBAC, PEMISAHAN WEWENANG, DAN SPESIFIKASI DASHBOARD ADMIN TEKNIS PUPR VS ADMIN KHUSUS ASET PUPR (M-PAD V5)

METADATA DOKUMEN & KONTROL:

Nomor Registrasi: BAPENDA-PUPR/MPAD-V5/RBAC-DASHBOARD/2026/002

Sistem: M-PAD V5.4.0 (Platform 2: Web Admin Panel - adminmpad.baubaukota.go.id)

Fokus: Pemisahan Kewenangan (Separation of Duties), Matriks RBAC, dan Desain Dashboard Layanan PUPR

BAB I. PRINSIP PEMISAHAN TUGAS (SEPARATION OF DUTIES) INTERNAL DINAS PUPR

Dalam tata kelola konvensional, pengelolaan izin teknis dan pengelolaan peralatan/aset sering bercampur pada level staf, yang berpotensi memicu konflik kepentingan, kelambatan verifikasi, dan kekaburan tanggung jawab operasional. M-PAD V5 menegakkan pemisahan mutlak (strict demarcation) antara Admin Teknis Layanan PUPR dan Admin Khusus Aset & Peralatan PUPR.

Dimensi

Admin Layanan Teknis PUPR

Admin Khusus Aset & Peralatan PUPR

Unit Penanggung Jawab

Bidang Cipta Karya, Bidang Bina Marga, Bidang Tata Ruang

UPTD Peralatan & Workshop (Pool Alat Berat / Subbagian Aset BMD PUPR)

Core Focus Operasional

Pemeriksaan kepatuhan standar teknis bangunan gedung (PBG/SLF), pengujian sampel konstruksi lab, dan izin ruang jalan (RUMIJA).

Kelaikan unit mesin/alat berat, kesiapan operator, pemeliharaan berkala, buffer jadwal pemakaian, keselamatan kerja, dan integritas fisik BMD.

Objek Transaksi

Persetujuan Teknis, Indeks Terintegrasi, Rekomendasi Teknis, Sertifikat Uji Lab.

Unit Armada Alat Berat, Kalender Ketersediaan, Surat Izin Pemakaian Alat (SIPAB), BAST, Denda Fisik/Kerusakan.

Integrasi Eksternal

H2H ke sistem SIMBG Kementerian PUPR & Portal DPMPTSP.

Sistem e-BMD/SIMDA Aset BPKAD, Database Kepegawaian Operator, Tracking GPS Alat.

BAB II. PROFIL & WEWENANG OPERASIONAL DUA ROLE ADMIN PUPR

2.1 Role 1: Admin Layanan Teknis PUPR (Role ID: ROLE_PUPR_TEKNIS)

Wewenang Utama:

Melakukan verifikasi berkas administratif dan teknis permohonan PBG yang masuk melalui integrasi SIMBG atau pendaftaran mandiri M-PAD.

Menginput hasil penilaian Tim Profesi Ahli (TPA) / Tim Penilai Teknis (TPT).

Mengoperasikan Kalkulator Teknis PBG di M-PAD (memasukkan Luas Lantai, Indeks Fungsi, Indeks Kompleksitas, Indeks Tingkat Permanensi, Indeks Ketinggian, dan Standar Harga Satuan Tertinggi / SHST).

Menerbitkan draft Surat Ketetapan Retribusi Daerah (SKRD) PBG untuk diotorisasi Kepala Dinas PUPR secara TTE.

Memvalidasi permohonan pengujian sampel laboratorium konstruksi dan menginput hasil analisis uji tekan/tarik.

Melakukan telaah teknis gambar trase penempatan kabel/pipa pada ruang milik jalan (RUMIJA).

Batasan Ketat: Dilarang mengubah tarif dasar Perda, dilarang mengakses kalender ketersediaan alat berat, dan dilarang menerbitkan izin pemakaian armada alat berat.

2.2 Role 2: Admin Khusus Aset & Peralatan PUPR (Role ID: ROLE_PUPR_ASET)

Wewenang Utama:

Mengelola Master Data Aset Alat Berat & Laboratorium (menambah unit baru, nomor inventaris BMD, status operasional: Siap Operasi, Terbatas, Rusak/Perbaikan, Dialihfungsikan).

Mengatur Kalender Sewa & Reservasi Armada (menerapkan aturan buffer H-1 untuk inspeksi pra-kirim dan H+1 untuk pembersihan/servis berkala).

Mengeksekusi Validasi Tahap 1: Meneliti ketersediaan unit, kesesuaian medan proyek pemohon, dan menugaskan Operator Alat resmi PUPR.

Memverifikasi notifikasi lunas pembayaran retribusi dari Kasda Bank Sultra.

Mengeksekusi Validasi Tahap 2: Menerbitkan Surat Izin Pemakaian Alat Berat (SIPAB) dan Berita Acara Serah Terima (BAST) ber-TTE BSrE.

Memantau laporan hasil checklist fisik harian pra & pasca operasi yang dikirim oleh Operator/Petugas Lapangan via Petugas Mobile App.

Menetapkan Surat Tagihan Retribusi Daerah (STRD) Denda apabila terjadi keterlambatan pengembalian unit atau kerusakan komponen akibat kelalaian penyewa.

Batasan Ketat: Dilarang memvalidasi permohonan PBG, dilarang mengubah rekening tujuan Kasda, dan dilarang mengubah formula dasar tarif sewa aset.

BAB III. MATRIKS KONTROL AKSES BERBASIS PERAN (RBAC MATRIX)

Fungsi / Modul Sistem

Admin Bapenda

Admin PTSP

Admin Teknis PUPR

Admin Aset PUPR

Petugas/Operator Mobile

Wajib Retribusi (Warga)

Konfigurasi Tarif Perda & Rekening Kasda

Penuh (CRUD)

Terkunci

Terkunci

Terkunci

Terkunci

Terkunci

Manajemen Master Unit Aset Alat Berat

Read-Only

Terkunci

Terkunci

Penuh (CRUD)

Terkunci

Terkunci

Verifikasi Dokumen Teknis & Sidang PBG

Terkunci

Terkunci

Penuh (CRUD)

Terkunci

Terkunci

Terkunci

Validasi Tahap 1 Sewa Aset & Buffer Jadwal

Terkunci

Terkunci

Terkunci

Penuh (CRUD)

Terkunci

Terkunci

Penerbitan Kode Billing (VA & QRIS)

Otomatis Core

Otomatis Core

Otomatis Core

Otomatis Core

Otomatis Core

Otomatis Core

Monitoring Status Pembayaran Lunas (Kasda)

Penuh (Real-time)

Read-Only

Read-Only

Read-Only

Read-Only

Read-Only

Penerbitan SIPAB & BAST Alat Berat (TTE)

Terkunci

Terkunci

Terkunci

Penuh (CRUD)

Terkunci

Terkunci

Penerbitan Izin Resmi PBG (TTE DPMPTSP)

Terkunci

Penuh (TTE)

Terkunci

Terkunci

Terkunci

Terkunci

Input Cek Fisik Lapangan & Hour Meter

Terkunci

Terkunci

Terkunci

Review/Approve

Penuh (Input Form)

Tanda Tangan Serah Terima

BAB IV. SPESIFIKASI DASHBOARD ADMIN PANEL (PLATFORM 2)

4.1 Dashboard Admin Layanan Teknis PUPR (/admin/pupr/teknis)

Top KPI Cards:

Total Permohonan PBG Aktif (Masuk / Sidang Teknis / Rekomtek Terbit).

SLA Kinerja Verifikasi Teknis (Rata-rata waktu proses: Target < 24 Jam).

Potensi Retribusi PBG Menunggu Pembayaran (Nominal Rp).

Antrean Sampel Uji Lab Bahan Konstruksi.

Main Panels & Data Tables:

Antrean Berkas SIMBG Terintegrasi: Menampilkan nomor registrasi, nama pemohon, fungsi gedung, luas total, dan status permohonan. Tombol aksi: "Buka Lembar Verifikasi Gambar", "Hitung Retribusi Teknis", "Terbitkan Rekomtek".

Monitoring Uji Lab Konstruksi: Daftar sampel beton/baja/tanah, tanggal uji tekan (7/14/28 hari), status input hasil uji, dan tombol penerbitan LHU.

Permohonan Izin RUMIJA: Pemetaan titik galian/bentangan kabel pada peta spasial jalan kota.

4.2 Dashboard Admin Khusus Aset & Peralatan PUPR (/admin/pupr/aset)

Top KPI Cards:

Tingkat Utilitas Armada (Occupancy Rate % bulan berjalan).

Status Kesiapan Armada (Unit Siap: X, Sedang Disewa: Y, Maintenance: Z).

Realisasi Penerimaan Sewa Alat Berat Direct RKUD (Nominal Rp vs Target Renstra).

Peringatan Servis Berkala (Unit mendekati batas jam kerja servis / 250 jam operasi).

Visual Fleet Management & Interactive Scheduler:

Gantt Chart Kalender Ketersediaan Unit: Garis waktu per unit alat berat dengan blok warna (Hijau = Siap Booking, Biru = Sedang Disewa, Kuning = Buffer H-1/H+1, Merah = Rusak/Maintenance).

Peta Spasial Posisi Alat (Live Fleet GPS): Menampilkan titik lokasi aktual alat berat yang sedang beroperasi di lapangan guna mencegah penyalahgunaan aset di luar lokasi SPK.

Tabel Pengajuan Sewa (Approval Queue):Tombol Aksi Tahap 1: "Verifikasi Kesiapan Unit & Tunjuk Operator" -> memicu terbitnya kode bayar ke pemohon.Tombol Aksi Tahap 2: "Verifikasi Pelunasan Kasda & Generate SIPAB TTE".

Monitoring Checklist Pengembalian & Denda: Notifikasi otomatis jika hour meter melebihi kuota sewa atau jika terdapat laporan kerusakan dari petugas mobile.