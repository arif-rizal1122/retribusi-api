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
Last login: Tue Apr 14 04:09:05 2026 from 180.251.145.115
-bash: /usr/lib/command-not-found: /usr/bin/python3: bad interpreter: No such file or directory
sipanda@sipanda:~$ <st --json > testing/results/C2_Route_Inventory.json
sipanda@sipanda:~/retribusi-api$ </testing/results/07_Hasil_Kalkulator_Semua_Pajak.md
# ð§® Laporan Hasil Uji Otomatis API Kalkulator Pajak

**Waktu Eksekusi**: 2026-04-14 04:08:42
Pengujian dieksekusi secara otomatis menembak server `Localhost:8000` via endpoint POST `/api/simulate-tax` untuk masing-masing klasifikasi.

⚠️ **Error**: Tidak dapat mengambil daftar formula dari API. Pastikan server berjalan di port 8000.
sipanda@sipanda:~/retribusi-api$ php testing/run_rbac_test.php
Pengujian RBAC Selesai. Laporan ditulis ke /home/sipanda/retribusi-api/testing/results/09_Laporan_Keamanan_RBAC.md
sipanda@sipanda:~/retribusi-api$ <usi-api/testing/results/09_Laporan_Keamanan_RBAC.md
# ð¡️ Laporan Hasil Uji Coba Keamanan Akses (RBAC)

**Waktu Eksekusi**: 2026-04-14 04:09:19
Pengujian ini menembak API lokal menggunakan Token Sanctum murni untuk membuktikan Sistem Isolasi Peran (Tenant Isolation & Authorization) berjalan sempurna.

### 1. Wajib Pajak Mengakses Endpoint Admin
- ❌ **KEBOCORAN**: Server mengembalikan status HTTP `0` bukannya 403.

### 2. Tamu (Tanpa Token) Mengakses Endpoint Terkunci
- ❌ **KEBOCORAN**: Endpoint bocor, HTTP `0`.

### 3. Petugas Lapangan Melakukan Aksi Destruktif (DELETE Tagihan/Objek)
- ❌ **KEBOCORAN**: Sistem membiarkan aksi selain 403 (HTTP 0).

sipanda@sipanda:~/retribusi-api$ exit
logout
Connection to 157.10.252.74 closed.
