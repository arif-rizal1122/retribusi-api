Secara umum, form ini **BELUM SEPENUHNYA SESUAI** dengan dokumen master [02_Proses_Bisnis_dan_Alur_Layanan_Persampahan](https://drive.google.com/open?id=1kvUxua6hxGgivhTIcvYaIOHWH4jR6xCRzV-Z7mLhee4&utm_source=gemini) serta regulasi [Perda Kota Baubau No. 1/2024](https://drive.google.com/open?id=1nVRrted30T9th42uXP0fuVH0FtO67_m5&utm_source=gemini).

Meskipun komponen teknis seperti *geotagging* dan perikatan hukum sudah masuk, form ini mengalami **tiga pelanggaran prinsip arsitektur layanan DLH**:

---

### 1. Pelanggaran Fatal terhadap Blueprint DLH

#### 🔴 Pelanggaran 1: Warga Dipaksa Memilih Kategori Tarif Teknis

* **Aturan di Dokumen Blueprint 02 (Tahap 1 Onboarding)**:
> *"Prinsip Desain: Form dibuat minimalis tanpa menampilkan opsi golongan tarif teknis agar tidak terjadi salah pilih atau manipulasi tarif. Warga hanya mengisi jenis peruntukan umum (Rumah Tinggal / Tempat Usaha). Penetapan golongan tarif resmi dilakukan pada Tahap 2 oleh Petugas Verifikator DLH."*


* **Kondisi di Form Saat Ini**:
Dropdown `Kategori Penghasil Sampah *` dijadikan kolom wajib (*mandatory*). Warga disuruh menilai sendiri apakah rumahnya tipe <36 atau >=36, yang memicu potensi manipulasi data agar membayar tarif termurah.

#### 🔴 Pelanggaran 2: Salah Kamar — Karcis Harian Pasar Muncul di Form Objek Bulanan

* **Aturan di Blueprint & Perda 1/2024**:
Retribusi sampah memiliki 3 model layanan terpisah:
1. **Layanan A (Rutin Bulanan/Langganan)**: Rumah tangga dan ruko ber-e-SKRD bulanan.
2. **Layanan B (Insidental/Panggilan)**: Pesta, tebangan pohon, renovasi (bayar per ritase di muka).
3. **Layanan C (Karcis Lapangan Harian)**: Pedagang pasar yang ditarik tunai/QRIS *on-the-spot* harian oleh juru pungut pasar (POS).


* **Kondisi di Form Saat Ini**:
Tabel tarif menampilkan *Karcis Sampah Harian Pasar (Rp 3.000 & Rp 6.000 per hari)* pada form pendaftaran objek langganan bulanan warga. Hal ini merusak proses bisnis dan membingungkan warga.

#### 🔴 Pelanggaran 3: Field "Perkiraan Volume Sampah Harian" Tidak Masuk Akal bagi Warga

* **Aturan di Blueprint**:
Perhitungan volume sampah ($m^3$ atau liter per hari) hanya berlaku untuk **Layanan B (Insidental)** atau tempat usaha komersial besar/industri.
* **Kondisi di Form Saat Ini**:
Field ini muncul untuk semua pendaftar. Warga rumah tangga biasa tidak memiliki timbangan atau meteran kubikasi sampah harian, sehingga isian ini hanya menjadi data tebakan/fiktif.

---

### 2. Catatan Teknis / Bug Antarmuka

1. **Field `Nama lokasi/objek` Terisi Nilai Default yang Salah**:
Terisi otomatis teks: `"Retribusi Persampahan & Kebersihan - Staging"`. Seharusnya berupa teks kosong dengan placeholder pengenal properti milik warga (contoh: *Rumah Tinggal Utama*, *Ruko Depan Lapangan Merdeka*).
2. **Badge "Tarif Flat" Tidak Tepat**:
Struktur tarif Perda No. 1/2024 adalah berjenjang (*stratified* berdasarkan fungsi dan skala), bukan flat tunggal.

---

### 3. Elemen yang Sudah Tepat Sesuai Blueprint & Regulasi

* **Peta Satelit & Geotagging**: Sesuai dengan instruksi *Zero Redundant Typing* dan kebutuhan penguncian koordinat GPS persil untuk *geofencing* penagihan lapangan (< 30 meter).
* **Upload 1 Foto Bangunan**: Sesuai dengan SOP verifikasi fisik tampak depan properti di Dokumen 02.
* **Pengecualian Rp 0 (Fasilitas Sosial & Ibadah)**: 100% patuh pada **Pasal 77 ayat (2) [Perda Kota Baubau No. 1/2024**](https://drive.google.com/open?id=1nVRrted30T9th42uXP0fuVH0FtO67_m5&utm_source=gemini).
* **3 Checkbox Perikatan Hukum**: Sesuai standar *Smart Forms* (kebenaran data, privasi data daerah, dan kepatuhan regulasi Perda No. 1/2024).

---

### Rekomendasi Solusi untuk Tim Frontend/Aiman

1. **Sederhanakan Dropdown**: Ganti `Kategori Penghasil Sampah` yang rumit menjadi pilihan sederhana:
* `Rumah Tinggal`
* `Tempat Usaha / Komersial`
* `Fasilitas Sosial / Rumah Ibadah (Bebas Biaya)`


2. **Sembunyikan Volume Sampah**: Field volume disembunyikan secara *default*, dan hanya muncul jika warga memilih `Tempat Usaha`.
3. **Bersihkan Tabel Tarif**: Hapus baris karcis harian pasar dari daftar tarif form pendaftaran objek bulanan ini.
4. **Kosongkan Input Nama Lokasi**: Ganti teks *staging* dengan placeholder netral.