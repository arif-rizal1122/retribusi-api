# MITIGASI KEGAGALAN SISTEMIK & PENCEGAHAN 5 SKENARIO FATAL
## Panduan Arsitektur Ketahanan Transaksi & Pencegahan Risiko Operasional M-PAD Kota Baubau

---

## 1. Pendahuluan
Berdasarkan dokumen teknis *source code*, temuan pengujian logika produksi, panduan SLA perbankan Bank Indonesia, dan matriks risiko M-PAD, dokumen ini merumuskan mitigasi komprehensif terhadap potensi kegagalan sistemik dan mengunci 5 skenario fatal operasional di lapangan.

---

## 2. Mitigasi Kegagalan Sistemik (Arsitektur & Jaringan)

### A. Idempotency Key pada Webhook Callback per Bank
* **Masalah**: Callback berulang dari bank (*retry storm*) akibat keterlambatan respons HTTP 200 jaringan kosmetik dapat memicu pencatatan ganda jurnal penerimaan kas daerah (akun 4.1.x).
* **Solusi Baku**:
  1. Pasangan kunci unik `bank_id + transaction_id` dijadikan *Idempotency Key* di basis data.
  2. Eksekusi pembaruan status pelunasan wajib menggunakan transaksi berantai dengan `lockForUpdate()`.
  3. Jika record tagihan sudah berstatus *Paid*, sistem langsung mengembalikan status sukses (200) tanpa mengeksekusi ulang fungsi pembukuan kas.

### B. Asynchronous Message Broker (Pemisahan Core Payment vs Dokumen TTE)
* **Masalah**: Lonjakan ribuan transaksi serentak pada puncak jatuh tempo PBB/PBJT dapat menyebabkan crash server jika kalkulasi denda dan *Auto-TTE Hook* ke API BSrE dilakukan secara sinkron.
* **Solusi Baku**:
  1. Callback pembayaran dari bank direspons sukses instan (< 1.5 detik) segera setelah saldo terkonfirmasi di rekening penampungan.
  2. Proses rendering PDF SKRD/bukti bayar bertanda tangan elektronik (TTE BSrE) dan pengiriman notifikasi WhatsApp dialihkan ke *Background Worker Queue* (Redis/Database Queue) secara asinkron.

### C. Failover Offline-First Mode pada Aplikasi Lapangan (PWA Local Cache)
* **Masalah**: Petugas lapangan atau juru parkir di zona *blank spot* (pesisir pantai atau pasar tradisional) mengalami *force close* atau hilangnya data isian formulir dan foto GPS.
* **Solusi Baku**:
  1. Memanfaatkan Service Worker dan IndexedDB di aplikasi `retribusi-petugas`.
  2. Semua isian formulir, foto kamera, dan geotag GPS disimpan lokal terlebih dahulu dengan status `pending_sync`.
  3. Sinkronisasi otomatis ke server pusat dieksekusi saat koneksi internet terdeteksi pulih.

---

## 3. Pencegahan 5 Skenario Fatal Operasional di Lapangan

```
[🚨 GANGGUAN SISTEMIK YANG WAJIB DIHINDARI DI LAPANGAN]

Skenario 1: Kebocoran Data NIK Warga ke Struk Publik (Pelanggaran UU PDP)
Skenario 2: Data Latency di Mode Offline Kalkulator Bisnis (Gagal Debet Rekening)
Skenario 3: Pemetaan Akun Pajak Hard-Coded (Temuan Selisih Audit BPK)
Skenario 4: Kegagalan Produksi Akibat Konfigurasi Flag Salah (Total Outage VA)
Skenario 5: Pengaduan Terbengkalai Melewati Batas SLA Bank Indonesia (>60 Hari)
```

### Skenario 1: Kebocoran Data NIK Warga ke Struk Publik (Pelanggaran UU PDP No. 27/2022)
* **Risiko**: Menyusun nomor Virtual Account bank dengan menempelkan 16 digit NIK warga secara mentah (`19960 + 16 digit NIK`). NIK termasuk data pribadi spesifik dan akan tercetak di kuitansi kertas, layar ATM, atau mutasi perbankan yang dapat diakses publik.
* **Pola Benar**: **Skema Hybrid**. Nomor VA dikunci menggunakan ID Transaksi unik berbasis database (primary key acak 7–10 digit). Relasi NIK warga tetap tersimpan dengan enkripsi AES-256 di memori server internal Bapenda yang terlindungi.

### Skenario 2: Penundaan Eksekusi AFT (Automatic Fund Transfer) pada Kalkulator Bisnis
* **Risiko**: Jeda waktu (*timing risk*) antara masuknya dana konsumen di rekening pedagang dengan eksekusi pemotongan komponen pajak 10% terlampau lama (misal mingguan). Pedagang dapat menghabiskan saldo titipan pajak untuk kebutuhan modal kerja harian, memicu kegagalan autodebet massal saat saldo kosong.
* **Pola Benar**: Perintah AFT oleh Bank Sultra wajib dieksekusi secara **real-time dalam hitungan detik** setelah notifikasi pembayaran QRIS/Transfer sukses terkonfirmasi perbankan.

### Skenario 3: Penjurnalan Pendapatan 1-Lapis Langsung ke Rekening Kas Daerah (Kasda)
* **Risiko**: Mencatat dana pembayaran VA langsung didebit ke akun Kas Daerah pada detik pertama pembayaran dilakukan. Hal ini memicu temuan audit oleh BPK RI karena fisik uangnya secara riil masih mengendap di rekening penampungan (*escrow account*) bank dan baru ditransfer secara akumulatif ke Kasda pada akhir hari kerja (settlement harian).
* **Pola Benar**: **Jurnal 2-Tahap**:
  - *Jurnal 1 (Real-time saat pembayaran)*: Debit Rekening Penampungan Bank & Kredit Pendapatan PAD.
  - *Jurnal 2 (Batch harian pukul 17.00 WITA)*: Debit Kas Daerah & Kredit Rekening Penampungan Bank.

### Skenario 4: Membiarkan Flag Konfigurasi Environment Produksi Salah Terpasang
* **Risiko**: Variabel vital seperti panjang nomor VA (`customer_no_length`) default ke 20 digit di server produksi, menghasilkan nomor VA sepanjang 25 digit yang ditolak oleh jaringan perbankan (*Total Outage* pembayaran warga).
* **Pola Benar**: Validasi *pre-flight check* ketat pada `.env` produksi. Panjang nomor VA dikunci tepat 15 digit (8 digit *partnerServiceId* + 7 digit *customerNo* acak).

### Skenario 5: Mengabaikan Pengaduan Sanggan Konsumen Lewat dari 60 Hari Kalender
* **Risiko**: Mengabaikan keluhan transaksi warga (misal saldo terpotong tapi status di sistem belum lunas). Berdasarkan Ketentuan Perlindungan Konsumen Bank Indonesia, jika penyelesaian pengaduan melewati batas 60 hari kalender sejak tanggal transaksi, tanggung jawab ganti rugi gugur dari bank dan sepenuhnya dibebankan kepada kas pemerintah daerah.
* **Pola Benar**: Otomasi *Auto-Ticketing System*. Setiap aduan kegagalan pembayaran wajib menghasilkan tiket pengaduan instan yang diteruskan via WhatsApp ke admin OPD pengampu dengan target resolusi SLA maksimal **20 hari kerja**.
