# 🏛️ INDEKS DOKUMENTASI INTEGRASI TTE BSRE BSSN
## Badan Pendapatan Daerah & Diskominfo Kota Baubau — Sistem M-PAD
*Folder: `docs/10_payment_gateway_h2h/BSRE/` | Status: Siap Audit & Uji Penerapan Modul*

---

## 📌 Daftar Berkas Dokumentasi Teknis (.md)

Seluruh pembahasan, arsitektur, dan materi slide presentasi resmi Balai Sertifikasi Elektronik (BSrE - BSSN) telah dialihkan sepenuhnya ke dalam format Markdown terstruktur:

| No | Berkas Dokumentasi (.md) | Deskripsi & Ruang Lingkup |
|:---|:---|:---|
| **01** | [`01_NOTULENSI_DAN_RINGKASAN_PEMBAHASAN_BSRE.md`](./01_NOTULENSI_DAN_RINGKASAN_PEMBAHASAN_BSRE.md) | Notulensi lengkap pembahasan pra-integrasi, 4 tahapan siklus integrasi, spesifikasi server, whitelist egress, fitur modul v2.3.3, dan kontak center resmi. |
| **02** | [`02_SPESIFIKASI_INFRASTRUKTUR_DAN_JARINGAN_ESIGN.md`](./02_SPESIFIKASI_INFRASTRUKTUR_DAN_JARINGAN_ESIGN.md) | Standar spesifikasi server Esign Client lokal (Ubuntu 24 LTS, 8 GB RAM, 4 Core vCPU, 50 GB Storage), konfigurasi port privat (80, 443, 22), dan isolasi jaringan non-publik. |
| **03** | [`03_JARINGAN_INTERNET_DAN_EGRESS_WHITELIST.md`](./03_JARINGAN_INTERNET_DAN_EGRESS_WHITELIST.md) | Panduan firewall egress dan 4 domain resmi BSSN yang wajib dibuka (Production, Development/Sandbox, Registry CA, Mail OTP). |
| **04** | [`04_ARSITEKTUR_DAN_FITUR_MODUL_ESIGN_CLIENT.md`](./04_ARSITEKTUR_DAN_FITUR_MODUL_ESIGN_CLIENT.md) | Konsep Hash Value Signing serta 5 fitur utama modul Esign Client v2.3.3 (Segel Elektronik, Bulking Signing, OTP, Password PDF, Expired Check). |
| **05** | [`05_PEMENUHAN_11_STANDAR_KESIAPAN_SISTEM_BSRE.md`](./05_PEMENUHAN_11_STANDAR_KESIAPAN_SISTEM_BSRE.md) | Bedah tuntas 11 kriteria wajib uji kesesuaian sistem BSrE (Teks Legalitas, Pemisahan Akun, Form Passphrase, Autocomplete Off, No Storage Passphrase, SSL/HTTPS, Notifikasi Sukses/Gagal, Log Audit Trail, Lifecycle Status, Validitas Acrobat LTV). |
| **06** | [`06_SOP_RESERVASI_DAN_FORMULIR_PENDAFTARAN.md`](./06_SOP_RESERVASI_DAN_FORMULIR_PENDAFTARAN.md) | Prosedur resmi reservasi portal `https://bsre.bssn.go.id/reservasi`, pengisian formulir permohonan ber-TTE pejabat instansi, dan kanal kontak resmi. |
| **07** | [`STANDAR_11_BSRE_H2H_IMPLEMENTATION_PLAN.md`](./STANDAR_11_BSRE_H2H_IMPLEMENTATION_PLAN.md) | Rencana teknis implementasi H2H pada ekosistem M-PAD (Dual-Mode Signing: Segel Elektronik Kasda 24/7 & Personal TTE Pejabat OPD). |

---

## 🖼️ Galeri Slide Presentasi Resmi BSrE BSSN

19 gambar slide presentasi resmi telah direname dan dikelompokkan secara terstruktur:

