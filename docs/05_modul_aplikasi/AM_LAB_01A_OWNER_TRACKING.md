# Tracking order lab citizen

Checkpoint AM-LAB-01A, 19 September 2026.

API menyediakan `GET /api/dlh/lab/orders/by-number/{orderNumber}` dan alias `/api/v1/dlh/...` di dalam middleware `auth:sanctum` + `scope_user`. Jika sesi adalah `Taxpayer`, query selalu dibatasi `taxpayer_id` dari token. Nomor order yang benar tetapi milik warga lain menghasilkan 404 dengan pesan order tidak ditemukan. User internal tetap dapat membaca order untuk workflow analis.

Respons memakai envelope detail yang sama dengan lookup berdasarkan ID: `data.order`, `data.bill`, dan `data.parameters`. Route diletakkan sebelum route `{id}` agar segmen `by-number` tidak ditangkap sebagai ID.

Endpoint ini dipakai mobile untuk lacak nomor order. Endpoint publik `/api/verify/dlh-lab/{orderNumber}` tetap untuk verifikasi QR/e-LHU minimum dan tidak dipakai sebagai jalur pelacakan private warga. Tidak ada perubahan ke admin/petugas, migration, tarif, pembayaran, atau penerbitan e-LHU/TTE.

Validasi gabungan: `CitizenDlhLabOwnershipTest`, `DlhLabServiceTest`, dan `DlhBankSultraPaymentIntegrationTest` lulus 10 test/61 assertion pada SQLite :memory:. Mobile service dan halaman mengonsumsi envelope ini; validasi frontend dicatat pada `retribusi-mobile/docs/AM_LAB_01A_TRACKING_TEST.md`.
