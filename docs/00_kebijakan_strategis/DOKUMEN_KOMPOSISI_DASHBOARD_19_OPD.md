# Buku Induk: Komposisi Dashboard & Keterkaitan Pendapatan 19 OPD Kota Baubau
**Arsitektur Multi-Tenant & Segregation of Duties (SoD) Ekosistem M-PAD**
*Dokumen Resmi Pemetaan Peran, Komposisi Antarmuka, dan Alur Kontrol Fiskal Daerah*

---

## 1. Landasan Strategis & Arsitektur Dashboard Multi-OPD

Sistem M-PAD Kota Baubau mengadopsi prinsip **Satu Data Fiskal Terintegrasi** dengan pemisahan wewenang (*Segregation of Duties* / SoD) yang ketat. 

```
                               ┌─────────────────────────────────────────┐
                               │  BAPENDA (Revenue & Fiscal Controller)  │
                               │  - Arus Uang (Cashflow) & Rekening Kasda│
                               │  - Review E-SPTPD & Red Channel Anomaly │
                               │  - Penetapan SKPDKB & Settlement H2H    │
                               └────────────────────┬────────────────────┘
                                                    │
                ┌───────────────────────────────────┼───────────────────────────────────┐
                │                                   │                                   │
                ▼                                   ▼                                   ▼
   ┌─────────────────────────┐         ┌─────────────────────────┐         ┌─────────────────────────┐
   │ OPD PENGHASIL RETRIBUSI │         │  OPD PEMBINA & PERIZINAN│         │ OPD PENEGAK & DATA MAKRO│
   │ (Dishub, Disperindag,   │         │ (DPMPTSP, Pariwisata,   │         │ (Satpol PP, Dukcapil,   │
   │  DLH, Perikanan, PUPR,  │         │  Damkar, Diskominfo)    │         │  Dinsos, Bapperida,     │
   │  Perkim)                │         │                         │         │  Setda Aset, Setwan)    │
   │ - Operasional Layanan   │         │ - Profiling Objek Teknis│         │ - Eksekusi Penertiban   │
   │ - Kuota & Sarana Fisik  │         │ - Rekomendasi Syarat    │         │ - Validasi Identitas/NIK│
   │ - Billing Retribusi     │         │ - Sertifikasi Kelayakan │         │ - Perencanaan Makro     │
   └─────────────────────────┘         └─────────────────────────┘         └─────────────────────────┘
```

Setiap OPD memiliki akun admin resmi (`role: opd`) yang terisolasi secara aman menggunakan filter `opd_id` pada backend, serta dashboard sektoral yang disesuaikan dengan tupoksi masing-masing dinas.

---

## 2. Rincian Komposisi Dashboard & Keterkaitan Pendapatan per OPD

---

### KLASTER I: OTORITAS PENGENDALI PENDAPATAN DAERAH

#### 1. Badan Pendapatan Daerah (BAPENDA)
* **Akun Admin:** `admin.bapenda@baubaukota.go.id` (`opd_id: 5`)
* **Peran Sistem:** *Super Controller / Master Fiscal Authority*.
* **Keterkaitan dengan Pendapatan:**
  - Memegang kontrol 100% atas seluruh jenis Pajak Daerah (PBB-P2, BPHTB, Reklame, MBLB, Sarang Walet, PBJT Makanan/Minuman, Perhotelan, Hiburan, Parkir, Listrik, Air Tanah) dan konsolidasi seluruh Retribusi Daerah 18 OPD lainnya.
  - Otoritas tunggal rekonsiliasi kas daerah (Kasda) dengan Bank Persepsi (Bank Sultra).
