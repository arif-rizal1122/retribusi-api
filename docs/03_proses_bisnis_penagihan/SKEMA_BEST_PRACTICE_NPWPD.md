# 🏛️ Cetak Biru & Skema Best Practice Penerapan NPWPD di Ekosistem M-PAD

Dokumen ini memuat analisis komparatif sistem warisan (**9pajak / SW_PATDA**), catatan kritis/koreksi terhadap kelemahan sistem lama, serta rekomendasi arsitektur **Best Practice** penerapan NPWPD di ekosistem M-PAD Kota Baubau (API, Mobile Warga, Aplikasi Petugas, dan Dashboard Admin).

---

## 1. Kilas Balik: Skema NPWPD di 9pajak (Legacy SW_PATDA)

Pada sistem 9pajak, alur pendaftaran dan pemanfaatan NPWPD diatur sebagai berikut:

1. **Pendaftaran Manual Terpusat**:
   - Wajib Pajak mengisi berkas fisik di loket Bapenda.
   - Petugas memasukkan data ke tabel `PATDA_WP` dengan atribut `CPM_NPWPD`, `CPM_NAMA_WP`, `CPM_ALAMAT_WP`, dan `CPM_JENIS_PAJAK`.
   - Format penomoran menggunakan segmentasi manual: `P.[Jenis].[No_Buku].[Kec].[Kel]`.
2. **Koneksi ke Objek Pajak (Contoh: Reklame)**:
   - Objek reklame didaftarkan di tabel `PATDA_REKLAME_PROFIL` menggunakan foreign key string `CPM_NPWPD`.
   - Dokumen permohonan & SKPD disimpan di `PATDA_REKLAME_DOC` yang merekam atribut fisik: `CPM_ATR_LEBAR`, `CPM_ATR_TINGGI`, `CPM_ATR_MUKA`, `CPM_ATR_KAWASAN` (Kelas Jalan A/B/C).
   - Tagihan diterbitkan dengan nomor bayar yang mengunci `CPM_NPWPD`.

---

## 2. Catatan Koreksi & Kelemahan Paradigma Lama (9pajak)

Berdasarkan audit teknis dan forensik data Bapenda, sistem 9pajak memiliki sejumlah titik kritis (*pain points*) yang menimbulkan inefisiensi dan potensi kebocoran PAD:

### ❌ Koreksi 1: Anomali Duplikasi NPWPD (Satu Orang Memiliki Banyak NPWPD)
- **Kelemahan**: Di 9pajak, kolom `CPM_JENIS_PAJAK` melekat di tabel `PATDA_WP`. Akibatnya, jika seorang pengusaha memiliki Restoran, lalu beberapa bulan kemudian ingin memasang Reklame, petugas loket sering kali mendaftarkannya kembali dengan **NPWPD baru**.
- **Dampak**: Database dipenuhi data ganda (*redundant taxpayers*), menyulitkan Bapenda melihat profil kepatuhan fiskal utuh satu wajib pajak (*Taxpayer 360° View*).

### ❌ Koreksi 2: Absennya Validasi "Single Identity" (Tidak Terikat NIK/NIB)
- **Kelemahan**: Identifier hanya mengandalkan string nama (`CPM_NAMA_WP`) tanpa validasi NIK Dukcapil (16 digit) untuk perorangan atau NIB OSS untuk badan usaha.
- **Dampak**: Muncul inkonsistensi penulisan nama (contoh: *"PT. Sinar Baubau"*, *"PT Sinar Baubau"*, *"Sinar Baubau CV"*). Saat terjadi tunggakan pajak reklame atau PBJT, petugas penagihan kesulitan melakukan penegakan hukum karena identitas kependudukan/hukum tidak terverifikasi.

### ❌ Koreksi 3: Hambatan Layanan Warga (*High Friction*) & Memicu Reklame Liar
- **Kelemahan**: Warga atau UMKM yang hanya ingin memasang spanduk/reklame insidentil selama 1–2 minggu dipaksa menjalani birokrasi antre pembuatan NPWPD formal ke kantor Bapenda.
- **Dampak**: Pelaku usaha enggan melapor dan memilih memasang reklame secara sembunyi-sembunyi (*underground/liar*), sehingga potensi PAD hilang.

