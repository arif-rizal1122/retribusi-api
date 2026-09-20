# 📝 NOTULENSI & RINGKASAN PEMBAHASAN INTEGRASI BSRE - M-PAD
## Rapat Koordinasi Teknis Pra-Integrasi & Persiapan Uji Kesesuaian Sistem TTE
### Pemerintah Kota Baubau & Balai Sertifikasi Elektronik (BSrE - BSSN)

- **Hari / Tanggal**: Minggu, 20 September 2026 (Sinkronisasi Slide & Transkrip Resmi)
- **Rujukan Materi**: Slide Resmi BSrE BSSN & Transkrip Konsultasi Integrasi M-PAD
- **Agenda Utama**:
  1. Penjelasan 4 Tahapan Integrasi Layanan Sertifikat Elektronik BSrE
  2. Standarisasi Kebutuhan Infrastruktur Server Modul Esign Client (Ubuntu 24 LTS, Isolasi Non-Publik)
  3. Konfigurasi Jaringan & Whitelist Egress Internet Port 443 ke Domain BSSN
  4. Pemanfaatan Fitur-Fitur Modul Esign Client v2.3.3 pada Sistem M-PAD
  5. Evaluasi Kepatuhan atas 11 Standar Kesiapan Aplikasi (Kriteria Pengujian Sistem)
  6. Prosedur Reservasi Online & Pengisian Formulir Permohonan Ber-TTE

---

## 1. Rangkuman Poin Pembahasan Utama

### A. 4 Tahapan Siklus Integrasi BSrE ([`02_tahapan_integrasi_bsre.jpeg`](./02_tahapan_integrasi_bsre.jpeg))
Pihak BSrE menegaskan bahwa proses integrasi wajib melalui 4 tahapan resmi secara berurutan:
1. **Konsultasi Pra-Integrasi**: Penyelarasan proses bisnis, analisis kebutuhan, dan pemetaan jenis sertifikat (Segel Elektronik vs Personal TTE). *(Memerlukan reservasi & surat permohonan)*.
2. **Instalasi Modul**: Penyiapan server lokal oleh Pemkot Baubau dan permohonan modul Esign Client. *(Khusus instalasi modul tidak perlu surat permohonan, melainkan cukup mengunggah Formulir Permohonan Instalasi Modul yang ditandatangani TTE pejabat instansi)*.
3. **Bimbingan Teknis (Bimtek) Integrasi**: Asistensi penghubungan REST API antara sistem M-PAD dengan modul Esign Client di lingkungan Sandbox/Development. *(Memerlukan reservasi & surat permohonan)*.
4. **Uji Penerapan Modul (Uji Kesesuaian Sistem)**: Pengujian formal terhadap **11 Standar Kesiapan Sistem** di hadapan tim penguji BSrE. *(Memerlukan reservasi & surat permohonan)*.

> *Keluaran Akhir: Setelah lulus tahapan Uji Penerapan Modul, BSrE BSSN akan menerbitkan **Surat Pengesahan Sistem** resmi bagi aplikasi M-PAD Kota Baubau.*

---

### B. Spesifikasi Server & Keamanan Jaringan Esign Client ([`03_spesifikasi_server_esign_client.jpeg`](./03_spesifikasi_server_esign_client.jpeg))
- **Sistem Operasi**: Wajib Ubuntu 24.04 LTS (Minimal Server).
- **Spesifikasi Fisik**: Minimal 4 Core vCPU, 8 GB RAM, 50 GB Storage.
- **Port Koneksi**: Port 80, 443, dan 22.
- **Prinsip Keamanan Isolasi Jaringan**:
  - Server Esign Client **TIDAK UNTUK DIBUKA KE PUBLIK (INTERNET)**.
  - Server aplikasi (`retribusi-api`) harus mengakses Server Esign Client **SECARA LOKAL (JARINGAN PRIVAT/LAN)**.

---

### C. Whitelist Egress Jaringan Internet BSSN ([`04_whitelist_koneksi_internet_bssn.jpeg`](./04_whitelist_koneksi_internet_bssn.jpeg))
Firewall Diskominfo Kota Baubau wajib membuka koneksi keluar (outbound HTTPS port 443) ke 4 domain resmi BSSN:
- `https://api-bsre.bssn.go.id` (Gateway Production)
- `https://osd-dev.bssn.go.id` (Gateway Development / Sandbox)
- `https://registry-bsre.bssn.go.id` (Direktori Sertifikat & CRL BSSN)
- `https://mail.bssn.go.id` (Sistem Notifikasi Email & OTP)

---

### D. Skema Integrasi & Pengiriman Hash ([`05_skema_integrasi_esign_client.jpeg`](./05_skema_integrasi_esign_client.jpeg))
- Aplikasi M-PAD mengirimkan berkas PDF ke modul Esign Client lokal.
- Modul Esign Client menghitung nilai intisari digital (*Hash Value*) dari berkas PDF.
- Nilai **Hash Value** (bukan isi berkas PDF) yang dikirimkan oleh Esign Client ke Server Signing BSSN.
- **Keamanan Data**: Server BSrE/BSSN tidak menyimpan dan tidak mengetahui isi naskah dokumen keuangan/perpajakan daerah Kota Baubau.