* **Komposisi Dashboard & Widget:**
  1. **Executive Fiscal Overview:** Total Realisasi vs Target PAD tahun berjalan, rasio efektivitas pemungutan (*collection rate*), grafik tren bulanan makro.
  2. **Portal Review E-SPTPD & Red Channel:**
     - Widget *Anomaly Gauge* (Deteksi dini pelaporan omzet anjlok > 40% vs histori/uji petik).
     - Tabel verifikasi laporan omzet mandiri dengan tombol *Setujui Tagihan*, *Tandai Red Channel*, dan *Tugaskan Uji Petik*.
  3. **Peta Pajak Digital (GIS NIB-NOP):** Sebaran objek pajak, heatmap potensi kepatuhan, dan status lunas per kecamatan/kelurahan.
  4. **Modul Rekonsiliasi Perbankan (H2H Bank Sultra):** Log transaksi H2H, webhook callback monitor, dan alokasi *Split Payment / AFT*.
  5. **Modul Penindakan & SKPDKB:** Daftar piutang macet, penetapan denda/bunga keterlambatan, dan penerbitan Surat Tagihan Pajak Daerah (STPD).

---

### KLASTER II: OPD PENGHASIL RETRIBUSI LANGSUNG

#### 2. Dinas Perhubungan (DISHUB)
* **Akun Admin:** `admin.dishub@baubaukota.go.id` (`opd_id: 2`)
* **Peran Sistem:** Pengelola Operasional Layanan Parkir & Terminal.
* **Keterkaitan dengan Pendapatan:**
  - **Retribusi Pelayanan Parkir di Tepi Jalan Umum** (Perda 1/2024: Motor Rp2.000, Mobil Rp3.000 - Rp5.000).
  - **Retribusi Tempat Khusus Parkir** (Pelabuhan Murhum, Pantai Kamali, Pasar Wameo).
  - **Retribusi Terminal & Pengujian Kendaraan Bermotor (Uji KIR)**.
* **Komposisi Dashboard (`ParkirDishubDashboard.tsx`):**
  1. **Early Warning System (EWS) Parkir:** Deteksi anomali setoran juru parkir (Jukir) yang di bawah estimasi potensi titik parkir.
  2. **Jukir Live Monitoring:** Status keaktifan juru parkir per shift, transaksi QRIS Dinamis jukir, dan saldo Jukir Wallet.
  3. **Manajemen Titik Parkir Spasial:** Inventarisasi titik parkir aktif, kapasitas kendaraan per ruas jalan, dan zonasi parkir.
  4. **Grafik Realisasi Harian:** Perbandingan target harian retribusi parkir vs realisasi kas masuk secara real-time.

#### 3. Dinas Perindustrian dan Perdagangan (DISPERINDAG)
* **Akun Admin:** `admin.disperindag@baubaukota.go.id` (`opd_id: 3`)
* **Peran Sistem:** Pengelola Pasar Rakyat & Metrologi Legal.
* **Keterkaitan dengan Pendapatan:**
  - **Retribusi Pelayanan Pasar:** Karcis harian pedagang kaki lima/pelataran pasar.
  - **Retribusi Tempat Usaha Pasar:** Sewa bulanan/tahunan kios, los, dan ruko di Pasar Sentral Wameo, Pasar Karya Nugraha, dan Pasar Modern Ngkari-ngkari.
  - **Retribusi Tera / Tera Ulang:** Pengujian alat ukur, takar, timbang, dan perlengkapannya (UTTP) bagi pedagang komersial.
* **Komposisi Dashboard (`OpdServices.tsx` scoped to Disperindag):**
  1. **Matriks Kios & Los Pasar:** Status okupansi toko/kios (Terisi, Kosong, Menunggak Sewa).
  2. **Kolektivitas Karcis Harian Digital:** Rekapitulasi setoran karcis retribusi pasar via petugas pasar mobile.
  3. **Jadwal Tera Ulang Spasial:** Kalender agenda pengujian timbangan SPBU, pasar, dan toko emas.
  4. **Monitoring Penjual Air Isi Ulang (Depot Air):** Pendataan teknis izin usaha depot air minum untuk dasar pengenaan pajak air tanah/retribusi.

