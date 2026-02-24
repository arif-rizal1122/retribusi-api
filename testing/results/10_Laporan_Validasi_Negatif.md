# 🛑 Laporan Hasil Uji Coba Input Invalid (Negative Testing)

**Waktu Eksekusi**: 2026-02-24 14:38:46
Pengujian ini sengaja merusak input API untuk memastikan Controller menolak transaksi berakibat fatal ke Database.

### 1. Injeksi Pembayaran Negatif (Rp -5.000.000)
*(Skip: Belum ada data Tagihan Unpaid untuk diuji)*

### 2. Double Payment / Membayar Ulang SKPD Lunas
- ✅ **SUKSES DITOLAK**: Sistem tahu resi sudah lunas. Permintaan Dobel dihentikan Controller (HTTP `422`).

### 3. Payload Bolong (Required Validation)
- ❌ **BUG**: Data kurang field masuk ke Controller logic (HTTP `404`).

