spawn ssh -o StrictHostKeyChecking=no sipanda@157.10.252.74
sipanda@157.10.252.74's password: 
Welcome to Ubuntu 22.04.5 LTS (GNU/Linux 5.15.0-164-generic x86_64)

 * Panduan:  https://idcloudhost.com/panduan
 -------------------------------------------
 * Documentation:  https://help.ubuntu.com
 * Management:     https://landscape.canonical.com
 * Support:        https://ubuntu.com/advantage

 System information as of Wed Mar  4 09:43:59 UTC 2026

  System load:  1.0                Processes:             126
  Usage of /:   48.6% of 19.20GB   Users logged in:       0
  Memory usage: 35%                IPv4 address for ens3: 10.48.77.206
  Swap usage:   0%

 * Strictly confined Kubernetes makes edge and IoT secure. Learn how MicroK8s
   just raised the bar for easy, resilient and secure K8s cluster deployment.

   https://ubuntu.com/engage/secure-kubernetes-at-the-edge

Expanded Security Maintenance for Applications is not enabled.

11 updates can be applied immediately.
To see these additional updates run: apt list --upgradable

18 additional security updates can be applied with ESM Apps.
Learn more about enabling ESM Apps service at https://ubuntu.com/esm


The list of available updates is more than a week old.
To check for new updates run: sudo apt update

