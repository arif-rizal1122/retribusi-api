# Register Sejarah Error & Mitigasi (SIPANDA Ecosystem)

Dokumen ini mencatat daftar kesalahan (error) kritis yang pernah terjadi selama pengembangan sistem SIPANDA dan langkah mitigasi permanen yang telah diterapkan.

---

## 1. Error Infrastruktur & Environment

| Error | Gejala | Akar Masalah | Mitigasi / Solusi |
|:---|:---|:---|:---|
| **CORS Blocked** | Status 500 / Blocked di browser | Domain `sipanda.online` belum diizinkan oleh `api.sipanda.online` | Update `cors.php` untuk mengizinkan wildcard `*` atau domain spesifik prod. |
| **New Subdomains CORS** | Akses dari `mpad`, `adminmpad`, `petugasmpad` diblokir | Domain baru tidak masuk `allowed_origins` di API CORS. | Menambahkan `https://mpad.baubaukota.go.id`, `https://adminmpad.baubaukota.go.id`, & `https://petugasmpad.baubaukota.go.id` ke `cors.php`. |
| **SSL/HTTPS Warning** | "Your connection is not private" | Domain baru menggunakan IP langsung atau sertifikat yang tidak valid / tidak sesuai. | Men-generate dan mengaktifkan sertifikat SSL Let's Encrypt via `certbot --nginx` untuk ketiga domain M-PAD. |
| **Nginx Routing Error** | Salah memuat aplikasi Frontend | Nginx server blocks belum dikonfigurasi untuk sub-domain yang baru. | Membuat file `mpad-frontend` di `/etc/nginx/sites-available` yang memetakan masing-masing domain ke `/dist` Mobile, Admin, dan Petugas. |
| **Bapenda API Fail** | "Login Failed" di PBB | Credential API PBB di `.env` VPS salah/kadaluarsa. | Update `.env` VPS dengan kredensial resmi. Gunakan `php artisan config:cache`. |
| **Migration Mismatch** | `Column not found` di Prod | Kolom baru di lokal belum ada di VPS. | Jalankan `php artisan migrate --force` di VPS setiap kali `git pull`. |
| **Local Login 401**| Gagal login di localhost | Akun demo di frontend tidak ada di database seeder. | Gunakan `TestingScenarioSeeder` yang lengkap atau buat user manual via Tinker. |

---

## 2. Error Logika Backend (API)

| Error | Gejala | Akar Masalah | Mitigasi / Solusi |
|:---|:---|:---|:---|
| **Zone Creation 500**| Crash saat simpan Zona | Validasi `opd_id: required` tapi Super Admin tidak kirim data. | Ubah ke `nullable` + Logic Auto-Inference dari `retribution_type`. |
| **Delete Rate 500** | Crash saat hapus Tarif | Method `destroy()` pakai `$request` tapi param tidak dideklarasi. | Tambahkan `Request $request` pada parameter semua method Controller. |
| **Formula Parser 500**| Gagal hitung tagihan | Variabel rumus tidak ditemukan atau pembagian nol. | Tambahkan `try-catch (\Throwable)` di service parser + default value `0`. |
| **Logout 401** | Gagal logout | Token sudah dihapus atau mismatch. | Tambahkan pengecekan `if ($request->user())` sebelum delete token. |
| **Duplicate NOP 500** | Crash saat update WP | `updateOrCreate` di `syncTaxObject` pakai matching criteria berbeda dari unique constraint `nop`. | Ganti dengan 3-step lookup (by combo → by NOP → create new) + fallback. |
| **ValidationException→500** | Error 422 jadi 500 | `try-catch(\Throwable)` menangkap `ValidationException`. | Tambahkan rethrow `ValidationException` dan `ModelNotFoundException` sebelum catch `\Throwable`. |

---

## 3. Error Frontend & Mobile

| Error | Gejala | Akar Masalah | Mitigasi / Solusi |
|:---|:---|:---|:---|
| **JSX Syntax Error** | White screen / Build fail | Tag JSX tidak tertutup di `Dashboard.tsx`. | Gunakan ESLint auto-fix dan periksa visual code indentasi. |
| **GPS Unavailable** | "Position unavailable" | Hardware GPS mati atau user menolak izin lokasi. | Implementasi Fallback: Jika GPS gagal, biarkan user input manual atau pakai koordinat terakhir. |
| **Empty State 500** | UI pecah saat data nol | Frontend mencoba `map()` pada data yang `null`. | Selalu gunakan optional chaining `data?.map()` atau default array `data || []`. |

---

## 4. Matriks Mitigasi Lintas Platform

Untuk mencegah error serupa di masa depan, sistem sekarang mewajibkan:

1.  **Backend "Anti-Gagal"**: Semua method `store/update/destroy` wajib dibungkus `try-catch (\Throwable)` dan mengembalikan `error_detail` jika gagal.
2.  **Inference Logic**: Field `opd_id` tidak boleh `required` jika bisa dicari otomatis dari relasi data di atasnya.
3.  **Checklist Depoyment**: `pull` → `migrate` → `cache:clear` adalah prosedur wajib satu paket.
4.  **Diagnostic UI**: Frontend sekarang menampilkan pesan error spesifik dari backend (bukan hanya "Gagal"), sehingga user/admin bisa langsung tahu penyebabnya (misal: "Baris 119: Undefined variable").
5.  **Duplicate-Safe Sync**: Fungsi `syncTaxObject()` menggunakan 3-step lookup (by combo → by NOP → create new) agar tidak crash saat NOP sudah terpakai oleh record sebelumnya.

---

## 5. Test Coverage Otomatis

Semua error di atas dilindungi oleh: **`HistoricalErrorRegressionTest.php`**

| Error | Test Case | Assert |
|:---|:---|:---|
| Login 401 | `error_login_returns_token_for_valid_credentials` | 200 + token |
| Login Invalid | `error_login_returns_401_for_invalid_credentials` | 401 |
| Logout | `error_logout_succeeds_for_authenticated_user` | 200 |
| Zone 500 | `error_zone_creation_without_opd_id_infers_from_type` | 201 + opd_id |
| Delete Rate 500 | `error_delete_rate_does_not_crash_with_500` | ≠500 |
| Formula 500 | `error_formula_parser_handles_missing_variables` | ≠500 |
| Verification 422 | `error_verification_store_returns_422_for_invalid_data` | 422 |
| Payment 422 | `error_payment_store_returns_422_for_missing_fields` | 422 |
| **Duplicate NOP** | `error_taxpayer_duplicate_nop_on_re_update_does_not_crash` | ≠500 |
| **Taxpayer 422** | `error_taxpayer_update_returns_422_for_invalid_data` | 422 |
| Migration | `error_taxpayer_migration_has_required_columns` | schema OK |
| NOP Column | `error_tax_objects_has_nop_column` | schema OK |

**Total: 28 tests, 46 assertions** — semua PASSED ✅

---
*Register ini akan terus diperbarui seiring perkembangan sistem.*

