# 🧪 API & CRUD Test Report

**Date**: 2026-03-13 05:47:33
**Target**: https://api.mpad.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ✅ | Admin login → token received (role: super_admin) |
| ⚠️ | Standard citizen login failed, attempting dynamic NIK discovery... |
| ✅ | Discovered valid NIK: 7404651929094717, retrying login... |
| ❌ | Citizen login failed (tried hardcoded and discovered NIKs) |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ✅ | GET /opds (list OPDs) → 200 |
| ✅ | GET /tax-formulas → 200 |
| ✅ | GET /pbb/classifications → 200 |
| ❌ | GET /citizen/bills?nik=... → 422 (expected 200) |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Citizen endpoints skipped (no token) |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ✅ | GET /user (admin profile) → 200 |
| ✅ | GET /me (admin) → 200 |
| ✅ | GET /dashboard/stats → 200 |
| ✅ | GET /dashboard/revenue-trend → 200 |
| ✅ | GET /dashboard/map-potentials → 200 |
| ✅ | GET /analytics/realization → 200 |
| ✅ | GET /analytics/heatmap → 200 |
| ✅ | GET /retribution-types → 200 |
| ✅ | GET /taxpayers → 200 |
| ✅ | GET /tax-objects → 200 |
| ✅ | GET /bills → 200 |
| ✅ | GET /zones → 200 |
| ✅ | GET /retribution-classifications → 200 |
| ✅ | GET /retribution-rates → 200 |
| ✅ | GET /users → 200 |
| ✅ | GET /verifications → 200 |
| ✅ | GET /reports/summary → 200 |
| ✅ | GET /reports/recent → 200 |
| ✅ | GET /reports/petugas-performance → 200 |
| ✅ | GET /reports/monthly → 200 |
| ✅ | GET /pengawas/audit-logs → 200 |
| ✅ | GET /pengawas/anomalies → 200 |
| ✅ | GET /pengawas/compliance-stats → 200 |
| ✅ | GET /pengawas/enforcements → 200 |
| ✅ | GET /pengawas/penindakan → 200 |
| ✅ | GET /tte/documents → 200 |
| ✅ | GET /amnesty → 200 |
| ✅ | GET /pbb/bapenda/transactions → 200 |
| ✅ | GET /pbb/bapenda/stats → 200 |
| ❌ | POST /pbb/bapenda/inquiry (Public - Expect 404) → 500 (expected 404) |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ✅ | CREATE zone → ID: 14 |
| ✅ | READ zone/14 → 200 |
| ✅ | UPDATE zone/14 → 200 |
| ✅ | VERIFY update → name contains 'Updated' |
| ✅ | DELETE zone/14 → 200 |
| ✅ | VERIFY delete → 404 (gone) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ✅ | CREATE taxpayer → ID: 37, NIK: 9900001773352098 |
| ✅ | GET /taxpayers/37 → 200 |
| ✅ | GET /taxpayers/search/9900001773352098 → 200 |
| ✅ | UPDATE taxpayer/37 → 200 |
| ✅ | DELETE taxpayer/37 → 200 |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ✅ | CREATE retribution-type → ID: 24 |
| ✅ | GET /retribution-types/24 → 200 |
| ✅ | UPDATE retribution-type/24 → 200 |
| ✅ | DELETE retribution-type/24 → 200 |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ✅ | PBB calculation → result received |

### 8. Response Format Validation
| St | Detail |
|----|--------|
| ✅ | /bills response has 'data' wrapper |
| ✅ | /bills response has pagination metadata |
| ✅ | Content-Type is application/json |
| ✅ | /me response has 'id' field |
| ✅ | /me response has 'name' field |
| ✅ | /me response has 'nik' field |

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ✅ | 401 response has 'message' field |
| ✅ | Unknown route → 404 |
| ✅ | Validation error returns structured error response |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|

## Summary

⚠️ **3 TEST(S) FAILED**

- **Pass**: 60
- **Fail**: 3
- **Warn**: 1
- **Skip**: 2
- **Total**: 66

