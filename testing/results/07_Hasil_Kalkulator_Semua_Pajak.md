# 🧮 Laporan Hasil Uji Otomatis API Kalkulator Pajak

**Waktu Eksekusi**: 2026-02-24 12:55:08
Pengujian dieksekusi secara otomatis menembak server `Localhost:8000` via endpoint POST `/api/simulate-tax` untuk masing-masing klasifikasi.

### PBB-P2 (`PBB-UMUM`)
- **Formula Server**: `(njop - 10000000) * (njkp_percent / 100) * (tariff / 100)`
- **Dummy Set Variabel**: `{"luas_tanah":120,"kelas_bumi":"080","luas_bangunan":60,"kelas_bangunan":"080","nomor_sertifikat":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### BPHTB (`BPHTB`)
- **Formula Server**: `(npop - npoptkp) * 0.05`
- **Dummy Set Variabel**: `{"npop":5000000,"npoptkp":1000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 200.000**

### Pajak Reklame (`REKLAME`)
- **Formula Server**: `nsr * 0.25`
- **Dummy Set Variabel**: `{"nsr":5000000,"ukuran":100,"lokasi_reklame":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 1.250.000**

### Pajak Sarang Burung Walet (`WALET`)
- **Formula Server**: `nilai_jual * 0.10`
- **Dummy Set Variabel**: `{"nilai_jual":5000000,"lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### Pajak MBLB (`MBLB`)
- **Formula Server**: `(volume * harga_patokan) * 0.15`
- **Dummy Set Variabel**: `{"volume":50,"harga_patokan":80000,"jenis_mineral":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 600.000**

### PBJT - Makan dan Minum (`PBJT-MNM`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Catering (`PBJT-CAT`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"nama_perusahaan":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Event/Hiburan Lainnya (`PBJT-EVT`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"nama_event":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Tenaga Listrik (`PBJT-LIS`)
- **Formula Server**: `tagihan_listrik * tariff`
- **Dummy Set Variabel**: `{"tagihan_listrik":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### PBJT - Jasa Perhotelan (`PBJT-HTL`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Parkir (`PBJT-PRK`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Kesenian dan Hiburan (`PBJT-HBR`)
- **Formula Server**: `omzet * tariff`
- **Dummy Set Variabel**: `{"omzet":5000000,"keterangan_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Pajak Air Tanah (`PAT`)
- **Formula Server**: `(volume * hda) * 0.20`
- **Dummy Set Variabel**: `{"volume":50,"hda":80000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 800.000**

### Penyediaan Tempat Kegiatan Usaha (`PTKU`)
- **Formula Server**: `amount`
- **Dummy Set Variabel**: `{"jenis_usaha":"Testing Data","lokasi_google_maps":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Retribusi Jasa Umum (`RJU`)
- **Formula Server**: `amount`
- **Dummy Set Variabel**: `{"jenis_layanan":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Retribusi Perizinan Tertentu (`RPT`)
- **Formula Server**: `amount`
- **Dummy Set Variabel**: `{"jenis_izin":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Persetujuan Bangunan Gedung (PBG) (`PBG`)
- **Formula Server**: `luas_lantai * (indeks_lokalitas * shst) * indeks_terintegrasi * indeks_bg`
- **Dummy Set Variabel**: `{"luas_lantai":100,"indeks_lokalitas":1,"shst":5560000,"indeks_terintegrasi":1,"indeks_bg":1}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 556.000.000**

### Opsen PKB (`OPS-PKB`)
- **Formula Server**: `pkb_pokok * 0.66`
- **Dummy Set Variabel**: `{"pkb_pokok":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 9.900**

### Opsen BBNKB (`OPS-BBN`)
- **Formula Server**: `bbnkb_pokok * 0.66`
- **Dummy Set Variabel**: `{"bbnkb_pokok":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 9.900**

