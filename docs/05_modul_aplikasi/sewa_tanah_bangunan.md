# Layanan Aset: Sewa Tanah dan Bangunan

**Dasar Hukum**: Perda Kota Baubau No. 1 Tahun 2024
**Kategori**: Retribusi Jasa Usaha (Pemanfaatan Kekayaan Daerah)

## 1. Skema Peraturan
Penyewaan ruko, gedung serbaguna, dan tanah Pemda. Berbasis **Kontrak (Perjanjian Kerja Sama)**. Tagihan (Billing) bersifat *recurring* atau *term* sesuai durasi kontrak.

## 2. Matriks Riset Blanko OPD

### A. Warga (`retribusi-mobile`)
- Warga mengakses "Katalog Aset Daerah" di aplikasi.
- Memilih tanggal sewa mulai & selesai (sistem menolak otomatis jika tanggal sudah dibooking/bentrok - *Anti-Double Booking Interlock*).
- Menulis tujuan penggunaan aset.

### B. Admin OPD (`retribusi-admin`)
- Admin BPKAD / OPD terkait me-review permohonan.
- Melakukan *appraisal* untuk menentukan tarif jika belum ada tarif baku.
- Menerbitkan draft kontrak.

### C. Penandatanganan & Penagihan (API)
- Warga dan Kepala OPD menyetujui kontrak via Tanda Tangan Elektronik (Pakta Integritas / BSrE).
- API men-*generate* tagihan (SKRD) secara otomatis sebelum jatuh tempo masa sewa setiap bulan/tahun.

### D. Petugas Lapangan (`retribusi-petugas`)
- Petugas melakukan inspeksi/surveillance (menggunakan LKOK Aset + GPS Kamera) untuk memastikan aset tidak rusak, tidak dialihfungsikan, atau tidak disub-sewakan ke pihak lain secara ilegal.
