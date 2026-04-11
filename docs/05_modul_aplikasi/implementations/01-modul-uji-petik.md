# Panduan Implementasi: Modul Uji Petik (Pengamatan Lapangan)

**Dasar Hukum:** Peraturan Wali Kota (Perwali) Baubau Nomor 58 Tahun 2024.
**Fungsi:** Sebagai Kertas Kerja Pengambilan Sampel Data observasi (jam-per-jam) sebagai bukti penerbitan SKPDKB/Pajak secara jabatan jika omzet riil WP tidak sesuai dengan laporannya.

---

## 🏗️ 1. Skema Database (Backend `retribusi-api`)
Buat dua buah tabel baru beserta `Model` dan `Migration`-nya.

### Tabel `spot_checks` (Header Pemeriksaan)
Tabel ini merepresentasikan 1 (satu) lembar formulir uji petik.
- `id` (PK)
- `taxpayer_id` (FK ke tabel Wajib Pajak)
- `tax_object_id` (FK ke tabel Objek Pajak, karena 1 WP bisa punya banyak cabang/objek)
- `inspector_id` (FK ke tabel Users, petugas pemeriksa yang melakukan observasi)
- `start_date` (Tanggal dimulainya observasi)
- `end_date` (Tanggal berkahirnya observasi)
- `is_weekend` (Boolean, penanda apakah observasi ini kategori Hari Biasa atau Akhir Pekan)
- `taxpayer_representative` (Nama perwakilan WP yang menandatangani form)
- `supervisor_id` (FK ke Kepala Sub Bidang yang mengesahkan)
- `status` (Enum: `draft`, `submitted`, `approved`)
- `remarks` (Catatan khusus pengamat di lapangan)

### Tabel `spot_check_items` (Detail Observasi Per-Jam)
Tabel ini merepresentasikan baris isian pada kertas kerja untuk observasi di jam tertentu.
- `id` (PK)
- `spot_check_id` (FK ke tabel `spot_checks`)
- `observation_time` (Misalnya: `07:00:00`, tipe data Time atau String)
- `visitor_count` (Integer: Jumlah kunjungan tamu)
- `transaction_count` (Integer: Jumlah transaksi kasir yang terjadi)
- `estimated_value` (Decimal/BigInt: Estimasi nilai nominal transaksi dalam Rupiah)
- `details` (JSON atau Text: Rincian spesifik seperti *kamar terjual*, *jenis tiket hiburan*, atau *kendaraan parkir*).

---

## 🧮 2. Skema Business Logic (Service Layer)
Buat sebuah file service bernama `app/Services/SpotCheckService.php`.

**Algoritma Analisis Estimasi Harian:**
Service ini dipanggil saat Pengawas menekan tombol "Generate Analisis Kinerja WP". Logikanya:
1. `getAverageDaily(tax_object_id)`: Menjumlahkan seluruh `estimated_value` dari `spot_checks` yang bertipe `is_weekend = false` (Senin-Jumat) lalu dibagi jumlah hari observasi.
2. `getAverageWeekend(tax_object_id)`: Melakukan hal yang sama khusus pencarian observasi yang `is_weekend = true` (Sabtu-Minggu).
3. **Kalkulasi Bulanan:** `(Rata2 Harian * 22 hari) + (Rata2 Akhir Pekan * 8 hari)`. Hasil kalkulasi ini akan menjadi **Omzet Riil Hasil Uji Petik**.

---

## 🖥️ 3. Skema Antarmuka (Dashboard Server `retribusi-admin`)
Buat halaman komponen baru `src/pages/SpotCheckForm.tsx`:

**User Interface Matrix (Grid):**
1. Bagian atas: Informasi Header Pemeriksaan (Dropdown WP, Objek Pajak, Tanggal).
2. Bagian tengah: Sebuah Tabel Dinamis berisi minimal 24 baris (merepresentasikan 24 Jam dari 07.00 pagi ke 06.00 besok).
   - Petugas/Pengawas bisa menginput angka di kolom `Jumlah Pengunjung`, `Jumlah Transaksi`, `Nominal Estimasi` langsung ke dalam sel (*inline editing* layaknya Excel).
3. Bagian bawah: Kalkulasi Total Otomatis dari jumlah yang diketik di sel atas, diikuti kolom penandatangan (*digital signature* / input nama terang).

---

## 🔗 4. Skema Integrasi dengan SKPDKB
Pada modul `PenindakanController` yang meng-generate SKPDKB, perbarui logikanya. Jika ada data `spot_checks` yang telah `status = approved` untuk wajib pajak di periode jatuh tempo tersebut, hitung tagihannya bukan dari pelaporan sang WP, melainkan menggunakan output/nilai omzet dari `SpotCheckService` sebagai **Pajak Ditetapkan Secara Jabatan** (yang biasanya bunganya lebih tinggi/maksimal).
