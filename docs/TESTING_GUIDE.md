# 🧪 Panduan Pengujian (Testing Guide)

Dokumen ini adalah acuan untuk menjalankan dan memahami pengujian sistem M-PAD.

## 1. Akun Acuan Testing
Pengujian menggunakan akun demo berikut (Data sudah ada di seeder/database):

| Peran (Role) | Kredensial (NIK/Email) | Kegunaan |
| :--- | :--- | :--- |
| **Super Admin** | `bapenda@baubaukota.go.id` | Pengujian CRUD Master Data & Laporan. |
| **Citizen (Wajib Pajak)**| `1234567890123456` | Pengujian Billing & Pelayanan Sipil. |
| **Petugas Lapangan** | (Dibuat otomatis via CRUD) | Pengujian Input Potensi & Pengawasan. |

*Catatan: Gunakan password `password123` untuk akun demo di atas.*

## 2. Inventori Script Testing
Script berada di folder `testing/` pada repo `retribusi-api`:

- **`test_production_ready.sh`**: 
  - *Fungsi:* Sanity check infrastruktur.
  - *Cakupan:* CORS, HTTPS, SSL, dan Health Path `/up`.
- **`test_api_crud.sh`**:
  - *Fungsi:* Validasi logika bisnis.
  - *Cakupan:* Pendaftaran warga, pembuatan zona, update retribusi, dan kalkulasi PBB.
- **`test_penetration.sh`**:
  - *Fungsi:* Audit keamanan.
  - *Cakupan:* Injeksi SQL, XSS, IDOR, dan kebocoran file sensitif.

## 3. Cara Menjalankan & Membaca Hasil
```bash
cd testing
chmod +x *.sh
./test_api_crud.sh
```
- **Warna Hijau (PASS):** Fitur berjalan sesuai spek.
- **Warna Merah (FAIL):** Ada kemungkinan bug atau database tidak sinkron.
- **Laporan:** Setiap pengujian menghasilkan file `.md` di `testing/results/` dengan timestamp lengkap.

## 4. Troubleshooting Testing
- **Error 401:** Token expired, script otomatis akan mencoba login ulang.
- **Error 422:** Validasi field kurang lengkap, cek `TargetContent` pada script.
- **Error 500:** Masalah permission atau syntax server (cek `MITIGATION_GUIDE.md`).

---
*Acuan Pengujian Terintegrasi. Terakhir diperbarui: 26 Februari 2026.*
