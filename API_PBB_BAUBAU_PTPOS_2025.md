# API POSPBB - PTPOS

## BAPENDA KOTA BAUBAU

**API POSPBB 2025**

---

## Daftar Isi

1. [Register User](#register-user)
2. [Login](#login)
3. [Inquiry Data](#inquiry-data)
4. [Payment](#payment)
5. [Reversal](#reversal)

---

## Register User

| Item | Detail |
|------|--------|
| **Method URL** | `http://103.182.72.241:8000/pospbb/Api_pos/register` |
| **Method Type** | `POST` |
| **Method Header** | `Content-Type: application/json` |
| **Description** | Register User |

### Input Parameter (Body Form-Data)

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| USERNAME | CHAR | 200 | 1 | |
| PASSWORD | VARCHAR | 200 | 1 | |
| OUTLET | CHAR | 50 | 1 | ptpos |

### Return Data

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| STATUS | VARCHAR | 100 | 1 | |
| MSG | CHAR | 200 | 1 | |

### Response Sukses

```json
{
  "status": 200,
  "msg": "Registrasi sukses."
}
```

### Response Gagal

```json
{
  "status": 400,
  "msg": "Registrasi gagal. Username sudah dipakai."
}
```

---

## Login

| Item | Detail |
|------|--------|
| **Method URL** | `http://103.182.72.241:8000/pospbb/Api_pos/login` |
| **Method Type** | `POST` |
| **Method Header** | `Content-Type: application/json` |
| **Description** | Login User to Inquiry Data |

### Input Parameter (Body Form-Data)

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| USERNAME | CHAR | 200 | 1 | |
| PASSWORD | VARCHAR | 200 | 1 | |

### Return Data

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| STATUS | VARCHAR | 100 | 1 | |
| TOKEN | Header: Bearer Token | | | Token for Auth, put in the header auto |

### Response Sukses

```json
{
  "status": 200,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE2ODc0NDgzOTksIm5iZiI6MTY4NzQ0ODQwOSwiZXhwIjoxNjg3NDUxOTk5LCJ1c2VybmFtZSI6IlVTRVIxIn0.5DY7ZW4fm_B4AbkERgRTLuL-e72AiUTylyb14dzqh5M"
}
```

### Response Wrong Username

```json
{
  "status": 404,
  "msg": "No data found"
}
```

### Response Wrong Password

```json
{
  "status": 400,
  "msg": "Password tidak sesuai."
}
```

---

## Inquiry Data

| Item | Detail |
|------|--------|
| **Method URL** | `http://103.182.72.241:8000/pospbb/Api_pos/inquiry` |
| **Method Type** | `POST` |
| **Method Header** | `Content-Type: application/json` |
| **Description** | Inquiry Data SPPT |

### Input Header

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| Token | Authorization: Bearer Token | | | 1 x 24 Hour |

### Input Parameter (Body Form-Data)

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| NOP | CHAR | 18 | 1 | Nomor Objek Pajak PBB |
| TAHUN | CHAR | 4 | 1 | Tahun Pajak PBB |

### Return Data

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| STATUS | VARCHAR | 100 | 1 | |
| NAMA_WP | CHAR | 100 | 1 | |
| ALAMAT_WP | CHAR | 100 | 1 | |
| KELURAHAN | CHAR | 100 | 1 | |
| KOTA | CHAR | 100 | 1 | |
| TAHUN | CHAR | 4 | 1 | |
| PBB_POKOK | NUMBER | 12 | 1 | |
| DENDA | NUMBER | 12 | 1 | |
| TOTAL_HARUS_DIBAYAR | NUMBER | 12 | 1 | |
| STATUS_BAYAR | CHAR | 100 | 1 | |

### Response Sukses

```json
{
  "status": 200,
  "nama_wp": "AZMAN",
  "alamat_wp": "DSN PADA KUKU",
  "kelurahan": "PADARAYA MAKMUR",
  "kota": "WAKATOBI",
  "tahun": "2022",
  "pbb_pokok": 53600,
  "denda": 536,
  "total_harus_dibayar": 54136,
  "status_bayar": "BLM BAYAR"
}
```

### Response No Data

```json
{
  "status": 401,
  "msg": "Data tidak ditemukan"
}
```

---

## Payment

| Item | Detail |
|------|--------|
| **Method URL** | `http://103.182.72.241:8000/pospbb/Api_pos/payment` |
| **Method Type** | `POST` |
| **Method Header** | `Content-Type: application/json` |
| **Description** | Inquiry Data SPPT |

### Input Header

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| Token | Authorization: Bearer Token | | | 1 x 24 Hour |

### Input Parameter (Body Form-Data)

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| NOP | CHAR | 18 | 1 | NOP |
| TAHUN | CHAR | 4 | 1 | Tahun Pajak |

### Return Data

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| STATUS | VARCHAR | 100 | 1 | |
| MSG | CHAR | 100 | 1 | |
| NTPD | CHAR | 14 | 1 | |

### Response Sukses

```json
{
  "status": 200,
  "msg": "Pembayaran Sukses",
  "ntpd": "11062025187293"
}
```

### Response No Data

```json
{
  "status": 401,
  "msg": "Data tidak ditemukan"
}
```

---

## Reversal

| Item | Detail |
|------|--------|
| **Method URL** | `http://103.182.72.241:8000/pospbb/Api_pos/reversal` |
| **Method Type** | `POST` |
| **Method Header** | `Content-Type: application/json` |
| **Description** | Inquiry Data SPPT |

### Input Header

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| Token | Authorization: Bearer Token | | | 1 x 24 Hour |

### Input Parameter (Body Form-Data)

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| NOP | CHAR | 18 | 1 | Nomor Objek Pajak PBB |
| TAHUN | CHAR | 4 | 1 | Tahun Pajak |
| KETERANGAN | CHAR | 100 | 1 | |

### Return Data

| Parameter | Type | Length | Repeat | Description |
|-----------|------|--------|--------|-------------|
| STATUS | VARCHAR | 100 | 1 | |
| MSG | CHAR | 100 | 1 | |

### Response Sukses

```json
{
  "status": 200,
  "msg": "Pembayaran Sukses dibatalkan"
}
```

### Response No Data

```json
{
  "status": 401,
  "msg": "Data tidak ditemukan"
}
```