#### 4. Dinas Lingkungan Hidup (DLH)
* **Akun Admin:** `admin.dlh@baubaukota.go.id` (`opd_id: 4`)
* **Peran Sistem:** Pengelola Persampahan & Kebersihan Kota.
* **Keterkaitan dengan Pendapatan:**
  - **Retribusi Pelayanan Kebersihan / Persampahan:**
    - Rumah tangga reguler (tarif Rp5.000 - Rp15.000/bulan).
    - Komersial/Badan Usaha (Hotel, Restoran, Rumah Sakit, Pertokoan, Swalayan) berdasarkan volume m³ timbulan sampah.
  - **Retribusi Penyedotan Kakus / Tinja (IPLT)**.
* **Komposisi Dashboard:**
  1. **Volume Tracking Limbah Komersial:** Input dan verifikasi volume sampah bulanan hotel, restoran, dan swalayan.
  2. **Billing Retribusi Sampah Terpadu:** Sinkronisasi tagihan ke rekening pelanggan atau gabungan tagihan air PDAM/PBJT.
  3. **Status Rute Armada Truk Sampah:** Monitoring jadwal pengangkutan sampah per kelurahan/zona layanan.
  4. **Indikator Kepatuhan Pelanggan:** Daftar objek usaha yang belum menyelesaikan kewajiban retribusi kebersihan.

#### 5. Dinas Perikanan
* **Akun Admin:** `admin.dinas_perikanan@baubaukota.go.id` (`opd_id: 10`)
* **Peran Sistem:** Pengelola Tempat Pelelangan Ikan (TPI) & Sarana Produksi Nelayan.
* **Keterkaitan dengan Pendapatan:**
  - **Retribusi Tempat Pelelangan Ikan (TPI Wameo):** Persentase dari nilai transaksi lelang hasil laut.
  - **Retribusi Tambatan Perahu / Dermaga Pendaratan Ikan**.
  - **Retribusi Pengujian Mutu / Laboratorium Pembinaan Produk Perikanan**.
  - **Sewa Cold Storage / Pabrik Es Milik Pemkot**.
* **Komposisi Dashboard:**
  1. **Volume Lelang Ikan Harian:** Pencatatan tonase ikan masuk, nilai transaksi lelang, dan potensi retribusi jasa lelang (2% - 5%).
  2. **Okupansi Tambatan Perahu:** Data kapal nelayan yang bersandar di dermaga binaan perikanan.
  3. **Manajemen Kontrak Cold Storage:** Status kontrak sewa sarana pendingin oleh asosiasi pedagang ikan.

#### 6. Dinas Pekerjaan Umum dan Penataan Ruang (PUPR)
* **Akun Admin:** `admin.pupr@baubaukota.go.id` (`opd_id: 9`)
* **Peran Sistem:** Pengelola Alat Berat & Pengujian Konstruksi.
* **Keterkaitan dengan Pendapatan:**
  - **Retribusi Pemakaian Kekayaan Daerah (Sewa Alat Berat):** Sewa excavator, bulldozer, dump truck, roller/mesin gilas untuk proyek swasta/kontraktor.
  - **Retribusi Laboratorium Pengujian Konstruksi:** Uji tekan beton, uji kepadatan aspal, uji tanah.
  - **Retribusi Pemanfaatan Sempadan Jalan / Jaringan Utilitas**.
* **Komposisi Dashboard:**
  1. **Kalender Booking Alat Berat:** Jadwal penyewaan alat berat, status unit (Tersedia, Beroperasi di Proyek, Maintenance), serta durasi jam operasional (*hour-meter*).
  2. **Sertifikat Uji Laboratorium:** Daftar pengujian mutu beton/aspal pemohon dan otomatisasi penerbitan draf SKRD laboratorium.
  3. **Verifikasi Teknis Air Baku Komersial:** Koordinasi pendataan sumur air tanah dan mata air debit besar untuk pemungutan Pajak Air Tanah.

