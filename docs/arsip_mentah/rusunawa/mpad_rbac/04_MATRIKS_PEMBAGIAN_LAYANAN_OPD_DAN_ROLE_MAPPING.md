# Matriks Pembagian Layanan OPD & Role Mapping M-PAD
## Bagian 4: Pemetaan Layanan Dinas, Profil Petugas Lapangan, dan Matriks Wewenang

### 1. Matriks 8 OPD Pemungut Aktif & Profil Petugas Lapangan

Berdasarkan Perda Kota Baubau No. 1 Tahun 2024 dan Peraturan Walikota terkait, berikut pemetaan jenis layanan retribusi yang dikelola oleh masing-masing instansi serta profil petugas lapangan yang mengoperasikannya:

| No | OPD / Unit Kerja | Kode OPD | Layanan Teknis yang Dikelola | Profil & Tugas Petugas Lapangan | Fitur Utama di Mobile Petugas |
|---|---|:---:|---|---|---|
| **1** | **Dinas Lingkungan Hidup** | `DLH` (4) | • Sampah Rumah Tangga Bulanan<br>• Sampah Niaga / Komersial Ruko<br>• Pengujian Lab Lingkungan Hidup<br>• Jasa Timbangan TPA Sampah | • Juru Pungut Sampah Kelurahan<br>• Surveyor Objek Persampahan<br>• Petugas Timbangan / Gerbang TPA | • Penagihan per Kelurahan<br>• Cetak Struk Thermal Iuran<br>• Input Lab Order & SKRD |
| **2** | **Dinas Perhubungan** | `DISHUB` (2) | • Parkir Tepi Jalan Umum (EWS)<br>• Parkir Khusus Luar Badan Jalan<br>• Jasa Tambat / Kepelabuhanan<br>• Pengujian Kendaraan Bermotor | • Juru Parkir Tepi Jalan (Jukir)<br>• Petugas Terminal Angkutan<br>• Petugas Pelabuhan & Tambat | • Menu `Parkir Cepat` (`/parkir`)<br>• Scan Struk QR Parkir<br>• Kasir Terminal Cepat |
| **3** | **Disperindag** | `DISPERINDAG` (3) | • Pelayanan Pasar (Karcis Harian)<br>• Sewa Kios / Los Pasar Tetap<br>• Rumah Potong Hewan (RPH)<br>• Pelayanan Tera / Tera Ulang | • Kolektor Karcis Harian Pasar<br>• Pengawas Kios Blok Pasar<br>• Petugas Pungut RPH Sapi/Kambing | • Penagihan Blok Pasar<br>• Cetak Karcis Harian<br>• Pencatatan Potong Hewan |
| **4** | **Dinas Pariwisata** | `PARIWISATA` (16) | • Karcis Masuk Tempat Rekreasi<br>• Sewa Sarana Rekreasi & Olahraga<br>• Jasa Kepariwisataan | • Petugas Loket Pintu Masuk<br>• Pengawas Lapangan Wisata | • Loket Karcis Masuk Wisata<br>• Pembayaran QRIS Loket |
| **5** | **Dinas Perikanan** | `PERIKANAN` (10) | • Tempat Pelelangan Ikan (TPI)<br>• Retribusi Tambat Perahu Nelayan<br>• Sewa Pabrik Es / Cold Storage | • Juru Pungut Lelang TPI<br>• Pencatat Timbangan Bobot Ikan | • Menu `TPI Lelang` (`/tpi-lelang`)<br>• Kalkulator Hasil Tangkapan |
| **6** | **BPKAD** | `BPKAD` (23) | • Sewa Gedung Serbaguna Daerah<br>• Sewa Tanah & Bangunan Pemda<br>• Pemanfaatan Barang Milik Daerah | • Pengawas Lapangan Aset BMD<br>• Petugas Verifikasi Fisik Sewa | • Checklist Kondisi Bangunan<br>• Verifikasi Kontrak Sewa |
| **7** | **Dinas PUPR** | `PUPR` (9) | • Sewa Alat Berat Konstruksi<br>• Pengujian Lab Bahan Konstruksi<br>• Pemanfaatan Ruang Manfaat Jalan | • Operator / Mandor Alat Berat<br>• Teknisi Lab Uji Tarik / Aspal | • Formulir Sewa Alat Berat<br>• Input Hasil Uji Lab Aspal |
| **8** | **DPMPTSP** | `DPMPTSP` (7) | • Persetujuan Bangunan Gedung (PBG)<br>• Penggunaan Tenaga Kerja Asing | • Tim Verifikator Lapangan PBG<br>(Sinkronisasi SIMBG & NIB) | • Monitoring Dokumen Teknis<br>• Validasi Rekomendasi PBG |
| **9** | **Bapenda** | `BAPENDA` (5) | • 15 Pajak Daerah (PBB-P2, BPHTB, Reklame, PBJT Resto/Hotel/Parkir/Hiburan/Listrik, MBLB, Air Tanah) | • Petugas Uji Petik PBJT<br>• Surveyor Pemetaan NOP PBB<br>• Juru Sita & Penagihan Piutang | • Menu `PBB Bapenda` (`/pbb-bapenda`)<br>• Menu `BPHTB` (`/bphtb`)<br>• Surveillance & Enforcement |