*** System restart required ***
Last login: Tue Apr 14 04:10:11 2026 from 180.251.145.115
-bash: /usr/lib/command-not-found: /usr/bin/python3: bad interpreter: No such file or directory
sipanda@sipanda:~$ <sipanda/retribusi-api && php artisan migrate:status

  Migration name .............................................. Batch / Status  
  0001_01_00_000000_create_opds_table ................................ [1] Ran  
  0001_01_01_000000_create_users_table ............................... [1] Ran  
  0001_01_01_000001_create_cache_table ............................... [1] Ran  
  0001_01_01_000002_create_jobs_table ................................ [1] Ran  
  2026_01_29_093218_create_personal_access_tokens_table .............. [1] Ran  
  2026_01_29_093248_create_retribution_types_table ................... [1] Ran  
  2026_01_29_100002_create_taxpayers_table ........................... [1] Ran  
  2026_01_29_100003_create_taxpayer_retribution_type_table ........... [1] Ran  
  2026_01_29_133048_create_verifications_table ....................... [1] Ran  
  2026_01_29_133442_create_zones_table ............................... [1] Ran  
  2026_01_29_161259_add_file_url_to_verifications_table .............. [1] Ran  
  2026_01_29_161259_add_image_url_to_opds_table ...................... [1] Ran  
  2026_01_29_171129_add_coordinates_to_zones_table ................... [1] Ran  
  2026_01_29_172055_add_password_to_taxpayers_table .................. [1] Ran  
  2026_01_29_173256_make_opd_id_nullable_in_taxpayers_table .......... [1] Ran  
  2026_01_30_011712_add_retribution_and_opd_to_zones_table ........... [1] Ran  
  2026_01_30_014508_create_tax_objects_table ......................... [1] Ran  
  2026_01_30_014510_create_bills_table ............................... [1] Ran  
  2026_01_30_014511_add_form_schema_to_retribution_types_table ....... [1] Ran  
  2026_01_30_014511_create_payments_table ............................ [1] Ran  
  2026_01_30_014608_add_tax_object_id_to_verifications_table ......... [1] Ran  
  2026_01_31_045352_create_retribution_classifications_table ......... [1] Ran  
  2026_01_31_045352_create_retribution_rates_table ................... [1] Ran  
  2026_01_31_045353_adjust_retribution_types_and_zones_for_hierarchy . [1] Ran  
  2026_01_31_085952_add_coordinates_to_zones_table ................... [1] Ran  
  2026_01_31_124442_add_metadata_to_taxpayers_table .................. [1] Ran  
  2026_01_31_180202_add_logo_url_to_opds_table ....................... [1] Ran  
  2026_02_01_034337_make_nik_nullable_in_taxpayers_table ............. [1] Ran  
  2026_02_01_184555_add_opd_id_to_bills_table ........................ [1] Ran  
  2026_02_01_195037_add_classification_to_taxpayer_pivot_table ....... [1] Ran  
  2026_02_01_195456_add_verification_to_payments_table ............... [1] Ran  
  2026_02_01_204400_create_object_verifications_table ................ [1] Ran  
  2026_02_02_000000_add_created_by_to_taxpayers_table ................ [1] Ran  
  2026_02_02_000000_add_icon_to_retribution_classifications_table .... [1] Ran  
  2026_02_02_000001_rename_kasir_to_petugas_in_users_table ........... [1] Ran  
  2026_02_02_081820_increase_nik_column_size_in_users_table .......... [1] Ran  
  2026_02_02_100000_create_user_retribution_assignments_table ........ [1] Ran  
  2026_02_02_100001_add_classification_to_bills_table ................ [1] Ran  
  2026_02_03_052305_move_schemas_to_classifications .................. [1] Ran  
  2026_02_12_060000_drop_amount_multiplier_from_zones ................ [1] Ran  
  2026_02_13_165347_add_calculation_formula_to_classifications_and_rates_table  [1] Ran  
  2026_02_14_122936_update_payments_table_for_virtual_ledger ......... [1] Ran  
  2026_02_14_154924_create_audit_logs_table .......................... [1] Ran  
  2026_02_14_154925_create_enforcement_notices_table ................. [1] Ran  
  2026_02_14_171300_add_audit_status_to_tax_objects_table ............ [1] Ran  
  2026_02_15_090058_add_gps_and_photo_to_enforcement_notices_table ... [1] Ran  
  2026_02_15_144101_add_penalty_columns_to_bills_table ............... [1] Ran  
  2026_02_15_144355_add_surcharge_to_bills_table ..................... [1] Ran  
  2026_02_15_162440_create_monthly_reports_table ..................... [1] Ran  
  2026_02_15_162602_add_self_assessment_to_classifications ........... [1] Ran  
  2026_02_15_165645_add_billing_cycle_to_retribution_types ........... [1] Ran  
  2026_02_15_200033_add_waived_penalty_amount_to_bills_table ......... [1] Ran  
  2026_02_15_200033_create_penalty_waivers_table ..................... [1] Ran  
  2026_02_15_201142_create_signed_documents_table .................... [1] Ran  
  2026_02_15_202332_add_classification_to_tax_objects_table .......... [1] Ran  
  2026_02_17_054520_create_incentive_tables .......................... [1] Ran  
  2026_02_17_142847_create_pbb_njop_classifications_table ............ [1] Ran  
  2026_02_19_060000_add_metadata_to_users_table ...................... [1] Ran  
  2026_02_20_025014_add_location_and_coordinates_to_taxpayers_table .. [1] Ran  
  2026_02_21_071646_add_spatial_data_to_zones_table .................. [1] Ran  
  2026_02_22_143428_add_assigned_to_to_enforcement_notices_table ..... [1] Ran  
  2026_02_23_100000_create_pbb_bapenda_tables ........................ [1] Ran  
  2026_02_25_061813_add_bank_accounts_to_retribution_classifications_table  [1] Ran  
  2026_02_25_182327_increase_billing_period_length_in_payments_table . [1] Ran  
  2026_02_26_094037_add_location_to_users_table ...................... [1] Ran  
  2026_02_26_144500_update_taxpayer_retribution_type_unique_key ...... [1] Ran  
  2026_03_09_031406_create_petugas_tasks_table ....................... [2] Ran  
  2026_03_09_033157_add_retribution_type_id_to_users_table ........... [3] Ran  
  2026_03_09_033345_create_spot_checks_table ......................... [4] Ran  
  2026_03_09_033355_create_spot_check_items_table .................... [4] Ran  
  2026_03_10_200730_add_completion_photo_path_to_petugas_tasks_table . [5] Ran  
  2026_03_10_200802_add_spot_check_id_to_bills_table ................. [6] Ran  
  2026_03_16_012444_update_tables_for_vtax_parity .................... [7] Ran  
  2026_03_16_012524_create_tax_transactions_table .................... [7] Ran  

sipanda@sipanda:~/retribusi-api$ php testing/run_role_e2e_test.php prod
Pengujian E2E Otomatis Selesai. Laporan ditulis ke /home/sipanda/retribusi-api/testing/results/08_Laporan_E2E_Lintas_Peran.md
sipanda@sipanda:~/retribusi-api$ <-api/testing/results/08_Laporan_E2E_Lintas_Peran.md
# ð Laporan Hasil Uji Coba Lintas Peran (E2E) Untuk Semua Jenis Pajak

**Waktu Eksekusi**: 2026-04-14 04:10:29
Pengujian E2E ini dilakukan secara otomatis (tanpa manual UI/Screenshot) dengan menstimulasi *Database Engine* Laravel secara langsung. Skenario yang diuji memastikan keutuhan proses: **Penerbitan SKPD (Admin) -> Integrasi Tagihan (Wajib Pajak) -> Pembayaran Lunas (Petugas)** berurutan.

