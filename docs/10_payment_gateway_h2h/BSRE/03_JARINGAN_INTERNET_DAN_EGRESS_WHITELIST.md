# 🌐 KEBIJAKAN JARINGAN & EGRESS WHITELIST DOMAIN BSSN
## Standar Konektivitas Keluar Server Esign Client M-PAD Kota Baubau
*Rujukan: Slide Presentasi BSrE (Screenshot 2026-09-17 at 14.52.25.png)*

---

## 1. Daftar Domain Resmi BSSN yang Wajib Dibuka (Whitelist)

Server modul **Esign Client** yang berada di dalam jaringan privat lokal Diskominfo Kota Baubau memerlukan koneksi internet keluar (outbound HTTPS port 443) ke sejumlah domain resmi Badan Siber dan Sandi Negara (BSSN):

| No | Nama Domain / URL Lengkap | Lingkungan (Environment) | Deskripsi & Fungsi Teknis |
|:---|:---|:---|:---|
| **1** | `https://api-bsre.bssn.go.id` | **Production** | Gateway utama untuk transaksi penandatanganan elektronik, segel elektronik, dan verifikasi sertifikat production. |
| **2** | `https://osd-dev.bssn.go.id` | **Development / Sandbox** | Gateway pengujian untuk tahap Bimtek, simulasi integrasi sistem, dan uji coba modul pengembang M-PAD. |
| **3** | `https://registry-bsre.bssn.go.id` | **Production & Development** | Akses direktori publik sertifikat digital, validasi status pencabutan sertifikat (*Certificate Revocation List / CRL*), dan protokol OCSP. |
| **4** | `https://mail.bssn.go.id` | **Production & Development** | Layanan pengiriman notifikasi persuratan, tautan aktivasi passphrase pengguna baru, dan pengiriman kode OTP / 2FA. |

---

## 2. Pengujian Konektivitas Jaringan (Connectivity Verification)

Sebelum tim BSrE melakukan instalasi modul atau uji coba, Administrator Jaringan Diskominfo wajib memverifikasi bahwa server Esign Client dapat menjangkau keempat URL tersebut:

```bash
# Uji koneksi ke gateway Production BSSN
curl -Iv https://api-bsre.bssn.go.id

# Uji koneksi ke gateway Development BSSN
curl -Iv https://osd-dev.bssn.go.id

# Uji koneksi ke Registry BSSN
curl -Iv https://registry-bsre.bssn.go.id

# Uji koneksi ke Mailer BSSN
curl -Iv https://mail.bssn.go.id
```

> **Catatan Verifikasi**:
> Pastikan respon mengembalikan status handshake TLS yang sukses (`HTTP/1.1 200 OK` atau kode response HTTP yang valid) dan tidak terjadi `Connection timed out` atau `Could not resolve host`.

---

## 3. Konfigurasi DNS & NTP (Time Synchronization)

Untuk mencegah kegagalan validasi stempel waktu (*Time Stamping Authority / TSA*) pada sertifikat TTE:
1. **NTP Server**: Server wajib disinkronkan ke server waktu resmi nasional:
   ```bash
   sudo timedatectl set-ntp on
   sudo timedatectl set-timezone Asia/Makassar
   ```
2. **DNS Resolver**: Gunakan resolver DNS yang stabil dan mampu meresolusi domain internal `.go.id` dengan cepat.
