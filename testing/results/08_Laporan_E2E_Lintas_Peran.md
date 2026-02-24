# 🔄 Laporan Hasil Uji Coba Lintas Peran (E2E) Untuk Semua Jenis Pajak

**Waktu Eksekusi**: 2026-02-24 14:50:44
Pengujian E2E ini dilakukan secara otomatis (tanpa manual UI/Screenshot) dengan menstimulasi *Database Engine* Laravel secara langsung. Skenario yang diuji memastikan keutuhan proses: **Penerbitan SKPD (Admin) -> Integrasi Tagihan (Wajib Pajak) -> Pembayaran Lunas (Petugas)** berurutan.

### 🗂️ Pengujian Objek: PBB-P2 (`PBB-UMUM`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBB-P2` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944644-1` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944644-1 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: BPHTB (`BPHTB`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat BPHTB` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944644-2` senilai **Rp 200.000**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944644-2 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Pajak Reklame (`REKLAME`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak Reklame` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944644-3` senilai **Rp 1.250.000**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944644-3 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Pajak Sarang Burung Walet (`WALET`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak Sarang Burung Walet` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944644-4` senilai **Rp 500.000**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944644-4 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Pajak MBLB (`MBLB`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak MBLB` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944644-5` senilai **Rp 600.000**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944644-5 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Makan dan Minum (`PBJT-MNM`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Makan dan Minum` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944644-8` senilai **Rp 500.000**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944644-8 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Jasa Catering (`PBJT-CAT`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Jasa Catering` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944644-9` senilai **Rp 500.000**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944644-9 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Jasa Event/Hiburan Lainnya (`PBJT-EVT`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Jasa Event/Hiburan Lainnya` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944644-10` senilai **Rp 500.000**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944644-10 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Tenaga Listrik (`PBJT-LIS`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Tenaga Listrik` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944644-11` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944644-11 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Jasa Perhotelan (`PBJT-HTL`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Jasa Perhotelan` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944644-12` senilai **Rp 500.000**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944644-12 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Jasa Parkir (`PBJT-PRK`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Jasa Parkir` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944644-13` senilai **Rp 500.000**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944644-13 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: PBJT - Jasa Kesenian dan Hiburan (`PBJT-HBR`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Jasa Kesenian dan Hiburan` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944644-14` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944644-14 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Pajak Air Tanah (`PAT`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak Air Tanah` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944644-15` senilai **Rp 800.000**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944644-15 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Penyediaan Tempat Kegiatan Usaha (`PTKU`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Penyediaan Tempat Kegiatan Usaha` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944644-16` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944644-16 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Retribusi Jasa Umum (`RJU`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Retribusi Jasa Umum` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944644-17` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944644-17 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Retribusi Perizinan Tertentu (`RPT`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Retribusi Perizinan Tertentu` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944644-18` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944644-18 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Persetujuan Bangunan Gedung (PBG) (`PBG`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Persetujuan Bangunan Gedung (PBG)` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944644-19` senilai **Rp 556.000.000**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944644-19 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Opsen PKB (`OPS-PKB`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Opsen PKB` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944645-23` senilai **Rp 9.900**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944645-23 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### 🗂️ Pengujian Objek: Opsen BBNKB (`OPS-BBN`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Opsen BBNKB` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1771944645-24` senilai **Rp 9.900**.
**B. Aktor: Wajib Pajak (`wp.otomatis@sipanda.com`)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@bapenda.go.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1771944645-24 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