#### 7. Dinas Perumahan, Kawasan Permukiman dan Pertanahan (PERKIM)
* **Akun Admin:** `admin.perkim@baubaukota.go.id` (`opd_id: 11`)
* **Peran Sistem:** Pengelola Hunian Daerah (Rusunawa) & Tanah Fasos/Fasum.
* **Keterkaitan dengan Pendapatan:**
  - **Retribusi Sewa Rusunawa:** Tarif sewa unit hunian per bulan bagi warga masyarakat berpenghasilan rendah.
  - **Pemanfaatan Tanah Daerah untuk Permukiman**.
* **Komposisi Dashboard:**
  1. **Denah Okupansi Rusunawa:** Visualisasi lantai dan kamar rusunawa (Warna: Hijau=Terhuni Lunas, Kuning=Masa Tenggang, Merah=Menunggak).
  2. **Manajemen Kontrak Penghuni:** Masa berlaku perjanjian sewa, verifikasi syarat warga, dan riwayat setoran sewa bulanan.
  3. **Peta Tanah Pemda untuk Permukiman:** Inventarisasi bidang tanah fasum perumahan yang berpotensi dikerjasamakan.

---

### KLASTER III: OPD PERIZINAN, REGULASI & PEMBINAAN TEKNIS

#### 8. Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu (DPMPTSP)
* **Akun Admin:** `admin.dpmptsp@baubaukota.go.id` (`opd_id: 7`)
* **Peran Sistem:** Gerbang Perizinan Berusaha Terpadu (OSS-RBA).
* **Keterkaitan dengan Pendapatan:**
  - **Interlock NIB & Calon Wajib Pajak:** Pelaku usaha yang mengurus izin baru di OSS otomatis masuk ke antrean M-PAD untuk verifikasi NPWPD/NOP.
  - **Retribusi Persetujuan Bangunan Gedung (PBG):** Verifikasi berkas kelayakan teknis perizinan gedung sebelum terbit SKRD PBG oleh Bapenda.
  - **Izin Penyelenggaraan Reklame (IPR):** Pengesahan titik reklame dari aspek tata ruang kota dan keselamatan konstruksi.
* **Komposisi Dashboard (`PtspDashboard.tsx`):**
  1. **NIB Matching & Conflict Monitor:** Memetakan status NIB wajib pajak (Matched, Pending, Conflict, Unregistered).
  2. **Calon Wajib Pajak Pipeline:** Feed pendaftaran izin baru yang belum terdaftar sebagai Wajib Pajak Daerah.
  3. **Tracking Rekomendasi PBG & Reklame:** Status verifikasi dokumen izin bangunan dan rekomendasi zona pemasangan baliho/billboard.

#### 9. Dinas Pariwisata dan Kebudayaan
* **Akun Admin:** `admin.pariwisata@baubaukota.go.id` (`opd_id: 16`)
* **Peran Sistem:** Pembina Industri Wisata, Standardisasi & Event Kota.
* **Keterkaitan dengan Pendapatan:**
  - **Dasar Potensi PBJT Jasa Perhotelan:** Data jumlah kamar, occupancy rate, fasilitas banquet/meeting room.
  - **Dasar Potensi PBJT Jasa Hiburan & Kesenian:** Kapasitas room KTV/karaoke, jumlah meja bilyard, lounge/klub malam.
  - **Event & Keramaian Budaya:** Kalender event yang mendorong lonjakan perputaran uang makan-minum dan tiket wisata.
  - **Retribusi Tempat Rekreasi, Pariwisata & Olahraga Milik Pemkot**.
* **Komposisi Dashboard (`PariwisataDashboard.tsx`):**
  1. **Direktori Teknis Hotel & Akomodasi:** Klasifikasi hotel bintang/melati, jumlah kamar tersedia, status operasional, verifikasi sertifikat CHSE.
  2. **Direktori Sarana Hiburan & Rekreasi:** Data kapasitas teknis tempat hiburan malam, room karaoke keluarga, meja bilyard.
  3. **Kalender Agenda Event Kota:** Jadwal festival budaya, perkiraan jumlah pengunjung, dan pemetaan potensi titik keramaian.
  4. **Banner Penegasan Tupoksi:** Pengingat sistem bahwa wewenang dinas adalah legalitas sarana & pembinaan, tanpa fungsi mengelola rekening kasda.

