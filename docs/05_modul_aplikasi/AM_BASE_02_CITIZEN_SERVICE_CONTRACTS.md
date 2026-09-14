# AM-BASE-02 — Kontrak layanan warga

14 September 2026. Snapshot API `8e0d530`, mobile `f1363d9`. Status: spesifikasi selesai, bukan implementasi endpoint baru. Acuan: [peta dashboard](AM_BASE_01_DASHBOARD_CONTRACT_MAP.md), [inventaris bank](../10_payment_gateway_h2h/AM_PAY_01_BANK_PRODUCT_INVENTORY.md), [route aktual](../../routes/api.php).

## Batas kontrak

Mobile memakai API untuk katalog, estimasi, pengajuan, pelacakan, tagihan, pembayaran dan dokumen. Keputusan pengelola tetap berasal dari dashboard/sumber berwenang. Semua path berlabel **usulan** di sini belum tersedia dan tidak boleh dipanggil mobile sebelum implementasi, otorisasi, dan test diselesaikan. Path existing dipertahankan; tidak mengganti envelope/enum shared secara diam-diam.

Kontrak baru menggunakan `/api/citizen/service-requests` untuk read model lintas layanan; pengajuan/quote memakai resource layanan masing-masing. Ini keputusan rancangan internal untuk implementasi berikutnya, bukan klaim kesepakatan dengan dashboard yang source-nya belum tersedia. Identitas eksternal tetap pasangan `source_system + source_id`; tidak menimpa primary key API.

## Resource bersama yang diusulkan

Response sukses endpoint baru: `{ "data": ... }`; daftar paginated menambahkan `meta` (`current_page`, `last_page`, `per_page`, `total`) dan `links` (`next`, `prev`). Error mengikuti Laravel: `message`, dan `errors` per field untuk 422. Field tambahan kompatibel; enum yang belum dikenal ditampilkan sebagai menunggu informasi dan tidak mengaktifkan aksi.

| Resource/field | Wajib dan arti | Aturan |
|---|---|---|
| `service.code`, `service.name`, `service.opd_id` | Wajib pada katalog/pengajuan baru; kode stabil dari master API | Kode usulan: `dishub.harbor_kiosk`, `dishub.parking_subscription`, `pupr.equipment_rental`, `pupr.septic_service`, `dlh.waste`, `dlh.environment_lab`; bukan ID klasifikasi DB hardcoded |
| `request.id`, `request.number`, `service`, `created_at`, `updated_at` | Wajib pada read model pengajuan | ID server; permohonan tidak diciptakan dari bill atau status pembayaran saja |
| `object` | `id`, `type`, `display_name`; nullable untuk layanan tanpa objek terdaftar | ID bangunan/NOP/unit aset dibedakan; NIK bukan referensi tagihan |
| `workflow` | `status`, `label`, `reason_code`; `updated_at` | Usulan: `submitted`, `under_review`, `needs_revision`, `quoted`, `approved`, `scheduled`, `in_service`, `completed`, `rejected`, `cancelled`; jadwal/approval hanya dari sumber berwenang |
| `quote` | Nullable; jika ada wajib `id`, `version`, `status`, `currency`, `lines`, `total`, `expires_at` (boleh null jika sumber tidak menetapkan) | Nominal decimal string, currency `IDR`; baris memuat kode/nama komponen, quantity, unit, unit_amount, amount, tariff_version. Tidak memakai float mobile sebagai otoritas |
| `quote.status` | `estimated`, `awaiting_confirmation`, `accepted`, `superseded`, `expired` | Estimasi bukan bill. Quote direvisi dengan versi baru; bill terbayar tidak dimutasi dari revisi quote |
| `billing` | Wajib object: `status`, `bills[]`, `reason_code` nullable | `not_issued`, `unpaid`, `partially_paid`, `paid`, `cancelled`, `disputed`, `unknown`. `bills[]` berisi ID bill resmi, nomor, periode, currency, total, status, can_pay; kosong jika belum diterbitkan |
| `payment_requests[]` | Wajib array, boleh kosong | Referensi ID/number/channel/method/status/expiry dari API; satu pengajuan dapat memiliki beberapa termin dan percobaan. Tidak menyamakan `request.id` layanan dengan `payment_request.id` |
| `documents[]` | Wajib array, boleh kosong | Jika ada: `id`, `type`, `version`, `status`, `issued_at`, `can_download`, `download_path`, `signature_status`, `verification_path`; path nullable dan hanya tersedia jika benar-benar diterbitkan/diizinkan |
| `actions[]` | Wajib array aksi yang diizinkan server | Setiap aksi memuat `code`, `enabled`, `reason_code`; UI tidak menebak izin dari label. `approve`, `upload_results`, `verify_remittance` tidak menjadi aksi warga |
| `source` | Sistem asal, ID referensi opaque, versi keputusan; hanya field aman pada response warga | Credential/callback payload, identitas internal peninjau sensitif dan rekening penuh tidak dipublikasikan |

