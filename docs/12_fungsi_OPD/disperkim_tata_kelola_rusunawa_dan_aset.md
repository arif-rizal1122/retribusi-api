# Panduan Teknis & Dokumen Rujukan Tata Kelola Aset & Rusunawa Disperkim Kota Baubau
**Sistem M-PAD (Manajemen Pendapatan Asli Daerah) V5**  
**OPD ID:** 11 (Dinas Perumahan, Kawasan Permukiman dan Pertanahan Kota Baubau)  
**Agenda Acuan:** Rapat Pendalaman & Penyelarasan Form M-PAD (Pukul 14.00 WITA)  
**Dasar Penugasan:** Surat Sekretaris Daerah Kota Baubau No. 900.1.13.1/4745/SETDA (3 September 2026)  
**Rujukan Regulasi:** [Perda Kota Baubau No. 1 Tahun 2024](https://drive.google.com/file/d/1nVRrted30T9th42uXP0fuVH0FtO67_m5/view?usp=drivesdk) & UU HKPD No. 1/2022  
**Standar Arsitektur:** [Master Blueprint Terpadu M-PAD V5](https://docs.google.com/document/d/1s6xxg6Bzn7pg66bjcpolIDyQCIS7Bv7RFaVYTBqtlR8/edit?usp=drivesdk&ouid=110643869878550118873) & [Framework 7-Langkah Tata Kelola PAD](https://docs.google.com/document/d/14vFl2JG0WjLWdngxUWVQxcSS0IEubjj2L0fsCaOJwy0/edit?usp=drivesdk&ouid=110643869878550118873)

---

## I. Konteks Administratif & Status Akun Disperkim di Sistem M-PAD

1. **Dasar Penugasan**: Tindak lanjut resmi Surat Sekretaris Daerah Kota Baubau No. 900.1.13.1/4745/SETDA tertanggal 3 September 2026 perihal *Rapat Pendalaman & Penyelarasan Form M-PAD*.
2. **Identitas Akun OPD**: Berdasarkan *Lampiran Tupoksi & Akun Admin Disperkim*, Disperkim telah teregistrasi pada sistem M-PAD dengan spesifikasi:
   - **OPD ID:** `11`
   - **Nama Resmi:** Dinas Perumahan, Kawasan Permukiman dan Pertanahan (Disperkim)
   - **Kode Singkatan:** `PERKIM`
   - **Portal Akses Web:** [https://adminmpad.baubaukota.go.id](https://adminmpad.baubaukota.go.id)
   - **Username / Email Kedinasan:** `admin.perkim@baubaukota.go.id`
   - **User ID di Database:** `265` (Status: `active`, Role: `opd`)
3. **Status Sistem Saat Ini**: Akun berstatus aktif dengan **cakupan data terisolasi (Tenant Isolation)** melalui penguncian query backend:
   ```php
   $query->whereHas('assetItem', fn ($q) => $q->where('opd_id', $user->opd_id));
   ```
   Petugas verifikator Disperkim hanya dapat melihat permohonan sewa dan unit kamar di bawah naungan Disperkim (`opd_id: 11`), tanpa memiliki akses ke kasda umum maupun aset milik dinas lain.
4. **Tujuan Utama Pertemuan (14.00 WITA)**: Mengidentifikasi seluruh potensi retribusi Disperkim, mengunci formula tarif Perda, menetapkan demarkasi wewenang, dan menyepakati skema Smart Form sebelum aktivasi modul di production.

---

## II. Pemetaan Objek & Formula Tarif Retribusi Disperkim

Berdasarkan **Perda Kota Baubau No. 1 Tahun 2024** dan data **Matriks Tarif Pemanfaatan Aset**, berikut adalah rincian tarif sewa unit Rusunawa yang wajib disinkronkan ke dalam sistem:

### 1. Matriks Tarif Rusunawa Perda No. 1/2024
| Lokasi Rusunawa | Tingkat Lantai | Tarif Sewa Resmi (Per Bulan) | Status di Database M-PAD (`asset_items`) |
| :--- | :--- | :--- | :--- |
| **Rusunawa Mahasiswa** | Lantai I | Rp 350.000 | ✅ Tersedia (`RUSUN-MHS-I`) |
| | Lantai II | Rp 325.000 | ⚠️ Perlu Di-seed Tambahan |
| | Lantai III | Rp 300.000 | ⚠️ Perlu Di-seed Tambahan |
| | Lantai IV | Rp 275.000 | ✅ Tersedia (`RUSUN-MHS-IV`) |
| **Rusunawa Wameo** | Lantai I | Rp 200.000 | ✅ Tersedia (`RUSUN-WAMEO-I`) |
| | Lantai II | Rp 175.000 | ⚠️ Perlu Di-seed Tambahan |
| | Lantai III | Rp 150.000 | ⚠️ Perlu Di-seed Tambahan |
| | Lantai IV | Rp 125.000 | ⚠️ Perlu Di-seed Tambahan |
| **Rusunawa ASN** | Lantai I | Rp 350.000 | ✅ Tersedia (`RUSUN-ASN-I`) |
| | Lantai II | Rp 325.000 | ⚠️ Perlu Di-seed Tambahan |
| | Lantai III | Rp 300.000 | ⚠️ Perlu Di-seed Tambahan |
| | Lantai IV | Rp 275.000 | ⚠️ Perlu Di-seed Tambahan |
| **Rusunawa Kotamara** | Lantai I | Rp 250.000 | ⚠️ Perlu Di-seed Tambahan |
| | Lantai II | Rp 225.000 | ⚠️ Perlu Di-seed Tambahan |

### 2. Pemanfaatan Tanah / Lahan Milik Daerah (Non-Pelabuhan/Non-Pasar)
- Identifikasi apakah ada tanah/lahan pemda yang disewakan untuk perumahan, perkebunan, atau tempat tinggal sementara di bawah pengawasan bidang pertanahan Disperkim.

### 3. Konfirmasi Objek Layanan Lainnya
- **Penyerahan PSU Perumahan**: Prasarana, Sarana, dan Utilitas Umum perumahan dari pengembang (apakah terdapat retribusi administrasi/pemanfaatan).
- **Retribusi TPU / Pemakaman**: Penegasan batas kewenangan apakah retribusi pelayanan pemakaman dikelola Disperkim atau DLH/Dinas Sosial.

---

## III. Penegasan Demarkasi Yurisdiksi Fiskal (Prinsip Master Blueprint)

Sesuai Bagian 3 *Master Blueprint M-PAD V5*, disepakati batasan yurisdiksi tegas agar sistem terhindar dari kerumitan administratif (*feature creep*):

```
┌────────────────────────────────────────────────────────┐
│            CAKUPAN M-PAD (DOMAIN FISKAL)               │
├────────────────────────────────────────────────────────┤
│ • Pendaftaran subjek sewa berbasis NIK (Dukcapil)      │
│ • Validasi ketersediaan unit kamar (Available/Occupied)│
│ • Perhitungan otomatis tarif sewa & denda legal 1%/bln │
│ • Penerbitan e-SKRD & kode billing SNAP BI (QRIS/VA)   │
│ • Rekonsiliasi otomatis 100% Direct Settling Kasda     │
│ • Recurring e-Billing bulanan via WhatsApp Gateway     │
└────────────────────────────────────────────────────────┘
                           ▲
             TIDAK SALING MENGINTERVENSI
                           ▼
┌────────────────────────────────────────────────────────┐
│      KEWENANGAN INTERNAL DISPERKIM (DOMAIN TEKNIS)     │
├────────────────────────────────────────────────────────┤
│ • Seleksi kriteria sosial calon penghuni (MBR/DTKS)    │
│ • Penegakan tata tertib & disiplin operasional gedung  │
│ • Eksekusi pengosongan unit / pengusiran (eviction)    │
│ • Sertifikasi kelaikan konstruksi & uji fungsi fisik   │
│ • Sengketa yuridis batas tanah perdata                 │
└────────────────────────────────────────────────────────┘
```

---

## IV. Desain Formulir Cerdas Rusunawa (5 Prinsip Smart Forms)

1. **Pre-Filled & Auto-Pull Data**: Penarikan data profil pemohon otomatis berbasis identitas NIK warga yang terautentikasi di aplikasi mobile.
2. **Hierarchical Master Selector (Anti-GPS Pin Bebas)**:
   > Unit Rusunawa merupakan aset fisik permanen milik Pemda dengan lokasi yang sudah pasti. Calon penyewa **dilarang diminta menandai titik koordinat peta secara bebas**.
   
   Alur formulir wajib menggunakan selektor bertingkat:
   $$\text{Pilih Kompleks Rusun} \longrightarrow \text{Pilih Blok/Tower} \longrightarrow \text{Pilih Lantai} \longrightarrow \text{Pilih Nomor Unit Kamar}$$
3. **Dynamic Responsive Calculator**: Sistem otomatis mengalikan tarif lantai Perda dengan durasi sewa (bulan) secara instan, ditambah nominal deposit jaminan awal jika diatur dalam ketentuan.
4. **Validasi In-Line Real-Time**:
   - Sistem memeriksa status unit kamar: hanya kamar berstatus `Available` yang dapat dipilih.
   - Pengecekan riwayat NIK: Mencegah pengajuan jika pemohon masih memiliki tunggakan pada masa sewa sebelumnya.
5. **Skema Recurring Tagihan**: Tagihan sewa berjalan diterbitkan otomatis setiap tanggal 1 via **WhatsApp Notification Gateway**, memuat tautan pembayaran instan QRIS dan Virtual Account Bank Sultra (100% Direct Settling ke Kas Daerah).
6. **Denda Keterlambatan Otomatis**: Keterlambatan pembayaran setelah jatuh tempo dikenakan denda administratif 1% per bulan (maksimal 24 bulan) sesuai UU HKPD.

---

## V. Pembagian Peran pada 3 Platform M-PAD

| Platform M-PAD | Pengguna Utama | Fitur & Batasan Akses |
| :--- | :--- | :--- |
| **Mobile Warga (PWA)**<br>`mpad.baubaukota.go.id` | Warga / Mahasiswa / ASN Penghuni Rusun | • Pendaftaran sewa baru berbasis selektor unit bertingkat.<br>• Perpanjangan sewa rutin bulanan.<br>• Pembayaran instan SNAP BI QRIS / VA Bank Sultra.<br>• Unduh bukti bayar sah e-SSPD ber-TTE. |
| **Petugas Lapangan**<br>`petugasmpad.baubaukota.go.id` | Pengelola Rusun / UPTD Lapangan | • Verifikasi fisik kamar dan serah terima kunci.<br>• Pengecekan kepatuhan sewa per pintu/unit via scan barcode.<br>• Pencatatan pembayaran perbantuan bagi penghuni tanpa smartphone. |
| **Web Admin Disperkim**<br>`adminmpad.baubaukota.go.id` | Verifikator & Pimpinan Disperkim (`opd_id: 11`) | • Dashboard okupansi & ketersediaan kamar.<br>• Verifikasi berkas pemohon (SLA verifikasi < 24 jam).<br>• Manajemen master inventaris unit kamar rusunawa.<br>• Terisolasi dari rekening penerimaan global OPD lain. |

---

## VI. Pertanyaan Kunci & Landasan Yuridis Arsitektur

### 1. Bagaimana Sistem Rusunawa yang Diminta (Dokumen Pak Massad & Arsitektur M-PAD)?
Rusunawa dikelola dengan pendekatan **Unit Aset Periodik Bulanan** di dalam modul aset terpadu (`JU-ASET`), di mana tarif per lantai dan aturan denda merujuk langsung pada Perda 1/2024. Alur transaksi mencakup 52 field verifikasi sewa, mulai dari registrasi NIK, pemilihan kamar bertingkat, verifikasi berkas oleh Disperkim, penerbitan e-SKRD otomatis, hingga rekonsiliasi kasda T+0.

### 2. Apakah Rusunawa Masuk dalam Layanan Tersendiri atau Fitur Aset?
Secara arsitektur M-PAD dan kesepakatan desain bersama Kepala Bapenda (Pak Massad Kaban), **Rusunawa masuk ke dalam Fitur Terpadu Pemanfaatan Aset Daerah (kode layanan: `JU-ASET`), BUKAN layanan/fitur terpisah di menu utama**.
- **Alasan Desain**: Menghindari fragmentasi puluhan layanan individual di antarmuka warga. Seluruh kekayaan daerah disatukan dalam **1 Pintu Layanan Aset Daerah** dengan field pertama berupa Dropdown Kategori:
  1. *Tanah*
  2. **`Bangunan`** $\longrightarrow$ Di sinilah Rusunawa berada (bersama Gedung Serba Guna / GSG)
  3. *Alat Berat* (PUPR)
  4. *Kendaraan* (Dump truck, Armada Sedot Kakus)
  5. *Mesin*
  6. *Peralatan*
- **Sisi Admin Web**: Walaupun di frontend warga berada dalam 1 payung layanan Aset, di sisi admin data Disperkim terisolasi secara otomatis berdasarkan `opd_id: 11`.

### 3. Fungsi Yuridis Serah Terima Akun Verifikator
Penyerahan akun resmi `admin.perkim@baubaukota.go.id` yang diatur dalam Surat Sekda memiliki 4 fungsi yuridis fundamental:
1. **Legitimasi Pejabat Penetap SKRD (UU HKPD 1/2022 & PP 35/2023)**:
   Penetapan retribusi daerah hanya sah apabila diverifikasi dan disetujui oleh aparatur berwenang dari dinas teknis pengampu. Serah terima akun menandai pengalihan sah wewenang operasional dari tim pengembang ke aparatur resmi Disperkim.
2. **Pemisahan Wewenang SOTK (*Segregation of Duties*)**:
   Bapenda bertindak sebagai koordinator penerimaan kasda, sedangkan Disperkim memegang wewenang tunggal verifikasi kelayakan teknis permohonan hunian.
3. **Kepastian Pertanggungjawaban pada *Immutable Audit Trail***:
   Setiap klik persetujuan sewa (*approve*), penolakan (*reject*), maupun perubahan status ketersediaan unit kamar dicatat secara permanen di database pada tabel `audit_logs` (merekam ID akun, timestamp, IP address, dan nilai perubahan) sebagai bukti audit Inspektorat, BPK, dan BPKP.
4. **Kepatuhan Perlindungan Data Pribadi (UU No. 27/2022 - UU PDP)**:
   Menjamin dokumen pribadi pemohon (NIK, KK, kontak) hanya dapat diakses oleh personil yang ditunjuk secara sah (*Authorized Personnel*) di lingkungan kedinasan.

---

## VII. Action Items & Kebutuhan Data dari Disperkim (Target Rapat 14.00 WITA)

Berikut adalah daftar kebutuhan data pendukung yang wajib disepakati untuk diserahkan oleh tim Disperkim:

1. **Master Inventaris Unit Rusunawa Lengkap**:
   - [ ] Daftar 4 kompleks rusun aktif (Kotamara, Wameo, ASN, Mahasiswa).
   - [ ] Rincian total unit kamar per lantai beserta penomoran pintu resmi (misal: Lantai 1 Kamar 101, 102, dst.).
   - [ ] Status keterisian kamar saat ini (*Available*, *Occupied*, *Maintenance*).
2. **Data Master Penghuni Aktif Eksisting**:
   - [ ] Data nama penyewa, NIK, No. HP/WhatsApp aktif, sisa masa kontrak sewa, dan riwayat piutang berjalan (untuk migrasi data awal).
3. **Penetapan Staf Operator / Verifikator Disperkim**:
   - [ ] Penunjukan pejabat/staf teknis pemegang akun `admin.perkim@baubaukota.go.id`.
   - [ ] Pelaksanaan serah terima akun, penggantian password perdana mandiri, dan simulasi singkat verifikasi permohonan.
4. **Kesepakatan Target Waktu (Sprint)**:
   - [ ] Penginputan data master kamar ke sistem: maksimal **3 hari kerja** pasca-kunjungan.
   - [ ] Uji coba penerbitan e-SKRD sewa Rusunawa perdana di lingkungan production.