#### 10. Dinas Pemadam Kebakaran dan Penyelamatan (DAMKAR)
* **Akun Admin:** `admin.damkar@baubaukota.go.id` (`opd_id: 13`)
* **Peran Sistem:** Verifikator Teknis Proteksi Kebakaran Gedung.
* **Keterkaitan dengan Pendapatan:**
  - Syarat mutlak penerbitan izin gedung komersial dan **Retribusi PBG** (UU 28/2002 & PP 16/2021).
  - Verifikasi ketersediaan APAR, sprinkler, hydrant, dan tangga darurat pada bangunan hotel, pabrik, mall, dan tempat hiburan.
* **Komposisi Dashboard:**
  1. **Verifikasi Teknis Proteksi Kebakaran (`tb_verifikasi_damkar`):** Daftar pengajuan gedung yang perlu inspeksi proteksi kebakaran.
  2. **Status Sertifikat Laik Fungsi (SLF) Kebakaran:** Riwayat penerbitan rekomendasi teknis keselamatan kebakaran.
  3. **Inspeksi Berkala Hydrant Usaha Komersial:** Checklist kelayakan instalasi pemadam pada objek-objek wajib pajak besar.

#### 11. Dinas Komunikasi dan Informatika (DISKOMINFO)
* **Akun Admin:** `admin.diskominfo@baubaukota.go.id` (`opd_id: 6`)
* **Peran Sistem:** Pengelola Infrastruktur SPBE, Jaringan & Menara Telekomunikasi.
* **Keterkaitan dengan Pendapatan:**
  - **Retribusi Pengendalian Menara Telekomunikasi:** Pengawasan fisik zonasi dan kelayakan operasional menara seluler (BTS) provider di wilayah Kota Baubau.
  - **Penyedia Infrastruktur Konektivitas Kasda & Keamanan Siber:** Menjamin uptime server M-PAD, jaringan FO Pemda, dan perlindungan data wajib pajak (CSIRT).
* **Komposisi Dashboard:**
  1. **GIS Menara Telekomunikasi (BTS):** Peta sebaran menara seluler per provider (Telkomsel, Indosat, XL, Smartfren) dan koordinat ketinggian.
  2. **Audit Kelayakan Operasional Menara:** Checklist kepatuhan teknis tahunan menara untuk dasar penagihan retribusi pengendalian menara.
  3. **System Health & Network Monitor:** Uptime server API, webhook latency ke Bank Sultra, dan integritas backup data basis pajak.

---

### KLASTER IV: OPD PENEGAKAN HUKUM & PENGAWASAN LAPANGAN

#### 12. Satuan Polisi Pamong Praja (SATPOL PP)
* **Akun Admin:** `admin.satpol_pp@baubaukota.go.id` (`opd_id: 14`)
* **Peran Sistem:** Penegak Peraturan Daerah & Eksekutor Ketertiban Lapangan.
* **Keterkaitan dengan Pendapatan:**
  - **Enforcement Notice & Penertiban Lapangan:** Eksekusi penyegelan tempat usaha yang menunggak pajak/retribusi setelah SP3 dari Bapenda.
  - **Penertiban Reklame Liar:** Pembongkaran baliho, spanduk, bando jalan yang tidak berizin atau belum memiliki stempel lunas pajak Bapenda.
  - **Pendampingan Tim Surveillance / Uji Petik:** Pengamanan fisik petugas Bapenda saat melakukan audit spot check di lokasi hiburan malam/restoran rawan gesekan.