Usulan read: `GET /api/citizen/service-requests?service_code=&status=&page=` dan `GET /api/citizen/service-requests/{id}`. Keduanya wajib taxpayer autentikasi dan owner scope; ID lain mengembalikan 404. Request baru mengambil taxpayer dari principal, tidak dari payload. Pengajuan atas nama pihak lain memerlukan kontrak representasi D05, bukan pencarian taxpayer berdasarkan nama.

Status workflow, bill, payment request dan dokumen merupakan empat domain terpisah. Contoh: alat sudah `completed`, termin kedua masih `unpaid`; lab `completed` tetapi dokumen `awaiting_signature`. `disputed` bukan reversal/refund otomatis. Mapping legacy (`UNPAID`, `PAID`, `lunas`, dll.) akan diterapkan melalui AM-BASE-03 dengan test, tanpa mengganti enum internal hanya karena tabel rancangan ini.

Implementasi parsial AM-BASE-03B (14 September 2026): response `/api/citizen/bills` menggunakan `CitizenBillStatus` untuk alias legacy dan status `pending`, `overdue`, `paid`, `pending_verification`, `cancelled`, `expired`, `disputed`, `unknown`. Hanya pending/overdue yang dapat memiliki can_pay=true; status kosong/tidak dikenal tidak diubah menjadi unpaid karena jatuh tempo atau klaim. Pemetaan ini hanya pada response citizen, tanpa penulisan ulang status database. Matriks PHP standalone 26 kasus dan consumer mobile lulus; feature endpoint masih tertunda karena autoload Laravel tidak tersedia. AM-BASE-03 belum selesai end-to-end.

## Kontrak per layanan

