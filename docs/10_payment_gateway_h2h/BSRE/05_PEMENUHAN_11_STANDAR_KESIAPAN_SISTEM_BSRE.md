# 📋 PANDUAN PEMENUHAN 11 STANDAR KESIAPAN SISTEM BSRE
## Checklist Evaluasi & Rujukan Pengujian Uji Penerapan Modul (Uji Kesesuaian)
### Sistem M-PAD Pemerintah Kota Baubau — Menuju Sertifikasi BSrE BSSN

Dokumen ini merupakan panduan komprehensif atas **11 Kriteria / Standar Integrasi Sistem** yang diuji secara formal oleh tim teknis Balai Sertifikasi Elektronik (BSrE - BSSN).

---

## 1. Rincian Pemenuhan Standar 01 s/d 11

### Standar 01: Pengecekan Status Sertifikat Penandatangan (Inquiry NIK)
- **Dasar Aturan**: Sistem harus memverifikasi terlebih dahulu apakah pejabat yang akan menandatangani naskah memiliki sertifikat aktif di BSrE.
- **Implementasi M-PAD**:
  - `TTEService::checkUserStatus(string $nik)` memanggil endpoint Esign Client `GET /api/user/status/{nik}`.
  - Respon status: `TERDAFTAR_AKTIF`, `BELUM_TERDAFTAR`, `EXPIRED`, atau `REVOKED`.
  - Jika belum aktif, tombol penandatanganan dinonaktifkan dengan tooltip yang jelas.

---

### Standar 02: Pemisahan Akun Masing-masing Pengguna Penandatangan
- **Dasar Aturan (Slide 05)**: *"Memisahkan akun masing-masing pengguna penandatangan"*. Dilarang menggunakan akun bersama (*shared account*).
- **Implementasi M-PAD**:
  - Role-Based Access Control (RBAC) M-PAD mengikat user login ke NIP dan NIK perorangan.
  - Kadis Bapenda, Kadis PUPR, Kepala BPKAD, Kadis Disperindag, Kadis Dishub, dan Kadis DLH masing-masing memiliki akun otentikasi unik di `retribusi-admin`.

---

### Standar 03: Penataan Spesimen TTE & Pratinjau Dokumen
- **Dasar Aturan (Slide 06)**: Penandatangan wajib diberikan pratinjau (*preview*) dokumen sebelum pengesahan dan dapat mengatur letak spesimen visual (QR Code TTE).
- **Implementasi M-PAD**:
  - Terdapat modal pratinjau PDF interaktif sebelum tombol eksekusi ditekan.
  - Untuk dokumen otomatis (`e-SSPD`, `e-SSRD`, `e-STTS`), koordinat QR telah dikunci pada pojok kanan bawah halaman naskah dengan ukuran baku 2.5 x 2.5 cm.

---

### Standar 04: Pencantuman Teks Informasi Legalitas Dokumen
- **Dasar Aturan (Slide 04)**: *"Mencantumkan informasi pada dokumen keluaran aplikasi yang menggunakan Sertifikat Elektronik bahwa dokumen telah ditandatangani secara elektronik menggunakan Sertifikat Elektronik yang diterbitkan oleh BSrE-BSSN"*.
- **Teks Baku Wajib**:
  > *"Dokumen ini telah ditandatangani secara elektronik menggunakan sertifikat elektronik yang diterbitkan oleh Balai Besar Sertifikasi Elektronik (BSrE), Badan Siber dan Sandi Negara"*
- **Implementasi M-PAD**:
  - Teks baku ini disematkan di bawah QR Code pengesahan pada template:
    - [`resources/views/pdf/sspd.blade.php`](../../../resources/views/pdf/sspd.blade.php) (Pajak Daerah)
    - [`resources/views/pdf/ssrd.blade.php`](../../../resources/views/pdf/ssrd.blade.php) (Retribusi Daerah)
    - [`resources/views/pdf/sipab.blade.php`](../../../resources/views/pdf/sipab.blade.php) (Izin Sewa Alat Berat & Aset BMD)
    - [`resources/views/pdf/sppt.blade.php`](../../../resources/views/pdf/sppt.blade.php) (SPPT PBB-P2)

---

### Standar 05: Proteksi Dokumen & Standar PDF/A
- **Dasar Aturan**: Berkas PDF keluaran harus mematuhi format PDF/A (tidak korup, tamper-evident), dan struktur penandatanganan tidak boleh merusak konten asli.
- **Implementasi M-PAD**:
  - Engine DomPDF dikonfigurasi menghasilkan format PDF terstandarisasi.
  - Sebelum dan setelah penandatanganan, sistem menghitung ringkasan digital (*checksum*) SHA-256 dan menyimpannya di kolom `sha256_checksum` pada tabel `signed_documents`.

---

