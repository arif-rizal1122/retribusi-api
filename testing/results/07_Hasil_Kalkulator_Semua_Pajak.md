# 🧮 Laporan Hasil Uji Otomatis API Kalkulator Pajak

**Waktu Eksekusi**: 2026-04-14 23:51:35
Pengujian dieksekusi secara otomatis menembak server `Localhost:8000` via endpoint POST `/api/simulate-tax` untuk masing-masing klasifikasi.

### Retribusi Jasa Umum Lainnya (`W1-OTH`)
- **Formula Server**: `tarif_flat`
- **Dummy Set Variabel**: `[]`
- **Status Pengujian**: ❌ **GAGAL (HTTP 302)**
- **Pesan Error Server**: `&lt;!DOCTYPE html&gt;
&lt;html&gt;
    &lt;head&gt;
        &lt;meta charset=&quot;UTF-8&quot; /&gt;
        &lt;meta http-equiv=&quot;refresh&quot; content=&quot;0;url=&#039;http://localhost:8000&#039;&quot; /&gt;

    `

### PBJT - Jasa Parkir (`PBJT-PRK`)
- **Formula Server**: `omzet * 0.1`
- **Dummy Set Variabel**: `{"omzet":5000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBB-P2 (`PBB-P2`)
- **Formula Server**: `(njop - 10000000) * 0.003`
- **Dummy Set Variabel**: `{"njop":15000,"tahun_sppt":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 0**

### BPHTB (`BPHTB`)
- **Formula Server**: `(npop - 80000000) * 0.05`
- **Dummy Set Variabel**: `{"npop":5000000,"jenis_perolehan":"Testing Data"}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp -3.750.000**

### Pajak Reklame (`REKLAME`)
- **Formula Server**: `((njopr + nspr) * luas * sisi) * 0.25`
- **Dummy Set Variabel**: `{"judul_reklame":"Testing Data","luas":15000,"sisi":15000,"njopr":15000,"nspr":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 1.687.500.000.000**

### Pajak MBLB (`MBLB`)
- **Formula Server**: `(volume * harga) * 0.15`
- **Dummy Set Variabel**: `{"volume":50,"harga":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 112.500**

### Pajak Sarang Burung Walet (`WALET`)
- **Formula Server**: `(volume * harga) * 0.10`
- **Dummy Set Variabel**: `{"volume":50,"harga":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 75.000**

### Opsen Pajak (`OPSEN`)
- **Formula Server**: `pokok_provinsi * 0.66`
- **Dummy Set Variabel**: `{"pokok_provinsi":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 9.900**

### PBJT - Makan dan Minum (`PBJT-FOOD`)
- **Formula Server**: `omzet * 0.1`
- **Dummy Set Variabel**: `{"omzet":5000000,"kapasitas_kursi":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Perhotelan (`PBJT-HTL`)
- **Formula Server**: `omzet * 0.1`
- **Dummy Set Variabel**: `{"omzet":5000000,"jumlah_kamar":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### PBJT - Jasa Kesenian dan Hiburan (`PBJT-HBR`)
- **Formula Server**: `omzet * 0.1`
- **Dummy Set Variabel**: `{"omzet":5000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 500.000**

### Hiburan Malam (Khusus) (`PBJT-HBR-SP`)
- **Formula Server**: `omzet * 0.4`
- **Dummy Set Variabel**: `{"omzet":5000000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 2.000.000**

### PBJT - Tenaga Listrik (`PBJT-PLN`)
- **Formula Server**: `tagihan * 0.1`
- **Dummy Set Variabel**: `{"tagihan":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 1.500**

### Pajak Air Tanah (`AIR-TANAH`)
- **Formula Server**: `(volume * hda) * 0.2`
- **Dummy Set Variabel**: `{"volume":50,"hda":80000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 800.000**

### Retribusi Persampahan (`RET-SMP`)
- **Formula Server**: `tarif_flat`
- **Dummy Set Variabel**: `{"tarif_flat":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 15.000**

### Retribusi Pelayanan Parkir (`RET-PRK`)
- **Formula Server**: `tarif_flat`
- **Dummy Set Variabel**: `{"tarif_flat":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 15.000**

### Retribusi PKD (Kios/Pasar) (`RET-PKD`)
- **Formula Server**: `tarif_dasar * koefisien`
- **Dummy Set Variabel**: `{"tarif_dasar":15000,"koefisien":15000}`
- **Status Pengujian**: ✅ **LULUS**
- **Hasil Parsing Kalkulator**: **Rp 225.000.000**

