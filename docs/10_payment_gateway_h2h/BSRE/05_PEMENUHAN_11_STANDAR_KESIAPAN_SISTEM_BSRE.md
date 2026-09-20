# 📋 PANDUAN PEMENUHAN 11 STANDAR KESIAPAN SISTEM BSRE
## Checklist Evaluasi & Rujukan Pengujian Uji Penerapan Modul (Uji Kesesuaian)
### Sistem M-PAD Pemerintah Kota Baubau — Menuju Sertifikasi BSrE BSSN

Dokumen ini merupakan panduan komprehensif atas **11 Kriteria / Standar Wajib Integrasi Sistem** yang diuji secara formal oleh tim teknis Balai Sertifikasi Elektronik (BSrE - BSSN). Seluruh butir persyaratan disusun berdasarkan materi konsultasi pra-integrasi resmi BSrE.

---

## 📌 Matriks Ringkas 11 Persyaratan Uji Kesesuaian Sistem BSrE

| No | Standar Kesiapan Sistem BSrE | Berkas Slide Rujukan | Kriteria Kunci Pengujian | Status di M-PAD |
|:---:|:---|:---|:---|:---:|
| **01** | **Informasi Dokumen Keluaran Aplikasi** | [`07_syarat_01_informasi_dokumen_keluaran.jpeg`](./07_syarat_01_informasi_dokumen_keluaran.jpeg) | Wajib memuat klausul teks legalitas baku sertifikat BSrE BSSN | ✅ *Selesai* |
| **02** | **Pemisahan Akun Pengguna** | [`08_syarat_02_pemisahan_akun_pengguna.jpeg`](./08_syarat_02_pemisahan_akun_pengguna.jpeg) | Setiap penandatangan wajib memiliki akun masing-masing (dilarang *shared account*) | ✅ *Selesai* |
| **03** | **Form Passphrase** | [`09_syarat_03_form_passphrase.jpeg`](./09_syarat_03_form_passphrase.jpeg) | Menambahkan form input passphrase mandiri pada halaman penandatanganan | ✅ *Selesai* |
| **04** | **Nonaktifkan Fitur Auto Complete** | [`10_syarat_04_nonaktifkan_autocomplete_passphrase.jpeg`](./10_syarat_04_nonaktifkan_autocomplete_passphrase.jpeg) | Fitur autocomplete browser dinonaktifkan (`autocomplete="off"`) pada form passphrase | ✅ *Selesai* |
| **05** | **Tidak Menyimpan Passphrase** | [`11_syarat_05_dilarang_simpan_passphrase.jpeg`](./11_syarat_05_dilarang_simpan_passphrase.jpeg) | Dilarang keras menyimpan passphrase di database, session, cookie, atau aplikasi | ✅ *Selesai* |
| **06** | **Menggunakan Secure Sockets Layer (SSL)** | [`12_syarat_06_pemasangan_ssl_https.jpeg`](./12_syarat_06_pemasangan_ssl_https.jpeg) | Aplikasi wajib dilindungi SSL/TLS HTTPS valid | ✅ *Selesai* |
| **07** | **Notifikasi Penanda Tangan Berhasil** | [`13_syarat_07_notifikasi_penandatanganan_berhasil.jpeg`](./13_syarat_07_notifikasi_penandatanganan_berhasil.jpeg) | Menampilkan notifikasi sukses kepada pengguna (pop-up, latensi ms) | ✅ *Selesai* |
| **08** | **Notifikasi Penanda Tangan Gagal** | [`14_syarat_08_notifikasi_penandatanganan_gagal.jpeg`](./14_syarat_08_notifikasi_penandatanganan_gagal.jpeg) | Menampilkan notifikasi jika penandatanganan gagal disertai pesan/status error | ✅ *Selesai* |
| **09** | **Perekaman Log Audit Trail** | [`15_syarat_09_perekaman_log_audit_trail.jpeg`](./15_syarat_09_perekaman_log_audit_trail.jpeg) | Merekam setiap proses signing (`id`, `message`, `remote_addr`, `user_agent`, `created_at`) | ✅ *Selesai* |
| **10** | **Perubahan Status Dokumen** | [`16_syarat_10_perubahan_status_dokumen.jpeg`](./16_syarat_10_perubahan_status_dokumen.jpeg) | Transisi siklus status dokumen yang jelas sebelum dan sesudah penandatanganan | ✅ *Selesai* |
| **11** | **Validitas Dokumen Keluaran Aplikasi** | [`17_syarat_11_validitas_tanda_tangan_pdf.jpeg`](./17_syarat_11_validitas_tanda_tangan_pdf.jpeg) | Tanda tangan PDF terverifikasi sah di Adobe Acrobat Reader (*Signed and all signatures valid*) | ✅ *Selesai* |