| Layanan | Existing yang diperiksa | Usulan endpoint baru | Input wajib dan referensi |
|---|---|---|---|
| Dishub kios pelabuhan | `GET /api/parking/kios-jembatan-batu`, POST suffix `/generate-bills`; daftar tenant/tarif masih hardcoded pada [ParkingController](../../app/Http/Controllers/ParkingController.php) | GET `/api/citizen/harbor/kiosks`; POST `/api/citizen/harbor/rental-requests` | Katalog: master kiosk ID, lokasi, periode/syarat, availability dan tariff reference. Pengajuan: `kiosk_id`, `requested_start`, `requested_end`, kontak; bukti hak sewa/masa final dari pengelola. Idempotency-Key wajib untuk create |
| Dishub parkir langganan | Route `/api/parking/locations`, `/sessions`, shift/jukir mengatur operasional; kontrak citizen langganan belum ditemukan | GET `/api/citizen/parking/subscription-plans`; POST `/api/citizen/parking/subscription-requests` | `plan_id`, `vehicle.registration_number`, `vehicle.type`, `requested_start`; periode, cakupan lokasi, tarif/kapasitas dari master. Tidak membuat shift/deposit jukir dari mobile |
| PUPR alat berat | GET `/api/citizen/asset/categories`, `/items`, `/form-template/{categoryId}`, `/items/{id}/availability`; POST/GET `/rentals`, GET `/rentals/{id}` di prefix asset | POST `/api/citizen/asset/quotes`; GET `/api/citizen/asset/quotes/{id}`; read model pengajuan mengadaptasi rental existing | Quote: `asset_item_id`, requested dates, quantity/unit, lokasi penggunaan, titik opsional, pekerjaan, pilihan mobilisasi. Submit existing mengikuti validator aktual; kontrak baru memakai `quote_id`/`quote_version`, bukan nominal dari client |
| PUPR penyedotan kakus/tinja | Resource asset/rental yang sama; field kategori khusus di [CitizenAssetController](../../app/Http/Controllers/CitizenAssetController.php) | Gunakan quote asset dan read model dengan service code terpisah; tidak membuat master armada duplikat | Nama/kontak konsumen, alamat, jenis bangunan, waktu diminta, jumlah rit diminta, titik/access note opsional. Rit/armada/biaya final setelah keputusan layanan; penamaan/tarif resmi D02 |
| DLH sampah | POST `/api/dlh/trash/calculate`, `/transfer-occupant/{taxObjectId}`, `/generate-bills`, alias `/api/v1/dlh`; [controller](../../app/Http/Controllers/Api/V1/Dlh/DlhTrashBillingController.php) | POST `/api/citizen/waste/inquiries`; POST `/api/citizen/waste/object-claims`; GET `/api/citizen/waste/objects`; POST `/api/citizen/waste/change-requests` | Inquiry: `building_id` namespace resmi, bukan NIK; response minimum. Claim: ID objek dan bukti seperlunya; perubahan: `object_id`, `change_type`, tanggal efektif, alasan. Keputusan ownership/tunggakan oleh sumber sah; D05/D07 |
| DLH lab lingkungan | GET `/api/dlh/lab/parameters`, POST `/orders`, GET `/orders/{id}`, POST `/orders/{id}/billing`, `/results` di prefix lab; alias v1 | GET `/api/citizen/lab/parameters`; POST/GET `/api/citizen/lab/orders`; GET `/api/citizen/lab/orders/{id}` | `sample_matrix`, parameter IDs unik/aktif, `sampling_mode` (`self_delivery`/`officer_sampling`), tanggal/waktu diminta, kontak; alamat/titik wajib sesuai mode dan SOP. Jadwal diminta bukan slot terkonfirmasi. Billing/result tetap aksi internal |

Kontrak lokasi baru: `{latitude, longitude}` WGS84, keduanya finite, latitude [-90,90], longitude [-180,180]; titik tidak otomatis diisi pusat kota atau GPS tanpa pilihan warga. Adaptor existing tetap mengirim `koordinat` sebagai string `lat,lng`. Alamat tetap bisa diisi tanpa GPS; batas area layanan menunggu geometri/SOP D04. Jarak garis lurus bukan otomatis tarif tronton.

Semua create/accept/retry kontrak baru memakai Idempotency-Key yang dicakup taxpayer+operasi. Payload sama mengembalikan resource semula; payload berbeda dengan key sama menghasilkan 409. Timestamp ISO-8601 dengan offset; tanggal requested disimpan terpisah dari confirmed. Quote kedaluwarsa/revisi tidak otomatis ditagihkan ulang.

## Envelope existing yang harus dipertahankan

| Existing | Response/input yang benar pada source | Konsekuensi consumer |
|---|---|---|
| Asset categories/items/rentals | `{data: ...}`; form template `{category, template}` | `assetService.ts` sudah memiliki penanganan berbeda; jangan melakukan unwrapping universal |
| Asset create | Validator mewajibkan `asset_item_id`, `tanggal_mulai`, `lokasi_penggunaan`; `satuan_sewa`, `metode_pembayaran`, tanggal selesai dan biaya/DP nullable | AM-BASE-05 harus menutup otoritas nominal dengan compatibility plan; spesifikasi ini tidak mengesahkan biaya dari mobile |
| Lab create | Wajib `applicant_name`, `contact_phone`, `sample_matrix`, `sampling_date`, `parameter_ids`; response `{status,message,data:{order,parameters}}` | Client API mobile mengembalikan JSON langsung, sehingga `res.data.data` saat ini salah; jangan mengaktifkan write baru tanpa ownership BASE-04 |
| Lab detail | `{status,data:{order,bill,parameters}}`; parameter detail adalah snapshot, berbeda dari katalog | `bill` nullable; hasil/katalog memakai tipe berbeda |
| Lab public verify | `GET /api/verify/dlh-lab/{orderNumber}` → `{status,data:{order_number,status,tte_status,...,parameters}}` | Bukan `data.order`; source memuat identitas/hasil dan tidak memberikan unduhan. Jangan jadikan endpoint publik sebagai pelacakan owner-scoped |
| Citizen bills/payments | GET `/api/citizen/bills`, `/payments/history`; POST `/payment-requests` menerima `bill_ids` 1–20 unik dan `method` `bri_va`/`sultra_va` | Referensi bill berasal dari API milik principal. Kesiapan Sultra masih bermasalah menurut AM-PAY-01; tidak mengaktifkan channel baru dari rancangan |
| Dokumen receipt | GET `/api/bills/{bill}/sspd` existing | Pakai path receipt dari payment API dan autentikasi; SIPAB/BAST JSON bukan receipt atau PDF resmi |

