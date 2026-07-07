#!/bin/bash
# ============================================================================
# BAUBAUKOTA PRODUCTION - Comprehensive Test Suite
# Tests API + all 3 frontends + PBB Bapenda
# ============================================================================

API="https://api.mpad.online/api"
ADMIN_FE="https://adminmpad.baubaukota.go.id"
MOBILE_FE="https://mpad.baubaukota.go.id"
PETUGAS_FE="https://petugasmpad.baubaukota.go.id"

PASS=0; FAIL=0; TOTAL=0
GREEN='\033[0;32m'; RED='\033[0;31m'; BLUE='\033[0;34m'; NC='\033[0m'
CURL="curl --retry 10 --retry-delay 1 --retry-all-errors -s"

check() {
  local label="$1" expected="$2" actual="$3"
  TOTAL=$((TOTAL + 1))
  if [ "$actual" == "$expected" ]; then
    echo -e "  ${GREEN}✅${NC} $label: HTTP $actual"
    PASS=$((PASS + 1))
  else
    echo -e "  ${RED}❌${NC} $label: HTTP $actual (expected $expected)"
    FAIL=$((FAIL + 1))
  fi
}

RESULTS="# 🏛️ BAUBAUKOTA Production Test Report\n\n"
RESULTS+="**Date**: $(date '+%Y-%m-%d %H:%M:%S')\n"
RESULTS+="**API**: $API\n\n"

echo "============================================"
echo "  BAUBAUKOTA PRODUCTION TEST SUITE"
echo "  Time: $(date)"
echo "============================================"
echo ""

# --- 1. FRONTEND AVAILABILITY ---
echo -e "${BLUE}[1] Frontend Availability${NC}"
for url in "$ADMIN_FE" "$MOBILE_FE" "$PETUGAS_FE"; do
  STATUS=$($CURL -4 -o /dev/null -w "%{http_code}" "$url/")
  check "$url" "200" "$STATUS"
done

echo ""
echo -e "${BLUE}[1.b] Frontend Build-Only Configuration${NC}"
# Verifikasi config.json untuk arsitektur Build-Only
CONFIG_HTTP=$($CURL -4 -o /dev/null -w "%{http_code}" "$ADMIN_FE/config.json")
check "Admin config.json Exists" "200" "$CONFIG_HTTP"

CONFIG_API=$($CURL -4 "$ADMIN_FE/config.json" 2>/dev/null | grep -o '"VITE_API_URL"[^,]*' | awk -F'"' '{print $4}')
if [[ "$CONFIG_API" == *"api.mpad.online"* ]] || [[ "$CONFIG_API" == *"apimpad.baubaukota.go.id"* ]]; then
  check "Admin config.json API_URL is Prod" "PASS" "PASS"
else
  check "Admin config.json API_URL is Prod" "PASS" "$CONFIG_API"
fi

# --- 2. API AUTH ---
echo ""
echo -e "${BLUE}[2] API Authentication${NC}"
TOKEN=$($CURL -4 -X POST "$API/login" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"admin@retribusi.id","password":"password123"}' | python3 -c "import sys,json; print(json.load(sys.stdin).get('token','FAIL'))" 2>/dev/null)

if [ "$TOKEN" == "FAIL" ] || [ -z "$TOKEN" ]; then
  echo -e "  ${RED}❌${NC} Admin LOGIN FAILED"
  FAIL=$((FAIL + 1)); TOTAL=$((TOTAL + 1))
else
  echo -e "  ${GREEN}✅${NC} Admin Login OK (Token: ${TOKEN:0:20}...)"
  PASS=$((PASS + 1)); TOTAL=$((TOTAL + 1))
fi

CITIZEN_TOKEN=$($CURL -4 -X POST "$API/citizen/login" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"nik":"3201234567890001","password":"password123"}' | python3 -c "import sys,json; print(json.load(sys.stdin).get('token','FAIL'))" 2>/dev/null)

if [ "$CITIZEN_TOKEN" == "FAIL" ] || [ -z "$CITIZEN_TOKEN" ]; then
  echo -e "  ${RED}❌${NC} Citizen LOGIN FAILED"
  FAIL=$((FAIL + 1)); TOTAL=$((TOTAL + 1))
else
  echo -e "  ${GREEN}✅${NC} Citizen Login OK"
  PASS=$((PASS + 1)); TOTAL=$((TOTAL + 1))
fi

# --- 3. CORE API ENDPOINTS ---
echo ""
echo -e "${BLUE}[3] Core API Endpoints (GET → 200)${NC}"
ENDPOINTS=(
  "zones" "retribution-types" "retribution-classifications"
  "retribution-rates" "verifications" "bills" "users"
  "taxpayers" "tax-objects" "dashboard/stats"
  "analytics/realization" "reports/summary" "amnesty"
)
for ep in "${ENDPOINTS[@]}"; do
  STATUS=$($CURL -4 -o /dev/null -w "%{http_code}" "$API/$ep" \
    -H "Authorization: Bearer $TOKEN" -H "Accept: application/json")
  check "$ep" "200" "$STATUS"
done

# --- 4. PBB BAPENDA ---
echo ""
echo -e "${BLUE}[4] PBB Bapenda API${NC}"
PBB_CALC=$($CURL -4 -o /dev/null -w "%{http_code}" -X POST "$API/pbb/calculate" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"luas_bumi":200,"kelas_bumi":"A1","luas_bangunan":100,"kelas_bangunan":"A1"}')
check "PBB Calculate" "200" "$PBB_CALC"

