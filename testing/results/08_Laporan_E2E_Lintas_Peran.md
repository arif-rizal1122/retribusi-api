# 🔄 Laporan Hasil Uji Coba Lintas Peran (E2E) Untuk Semua Jenis Pajak

**Waktu Eksekusi**: 2026-04-13 01:32:42
Pengujian E2E ini dilakukan secara otomatis (tanpa manual UI/Screenshot) dengan menstimulasi *Database Engine* Laravel secara langsung. Skenario yang diuji memastikan keutuhan proses: **Penerbitan SKPD (Admin) -> Integrasi Tagihan (Wajib Pajak) -> Pembayaran Lunas (Petugas)** berurutan.

### 🗂️ Pengujian Objek: Retribusi Jasa Umum Lainnya (`W1-OTH`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Retribusi Jasa Umum Lainnya` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776043963-156` senilai **Rp 150.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 150.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776043963-156 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Jasa Parkir (`PBJT-PRK`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Jasa Parkir` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776043963-172` senilai **Rp 50.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 50.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776043963-172 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBB-P2 (`PBB-P2`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBB-P2` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776043963-186` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776043963-186 menjadi Paid dan merilis SSPD).*

#### 🏦 Integrasi PBB Bapenda (2026 Spec)
- [x] Melakukan Inquiry NOP: `320100000000138852`
- [x] Sukses Bayar PBB. **NTPD Terbit**: `NTPD3801D9F315`
- [x] Verifikasi Tab Riwayat (Mobile): Transaksi terdeteksi.
**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: BPHTB (`BPHTB`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat BPHTB` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776043963-187` senilai **Rp -3.975.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp -3.975.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776043963-187 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Pajak Reklame (`REKLAME`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak Reklame` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776043963-188` senilai **Rp 500.000.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 500.000.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776043963-188 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Pajak MBLB (`MBLB`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak MBLB` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776043963-189` senilai **Rp 7.500**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 7.500**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776043963-189 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Pajak Sarang Burung Walet (`WALET`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak Sarang Burung Walet` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776043963-190` senilai **Rp 5.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 5.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776043963-190 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Opsen Pajak (`OPSEN`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Opsen Pajak` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776043963-191` senilai **Rp 660**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 660**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776043963-191 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Makan dan Minum (`PBJT-FOOD`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Makan dan Minum` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776043963-192` senilai **Rp 50.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 50.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776043963-192 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Jasa Perhotelan (`PBJT-HTL`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Jasa Perhotelan` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776043963-193` senilai **Rp 50.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 50.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776043963-193 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Jasa Kesenian dan Hiburan (`PBJT-HBR`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Jasa Kesenian dan Hiburan` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776043963-194` senilai **Rp 50.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 50.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776043963-194 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Hiburan Malam (Khusus) (`PBJT-HBR-SP`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Hiburan Malam (Khusus)` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776043963-195` senilai **Rp 200.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 200.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776043963-195 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Tenaga Listrik (`PBJT-PLN`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Tenaga Listrik` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776043963-196` senilai **Rp 100**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 100**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776043963-196 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Pajak Air Tanah (`AIR-TANAH`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak Air Tanah` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776043963-197` senilai **Rp 80.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 80.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776043963-197 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Retribusi Persampahan (`RET-SMP`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Retribusi Persampahan` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776043963-198` senilai **Rp 1.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 1.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776043963-198 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Retribusi Pelayanan Parkir (`RET-PRK`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Retribusi Pelayanan Parkir` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776043963-199` senilai **Rp 1.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 1.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776043963-199 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Retribusi PKD (Kios/Pasar) (`RET-PKD`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Retribusi PKD (Kios/Pasar)` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776043963-200` senilai **Rp 1.000.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 1.000.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776043963-200 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
