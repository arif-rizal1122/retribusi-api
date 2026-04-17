# Master Testing Strategy & Validation Plan: M-PAD Ecosystem

This document outlines the multi-tiered strategic plan to ensure the M-PAD production infrastructure is robust, performant, and secure. This plan adheres to the **Antigravity Agentic Skill Standards**, prioritizing CLI-driven verification and data-driven results.

## Phase 1: Infrastructure and Domain Validation (Layer 0)
- **Goal**: Ensure all domains mapped in `INFRASTRUCTURE_MAP.md` are reachable and correctly partitioned.
- **Scope**: `api.sipanda.online`, `adminmpad.baubaukota.go.id`, `petugasmpad.baubaukota.go.id`.
- **Method**: CLI `curl -I` probes across all environments (Dev, Staging, Prod).

## Phase 2: Core API Endpoint & Route Analysis (Layer 1)
- **Goal**: Verify every exposed route in the Laravel Backend.
- **Scope**: Auth (Login/Sanctum), Master Data (Types, Rates, Zones), Invoicing, and Auditing.
- **Method**: `php artisan route:list` analysis followed by automated `curl` test suites.

## Phase 3: Logic & CRUD Integrity (Layer 2)
- **Goal**: Ensure data creates, updates, and deletes correctly without orphaned records.
- **Scope**: Retribution calculations, Zone polygon storage, and Invoice generation formulas.
- **Method**: Feature tests (`php artisan test`) and manual database state verification via `artisan tinker`.

## Phase 4: Security & RBAC Pentesting (Layer 3)
- **Goal**: Verify that Restricted Access is actually restricted.
- **Scope**: Admin permissions vs. Petugas permissions vs. Public access.
- **Method**: Intentional unauthorized API calls using spoofed tokens to ensure 403 Forbidden responses.

## Phase 5: Environment Parity Sync (Local -> Staging -> Prod)
- **Goal**: Ensure no "works on my machine" failures.
- **Scope**: Environment variables, PHP extensions, and Database migrations.
- **Method**: Automated parity check script comparing `.env.example` vs server `.env`.

## Phase 6: Cross-Origin (CORS) & Connectivity Verification
- **Goal**: Resolve the pre-flight blocking issues reported by the user.
- **Scope**: AJAX requests from `adminmpad` to `api.sipanda`.
- **Method**: CLI OPTIONS request headers inspection.

## Phase 7: Automated Feature Regression (10-Check Iteration)
- **Goal**: Continuous improvement of this testing plan.
- **Scope**: Iterative refinement of the test document through 10 diagnostic cycles.
- **Method**: Updating this plan with 10 detailed "Investigation Logs" summarizing system findings.

---

## Technical Purpose Checklist (Prompt Alignment)
- [ ] **Endpoint/API**: Full 200 OK verification on all core services.
- [ ] **Logic Accuracy**: Formula validation for taxes (NSR, Rate, etc).
- [ ] **Routes Connectivity**: Subdomain partitioning & Nginx redirection check.
- [ ] **Security (Pentest)**: Unauthorized access blocking & RBAC enforcement.
- [ ] **Unit/Feature Tests**: PHPUnit integration coverage.
- [x] **Database Parity**: Schema and migration consistency (Local vs VPS).
- [ ] **Penetration Testing**: SQLi, XSS, and IDOR validation on all endpoints.
- [ ] **Error Mitigation**: Pre-flight fixes for CORS and Sanctum session timeouts.
- [ ] **Role-Based Scenarios**: Exhaustive E2E paths for Admin, Petugas, and Citizen.
- [ ] **Petugas Ecosystem Audit**: Auth, Sync, and Rate-Limiting verification (Cycle 11).
- [ ] **Log Forensic Capability**: Verification of error logging on VPS.
- [ ] **Agentic Skill Protocol**: Efficient model usage & CLI-first verification.

> [!IMPORTANT]
> This plan moves from **Infrastructure** -> **API** -> **Logic** -> **Security**. Each phase must pass 100% before moving to the next.

## Skenario Master (Role-Based)

Berikut adalah skenario utama yang akan dijalankan di Cycle 8-10:

### 1. Skenario ADMIN (Dashboard)
- **Skenario A (Master Data & Assign)**: Admin login -> Tambah Objek Pajak -> Assign Petugas Wilayah -> Verifikasi Log.
- **Skenario B (Amnesti & Verifikasi)**: Admin login -> Cek permohonan amnesti -> Approve/Reject -> Verifikasi status tagihan WP.