### Standar 06: Form Pengisian Passphrase Mandiri (Self-Sign)
- **Dasar Aturan (Slide 06)**: *"Menambahkan form untuk pengisian passphrase pada halaman penanda tangan"*. **DILARANG MENYIMPAN PASSPHRASE DI BASIS DATA ATAU STORAGE APLIKASI!**
- **Implementasi M-PAD**:
  - Antarmuka web `retribusi-admin` memunculkan modal input dialog dengan tipe `password`, opsi show/hide, dan validasi *"Mohon isi Passphrase terlebih dahulu!"*.
  - Passphrase hanya ada di memori peramban dan memori server selama proses transmisi HTTPS, lalu segera dibuang (*in-memory disposal*).

---

### Standar 07: Notifikasi Penandatanganan Berhasil
- **Dasar Aturan (Slide 07)**: *"Menampilkan notifikasi dan/atau sejenisnya kepada pengguna jika penanda tangan berhasil"*.
- **Implementasi M-PAD**:
  - Dialog pop-up sukses dengan icon centang hijau dan teks:
    - Judul: **"Mantap!"**
    - Pesan: *"Surat berhasil ditandatangani dan diproses (Waktu proses TTE : {latency} ms)"*
    - Tombol aksi: `[Atur Spesimen Berikutnya]` atau `[Unduh Dokumen Ber-TTE]`.

---

### Standar 08: Pemasangan Sertifikat SSL / HTTPS
- **Dasar Aturan (Slide 08)**: *"Memasangkan SSL pada aplikasi yang menggunakan Sertifikat Elektronik BSrE"*.
- **Implementasi M-PAD**:
  - Domain publik `mpad.baubaukota.go.id`, portal API `api.sipanda.online`, dan admin dashboard `admin.sipanda.online` seluruhnya menerapkan enkripsi TLS 1.3 dengan skor SSL Labs Grade A.

---

### Standar 09: Perekaman Log Audit Trail Lengkap (Log Gagal & Berhasil)
- **Dasar Aturan (Slide 09)**: *"Mengaplikasikan/menerapkan mekanisme penyimpanan/perekaman log untuk setiap proses penandatanganan"*.
- **Struktur Tabel Standar BSrE (`esign_log` / `tte_logs`)**:
  - `id`: Primary key log.
  - `message`: Detail respon error atau konfirmasi sukses dari engine TTE.
  - `remote_addr`: Alamat IP klien pengirim permintaan penandatanganan.
  - `user_agent`: String peramban / aplikasi pengirim.
  - `created_at`: Stempel waktu pencatatan log secara presisi.
  - `duration_ms`: Durasi eksekusi dalam milidetik.
  - `status`: Status eksekusi (`success`, `failed`, `fallback_local_seal`).
- **Pemeriksaan Audit**:
  Verifikator BSrE akan mengecek langsung melalui terminal database:
  ```sql
  SELECT id, message, remote_addr, user_agent, created_at FROM tte_logs ORDER BY id DESC LIMIT 10;
  ```

---

### Standar 10: Mekanisme Perubahan Status Siklus Dokumen
- **Dasar Aturan (Slide 10)**: *"Mengaplikasikan/menerapkan mekanisme perubahan status dokumen sebelum dan setelah dilakukan penandatanganan"*.
- **Siklus Status State Machine**:
  ```mermaid
  stateDiagram-v2
      [*] --> Draft: Penerbitan Tagihan / Draf Naskah
      Draft --> Proses: Pengajuan Penandatanganan (POST API)
      Proses --> Selesai: TTE Berhasil Dibubuhkan (Esign 200 OK)
      Proses --> Gagal: TTE Gagal (Passphrase Salah / Timeout)
      Gagal --> Proses: Ajukan Ulang Penandatanganan
      Selesai --> [*]: Terdaftar di E-Registry Resmi
  ```
- **Implementasi di UI Admin**:
  - `Draft`: Badge Abu-abu
  - `Proses`: Badge Biru (animasi loading)
  - `Selesai`: Badge Hijau
  - `Gagal`: Badge Merah

---

### Standar 11: Validitas Dokumen Keluaran Aplikasi (PDF Reader & LTV)
- **Dasar Aturan (Slide 11)**: *"Status tanda tangan pada dokumen keluaran aplikasi dapat diverifikasi validitasnya"*.
- **Hasil Verifikasi Adobe Acrobat Reader**:
  - Panel Tanda Tangan menampilkan:
    - ✅ *"Signed and all signatures are valid"*
    - ✅ *"Document has not been modified since it was signed"*
    - ✅ *"Signer's identity is valid"*
    - ✅ *"Signature is LTV enabled"*
    - ✅ *Issued by: Balai Sertifikasi Elektronik - Badan Siber dan Sandi Negara*.
- **Verifikasi Publik**:
  - QR Code pada dokumen mengarahkan ke halaman verifikasi publik M-PAD (`https://mpad.baubaukota.go.id/verify/{DOC_NUMBER}`) yang menampilkan rincian sertifikat resmi.
