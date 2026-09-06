# Matriks Peran dan Hak Akses Pengguna (User Access Matrix)
**Ekosistem M-PAD & Cetak Biru Sultra Connect**

Dokumen ini merupakan rujukan resmi arsitektur otorisasi (*Role-Based Access Control* - RBAC) untuk seluruh pilar M-PAD (Pajak, Retribusi, dan Aset Daerah).

---

## I. Internal Pemerintah Daerah (Sisi Pengelola & Pengawas)
Kelompok pengguna ini beroperasi di *back-end* untuk mengelola data, memvalidasi transaksi, rekonsiliasi, dan mengawasi kebocoran anggaran.

### 1. Badan Pendapatan Daerah (Bapenda)
* **Peran:** Pengendali Utama (*Super Admin / Main Operator Perpajakan*).
* **Hak Akses:** Akses penuh seluruh database wajib pajak, penerbitan Surat Pemberitahuan Pajak Terutang (e-SPPT/e-SPTPD), validasi laporan omzet bulanan usaha, penguncian data uji petik (*spot check*), dan rekonsiliasi data keuangan dari Bank Persepsi.

### 2. Badan Pengelola Keuangan dan Aset Daerah (BPKAD) - Bidang Aset
* **Peran:** Pengelola Data Inventaris dan Pemanfaatan Kekayaan Daerah.
* **Hak Akses:** Mengakses modul Manajemen Aset untuk penginputan Kartu Inventaris Barang (KIB) daerah, pemetaan koordinat spasial aset Pemda (tanah, bangunan, ruko), data legalitas/sertifikat, serta pengelolaan draf kontrak sewa/kerja sama pemanfaatan (KSP) aset dengan pihak ketiga.

### 3. OPD / Dinas Teknis Penghasil Retribusi (Dishub, Disperindag, Pariwisata, dll.)
* **Peran:** Eksekutor Lapangan Pemungutan Retribusi Sektoral.
* **Hak Akses:** Terbatas pada kluster retribusi dinas masing-masing (*Multi-Tenant Access*). Petugas Dishub hanya mengakses retribusi parkir/terminal; petugas Disperindag hanya melihat sewa kios/los pasar. Menginput realisasi harian dan memantau target retribusi dinas.

### 4. Kepala Daerah (Walikota/Bupati) & Sekretaris Daerah (Sekda)
* **Peran:** Pengambil Kebijakan Strategis & Evaluasi PAD.
* **Hak Akses:** *Executive Dashboard (Read-Only Tingkat Tinggi)*. Tidak mengubah atau menghapus data, melainkan memantau grafik makro real-time dari gabungan sektor Pajak, Retribusi, dan Aset guna evaluasi kinerja OPD dan proyeksi anggaran 3–5 tahun ke depan.

### 5. Inspektorat / Badan Pengawas (Internal Auditor)
* **Peran:** Auditor Akuntabilitas dan Pencegahan Korupsi.
* **Hak Akses:** Akses Pengawasan (*Audit Trail*). Melacak riwayat log transaksi untuk mendeteksi anomali/manipulasi data, memantau piutang pajak macet, serta memverifikasi kontrak pemanfaatan aset daerah demi mencegah kerugian negara.

---

## II. Eksternal Pemerintah (Sisi Pembayar & Mitra Strategis)
Kelompok pengguna ini mengakses sistem melalui portal web atau aplikasi mobile (*front-end*) untuk transaksi dan pelayanan publik.

### 1. Wajib Pajak (Pelaku Usaha & Masyarakat Umum)
* **Peran:** Subjek Pembayar Pajak Daerah.
* **Hak Akses:** Akun personal (*Single Sign-On*). Pendaftaran usaha mandiri, pelaporan omzet bulanan (e-SPTPD), pengecekan tagihan PBB-P2, perolehan Kode Bayar (Billing Code/QRIS/VA), dan pengunduhan bukti bayar digital (e-Resi/e-SSPD). Data usaha lain terisolasi ketat.

### 2. Penyewa / Investor Aset Daerah (Mitra Pihak Ketiga)
* **Peran:** Mitra Pemanfaatan Kekayaan Daerah.
* **Hak Akses:** Portal informasi aset idle/terbuka untuk dikerjasamakan. Pengajuan permohonan sewa, pemantauan masa aktif kontrak, dan pembayaran tagihan sewa tepat waktu melalui sistem.

### 3. Bank Pembangunan Daerah (Bank Sultra) & Mitra Payment Gateway
* **Peran:** Jembatan Transaksi Finansial (*Host-to-Host*).
* **Hak Akses:** Akses API aman (*H2H Inquiry & Payment Notification*). Membaca validasi kode billing saat pembayaran di ATM, Mobile Banking, atau QRIS, serta mengirimkan notifikasi *Settlement* instan ke kas daerah.

### 4. Instansi Vertikal (ATR/BPN & Disdukcapil)
* **Peran:** Validasi dan Penyelarasan Data Makro (*Interlock Anti-Fraud*).
* **Hak Akses:** Interkoneksi data-sharing. BPN mengakses modul BPHTB untuk memvalidasi keabsahan setoran pajak sebelum sertipikat tanah terbit, sedangkan Disdukcapil menyediakan validasi NIK/NIB guna memastikan entitas wajib pajak nyata dan valid.

---

## III. Skalabilitas Multi-Daerah (Sultra Connect)
Dengan arsitektur RBAC berbasis multi-tenant ini, saat M-PAD direplikasi ke tingkat provinsi:
1. Struktur hak akses tetap konsisten.
2. Pemerintah Provinsi (Gubernur) cukup diposisikan sebagai **Aktor Pengawas Makro Regional** dengan hak pantau agregat atas 17 kabupaten/kota se-Sulawesi Tenggara.
