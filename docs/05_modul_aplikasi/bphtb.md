# Layanan Pajak: BPHTB

**Dasar Hukum**: Perda Kota Baubau No. 1 Tahun 2024
**Sistem Pemungutan**: Official Assessment (Melibatkan PPAT/Notaris)
**Tarif**: 5%
**NPOPTKP**: Jual Beli (Rp 80 Juta), Waris (Rp 300 Juta)

## 1. Skema Perhitungan & Regulasi
Dikenakan atas perolehan hak atas tanah dan/atau bangunan.
- Jika NPOP (Nilai Transaksi) < NJOP PBB, maka yang digunakan adalah NJOP PBB.
- **Rumus**: `(NPOP/NJOP - NPOPTKP) x 5%`

## 2. Matriks Riset Blanko OPD

### A. Warga / PPAT (`retribusi-mobile` / Web PPAT)
- Notaris / Warga menginput NOP, NIK pembeli/penjual, dan **Nilai Transaksi Kasat Mata**. Mengunggah draft Akta/Sertifikat.
- Warga tidak perlu menghitung NPOPTKP secara manual.

### B. Admin & Backend API (`retribusi-admin` & `retribusi-api`)
- API membandingkan Nilai Transaksi dengan database NJOP PBB. Mengambil nilai tertinggi.
- API mengurangkan Rp 80 Juta atau Rp 300 Juta, lalu mengalikan 5% secara otomatis (Readonly bagi Warga).
- Admin me-review dan membubuhkan TTE untuk menerbitkan SSPD BPHTB (Syarat Pembuatan Akta di BPN).
