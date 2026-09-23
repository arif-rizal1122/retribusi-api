# BUSINESS REQUIREMENTS DOCUMENT (BRD)
## Tata Kelola Mutasi PBB-P2, 9 Skenario Pelayanan, & Matriks Verifikasi 3 Tingkat Bapenda Kota Baubau

---

**Nomor Dokumen:** BRD-MPAD-PBB-002  
**Versi:** 1.0.0  
**Tanggal:** 23 September 2026  
**Penyusun:** Tim Arsitektur & Analis Sistem M-PAD (Bapenda Kota Baubau)  
**Status:** Disetujui untuk Implementasi & Rujukan Sistem  
**Target Modul:** Layanan PBB-P2, Mutasi Objek, Pemecahan Kavling, & RBAC Multi-Tier  

---

## 1. Latar Belakang & Urgensi Bisnis

Pajak Bumi dan Bangunan Perdesaan dan Perkotaan (PBB-P2) merupakan salah satu pilar utama Pendapatan Asli Daerah (PAD) Kota Baubau berdasarkan **Undang-Undang Nomor 1 Tahun 2022 (UU HKPD)** dan **Peraturan Daerah Kota Baubau Nomor 1 Tahun 2024**.

Dalam tata kelola pelayanan fiskal sebelumnya, proses pelayanan mutasi, pembetulan, pemecahan, dan pendaftaran objek pajak baru kerap menghadapi beberapa hambatan operasional:
1. **Risiko Moral Hazard & Piutang Tak Tertagih:** Pada pemecahan tanah kavlingan oleh pengembang, permohonan sering terhambat karena tunggakan NOP Induk belum lunas seutuhnya, atau sebaliknya diterbitkan tanpa perhitungan pembagian piutang yang proporsional.
2. **Ketiadaan Bangunan Legalitas PBG:** Banyak objek tanah memiliki bangunan fisik riil tetapi pemilik belum memiliki Persetujuan Bangunan Gedung (PBG/IMB). Akibatnya, potensi pajak bangunan sering tidak tertagih karena kekhawatiran SPPT disalahgunakan sebagai bukti legalitas fisik/bangunan.
3. **Pemisahan Kewenangan (Segregation of Duties) yang Belum Terstandardisasi:** Perlunya hierarki verifikasi yang jelas antara staf operasional, pejabat pengawas teknis, dan pimpinan struktural berwenang.

Oleh karena itu, platform M-PAD mengimplementasikan **Matriks Verifikasi Berjenjang 3 Tingkat** yang melibatkan aparatur Bapenda:
- **Level 1 (Staf Operasional / Admin Layanan):** Ibu Yani
- **Level 2 (Kepala Seksi / Kasubid Pendataan, Pendaftaran, dan Penetapan):** Ibu Salma
- **Level 3 (Kepala Bidang Pajak Daerah / Pendapatan):** Ibu Ochi
- **Pelaksana Teknis Faktual:** Surveyor Lapangan (`retribusi-petugas`)

---

## 2. Sasaran & Tujuan Bisnis (Business Objectives)

1. **Digitalisasi Penuh 9 Skenario Pelayanan PBB-P2:**
   Mengakomodasi seluruh dinamika lapangan wajib pajak, mulai dari pecah kavling, bangunan eksisting tanpa PBG, objek baru mandiri/proxy, pembetulan (rectification), balik nama (mutasi penuh), penggabungan (amalgamation), pembatalan/NOP ganda, pengurangan/keringanan fiskal, hingga keberatan NJOP.
2. **Kebijakan Afirmatif Piutang Proporsional Kavlingan:**
   Memberikan keadilan fiskal bagi pengembang dan pembeli kavling dengan membagi beban piutang NOP induk secara proporsional sesuai perbandingan luas meter persegi:
   $$\text{Piutang Pecahan} = \left( \frac{\text{Luas Kavling Pecahan}}{\text{Luas NOP Induk}} \right) \times \text{Total Piutang NOP Induk}$$
3. **Penegakan Asas Kebenaran Materiil (*Substance over Form*):**
   Setiap bangunan fisik yang berdiri nyata di wilayah Kota Baubau wajib dikenakan NJOP Bangunan untuk mengamankan PAD, dengan menyertakan klausul *legal disclaimer* resmi pada e-SPPT bahwa SPPT adalah instrumen perpajakan dan bukan bukti kepemilikan hak atas tanah/izin bangunan.
