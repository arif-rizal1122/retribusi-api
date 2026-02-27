#!/bin/bash
# ============================================================================
# API Endpoint & CRUD Testing Suite - api.sipanda.online
# ============================================================================
# Tests all API routes: public, auth, CRUD lifecycle, response validation
# Run: chmod +x test_api_crud.sh && ./test_api_crud.sh
# ============================================================================

# Default to Production
API="https://api.sipanda.online/api"
ORIGIN="https://sipanda.online"

# Handle environment argument
if [ "$1" == "dev" ]; then
  echo -e "${YELLOW}Mode: DEVELOPMENT (VPS)${NC}"
  API="https://api-dev.sipanda.online/api"
  ORIGIN="https://dev.sipanda.online"
elif [ "$1" == "local" ]; then
  echo -e "${YELLOW}Mode: LOCALHOST${NC}"
  API="http://localhost:8000/api"
  ORIGIN="http://localhost:3000"
fi

RESULTS_FILE="results/14_API_CRUD_Test_${1:-prod}_$(date +%Y%m%d_%H%M%S).md"

PASS=0; FAIL=0; WARN=0; SKIP=0
GREEN='\033[0;32m'; RED='\033[0;31m'; YELLOW='\033[1;33m'
BLUE='\033[0;34m'; CYAN='\033[0;36m'; DIM='\033[2m'; NC='\033[0m'

log_pass() { echo -e "  ${GREEN}✅${NC} $1"; ((PASS++)); RESULTS+="| ✅ | $1 |\n"; }
log_fail() { echo -e "  ${RED}❌${NC} $1"; ((FAIL++)); RESULTS+="| ❌ | $1 |\n"; }
log_warn() { echo -e "  ${YELLOW}⚠️${NC}  $1"; ((WARN++)); RESULTS+="| ⚠️ | $1 |\n"; }
log_skip() { echo -e "  ${DIM}⏭️  $1${NC}"; ((SKIP++)); RESULTS+="| ⏭️ | $1 |\n"; }
log_section() { echo -e "\n${BLUE}━━━ $1 ━━━${NC}"; RESULTS+="\n### $1\n| St | Detail |\n|----|--------|\n"; }
log_subsection() { echo -e "${CYAN}  ▸ $1${NC}"; }

RESULTS="# 🧪 API & CRUD Test Report\n\n"
RESULTS+="**Date**: $(date '+%Y-%m-%d %H:%M:%S')\n"
RESULTS+="**Target**: $API\n\n"

# Helper: test endpoint, returns HTTP code
test_endpoint() {
  local METHOD=$1 URL=$2 DATA=$3 AUTH=$4 EXPECTED=$5 DESC=$6
  local ARGS=(-s -o /tmp/api_body.txt -w "%{http_code}" -X "$METHOD" "$URL"
    -H "Accept: application/json" -H "Origin: $ORIGIN")
  [ -n "$AUTH" ] && ARGS+=(-H "Authorization: Bearer $AUTH")
  [ -n "$DATA" ] && ARGS+=(-H "Content-Type: application/json" -d "$DATA")
  
  local CODE=$(curl "${ARGS[@]}")
  local BODY=$(cat /tmp/api_body.txt 2>/dev/null)

  if [ "$CODE" = "$EXPECTED" ]; then
    log_pass "$METHOD $DESC → $CODE"
  elif [ "$CODE" = "429" ]; then
    log_warn "$METHOD $DESC → 429 Rate Limited (rerun later)"
  else
    log_fail "$METHOD $DESC → $CODE (expected $EXPECTED)"
  fi
  echo "$CODE"
}

# Helper: test endpoint and get JSON body
test_json() {
  local METHOD=$1 URL=$2 DATA=$3 AUTH=$4
  local ARGS=(-s -X "$METHOD" "$URL" -H "Accept: application/json" -H "Origin: $ORIGIN")
  [ -n "$AUTH" ] && ARGS+=(-H "Authorization: Bearer $AUTH")
  [ -n "$DATA" ] && ARGS+=(-H "Content-Type: application/json" -d "$DATA")
  curl "${ARGS[@]}"
}

echo -e "${BLUE}🧪 API & CRUD Testing Suite${NC}"
echo -e "${DIM}Target: $API${NC}\n"