* **Komposisi Dashboard (`Enforcement.tsx` & `PengawasDashboard.tsx`):**
  1. **Daftar Perintah Tugas Penertiban:** Feed otomatis Surat Tugas Penertiban dari Bapenda dan OPD Teknis.
  2. **Log Penempelan Stiker / Segel Peringatan:** Upload bukti foto penempelan stiker "Objek Pajak Ini Menunggak Pajak Daerah" beserta koordinat GPS.
  3. **Monitoring Reklame Non-Compliant:** Dashboard tracking masa berlaku reklame berizin vs reklame liar yang siap dieksekusi penurunannya.

---

### KLASTER V: OPD VALIDASI IDENTITAS & INTEGRASI DATA MAKRO

#### 13. Dinas Kependudukan dan Pencatatan Sipil (DUKCAPIL)
* **Akun Admin:** `admin.dukcapil@baubaukota.go.id` (`opd_id: 12`)
* **Peran Sistem:** Verifikator Keabsahan Identitas Warga (NIK).
* **Keterkaitan dengan Pendapatan:**
  - **Interlock NIK Anti-Fraud:** Memvalidasi bahwa setiap Wajib Pajak Orang Pribadi yang mendaftar atau memohon mutasi PBB/BPHTB adalah entitas nyata yang tercatat di database kependudukan nasional.
  - Mencegah manipulasi data pemohon keringanan pajak atau pendaftaran wajib pajak fiktif.
* **Komposisi Dashboard:**
  1. **NIK Validation Service Monitor:** Metrik jumlah query verifikasi NIK dari modul registrasi mandiri wajib pajak dan pendaftaran PPAT/Notaris.
  2. **Data Pemutakhiran Kematian & Ahli Waris:** Notifikasi data warga meninggal dunia untuk mempermudah pemutakhiran nama SPPT PBB-P2 ke ahli waris.

#### 14. Dinas Sosial dan Tenaga Kerja
* **Akun Admin:** `admin.dinas_sosial@baubaukota.go.id` (`opd_id: 15`)
* **Peran Sistem:** Verifikator Keringanan Pajak Sosial & Tenaga Kerja Asing.
* **Keterkaitan dengan Pendapatan:**
  - **Verifikasi DTKS untuk Keringanan PBB-P2:** Menjamin bahwa pemohon pengurangan/pembebasan PBB-P2 benar-benar terdaftar dalam Data Terpadu Kesejahteraan Sosial (DTKS) warga miskin/lansia sebatang kara.
  - **Retribusi Penggunaan Tenaga Kerja Asing (R-PTKA):** Dana Kompensasi Penggunaan Tenaga Kerja Asing (DKP-TKA) sebesar USD 100 per bulan per tenaga kerja asing yang beroperasi di wilayah Baubau.
* **Komposisi Dashboard:**
  1. **Verifikasi DTKS Pemohon Keringanan Pajak:** Tool pengecekan desil kemiskinan pemohon stimulus/amnesty pajak daerah.
  2. **Monitoring Izin TKA (DKP-TKA):** Jumlah TKA terdaftar di perusahaan lokal, masa berlaku RPTKA, dan penghitungan tagihan retribusi kompensasi TKA.

#### 15. Badan Kesatuan Bangsa dan Politik (KESBANGPOL)
* **Akun Admin:** `admin.kesbangpol@baubaukota.go.id` (`opd_id: 17`)
* **Peran Sistem:** Verifikator Legalitas Ormas, Yayasan & Kegiatan Publik.
* **Keterkaitan dengan Pendapatan:**
  - Verifikasi izin ormas/yayasan nirlaba yang mengajukan **pembebasan Pajak Hiburan / PBB untuk sarana sosial, keagamaan, dan amal** (non-komersial).
* **Komposisi Dashboard:**
  1. **Database Ormas & Yayasan Nirlaba Terdaftar:** Verifikasi status badan hukum lembaga yang memohon status bebas pajak.
  2. **Rekomendasi Kegiatan Non-Komersial:** Pengesahan bahwa suatu event festival/hiburan adalah murni sosial tanpa keuntungan komersial.