## Matriks kompatibilitas consumer

| Consumer lokal | Kontrak existing yang dipakai | Perubahan yang diizinkan dalam scope API/mobile | Handoff yang diperlukan sebelum perubahan shared |
|---|---|---|---|
| Mobile `assetService`, `Aset` | Katalog/template/calendar/rental citizen | Adapter quote/read model baru setelah tersedia; tetap `koordinat` string pada route lama | Nominal/enum/request baru harus memiliki fallback yang aman dan test API |
| Mobile `dlhLabService`, `LabAirDlh` | Path saat ini kehilangan `/api`, parser/verify envelope salah | Pindah ke endpoint citizen owner-scoped setelah BASE-04; state error/retry dan larangan aksi internal | Jangan memperbaiki path billing publik-warga sehingga akses internal yang belum aman justru aktif |
| Mobile `citizenBillingService`, `paymentRequestService`, `Pembayaran` | Bill/availability/lifecycle/receipt | Tambahan service/object reference aman dan grouping backend | Status/cancel/gate baru harus diuji dengan consumer existing |
| Admin `DishubDashboard`, `AssetRentalsManagement`, `PuprAssetDashboard`, `DlhDashboard`, `H2HLogs` | Kios, asset internal, DLH v1 spatial/report, log/reconcile | Read-only dalam pekerjaan ini; endpoint/field legacy tetap | Jika role/response perlu migrasi UI admin, task tersebut diblokir sampai handoff |
| Petugas `parkingService`, `DaftarTugas`, `AssetSurveyForm` | Shift/transaksi parkir, daftar rental/survei | Read-only; bukan consumer pengajuan warga baru | Cash/QRIS pending/setoran tidak boleh digabung sebagai paid hanya untuk normalisasi UI |
| Dashboard aset/bisnis terpisah | Source belum tersedia | Dokumentasikan adapter source/ID/version/decision | D01; jangan menganggap callback/otoritas final sudah disepakati |

## Penerimaan implementasi berikutnya

- Guest 401; principal internal ditolak di aksi khusus taxpayer; taxpayer lain 404 tanpa bocoran field; internal actor memerlukan role, OPD, dan penugasan yang sesuai.
- Normalisasi status diuji dari lab generate bill sampai settlement/rekap, termasuk alias uppercase; status pelayanan tidak boleh memicu paid.
- Semua nominal/versi quote/kapasitas dihitung dan diverifikasi server; stale quote, double-submit, callback berulang dan pilihan channel berbeda memiliki hasil deterministik.
- Dokumen hanya dapat diunduh setelah file/versi/penerbit/otorisasi tersedia. `tte_status` literal tidak mengaktifkan unduhan atau label sah.
- Katalog kosong/error/layanan belum siap tidak diisi data dummy. Persisted transaction dipulihkan dari API, bukan hasil optimistic UI.

D01/D02/D04/D05/D06/D07 tetap diperlukan untuk implementasi layanan terkait; kontrak usulan ini tidak menetapkan tarif, SOP, identitas master, sanksi, kewenangan pihak ketiga, atau template resmi. AM-BASE-02 selesai pada spesifikasi/matriks kompatibilitas. Tidak ada endpoint/schema/runtime yang berubah. Validasi checkpoint: inspeksi sumber, target tautan, dan `git diff --check`; bukan test aplikasi/UAT.
