# Register Sejarah Error & Mitigasi (SIPANDA Ecosystem)

Dokumen ini mencatat daftar kesalahan (error) kritis yang pernah terjadi selama pengembangan sistem SIPANDA dan langkah mitigasi permanen yang telah diterapkan.

---

## 1. Error Infrastruktur & Environment

| Error | Gejala | Akar Masalah | Mitigasi / Solusi |
|:---|:---|:---|:---|
| **CORS Blocked** | Status 500 / Blocked di browser | Domain `sipanda.online` belum diizinkan oleh `api.sipanda.online` | Update `cors.php` untuk mengizinkan wildcard `*` atau domain spesifik prod. |
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

---
*Register ini akan terus diperbarui seiring perkembangan sistem.*
