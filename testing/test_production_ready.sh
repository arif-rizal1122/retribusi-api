#!/bin/bash
# ============================================================================
# Production Readiness Test - api.sipanda.online
# ============================================================================
# Tests: CORS, Authentication, API Endpoints, Error Handling, PWA
# Run: chmod +x test_production_ready.sh && ./test_production_ready.sh
# ============================================================================

# Default values
API_URL="https://api.sipanda.online"
FRONTEND_ORIGIN="https://sipanda.online"
ADMIN_ORIGIN="https://admin.sipanda.online"

# Environment selection
if [ "$1" == "staging" ]; then
  echo -e "${YELLOW}Mode: STAGING (sipanda.online)${NC}"
  API_URL="https://api.sipanda.online"
  FRONTEND_ORIGIN="https://sipanda.online"
  ADMIN_ORIGIN="https://admin.sipanda.online"
elif [ "$1" == "dev" ]; then
  echo -e "${YELLOW}Mode: DEVELOPMENT (sipanda.online)${NC}"
  API_URL="https://api-dev.sipanda.online"
  FRONTEND_ORIGIN="https://dev.sipanda.online"
  ADMIN_ORIGIN="https://admin-dev.sipanda.online"
fi

RESULTS_FILE="results/12_Production_Readiness_$(date +%Y%m%d_%H%M%S).md"

PASS=0
FAIL=0
WARN=0
TOKEN=""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_pass() { echo -e "${GREEN}✅ PASS${NC}: $1"; ((PASS++)); RESULTS+="| ✅ PASS | $1 |\n"; }
log_fail() { echo -e "${RED}❌ FAIL${NC}: $1"; ((FAIL++)); RESULTS+="| ❌ FAIL | $1 |\n"; }
log_warn() { echo -e "${YELLOW}⚠️  WARN${NC}: $1"; ((WARN++)); RESULTS+="| ⚠️ WARN | $1 |\n"; }
log_section() { echo -e "\n${BLUE}━━━ $1 ━━━${NC}"; RESULTS+="\n### $1\n| Status | Detail |\n|--------|--------|\n"; }

RESULTS="# Production Readiness Test Report\n\n"
RESULTS+="**Date**: $(date '+%Y-%m-%d %H:%M:%S')\n"
RESULTS+="**API**: $API_URL\n"
RESULTS+="**Frontend**: $FRONTEND_ORIGIN\n\n"

# ============================================================================
# 1. CORS PREFLIGHT TESTS
# ============================================================================
log_section "1. CORS Preflight (OPTIONS)"

# Test OPTIONS from sipanda.online
RESPONSE=$(curl --retry 10 --retry-delay 1 --retry-all-errors -sI -X OPTIONS "$API_URL/api/me" \
  -H "Origin: $FRONTEND_ORIGIN" \
  -H "Access-Control-Request-Method: GET" \
  -H "Access-Control-Request-Headers: Content-Type, Authorization" 2>&1)

HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP/" | awk '{print $2}')
ACAO=$(echo "$RESPONSE" | grep -i "Access-Control-Allow-Origin:" | head -1 | tr -d '\r')
ACAC=$(echo "$RESPONSE" | grep -i "Access-Control-Allow-Credentials:" | head -1 | tr -d '\r')
ACAM=$(echo "$RESPONSE" | grep -i "Access-Control-Allow-Methods:" | head -1 | tr -d '\r')

if [ "$HTTP_CODE" = "204" ]; then log_pass "OPTIONS /api/me → 204 No Content"; else log_fail "OPTIONS /api/me → $HTTP_CODE (expected 204)"; fi

if echo "$ACAO" | grep -q "$FRONTEND_ORIGIN"; then
  log_pass "Access-Control-Allow-Origin: $FRONTEND_ORIGIN"
else
  log_fail "Missing/wrong Access-Control-Allow-Origin header"
fi

if echo "$ACAC" | grep -qi "true"; then
  log_pass "Access-Control-Allow-Credentials: true"
else
  log_fail "Missing Access-Control-Allow-Credentials"
fi

if echo "$ACAM" | grep -q "POST"; then
  log_pass "Access-Control-Allow-Methods includes POST"
else
  log_fail "Access-Control-Allow-Methods missing POST"
fi

# Check for duplicate Access-Control-Allow-Origin headers
DUP_COUNT=$(echo "$RESPONSE" | grep -ci "Access-Control-Allow-Origin:")
if [ "$DUP_COUNT" -le 1 ]; then
  log_pass "No duplicate Access-Control-Allow-Origin headers"
else
  log_fail "Duplicate Access-Control-Allow-Origin headers ($DUP_COUNT found)"
fi

# ============================================================================
# 2. CORS ON ACTUAL REQUESTS
# ============================================================================
log_section "2. CORS on Actual Responses"

