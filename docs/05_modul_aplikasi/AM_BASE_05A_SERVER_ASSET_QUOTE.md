# AM-BASE-05A / AM-PUPR-03A — Otoritas Quote Sewa Aset

Tanggal checkpoint: 19 September 2026

## Perubahan

- `CitizenAssetController::storeRental` selalu mengambil tarif dan satuan dari `asset_items.tarif_default`/`satuan_tarif`. Nilai `tarif_per_satuan` dan `satuan_sewa` dari mobile tidak lagi menjadi otoritas.
- Biaya tronton dihitung server dari band jarak yang sudah ada. `biaya_tronton` kiriman client diabaikan; `wajib_tronton` pada master tetap memaksa opsi tronton.
- `dp` dari payload tidak dicatat sebagai pembayaran. Nilai tersimpan tetap nol sampai payment flow API mengonfirmasi settlement; input warga disimpan sebagai `metadata.requested_dp` untuk audit.
- Aset tanpa tarif master aktif ditolak dengan `ASSET_RATE_UNAVAILABLE`, sehingga pengajuan tidak dapat menghasilkan quote gratis secara tidak sengaja.

## Batas dependensi

Band tronton dan tarif master yang tersedia masih perlu dicocokkan dengan lampiran Perda/SOP resmi (D02/D04), serta payment flow rental perlu mengubah DP hanya setelah pembayaran terkonfirmasi. Review rute lapangan/zonasi akhir tetap menjadi keputusan dashboard PUPR. Tidak ada migration, perubahan admin/petugas, atau integrasi bank pada checkpoint ini.

## Validasi

`CitizenAssetRentalTest` menambah kasus spoof tarif, satuan, DP, biaya tronton, dan tarif master kosong; lulus 38 test/206 assertion pada SQLite in-memory. `PuprInspectionAndDocumentsTest` lulus 4 test/14 assertion. Consumer mobile mengirim field lama tetapi menerima nilai quote server yang sama bentuknya.

Suite `AssetRentalWorkflowTest` dan `AssetRentalBookingConflictTest` belum dapat mencapai request karena fixture membuat slug `alat-berat` yang telah di-seed migration, sehingga gagal pada unique constraint `asset_categories.slug`. Ini merupakan fixture/migration baseline yang terpisah dari perubahan quote.
