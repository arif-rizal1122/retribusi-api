# 🧾 RANCANGAN STRUK BUKTI PEMBAYARAN RETRIBUSI TPI
## Format Struk Thermal Printer (58mm / 80mm) — Dinas Perikanan Kota Baubau
*Sistem M-PAD (Mitra Pajak, Retribusi, dan Aset Daerah)*

---

## 1. Tata Letak (Layout) Struk Thermal

Format teks terformat untuk printer bluetooth portable petugas lapangan:

```text
========================================
       PEMERINTAH KOTA BAUBAU
     BADAN PENDAPATAN DAERAH
  DINAS PERIKANAN & KELAUTAN
========================================
   BUKTI PEMBAYARAN RETRIBUSI TPI
        (SISTEM M-PAD BAUBAU)
========================================
No. Transaksi : TPI-WM/20260908/0042
No. NTPD      : 882026090809301501
Tgl / Waktu   : 08-09-2026 / 09:30:15 WITA
Lokasi TPI    : TPI Wameo
Petugas Pos   : Petugas-04 (Syahrul)
----------------------------------------
DATA SUBJEK & OBJEK RETRIBUSI:
Kapal / GT    : KM Sinar Laut / 15 GT
Pembeli/Bakul : H. Aris (PT Laut Murni)
Vol. Lelang   : 2.500 Kg
Nilai Trans   : Rp 50.000.000
----------------------------------------
RINCIAN RETRIBUSI DAERAH:
- Tambat Labuh (15 GT x 2 Hr)
  @ Rp 1.000                    Rp 30.000
- Sewa Meja (4 Unit)
  @ Rp 15.000                   Rp 60.000
- Sewa Timbangan (2 Unit)
  @ Rp 10.000                   Rp 20.000
- Pasokan Es (1.5 Ton)
  @ Rp 50.000                   Rp 75.000
- Parkir Truk Pendingin
  (1 Unit - Truk Sedang)        Rp 20.000
- Jasa Lelang TPI (1%)
  1% x Rp 50.000.000           Rp 500.000
----------------------------------------
TOTAL RETRIBUSI : Rp 705.000
----------------------------------------
Metode Bayar    : QRIS / E-Wallet
Bank / Channel  : Bank Sultra / QRIS
No. Ref Bank    : QRS981237419283
STATUS          : LUNAS / SAH
----------------------------------------
        [ QR CODE VERIFIKASI ]
    (Scan untuk Cek Keabsahan NTPD)
----------------------------------------
 Pembayaran ini sah berdasarkan PERDA 
 Pajak Daerah & Retribusi Daerah (PDRD)
              Kota Baubau.
  -- Simpan Struk Ini Sebagai Bukti --
========================================
```

---

## 2. Spesifikasi Elemen Penting untuk Pengembang

1. **NTPD (Nomor Transaksi Penerimaan Daerah)**:
   - Nomor unik yang digenerate otomatis oleh backend M-PAD saat transaksi terverifikasi LUNAS oleh sistem perbankan / Payment Gateway. Ini berfungsi sebagai audit trail resmi Keuangan Daerah.
2. **QR Code Verifikasi di Struk**:
   - Berisi URL terenkripsi yang jika dipindai oleh ponsel wajib pajak/petugas audit akan mengarah ke halaman validasi publik M-PAD (`https://mpad.baubaukota.go.id/verify/...`) untuk membuktikan struk tersebut asli dan tercatat di Kas Daerah.
3. **Format Struk Digital (PDF / WhatsApp)**:
   - Selain cetak thermal paper (bluetooth printer portable), M-PAD dapat mengirimkan versi PDF atau kartu ringkasan berbasis teks langsung ke nomor WhatsApp/Email pembeli/pembongkar kapal sesaat setelah bayar.
