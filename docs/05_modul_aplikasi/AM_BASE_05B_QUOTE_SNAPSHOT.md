# AM-BASE-05B / AM-PUPR-03B - Snapshot Quote Sewa Aset

Tanggal checkpoint: 19 September 2026

## Perubahan

`CitizenAssetController::storeRental` sekarang menyimpan `metadata.quote` pada setiap pengajuan citizen yang berhasil. Snapshot ini memuat:

- versi adapter quote (`asset-master-v1`) dan sumber `asset_items`;
- ID aset serta waktu perubahan master aset;
- tarif, satuan, kuantitas layanan, dan jumlah hari sewa;
- apakah tronton dipakai, jarak yang dikirim untuk zonasi, serta biaya tronton hasil server;
- total biaya, DP yang diminta warga, DP yang sudah dikonfirmasi (tetap nol saat pengajuan), dan sisa pembayaran.

Snapshot memakai angka yang sama dengan kolom nominal rental saat transaksi dibuat. Nilai ini membantu audit dan penjelasan perubahan quote; bukan pengesahan tarif Perda atau bukti pembayaran.

## Batas dependensi

Versi resmi tarif/zona masih menunggu D02/D04. Payment flow rental yang mengubah `confirmed_dp` setelah settlement juga belum tersedia. Tidak ada migration, perubahan response utama, consumer frontend, dashboard, atau gateway bank pada checkpoint ini.

## Validasi

`CitizenAssetRentalTest` lulus 38 test/219 assertion pada SQLite in-memory. Kasus spoof tarif/satuan/DP/tronton, tarif master kosong, dan isi snapshot diuji. PHP lint, Pint, serta `git diff --check` lulus.
