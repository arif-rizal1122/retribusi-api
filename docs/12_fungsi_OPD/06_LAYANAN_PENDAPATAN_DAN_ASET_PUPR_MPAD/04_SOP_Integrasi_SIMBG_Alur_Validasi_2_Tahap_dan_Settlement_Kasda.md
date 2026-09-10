STANDAR OPERASIONAL PROSEDUR (SOP) INTEGRASI SIMBG-MPAD, ALUR VALIDASI 2 TAHAP SEWA ASET PUPR, DAN SETTLEMENT KASDA

METADATA DOKUMEN & KONTROL:

Nomor Registrasi: BAPENDA-PUPR/MPAD-V5/SOP-SETTLEMENT/2026/004

Klasifikasi: Standar Operasional Prosedur Tetap (SOP Mandatory)

Pihak Terkait: Bapenda Baubau, Dinas PUPR (Bidang Teknis & UPTD Peralatan), DPMPTSP, Bank Sultra

SOP 1: INTEROPERABILITAS SISTEM SIMBG KEMENPUPR DENGAN SUPER APP M-PAD (LAYANAN PBG & SLF)

1. Deskripsi Alur & Swimlane

Registrasi & Konsultasi SIMBG: Pemohon mengajukan permohonan PBG melalui portal SIMBG (simbg.pu.go.id). Tim Ahli (TPA/TPT) Dinas PUPR Baubau melakukan pemeriksaan gambar teknis dan menerbitkan Berita Acara Rekomendasi Teknis.

Penetapan Retribusi Teknis: Admin Teknis PUPR menginput variabel indeks terintegrasi pada kalkulator PBG. Sistem SIMBG menetapkan Surat Ketetapan Retribusi Daerah (SKRD) PBG dengan status MENUNGGU_PEMBAYARAN.

Inquiry Tagihan (PULL API M-PAD): M-PAD Core Engine menyinkronkan data tagihan aktif dari SIMBG melalui Web Service resmi / SPLP:GET /api/v1/simbg/inquiry/{nomor_registrasi_pbg}

Penerbitan Multi-Kanal Pembayaran M-PAD: Wajib retribusi membuka aplikasi M-PAD Mobile / Web dan memilih pembayaran PBG. M-PAD menerbitkan Kode Bayar Virtual Account SNAP (Bank Sultra, BRI, Mandiri, BNI) serta QRIS Dinamis.

Pelunasan & Settlement Kasda: Wajib retribusi membayar melalui kanal perbankan. Dana langsung masuk 100% ke Rekening Kas Umum Daerah (RKUD) Kota Baubau di Bank Sultra. Server bank mengirimkan webhook pelunasan real-time ke M-PAD API Gateway.

Callback Pelunasan Otomatis (PUSH API ke SIMBG): Begitu dana terkonfirmasi di RKUD, M-PAD secara instan memicu webhook callback ke SIMBG:POST /api/v1/simbg/payment-callbackPayload: { "nomor_registrasi": "PBG-747201-...", "status": "LUNAS", "ntpd": "NTPD-202609...", "nomor_ssrd": "SSRD/PUPR/...", "nominal": 8750000, "tgl_bayar": "2026-09-10T10:15:30+08:00" }

Penerbitan Dokumen PBG Resmi di DPMPTSP: Sistem SIMBG secara otomatis mengubah status menjadi "Lunas", memungkinkan Kepala Dinas DPMPTSP membubuhkan Tanda Tangan Elektronik (TTE) sertifikasi BSrE pada dokumen izin PBG tanpa perlu verifikasi slip kertas manual.

Feedback Loop Pemutakhiran PBB-P2: Data luas bangunan dan fungsi bangunan yang telah diterbitkan PBG disinkronkan ke basis data objek PBB-P2 M-PAD untuk penyesuaian NJOP Bangunan tahun anggaran berikutnya.

SOP 2: ALUR VALIDASI 2 TAHAP SEWA ALAT BERAT & PEMANFAATAN ASET BMD PUPR

Tahap 1: Validasi Kesiapan Alat & Buffer Jadwal Operasional

Pendaftaran Sewa: Pemohon mengisi Smart Form Peminjaman Alat Berat di Mobile Warga M-PAD (unit, durasi, lokasi proyek, upload surat permohonan).

Telaah Kelaikan oleh Admin Aset PUPR:

Memeriksa status operasional unit di sistem (wajib status "Siap Operasi").

