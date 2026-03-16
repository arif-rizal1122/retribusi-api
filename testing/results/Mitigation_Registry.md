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
- **Akar Masalah (Root Cause)**: `generateSPMP()` memanggil `$notice->load(['taxObject.taxpayer', 'auditor'])` tetapi model `EnforcementNotice` tidak memiliki relasi `auditor()`. Relasi yang benar adalah `creator()` (`created_by`). Property `deficit_amount` juga tidak ada — digantikan oleh `amount_at_issue`.
- **Solusi (Mitigation)**: 
    1. Ubah eager-load dari `auditor` → `creator`.
    2. Ubah `$notice->deficit_amount` → `$notice->amount_at_issue ?? 0`.
    3. Re-run test: SPMP renders OK.
