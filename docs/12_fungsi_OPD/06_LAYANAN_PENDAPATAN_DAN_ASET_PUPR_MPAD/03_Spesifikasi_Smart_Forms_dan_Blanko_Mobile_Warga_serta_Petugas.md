KATALOG SPESIFIKASI SMART FORMS, SKEMA JSON DATA, DAN FORMULIR BLANKO PENDAFTARAN/PENDATAAN PADA MOBILE WARGA DAN PETUGAS (M-PAD V5)

METADATA DOKUMEN & KONTROL:

Nomor Registrasi: BAPENDA-PUPR/MPAD-V5/SMART-FORMS/2026/003

Aplikasi Terkait: Platform 3: Mobile Warga PWA (mpad.baubaukota.go.id) & Platform 4: Petugas Mobile App (petugasmpad.baubaukota.go.id)

Prinsip Desain: Declarative JSON Schema, Zero-Typing via IKD/GPS, Double Validation, Auto-Calculation

BAB I. PRINSIP FUNDAMENTAL SMART FORMS M-PAD V5

Seluruh formulir layanan pendapatan PUPR dibangun di atas arsitektur Smart Forms dengan lima prinsip teknis:

Single Source of Truth: Identitas pemohon divalidasi otomatis via NIK Disdukcapil/IKD; data objek lokasi divalidasi via GIS MapPicker.

Kalkulasi Komputasi Otomatis (Computed Fields): Pengguna tidak menginput tarif secara manual. Total tagihan dihitung secara otomatis oleh sistem di sisi klien dan divalidasi ulang di backend Laravel.

Dynamic JSON Schema: Struktur formulir disimpan dalam tabel asset_form_templates sehingga penambahan atribut data teknis baru dapat dilakukan tanpa proses kompilasi ulang (re-deploy) aplikasi mobile.

Geofencing & Geotagged Watermarking: Pengambilan foto fisik di aplikasi petugas wajib merekam koordinat GPS, arah mata angin, dan stempel waktu anti-pemalsuan.

Offline Capability & Synchronization: Formulir inspeksi petugas lapangan dapat diisi dalam kondisi luar jaringan (offline mode) dan disinkronkan otomatis saat perangkat mendeteksi koneksi internet.

BAB II. BLANKO 1: FORMULIR PENDAFTARAN & PEMINJAMAN ALAT BERAT PUPR (MOBILE WARGA)

Akses Pengguna: Wajib Retribusi / Kontraktor melalui Mobile Warga PWA (/layanan/pupr/sewa-alat).

Bagian Formulir

Field Key

Tipe Data / Komponen UI

Sumber Data & Validasi

Keterangan Bisnis

1. Identitas Pemohon

nik_pemohon

Text (16 Digit)

Auto-fill dari sesi akun IKD

Identitas penanggung jawab pemohon.

1. Identitas Pemohon

nama_pemohon

Text Read-Only

Auto-fill SIAK Dukcapil

Nama lengkap sesuai KTP.

1. Identitas Pemohon

kategori_pemohon

Dropdown Select

Pilihan: Perorangan, Badan Usaha (PT/CV), Instansi Pemerintah

Menentukan kelengkapan dokumen legalitas.

1. Identitas Pemohon

no_whatsapp

Tel (+62)

Validasi Regex Nomor WA Aktif

Kanal pengiriman kode billing & e-SIPAB.

2. Unit & Spesifikasi Sewa

unit_id

Dropdown Unit Selector

PULL dari Master Data Unit Aset Aktif

Pilihan unit (Excavator PC-200, Tandem Roller, dll). Menampilkan status siap operasi.

2. Unit & Spesifikasi Sewa

satuan_sewa

Radio Button

Per Jam / Per Hari / Per Bulan

Sesuai lampiran tarif Perda 1/2024.

2. Unit & Spesifikasi Sewa

tanggal_mulai

Date Picker

Minimal H+2 dari hari pendaftaran

Memberi waktu inspeksi buffer H-1 oleh dinas.

2. Unit & Spesifikasi Sewa

durasi_sewa

Number Input (Min: 1)

Integer (Jam/Hari)

Durasi riil rencana pemakaian.

3. Lokasi & Pekerjaan Proyek

lokasi_proyek

MapPicker GIS (Titik & Poligon)

Koordinat Lat/Long + Reverse Geocoding

Lokasi pekerjaan fisik proyek.

3. Lokasi & Pekerjaan Proyek

jenis_pekerjaan

Select / Text

Pematangan Lahan, Drainase, Pengaspalan, Galian Tanah

Kesesuaian fungsi alat berat.

3. Lokasi & Pekerjaan Proyek

include_mobilisasi

Checkbox Toggle

Boolean (Ya/Tidak)

Apakah armada tronton mobilisasi disediakan oleh PUPR.

4. Lampiran & Pernyataan

berkas_permohonan

File Upload (PDF/JPG < 5MB)

Wajib: Surat Permohonan Resmi / SPK Proyek

Legalitas kegiatan di lapangan.

4. Lampiran & Pernyataan

surat_pernyataan_ganti_rugi

Checkbox Persetujuan

Wajib Dicentang

Pernyataan bersedia mengganti kerusakan akibat kelalaian operasi di luar SOP.

4. Lampiran & Pernyataan

total_retribusi_estimasi

Computed Currency (Rp)

Formula: durasi * tarif_satuan + biaya_mob_demob

Nilai billing yang akan diterbitkan setelah disetujui Tahap 1.

Skema JSON Schema Form Peminjaman Alat Berat

