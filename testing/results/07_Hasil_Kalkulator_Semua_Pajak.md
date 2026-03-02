# 🧮 Laporan Hasil Uji Otomatis API Kalkulator Pajak

**Waktu Eksekusi**: 2026-03-01 00:46:04
Pengujian dieksekusi secara otomatis menembak server `Localhost:8000` via endpoint POST `/api/simulate-tax` untuk masing-masing klasifikasi.

### PBJT - Makan dan Minum (`PBJT-MNM`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Tenaga Listrik (`PBJT-LIS`)
- **Formula Server**: `tagihan * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### PBJT - Jasa Perhotelan (`PBJT-HTL`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### Pajak Reklame (`REKLAME`)
- **Formula Server**: `nsr * 0.25`
- **Dummy Set Variabel**: `{"omzet":5000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Pajak MBLB (`MBLB`)
- **Formula Server**: `(volume * harga) * 0.15`
- **Dummy Set Variabel**: `{"omzet":5000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### BPHTB (`BPHTB`)
- **Formula Server**: `(npop - npoptkp) * 0.05`
- **Dummy Set Variabel**: `{"omzet":5000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### PBB-P2 (`PBB`)
- **Formula Server**: `(njop - 10000000) * 0.003`
- **Dummy Set Variabel**: `{"omzet":5000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Sarang Burung Walet (`WALET`)
- **Formula Server**: `nilai_jual * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Opsen PKB/BBNKB (`OPSEN`)
- **Formula Server**: `pokok * 0.66`
- **Dummy Set Variabel**: `{"omzet":5000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### PBJT - Kesenian dan Hiburan (`PBJT-HBR`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Parkir (`PBJT-PRK`)
- **Formula Server**: `omzet * 0.10`
- **Dummy Set Variabel**: `{"omzet":5000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### Pajak Air Tanah (`PAT`)
- **Formula Server**: `(volume * hda) * 0.20`
- **Dummy Set Variabel**: `{"omzet":5000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Retribusi Persampahan (`SAMPAH`)
- **Formula Server**: `tarif_flat`
- **Dummy Set Variabel**: `{"omzet":5000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### Retribusi PKD (`PKD`)
- **Formula Server**: `tarif_kios`
- **Dummy Set Variabel**: `{"omzet":5000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### PBG (Building Permit) (`PBG`)
- **Formula Server**: `luas * indeks`
- **Dummy Set Variabel**: `{"omzet":5000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