### ð️ Pengujian Objek: Retribusi Jasa Umum Lainnya (`W1-OTH`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Retribusi Jasa Umum Lainnya` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776139830-156` senilai **Rp 150.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 150.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776139830-156 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### ð️ Pengujian Objek: PBJT - Jasa Parkir (`PBJT-PRK`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Jasa Parkir` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776139831-172` senilai **Rp 500.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 500.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776139831-172 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### ð️ Pengujian Objek: PBB-P2 (`PBB-P2`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBB-P2` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776139831-186` senilai **Rp 0**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 0**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776139831-186 menjadi Paid dan merilis SSPD).*

#### ð¦ Integrasi PBB Bapenda (2026 Spec)
- [x] Melakukan Inquiry NOP: `320100000000138817`
- [x] Sukses Bayar PBB. **NTPD Terbit**: `NTPDDCA0468F4E`
- [x] Verifikasi Tab Riwayat (Mobile): Transaksi terdeteksi.
**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### ð️ Pengujian Objek: BPHTB (`BPHTB`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat BPHTB` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776139831-187` senilai **Rp -3.750.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp -3.750.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776139831-187 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### ð️ Pengujian Objek: Pajak Reklame (`REKLAME`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak Reklame` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776139831-188` senilai **Rp 1.687.500.000.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 1.687.500.000.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776139831-188 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### ð️ Pengujian Objek: Pajak MBLB (`MBLB`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak MBLB` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776139832-189` senilai **Rp 112.500**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 112.500**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776139832-189 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### ð️ Pengujian Objek: Pajak Sarang Burung Walet (`WALET`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak Sarang Burung Walet` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776139832-190` senilai **Rp 75.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 75.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776139832-190 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### ð️ Pengujian Objek: Opsen Pajak (`OPSEN`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Opsen Pajak` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776139832-191` senilai **Rp 9.900**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 9.900**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776139832-191 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### ð️ Pengujian Objek: PBJT - Makan dan Minum (`PBJT-FOOD`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Makan dan Minum` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776139832-192` senilai **Rp 500.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 500.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776139832-192 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### ð️ Pengujian Objek: PBJT - Jasa Perhotelan (`PBJT-HTL`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Jasa Perhotelan` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776139833-193` senilai **Rp 500.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 500.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776139833-193 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### ð️ Pengujian Objek: PBJT - Jasa Kesenian dan Hiburan (`PBJT-HBR`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Jasa Kesenian dan Hiburan` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776139833-194` senilai **Rp 500.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 500.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776139833-194 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### ð️ Pengujian Objek: Hiburan Malam (Khusus) (`PBJT-HBR-SP`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Hiburan Malam (Khusus)` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776139833-195` senilai **Rp 2.000.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 2.000.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776139833-195 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### ð️ Pengujian Objek: PBJT - Tenaga Listrik (`PBJT-PLN`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat PBJT - Tenaga Listrik` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776139833-196` senilai **Rp 1.500**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 1.500**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776139833-196 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### ð️ Pengujian Objek: Pajak Air Tanah (`AIR-TANAH`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Pajak Air Tanah` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776139833-197` senilai **Rp 800.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 800.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776139833-197 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### ð️ Pengujian Objek: Retribusi Persampahan (`RET-SMP`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Retribusi Persampahan` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776139833-198` senilai **Rp 15.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 15.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776139833-198 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### ð️ Pengujian Objek: Retribusi Pelayanan Parkir (`RET-PRK`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Retribusi Pelayanan Parkir` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776139833-199` senilai **Rp 15.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 15.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776139833-199 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
### ð️ Pengujian Objek: Retribusi PKD (Kios/Pasar) (`RET-PKD`)
**A. Aktor: Admin Bapenda (`admin@retribusi.id`)**
- [x] Sukses mendaftarkan Objek Pajak: `Usaha Dummy Cepat Retribusi PKD (Kios/Pasar)` atas nama WP otomatis.
- [x] Sukses menerbitkan SKPD Nomor: `SKPD-TEST-1776139833-200` senilai **Rp 225.000.000**.
**B. Aktor: Wajib Pajak (``)**
- [x] Database Sync: Wajib Pajak (via Mobile) mendeteksi adanya piutang tagihan ini dengan status **Belum Lunas (Unpaid)**.
**C. Aktor: Petugas Lapangan (`petugas@demo.id`)**
- [x] Petugas mencari tagihan dan menekan tombol Konfirmasi Tunai senilai **Rp 225.000.000**.
*(Sistem otomatis memutasi status SKPD SKPD-TEST-1776139833-200 menjadi Paid dan merilis SSPD).*

**✅ HASIL UJI E2E: SELARAS DAN LULUS SEMPURNA**

---
sipanda@sipanda:~/retribusi-api$ exit
logout
Connection to 157.10.252.74 closed.