Memeriksa kalender booking: memastikan terdapat jeda waktu (buffer) minimal H-1 sebelum pemakaian untuk mobilisasi/pengecekan dan H+1 pasca sewa untuk pembersihan dan servis berkala.

Menunjuk Operator Alat Berat resmi bertugas dari daftar pegawai berlisensi (SIO).

Keputusan Tahap 1:

Jika Disetujui: Admin Aset PUPR mengklik tombol "Approve Kesiapan Alat". Sistem M-PAD Core menerbitkan Kode Billing Sewa Aset (VA SNAP Bank Sultra & QRIS) dengan masa berlaku 1x24 jam.

Jika Ditolak/Jadwal Bentrok: Admin Aset memberikan catatan penolakan atau opsi alternatif unit / tanggal sewa lain melalui notifikasi aplikasi.

Tahap 2: Pasca-Bayar, Penerbitan SIPAB TTE, & Eksekusi Lapangan

Verifikasi Pelunasan Kasda: Sistem menerima konfirmasi settlement dana sewa alat berat di RKUD Bank Sultra. Status pengajuan otomatis berubah menjadi LUNAS_MENUNGGU_DOKUMEN.

Penerbitan SIPAB & BAST Elektronik:

Admin Khusus Aset mengklik tombol "Terbitkan Surat Izin Pemakaian Alat Berat (SIPAB)".

Dokumen SIPAB dan Berita Acara Serah Terima (BAST) diterbitkan secara digital, dilengkapi stempel QR Code dan TTE pejabat berwenang Dinas PUPR.

Cek Fisik Serah Terima Awal: Operator Lapangan dan Penyewa melakukan inspeksi unit di Pool PUPR atau lokasi mobilisasi, mengisi Formulir Cek Fisik Pra-Operasi pada Petugas Mobile App (mencatat Hour Meter awal & foto kondisi fisik), serta menandatangani e-signature serah terima.

Operasional & Monitoring: Unit dioperasikan di lokasi proyek sesuai durasi izin. Peta GPS memantau pergerakan armada.

Pemeriksaan Pengembalian (Cek Fisik Akhir):

Operator Lapangan melakukan pencatatan Hour Meter akhir dan pemeriksaan fisik komponen unit.

Kondisi Baik & Tepat Waktu: Siklus sewa ditutup di sistem, status aset kembali "Siap Operasi" setelah jeda buffer servis H+1.

Kondisi Terlambat / Kerusakan Komponen: Sistem atau Admin Aset menerbitkan Surat Tagihan Retribusi Daerah (STRD) Denda Kompensasi Kerusakan / Biaya Lembur. Penyewa wajib melunasi denda melalui M-PAD.

SOP 3: REKONSILIASI PENERIMAAN KAS DAERAH DAN INTEGRASI PENATAUSAHAAN SIPD-RI

1. Kodefikasi Rekening Penerimaan PUPR (Bagan Akun Standar Kemendagri)

No

Jenis Penerimaan PUPR

Kode Rekening Pendapatan SIPD-RI

Uraian Akun Pendapatan

1

Retribusi PBG & SLF

4.1.02.01.01.0001

Retribusi Persetujuan Bangunan Gedung (Jasa Perizinan Tertentu)

2

Retribusi Sewa Alat Berat

4.1.02.02.01.0014

Retribusi Pemanfaatan Barang Milik Daerah - Sewa Alat Berat Konstruksi

3

Retribusi Uji Lab Konstruksi

4.1.02.02.01.0022

Retribusi Pelayanan Jasa Pengujian Laboratorium Bahan Bangunan

4

Pemanfaatan Ruang Milik Jalan

4.1.02.02.01.0035

Retribusi Pemanfaatan Bagian-Bagian Jalan (RUMIJA)

2. Mekanisme Rekonsiliasi Kasda H+0 & Pelaporan

Settlement Otomatis: Seluruh pembayaran via QRIS dan Virtual Account disalurkan secara langsung ke RKUD Bank Sultra (Rekening No: 010.01.05.xxxxxx).

Rekonsiliasi Harian (EOD Matching): Setiap pukul 23.59 WITA, modul scheduler M-PAD mencocokkan log mutasi kas Bank Sultra terhadap seluruh NTPD yang diterbitkan pada hari berjalan.

Ekspor Jurnal Pendapatan SIPD-RI: Admin Keuangan Bapenda dan Admin Dinas PUPR dapat mengunduh rekapitulasi Buku Kas Umum (BKU) Penerimaan harian dalam format baku CSV/XLSX yang siap diunggah ke portal SIPD-RI Penatausahaan Kemendagri.