4. **Akuntabilitas & Audit Trail Melalui Verifikasi 3 Tingkat:**
   Mencegah penerbitan NOP atau koreksi NJOP sepihak dengan mensyaratkan persetujuan berjenjang: Validasi Berkas (Level 1) $\rightarrow$ Pre-Approve Teknis & Disposisi Survei (Level 2) $\rightarrow$ Otorisasi SK Definitif Ber-TTE (Level 3).

---

## 3. Matriks Hierarki Tata Kelola Verifikasi 3 Tingkat

| Tingkat | Pejabat / Aktor Pengampu | Nomenklatur Jabatan | Wewenang & Tanggung Jawab Kunci |
|---|---|---|---|
| **Level 1** | **Verifikator / Staf Pelayanan** | Staf Admin Operasional Layanan Pajak Daerah | • Penerimaan awal permohonan digital dari warga/PPAT.<br>• Verifikasi kelengkapan berkas administratif (KTP, Sertipikat, Bukti Bayar).<br>• Gatekeeper status bebas tunggakan / piutang NOP induk.<br>• Validasi batas spasial awal pada modul OpenLayers GIS.<br>• Output status: `VERIFIED_L1`. |
| **Level 2** | **Kasubid Pendataan** | Kepala Seksi / Kasubid Pendataan, Pendaftaran, dan Penetapan | • Verifikasi substantif teknis dan analisis potensi fiskal.<br>• Evaluasi kelayakan Zona Nilai Tanah (ZNT) dan Daftar Biaya Komponen Bangunan (DBKB).<br>• Penerbitan surat tugas survei lapangan dan disposisi surveyor.<br>• Review & validasi hasil survei lapangan, foto fisik, dan formulir LSPOP.<br>• Verifikasi akurasi kalkulasi piutang proporsional pecahan.<br>• Output status: `PRE_APPROVED_L2` atau `SURVEY`. |
| **Level 3** | **Kabid Pajak Daerah** | Kepala Bidang Pajak Daerah / Pendapatan | • Otoritas penetapan kebijakan fiskal tertinggi operasional PBB-P2.<br>• Penandatanganan elektronik (TTE) SK Mutasi, SK Pembetulan, dan SK Pembatalan NOP.<br>• Pengesahan penerbitan e-SPPT PBB-P2 definitif ke database SIMPBB.<br>• Supervisi pencatatan penerimaan Kas Daerah.<br>• Output status: `APPROVED` (Definitif) atau `REJECTED`. |
| **Petugas Lapangan** | **Surveyor PBB** | Petugas Lapangan Bapenda (`retribusi-petugas`) | • Pelaksanaan uji petik fisik / survei lokasi.<br>• Pengambilan geotagging titik koordinat riil dan batas poligon persil.<br>• Input data teknis konstruksi bangunan (formulir LSPOP).<br>• Upload foto fisik dan submit Berita Acara Survei Lapangan (BASL). |

---

## 4. Cakupan Kebutuhan Fungsional Bisnis (9 Skenario Layanan)

### FR-PBB-01: Skenario 1 - Pemecahan Kavlingan & Konservasi Luas
- Sistem menghitung secara otomatis piutang proporsional tiap kavling pecahan.
- Menerapkan rumus konservasi luas: Luas Induk Awal = Luas Sisa Induk + $\sum$ Luas Pecahan Baru. Sistem menolak permohonan jika $\sum$ Luas Pecahan $\ge$ Luas Induk Awal.
- Menerbitkan NOP pecahan baru dan memperbarui NOP induk setelah verifikasi L1, L2, dan disahkan L3.

### FR-PBB-02: Skenario 2 - Bangunan Eksisting Tanpa PBG
- Wajib pajak tetap dapat mendaftarkan bangunan fisik tanpa PBG.
- Pengisian spesifikasi bangunan pada LSPOP oleh Petugas Lapangan/Kasubid Pendataan untuk kalkulasi NJOP Bangunan berbasis DBKB.
- Sistem membubuhkan *disclaimer note* otomatis pada lembar e-SPPT yang dihasilkan:
  > *"SPPT PBB-P2 adalah surat pemberitahuan pajak terutang dan BUKAN merupakan bukti kepemilikan hak atas tanah serta BUKAN merupakan izin kelayakan bangunan (PBG/SLF)."*

