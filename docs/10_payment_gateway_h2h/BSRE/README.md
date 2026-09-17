# 🏛️ INDEKS DOKUMENTASI INTEGRASI TTE BSRE BSSN
## Badan Pendapatan Daerah & Diskominfo Kota Baubau — Sistem M-PAD
*Folder: `docs/10_payment_gateway_h2h/BSRE/` | Status: Siap Audit & Uji Penerapan Modul*

---

## 📌 Daftar Berkas Dokumentasi (.md)

Seluruh pembahasan, arsitektur, dan materi slide presentasi resmi Balai Sertifikasi Elektronik (BSrE - BSSN) telah dialihkan sepenuhnya ke dalam format Markdown terstruktur:

| No | Berkas Dokumentasi (.md) | Deskripsi & Ruang Lingkup |
|:---|:---|:---|
| **01** | [`01_NOTULENSI_DAN_RINGKASAN_PEMBAHASAN_BSRE.md`](./01_NOTULENSI_DAN_RINGKASAN_PEMBAHASAN_BSRE.md) | Notulensi lengkap rapat koordinasi teknis pra-integrasi, daftar peserta (BSSN, Bapenda, Diskominfo, Pengembang), dan poin keputusan strategis. |
| **02** | [`02_SPESIFIKASI_INFRASTRUKTUR_DAN_JARINGAN_ESIGN.md`](./02_SPESIFIKASI_INFRASTRUKTUR_DAN_JARINGAN_ESIGN.md) | Standar spesifikasi server Esign Client lokal (Ubuntu 24.04 LTS, 8 GB RAM, 4 Core vCPU, 50 GB SSD), konfigurasi port privat (80, 443, 22), dan isolasi jaringan non-publik. |
| **03** | [`03_JARINGAN_INTERNET_DAN_EGRESS_WHITELIST.md`](./03_JARINGAN_INTERNET_DAN_EGRESS_WHITELIST.md) | Panduan firewall egress dan 4 domain resmi BSSN yang wajib dibuka (Production, Development/Sandbox, Registry CA, Mail OTP). |
| **04** | [`04_ARSITEKTUR_DAN_FITUR_MODUL_ESIGN_CLIENT.md`](./04_ARSITEKTUR_DAN_FITUR_MODUL_ESIGN_CLIENT.md) | Topologi modul Esign Client (Web Service Interface, Crypto Module, TSA Client) serta 5 fitur utama (Segel Elektronik, Bulking Signing, 2FA OTP, Password PDF, Expired Check). |
| **05** | [`05_PEMENUHAN_11_STANDAR_KESIAPAN_SISTEM_BSRE.md`](./05_PEMENUHAN_11_STANDAR_KESIAPAN_SISTEM_BSRE.md) | Bedah tuntas 11 kriteria wajib uji kesesuaian sistem BSrE (Inquiry NIK, Pemisahan Akun, Spesimen, Klausul Legalitas, PDF/A, Passphrase Mandiri, Latensi ms, SSL, Perekaman Log `esign_log`, Status Lifecycle, dan Validitas Acrobat LTV). |
| **06** | [`06_SOP_RESERVASI_DAN_FORMULIR_PENDAFTARAN.md`](./06_SOP_RESERVASI_DAN_FORMULIR_PENDAFTARAN.md) | SOP reservasi kegiatan "Instalasi Modul" di portal `https://bsre.bssn.go.id/reservasi`, template surat permohonan, dan alur penandatanganan TTE pejabat instansi. |
| **07** | [`STANDAR_11_BSRE_H2H_IMPLEMENTATION_PLAN.md`](./STANDAR_11_BSRE_H2H_IMPLEMENTATION_PLAN.md) | Rencana teknis implementasi H2H pada ekosistem M-PAD (Dual-Mode Signing: Segel Elektronik Kasda 24/7 & Personal TTE Pejabat OPD). |

---

## 🔄 Sinkronisasi Google Drive
Folder ini disinkronisasikan ke repositori Google Drive master M-PAD pada path:
`gdrive:M-PAD/02_INTEGRASI_BANK_DAN_API/09_BSRE/`
