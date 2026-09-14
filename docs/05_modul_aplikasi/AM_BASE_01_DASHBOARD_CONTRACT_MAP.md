# AM-BASE-01: Peta kontrak dashboard dan mobile warga

Tanggal pemeriksaan: 14 September 2026. Status: pemetaan sumber lokal selesai; integrasi dashboard eksternal belum diverifikasi.

Snapshot sebelum checkpoint: API `4c383b2`, mobile `40424e1`, admin `836167b`, petugas `b09c9b8`. Pemeriksaan admin/petugas hanya membaca source. Dokumen ini tidak membuktikan database, deployment, transaksi bank, atau UAT.

## Batas kepemilikan

API menjadi otoritas data, validasi, hak akses, nominal, billing, dan pembayaran. Mobile menjadi portal warga untuk memilih layanan, mengajukan, melihat estimasi/status, membayar tagihan, dan mengakses dokumen yang diterbitkan sumber berwenang. Pengelolaan aset, survei, persetujuan, serta pengelolaan bisnis tidak perlu dibuat ulang untuk menyelesaikan portal warga.

`retribusi-mobile/mpad.code-workspace` merujuk `../retribusi-aset` dan `../retribusi-bisnis`. Kedua folder tersebut tidak tersedia pada workspace yang diperiksa. Karena itu, owner operasional, route, ID, callback, dan penerbit dokumen dari kedua dashboard tersebut **belum diketahui**, bukan dinyatakan belum diimplementasikan. D01 tetap terbuka untuk integrasi akhirnya.

| Domain | Sumber yang terbukti pada empat repo lokal | Consumer yang ditemukan | Batas keputusan |
|---|---|---|---|
| Katalog/jadwal/sewa aset | API: `AssetCategory`, `AssetItem`, `AssetRental`, `CitizenAssetController`, layanan availability | Mobile `/aset`, `assetService.ts` | API menyimpan permohonan; hubungan dengan dashboard aset terpisah belum diketahui |
| Survei/persetujuan/kontrak/inspeksi | API: `AdminAssetController` | Admin pengelolaan sewa/dashboard PUPR; petugas daftar tugas/form survei | Pertahankan kontrak consumer lama; warga tidak diberi aksi persetujuan |
| Dokumen sewa | API: `documentSipab`, `documentBast` | Endpoint internal tersedia | Response JSON belum merupakan berkas resmi atau bukti TTE |
| Pencatatan usaha lokal | Mobile: `KelolaBisnis`, `businessLedger`, `businessBackup` | `/kelola-bisnis` dan komponen POS | Penyimpanan lokal/backup perangkat bukan bukti sinkronisasi dashboard bisnis |
| Pajak omzet/AFT | API: `KalkulatorBisnisController`, `AutoDeductLog`, job `ExecuteAutoDebitAFT` | Source `aftService`, `BusinessCalculator`, `AftDashboard` | Dua route halaman AFT kini redirect ke Home; keberadaan source bukan bukti alur aktif |
| Adopsi kalkulator | API: `BusinessUnit`, `BusinessUnitController` | Endpoint internal `/api/business-units` | Data adopsi tidak membuktikan sinkronisasi ledger, stok, pesanan, atau HPP |
| Tagihan/pembayaran warga | API: citizen bills, payment requests, payment history | Mobile `/tagihan`, `/pembayaran` | Nomor sewa/local business ID tidak dapat langsung diperlakukan sebagai bill ID |

## Kontrak aset yang sudah tersedia

Semua path di bawah mencantumkan prefix `/api`. Rujukan utama: [routes/api.php](../../routes/api.php), [CitizenAssetController](../../app/Http/Controllers/CitizenAssetController.php), [AdminAssetController](../../app/Http/Controllers/AdminAssetController.php), dan [AssetRental](../../app/Models/AssetRental.php).

| Aksi | Route aktual | ID/request dan hasil yang penting | Penggunaan |
|---|---|---|---|
| Kategori | `GET /api/citizen/asset/categories` | `data[]`: `id`, nama, slug, ikon, jumlah item | Mobile memilih kategori API |
| Item | `GET /api/citizen/asset/items` | Filter `category_id`, `status`; `data[]` memuat item ID, tarif, satuan, kondisi, `wajib_tronton`; `category` pada DTO mobile berupa nama | Simpan ID item; jangan menganggap nama kategori sebagai FK |
| Template | `GET /api/citizen/asset/form-template/{categoryId}` | Envelope `category` dan `template` | Berbeda dari envelope daftar; adapter tidak boleh meratakan semua response secara generik |
| Kalender | `GET /api/citizen/asset/items/{id}/availability` | Query `month=YYYY-MM`; status harian dan saran slot | Informasi kalender perlu dicek ulang saat submit |
| Pengajuan | `POST /api/citizen/asset/rentals` | Item, tanggal, lokasi, pilihan tronton, dan field biaya; hasil `data` rental beserta item | API mengunci item dalam transaksi dan memeriksa benturan jadwal |
| Riwayat/detail | `GET /api/citizen/asset/rentals`, `GET /api/citizen/asset/rentals/{id}` | `id`, `rental_code`, status, kontrak, survey, biaya dan tahap pembayaran | Query dibatasi taxpayer; peninjauan jenis principal/field publik tetap task BASE-04 |
| Operasional | `GET /api/asset/dashboard`, `GET /api/asset/rentals` | Ringkasan dan daftar internal | Bukan endpoint katalog warga |
| Keputusan | `POST /api/asset/rentals/{id}/{survey,contract,verify,approve,reject,complete,inspection}` | ID rental lokal; setiap suffix merupakan route terpisah | Dijalankan sumber berwenang; jangan dipanggil warga |
| Dokumen | `GET /api/asset/rentals/{id}/document/{sipab,bast}` | JSON metadata dokumen | Belum ada kontrak unduhan citizen dalam `assetService.ts` |

