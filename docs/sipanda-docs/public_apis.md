# Public APIs & Authentication

Endpoint ini digunakan untuk proses login, registrasi awal, dan simulasi perhitungan pajak tanpa memerlukan token autentikasi penuh (kecuali ditentukan lain).

## 1. Authentication

### Login (Admin/Petugas)
Digunakan untuk login ke aplikasi Admin dan Petugas.
- **URL**: `/login`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "email": "admin@example.com",
    "password": "password"
  }
  ```
- **Response**: `200 OK` (dengan Bearer Token)

### Citizen Login
Digunakan khusus untuk aplikasi Mobile (Wajib Pajak).
- **URL**: `/citizen/login`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "nik": "7471xxxxxxxxxxxx",
    "password": "password"
  }
  ```

---

## 2. Tax Simulation & Formulas

### Simulasi Perhitungan Pajak
Menghitung estimasi pajak berdasarkan rumus dinamis.
- **URL**: `/simulate-tax`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "classification_id": 1,
    "variables": {
      "nilai_transaksi": 1000000,
      "jumlah_hari": 30
    }
  }
  ```

### Ambil Daftar Rumus
Mengambil daftar klasifikasi yang memiliki schema input untuk kalkulator.
- **URL**: `/tax-formulas`
- **Method**: `GET`

---

## 3. PBB-P2 Lookup & Calculation

### Lookup Kelas NJOP
Mencari kelas NJOP berdasarkan nilai (Bumi/Bangunan).
- **URL**: `/pbb/lookup-class`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "type": "bumi",
    "value": 250000
  }
  ```

### Kalkulasi PBB
Menghitung PBB-P2 berdasarkan luas dan kelas.
- **URL**: `/pbb/calculate`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "luas_bumi": 100,
    "kelas_bumi": "075",
    "luas_bangunan": 50,
    "kelas_bangunan": "030"
  }
  ```

---

## 4. Public Verification

### Verifikasi SKRD/SSPD
Memeriksa keabsahan dokumen via QR Code/Nomor.
- **URL**: `/verify/bill/{number}` atau `/verify/payment/{number}`
- **Method**: `GET`