#### 16. Badan Penanggulangan Bencana Daerah (BPBD)
* **Akun Admin:** `admin.bpbd@baubaukota.go.id` (`opd_id: 18`)
* **Peran Sistem:** Pemetaan Spasial Bencana & Status Tanggap Darurat.
* **Keterkaitan dengan Pendapatan:**
  - **Penetapan Status Darurat untuk Keringanan Pajak:** Saat terjadi bencana alam (banjir, tanah longsor, abrasi pantai), rekomendasi BPBD menjadi dasar hukum SK Walikota untuk penundaan jatuh tempo atau pengurangan pokok PBB-P2 bagi warga terdampak.
* **Komposisi Dashboard:**
  1. **GIS Zona Rawan Bencana:** Overlay peta kerawanan bencana dengan peta objek PBB-P2 (ZNT/NJOP) untuk kajian penyesuaian nilai jual objek.
  2. **Log Insiden Bencana & Objek Terdampak:** Input data bidang tanah/bangunan rusak akibat bencana untuk permohonan keringanan pajak.

---

### KLASTER VI: LEMBAGA PERENCANAAN, ASET DAERAH & PENGAWAS KEBIJAKAN

#### 17. Badan Perencanaan Pembangunan dan Riset Daerah (BAPPERIDA)
* **Akun Admin:** `admin.bapperida@baubaukota.go.id` (`opd_id: 8`)
* **Peran Sistem:** Perencana Strategis Makro Fiskal & Proyeksi PAD 3–5 Tahun.
* **Keterkaitan dengan Pendapatan:**
  - Menyelaraskan target PAD dalam APBD/RPJMD dengan tren kapasitas fiskal riil yang terekam di M-PAD.
  - Perencanaan investasi daerah dan simulasi dampak kenaikan/penurunan tarif pajak terhadap pertumbuhan ekonomi kota.
* **Komposisi Dashboard (`Executive Macro View`):**
  1. **Proyeksi Pertumbuhan Fiskal:** Model regresi tren penerimaan pajak dan retribusi 3-5 tahun ke depan.
  2. **Rasio Kemandirian Fiskal Daerah:** Grafik kontribusi PAD terhadap total belanja APBD Kota Baubau.
  3. **Analisis Elastisitas Sektoral:** Kontribusi sektor pariwisata, perdagangan, dan maritim terhadap pendapatan asli daerah.

#### 18. Sekretariat Daerah Kota Baubau (SETDA) — Bagian Aset / BPKAD
* **Akun Admin:** `admin.setda@baubaukota.go.id` (`opd_id: 19`)
* **Peran Sistem:** Pengelola Inventaris Barang Milik Daerah (BMD) & Kontrak Pemanfaatan.
* **Keterkaitan dengan Pendapatan:**
  - **Retribusi Pemanfaatan Kekayaan Daerah:** Sewa gedung serbaguna, aula kantor, tanah kosong pemda, dan kerja sama pemanfaatan (KSP/BGS/BSG) dengan investor swasta.
* **Komposisi Dashboard (`AssetRentalsManagement.tsx`):**
  1. **Kartu Inventaris Barang (KIB) Digital:** Daftar aset tanah, bangunan, dan ruko milik Pemda yang berstatus idle (*siap disewakan*).
  2. **Monitoring Kontrak Sewa Aset:** Notifikasi tanggal jatuh tempo sewa, nilai kontrak, dan histori pembayaran investor pihak ketiga.
  3. **Peta Spasial Kekayaan Daerah:** Titik koordinat dan sertipikat tanah aset milik Pemkot Baubau.