---

### 2. OPD Pengampu Sekunder & OPD Non-Pemungut

- **OPD Pengampu Sekunder (Rekomendasi Teknis)**:
  - **Dinas Damkar (OPD ID 13)**: Verifikator keselamatan proteksi kebakaran sebagai syarat penerbitan PBG (tidak menerbitkan tagihan retribusi sendiri).
  - **Dinas Kominfo (OPD ID 8)**: Pengampu teknis pengawasan game center / warnet & menara telekomunikasi.
  - **Satpol PP (OPD ID 14)**: Penegakan ketertiban perda & penertiban reklame liar / penutupan sementara tempat usaha penunggak pajak.
- **11 OPD Non-Pemungut (Dinsos, Kesbangpol, Disdukcapil, dll)**:
  - Diberikan hak akses *Read-Only Dashboard* ringkasan instansi tanpa menu operasional billing, objek pajak, atau penagihan lapangan, menjaga efisiensi antarmuka.

---

### 3. Matriks Otorisasi Rinci (Detailed Permission Matrix)

| Modul & Wewenang | Super Admin | Bapenda Admin | Admin OPD | Verifikator OPD | Bendahara OPD | Petugas Lapangan | Warga / WP |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **Kelola Master Tarif Perda** | Ya | Ya | Baca | Baca | Baca | Tidak | Baca |
| **Kelola Akun Petugas Lapangan** | Ya (Semua) | Ya (Semua) | **Ya (OPD-nya)** | Tidak | Tidak | Tidak | Tidak |
| **Petakan Layanan & Wilayah Petugas** | Ya | Ya | **Ya (OPD-nya)** | Tidak | Tidak | Tidak | Tidak |
| **Verifikasi Objek / Persil Baru** | Ya | Ya | **Ya (OPD-nya)** | **Ya (OPD-nya)** | Tidak | Hanya Input Sensus | Mandiri (Pending) |
| **Penerbitan e-SKRD Massal** | Ya | Ya | **Ya (OPD-nya)** | Tidak | Tidak | Tidak | Tidak |
| **Pungut Tunai & Struk Thermal** | Tidak | Tidak | Tidak | Tidak | Tidak | **Ya (Sesuai Layanan)** | Tidak |
| **Bantu Bayar QRIS / VA Lapangan** | Tidak | Tidak | Tidak | Tidak | Tidak | **Ya (Binaannya)** | Mandiri |
| **Validasi Setoran Kasda / BKU** | Ya (Global) | Ya (Global) | Baca | Tidak | **Ya (OPD-nya)** | Tidak | Tidak |
| **Penetapan Rekomendasi PBG/Damkar** | Ya | Baca | Tidak | **Ya (Damkar/PTSP)** | Tidak | Tidak | Tidak |
| **Penegakan / Uji Petik (*Enforcement*)** | Ya | Ya (Pengawas) | Baca | Tidak | Tidak | Lapor Temuan | Tidak |
| **Lihat Audit Trail & Log Transaksi** | Ya (Global) | Ya (Global) | **Ya (OPD-nya)** | Tidak | Baca (Keuangan) | Histori Sendiri | Bukti Mandiri |

