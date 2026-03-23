# Tahap 4: Pengujian Fitur Kritis Tambahan (Edge Cases)

Selain alur inti pembayaran, fungsi-fungsi teknis spesifik administratif pemerintahan juga harus disorot untuk memastikan legalitas dan validitas data sistem.

## 4.1. Ujian Pengesahan Surat Tanda Tangan Elektronik (TTE)
- **Langkah 1**: Login kembali sebagai Admin Web (`admin.sipanda.online`).
- **Langkah 2**: Pergi menjelajahi menu **"Dokumen TTE / Pengesahan"**.
- **Langkah 3**: Lakukan pencarian nama dokumen "Surat Ketetapan (SKPD)" yang diterbitkan untuk WP `Budi Tester UAT` pada Tahap 3 sebelumnya.
- **Langkah 4**: Simulasikan Kepala Badan / Pejabat yang berwenang menge-klik tombol aksi **"Tandatangani Dokumen / Approve TTE"**.
- **Hasil yang Diharapkan**: 
  - File PDF yang tadinya polosan, kini memiliki bubuhan stempel visual berupa *QR Code* atau *Digital Stamp* resmi di pojok/akhir halaman.
  - Saat Surat PDF dicetak/dibiarkan di layar lalu disorot *QR Code*-nya menggunakan fitur kamera Smartphone konvensional biasa, *browser* HP harusnya langsung mengarah secara *secure* (HTTPS) ke laman web resmi verifikasi (misal kominfo/BSRE/laman verifikasi mpad) menegaskan Dokumen Valid ditandatangani hari ini.

## 4.2. Ujian Visualisasi Pemetaan Cerdas (GIS / Peta Potensi)
- **Langkah 1**: Masih di Admin Web (`admin.sipanda.online`), buka sub-fitur **"Peta Potensi"**.
- **Hak Prioritas Kesuksesan Rute**:
  - Halaman Peta tidak boleh memuntahkan peringatan Error _CORS Policy_ atau terhenti di layar abu-abu (*Blank map tiles*).  
  - Peta pulau sebaran sanggup memuat *layer satelit* atau *jalan*.
  - Menzoom mendekat, terdapat bentukan grafis ikon *pin drop* / koordinat lokasi objek pajak "Warung Makan xyz" (dan objek WP lain).
  - Ketika sebuah *pin* diketuk dua jari/di-klik mouse, sistem sukses me-*render* jendala kecil (info-window) berisikan rincian singkat objek bangunan/usaha beserta melampirkan *Foto Lokasi* tempat tersebut asri tanpa rusak/broken image box.

## 4.3. Ujian Anomali (Pembatalan/Diskon/Amnesty Penolakan)
- **Langkah 1**: Uji coba ekstrem. Daftarkan dan terbitkan 1 Tagihan Asal-Asalan PBB-P2 untuk Wajib Pajak `Tester Lain` senilai Rp 10 Juta Rupiah.
- **Langkah 2**: Log out, masuk sebagai Manajer/Kasubbid. Coba batalkan 1 tagihan tersebut menggunakan aksi Pembatalan/Pengajuan Keringanan Tagihan (Amnesty) menjadi Diskon 50%.
- **Langkah 3**: Sistem harus menghentikan atau mengubah *invoice ID* Wajib Pajak tadi secara matematika presisi ter-kalkulasi menjadi angka akhir penagihan yang sisa dibayar cuma 5 Juta secara wajar. (Tidak terjadi *bug* Minus atau Infinity Array).
