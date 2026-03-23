# 📔 Mitigation Registry & QA Error Log

Registry ini mencatat temuan bug serius, akar masalahnya, dan pola mitigasi yang telah divalidasi selama proses pengembangan dan pengujian.

---

### BUG-001: Infinite Recursion in Sanctum Auth Guard - 2026-03-12
- **Environment**: Local & Staging
- **Endpoint/Kasus**: `GET /api/me`, `GET /api/citizen/services` (Semua rute ber-middleware `auth:sanctum`)
- **Deskripsi Error**: 500 Internal Server Error dengan *Empty Body* (atau JSON error jika handler aktif). Request mengalami delay ~9 detik sebelum crash. Stack trace menunjukkan ribuan frame (52,000+) di `Sanctum\Guard`.
- **Akar Masalah (Root Cause)**: 
    1. **Circular Guard**: `config/sanctum.guard` menyertakan guard `citizens` yang sendiri menggunakan driver `sanctum`. Hal ini memicu Sanctum memanggil dirinya sendiri tanpa henti.
    2. **Unsafe Global Scope**: `RetributionTypeScope` memanggil `Auth::user()` atau `Auth::hasUser()`. Saat Sanctum sedang meresolusi token, model `Taxpayer` dipanggil, yang memicu Global Scope, yang memanggil `Auth::user()`, yang kembali memicu Sanctum Guard.
- **Solusi (Mitigation)**: 
    1. **Config Cleanup**: Hapus guard berbasis sanctum dari `config/sanctum.guard`. Hanya sertakan stateful guards (seperti `web`).
    2. **Passive Scoping**: Hapus semua pemanggilan facade `Auth` dari dalam `apply()` method pada Global Scope.
    3. **Middleware Injection**: Implementasikan middleware `SetScopeUser` yang berjalan SETELAH `auth:sanctum`. Middleware ini mengambil user yang sudah ter-resolusi dan menyuntikkannya secara statis ke `RetributionTypeScope::setAuthenticatedUser($user)`.

---

### BUG-002: Divergent Branches & Permission Denied on Deployment - 2026-03-12
- **Environment**: Staging (VPS)
- **Endpoint/Kasus**: CI/CD (Manual Deployment via SSH)
- **Deskripsi Error**: `git pull` gagal karena "Need to specify how to reconcile divergent branches". `git reset --hard` gagal karena `Permission denied` pada folder `storage` atau `bootstrap/cache`.
- **Akar Masalah (Root Cause)**: Perbedaan history commit lokal vs remote dan kepemilikan file oleh `www-data` yang memblokir proses unlink file oleh user SSH (`sipanda`).
- **Solusi (Mitigation)**: 
    1. **Hard Sync**: Gunakan rantaian perintah: `git fetch origin` ➡️ `git reset --hard origin/staging`.
    2. **Permission Toggle**: Jalankan `sudo chown -R sipanda:www-data` sebelum sync git, lalu kembalikan ke `sudo chown -R www-data:www-data` setelah `optimize:clear` selesai.
    3. **Automation**: Gunakan Expect script (`.exp`) untuk menangani prompt password SSH dan Sudo secara konsisten.

---

### BUG-003: SPMP Undefined Relationship `auditor` — 2026-03-16
- **Environment**: Local
- **Endpoint/Kasus**: `OfficialDocumentService::generateSPMP()` via `verify_pdf_templates.php`
- **Deskripsi Error**: `Call to undefined relationship [auditor] on model [App\Models\EnforcementNotice]`
- **Akar Masalah (Root Cause)**: `generateSPMP()` memanggil `$notice->load(['taxObject.taxpayer', 'auditor'])` tetapi model `EnforcementNotice` tidak memiliki relasi `auditor()`. Relasi yang benar adalah `creator()` (`created_by`). Property `deficit_amount` juga tidak ada — digantikan by `amount_at_issue`.
- **Solusi (Mitigation)**: 
    1. Ubah eager-load dari `auditor` → `creator`.
    2. Ubah `$notice->deficit_amount` → `$notice->amount_at_issue ?? 0`.
    3. Re-run test: SPMP renders OK.

---

### BUG-004: SSH Authentication Failure (can't connect) — 2026-03-17
- **Environment**: Staging & Production CI/CD
- **Endpoint/Kasus**: GitHub Actions (`appleboy/ssh-action` & `appleboy/scp-action`)
- **Deskripsi Error**: `Error: can't connect without a private SSH key or password`.
- **Akar Masalah (Root Cause)**: 
    1. **Password Rotated**: Password VPS berubah tetapi rahasia di GitHub belum di-update.
    2. **Unstable Versioning**: Penggunaan tag `@master` pada GitHub Actions menyebabkan ketidakkonsistenan saat rahasia hilang atau format input berubah.