---

## 🔍 Pembahasan Mendalam Pemenuhan Standar 01 s/d 11

### Standar 01: Informasi Dokumen Keluaran Aplikasi
- **Rujukan Slide**: [`07_syarat_01_informasi_dokumen_keluaran.jpeg`](./07_syarat_01_informasi_dokumen_keluaran.jpeg)
- **Klausul Wajib BSrE**:
  Pada seluruh dokumen PDF keluaran aplikasi yang menggunakan sertifikat elektronik, **wajib mencantumkan kalimat baku**:
  > *"Dokumen ini telah ditandatangani secara elektronik menggunakan sertifikat elektronik yang diterbitkan oleh Balai Besar Sertifikasi Elektronik (BSrE), Badan Siber dan Sandi Negara"*
- **Implementasi pada M-PAD**:
  Teks baku disematkan pada seluruh dokumen output resmi Pemerintah Kota Baubau, tepat di bawah atau berdampingan dengan spesimen visual QR Code tanda tangan:
  - [`resources/views/pdf/sspd.blade.php`](../../../resources/views/pdf/sspd.blade.php) (Surat Setoran Pajak Daerah)
  - [`resources/views/pdf/ssrd.blade.php`](../../../resources/views/pdf/ssrd.blade.php) (Surat Setoran Retribusi Daerah)
  - [`resources/views/pdf/stts.blade.php`](../../../resources/views/pdf/stts.blade.php) (Surat Tanda Terima Setoran PBB-P2)
  - [`resources/views/pdf/sppt.blade.php`](../../../resources/views/pdf/sppt.blade.php) (SPPT PBB-P2 Massal)
  - [`resources/views/pdf/sipab.blade.php`](../../../resources/views/pdf/sipab.blade.php) (Surat Izin Pemakaian Alat Berat & BMD)

---

### Standar 02: Pemisahan Akun Pengguna
- **Rujukan Slide**: [`08_syarat_02_pemisahan_akun_pengguna.jpeg`](./08_syarat_02_pemisahan_akun_pengguna.jpeg)
- **Aturan BSrE**:
  Setiap pengguna atau pejabat penandatangan wajib memiliki akun tersendiri. **Dilarang keras berbagi akun (*shared account*)** antar pegawai atau staf.
- **Implementasi pada M-PAD**:
  - Arsitektur Role-Based Access Control (RBAC) M-PAD mengikat setiap akun pengguna ke NIP dan NIK perorangan pejabat.
  - Kadis Bapenda, Kadis PUPR, Kepala BPKAD, Kadis Perindag, Kadis Perhubungan, Kadis DLH, serta staf verifikator masing-masing memiliki kredensial otentikasi unik di `retribusi-admin`.
  - Sistem mencatat `user_id` spesifik pada setiap aktivitas di tabel audit trail.

---

### Standar 03: Form Passphrase
- **Rujukan Slide**: [`09_syarat_03_form_passphrase.jpeg`](./09_syarat_03_form_passphrase.jpeg)
- **Aturan BSrE**:
  Menambahkan form khusus untuk pengisian passphrase pada halaman penandatanganan saat pejabat akan melakukan tanda tangan mandiri (*Self-Sign*).
- **Implementasi pada M-PAD**:
  - Antarmuka web `retribusi-admin` memunculkan modal dialog bertajuk **"Pengesahan Dokumen / TTE Mandiri"** saat tombol aksi *[Tandatangani]* ditekan.
  - Modal menyediakan bidang isian passphrase terproteksi (`type="password"`) dilengkapi toggle lihat/sembunyikan ikon mata serta validasi kewajiban input sisi klien sebelum request dikirimkan ke backend.

---

### Standar 04: Nonaktifkan Fitur Auto Complete
- **Rujukan Slide**: [`10_syarat_04_nonaktifkan_autocomplete_passphrase.jpeg`](./10_syarat_04_nonaktifkan_autocomplete_passphrase.jpeg)
- **Aturan BSrE**:
  Menonaktifkan fitur autocomplete, autofill, atau sejenisnya pada form untuk pengisian passphrase guna mencegah peramban menyimpan atau mengisi passphrase secara otomatis.
- **Implementasi pada M-PAD**:
  - Input field passphrase pada komponen modal dikonfigurasi dengan atribut HTML proteksi:
    ```html
    <input 
      type="password" 
      id="tte-passphrase" 
      name="passphrase" 
      autocomplete="new-password" 
      autocorrect="off" 
      autocapitalize="off" 
      spellcheck="false" 
      placeholder="Masukkan Passphrase Anda" 
    />
    ```
  - Mencegah browser password manager (Chrome, Edge, Safari) menawarkan penyimpanan otomatis kredensial passphrase TTE.