---

### 3.1. Matriks Khusus Verifikasi Berjenjang PBB-P2 Bapenda (Maker-Checker-Signer)

Untuk layanan PBB-P2 (9 Skenario Mutasi & Pembetulan), diterapkan jenjang otorisasi khusus aparatur Bapenda:

| Jenjang Otorisasi | Aktor Pengampu | Role Sistem | Wewenang & Batasan Aksi |
|---|---|---|---|
| **Level 1 (Maker/Validator)** | **Staf Pelayanan / Verifikator** | `verifikator` / `admin` | • Pemeriksaan kelengkapan warkah & dokumen permohonan.<br>• Verifikasi spasial batas bidang tanah awal via GIS.<br>• Transisi status ke `VERIFIED_L1`. |
| **Level 2 (Checker/Analyst)** | **Kasubid Pendataan** | `kasubid_pendataan` | • Verifikasi kesesuaian ZNT & DBKB bangunan.<br>• Disposisi tugas survei lapangan ke petugas (`SURVEY`).<br>• Validasi hasil survei/LSPOP dan kalkulasi piutang proporsional.<br>• Transisi status ke `PRE_APPROVED_L2`. |
| **Level 3 (Signer/Approver)** | **Kabid Pajak Daerah** | `kabid_pengawas` / `kepala_bapenda` | • Otorisasi hukum penetapan SK Mutasi/Pembetulan.<br>• Penandatanganan TTE elektronik dan pengesahan SPPT definitif.<br>• Transisi status ke `APPROVED` atau `REJECTED`. |

*Standar Akun Kelembagaan:* Akun verifikasi Level 2 didaftarkan sebagai `kasubid_pendataan@mpad.online` dengan nama akun **`Kasubid Pendataan`** (role: `kasubid_pendataan`).

---

### 4. Pedoman Implementasi Teknis & Sinkronisasi Lintas Repositori

1. **Backend (`retribusi-api`)**:
   - `EnsureAdmin`: Mengunci gerbang internal aparatur, menolak akun warga (`Taxpayer`).
   - `EnsureOpdScope`: Menegakkan isolasi data instansi (`opd_id`).
   - `EnsureRole`: Maker-checker gate untuk aksi persetujuan bernilai uang/data.
   - `UserController`: Mengunci pembuatan dan penugasan petugas agar `opd_id` selalu sinkron dengan OPD pembuatnya.
   - `TaxpayerController` & `BillController`: Menyaring basis data hanya pada objek dan sub-retribusi yang ditugaskan kepada petugas lapangan.
2. **Dashboard Internal (`retribusi-admin`)**:
   - `roleUtils.ts`: Menyediakan `opdCanAccessPath(user, path)` untuk menyaring navigasi sidebar secara dinamis.
   - Penegakan form input petugas di mana pilihan OPD terkunci otomatis untuk pengguna role `opd`.
3. **Aplikasi Lapangan (`retribusi-petugas`)**:
   - Menyediakan `officerRoleUtils.ts` dengan fungsi `officerCanAccessPath(user, path)`.
   - Sidebar navigasi hanya menampilkan menu yang relevan dengan dinas petugas:
     - Jukir Dishub hanya melihat `Parkir Cepat` (`/parkir`).
     - Kolektor Perikanan hanya melihat `TPI Lelang` (`/tpi-lelang`).
     - Petugas DLH tidak dibebani menu parkir, TPI, atau PBB Bapenda.
4. **Portal Publik Warga (`retribusi-mobile`)**:
   - Guard terisolasi (`Taxpayer`) tanpa akses ke endpoint administratif.
   - Menampilkan e-SKRD dan e-SSRD warga secara transparan sesuai NIK/identitas persil.