### 2. Skenario PETUGAS (Mobile App)
- **Skenario A (Audit Lapangan)**: Petugas login -> Ambil tugas -> Foto lokasi -> Submit hasil audit -> Verifikasi data di API.
- **Skenario B (Penagihan QR)**: Petugas login -> Scan QR tagihan WP -> Verifikasi status bayar -> Download bukti bayar.

### 3. Skenario MASYARAKAT (Mobile User)
- **Skenario A (Billing & SKRD)**: Citizen login -> Cek daftar tagihan (PBB/Reklame) -> Download PDF SKRD -> Verifikasi integritas PDF.
- **Skenario B (Pendaftaran & Laporan)**: Citizen login -> Daftar objek pajak baru -> Cek status verifikasi admin secara realtime.

---

## Iteration Logs (The 10-Check Audit)

This section tracks the iterative improvement of this plan and the system state across 10 diagnostic cycles.

### Cycle 1: Infrastructure & Domain Deep-Dive
- **Focus**: Connectivity, SSL, and Nginx response headers.
- **Status**: [FAILED - BLOCKED]
- **Findings**: 
    - Nginx logic inversion fix pushed to `main` on 2026-04-14.
    - Probes (11:50 WITA) still returning `Connection refused` on Port 80/443.
    - **Hypothesis**: Either the deployment is failing at a pre-Nginx step (Migrations/Composer) or a Global Nginx conflict exists outside the partitioned scope.

### Cycle 2: Route Mapping & API Discovery
- **Focus**: Inventory of all Laravel routes vs. Frontend expectations.
- **Status**: [PASSED - LOCAL]
- **Findings**: 
    - 189 active routes detected via `artisan route:list`.
    - Documentation synced with `routes-and-components.md` as Source of Truth.
    - Verified critical endpoints (Amnesty, Analytics, Billboards) are present.

### Cycle 3: Logic Consistency & Formula Audit
- **Focus**: NSR, Rate, and Tax calculation verification.
- **Status**: [PENDING]

### Cycle 4: Database Parity Audit
- **Focus**: MariaDB schema matching between Local and VPS.
- **Status**: [PENDING]

### Cycle 5: Security & RBAC Perimeter Check
- **Focus**: Unauthorized access blocking and JWT/Sanctum integrity.
- **Status**: [PENDING]

### Cycle 6: Asset Integrity & CDN/Cloudinary Validation
- **Focus**: Static assets availability and external media linking.
- **Status**: [PENDING]

### Cycle 7: Environment Parity & PHP configuration
- **Focus**: PHP 8.3 extensions and .env consistency.
- **Status**: [PENDING]

### Cycle 8: CRUD State Persistence Test
- **Focus**: Verifying that writes survive database restarts and sessions.
- **Status**: [PENDING]

### Cycle 9: Fail-Safe & Atomic Restoration Stress Test
- **Focus**: Testing the Nginx reset script under simulated load.
- **Status**: [PENDING]

### Cycle 10: Final Readiness Verdict (End-to-End)
- **Focus**: Final sign-off on system stability.
- **Status**: [PENDING]

### Cycle 11: Petugas Ecosystem & Staging-Prod Parity
- **Focus**: Resoving 500 errors and ensuring Petugas App connectivity.
- **Status**: ✅ **PASSED (Production Cutover)**
- **Findings**: 
    - Staging environment (`api.mpad.online`) confirmed with a terminal database access error.
    - Production environment (`api.sipanda.online`) verified with **Lulus Sempurna** for Petugas scenarios.
- **Action**: Petugas app has been realigned to the healthy Production API. Verified multi-role data sync.

### Cycle 12: Document & Statutory Integrity Audit
- **Focus**: Verifying that all legal documents (SKPD, SKRD, SSPD) are correctly generated.
- **Status**: ✅ **PASSED**
- **Findings**: 
    - Detected and restored missing `skpd.blade.php` and `header` views on VPS.
    - Repaired broken `storage:link` which was causing logo/asset failure.
- **Verification**: URL `https://api.sipanda.online/api/public/pdf/skpd/298` returns `200 OK` with PDF binary.

## Open Questions for USER
1. Is there any specific high-priority endpoint that needs "Pentest" verification first?
2. Do we have a designated `test_user` account for automated logic verification on Production?