- **Solusi (Mitigation)**: 
    1. **Action Pinning**: Gunakan versi stabil (`ssh-action@v1.2.0` dan `scp-action@v0.1.7`).
    2. **Explicit Port**: Selalu tambahkan `port: ${{ secrets.VPS_PORT }}` (default 22).
    3. **CLI Sync**: Gunakan `gh secret set` untuk sinkronisasi massal rahasia antar repositori.

---

### BUG-005: 500 Server Error Stale configuration — 2026-03-17
- **Environment**: Staging
- **Endpoint/Kasus**: `GET https://api.sipanda.online/up` (Layanan API)
- **Deskripsi Error**: HTTP 500 Server Error pasca deployment berhasil.
- **Akar Masalah (Root Cause)**: Cache konfigurasi, rute, atau view yang sudah usang (*stale*) setelah perubahan skema database V-Tax yang signifikan, menyebabkan konflik resolusi dependensi pada kontainer Laravel.
- **Solusi (Mitigation)**: 
    1. Jalankan `php artisan config:clear`, `php artisan cache:clear`, dan `php artisan view:clear` di VPS.
    2. Verifikasi status migrasi dengan `php artisan migrate:status`.
    3. Pastikan `.env` terisi dengan benar (tidak ada baris yang korup).
    4. **Memori PHP-FPM Usang**: Jika kredensial database (contoh: password) usang masih dipertahankan meskipun *artisan cache* sudah dihapus, muat ulang layanan secara paksa (contoh: `sudo systemctl reload php8.3-fpm && sudo systemctl reload php8.4-fpm`).

---

### BUG-006: RBAC Isolation Bypass & Deployment Obstacles — 2026-03-17
- **Environment**: Staging
- **Endpoint/Kasus**: `GET /api/users` (dan rujukan *middleware* tingkat staf)
- **Deskripsi Error**: Panggilan memakai token **Citizen** (Taxpayer) justru diberi respons `HTTP 200 OK`, memperlihatkan daftar pengguna dan staf BAPENDA.
- **Akar Masalah (Root Cause)**: Middleware `EnsureAdmin` diinjeksi dengan pemeriksaan sintaks lemah yang mengizinkan *instance* spesifik `App\Models\Taxpayer` pada rutenya secara global sebelum dicegat di tempat lain.
- **Solusi (Mitigation)**: 
    1. **Strict Instance Validation**: Ganti penyaringan di dalam `app/Http/Middleware/EnsureAdmin.php` agar secara absolut HANYA mengizinkan `App\Models\User`.
    2. **SOP Fallback Sinkronisasi File (SCP)**: Sistem CI/CD mungkin berstatus *Success* tapi *code update* gagal menimpa VPS lama karena *divergent branches* maupun ketiadaan bash command `git` di lingkungan SSH non-interaktif VPS. Gunakan terminal lokal untuk menimpa berkas kritis langsung dengan `scp -o StrictHostKeyChecking=no local_file.php user@ip:/path/remote_file.php` lantas jalankan optimasi ulang memori di VPS.

---

### BUG-007: Access Denied for User (using password: NO) — 2026-03-24
- **Environment**: Staging / Production
- **Endpoint/Kasus**: All endpoints (misal `GET /api/me`)
- **Deskripsi Error**: 500 Internal Server Error dengan `SQLSTATE[HY000] [1045] Access denied for user 'sipanda'@'localhost' (using password: NO)`.
- **Akar Masalah (Root Cause)**: Laravel membaca bahwa tidak ada password di `.env`, meskipun string `DB_PASSWORD` sebenarnya terisi. Hal ini disebabkan oleh *stale configuration cache* atau PHP-FPM yang tidak mengenali perubahan `.env` terbaru, ataupun tanda kutip ganda pada password yang disalahartikan saat di-cache.
- **Solusi (Mitigation)**: 
    1. Pastikan string `DB_PASSWORD` tidak menggunakan tanda kutip jika tidak ada spasi.
    2. Jalankan `php artisan config:clear` dan `php artisan cache:clear`.
    3. Jika masih membandel, reload paksa worker PHP-FPM: `echo '<vps-password>' | sudo -S systemctl reload php8.3-fpm`.