#### 19. Sekretariat DPRD Kota Baubau (SETWAN)
* **Akun Admin:** `admin.setwan@baubaukota.go.id` (`opd_id: 20`)
* **Peran Sistem:** Pengawas Legislatif Pelaksanaan Perda Pajak & Retribusi.
* **Keterkaitan dengan Pendapatan:**
  - Pemantauan transparansi realisasi PAD oleh Komisi II DPRD Kota Baubau untuk keperluan Rapat Dengar Pendapat (RDP) dan penetapan APBD Perubahan.
* **Komposisi Dashboard (`Read-Only Legislative Overview`):**
  1. **Ringkasan Realisasi per Mata Anggaran:** Tabel perbandingan target vs realisasi APBD berbasis akun rekening SIPD.
  2. **Laporan Audit Akuntabilitas (BPK-Ready):** Data agregat pelaporan piutang pajak dan kepatuhan ETPD tanpa akses ke data rahasia pribadi warga.

---

## 3. Matriks Keterhubungan Antara Tupoksi OPD dengan Aliran Kas Bapenda

| OPD | Data Teknis yang Dihasilkan | Titik Sentuh Pendapatan | Pengendali Akhir Kas (Bapenda) |
| :--- | :--- | :--- | :--- |
| **Dishub** | Titik parkir, kuota jukir, barcode ruas jalan | Retribusi Parkir Tepi Jalan & Khusus | Rekonsiliasi setoran QRIS Jukir ke Kasda Bank Sultra |
| **Disperindag** | Nomor kios, blok pasar, status tera ulang | Retribusi Pelayanan Pasar & Kios | Rekapitulasi kas masuk harian pasar & audit piutang |
| **DLH** | Volume m³ limbah sampah hotel/resto | Retribusi Pelayanan Persampahan | Penerbitan SKRD sampah terintegrasi PBJT |
| **Pariwisata** | Jumlah kamar, kapasitas room KTV, event | PBJT Hotel, Hiburan, Restoran | Deteksi anomali omzet (Red Channel) vs kapasitas |
| **DPMPTSP** | NIB baru, lokasi billboard, dokumen PBG | Pendaftaran WP Baru, Pajak Reklame, Retribusi PBG | Penetapan SKPD Reklame & penetapan tarif PBG |
| **PUPR** | Jam operasional excavator, uji beton | Retribusi Sewa Alat Berat & Lab | Penerbitan kode billing sewa alat berat |
| **Perikanan** | Tonase ikan lelang, sewa cold storage | Retribusi TPI & Jasa Pelabuhan Perikanan | Rekonsiliasi kasda hasil lelang ikan TPI Wameo |
| **Perkim** | Unit terhuni rusunawa, status tunggakan | Retribusi Sewa Rusunawa | Kunci kode bayar rusunawa & audit piutang sewa |
| **Damkar** | Sertifikat inspeksi sistem hydrant | Syarat Teknis Retribusi PBG | Validasi kelayakan sebelum penerbitan SKRD PBG |
| **Satpol PP** | Berita Acara Penyegelan / Penertiban | Pemulihan Piutang Pajak Macet | Pencabutan status tunggakan setelah pelunasan kasda |
| **Setda Aset** | Draf kontrak sewa gedung & tanah pemda | Retribusi Pemanfaatan Aset Daerah | Penguncian settlement pembayaran sewa aset Pemda |

---

## 4. Kesimpulan & Nilai Tambah Arsitektur Ini

1. **Anti Tumpang-Tindih:** OPD Teknis tidak lagi dibebani fungsi pembukuan keuangan atau penagihan uang tunai yang rawan kebocoran kas (*leakage*).
2. **Kekuatan Pembinaan:** Dinas teknis (seperti Pariwisata dan Perhubungan) dapat fokus meningkatkan mutu layanan, keselamatan sarana, dan pembinaan pelaku usaha.
3. **Sentralisasi Kontrol Fiskal:** Bapenda bertindak sebagai *single source of truth* untuk seluruh aliran dana pendapatan daerah Kota Baubau, dengan transparansi penuh hingga ke level transaksi perbankan Host-to-Host Bank Sultra.
