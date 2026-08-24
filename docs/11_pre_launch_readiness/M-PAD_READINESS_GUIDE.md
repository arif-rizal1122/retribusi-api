# 🚀 M-PAD Pre-Launch Readiness Guide
*(Dokumen Serah Terima & Catatan Tim Pengembang)*

Dokumen ini adalah **Catatan Teknis Resmi** yang menjabarkan daftar pekerjaan esensial (*mandatory*) yang harus dieksekusi oleh tim pengembang **sembari menunggu akses API Key / Sandbox dari Bank Mitra (H2H)**. Jika seluruh poin di bawah ini terpenuhi, maka sistem M-PAD dinyatakan 100% *Production-Ready* dan siap *Go-Live* kapan saja *Sandbox* dibuka.

> [!WARNING]
> **Kondisi Saat Ini (Telah Dieksekusi)**
> - Antarmuka *E-Wallet / Saldo* di `retribusi-mobile` telah **DIBERSIHKAN**. Aplikasi warga kini mengarah 100% ke *Direct Payment* (Tanpa Uang Elektronik).
> - Antarmuka *Login* warga telah diubah menjadi berbasis **NIK & Nomor WhatsApp**.
> - Fitur Lapor GPS (Warga) dan *Searchable Dropdown* (Petugas) sudah terpasang.

---

## 🛠️ DAFTAR TUGAS TIM PENGEMBANG (BACKEND & ADMIN)

Berikut adalah cetak biru teknis yang **harus** dilanjutkan oleh tim *Backend* dan *Admin*:

### 1. Sinkronisasi Modul Identitas (WA Gateway & OTP)
Karena *Frontend* sudah dirombak menggunakan OTP, tim *Backend* wajib mengintegrasikan API dengan Node.js Baileys:
- **Tugas API:** Buat `App\Services\WaGatewayService` yang melakukan POST ke `http://localhost:3001/send-message`.
- **Tugas Auth:** Ubah `AuthController.php`. Hilangkan validasi *password*. Saat NIK dipanggil, buat 6-digit angka *random*, simpan di *Cache* / *Database* (dengan `expires_at` 5 menit), lalu tembak melalui WA Gateway.
- **Tugas Dukcapil:** Pastikan validasi NIK tersambung ke `IdentityValidationService` agar NIK fiktif tidak bisa menerima OTP.

### 2. Modul Agregasi PBB-P2 (Primadona PAD)
M-PAD tidak akan lengkap jika PBB tidak masuk ke dalam satu pintu:
- **Database:** Eksekusi migrasi tabel `user_nops` (Relasi: `user_id` -> banyak `nop`).
- **Endpoint:** Buat rute `POST /api/citizen/nops/link` untuk mengecek NOP ke *database* Bapenda dan menautkannya ke profil warga.
- **Billing Service:** Pastikan kelas penagihan menggabungkan data `retribution_bills` dan data tunggakan PBB dari NOP yang tersambung saat warga membuka *Dashboard* mereka.

### 3. Tunggakan Parsial (*Checkout List*)
Warga seringkali hanya sanggup membayar tunggakan beberapa tahun terakhir saja.
- **Tugas Mobile:** Ubah UI `Tagihan.tsx` menggunakan komponen *Checkbox*. Kirimkan dalam bentuk *Array* JSON `{"bill_ids": [12, 13]}` saat *checkout*.
- **Tugas API:** Ubah `BillController` untuk tidak menghitung *total tagihan keseluruhan*, melainkan melakukan iterasi (SUM) hanya pada `id` yang dikirim dalam *array*.

### 4. Administrasi Mutasi Objek Pajak (Peralihan Aset)
Integritas data kepemilikan sangat krusial agar tidak ada salah tagih (*Omni-Sync Protocol*).
- **Tugas Admin:** Buat panel "Mutasi Wajib Pajak" di Web Vue/React Admin.
- **Tugas API (`MutationController`):**
  - Ubah *foreign key* `user_id` dari Objek Pajak (`tax_objects`) dari pengguna Lama ke Baru.
  - **CRITICAL (Sapu Bersih):** Batalkan (`void`) seluruh tugas inspeksi (*notices*) Petugas Lapangan untuk Wajib Pajak lama.
  - Hapus tagihan tertunggak dari *Dashboard* Mobile warga lama, lalu *assign* tagihan tersebut ke warga baru.

### 5. Buku Rekap Keuangan Bapenda (LRA Real-Time)
Kepala Bapenda membutuhkan *Dashboard* penerimaan sebelum bank melakukan *End-of-Day (EOD)* rekonsiliasi.
- **Tugas API:** Kembangkan kueri analitik (GROUP BY `retribution_types.name`, `DATE(payments.created_at)`) di `AnalyticsController` untuk menghitung uang yang sudah berstatus `Success`.
- **Tugas Admin:** Buat grafik Visual / Tabel Harian yang mengekspor data ini ke format PDF/Excel.

---

## 🔒 INFRASTRUKTUR & PERSIAPAN SANDBOX BANK

Ketika pihak Bank (BRI/Sultra/Mandiri) menelepon untuk memberikan akses *Sandbox*, pastikan hal-hal berikut sudah ada di tangan administrator VPS:

> [!IMPORTANT]
> 1. **IP Publik Statis (Production & Staging):** Siapkan IP VPS (Misal Biznet Neo) untuk didaftarkan ke sistem *Whitelisting* Bank. Jika IP berubah, API Bank akan langsung me- *reject* koneksi (Error 401).
> 2. **Signature BRIVA BRI:** Dokumentasi BRIVA Online v2.0 yang menjadi acuan M-PAD mencantumkan `X-SIGNATURE` HMAC-SHA512. Simpan shared secret hanya di secret manager atau `.env` server, dan konfirmasi canonical string serta encoding signature kepada tim IT BRI. Partner SNAP lain dapat memiliki mekanisme key yang berbeda.
> 3. **Callback / Webhook URL:** Siapkan domain publik (bukan *localhost* atau IP) yang sudah bersertifikat SSL (HTTPS) valid untuk rute `POST /snap/v1.0/transfer-va/payment`. Bank tidak akan mau menembak ke URL HTTP biasa.