### ❌ Koreksi 4: Database Terfragmentasi (*Silo Tables*)
- **Kelemahan**: 9pajak memecah setiap jenis pajak ke dalam tabel terisolasi (`PATDA_HOTEL_*`, `PATDA_REKLAME_*`, `PATDA_RESTORAN_*`).
- **Dampak**: Tidak ada standardisasi status dokumen, mutasi kepemilikan rumit, dan pelaporan keuangan konsolidasi membutuhkan kueri join yang sangat lambat dan rapuh.

### ❌ Koreksi 5: Lemahnya Interlock Kadaluarsa & Penertiban Lapangan
- **Kelemahan**: Izin tayang reklame di `PATDA_REKLAME_DOC` tidak terhubung dengan sistem penertiban lapangan berbasis GPS atau peringatan otomatis.
- **Dampak**: Banyak reklame komersial tetap tayang berbulan-bulan setelah masa izin habis tanpa membayar perpanjangan pajak.

---

## 3. Skema Best Practice Penerapan NPWPD di Ekosistem M-PAD

Menjawab kelemahan di atas, arsitektur M-PAD mengadopsi standar modern berbasis **UU HKPD No. 1/2022**, **Perda Baubau No. 1/2024**, dan **Prinsip Integrasi Satu Data Indonesia**:

```
                       ┌──────────────────────────────┐
                       │      WAJIB PAJAK (SUBJEK)    │
                       │   NIK / NIB (Single Source)  │
                       │  NPWPD: P.YYYY.XXXXX (Reg)   │
                       └──────────────┬───────────────┘
                                      │ 1 to Many
         ┌────────────────────────────┼────────────────────────────┐
         ▼                            ▼                            ▼
┌─────────────────┐          ┌─────────────────┐          ┌─────────────────┐
│  OBJEK PAJAK 1  │          │  OBJEK PAJAK 2  │          │  OBJEK PAJAK 3  │
│  PBJT Restoran  │          │  Pajak Reklame  │          │  PBB-P2 Properti│
│  (NOPD Kasir)   │          │  (NOPD Billboard)│         │  (NOP 18 Digit) │
└────────┬────────┘          └────────┬────────┘          └────────┬────────┘
         │                            │                            │
         ▼                            ▼                            ▼
[SPTPD Omzet Bulanan]        [Masa Tayang & Luas/Sisi]    [SPPT Tahunan Massal]
         │                            │                            │
         └────────────────────────────┼────────────────────────────┘
                                      ▼
                        ┌───────────────────────────┐
                        │      KODE BILLING MPAD    │
                        │    (H2H RKUD Bank Sultra) │
                        └───────────────────────────┘
```

---

### Pilar 1: Single Identity & Dual-Key Resolution

1. **Primary Legal Key (Nasional)**:
   - **Orang Pribadi**: NIK 16 digit terintegrasi profil Dukcapil / IKD.
   - **Badan Usaha**: NIB 13 digit (OSS RBA) atau NPWP 16 digit.
2. **Regional Fiscal Key (Daerah)**:
   - Nomor NPWPD resmi format Bapenda: `P.YYYY.XXXXX` (di-generate otomatis oleh `Taxpayer::resolveNpwpd()`).
3. **Aturan Integritas**:
   - 1 NIK / NIB **hanya boleh memiliki 1 record `taxpayers`** dan 1 NPWPD seumur hidup di Kota Baubau.
   - Seluruh unit usaha baru (restoran, hotel, titik reklame) diikat ke `taxpayer_id` yang sama.

---

### Pilar 2: Seamless Auto-Provisioning (Penerbitan e-NPWPD Instan)