Status rental yang dipakai controller: `Diajukan`, `Diverifikasi`, `Disetujui`, `Ditolak`, `Selesai`. Verifikasi menerima `Diajukan`; persetujuan menerima `Diajukan` atau `Diverifikasi`; penyelesaian menerima `Disetujui` atau `Diverifikasi`. Jadi mobile tidak boleh mengasumsikan semua permohonan selalu melewati setiap tahap. Status kalender (`available`, `buffer`, `booked`, `pending_verification`, `disabled_past`, `out_of_service`) merupakan domain berbeda.

`AssetRental.id`, `rental_code`, `asset_item_id`, `nomor_kontrak`, `retribution_type_id`, dan `retribution_classification_id` memiliki fungsi berbeda. Pada model/controller sewa yang diperiksa, belum ditemukan relasi langsung ke Bill atau PaymentRequest. Field `dp`, `sisa`, `tahap_1_*`, `tahap_2_*`, dan status `Selesai` bukan bukti settlement bank. Ringkasan pendapatan dashboard yang menjumlah total sewa selesai juga tidak dapat digunakan sebagai riwayat penerimaan kas.

Controller citizen masih menerima override `tarif_per_satuan`, `biaya_tronton`, dan `dp` dari request; terdapat perhitungan zona tronton dalam source. Ini perlu ditutup melalui AM-BASE-05 dengan nilai sah dari backend, bukan disalin menjadi aturan mobile atau dianggap tarif Perda yang sudah terverifikasi. Audit dasar hukum tetap D02.

Belum ditemukan callback sinkronisasi dashboard aset terpisah pada route/controller yang diperiksa. Perubahan operasional lokal dilakukan lewat route internal API. Jika dashboard terpisah memakai database/API lain, diperlukan mapping external ID, autentikasi sumber, versi keputusan, idempotensi, serta aturan konflik sebelum integrasi write diaktifkan.

## Dokumen dan survei: batas bukti

`documentSipab` dan `documentBast` mengembalikan JSON. Source SIPAB memasukkan label `TTE_VERIFIED_BSRE` secara literal dan membentuk URL QR; pemeriksaan route lokal belum menemukan implementasi verifikasi SIPAB/BAST yang membuktikan URL tersebut bekerja. Jangan menampilkan label ini sebagai hasil verifikasi tanda tangan atau menganggap JSON sebagai PDF resmi. Penerbit, berkas, template, status tanda tangan, dan otorisasi unduh tetap perlu kontrak D06.

Consumer yang harus dipertahankan saat perubahan API berikutnya:

- Admin `src/pages/AssetRentalsManagement.tsx`: daftar sewa, verify, approve, contract, complete; `src/pages/PuprAssetDashboard.tsx`: dashboard/daftar.
- Petugas `src/pages/DaftarTugas.tsx`: daftar sewa; `src/components/AssetSurveyForm.tsx`: submit survei.
- Mobile `src/services/assetService.ts`: kategori, item, template, kalender, pengajuan, riwayat.

Form petugas memiliki label rekomendasi tronton berbentuk kalimat, sedangkan UI admin memakai pilihan tersendiri. Normalisasi harus mempertahankan data lama atau memakai adapter citizen; enum shared tidak boleh diganti sepihak.

## Kontrak bisnis dan batas mobile

Rujukan: [KalkulatorBisnisController](../../app/Http/Controllers/Api/Merchant/KalkulatorBisnisController.php), [BusinessUnitController](../../app/Http/Controllers/BusinessUnitController.php), dan `retribusi-mobile/src/App.tsx`.

