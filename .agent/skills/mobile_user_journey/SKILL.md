---
name: "Universal User Journey & Citizen Experience (M-PAD Mobile)"
description: "Panduan master arsitektur alur perjalanan Warga & Wajib Pajak (User Journey) di M-PAD Mobile Kota Baubau, mencakup 3 paradigma layanan (Self-Assessment e-SPTPD, Official-Assessment PBB/Reklame, dan Retribusi Daerah/Sewa Aset), dari Onboarding, Smart Form GIS, Transparansi Hukum SKPD, Pembayaran QRIS/VA, hingga Bukti Sah E-BSR/E-STTS."
---

# Universal User Journey & Citizen Experience (M-PAD Mobile)

Skill ini adalah **panduan standar operasional UX & Arsitektur Alur Pengguna (User Journey)** untuk seluruh aplikasi warga (`retribusi-mobile`) di ekosistem M-PAD Pemerintah Kota Baubau, berdasarkan **UU HKPD No. 1 Tahun 2022** dan **Perda Kota Baubau No. 1 Tahun 2024**.

Dokumen referensi utama:
- [`user_journey_universal_mpad_mobile.md`](file:///Users/pondokit/Herd/retribusi-api/docs/05_modul_aplikasi/user_journey_universal_mpad_mobile.md)
- [`user_journey_mpad_mobile.md`](file:///Users/pondokit/Herd/retribusi-api/docs/05_modul_aplikasi/user_journey_mpad_mobile.md)
- [`proses_berpikir_penerbitan_layanan.md`](file:///Users/pondokit/Herd/retribusi-api/docs/05_modul_aplikasi/proses_berpikir_penerbitan_layanan.md)

---

## 1. Tiga Paradigma Pola Layanan M-PAD

Setiap fitur atau modul baru di `retribusi-mobile` **WAJIB** diklasifikasikan ke dalam salah satu dari 3 paradigma berikut:

| Paradigma | Contoh Layanan | Pola Interaksi Warga | Dokumen Tagihan & Bukti |
| :--- | :--- | :--- | :--- |
| **Pola A: Self-Assessment (Lapor Sendiri)** | • Sarang Burung Walet<br>• PBJT (Restoran, Hotel, Parkir)<br>• Pajak MBLB | Warga lapor mandiri omzet/volume panen via e-SPTPD $\rightarrow$ Verifikasi Admin Bapenda. | • Tagihan: **SKPD**<br>• Bukti: **E-BSR** |
| **Pola B: Official-Assessment (Ketetapan Bapenda)** | • PBB-P2 (Bumi & Bangunan)<br>• Pajak Reklame<br>• BPHTB | Inkuiri NOP instan (PBB) atau form teknis reklame dengan simulasi tarif. | • Tagihan: **SPPT / SKPD Reklame**<br>• Bukti: **E-STTS** |
| **Pola C: Retribusi & Sewa Aset Daerah** | • Sewa Rusunawa (Wameo, Kotamara, ASN)<br>• Retribusi Pasar (Kios/Los)<br>• Retribusi Sampah | Terikat kontrak/penempatan unit. Tagihan bulanan otomatis terbit setiap awal bulan. | • Tagihan: **SKRD**<br>• Bukti: **E-SKRD Lunas / Karcis** |

---

## 2. Standar 6 Fase Universal User Journey

Setiap pengembangan halaman atau alur di `retribusi-mobile` harus mematuhi tahapan ini:

### Fase 1: Smart Onboarding (Akses Inklusif)
- **Teknologi**: SSO NIK 16-digit + OTP WhatsApp Gateway Bapenda (`+6282189456728`).
- **Aturan UX**: Warga tidak boleh dibebani membuat password rumit. Profil, daftar objek, dan riwayat tagihan terikat langsung ke NIK.

### Fase 2: Inisiasi Layanan & Smart Data Capture
- **PBB-P2**: Inkuiri 18 digit NOP $\rightarrow$ menampilkan tunggakan multi-tahun dan NJOP.
- **e-SPTPD**: Form pelaporan harus mengunci unit (*Selected Unit Card*) tanpa dropdown terbuka jika berasal dari objek tertentu. Nilai omzet & pajak dihitung otomatis (misal: Walet Rp 6jt/Kg $\times$ 10%).
- **Pendaftaran Baru**: Menggunakan Smart GIS MapPicker untuk penentuan titik/poligon koordinat presisi.

### Fase 3: Transparansi Regulasi & Interlock Legal
- **Kalkulator Terbuka**: Menampilkan dasar hukum Perda 1/2024 dan rincian persentase tarif secara transparan.
- **Card Edukasi SKPD/SKRD**: Menjelaskan arti SKPD/SKRD sebagai surat ketetapan piutang daerah berkekuatan hukum.
- **Interlock Anti-Fraud**: Checkbox konfirmasi keabsahan data wajib dicentang sebelum tombol kirim aktif (misal tombol: *"Kirim SKPD"*).

### Fase 4: Tracking Real-Time & Notifikasi Proaktif
- Warga mendapatkan Nomor Registrasi/Pelaporan.
- Status linimasa permohonan transparan:
  - **Pendaftaran Objek Baru**: `Draft` $\rightarrow$ `Verifikasi Berkas` $\rightarrow$ `Survei Fisik Surveyor Lapangan` $\rightarrow$ `Disetujui`.
  - **Pelaporan Rutin (e-SPTPD)**: `Draft` $\rightarrow$ `Review Admin Bapenda` $\rightarrow$ `Disetujui (SKPD Terbit)`.
- Notifikasi status terkirim otomatis ke WhatsApp warga.

> ⚖️ **Aturan Batasan Tupoksi Petugas di Ekosistem M-PAD**:
> 1. **Petugas Lapangan (Surveyor)**: HANYA beroperasi pada **tahap awal pendaftaran / penambahan unit objek pajak** (survei fisik lokasi, cek LSPOP, dan verifikasi titik/poligon GPS). Petugas survei TIDAK terlibat pada pelaporan rutin bulanan warga.
> 2. **Bidang Pengawasan (Wasdal & Penindakan)**: Jika terjadi **anomali pelaporan** (omzet anjlok drastis, manipulasi panen, atau tunggakan), penanganan beralih ke **Petugas Pengawasan** untuk menjalankan **Uji Petik (*Spot Check*)** (`POST /api/spot-checks`), penerbitan **SKPDKB**, dan penindakan sanksi denda (mengacu pada skill [`Auditor & Enforcement Specialist`](file:///Users/pondokit/Herd/retribusi-api/.agent/skills/audit_enforcement/SKILL.md)).

### Fase 5: Pembayaran Digital Terpadu (Omni-Payment Gateway)
- Integrasi QRIS Nasional (dukung semua bank & e-wallet).
- Virtual Account Multi-Bank (Bank Sultra, BSI, BRI H2H).
- Real-time settlement: Sistem mengunci tagihan menjadi `LUNAS` secara idempotent dalam hitungan detik.

### Fase 6: Dokumen Sah Digital & Arsip Permanen
- Penerbitan tanda bukti lunas resmi ber-barcode verifikasi TTE (E-BSR / E-STTS / E-SKRD).
- Bukti tersimpan permanen di tab **Riwayat Transaksi** dan dapat diunduh PDF kapan saja.

---

## 3. Checklist Verifikasi Implementasi Fitur Mobile

Saat membuat atau mereview fitur di `retribusi-mobile`:
- [ ] Apakah alur termasuk Pola A, B, atau C?
- [ ] Apakah form warga bebas dari istilah teknis rumit (prinsip *Citizen-Centric*)?
- [ ] Apakah ada interlock hukum (checkbox pernyataan keabsahan data)?
- [ ] Apakah ada indikator loading/feedback jelas saat memanggil API backend?
- [ ] Apakah tombol pembayaran mengarahkan ke kanal resmi QRIS/VA Bapenda tanpa manipulasi nominal di frontend?
- [ ] Apakah bukti setor dapat diunduh atau dilihat kembali di tab Riwayat?