Warga atau pelaku usaha **tidak boleh dibebani loket terpisah** hanya untuk mendapatkan NPWPD.
1. Saat warga mendaftar di aplikasi `retribusi-mobile`, profil diverifikasi dengan NIK.
2. Saat warga pertama kali mengakses layanan yang membutuhkan NPWPD (misal: Pajak Reklame atau PBJT Restoran):
   - Sistem mendeteksi apakah `taxpayers.npwpd` sudah terisi.
   - Jika belum, backend otomatis memanggil `resolveNpwpd($taxpayer->nik)` yang menerbitkan nomor register `P.2026.XXXXX` di latar belakang secara instan (*0 detik*).
3. Formulir pengajuan reklame langsung terbuka dengan kolom NPWPD terisi otomatis (*pre-filled & read-only*).
4. Warga langsung memperoleh **Kartu Virtual e-NPWPD** ber-QR code resmi di menu profilnya.

---

### Pilar 3: Pemanfaatan Terpadu pada Objek Reklame

Pada layanan Pajak Reklame, pemanfaatan NPWPD diatur secara digital dan terukur:

| Komponen | Implementasi di M-PAD | Keunggulan Dibanding 9pajak |
| :--- | :--- | :--- |
| **Identitas Subjek** | `tax_objects.taxpayer_id` merujuk ke tabel `taxpayers`. | Tidak ada duplikasi data wajib pajak. |
| **Identitas Titik** | Kode Objek / NOPD unik titik reklame. | Menghitung pajak per titik secara presisi. |
| **Spesifikasi Fisik** | Disimpan di JSON `tax_objects.metadata` (Panjang, Lebar, Sisi, Sudut Pandang, Zonasi Jalan A/B/C). | Fleksibel terhadap perubahan regulasi tarif. |
| **Lokasi & Geospasial** | Titik koordinat GPS (`latitude`, `longitude`) + Foto lingkungan. | Mencegah reklame fiktif / titik liar. |
| **Masa Berlaku** | `metadata->masa_tayang_mulai` dan `metadata->masa_tayang_selesai`. | Memungkinkan sistem peringatan otomatis (*auto-expiry*). |

---

### Pilar 4: Interlocking Otomatis & Siklus Pengawasan Lapangan

1. **Peringatan Pra-Kadaluarsa (H-14 & H-7)**:
   - Mesin cron job M-PAD memindai objek reklame yang masa tayangnya akan habis.
   - Notifikasi dikirimkan ke aplikasi mobile wajib pajak dan WhatsApp Bapenda untuk perpanjangan 1 klik.
2. **Status Kadaluarsa (Expired)**:
   - Jika lewat tanggal selesai belum dibayar, status objek otomatis berubah menjadi `expired`.
3. **Disposisi Petugas Lapangan (`retribusi-petugas`)**:
   - Titik reklame kadaluarsa otomatis muncul sebagai pin merah di radar peta patroli petugas lapangan.
   - Petugas menempelkan stiker peringatan resmi / QR Penertiban Bapenda.

---

### Pilar 5: Single Billing & Konsolidasi Penagihan

- Bapenda dapat melihat ringkasan keuangan seorang Wajib Pajak:
  - Total PBJT Restoran bulan berjalan: Rp 4.500.000
  - Total Pajak Reklame 2 titik: Rp 2.200.000
  - Total PBB-P2 1 ruko: Rp 850.000
- Wajib Pajak dapat membayar sekaligus melalui **Multi-Bill Payment** atau membayar terpisah per kode billing via QRIS / VA Bank Sultra / teller perbankan.

---

## 4. Kesimpulan & Rekomendasi Eksekusi

1. **Jadikan NIK/NIB sebagai jangkar utama**: Hapus keharusan pengurusan NPWPD manual di loket.
2. **Sistem 1 Subjek $\rightarrow$ Banyak Objek**: Satu NPWPD menaungi seluruh objek reklame dan usaha PBJT milik pengusaha tersebut.
3. **Pertahankan format `P.YYYY.XXXXX` sebagai nomor register daerah**: Format ini sudah teruji di M-PAD dan selaras dengan arsip sejarah Bapenda.
4. **Perkuat interlock masa tayang reklame**: Hubungkan tanggal kadaluarsa izin tayang reklame dengan penugasan penertiban petugas lapangan secara otomatis.