### FR-PBB-03: Skenario 3 - Pendaftaran Objek Baru (Mandiri & Proxy)
- Pendaftaran objek baru yang belum pernah terdata di SIMPBB.
- Mode Mandiri: Menggunakan akun IKD warga dengan penandaan poligon spasial.
- Mode Proxy: Memerlukan upload surat kuasa bermaterai dan KTP pemilik asli.
- Wajib melalui alur verifikasi berkas (L1), review teknis / survei (L2), dan penetapan SK NOP Baru (L3).

### FR-PBB-04: Skenario 4 - Pembetulan SPPT (Rectification)
- Sesuai Pasal 71 PP 35/2023 untuk koreksi salah tulis (Nama/NIK/Alamat) atau salah hitung (Luas/Kelas Bumi) tanpa pergantian subjek hak.
- Koreksi teknis diaudit oleh Verifikator (L1), diverifikasi parameternya oleh Kasubid Pendataan (L2), dan diterbitkan SK Pembetulan serta SPPT revisi oleh Kabid Pajak Daerah (L3).

### FR-PBB-05: Skenario 5 - Mutasi Penuh / Balik Nama
- Pengalihan kepemilikan secara menyeluruh (AJB, Hibah, Waris, Risalah Lelang).
- Terintegrasi otomatis dengan sistem e-BPHTB: pelunasan BPHTB dan penerbitan NTPD memicu pembuatan draf mutasi balik nama PBB secara otomatis.

### FR-PBB-06: Skenario 6 - Penggabungan Objek Pajak (Amalgamation)
- Menggabungkan dua atau lebih NOP berdampingan milik satu subjek pajak menjadi satu NOP Utama.
- NOP sekunder dinonaktifkan (`INACTIVE/MERGED`) dan total luas diakumulasikan ke NOP Utama.

### FR-PBB-07: Skenario 7 - Pembatalan Ketetapan & Objek Pajak Ganda
- Digunakan untuk penanganan NOP ganda (*overlapping*), objek musnah akibat bencana, atau tanah yang dialihkan menjadi fasilitas umum/milik negara.
- Memerlukan berita acara verifikasi spasial dari Kasubid Pendataan dan SK Pembatalan resmi dari Kabid Pajak Daerah.

### FR-PBB-08: Skenario 8 - Pengurangan & Keringanan Pajak
- Pemberian diskon ketetapan PBB-P2 bagi subjek pajak tertentu: Pensiunan, Veteran, Cagar Budaya, Lahan Pertanian Berkelanjutan, atau Korban Bencana Alam.
- Diskon persentase diverifikasi kriteria pendukungnya oleh Kasubid Pendataan dan disahkan oleh Kabid Pajak Daerah.

### FR-PBB-09: Skenario 9 - Keberatan Pajak / NJOP
- Wajib pajak mengajukan sanggahan terhadap penetapan NJOP hasil penilaian massal.
- Kasubid Pendataan melakukan analisis pembanding nilai pasar atau menugaskan tim penilaian ulang (*re-appraisal*).

---

## 5. Kebutuhan Akun & Otorisasi Pengguna Khusus (Institutional Account)

1. **Role Kasubid Pendataan (`kasubid_pendataan`):**
   - Nama Akun: **`Kasubid Pendataan`**
   - Email Resmi Sistem: **`kasubid_pendataan@mpad.online`**
   - Role Sistem: **`kasubid_pendataan`**
   - Hak akses eksklusif untuk tombol **Pre-Approve L2**, disposisi tugas survei ke petugas lapangan, serta telaah parameter ZNT/DBKB.
2. **Pembaruan Standar Penamaan Akun (Bukan Nama Orang Pribadi):**
   - Seluruh akun fungsional aparatur menggunakan format jabatan kelembagaan (Institutional Role-Based Accounts) untuk menjaga keberlanjutan mutasi/rotasi pejabat:
     - `kasubid_pendataan@mpad.online` (`Kasubid Pendataan`)
     - `kasubidpelayanan@mpad.online` (`Kasubid Pelayanan`)
     - `kabid@retribusi.id` (`Kabid Pengawas / Pajak Daerah`)
     - `kasubid@retribusi.id` (`Kasubid Pengawas`)
3. **Pemisahan Tegas dari Role Pengawas:**
   - Role pengawasan operasional lapangan (`kasubid_pengawas` / `kabid_pengawas`) dipisahkan secara struktural dari role penetapan & pendaftaran (`kasubid_pendataan`) guna menjamin fungsi *checks and balances* perpajakan daerah.