---

### Standar 05: Tidak Mengaplikasikan Penyimpanan Passphrase
- **Rujukan Slide**: [`11_syarat_05_dilarang_simpan_passphrase.jpeg`](./11_syarat_05_dilarang_simpan_passphrase.jpeg)
- **Aturan BSrE**:
  **DILARANG KERAS** menyimpan passphrase pada basis data, berkas log, session aplikasi, cookie, cache (Redis/Memcached), maupun local storage peramban.
- **Implementasi pada M-PAD**:
  - Transmisi Passphrase bersifat murni *in-memory* (*transient ephemeral payload*).
  - Backend controller menerima request HTTPS, meneruskan string passphrase langsung ke modul Esign Client lokal, dan setelah respon diterima, variabel string passphrase langsung di-unset / garbage collected.
  - Tabel `tte_logs`, `signed_documents`, maupun log framework Laravel dikonfigurasi secara eksplisit untuk menyembunyikan (*masking/redaction*) field `passphrase`.

---

### Standar 06: Menggunakan Secure Sockets Layer (SSL)
- **Rujukan Slide**: [`12_syarat_06_pemasangan_ssl_https.jpeg`](./12_syarat_06_pemasangan_ssl_https.jpeg)
- **Aturan BSrE**:
  Aplikasi yang terintegrasi dengan sertifikat elektronik BSrE wajib menggunakan protokol aman HTTPS dengan sertifikat SSL/TLS yang valid dan terpercaya.
- **Implementasi pada M-PAD**:
  - Seluruh subdomain resmi M-PAD Pemerintah Kota Baubau dipasangi SSL/TLS modern dengan konfigurasi Nginx:
    - `https://mpad.baubaukota.go.id` (Portal Warga / Pembayar Pajak)
    - `https://apimpad.baubaukota.go.id` (REST API Gateway)
    - `https://adminmpad.baubaukota.go.id` (Dashboard Internal Pejabat & Operator)
    - `https://petugasmpad.baubaukota.go.id` (Aplikasi Petugas Lapangan)
  - HTTP Strict Transport Security (HSTS) aktif, TLSv1.2 & TLSv1.3 only, dengan cipher suite berstandar Grade A.

---

### Standar 07: Notifikasi Penanda Tangan Berhasil
- **Rujukan Slide**: [`13_syarat_07_notifikasi_penandatanganan_berhasil.jpeg`](./13_syarat_07_notifikasi_penandatanganan_berhasil.jpeg)
- **Aturan BSrE**:
  Menampilkan notifikasi kepada pengguna jika proses penandatanganan berhasil diproses.
- **Implementasi pada M-PAD**:
  - Dialog pop-up interaktif berdesain resmi:
    - Ikon: Centang Hijau Sukses
    - Judul: **"Mantap!"**
    - Pesan: *"Surat berhasil ditandatangani dan diproses (Waktu proses TTE : {latency} ms)"*
    - Tombol Aksi: `[Unduh Dokumen Ber-TTE]` dan `[Kembali ke Daftar Tagihan]`.

---

### Standar 08: Notifikasi Penanda Tangan Gagal
- **Rujukan Slide**: [`14_syarat_08_notifikasi_penandatanganan_gagal.jpeg`](./14_syarat_08_notifikasi_penandatanganan_gagal.jpeg)
- **Aturan BSrE**:
  Menampilkan notifikasi kepada pengguna jika penandatanganan gagal, **wajib disertai dengan status atau keterangan error** yang jelas (misal: passphrase salah, sertifikat kedaluwarsa, atau dokumen korup).
- **Implementasi pada M-PAD**:
  - Dialog pop-up alert kegagalan:
    - Ikon: Tanda Seru Merah
    - Judul: **"Ooops!"**
    - Pesan: *"Gagal menandatangani surat: {error_message}"* (Contoh: *"Passphrase yang Anda masukkan tidak cocok"* atau *"Sertifikat elektronik Anda berstatus EXPIRED"*).
    - Tombol Aksi: `[Coba Lagi]` atau `[Tutup]`.

---

### Standar 09: Perekaman Log Audit Trail (Log Sukses & Gagal)
- **Rujukan Slide**: [`15_syarat_09_perekaman_log_audit_trail.jpeg`](./15_syarat_09_perekaman_log_audit_trail.jpeg)
- **Aturan BSrE**:
  Mengaplikasikan mekanisme penyimpanan/perekaman log audit untuk setiap proses penandatanganan (baik berhasil maupun gagal). Verifikator BSrE akan melakukan query langsung ke database saat uji kesesuaian.
