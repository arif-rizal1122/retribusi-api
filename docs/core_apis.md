# Core APIs (Sipanda)

Modul ini menangani pengelolaan entitas bisnis inti: Wajib Pajak, Objek Pajak, Penagihan (Billing), dan Pembayaran. Kebanyakan endpoint ini memerlukan **Bearer Token**.

## 1. Wajib Pajak (Taxpayers)

### List Wajib Pajak
- **URL**: `/taxpayers`
- **Method**: `GET`
- **Filter**: `?search=name_or_nik`

### Cari Wajib Pajak (NIK)
Digunakan untuk pencarian cepat saat pendaftaran objek baru.
- **URL**: `/taxpayers/search/{nik}`
- **Method**: `GET`

---

## 2. Objek Pajak (Tax Objects)

### List Objek Pajak
- **URL**: `/tax-objects`
- **Method**: `GET`

---

## 3. Billing & Penagihan

### Buat Tagihan (Entry Data)
- **URL**: `/bills`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "tax_object_id": 1,
    "amount": 500000,
    "period_month": 3,
    "period_year": 2026,
    "notes": "Tagihan Maret"
  }
  ```

### Export Dokumen (PDF)
- **SKRD**: `/bills/{id}/skrd`
- **SSPD**: `/bills/{id}/sspd`
- **SPPT (PBB)**: `/bills/{id}/sppt`
- **Method**: `GET`

---

## 4. Pembayaran (Payments)

### Konfirmasi Pembayaran
- **URL**: `/payments`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "bill_id": 101,
    "payment_method": "cash",
    "amount_paid": 500000
  }
  ```

### Cek Tunggakan (Mobile)
- **URL**: `/citizen/services/pending-periods`
- **Method**: `GET`
