# Tahap 7: Pengujian Formula Objek Pajak & Retribusi (Kalkulator Dinamis)

Aplikasi Mpad memiliki puluhan jenis pungutan pajak dengan struktur *form* dan rumus hitung (*formula*) yang saling berbeda-beda sesuai Perwali 58/2024. Modul ini bertujuan menguji keakuratan matematis mesin *Calculator* API.

## Checklist Pengujian Klasifikasi Pajak:

### A. Wilayah I (Pajak Utama & Official Assessment)
- [ ] **PBB-P2**:
  - **Input Dummy**: Luas Tanah 100m2 (Kelas Bumi 080), Luas Bangunan 50m2 (Kelas Bangunan 044).
  - **Kriteria Validasi**: Parameter `pbb_terhutang` dari API kalkulator tidak boleh *minus* atau bernilai *null*, dan rumusnya mengakomodir NJOPTKP (default Rp 10 Juta).
- [ ] **BPHTB**:
  - **Input Dummy**: NPOP Rp 250.000.000.
  - **Kriteria Validasi**: Harus menghitung `(NPOP - NPOPTKP) * 5%`.
- [ ] **Pajak Reklame**:
  - **Input Dummy**: NSR Rp 5.000.000, Ukuran 10m2.
  - **Kriteria Validasi**: Tagihan dikenakan *Tarif Reklame 25%* secara presisi.
- [ ] **Pajak Sarang Burung Walet**:
  - **Input Dummy**: Nilai Jual Rp 15.000.000.
  - **Kriteria Validasi**: Tagihan Rp 1.500.000 (Tarif 10%).
- [ ] **Pajak MBLB (Mineral Bukan Logam)**:
  - **Input Dummy**: Volume 100 Rit/m3, Harga Patokan Rp 80.000.
  - **Kriteria Validasi**: Tagihan Rp 1.200.000 (Tarif 15%).
- [ ] **Opsen PKB & Opsen BBNKB**:
  - **Input Dummy**: Pokok dari Provinsi Rp 2.000.000.
  - **Kriteria Validasi**: Opsen untuk daerah adalah 66% (Rp 1.320.000).

### B. Wilayah II (Self Assessment PBJT)
- [ ] **PBJT Restoran / Makan Minum**:
  - **Input Dummy**: Omzet Penjualan Bulanan Rp 10.000.000.
  - **Kriteria Validasi**: Hasil wajib Rp 1.000.000 (Tarif standar 10%).
- [ ] **PBJT Jasa Perhotelan**:
  - **Input Dummy**: Omzet Kamar Rp 25.000.000.
  - **Kriteria Validasi**: Hasil wajib Rp 2.500.000.
- [ ] **PBJT Tenaga Listrik**:
  - **Input Dummy**: Tagihan PLN Rp 5.000.000. (Uji menggunakan Tarif Industri: 3%).
  - **Kriteria Validasi**: Hasil wajib Rp 150.000.
- [ ] **PBJT Hiburan Malam (Khusus)**:
  - **Input Dummy**: Omzet Diskotik Rp 50.000.000. (Uji Tarif Hiburan Malam: 40%).
  - **Kriteria Validasi**: Tagihan raksasa sebesar Rp 20.000.000.

### C. Retribusi Daerah
- [ ] **Persetujuan Bangunan Gedung (PBG)**:
  - **Input Dummy**: Luas Lantai 120m2, Indeks Terintegrasi 1.2, Indeks BG 1.0. (SHST Rp 5.560.000).
  - **Kriteria Validasi**: Angka tidak boleh desimal pecahan error (NaN).
- [ ] **Retribusi Jasa Umum (Kesehatan/Sampah/Parkir)**:
  - **Kriteria Validasi**: Tagihan Parkir Roda 2 (Flat Rp 2.000), Sampah Bulanan Rumah Tangga (Flat Rp 8.500). Tidak boleh memunculkan nilai *Percentage*.
