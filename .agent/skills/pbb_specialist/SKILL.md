---
name: PBB-P2 Integration Specialist
description: Panduan khusus untuk manajemen Pajak Bumi dan Bangunan (PBB-P2) di ekosistem M-PAD, mencakup inkuiri NOP, NJOP, dan distribusi E-SPPT.
---

# 🏠 PBB-P2 Integration Specialist

Skill ini mendefinisikan standar integrasi dan pengelolaan data PBB-P2 di sistem M-PAD Kota Baubau.

## 🏢 Inkuiri NOP (Nomor Objek Pajak)
Pencarian data PBB secara real-time dari sistem Bapenda (SISMIOP).
- **Endpoint**: `POST /api/pbb/bapenda/inquiry`.
- **Logic**: Masukkan NOP (18 digit) untuk mendapatkan status tagihan tahun berjalan dan tunggakan.
- **Verification**: Data NOP dapat dihubungkan (`linkNop`) ke akun Wajib Pajak untuk monitoring berkala di aplikasi Mobile.

## 📄 E-SPPT (Surat Pemberitahuan Pajak Terhutang)
Digitalisasi dokumen ketetapan PBB.
- **Format**: PDF yang sah dengan integrasi TTE (Tanda Tangan Elektronik).
- **Download**: `GET /api/pbb/bapenda/download-sppt`.
- **Registry**: SPPT digital wajib memiliki QR-Code unik untuk validasi keaslian di portal E-Registry.

## 💰 Kalkulasi NJOP & Ketetapan
- **Model**: `PbbNjopClassification`, `TransactionPbb`.
- **Logic**: Perhitungan menggunakan parameter Luas Bumi (Tanah), Kelas Bumi, Luas Bangunan, Kelas Bangunan, NJOPTKP, dan Tarif daerah.
- **Service**: `PbbCalculationService.php`.

## 🔄 Sinkronisasi & Rekonsiliasi
- **Sync All**: `POST /api/pbb/bapenda/sync-all` untuk sinkronisasi master data objek PBB ke portal M-PAD.
- **Payment Reversal**: `POST /api/pbb/bapenda/reversal` untuk pembatalan transaksi PBB jika terjadi kesalahan input atau pembatalan dari Bank.

## 📈 Dashboard PBB
- **Stats**: Pantau performa realisasi PBB per wilayah (Kecamatan/Kelurahan) melalui `GET /api/pbb/bapenda/stats`.
- **GIS Layout**: Visualisasi sebaran objek PBB di atas peta Satelit (ESRI).
