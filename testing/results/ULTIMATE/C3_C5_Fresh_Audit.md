spawn ssh -o StrictHostKeyChecking=no sipanda@157.10.252.74
sipanda@157.10.252.74's password: 
Welcome to Ubuntu 22.04.5 LTS (GNU/Linux 5.15.0-164-generic x86_64)

 * Panduan:  https://idcloudhost.com/panduan
 -------------------------------------------
 * Documentation:  https://help.ubuntu.com
 * Management:     https://landscape.canonical.com
 * Support:        https://ubuntu.com/advantage

 System information as of Wed Mar  4 09:43:59 UTC 2026

  System load:  1.0                Processes:             126
  Usage of /:   48.6% of 19.20GB   Users logged in:       0
  Memory usage: 35%                IPv4 address for ens3: 10.48.77.206
  Swap usage:   0%

 * Strictly confined Kubernetes makes edge and IoT secure. Learn how MicroK8s
   just raised the bar for easy, resilient and secure K8s cluster deployment.

   https://ubuntu.com/engage/secure-kubernetes-at-the-edge

Expanded Security Maintenance for Applications is not enabled.

11 updates can be applied immediately.
To see these additional updates run: apt list --upgradable

18 additional security updates can be applied with ESM Apps.
Learn more about enabling ESM Apps service at https://ubuntu.com/esm


The list of available updates is more than a week old.
To check for new updates run: sudo apt update

*** System restart required ***
Last login: Tue Apr 14 04:09:40 2026 from 180.251.145.115
-bash: /usr/lib/command-not-found: /usr/bin/python3: bad interpreter: No such file or directory
sipanda@sipanda:~$ </testing/results/07_Hasil_Kalkulator_Semua_Pajak.md
# ð§® Laporan Hasil Uji Otomatis API Kalkulator Pajak

**Waktu Eksekusi**: 2026-04-14 04:09:45
Pengujian dieksekusi secara otomatis menembak server `Localhost:8000` via endpoint POST `/api/simulate-tax` untuk masing-masing klasifikasi.

### Retribusi Jasa Umum Lainnya (`W1-OTH`)
- **Formula Server**: `tarif_flat`
- **Dummy Set Variabel**: `[]`
- **Status Pengujian**: ❌ **GAGAL (HTTP 302)**
- **Pesan Error Server**: `&lt;!DOCTYPE html&gt;
&lt;html&gt;
    &lt;head&gt;
        &lt;meta charset=&quot;UTF-8&quot; /&gt;
        &lt;meta http-equiv=&quot;refresh&quot; content=&quot;0;url=&#039;https://api.sipanda.online&#039;&quot; /&gt;
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

sipanda@sipanda:~$ <usi-api/testing/results/09_Laporan_Keamanan_RBAC.md
# ð¡️ Laporan Hasil Uji Coba Keamanan Akses (RBAC)

**Waktu Eksekusi**: 2026-04-14 04:09:57
Pengujian ini menembak API lokal menggunakan Token Sanctum murni untuk membuktikan Sistem Isolasi Peran (Tenant Isolation & Authorization) berjalan sempurna.

### 1. Wajib Pajak Mengakses Endpoint Admin
- ✅ **SUKSES DIBLOKIR**: Server mengembalikan status HTTP `403`. Wajib pajak tidak bisa masuk dapur admin.

### 2. Tamu (Tanpa Token) Mengakses Endpoint Terkunci
- ✅ **SUKSES DIBLOKIR**: Pengunjung dilarang masuk. `401 Unauthenticated`.

### 3. Petugas Lapangan Melakukan Aksi Destruktif (DELETE Tagihan/Objek)
- ✅ **SUKSES DIBLOKIR**: Petugas dilarang menghapus. Server menolak keras dengan blokade Otorisasi (HTTP `403`).

sipanda@sipanda:~$ exit
logout
Connection to 157.10.252.74 closed.