PBB_CLASS=$($CURL -4 -o /dev/null -w "%{http_code}" "$API/pbb/classifications" \
  -H "Accept: application/json")
check "PBB Classifications" "200" "$PBB_CLASS"

# Check admin PBB page (SPA route — should return index.html)
PBB_FE=$($CURL -4 -o /dev/null -w "%{http_code}" "$ADMIN_FE/pbb-bapenda")
check "Admin PBB Page" "200" "$PBB_FE"

# --- 4.b AUTO DEDUCT & CORRECTION ---
echo ""
echo -e "${BLUE}[4.b] Auto Deduct & Correction API${NC}"
# Pastikan endpoint Webhook merespons (bukan 404, melainkan 401 karena butuh Basic Auth)
AD_WEBHOOK=$($CURL -4 -o /dev/null -w "%{http_code}" -X POST "$API/auto-deduct/webhook" \
  -H "Content-Type: application/json" \
  -d '{"event":"TEST"}')
check "Auto Deduct Webhook Auth (Expect 401)" "401" "$AD_WEBHOOK"

# Pastikan route internal admin untuk status Auto Deduct ada (Expect 200)
AD_STATUS=$($CURL -4 -o /dev/null -w "%{http_code}" "$API/auto-deduct/status" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json")
check "Auto Deduct Status" "200" "$AD_STATUS"

# --- 5. CORS CROSS-ORIGIN VALIDATION ---
# This section prevents regressions like baubaukota.go.id not being whitelisted
echo ""
echo -e "${BLUE}[5] CORS Cross-Origin (REGRESSION GUARD)${NC}"
for origin in "$ADMIN_FE" "$MOBILE_FE" "$PETUGAS_FE"; do
  CORS_RESP=$(curl --retry 5 --retry-all-errors -s -4 -I -X OPTIONS "$API/me" \
    -H "Origin: $origin" \
    -H "Access-Control-Request-Method: GET" \
    -H "Access-Control-Request-Headers: Content-Type, Authorization" 2>&1)
  ACAO=$(echo "$CORS_RESP" | grep -i "Access-Control-Allow-Origin:" | tr -d '\r')
  if echo "$ACAO" | grep -qi "$origin"; then
    check "CORS allows $origin" "PASS" "PASS"
  else
    check "CORS allows $origin" "PASS" "BLOCKED"
  fi
done

# --- 6. PUBLIC ENDPOINTS ---
echo ""
echo -e "${BLUE}[6] Public Endpoints${NC}"
for ep in "opds" "tax-formulas"; do
  STATUS=$($CURL -4 -o /dev/null -w "%{http_code}" "$API/$ep" -H "Accept: application/json")
  check "$ep (public)" "200" "$STATUS"
done

# --- 7. CITIZEN ENDPOINTS ---
echo ""
echo -e "${BLUE}[7] Citizen Endpoints${NC}"
for ep in "citizen/services" "citizen/bills?nik=3201234567890001"; do
  STATUS=$($CURL -4 -o /dev/null -w "%{http_code}" "$API/$ep" \
    -H "Authorization: Bearer $CITIZEN_TOKEN" -H "Accept: application/json")
  check "$ep" "200" "$STATUS"
done

# --- 8. ERROR HANDLING ---
echo ""
echo -e "${BLUE}[8] Error Handling${NC}"
STATUS=$($CURL -4 -o /dev/null -w "%{http_code}" "$API/zones" \
  -H "Authorization: Bearer invalid" -H "Accept: application/json")
check "401 Bad Token" "401" "$STATUS"

STATUS=$($CURL -4 -o /dev/null -w "%{http_code}" "$API/zones/99999" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json")
check "404 Not Found" "404" "$STATUS"

# --- SUMMARY ---
echo ""
echo "============================================"
echo "  HASIL: $PASS/$TOTAL PASSED"
if [ $FAIL -gt 0 ]; then
  echo "  ⚠️  $FAIL FAILED"
else
  echo "  🎉 ALL TESTS PASSED"
fi
echo "============================================"

# Save results
RESULTS_FILE="results/15_Baubaukota_Prod_$(date +%Y%m%d_%H%M%S).md"
mkdir -p results

cat << REPORT > "$RESULTS_FILE"
# 🏛️ BAUBAUKOTA Production Test Report

**Date**: $(date '+%Y-%m-%d %H:%M:%S')
**API**: $API
**Admin FE**: $ADMIN_FE
**Mobile FE**: $MOBILE_FE
**Petugas FE**: $PETUGAS_FE

## Summary
- **PASS**: $PASS
- **FAIL**: $FAIL
- **TOTAL**: $TOTAL

## Sections Tested
1. Frontend Availability (3 domains)
2. API Authentication (Admin + Citizen)
3. Core API Endpoints (13 endpoints)
4. PBB Bapenda API (Calculate + Classifications + FE)
5. CORS Cross-Origin Validation (3 origins) — **REGRESSION GUARD**
6. Public Endpoints
7. Citizen Endpoints
8. Error Handling (401/404)
9. Auto Deduct & Build-Only Config Verification

> **NOSS Compliance**: Seluruh pengujian dilakukan tanpa screenshot, murni via CLI/curl.
REPORT

echo "Results saved to: $RESULTS_FILE"

exit $FAIL