| Permukaan | Kontrak/sumber aktual | Implikasi |
|---|---|---|
| `/kelola-bisnis` | `KelolaBisnis` memakai ledger, unit usaha, menu, meja, shift dan backup lokal; route tidak dibungkus `ProtectedRoute` | Jangan otomatis mengikat local business ID ke taxpayer/bill atau menghapus fitur ini dalam task portal |
| `/business-calculator`, `/aft-dashboard` | Keduanya `Navigate` ke `/home` pada snapshot ini | Catatan lama tentang route AFT aktif dengan feature flag tidak menggambarkan routing saat ini |
| `/kalkulator-pajak` | Simulasi generik `POST /api/simulate-tax` | Estimasi pajak berbeda dari pembayaran/AFT dan ledger usaha |
| AFT submit | `POST /api/citizen/merchant/submit-omzet`: `transaction_amount`, `tax_object_id`, `bill_id` | Wajib merchant taxpayer, ownership, SKDR/objek/bill sah, dan readiness; nominal pajak divalidasi API |
| AFT hasil submit | HTTP 201 dengan `data.reference_number`, `tax_amount`, `omzet`, `bill_id`, `tax_object_id`; job diproses terpisah | Penerimaan request bukan pembayaran berhasil |
| AFT read | `GET /api/citizen/merchant/aft-availability`, `GET /api/citizen/merchant/aft-history` | History memakai status `pending`, `processing`, `success`, `failed`, `cancelled`; `can_retry`/`can_cancel` false |
| Adopsi bisnis | `GET /api/business-units` internal; submit omzet melakukan upsert `BusinessUnit` | Response `data` paginator dan `summary` bukan kontrak upload ledger/backup POS |

History AFT memberikan `reference_number`, `bill_reference`, periode, ringkasan objek, nominal, dan waktu. ID bisnis lokal mobile berupa identitas perangkat/unit lokal; API `tax_object_id`, `bill_id`, serta AFT `reference_number` tetap harus dibedakan. Komponen branding POS juga memanggil pengecekan NIB publik; hal ini tidak membuktikan adanya sinkronisasi transaksi POS. `businessBackup` mengekspor data perangkat; backup tidak diperlakukan sebagai buku besar otoritatif API.

AFT Bank Sultra merupakan jalur direct debit dengan readiness tersendiri. Dokumen ini tidak memvalidasi spesifikasi bank, callback pembayaran, aktivasi produk, atau status UAT. Inventaris per produk menjadi AM-PAY-01; jangan menyamakan AFT dengan VA Sultra ataupun BRIVA.

## Keputusan persiapan untuk task berikutnya

1. AM-BASE-02 dapat disusun sekarang dari route nyata: katalog, pengajuan, status, dan referensi bill/payment/document. Pisahkan field existing dari kontrak usulan; nilai yang belum terbit bersifat kosong disertai status ketersediaan, bukan ID/URL buatan.
2. AM-BASE-03/04/05 harus memeriksa consumer shared sebelum mengubah status, otorisasi, atau nominal. Endpoint citizen baru/adaptor dapat menjaga kompatibilitas; akses internal tidak dibuka ke warga.
3. Hubungkan pembayaran lewat bill resmi dan payment request API. Keputusan operasional, selesai layanan, dan pembayaran merupakan status terpisah.
4. Tidak menambahkan dashboard aset/kalkulator bisnis, menu admin/petugas, integrasi bank baru, atau dokumen TTE simulasi dalam checkpoint ini.

| Dependensi terbuka | Informasi yang masih diperlukan | Yang tetap dapat disiapkan lokal |
|---|---|---|
| D01: aset/bisnis | Source/API dashboard, owner entity, source ID, mekanisme keputusan/callback, autentikasi dan konflik | Peta sumber ini; kontrak citizen dan compatibility matrix |
| D02/D04: tarif/SOP | Master tarif sah, zona/mobilisasi, SOP survei dan persetujuan | Identifikasi input biaya tak tepercaya; rancangan validasi/backend quote |
| D03: bank | Kontrak produk, environment dan bukti pengujian bank | Inventaris implementasi dan test terisolasi sesuai kontrak tersedia |
| D06: dokumen | Penerbit, template, berkas, TTE/verifikasi dan izin unduh | Kontrak referensi dokumen; jangan klaim dokumen resmi tersedia |

AM-BASE-01 selesai dalam arti seluruh sumber lokal dipetakan dan sumber yang tidak tersedia dicatat sebagai dependensi, sesuai kriteria task. D01 tetap terbuka; tidak ada klaim integrasi dashboard terpisah telah selesai.

## Validasi checkpoint

- Inspeksi read-only route, controller, model, service mobile, routing, dan consumer admin/petugas menggunakan `rg` dan `Get-Content`.
- `Test-Path` untuk `retribusi-aset` dan `retribusi-bisnis`: keduanya tidak tersedia.
- Perubahan checkpoint hanya Markdown. Validasi mencakup target tautan relatif, keberadaan referensi source, dan `git diff --check`; tidak menjalankan suite aplikasi untuk dokumentasi ini.
- Commit/push dicatat terpisah pada handoff workspace. Tidak ada pengujian browser, perangkat, bank, atau produksi pada checkpoint ini.
