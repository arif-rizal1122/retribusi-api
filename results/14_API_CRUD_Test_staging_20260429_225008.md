# 🧪 API & CRUD Test Report

**Date**: 2026-04-29 22:50:08
**Target**: https://api.sipanda.online/api


### 0. Authentication Setup
| St | Detail |
|----|--------|
| ⚠️ | Admin login failed — some tests will be skipped. Response:  |
| ❌ | Citizen login failed (tried hardcoded and discovered NIKs) |

### 1. Public Endpoints (No Auth)
| St | Detail |
|----|--------|
| ❌ | GET /opds (list OPDs) → 000 (expected 200) |
| ❌ | GET /tax-formulas → 000 (expected 200) |
| ❌ | GET /pbb/classifications → 000 (expected 200) |
| ❌ | GET /citizen/bills?nik=... → 000 (expected 200) |
| ❌ | GET /up (health) → 000 (expected 200) |

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
| ⚠️ | PBB calculation → 000 (may need correct kelas data) |

### 8. Response Format Validation
| St | Detail |
|----|--------|

### 9. Error Response Validation
| St | Detail |
|----|--------|
| ❌ | 401 response missing 'message' field |
| ❌ | Unknown route → 000 (expected 404) |
| ⚠️ | Validation error format unexpected |

### 10. Citizen Profile Update
| St | Detail |
|----|--------|

## Summary

⚠️ **8 TEST(S) FAILED**

- **Pass**: 0
- **Fail**: 8
- **Warn**: 3
- **Skip**: 6
- **Total**: 17