- **Implementasi pada M-PAD**:
  Tabel audit `tte_logs` (atau view alias `esign_log`) menyimpan atribut standar:
  - `id`: ID unik pencatatan log (Primary Key auto-increment).
  - `message`: Respon detail error atau status sukses dari modul Esign Client.
  - `remote_addr`: Alamat IP klien penandatangan (misal: `192.168.1.15`).
  - `user_agent`: User-Agent peramban / aplikasi pengirim.
  - `status`: Status eksekusi (`SUCCESS`, `FAILED`, `FALLBACK`).
  - `duration_ms`: Durasi eksekusi dalam satuan milidetik.
  - `created_at`: Timestamp presisi waktu terjadinya proses penandatanganan.

  *Query Pengujian Tim BSrE*:
  ```sql
  SELECT id, message, remote_addr, user_agent, created_at FROM tte_logs ORDER BY id DESC LIMIT 10;
  ```

---

### Standar 10: Perubahan Status Dokumen
- **Rujukan Slide**: [`16_syarat_10_perubahan_status_dokumen.jpeg`](./16_syarat_10_perubahan_status_dokumen.jpeg)
- **Aturan BSrE**:
  Menerapkan mekanisme perubahan status dokumen sebelum dan sesudah dilakukan penandatanganan secara konsisten.
- **Implementasi pada M-PAD**:
  Siklus hidup (*lifecycle state machine*) dokumen perpajakan:
  ```mermaid
  stateDiagram-v2
      [*] --> Draft: Penerbitan Tagihan / Surat Ketetapan
      Draft --> Menunggu_TTE: Verifikasi Staf OPD Selesai
      Menunggu_TTE --> Proses_TTE: Pejabat Input Passphrase & Submit
      Proses_TTE --> Selesai_TTE: Esign Client 200 OK (TTE Sah)
      Proses_TTE --> Gagal_TTE: Esign Client Error (Passphrase Salah/Timeout)
      Gagal_TTE --> Menunggu_TTE: Reset Form & Ajukan Ulang
      Selesai_TTE --> [*]: Dokumen Terbit di E-Registry
  ```
  - Pada antarmuka tabel admin, setiap dokumen memiliki badge warna:
    - `Draft` / `Menunggu TTE`: Kuning / Abu-abu
    - `Proses TTE`: Biru (animasi berputar)
    - `Selesai TTE`: Hijau
    - `Gagal TTE`: Merah (disertai tombol retry)

---

### Standar 11: Validitas Dokumen Keluaran Aplikasi
- **Rujukan Slide**: [`17_syarat_11_validitas_tanda_tangan_pdf.jpeg`](./17_syarat_11_validitas_tanda_tangan_pdf.jpeg)
- **Aturan BSrE**:
  Status tanda tangan pada berkas PDF keluaran aplikasi harus dapat diverifikasi keabsahannya secara independen melalui aplikasi PDF reader resmi (Adobe Acrobat Reader).
- **Indikator Validitas Adobe Acrobat Reader**:
  - ✅ Bilah atas menampilkan: *"Signed and all signatures are valid"*
  - ✅ Panel Signature menampilkan:
    - *"Document has not been modified since it was signed"*
    - *"Signer's identity is valid"*
    - *"Signature is LTV enabled"*
    - *Issued by: Balai Sertifikasi Elektronik (BSrE), Badan Siber dan Sandi Negara*
- **Verifikasi Publik Tambahan M-PAD**:
  - QR Code pada dokumen mengarahkan publik ke URL: `https://mpad.baubaukota.go.id/verify/{DOC_HASH_OR_NUMBER}` yang menampilkan sertifikat keaslian dokumen secara transparan.

---

## 🛠️ Fitur Teknis Tambahan Pendukung Uji Kesesuaian

Selain 11 standar di atas, sistem M-PAD melengkapi kesiapan integrasi dengan:
1. **Pengecekan Status Sertifikat (Inquiry NIK)**: Memanggil endpoint Esign Client `GET /api/user/status/{nik}` untuk memeriksa sertifikat aktif sebelum form TTE dibuka.
2. **Pratinjau Naskah (*Document Preview*)**: Modal viewer PDF disajikan kepada pejabat sebelum menekan tombol persetujuan akhir.
3. **Penyelarasan Stempel Waktu (NTP)**: Server lokal Esign Client disinkronkan ke server waktu nasional via port 123 NTP agar stempel waktu PAdES akurat.