# GET without auth (should return 401 with CORS headers)
RESPONSE=$(curl --retry 10 --retry-delay 1 --retry-all-errors -sv "$API_URL/api/me" \
  -H "Origin: $FRONTEND_ORIGIN" \
  -H "Accept: application/json" 2>&1)

HTTP_CODE=$(echo "$RESPONSE" | grep "< HTTP/" | awk '{print $3}')
ACAO_COUNT=$(echo "$RESPONSE" | grep -c "< Access-Control-Allow-Origin:")
BODY=$(echo "$RESPONSE" | tail -1)

if [ "$HTTP_CODE" = "401" ]; then
  log_pass "GET /api/me (no auth) → 401 Unauthorized"
else
  log_fail "GET /api/me (no auth) → $HTTP_CODE (expected 401)"
fi

if echo "$RESPONSE" | grep -q "< Access-Control-Allow-Origin: $FRONTEND_ORIGIN"; then
  log_pass "401 response includes CORS headers"
else
  log_fail "401 response missing CORS headers"
fi

if [ "$ACAO_COUNT" -le 1 ]; then
  log_pass "No duplicate CORS headers on 401 response"
else
  log_fail "Duplicate CORS headers on 401 response ($ACAO_COUNT found)"
fi

if echo "$BODY" | grep -q "Unauthenticated"; then
  log_pass "401 returns JSON: {\"message\":\"Unauthenticated.\"}"
else
  log_warn "401 response body unexpected: $BODY"
fi

# ============================================================================
# 3. CITIZEN LOGIN
# ============================================================================
log_section "3. Citizen Login"

# Login with demo credentials
LOGIN_RESPONSE=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s "$API_URL/api/citizen/login" \
  -X POST \
  -H "Origin: $FRONTEND_ORIGIN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"nik":"1234567890123456","password":"password"}')

if echo "$LOGIN_RESPONSE" | grep -q '"token"'; then
  TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
  log_pass "Citizen login successful, token received"
else
  log_fail "Citizen login failed: $LOGIN_RESPONSE"
fi

if echo "$LOGIN_RESPONSE" | grep -q '"user"'; then
  log_pass "Login response includes user data"
else
  log_fail "Login response missing user data"
fi

# Login with wrong credentials
BAD_LOGIN=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s "$API_URL/api/citizen/login" \
  -X POST \
  -H "Origin: $FRONTEND_ORIGIN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"nik":"0000000000000000","password":"wrongpassword"}')

if echo "$BAD_LOGIN" | grep -qi "salah\|invalid\|unauthorized"; then
  log_pass "Bad login correctly rejected"
else
  log_warn "Bad login response: $BAD_LOGIN"
fi