{  "$schema": "https://json-schema.org/draft/2020-12/schema",  "title": "FormSewaAlatBeratPUPR",  "type": "object",  "required": ["unit_id", "tanggal_mulai", "durasi_sewa", "lokasi_lat", "lokasi_lng", "pernyataan_kesanggupan"],  "properties": {    "unit_id": { "type": "string", "format": "cuid" },    "tanggal_mulai": { "type": "string", "format": "date" },    "durasi_sewa": { "type": "integer", "minimum": 1 },    "satuan_sewa": { "type": "string", "enum": ["JAM", "HARI", "BULAN"] },    "lokasi_lat": { "type": "number" },    "lokasi_lng": { "type": "number" },    "alamat_proyek": { "type": "string", "minLength": 5 },    "pernyataan_kesanggupan": { "type": "boolean", "const": true }  }}

BAB III. BLANKO 2: FORMULIR PEMERIKSAAN FISIK ALAT BERAT PRA & PASCA OPERASI (PETUGAS MOBILE APP)

Akses Pengguna: Petugas UPTD Peralatan / Operator Lapangan melalui Petugas Mobile App (petugasmpad.baubaukota.go.id).

Tahap Pemeriksaan

Parameter Checklist Fisik

Metode Pengukuran / Opsi Input

Ambang Batas & Ketentuan

Tahap A: Serah Terima Awal (Pra-Operasi di Pool/Proyek)

Hour Meter (HM) / Odometer Awal

Number Input + Foto Angka HM Geotagged

Angka meteran acuan pemakaian awal.

Tahap A: Serah Terima Awal (Pra-Operasi di Pool/Proyek)

Sistem Fluida & Mesin

Checkbox: Level Oli Mesin, Oli Hidrolik, Air Radiator, Kondisi Aki

Wajib seluruhnya status "NORMAL / AMAN".

Tahap A: Serah Terima Awal (Pra-Operasi di Pool/Proyek)

Kondisi Fisik & Attachment

Checklist: Bucket / Blade / Drum Roller / Track Shoe / Ban

Catat goresan/keausan awal pada kolom catatan.

Tahap A: Serah Terima Awal (Pra-Operasi di Pool/Proyek)

Tanda Tangan Digital Pihak Penyewa

E-Signature Canvas di Layar Smartphone

Menyatakan unit diterima dalam kondisi laik jalan.

Tahap B: Pengembalian Unit (Pasca-Operasi)

Hour Meter (HM) / Odometer Akhir

Number Input + Foto Angka HM Geotagged

Selisih HM dihitung otomatis terhadap kuota sewa.

Tahap B: Pengembalian Unit (Pasca-Operasi)

Pemeriksaan Keausan & Kerusakan

Toggle: Normal / Terdapat Kerusakan Akibat Kelalaian

Jika rusak: Wajib ambil foto kerusakan + deskripsi.

Tahap B: Pengembalian Unit (Pasca-Operasi)

Kelebihan Jam Pemakaian (Overtime)

Computed: Max(0, HM_Akhir - (HM_Awal + Durasi_Sewa))

Jika > 0, sistem otomatis menerbitkan SKRD Denda Jam Operasi.

Tahap B: Pengembalian Unit (Pasca-Operasi)

Pengesahan Serah Terima Kembali

E-Signature Operator PUPR & Penyewa

Menyelesaikan siklus sewa aset pada database.

BAB IV. BLANKO 3: FORMULIR PENDAFTARAN PENGUJIAN LABORATORIUM KONSTRUKSI (MOBILE & PETUGAS)

Objek Layanan: UPTD Laboratorium Bahan Konstruksi Dinas PUPR Kota Baubau.

Identitas Proyek: Nama Proyek / Paket Pekerjaan, Nama Kontraktor/Konsultan, Lokasi Kegiatan.

Spesifikasi Benda Uji:

Uji Kuat Tekan Beton: Tipe Cetakan (Kubus 15x15 cm / Silinder 15x30 cm), Mutu Rencana (K-175, K-250, K-300, Fc 20, Fc 25), Tanggal Pengecoran, Umur Uji Rencana (7 hari, 14 hari, 28 hari), Jumlah Sampel.

Uji Tarik & Lengkung Baja Tulangan: Diameter Tulangan (Polos/Ulir: D8, D10, D12, D16, dst), Jumlah Benda Uji.

Uji Lapangan Sondir / DCP / Triaxial: Titik Pengujian Geospasial, Kedalaman Titik Uji.

Output Komputasi Retribusi: Jumlah Sampel * Tarif Uji Per Benda Uji (sesuai Perda 1/2024) -> Terbit Kode Billing M-PAD.

Output Hasil Uji: Berita Acara Pengujian dan Laporan Hasil Uji (LHU) digital ber-QR TTE Kepala UPTD Lab.

BAB V. BLANKO 4: FORMULIR PEMANFAATAN RUANG MILIK JALAN (RUMIJA) & JARINGAN UTILITAS

Objek Layanan: Penempatan Infrastruktur Telekomunikasi, Pipa, dan Jembatan Utilitas.

Nama Perusahaan / Vendor Utilitas: PT Provider Telekomunikasi / PDAM / BUMD.

Spesifikasi Pemanfaatan:

Ruas Jalan Kota (Pilih dari Master Data Ruas Jalan PUPR).

Jenis Fasilitas: Kabel Fiber Optik (FO Underground / FO Udara), Pipa Air Tanah, Tiang Microcell, Jembatan Utilitas.

Dimensi Penggunaan: Panjang Bentangan (Meter) atau Luas Tapak Tiang (m²).

Durasi Pemanfaatan (Tahun): Sesuai izin rekomendasi teknis Bina Marga.

Validasi Petugas Lapangan (Petugas Mobile App):

Survei kesesuaian trase dengan Rencana Tata Ruang Wilayah (RTRW).

Foto lokasi galian/penempatan tiang ber-geotagging.

Rekomendasi teknis kedalaman minimal galian (minimal 1.5 meter di bawah permukaan jalan).