# 📝 NOTULENSI & RINGKASAN PEMBAHASAN INTEGRASI BSRE - M-PAD
## Rapat Koordinasi Teknis Pra-Integrasi & Persiapan Uji Kesesuaian Sistem TTE
### Pemerintah Kota Baubau & Balai Sertifikasi Elektronik (BSrE - BSSN)

- **Hari / Tanggal**: Kamis, 17 September 2026
- **Waktu**: 09:00 - 11:30 WITA
- **Media**: Zoom Cloud Meeting (Bilik Pertemuan Virtual BSrE BSSN)
- **Agenda**:
  1. Penjelasan 4 Tahapan Integrasi Layanan Sertifikat Elektronik BSrE
  2. Standarisasi Kebutuhan Infrastruktur Server Modul Esign Client
  3. Evaluasi 11 Standar Kesiapan Aplikasi (Kriteria Pengujian Sistem)
  4. Penyusunan Roadmap Integrasi Dual-Mode TTE pada Sistem M-PAD Kota Baubau

---

## 1. Daftar Peserta Rapat

1. **Pihak Balai Sertifikasi Elektronik (BSrE - BSSN)**:
   - **Arina** — *Narasumber & Verifikator Integrasi Sistem BSrE BSSN*
2. **Pihak Pemerintah Kota Baubau**:
   - **Pak Yakin** — *Dinas Komunikasi dan Informatika (Diskominfo) Kota Baubau*
   - **Fauziah** — *Badan Pendapatan Daerah (Bapenda) Kota Baubau*
   - **Tim Teknis Diskominfo Kota Baubau**
3. **Pihak Pengembang Sistem**:
   - **Tim Pondok Informatika / Ekosistem Digital M-PAD**

---

## 2. Rangkuman Poin Pembahasan Utama

### A. 4 Tahapan Siklus Integrasi BSrE (Slide 14.51.20)
Pihak BSrE menegaskan bahwa proses integrasi wajib melalui 4 gerbang verifikasi resmi:
1. **Konsultasi Pra-Integrasi**: Penyelarasan kebutuhan bisnis dan identifikasi jenis sertifikat (Segel Elektronik vs Personal TTE).
2. **Instalasi Modul**: Penyediaan server lokal oleh Pemkot Baubau dan permohonan modul Esign Client via portal reservasi resmi.
3. **Bimbingan Teknis (Bimtek) Integrasi**: Asistensi penghubungan REST API antara sistem M-PAD dengan modul Esign Client di lingkungan Sandbox/Development.
4. **Uji Penerapan Modul (Uji Kesesuaian Sistem)**: Pengujian formal terhadap **11 Standar Kesiapan Sistem** di hadapan tim penguji BSrE.

> *Catatan Wajib BSSN: Setiap tahapan kegiatan memerlukan reservasi resmi di `https://bsre.bssn.go.id/reservasi` disertai surat permohonan dan formulir permohonan ber-TTE pejabat instansi.*

---

### B. Spesifikasi Server & Keamanan Jaringan Esign Client (Slide 14.52.09 & 14.52.10)
- **Sistem Operasi**: Wajib Ubuntu 24.04 LTS (Minimal Server).
- **Spesifikasi Fisik**: Minimal 4 Core vCPU, 8 GB RAM, 50 GB SSD.
- **Isolasi Keamanan Ketat**:
  - Server Esign Client **TIDAK BOLEH DIBUKA KE INTERNET/PUBLIK**.
  - Akses dari server aplikasi (`retribusi-api`) ke Server Esign Client dilakukan **secara lokal (LAN/IP Privat)** melalui port 80/443.
  - Port 22 hanya boleh diakses melalui jaringan internal tim admin Diskominfo.

---

### C. Whitelist Egress Jaringan BSSN (Slide 14.52.25)
Firewall Diskominfo Kota Baubau wajib membuka koneksi internet keluar (outbound/egress) pada port 443 ke 4 URL resmi BSSN:
- `https://api-bsre.bssn.go.id` (Gateway Production)
- `https://osd-dev.bssn.go.id` (Gateway Development/Testing)
- `https://registry-bsre.bssn.go.id` (Direktori Sertifikat & CRL BSSN)
- `https://mail.bssn.go.id` (Sistem Notifikasi Email & OTP)

---

### D. Fitur Modul Esign Client yang Digunakan M-PAD (Slide 14.53.29)
1. **Segel Elektronik (Organisasi)**: Digunakan untuk penerbitan otomatis dokumen pelunasan pendapatan daerah (`e-SSPD`, `e-SSRD`, `e-STTS`) saat settlement kasda 24/7 tanpa perlu input passphrase manual per transaksi.
2. **Bulking Signing**: Fitur penerbitan TTE massal untuk ratusan/ribuan lembar `e-SPPT` PBB-P2 tahunan secara simultan.
3. **Personal TTE dengan 2FA / OTP & Passphrase Mandiri**: Digunakan untuk pengesahan surat izin/kontrak sewa aset OPD (`SIPAB` PUPR, Kontrak BMD BPKAD, `LHU` DLH).
4. **Certificate Expired Check**: Pengecekan otomatis sebelum signing agar sistem memberi peringatan dini jika sertifikat mendekati masa kedaluwarsa (H-30).

---

### E. Penegasan Terhadap 11 Standar Kesiapan Aplikasi
Pihak BSrE menggarisbawahi poin-poin krusial yang paling sering menjadi temuan saat uji kesesuaian:
1. **Passphrase Tidak Boleh Disimpan (Slide 06)**: Passphrase wajib diinput manual oleh pemilik sertifikat dan diteruskan langsung tanpa tersimpan di database M-PAD.
2. **Klausul Teks Legalitas Wajib (Slide 04)**: Dokumen PDF keluaran harus memuat teks baku legalitas BSrE-BSSN.
3. **Perekaman Log Audit Trail (Slide 09)**: Penguji BSrE akan melakukan verifikasi langsung ke database sistem (`select * from esign_log`) untuk melihat bukti perekaman kegagalan/keberhasilan penandatanganan (kolom: `id`, `message`, `remote_addr`, `user_agent`, `created_at`).
4. **Siklus Status Dokumen (Slide 10)**: Dokumen harus memiliki transisi status yang jelas (`Draft` ➔ `Proses` ➔ `Selesai` / `Gagal`).
5. **Validitas Acrobat Reader (Slide 11)**: Berkas PDF hasil keluaran harus berstatus *Valid* dan *LTV Enabled* di software PDF viewer resmi.

---

## 3. Rencana Tindak Lanjut (Action Items)

| No | Tindakan | Penanggung Jawab | Target Waktu |
|:---|:---|:---|:---|
| 1 | Menyiapkan 1 unit VM Server Ubuntu 24 (4 Core, 8GB RAM, 50GB SSD) di Data Center Pemkot | Diskominfo (Pak Yakin) | H+2 Rapat |
| 2 | Mengajukan reservasi "Instalasi Modul" di `bsre.bssn.go.id/reservasi` beserta formulir ber-TTE | Diskominfo & Bapenda | H+3 Rapat |
| 3 | Mengimplementasikan skema database audit log dan state machine dokumen di `retribusi-api` | Pengembang (Pondok Informatika) | Selesai (Sprint Berjalan) |
| 4 | Memperbarui teks legalitas BSrE-BSSN pada seluruh template Blade PDF | Pengembang (Pondok Informatika) | Selesai (Sprint Berjalan) |
| 5 | Mengintegrasikan modal passphrase mandiri dan notifikasi latensi (ms) di `retribusi-admin` | Pengembang (Pondok Informatika) | Selesai (Sprint Berjalan) |