# CORS headers on login response
LOGIN_HEADERS=$(curl --retry 10 --retry-delay 1 --retry-all-errors -sv "$API_URL/api/citizen/login" \
  -X POST \
  -H "Origin: $FRONTEND_ORIGIN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"nik":"3201234567890001","password":"password123"}' 2>&1)

LOGIN_ACAO_COUNT=$(echo "$LOGIN_HEADERS" | grep -c "< Access-Control-Allow-Origin:")
if [ "$LOGIN_ACAO_COUNT" -eq 1 ]; then
  log_pass "Login response has exactly 1 Access-Control-Allow-Origin header"
else
  log_fail "Login response has $LOGIN_ACAO_COUNT Access-Control-Allow-Origin headers (expected 1)"
fi

# ============================================================================
# 4. AUTHENTICATED API ENDPOINTS
# ============================================================================
log_section "4. Authenticated API Endpoints"

if [ -n "$TOKEN" ]; then
  # /api/me
  ME_RESPONSE=$(curl --retry 10 --retry-delay 1 --retry-all-errors -sv "$API_URL/api/me" \
    -H "Origin: $FRONTEND_ORIGIN" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $TOKEN" 2>&1)

  ME_CODE=$(echo "$ME_RESPONSE" | grep "< HTTP/" | awk '{print $3}')
  ME_ACAO=$(echo "$ME_RESPONSE" | grep -c "< Access-Control-Allow-Origin:")

  if [ "$ME_CODE" = "200" ]; then
    log_pass "GET /api/me (authenticated) → 200 OK"
  else
    log_fail "GET /api/me (authenticated) → $ME_CODE"
  fi

  if [ "$ME_ACAO" -eq 1 ]; then
    log_pass "/api/me has exactly 1 CORS header"
  else
    log_fail "/api/me has $ME_ACAO CORS headers"
  fi

  # /api/citizen/services
  SVC_RESPONSE=$(curl --retry 10 --retry-delay 1 --retry-all-errors -sv "$API_URL/api/citizen/services" \
    -H "Origin: $FRONTEND_ORIGIN" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $TOKEN" 2>&1)

  SVC_CODE=$(echo "$SVC_RESPONSE" | grep "< HTTP/" | awk '{print $3}')
  if [ "$SVC_CODE" = "200" ]; then
    log_pass "GET /api/citizen/services → 200 OK"
  else
    log_warn "GET /api/citizen/services → $SVC_CODE"
  fi

  # /api/citizen/bills
  BILLS_RESPONSE=$(curl --retry 10 --retry-delay 1 --retry-all-errors -sv "$API_URL/api/citizen/bills?nik=1234567890123456" \
    -H "Origin: $FRONTEND_ORIGIN" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $TOKEN" 2>&1)

  BILLS_CODE=$(echo "$BILLS_RESPONSE" | grep "< HTTP/" | awk '{print $3}')
  if [ "$BILLS_CODE" = "200" ]; then
    log_pass "GET /api/citizen/bills → 200 OK"
  else
    log_warn "GET /api/citizen/bills → $BILLS_CODE"
  fi
else
  log_fail "Skipping authenticated tests - no token"
fi

# ============================================================================
# 5. CROSS-ORIGIN TESTS (Admin Origin)
# ============================================================================
log_section "5. Cross-Origin (admin.sipanda.online)"

ADMIN_PREFLIGHT=$(curl --retry 10 --retry-delay 1 --retry-all-errors -sI -X OPTIONS "$API_URL/api/me" \
  -H "Origin: $ADMIN_ORIGIN" \
  -H "Access-Control-Request-Method: GET" 2>&1)

ADMIN_CODE=$(echo "$ADMIN_PREFLIGHT" | grep "HTTP/" | awk '{print $2}')
if [ "$ADMIN_CODE" = "204" ]; then
  log_pass "OPTIONS from admin.sipanda.online → 204"
else
  log_fail "OPTIONS from admin.sipanda.online → $ADMIN_CODE"
fi

if echo "$ADMIN_PREFLIGHT" | grep -qi "Access-Control-Allow-Origin.*$ADMIN_ORIGIN"; then
  log_pass "CORS allows admin.sipanda.online"
else
  log_warn "CORS header for admin.sipanda.online may be missing"
fi

# ============================================================================
# 6. ERROR HANDLING
# ============================================================================
log_section "6. Error Handling (No 500s)"

# Test 404 endpoint
ERR_RESPONSE=$(curl --retry 10 --retry-delay 1 --retry-all-errors -sv "$API_URL/api/nonexistent-endpoint-xyz" \
  -H "Origin: $FRONTEND_ORIGIN" \
  -H "Accept: application/json" 2>&1)

ERR_CODE=$(echo "$ERR_RESPONSE" | grep "< HTTP/" | awk '{print $3}')
if [ "$ERR_CODE" != "500" ]; then
  log_pass "Unknown endpoint returns $ERR_CODE (not 500)"
else
  log_fail "Unknown endpoint returns 500 Internal Server Error"
fi

# Health check
HEALTH=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s -o /dev/null -w "%{http_code}" "$API_URL/up")
if [ "$HEALTH" = "200" ]; then
  log_pass "Health endpoint /up → 200 OK"
else
  log_warn "Health endpoint /up → $HEALTH"
fi

# ============================================================================
# 7. PWA MANIFEST
# ============================================================================
log_section "7. Frontend & PWA"

PWA_CHECK=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s "https://sipanda.online" -o /dev/null -w "%{http_code}")
if [ "$PWA_CHECK" = "200" ]; then
  log_pass "Frontend sipanda.online → 200 OK"
else
  log_fail "Frontend sipanda.online → $PWA_CHECK"
fi

# ============================================================================
# SUMMARY
# ============================================================================
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}  PRODUCTION READINESS SUMMARY${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "  ${GREEN}PASS${NC}: $PASS"
echo -e "  ${RED}FAIL${NC}: $FAIL"
echo -e "  ${YELLOW}WARN${NC}: $WARN"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

if [ "$FAIL" -eq 0 ]; then
  echo -e "\n${GREEN}🎉 ALL CRITICAL TESTS PASSED - PRODUCTION READY${NC}"
  RESULTS+="\n## Summary\n\n🎉 **ALL CRITICAL TESTS PASSED** — Production ready.\n\n"
else
  echo -e "\n${RED}⚠️  $FAIL CRITICAL TEST(S) FAILED - NOT PRODUCTION READY${NC}"
  RESULTS+="\n## Summary\n\n⚠️ **$FAIL CRITICAL TEST(S) FAILED** — Needs attention.\n\n"
fi

RESULTS+="- **Pass**: $PASS\n- **Fail**: $FAIL\n- **Warn**: $WARN\n"

# Save results
mkdir -p results
echo -e "$RESULTS" > "$RESULTS_FILE"
echo -e "\nResults saved to: $RESULTS_FILE"
