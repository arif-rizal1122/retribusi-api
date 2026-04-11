# 🧪 API & CRUD Test Report

**Date**: 2026-03-17 10:28:03
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ⚠️ | Admin login failed — some tests will be skipped. Response: { "message": "Server Error" }{ "message": "Server Error" }{ "message": "Server Error" }{ "message":  |
| ❌ | Citizen login failed (tried hardcoded and discovered NIKs) |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ❌ | GET /opds (list OPDs) → 500 (expected 200) |
| ❌ | GET /tax-formulas → 500 (expected 200) |
| ❌ | GET /pbb/classifications → 500 (expected 200) |
| ❌ | GET /citizen/bills?nik=... → 422 (expected 200) |
| ✅ | GET /up (health) → 200 |

### 2. Citizen Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Citizen endpoints skipped (no token) |

### 3. Admin/Petugas Authenticated Endpoints
| St | Detail |
|----|--------|
| ⏭️ | Admin endpoints skipped (no token) |

### 4. CRUD Lifecycle: Zone
| St | Detail |
|----|--------|
| ⏭️ | Zone CRUD skipped (no admin token) |

### 5. CRUD Lifecycle: Taxpayer
| St | Detail |
|----|--------|
| ⏭️ | Taxpayer CRUD skipped (no admin token) |

### 6. CRUD Lifecycle: Retribution Type
| St | Detail |
|----|--------|
| ⏭️ | Retribution Type CRUD skipped |

### 7. Tax Simulation & PBB Calculation
| St | Detail |
|----|--------|
| ⏭️ | Tax simulation skipped (no formula found) |
| ⚠️ | PBB calculation → 500 (may need correct kelas data) |

### 8. Response Format Validation
| St | Detail |
|----|--------|

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

⚠️ **5 TEST(S) FAILED**

- **Pass**: 4
- **Fail**: 5
- **Warn**: 2
- **Skip**: 6
- **Total**: 17