| No | Nama Berkas Slide | Topik / Pembahasan |
|:---:|:---|:---|
| **01** | [`01_cover_konsultasi_pra_integrasi.jpeg`](./01_cover_konsultasi_pra_integrasi.jpeg) | Cover: Konsultasi Pra Integrasi BSrE BSSN |
| **02** | [`02_tahapan_integrasi_bsre.jpeg`](./02_tahapan_integrasi_bsre.jpeg) | 4 Tahapan Integrasi (Konsultasi, Instalasi Modul, Bimtek, Uji Penerapan) |
| **03** | [`03_spesifikasi_server_esign_client.jpeg`](./03_spesifikasi_server_esign_client.jpeg) | Spesifikasi Server Esign Client (Ubuntu 24, 8GB RAM, 4 Core, 50GB HDD, Non-Publik) |
| **04** | [`04_whitelist_koneksi_internet_bssn.jpeg`](./04_whitelist_koneksi_internet_bssn.jpeg) | Whitelist Koneksi Internet (Domain Production, Dev, Registry, Mail) |
| **05** | [`05_skema_integrasi_esign_client.jpeg`](./05_skema_integrasi_esign_client.jpeg) | Skema Integrasi Esign Client Service (Hash Value Signing) |
| **06** | [`06_fitur_esign_client_v2.3.3.jpeg`](./06_fitur_esign_client_v2.3.3.jpeg) | Fitur Esign Client v2.3.3 (Segel, Bulk Sign, OTP, PDF Password, Expired Check) |
| **07** | [`07_syarat_01_informasi_dokumen_keluaran.jpeg`](./07_syarat_01_informasi_dokumen_keluaran.jpeg) | **Syarat 01**: Informasi Dokumen Keluaran Aplikasi (Klausul Teks Legalitas BSrE) |
| **08** | [`08_syarat_02_pemisahan_akun_pengguna.jpeg`](./08_syarat_02_pemisahan_akun_pengguna.jpeg) | **Syarat 02**: Pemisahan Akun Pengguna (Dilarang Shared Account) |
| **09** | [`09_syarat_03_form_passphrase.jpeg`](./09_syarat_03_form_passphrase.jpeg) | **Syarat 03**: Form Passphrase Mandiri pada Halaman Penandatangan |
| **10** | [`10_syarat_04_nonaktifkan_autocomplete_passphrase.jpeg`](./10_syarat_04_nonaktifkan_autocomplete_passphrase.jpeg) | **Syarat 04**: Nonaktifkan Fitur Auto Complete pada Form Passphrase |
| **11** | [`11_syarat_05_dilarang_simpan_passphrase.jpeg`](./11_syarat_05_dilarang_simpan_passphrase.jpeg) | **Syarat 05**: Dilarang Menyimpan Passphrase di Database/Session/Aplikasi |
| **12** | [`12_syarat_06_pemasangan_ssl_https.jpeg`](./12_syarat_06_pemasangan_ssl_https.jpeg) | **Syarat 06**: Menggunakan Secure Sockets Layer (SSL / HTTPS) |
| **13** | [`13_syarat_07_notifikasi_penandatanganan_berhasil.jpeg`](./13_syarat_07_notifikasi_penandatanganan_berhasil.jpeg) | **Syarat 07**: Notifikasi Penanda Tangan Berhasil (Latensi Milidetik) |
| **14** | [`14_syarat_08_notifikasi_penandatanganan_gagal.jpeg`](./14_syarat_08_notifikasi_penandatanganan_gagal.jpeg) | **Syarat 08**: Notifikasi Penanda Tangan Gagal (Disertai Status/Pesan Error) |
| **15** | [`15_syarat_09_perekaman_log_audit_trail.jpeg`](./15_syarat_09_perekaman_log_audit_trail.jpeg) | **Syarat 09**: Perekaman Log Audit Trail Lengkap (`created_at`, IP, Agent, Message) |
| **16** | [`16_syarat_10_perubahan_status_dokumen.jpeg`](./16_syarat_10_perubahan_status_dokumen.jpeg) | **Syarat 10**: Perubahan Status Dokumen Sebelum dan Sesudah Penandatanganan |
| **17** | [`17_syarat_11_validitas_tanda_tangan_pdf.jpeg`](./17_syarat_11_validitas_tanda_tangan_pdf.jpeg) | **Syarat 11**: Status Dokumen Keluaran Aplikasi Valid di Adobe Acrobat Reader |
| **18** | [`18_persiapan_instalasi_modul_reservasi.jpeg`](./18_persiapan_instalasi_modul_reservasi.jpeg) | Prosedur Persiapan & Reservasi "Instalasi Modul" di Portal BSrE |
| **19** | [`19_kontak_center_dan_kanal_resmi_bsre.jpeg`](./19_kontak_center_dan_kanal_resmi_bsre.jpeg) | Saluran Komunikasi & Kontak Center Resmi BSrE BSSN |
