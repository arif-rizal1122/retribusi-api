# Alur Aktivasi & Persetujuan Akun Notaris/PPAT oleh Ka.Bapenda

Rencana implementasi ini menguraikan tahapan untuk menambahkan alur pendaftaran mandiri (Public Registration) bagi Notaris/PPAT, yang mana akun mereka akan secara *default* berada pada status `PENDING`, dan memerlukan aktivasi manual oleh Kepala Bapenda (atau Super Admin) melalui dasbor khusus.

## User Review Required

> [!IMPORTANT]
> **Keputusan Role & Akses:** Saat ini sistem tidak memiliki *role* spesifik `kepala_bapenda`. Apakah hak akses untuk menyetujui akun Notaris ini akan digabungkan ke `super_admin`, atau kita perlu membuat *role* baru khusus `kepala_bapenda`? Pada rencana ini, saya mengasumsikan hak akses akan diberikan pada `super_admin`.

## Open Questions

> [!WARNING]
> 1. **Dokumen Persyaratan:** Dokumen apa saja yang wajib diunggah oleh Notaris saat mendaftar? (Misal: SK PPAT, KTP, atau Surat Permohonan). Saya akan menyiapkan fitur *upload* dokumen di *frontend*.
> 2. **Halaman Pendaftaran:** Apakah halaman pendaftaran Notaris (Public Registration) akan kita letakkan menyatu dengan *Login Screen* di `retribusi-admin` (contoh: tombol "Daftar sebagai PPAT")?

---

## Proposed Changes

### 1. Backend (`retribusi-api`)

#### [MODIFY] `app/Models/User.php`
- Menambahkan konstanta baru: `const ROLE_NOTARIS = 'notaris';`
- Menambahkan *helper function* `isNotaris()`.

#### [MODIFY] `app/Http/Controllers/AuthController.php`
- Menambahkan method `registerNotaris(Request $request)` yang menerima input (Nama, NIK, Email, Password, serta *file upload* SK PPAT ke `metadata`).
- Mengatur agar nilai *default* user yang baru dibuat adalah `role = 'notaris'` dan `status = 'pending'`.

#### [NEW] `app/Http/Controllers/NotarisApprovalController.php`
- Membuat *controller* baru yang difokuskan untuk Dasbor Persetujuan Ka.Bapenda.
- Method `index()`: Mengambil daftar semua Notaris, baik yang berstatus `pending`, `active`, maupun `rejected`.
- Method `approve($id)`: Mengubah status *user* menjadi `active`.
- Method `reject($id)`: Mengubah status *user* menjadi `rejected`.

#### [MODIFY] `routes/api.php`
- **Public Routes:** Menambahkan `POST /notaris/register` di grup *throttled*.
- **Protected Routes:** Menambahkan rute manajemen `api/notaris-approvals` dengan akses khusus untuk `super_admin`.

---

### 2. Frontend (`retribusi-admin`)

#### [NEW] `src/pages/auth/RegisterPpat.tsx`
- Menambahkan halaman publik baru (tidak dikunci oleh *login*) bagi Notaris untuk mengisi formulir pendaftaran dan mengunggah dokumen SK.

#### [MODIFY] `src/pages/Login.tsx` atau `src/App.tsx`
- Menambahkan *link* navigasi menuju halaman Registrasi PPAT pada halaman *Login*.
- Mendaftarkan rute `/register-ppat` di `App.tsx`.

#### [NEW] `src/pages/NotarisApproval.tsx`
- Membuat halaman dasbor *Approval Desk* untuk Kepala Bapenda. Menampilkan antrean pendaftaran PPAT dalam format tabel (mencakup data NIK, Nama, dan *link* dokumen SK).
- Menyediakan tombol *Action* **[Setujui & Aktifkan]** dan **[Tolak]**.

#### [MODIFY] `src/components/Layout.tsx` (Sidebar)
- Menambahkan sub-menu baru **"Aktivasi PPAT"** di bawah *section* 'Pengawasan' atau 'Sistem', yang hanya dapat dilihat oleh Ka.Bapenda (`super_admin`).

---

## Verification Plan

### Automated Tests
- Eksekusi *endpoint* pendaftaran PPAT via cURL atau *tinker* untuk memverifikasi bahwa status secara mutlak menjadi `pending`.
- Pengujian *login* dengan akun berstatus `pending` harus ditolak oleh sistem.

### Manual Verification
1. Mensimulasikan notaris yang mendaftar melalui UI `/register-ppat`.
2. Login sebagai Ka.Bapenda (`super_admin`), mengakses menu Aktivasi PPAT, dan menekan tombol Setujui.
3. Mencoba login menggunakan kredensial Notaris tersebut untuk memverifikasi bahwa akun telah berhasil diakses dan masuk ke menu e-BPHTB.
