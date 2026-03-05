#!/bin/bash
# ============================================================================
# SIPANDA - Production Endpoint Regression Test
# Menguji SEMUA endpoint API produksi untuk memastikan tidak ada error 500.
#
# Penggunaan: bash testing/test_production_regression.sh
# ============================================================================

BASE_URL="${1:-https://api.sipanda.online}"
EMAIL="${2:-superadmin@sipanda.online}"
PASS="${3:-Bapenda2026!}"

PASS_COUNT=0
FAIL_COUNT=0
TOTAL=0

check() {
  local label="$1"
  local expected="$2"
  local actual="$3"
  TOTAL=$((TOTAL + 1))
  if [ "$actual" == "$expected" ]; then
    echo "  ✅ $label: HTTP $actual"
    PASS_COUNT=$((PASS_COUNT + 1))
  else
    echo "  ❌ $label: HTTP $actual (expected $expected)"
    FAIL_COUNT=$((FAIL_COUNT + 1))
  fi
}

echo "============================================"
echo "  SIPANDA Production Regression Test"
echo "  Target: $BASE_URL"
echo "  Time: $(date)"
echo "============================================"
echo ""

# --- 1. LOGIN ---
echo "[1] Authentication"
TOKEN=$(curl -s -X POST "$BASE_URL/api/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASS\"}" | python3 -c "import sys,json; print(json.load(sys.stdin).get('token','FAIL'))" 2>/dev/null)

if [ "$TOKEN" == "FAIL" ] || [ -z "$TOKEN" ]; then
  echo "  ❌ LOGIN FAILED! Tidak bisa melanjutkan."
  exit 1
fi
echo "  ✅ Login OK (Token: ${TOKEN:0:20}...)"
PASS_COUNT=$((PASS_COUNT + 1))
TOTAL=$((TOTAL + 1))

AUTH="-H \"Authorization: Bearer $TOKEN\" -H \"Accept: application/json\""

# --- 2. LIST ENDPOINTS (harus 200) ---
echo ""
echo "[2] List Endpoints (GET → 200)"
ENDPOINTS=(
  "zones"
  "retribution-types"
  "retribution-classifications"
  "retribution-rates"
  "verifications"
  "bills"
  "users"
  "taxpayers"
  "tax-objects"
  "dashboard/stats"
  "analytics/realization"
  "reports/summary"
  "amnesty"
)

for ep in "${ENDPOINTS[@]}"; do
  STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/api/$ep" \
    -H "Authorization: Bearer $TOKEN" -H "Accept: application/json")
  check "$ep" "200" "$STATUS"
done

# --- 3. PUBLIC ENDPOINTS ---
echo ""
echo "[3] Public Endpoints (no auth → 200)"
PUBLIC_ENDPOINTS=("opds" "tax-formulas")
for ep in "${PUBLIC_ENDPOINTS[@]}"; do
  STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/api/$ep" -H "Accept: application/json")
  check "$ep (public)" "200" "$STATUS"
done

# --- 4. ERROR HANDLING ---
echo ""
echo "[4] Error Handling"

# 401 - Bad token
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/api/zones" \
  -H "Authorization: Bearer invalid" -H "Accept: application/json")
check "401 Bad Token" "401" "$STATUS"

# 422 - Validation error
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/api/zones" \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"name":"test-no-type"}')
check "422 Validation" "422" "$STATUS"

# 404 - Not found
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/api/zones/99999" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json")
check "404 Not Found" "404" "$STATUS"

# 404 - Delete nonexistent
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X DELETE "$BASE_URL/api/retribution-rates/99999" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json")
check "404 Delete Nonexistent" "404" "$STATUS"

# --- 5. FRONTEND ---
echo ""
echo "[5] Frontend Apps"
for url in "https://admin.sipanda.online" "https://sipanda.online" "https://petugas.sipanda.online"; do
  STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$url/")
  check "$url" "200" "$STATUS"
done

# --- 6. LOGOUT ---
echo ""
echo "[6] Logout & Post-Logout"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/api/logout" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json")
check "Logout" "200" "$STATUS"

STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/api/zones" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json")
check "After Logout (401)" "401" "$STATUS"

# --- SUMMARY ---
echo ""
echo "============================================"
echo "  HASIL: $PASS_COUNT/$TOTAL PASSED"
if [ $FAIL_COUNT -gt 0 ]; then
  echo "  ⚠️  $FAIL_COUNT FAILED"
fi
echo "============================================"

exit $FAIL_COUNT