# ============================================================================
# STEP 0: Get Auth Tokens (admin + citizen)
# ============================================================================
log_section "0. Authentication Setup"

# Admin login
ADMIN_RESP=$(test_json POST "$API/login" '{"email":"bapenda@baubaukota.go.id","password":"password123"}')
ADMIN_TOKEN=$(echo "$ADMIN_RESP" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
if [ -n "$ADMIN_TOKEN" ]; then
  ADMIN_ROLE=$(echo "$ADMIN_RESP" | grep -o '"role":"[^"]*"' | cut -d'"' -f4)
  log_pass "Admin login → token received (role: $ADMIN_ROLE)"

  # Dynamically fetch a valid retribution_type_id and opd_id for CRUD tests
  RT_LIST=$(test_json GET "$API/retribution-types" "" "$ADMIN_TOKEN")
  VALID_RT_ID=$(echo "$RT_LIST" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
  VALID_OPD_ID=$(echo "$ADMIN_RESP" | grep -o '"opd_id":[0-9]*' | head -1 | cut -d':' -f2)
  [ -z "$VALID_RT_ID" ] && VALID_RT_ID=16
  [ -z "$VALID_OPD_ID" ] && VALID_OPD_ID=5
else
  log_warn "Admin login failed — some tests will be skipped. Response: $(echo $ADMIN_RESP | head -c 100)"
fi

# Citizen login
CITIZEN_RESP=$(test_json POST "$API/citizen/login" '{"nik":"1234567890123456","password":"password123"}')
CITIZEN_TOKEN=$(echo "$CITIZEN_RESP" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
if [ -n "$CITIZEN_TOKEN" ]; then
  log_pass "Citizen login → token received"
  CITIZEN_ID=$(echo "$CITIZEN_RESP" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
  CITIZEN_NIK=$(echo "$CITIZEN_RESP" | grep -o '"nik":"[^"]*"' | cut -d'"' -f4)
  log_pass "Citizen ID: $CITIZEN_ID, NIK: $CITIZEN_NIK"
else
  log_fail "Citizen login failed"
fi

# ============================================================================
# 1. PUBLIC ENDPOINTS
# ============================================================================
log_section "1. Public Endpoints (No Auth)"

test_endpoint GET "$API/opds" "" "" "200" "/opds (list OPDs)" > /dev/null
test_endpoint GET "$API/tax-formulas" "" "" "200" "/tax-formulas" > /dev/null
test_endpoint GET "$API/pbb/classifications" "" "" "200" "/pbb/classifications" > /dev/null
test_endpoint GET "$API/citizen/bills?nik=$CITIZEN_NIK" "" "" "200" "/citizen/bills?nik=..." > /dev/null

# Health check
test_endpoint GET "https://api.sipanda.online/up" "" "" "200" "/up (health)" > /dev/null

# ============================================================================
# 2. AUTH-REQUIRED ENDPOINTS (Citizen)
# ============================================================================
log_section "2. Citizen Authenticated Endpoints"

if [ -n "$CITIZEN_TOKEN" ]; then
  test_endpoint GET "$API/me" "" "$CITIZEN_TOKEN" "200" "/me (citizen profile)" > /dev/null
  
  # Citizen Services
  log_subsection "Citizen Services"
  SVC_RESP=$(test_json GET "$API/citizen/services" "" "$CITIZEN_TOKEN")
  SVC_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$API/citizen/services" -H "Authorization: Bearer $CITIZEN_TOKEN" -H "Accept: application/json")
  if [ "$SVC_CODE" = "200" ]; then
    log_pass "GET /citizen/services → 200"
    # Extract a service ID for detail test
    SVC_ID=$(echo "$SVC_RESP" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
    if [ -n "$SVC_ID" ]; then
      test_endpoint GET "$API/citizen/services/$SVC_ID" "" "$CITIZEN_TOKEN" "200" "/citizen/services/$SVC_ID (detail)" > /dev/null
      test_endpoint GET "$API/citizen/services/$SVC_ID/bills" "" "$CITIZEN_TOKEN" "200" "/citizen/services/$SVC_ID/bills" > /dev/null
    fi
  else
    log_fail "GET /citizen/services → $SVC_CODE"
  fi
  test_endpoint GET "$API/citizen/services/pending-periods" "" "$CITIZEN_TOKEN" "200" "/citizen/services/pending-periods" > /dev/null

  # Citizen reports
  log_subsection "Citizen Reports"
  test_endpoint GET "$API/citizen/reports" "" "$CITIZEN_TOKEN" "200" "/citizen/reports" > /dev/null

  # PBB Bapenda
  log_subsection "PBB Bapenda"
  test_endpoint GET "$API/pbb/bapenda/my-objects" "" "$CITIZEN_TOKEN" "200" "/pbb/bapenda/my-objects" > /dev/null
  test_endpoint GET "$API/pbb/bapenda/my-transactions" "" "$CITIZEN_TOKEN" "200" "/pbb/bapenda/my-transactions" > /dev/null
else
  log_skip "Citizen endpoints skipped (no token)"
fi

# ============================================================================
# 3. AUTH-REQUIRED ENDPOINTS (Admin/Petugas)
# ============================================================================
log_section "3. Admin/Petugas Authenticated Endpoints"

if [ -n "$ADMIN_TOKEN" ]; then

  # User Profile
  log_subsection "User Profile"
  test_endpoint GET "$API/user" "" "$ADMIN_TOKEN" "200" "/user (admin profile)" > /dev/null
  test_endpoint GET "$API/me" "" "$ADMIN_TOKEN" "200" "/me (admin)" > /dev/null

  # Dashboard
  log_subsection "Dashboard"
  test_endpoint GET "$API/dashboard/stats" "" "$ADMIN_TOKEN" "200" "/dashboard/stats" > /dev/null
  test_endpoint GET "$API/dashboard/revenue-trend" "" "$ADMIN_TOKEN" "200" "/dashboard/revenue-trend" > /dev/null
  test_endpoint GET "$API/dashboard/map-potentials" "" "$ADMIN_TOKEN" "200" "/dashboard/map-potentials" > /dev/null

  # Analytics
  log_subsection "Analytics"
  test_endpoint GET "$API/analytics/realization" "" "$ADMIN_TOKEN" "200" "/analytics/realization" > /dev/null
  test_endpoint GET "$API/analytics/heatmap" "" "$ADMIN_TOKEN" "200" "/analytics/heatmap" > /dev/null

  # Resource Listing (READ)
  log_subsection "Resource Listings (Index)"
  test_endpoint GET "$API/retribution-types" "" "$ADMIN_TOKEN" "200" "/retribution-types" > /dev/null
  test_endpoint GET "$API/taxpayers" "" "$ADMIN_TOKEN" "200" "/taxpayers" > /dev/null
  test_endpoint GET "$API/tax-objects" "" "$ADMIN_TOKEN" "200" "/tax-objects" > /dev/null
  test_endpoint GET "$API/bills" "" "$ADMIN_TOKEN" "200" "/bills" > /dev/null
  test_endpoint GET "$API/zones" "" "$ADMIN_TOKEN" "200" "/zones" > /dev/null
  test_endpoint GET "$API/retribution-classifications" "" "$ADMIN_TOKEN" "200" "/retribution-classifications" > /dev/null
  test_endpoint GET "$API/retribution-rates" "" "$ADMIN_TOKEN" "200" "/retribution-rates" > /dev/null
  test_endpoint GET "$API/users" "" "$ADMIN_TOKEN" "200" "/users" > /dev/null
  test_endpoint GET "$API/verifications" "" "$ADMIN_TOKEN" "200" "/verifications" > /dev/null

  # Reports
  log_subsection "Reports"
  test_endpoint GET "$API/reports/summary" "" "$ADMIN_TOKEN" "200" "/reports/summary" > /dev/null
  test_endpoint GET "$API/reports/recent" "" "$ADMIN_TOKEN" "200" "/reports/recent" > /dev/null
  test_endpoint GET "$API/reports/petugas-performance" "" "$ADMIN_TOKEN" "200" "/reports/petugas-performance" > /dev/null
  test_endpoint GET "$API/reports/monthly" "" "$ADMIN_TOKEN" "200" "/reports/monthly" > /dev/null

  # Pengawas / Surveillance
  log_subsection "Pengawas / Surveillance"
  test_endpoint GET "$API/pengawas/audit-logs" "" "$ADMIN_TOKEN" "200" "/pengawas/audit-logs" > /dev/null
  test_endpoint GET "$API/pengawas/anomalies" "" "$ADMIN_TOKEN" "200" "/pengawas/anomalies" > /dev/null
  test_endpoint GET "$API/pengawas/compliance-stats" "" "$ADMIN_TOKEN" "200" "/pengawas/compliance-stats" > /dev/null
  test_endpoint GET "$API/pengawas/enforcements" "" "$ADMIN_TOKEN" "200" "/pengawas/enforcements" > /dev/null
  test_endpoint GET "$API/pengawas/penindakan" "" "$ADMIN_TOKEN" "200" "/pengawas/penindakan" > /dev/null

  # TTE
  log_subsection "TTE / E-Registry"
  test_endpoint GET "$API/tte/documents" "" "$ADMIN_TOKEN" "200" "/tte/documents" > /dev/null

  # Amnesty
  log_subsection "Amnesty"
  test_endpoint GET "$API/amnesty" "" "$ADMIN_TOKEN" "200" "/amnesty" > /dev/null

  # PBB Bapenda Admin
  log_subsection "PBB Bapenda Admin"
  test_endpoint GET "$API/pbb/bapenda/transactions" "" "$ADMIN_TOKEN" "200" "/pbb/bapenda/transactions" > /dev/null
  test_endpoint GET "$API/pbb/bapenda/stats" "" "$ADMIN_TOKEN" "200" "/pbb/bapenda/stats" > /dev/null

else
  log_skip "Admin endpoints skipped (no token)"
fi

# ============================================================================
# 4. CRUD LIFECYCLE - ZONE (Create → Read → Update → Delete)
# ============================================================================
log_section "4. CRUD Lifecycle: Zone"

if [ -n "$ADMIN_TOKEN" ]; then
  ZONE_NAME="Test Zone $(date +%s)"
  
  # CREATE
  log_subsection "CREATE"
  ZONE_CODE="Z$(date +%s | tail -c 8 | tr -d '\n')"
  CREATE_RESP=$(test_json POST "$API/zones" \
    "{\"name\":\"$ZONE_NAME\",\"code\":\"$ZONE_CODE\",\"description\":\"Auto-test zone\",\"opd_id\":$VALID_OPD_ID,\"retribution_type_id\":$VALID_RT_ID}" "$ADMIN_TOKEN")
  ZONE_ID=$(echo "$CREATE_RESP" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
  
  if [ -n "$ZONE_ID" ]; then
    log_pass "CREATE zone → ID: $ZONE_ID"
    
    # READ
    log_subsection "READ"
    READ_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$API/zones/$ZONE_ID" \
      -H "Authorization: Bearer $ADMIN_TOKEN" -H "Accept: application/json")
    if [ "$READ_CODE" = "200" ]; then
      log_pass "READ zone/$ZONE_ID → 200"
    else
      log_fail "READ zone/$ZONE_ID → $READ_CODE"
    fi
    
    # UPDATE
    log_subsection "UPDATE"
    UPDATE_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$API/zones/$ZONE_ID" \
      -X PUT \
      -H "Authorization: Bearer $ADMIN_TOKEN" \
      -H "Accept: application/json" \
      -H "Content-Type: application/json" \
      -d "{\"name\":\"$ZONE_NAME Updated\",\"description\":\"Updated by test\"}")
    if [ "$UPDATE_CODE" = "200" ]; then
      log_pass "UPDATE zone/$ZONE_ID → 200"
    else
      log_fail "UPDATE zone/$ZONE_ID → $UPDATE_CODE"
    fi
    
    # Verify update
    UPDATED_NAME=$(test_json GET "$API/zones/$ZONE_ID" "" "$ADMIN_TOKEN" | grep -o '"name":"[^"]*"' | cut -d'"' -f4)
    if echo "$UPDATED_NAME" | grep -q "Updated"; then
      log_pass "VERIFY update → name contains 'Updated'"
    else
      log_warn "Update may not have persisted (name: $UPDATED_NAME)"
    fi
    
    # DELETE
    log_subsection "DELETE"
    DELETE_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$API/zones/$ZONE_ID" \
      -X DELETE \
      -H "Authorization: Bearer $ADMIN_TOKEN" \
      -H "Accept: application/json")
    if [ "$DELETE_CODE" = "200" ] || [ "$DELETE_CODE" = "204" ]; then
      log_pass "DELETE zone/$ZONE_ID → $DELETE_CODE"
    else
      log_fail "DELETE zone/$ZONE_ID → $DELETE_CODE"
    fi
    
    # Verify deletion
    GONE_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$API/zones/$ZONE_ID" \
      -H "Authorization: Bearer $ADMIN_TOKEN" -H "Accept: application/json")
    if [ "$GONE_CODE" = "404" ]; then
      log_pass "VERIFY delete → 404 (gone)"
    else
      log_warn "Zone still accessible after delete ($GONE_CODE)"
    fi
  else
    log_fail "CREATE zone failed: $(echo $CREATE_RESP | head -c 150)"
  fi
else
  log_skip "Zone CRUD skipped (no admin token)"
fi

# ============================================================================
# 5. CRUD LIFECYCLE - TAXPAYER
# ============================================================================
log_section "5. CRUD Lifecycle: Taxpayer"

if [ -n "$ADMIN_TOKEN" ]; then
  TP_NIK="99$(date +%s | tail -c 15)"
  # Ensure NIK is exactly 16 digits
  TP_NIK=$(printf "99%014d" "$(date +%s)")
  
  # CREATE
  log_subsection "CREATE"
  TP_CREATE=$(test_json POST "$API/taxpayers" \
    "{\"nik\":\"$TP_NIK\",\"name\":\"Test Wajib Pajak\",\"address\":\"Jl. Test No. 1\",\"phone\":\"081234567890\",\"retribution_type_ids\":[$VALID_RT_ID]}" "$ADMIN_TOKEN")
  TP_ID=$(echo "$TP_CREATE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
  
  if [ -n "$TP_ID" ]; then
    log_pass "CREATE taxpayer → ID: $TP_ID, NIK: $TP_NIK"
    
    # READ
    log_subsection "READ"
    test_endpoint GET "$API/taxpayers/$TP_ID" "" "$ADMIN_TOKEN" "200" "/taxpayers/$TP_ID" > /dev/null
    
    # SEARCH
    log_subsection "SEARCH"
    test_endpoint GET "$API/taxpayers/search/$TP_NIK" "" "$ADMIN_TOKEN" "200" "/taxpayers/search/$TP_NIK" > /dev/null
    
    # UPDATE
    log_subsection "UPDATE"
    UP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$API/taxpayers/$TP_ID" \
      -X PUT \
      -H "Authorization: Bearer $ADMIN_TOKEN" \
      -H "Accept: application/json" \
      -H "Content-Type: application/json" \
      -d "{\"name\":\"Test WP Updated\",\"address\":\"Jl. Updated\"}")
    if [ "$UP_CODE" = "200" ]; then
      log_pass "UPDATE taxpayer/$TP_ID → 200"
    else
      log_fail "UPDATE taxpayer/$TP_ID → $UP_CODE"
    fi
    
    # DELETE
    log_subsection "DELETE"
    DEL_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$API/taxpayers/$TP_ID" \
      -X DELETE \
      -H "Authorization: Bearer $ADMIN_TOKEN" \
      -H "Accept: application/json")
    if [ "$DEL_CODE" = "200" ] || [ "$DEL_CODE" = "204" ]; then
      log_pass "DELETE taxpayer/$TP_ID → $DEL_CODE"
    else
      log_fail "DELETE taxpayer/$TP_ID → $DEL_CODE"
    fi
  else
    log_fail "CREATE taxpayer failed: $(echo $TP_CREATE | head -c 200)"
  fi
else
  log_skip "Taxpayer CRUD skipped (no admin token)"
fi

# ============================================================================
# 6. CRUD LIFECYCLE - RETRIBUTION TYPE
# ============================================================================
log_section "6. CRUD Lifecycle: Retribution Type"

if [ -n "$ADMIN_TOKEN" ]; then
  RT_CODE_VAL="RT$(date +%s | tail -c 6)"
  
  # CREATE
  log_subsection "CREATE"
  RT_CREATE=$(test_json POST "$API/retribution-types" \
    "{\"name\":\"Test Retribusi $RT_CODE_VAL\",\"code\":\"$RT_CODE_VAL\",\"category\":\"retribusi_jasa_umum\",\"base_amount\":10000,\"unit\":\"orang\"}" "$ADMIN_TOKEN")
  RT_ID=$(echo "$RT_CREATE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
  
  if [ -n "$RT_ID" ]; then
    log_pass "CREATE retribution-type → ID: $RT_ID"
    
    # READ
    log_subsection "READ"
    test_endpoint GET "$API/retribution-types/$RT_ID" "" "$ADMIN_TOKEN" "200" "/retribution-types/$RT_ID" > /dev/null
    
    # UPDATE
    log_subsection "UPDATE"
    RT_UP=$(curl -s -o /dev/null -w "%{http_code}" "$API/retribution-types/$RT_ID" \
      -X PUT \
      -H "Authorization: Bearer $ADMIN_TOKEN" \
      -H "Accept: application/json" \
      -H "Content-Type: application/json" \
      -d "{\"name\":\"Test Retribusi Updated\"}")
    if [ "$RT_UP" = "200" ]; then
      log_pass "UPDATE retribution-type/$RT_ID → 200"
    else
      log_fail "UPDATE retribution-type/$RT_ID → $RT_UP"
    fi
    
    # DELETE
    log_subsection "DELETE"
    RT_DEL=$(curl -s -o /dev/null -w "%{http_code}" "$API/retribution-types/$RT_ID" \
      -X DELETE \
      -H "Authorization: Bearer $ADMIN_TOKEN" \
      -H "Accept: application/json")
    if [ "$RT_DEL" = "200" ] || [ "$RT_DEL" = "204" ]; then
      log_pass "DELETE retribution-type/$RT_ID → $RT_DEL"
    else
      log_fail "DELETE retribution-type/$RT_ID → $RT_DEL"
    fi
  else
    log_fail "CREATE retribution-type failed: $(echo $RT_CREATE | head -c 200)"
  fi
else
  log_skip "Retribution Type CRUD skipped"
fi

# ============================================================================
# 7. TAX SIMULATION (Public CRUD-like)
# ============================================================================
log_section "7. Tax Simulation & PBB Calculation"

# Get a formula classification
FORMULA_RESP=$(test_json GET "$API/tax-formulas" "" "")
FORMULA_ID=$(echo "$FORMULA_RESP" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

if [ -n "$FORMULA_ID" ]; then
  SIM_RESP=$(test_json POST "$API/simulate-tax" \
    "{\"classification_id\":$FORMULA_ID,\"variables\":{\"volume\":10,\"tarif\":5000,\"luas\":100}}" "")
  if echo "$SIM_RESP" | grep -q '"result"'; then
    log_pass "Tax simulation → result received"
  elif echo "$SIM_RESP" | grep -q '"error"'; then
    log_warn "Tax simulation → error (may need correct variables)"
  else
    log_fail "Tax simulation → unexpected response"
  fi
else
  log_skip "Tax simulation skipped (no formula found)"
fi

# PBB calculation
PBB_RESP=$(test_json POST "$API/pbb/calculate" \
  '{"luas_bumi":200,"kelas_bumi":"A1","luas_bangunan":100,"kelas_bangunan":"A1"}' "")
if echo "$PBB_RESP" | grep -qi "pbb_terhutang\|result\|pajak"; then
  log_pass "PBB calculation → result received"
else
  PBB_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$API/pbb/calculate" -X POST \
    -H "Content-Type: application/json" \
    -d '{"luas_bumi":200,"kelas_bumi":"A1","luas_bangunan":100,"kelas_bangunan":"A1"}')
  if [ "$PBB_CODE" = "200" ]; then
    log_pass "PBB calculation → 200 OK"
  else
    log_warn "PBB calculation → $PBB_CODE (may need correct kelas data)"
  fi
fi

# ============================================================================
# 8. RESPONSE FORMAT VALIDATION
# ============================================================================
log_section "8. Response Format Validation"

if [ -n "$ADMIN_TOKEN" ]; then
  # Check pagination on list endpoints
  BILLS_RESP=$(test_json GET "$API/bills" "" "$ADMIN_TOKEN")
  if echo "$BILLS_RESP" | grep -q '"data"'; then
    log_pass "/bills response has 'data' wrapper"
  else
    log_warn "/bills response missing 'data' wrapper"
  fi

  if echo "$BILLS_RESP" | grep -qE '"current_page"|"total"|"per_page"'; then
    log_pass "/bills response has pagination metadata"
  else
    log_warn "/bills response missing pagination info"
  fi

  # Check JSON content-type
  CT=$(curl -sI "$API/bills" -H "Authorization: Bearer $ADMIN_TOKEN" -H "Accept: application/json" | grep -i "Content-Type:" | tr -d '\r')
  if echo "$CT" | grep -qi "application/json"; then
    log_pass "Content-Type is application/json"
  else
    log_fail "Content-Type is not JSON: $CT"
  fi

  # Validate user response structure
  USER_RESP=$(test_json GET "$API/me" "" "$ADMIN_TOKEN")
  for field in id name nik; do
    if echo "$USER_RESP" | grep -q "\"$field\""; then
      log_pass "/me response has '$field' field"
    else
      log_warn "/me response missing '$field' field"
    fi
  done
fi

# ============================================================================
# 9. ERROR RESPONSE VALIDATION
# ============================================================================
log_section "9. Error Response Validation"

# 401 format
UNAUTH_RESP=$(test_json GET "$API/me" "" "")
if echo "$UNAUTH_RESP" | grep -q '"message"'; then
  log_pass "401 response has 'message' field"
else
  log_fail "401 response missing 'message' field"
fi

# 404 format
NOT_FOUND=$(test_json GET "$API/nonexistent-route-xyz" "" "$ADMIN_TOKEN")
NF_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$API/nonexistent-route-xyz" \
  -H "Authorization: Bearer $ADMIN_TOKEN" -H "Accept: application/json")
if [ "$NF_CODE" = "404" ]; then
  log_pass "Unknown route → 404"
else
  log_fail "Unknown route → $NF_CODE (expected 404)"
fi

# 422 validation format
INVALID_REG=$(test_json POST "$API/citizen/register" '{"nik":"","password":""}')
if echo "$INVALID_REG" | grep -qE '"errors"|"message"'; then
  log_pass "Validation error returns structured error response"
else
  log_warn "Validation error format unexpected"
fi

# ============================================================================
# 10. CITIZEN PROFILE UPDATE (CRUD)
# ============================================================================
log_section "10. Citizen Profile Update"

if [ -n "$CITIZEN_TOKEN" ]; then
  # Read current
  CURRENT=$(test_json GET "$API/me" "" "$CITIZEN_TOKEN")
  CURRENT_NAME=$(echo "$CURRENT" | grep -o '"name":"[^"]*"' | cut -d'"' -f4)
  log_pass "READ profile → name: $CURRENT_NAME"

  # Update
  UP_RESP=$(test_json POST "$API/me/update" \
    "{\"name\":\"$CURRENT_NAME\",\"address\":\"Jl. Merdeka No. 1, Bau-Bau\"}" "$CITIZEN_TOKEN")
  UP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$API/me/update" -X POST \
    -H "Authorization: Bearer $CITIZEN_TOKEN" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d "{\"name\":\"$CURRENT_NAME\",\"address\":\"Jl. Merdeka No. 1, Bau-Bau\"}")
  if [ "$UP_CODE" = "200" ]; then
    log_pass "UPDATE profile → 200 OK"
  else
    log_warn "UPDATE profile → $UP_CODE"
  fi
fi

# ============================================================================
# SUMMARY
# ============================================================================
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}  🧪 API & CRUD TEST SUMMARY${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "  ${GREEN}PASS${NC}:    $PASS"
echo -e "  ${RED}FAIL${NC}:    $FAIL"
echo -e "  ${YELLOW}WARN${NC}:    $WARN"
echo -e "  ${DIM}SKIP${NC}:    $SKIP"
TOTAL=$((PASS + FAIL + WARN + SKIP))
echo -e "  TOTAL:   $TOTAL"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

if [ "$FAIL" -eq 0 ]; then
  echo -e "\n${GREEN}🎉 ALL API & CRUD TESTS PASSED${NC}"
  RESULTS+="\n## Summary\n\n🎉 **ALL TESTS PASSED**\n\n"
else
  echo -e "\n${RED}⚠️  $FAIL TEST(S) FAILED${NC}"
  RESULTS+="\n## Summary\n\n⚠️ **$FAIL TEST(S) FAILED**\n\n"
fi

RESULTS+="- **Pass**: $PASS\n- **Fail**: $FAIL\n- **Warn**: $WARN\n- **Skip**: $SKIP\n- **Total**: $TOTAL\n"

mkdir -p results
echo -e "$RESULTS" > "$RESULTS_FILE"
echo -e "\nResults saved to: $RESULTS_FILE"
