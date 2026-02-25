# Surveillance & TTE (Advanced - MITRA)

Modul ini digunakan oleh Pengawas dan Admin untuk memantau kepatuhan, audit trail, serta proses legalisasi dokumen secara digital (TTE).

## 1. Surveillance (Pengawasan)

### Audit Logs
Melihat riwayat aktifitas user di sistem.
- **URL**: `/pengawas/audit-logs`
- **Method**: `GET`

### Deteksi Anomali
Melihat Wajib Pajak atau tagihan yang diidentifikasi tidak wajar.
- **URL**: `/pengawas/anomalies`
- **Method**: `GET`

### Monitoring Penegakan (Enforcement)
- **List Notifikasi**: `/pengawas/enforcements` (Method: `GET`)
- **Approve Notifikasi**: `/pengawas/enforcements/{id}/approve` (Method: `POST`)

---

## 2. Tax Amnesty (Penghapusan Denda)

Digunakan untuk mengajukan atau menyetujui penghapusan denda administratif.
- **Ajukan Amnesty**: `/amnesty` (Method: `POST`)
- **Setujui/Tolak**: `/amnesty/{id}/approve` atau `/amnesty/{id}/reject` (Method: `POST`)

---

## 3. E-Registry & TTE

### Registrasi E-Doc
Melihat dokumen yang terdaftar di registry.
- **URL**: `/tte/documents`
- **Method**: `GET`

### Tanda Tangan Digital (Sign)
Melakukan proses TTE pada tagihan (Bill).
- **URL**: `/tte/sign`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "bill_id": 1,
    "passphrase": "your_secure_passphrase"
  }
  ```

### Verifikasi TTE (Public Access)
Memeriksa keabsahan tanda tangan digital pada dokumen.
- **URL**: `/tte/verify/{document_number}`
- **Method**: `GET`
