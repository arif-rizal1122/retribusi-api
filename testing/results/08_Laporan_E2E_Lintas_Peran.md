# 🔄 Laporan Hasil Uji Coba Lintas Peran (E2E) Untuk Semua Jenis Pajak

**Waktu Eksekusi**: 2026-07-07 15:30:37
Pengujian E2E ini dilakukan secara otomatis (tanpa manual UI/Screenshot) dengan menstimulasi *Database Engine* Laravel secara langsung. Skenario yang diuji memastikan keutuhan proses: **Penerbitan SKPD (Admin) -> Integrasi Tagihan (Wajib Pajak) -> Pembayaran Lunas (Petugas)** berurutan.

### 🗂️ Pengujian Objek: Umum Lainnya (`W1-OTH`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Umum Lainnya` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1783438237-156` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1783438237-156 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBB-P2 (`PBB-P2`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBB-P2` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1783438237-186` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1783438237-186 menjadi Paid dan merilis SSPD).*

#### 🏦 Integrasi PBB Bapenda (2026 Spec)
- [x] Melakukan Inquiry NOP: `320100000000000101`
- [x] Sukses Bayar PBB. **NTPD Terbit**: `NTPDB9DDE413BB`
- [x] Verifikasi Tab Riwayat (Mobile): Transaksi terdeteksi.
**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: BPHTB (`BPHTB`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat BPHTB` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1783438237-187` senilai **Rp -4.000.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp -4.000.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1783438237-187 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Pajak Reklame (`REKLAME`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak Reklame` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1783438237-188` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1783438237-188 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Pajak MBLB (`MBLB`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak MBLB` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1783438237-189` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1783438237-189 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Pajak Walet (`WALET`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak Walet` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1783438237-190` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1783438237-190 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Opsen Pajak (`OPSEN`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Opsen Pajak` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1783438237-191` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1783438237-191 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT Makan/Minum (`PBJT-FOOD`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT Makan/Minum` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1783438237-192` senilai **Rp 50.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 50.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1783438237-192 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT Perhotelan (`PBJT-HTL`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT Perhotelan` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1783438237-193` senilai **Rp 50.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 50.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1783438237-193 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT Hiburan (`PBJT-HBR`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT Hiburan` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1783438237-194` senilai **Rp 50.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 50.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1783438237-194 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Hiburan Malam (`PBJT-HBR-SP`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Hiburan Malam` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1783438237-195` senilai **Rp 200.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 200.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1783438237-195 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Tenaga Listrik (`PBJT-PLN`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Tenaga Listrik` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1783438237-196` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1783438237-196 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Pajak Air Tanah (`AIR-TANAH`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak Air Tanah` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1783438237-197` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1783438237-197 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Persampahan (`RET-SMP`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Persampahan` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1783438237-198` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1783438237-198 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Parkir Tepi Jalan (`RET-PRK`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Parkir Tepi Jalan` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1783438237-199` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1783438237-199 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Sewa PKD (Lapak) (`RET-PKD`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Sewa PKD (Lapak)` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1783438237-200` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1783438237-200 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
