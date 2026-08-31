# Layanan Retribusi: Pelayanan Pasar

**Dasar Hukum**: Perda No. 1/2024 & Perwali 55/2025
**Kategori**: Retribusi Jasa Umum / Jasa Usaha
**Sistem Pemungutan**: Official Assessment (Recurring Billing)

## 1. Skema Perhitungan & Regulasi
Tarif sewa lapak/kios/pelataran pasar dikalikan **Koefisien Zonasi**:
- Premium (1.3): Kawasan strategis/wisata.
- Strategis (1.2): Pusat perdagangan.
- Ekonomi (1.1): Pasar Wameo, dll.
- Umum (1.0).

## 2. Matriks Riset Blanko OPD

### A. Warga / Pedagang (`retribusi-mobile`)
- Pedagang mengajukan sewa lapak dengan memilih lokasi di peta pasar, menginput jenis dagangan, dan mengunggah KTP.
- Pedagang hanya melihat "Tarif Akhir", tanpa perlu mengisi form koefisien zonasi.

### B. Petugas Penagih (`retribusi-petugas`)
- Petugas pasar berkeliling membawa aplikasi M-PAD. 
- Aplikasi menampilkan daftar lapak yang menunggak (berbasis jarak/GPS).
- Pembayaran dilakukan via **QRIS Offline** atau **Tunai**.
- Petugas mencetak struk **SSRD Thermal Bluetooth** di tempat (menggantikan karcis sobek rawan fraud).

### C. Admin & API
- API otomatis menerbitkan tagihan (SKRD) bulanan/harian.
- Admin mengawasi realisasi penerimaan per blok pasar secara *real-time*.
