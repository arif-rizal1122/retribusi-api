# Skema Layanan & Cara Kerja Pajak Reklame M-PAD

Layanan Pajak Reklame di ekosistem M-PAD (ID Klasifikasi: **188**) menerapkan skema **Official Assessment** (ditetapkan oleh Fiskus/Pemerintah), berbeda dengan pajak seperti Hotel/Restoran yang menerapkan *Self Assessment*. 

Sesuai filosofi **Riset Blanko OPD**, pendaftaran pajak reklame tidak lagi menggunakan form rumit yang membingungkan warga, melainkan dipisahkan antara input kasat mata warga dan form teknis lapangan petugas.

Berikut adalah detail skema cara kerja hulu-ke-hilir untuk layanan Reklame berdasarkan regulasi (Perwali 11/2025 & Perda 1/2024):

---

## 1. Skema Perhitungan (Kalkulasi Otomatis API)
Sistem M-PAD (`retribusi-api`) memiliki kalkulator pajak yang menghitung Pajak Terutang secara definitif berdasarkan rumus: 
**`Pajak Terutang = Nilai Sewa Reklame (NSR) x Tarif (25%)`**

NSR dibentuk dari komponen-komponen berikut yang dihitung otomatis oleh server, tanpa perlu diinput oleh warga:
*   **NJOPR (Nilai Jual Objek Pajak Reklame):** Dihitung berdasarkan **Luas x Tarif Jenis Reklame**. Jika luas kurang dari 1 meter persegi, sistem otomatis membulatkan ke atas menjadi 1 m2.
*   **NSPR (Nilai Strategis Penyelenggaraan Reklame):** Nilai tambah berdasarkan zonasi yang dideteksi otomatis via peta GIS Bapenda:
    *   **Kelas Jalan A (Sangat Strategis)**
    *   **Kelas Jalan B (Strategis)**
    *   **Kelas Jalan C (Standar)**
*   **Multiplier (Faktor Pengali Khusus):** Rokok / Minuman Beralkohol (1.1), Lokasi Indoor / Mall (0.5).

---

## 2. Matriks Blanko OPD (Pendaftaran & Billing)
Proses manual formulir kertas telah ditransformasikan ke dalam 3 tahapan (*Cross-Repo Sync*):

1. **Aplikasi Warga (`retribusi-mobile`):** Warga hanya memilih kategori (Misal: Spanduk), mengetik judul iklan, menjatuhkan *pin* titik di peta (MapPicker), menginput panjang & lebar fisik (luas otomatis readonly), serta mengunggah desain materi/foto fisik. Warga **tidak menginput** NSPR, durasi hari spesifik, maupun rumus tarif.
2. **Penetapan Admin (`retribusi-admin`):** Verifikator Bapenda memeriksa kesesuaian gambar dan ukuran. Jika disetujui, sistem mengunci nilai dan menerbitkan **SKPD** (Surat Ketetapan Pajak Daerah).
3. **Immutability:** SKPD yang telah diterbitkan (menjadi `Bill`) berstatus terkunci (*immutable*) dan tidak dapat diubah angkanya tanpa melalui prosedur audit ketat.

---

## 3. Skema Operasional Petugas Lapangan (Penertiban Reklame)
Petugas lapangan menggunakan aplikasi khusus `retribusi-petugas` untuk melakukan **Billboard Audit (Penertiban Reklame)**:
*   **Visual Evidence (Bukti Otentik):** Petugas wajib mengambil foto kondisi reklame di lapangan yang tersemat **koordinat GPS** (Anti-Fake GPS) sebagai bukti sah pemasangan.
*   **Uji Petik / Pencocokan Data:** Petugas mencocokkan fisik reklame dengan data yang ada di *database* server (misal: apakah reklamenya membesar atau sudah kedaluwarsa).
*   **Digital LKOK:** Hasil inspeksi dicatat ke dalam **Lembar Kerja Objek Khusus (LKOK)** digital yang di-TTE (Tanda Tangan Elektronik) oleh petugas di tempat.
*   **Flagging Reklame Liar:** Jika ditemukan reklame terpasang namun belum memiliki NPWPD (Nomor Pokok Wajib Pajak Daerah) atau belum berizin, petugas memberikan *flag* "Anomaly" atau "Unregistered" pada aplikasi.

---

## 4. Penindakan & Penagihan Aktif (Enforcement)
Berdasarkan data dari petugas lapangan, sistem akan melakukan eskalasi:
*   **Kurang Bayar:** Jika reklame ternyata lebih besar/banyak dari yang dilaporkan, Admin akan menerbitkan **SKPDKB** (Surat Ketetapan Pajak Daerah Kurang Bayar).
*   **Siklus Teguran:** Jika Wajib Pajak menunggak, sistem `Billing` akan secara berkala mencetak Surat Teguran 1, 2, 3.
*   **Penyegelan:** Untuk reklame liar atau menunggak berat, sistem menerbitkan **SPMP** (Surat Paksa) sebagai dasar hukum petugas lapangan menempelkan **Stiker Penertiban (Disegel)** atau pembongkaran.
