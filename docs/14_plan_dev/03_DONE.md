pikirkan ini dan tanggapi
Fokus implementasi **retribusi sampah/kebersihan pasar pada Dinas Perdaginkop (Disperindag)** merupakan titik paling krusial untuk menertibkan tata kelola pasar, karena di lapangan area inilah yang paling sering menjadi sumber pungutan liar (pungli) informal dan keluhan pedagang.

Berdasarkan regulasi **[Perda Kota Baubau No. 1 Tahun 2024](https://drive.google.com/file/d/1nVRrted30T9th42uXP0fuVH0FtO67_m5/view?usp=drivesdk&utm_source=gemini)**, prinsip sistem **M-PAD**, dan kondisi riil di Pasar Wameo serta pasar lainnya, berikut adalah desain implementasi lengkapnya:

---

### 1. Masalah Lapangan yang Harus Diselesaikan di Disperindag

* **Praktik Pungutan Dobel / Liar**: Pedagang sering ditarik uang dua kali: satu kali karcis pasar oleh petugas resmi, dan satu kali lagi "uang sampah/kebersihan" (Rp1.000–Rp2.000) oleh oknum atau petugas kebersihan tanpa karcis resmi.
* **Sampah Masif Non-Standar**: Pedagang tertentu menghasilkan sampah dalam volume sangat besar (misal: sabut kelapa berton-ton, sisa pemotongan ayam/jeroan, dan peti buah busuk), sementara pedagang pakaian/kelontong menghasilkan sampah sangat minim.
* **Beban Operasional Juru Bersih**: Pasukan kebersihan pasar berada di bawah UPTD Pasar / Disperindag, membutuhkan kepastian insentif dan alat kerja (sapu, gerobak dorong, karung sampah).

---

### 2. Skema Implementasi M-PAD: 2 Jalur Retribusi Sampah Pasar

M-PAD membagi penarikan retribusi sampah pasar di Disperindag ke dalam **2 skema yang transparan dan sah secara hukum**:

```
                              ┌────────────────────────────────────────┐
                              │  PENGELOLAAN SAMPAH PASAR DISPERINDAG  │
                              └───────────────────┬────────────────────┘
                                                  │
                 ┌────────────────────────────────┴────────────────────────────────┐
                 ▼                                                                 ▼
      [ JALUR A: PEDAGANG STANDAR ]                                    [ JALUR B: SAMPAH VOLUME BESAR ]
   (Pelataran, Los, Kios Pakaian/Sayur)                             (Kupasan Kelapa, Potong Ayam, Grosir Buah)
                 │                                                                 │
                 ▼                                                                 ▼
      SKEMA BUNDLING RESMI TERPADU                                      SKEMA VOLUMETRIK / SURCHARGE
- Pelataran : Rp 1.000 / hari                                    - Dipungut per gerobak / per karung jumbo
- Los       : Rp 30.000 / bulan                                  - Menu khusus di Petugas Mobile App
- Kios      : Rp 40.000 / bulan                                  - Terbit e-Karcis Sampah Tambahan ber-NTPD
*SUDAH TERMASUK SAMPAH (Rp 0 Tambahan)

```

---

### 3. Rincian Teknis Operasional M-PAD

#### Jalur A: Skema Terintegrasi / Bundling (Pedagang Standar)

* **Dasar Hukum**: Sesuai Pasal 79 Perda No. 1/2024, **Retribusi Pelayanan Pasar (Jasa Umum 202 - `RET-PSR`)** secara hukum telah melekat dengan fungsi kebersihan lingkungan pasar.
* **Penegasan di Struk Thermal**:
Pada cetakan e-Karcis thermal 58mm petugas, redaksi diubah secara eksplisit:
```
RETRIBUSI PELAYANAN & KEBERSIHAN PASAR
Tarif: Rp 1.000 (LUNAS)
*Sudah Termasuk Jasa Pengangkutan Sampah Lapak

```


* **Dampak**: Pedagang memiliki bukti hitam-di-atas-putih bahwa mereka **sudah membayar sampah**. Jika ada oknum yang meminta uang sampah lagi di lorong pasar, pedagang berhak menolak.

#### Jalur B: Skema Tambahan Volume Khusus (*Surcharge / Volumetrik*)

* **Untuk Siapa**: Khusus pedagang penghasil limbah padat ekstra berat (bukan pedagang eceran biasa).
* **Fitur di Aplikasi Kasir (`retribusi-petugas`)**:
* Ditambahkan sub-kategori: **`Sampah Ekstra / Limbah Khusus Pasar`**.
* Parameter penarikan berbasis volume:
* Per Gerobak Dorong Sampah: misal Rp 5.000
* Per Bak / Karung Jumbo: misal Rp 2.500




* **Pencetakan Bukti Sah**:
Petugas memilih volume sampah $\rightarrow$ Cetak e-Karcis Sampah Khusus ber-NTPD. Uang tunai langsung masuk ke pencatatan kas petugas (*Cash Holding Limit*) dan disetorkan ke Kasda.

---

### 4. Pembagian Tugas Hulu-Hilir: Disperindag vs DLH

Untuk menjamin kepatuhan audit BPK dan tidak tumpang tindih antar-dinas:

| Titik Operasional | OPD Penanggung Jawab | Tugas & Pendanaan |
| --- | --- | --- |
| **Hulu (Lapak $\rightarrow$ Lorong Pasar)** | **Disperindag** (UPTD Pasar) | Penyapuan lorong, penyediaan tong sampah di tiap blok, penarikan retribusi pelayanan pasar via M-PAD. |
| **Tengah (Lorong $\rightarrow$ TPS Pasar)** | **Disperindag** (Juru Bersih Pasar) | Pengangkutan sampah dari lapak pedagang ke bak kontainer TPS terpadu pasar menggunakan gerobak motor/dorong pasar. |
| **Hilir (TPS Pasar $\rightarrow$ TPA Akhir)** | **Dinas Lingkungan Hidup (DLH)** | Truk amrol/kontainer sampah DLH mengangkut kontainer dari pasar menuju TPA akhir Kota Baubau. Pembiayaan diselesaikan lewat operasional dinas, **tanpa memungut pedagang lagi**. |

---

### 5. Strategi Eliminasi Pungli Sampah Liar di Lapangan

1. **Maklumat Papan Pengumuman Resmi di Pintu Masuk Pasar**:
Disperindag memasang papan informasi permanen di Pasar Wameo dan pasar lainnya:
> *"PERHATIAN: Karcis Retribusi Pasar Rp 1.000 / hari SUDAH TERMASUK BIAYA KEBERSIHAN SAMPAH LAPAK. Dilarang keras membayar pungutan sampah tambahan tanpa struk resmi M-PAD!"*


2. **Pemberdayaan Pasukan Kuning / Tenaga Kebersihan Pasar**:
Tenaga kebersihan pasar di bawah Disperindag diberikan seragam resmi dan insentif operasional yang jelas dari pos pelayanan pasar, sehingga mereka tidak lagi "mengemis" atau meminta uang rokok/uang sampah ke pedagang pelataran.
3. **Audit Volume di Dashboard Disperindag (`retribusi-admin`)**:
Admin Disperindag dapat melihat perbandingan antara jumlah karcis harian yang terbit dengan estimasi ritase sampah harian yang dihasilkan per blok pasar.

### 6. Tanggapan & Rencana Eksekusi Teknis (Antigravity AI Engine)

Skema 2-Jalur yang dirumuskan di atas merupakan **solusi paling pragmatis dan berkeadilan fiskal** untuk membedah benang kusut persampahan pasar di Kota Baubau. Berikut tanggapan teknis dan kesiapan implementasi arsitektur M-PAD:

#### A. Tanggapan terhadap Desain 2-Jalur
1. **Perlindungan Pedagang Kecil (Jalur A)**:
   - Sangat tepat dan sesuai dengan filosofi **Perda Kota Baubau No. 1/2024 (Pasal 79)**. Retribusi pelataran Rp 1.000/hari, los Rp 30.000/bulan, dan kios Rp 40.000/bulan secara hukum adalah satu paket (*bundled service*) dengan kebersihan lingkungan pasar.
   - Penegasan teks cetak pada struk thermal 58mm adalah kunci psikologis dan legal untuk mematikan pungli: pedagang memiliki pegangan sah untuk menolak juru pungut informal.
2. **Keadilan Beban Emisi/Limbah (Jalur B)**:
   - Mencegah fenomena *tragedy of the commons* di mana pedagang kelapa atau pemotongan hewan menghasilkan ratusan kilogram limbah basah namun hanya membayar Rp 1.000 yang sama dengan pedagang peniti/pakaian.
   - Penerbitan karcis surcharge bervolume per gerobak dorong / karung jumbo memberikan sumber PAD sah tambahan bagi daerah tanpa melanggar batasan retribusi jasa umum.

#### B. Langkah Integrasi ke Ekosistem Kode M-PAD
1. **Thermal Print Struk Staf Lapangan (`retribusi-petugas`)**:
   - Memperbarui `ThermalPrintService.ts` untuk menambahkan klausul proteksi resmi pada karcis harian pelataran:
     ```
     RETRIBUSI PELAYANAN & KEBERSIHAN PASAR
     *Sudah Termasuk Jasa Pengangkutan Sampah Lapak
     *Dilarang Membayar Uang Sampah Tambahan Tanpa Struk
     ```
2. **UI Kasir Petugas Lapangan (`PasarDisperindagPage.tsx`)**:
   - Menambahkan toggle/mode cepat di Tab Karcis:
     - `Karcis Standar Pelataran (Bundled Sampah)`
     - `Karcis Surcharge Limbah Volume Khusus` (Dropdown/Counter: Karung Jumbo @Rp 2.500 / Gerobak @Rp 5.000).
3. **Backend API Data Ledger (`retribusi-api`)**:
   - Menambahkan klasifikasi tarif `RET-PSR-SURCHARGE` pada database Disperindag sehingga penerbitan karcis volumetrik menghasilkan NTPD unik, tercatat di *Cash Holding Limit* petugas, dan langsung diaudit via GPS geofence.
4. **Harmonisasi Hulu-Hilir Disperindag & DLH**:
   - Disperindag mengelola internal pasar hingga Kontainer TPS Pasar (Dokumen 01).
   - Form pendaftaran online warga DLH (`retribusi-mobile`) telah dibersihkan dari karcis harian pasar (Dokumen 02).