---

### E. Fitur-Fitur Modul Esign Client v2.3.3 ([`06_fitur_esign_client_v2.3.3.jpeg`](./06_fitur_esign_client_v2.3.3.jpeg))
1. **Segel Elektronik (Organisasi)**: Digunakan untuk settlement otomatis 24/7 dokumen pelunasan pendapatan daerah (`e-SSPD`, `e-SSRD`, `e-STTS`) tanpa input passphrase per lembar transaksi.
2. **Bulking Signing**: Fitur penandatanganan massal untuk ribuan berkas `e-SPPT` PBB-P2 tahunan secara simultan.
3. **Penggunaan OTP pada Tanda Tangan**: Lapisan 2FA untuk dokumen ketetapan bernilai besar (SKPDKB).
4. **Tanda Tangan PDF Ber-Password**: Penandatanganan berkas PDF terenkripsi tanpa merusak struktur password aslinya.
5. **Pemeriksaan Kedaluwarsa Sertifikat**: Cek otomatis masa berlaku sertifikat pejabat sebelum penandatanganan dijalankan.

---

### F. Ringkasan 11 Standar Kesiapan Aplikasi
Pihak BSrE memaparkan 11 kriteria yang diuji langsung saat Uji Penerapan Modul:
1. **Informasi Dokumen Keluaran Aplikasi** ([`07_syarat_01_informasi_dokumen_keluaran.jpeg`](./07_syarat_01_informasi_dokumen_keluaran.jpeg)): Memuat teks klausul legalitas baku BSrE BSSN.
2. **Pemisahan Akun Pengguna** ([`08_syarat_02_pemisahan_akun_pengguna.jpeg`](./08_syarat_02_pemisahan_akun_pengguna.jpeg)): Akun perorangan, dilarang *shared account*.
3. **Form Passphrase** ([`09_syarat_03_form_passphrase.jpeg`](./09_syarat_03_form_passphrase.jpeg)): Form input passphrase pada halaman penandatanganan.
4. **Nonaktifkan Fitur Auto Complete** ([`10_syarat_04_nonaktifkan_autocomplete_passphrase.jpeg`](./10_syarat_04_nonaktifkan_autocomplete_passphrase.jpeg)): Menonaktifkan autocomplete/autofill browser pada form passphrase.
5. **Tidak Menyimpan Passphrase** ([`11_syarat_05_dilarang_simpan_passphrase.jpeg`](./11_syarat_05_dilarang_simpan_passphrase.jpeg)): Dilarang menyimpan passphrase pada database, session, cookie, atau aplikasi.
6. **Pemasangan SSL / HTTPS** ([`12_syarat_06_pemasangan_ssl_https.jpeg`](./12_syarat_06_pemasangan_ssl_https.jpeg)): Aplikasi wajib menggunakan sertifikat SSL/TLS valid.
7. **Notifikasi Berhasil** ([`13_syarat_07_notifikasi_penandatanganan_berhasil.jpeg`](./13_syarat_07_notifikasi_penandatanganan_berhasil.jpeg)): Menampilkan pop-up sukses dan pencatatan latensi milidetik (ms).
8. **Notifikasi Gagal** ([`14_syarat_08_notifikasi_penandatanganan_gagal.jpeg`](./14_syarat_08_notifikasi_penandatanganan_gagal.jpeg)): Menampilkan pop-up error disertai status/alasan kegagalan.
9. **Perekaman Log Audit Trail** ([`15_syarat_09_perekaman_log_audit_trail.jpeg`](./15_syarat_09_perekaman_log_audit_trail.jpeg)): Merekam detail log proses signing ke tabel database (`id`, `message`, `remote_addr`, `user_agent`, `created_at`).
10. **Perubahan Status Dokumen** ([`16_syarat_10_perubahan_status_dokumen.jpeg`](./16_syarat_10_perubahan_status_dokumen.jpeg)): Transisi siklus status dokumen yang jelas sebelum dan sesudah penandatanganan.
11. **Validitas Dokumen PDF** ([`17_syarat_11_validitas_tanda_tangan_pdf.jpeg`](./17_syarat_11_validitas_tanda_tangan_pdf.jpeg)): Terverifikasi sah dan valid di Adobe Acrobat Reader (*Signed and all signatures are valid*).

---

### G. Kanal Kontak Center Resmi BSrE ([`19_kontak_center_dan_kanal_resmi_bsre.jpeg`](./19_kontak_center_dan_kanal_resmi_bsre.jpeg))
- **Telepon**: 183
- **Telegram Bot**: `@ccbsre_bot`
- **Email**: `info.bsre@bssn.go.id`
- **Telegram Channel Info**: `@bsreupdate`
