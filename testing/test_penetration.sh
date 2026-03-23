#!/bin/bash
# ============================================================================
# Penetration Testing Suite - api.sipanda.online
# ============================================================================
# OWASP Top 10 + Common Attack Vectors
# Run: chmod +x test_penetration.sh && ./test_penetration.sh
# ============================================================================

# Default values
API_URL="https://api.sipanda.online"
FRONTEND="https://sipanda.online"

# Environment selection
if [ "$1" == "staging" ]; then
  echo -e "${YELLOW}Mode: STAGING (sipanda.online)${NC}"
  API_URL="https://api.sipanda.online"
  FRONTEND="https://sipanda.online"
elif [ "$1" == "dev" ]; then
  echo -e "${YELLOW}Mode: DEVELOPMENT (sipanda.online)${NC}"
  API_URL="https://api-dev.sipanda.online"
  FRONTEND="https://dev.sipanda.online"
fi

RESULTS_FILE="results/13_Penetration_Test_$(date +%Y%m%d_%H%M%S).md"

PASS=0
FAIL=0
WARN=0
CRITICAL=0

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
NC='\033[0m'

log_pass()     { echo -e "${GREEN}✅ SECURE${NC}: $1"; ((PASS++)); RESULTS+="| ✅ SECURE | $1 |\n"; }
log_fail()     { echo -e "${RED}❌ VULN${NC}:   $1"; ((FAIL++)); RESULTS+="| ❌ VULN | $1 |\n"; }
log_warn()     { echo -e "${YELLOW}⚠️  WARN${NC}:   $1"; ((WARN++)); RESULTS+="| ⚠️ WARN | $1 |\n"; }
log_critical() { echo -e "${MAGENTA}🚨 CRITICAL${NC}: $1"; ((CRITICAL++)); RESULTS+="| 🚨 CRITICAL | $1 |\n"; }
log_section()  { echo -e "\n${BLUE}━━━ $1 ━━━${NC}"; RESULTS+="\n### $1\n| Status | Detail |\n|--------|--------|\n"; }

RESULTS="# 🔒 Penetration Test Report\n\n"
RESULTS+="**Date**: $(date '+%Y-%m-%d %H:%M:%S')\n"
RESULTS+="**Target**: $API_URL\n"
RESULTS+="**Methodology**: OWASP Top 10 + Custom Vectors\n\n"

# Get a valid token for authenticated tests
TOKEN=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s "$API_URL/api/citizen/login" -X POST \
  -H "Content-Type: application/json" \
  -d '{"nik":"1234567890123456","password":"password"}' | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

# ============================================================================
# 1. SENSITIVE FILE EXPOSURE (A01:2021 - Broken Access Control)
# ============================================================================
log_section "1. Sensitive File Exposure"

# .env file
ENV_CODE=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s -o /dev/null -w "%{http_code}" "$API_URL/.env")
if [ "$ENV_CODE" = "403" ] || [ "$ENV_CODE" = "404" ]; then
  log_pass ".env file blocked ($ENV_CODE)"
else
  log_critical ".env file accessible! Status: $ENV_CODE"
fi

# .git directory
GIT_CODE=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s -o /dev/null -w "%{http_code}" "$API_URL/.git/config")
if [ "$GIT_CODE" = "403" ] || [ "$GIT_CODE" = "404" ]; then
  log_pass ".git directory blocked ($GIT_CODE)"
else
  log_critical ".git directory exposed! Status: $GIT_CODE"
fi

# .htaccess
HT_CODE=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s -o /dev/null -w "%{http_code}" "$API_URL/.htaccess")
if [ "$HT_CODE" = "403" ] || [ "$HT_CODE" = "404" ]; then
  log_pass ".htaccess blocked ($HT_CODE)"
else
  log_warn ".htaccess accessible: $HT_CODE"
fi

# composer.json
COMPOSER_CODE=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s -o /dev/null -w "%{http_code}" "$API_URL/composer.json")
if [ "$COMPOSER_CODE" = "404" ] || [ "$COMPOSER_CODE" = "403" ]; then
  log_pass "composer.json not exposed ($COMPOSER_CODE)"
else
  log_warn "composer.json accessible: $COMPOSER_CODE"
fi

# storage/logs
LOG_CODE=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s -o /dev/null -w "%{http_code}" "$API_URL/storage/logs/laravel.log")
if [ "$LOG_CODE" = "404" ] || [ "$LOG_CODE" = "403" ]; then
  log_pass "Laravel logs not exposed ($LOG_CODE)"
else
  log_critical "Laravel logs accessible! Status: $LOG_CODE"
fi

# phpinfo
PHPINFO_CODE=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s -o /dev/null -w "%{http_code}" "$API_URL/phpinfo.php")
if [ "$PHPINFO_CODE" = "404" ]; then
  log_pass "phpinfo.php not exposed ($PHPINFO_CODE)"
else
  log_warn "phpinfo.php accessible: $PHPINFO_CODE"
fi

# ============================================================================
# 2. SQL INJECTION (A03:2021 - Injection)
# ============================================================================
log_section "2. SQL Injection"

# Login with SQL injection
SQLI_LOGIN=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s "$API_URL/api/citizen/login" -X POST \
  -H "Content-Type: application/json" \
  -d '{"nik":"1'\'' OR 1=1--","password":"test"}')

if echo "$SQLI_LOGIN" | grep -qi "token\|success"; then
  log_critical "SQL Injection in login - AUTH BYPASS!"
else
  log_pass "Login immune to basic SQLi (OR 1=1)"
fi

# UNION injection in search
SQLI_UNION=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s "$API_URL/api/citizen/bills?nik=1' UNION SELECT * FROM users--" \
  -H "Accept: application/json" 2>&1)

if echo "$SQLI_UNION" | grep -qi "SQLSTATE\|syntax error\|mysql"; then
  log_fail "SQL error leakage in bills endpoint"
else
  log_pass "No SQL error leakage in bills query"
fi

# Blind SQLi with time delay
SQLI_TIME_START=$(date +%s)
curl --retry 10 --retry-delay 1 --retry-all-errors -s "$API_URL/api/citizen/login" -X POST \
  -H "Content-Type: application/json" \
  -d '{"nik":"1'\'' AND SLEEP(5)--","password":"test"}' > /dev/null
SQLI_TIME_END=$(date +%s)
SQLI_DURATION=$((SQLI_TIME_END - SQLI_TIME_START))

if [ "$SQLI_DURATION" -ge 4 ]; then
  log_fail "Possible blind SQLi (response took ${SQLI_DURATION}s)"
else
  log_pass "No blind SQLi detected (response in ${SQLI_DURATION}s)"
fi

# ============================================================================
# 3. XSS (A03:2021 - Injection)
# ============================================================================
log_section "3. Cross-Site Scripting (XSS)"

# Stored XSS via registration
XSS_REG=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s "$API_URL/api/citizen/register" -X POST \
  -H "Content-Type: application/json" \
  -d '{"nik":"9999999999999999","name":"<script>alert(1)</script>","password":"test123","password_confirmation":"test123","address":"<img src=x onerror=alert(1)>"}')

if echo "$XSS_REG" | grep -q "<script>"; then
  log_fail "XSS payload reflected in registration response"
else
  log_pass "XSS payload not reflected in registration"
fi

# XSS via query parameters
XSS_QUERY=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s "$API_URL/api/citizen/bills?nik=<script>alert(1)</script>" \
  -H "Accept: application/json")

if echo "$XSS_QUERY" | grep -q "<script>"; then
  log_fail "XSS payload reflected in query response"
else
  log_pass "XSS payload not reflected in query params"
fi

# ============================================================================
# 4. BROKEN AUTHENTICATION (A07:2021)
# ============================================================================
log_section "4. Broken Authentication"

# Access protected endpoint without token
NO_AUTH=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s -o /dev/null -w "%{http_code}" "$API_URL/api/me" \
  -H "Accept: application/json")
if [ "$NO_AUTH" = "401" ]; then
  log_pass "/api/me returns 401 without token"
else
  log_fail "/api/me returns $NO_AUTH without token (expected 401)"
fi

# Access with fabricated token
FAKE_TOKEN=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s -o /dev/null -w "%{http_code}" "$API_URL/api/me" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer fake_token_12345_should_not_work")
if [ "$FAKE_TOKEN" = "401" ]; then
  log_pass "Fabricated token rejected (401)"
else
  log_fail "Fabricated token accepted! Status: $FAKE_TOKEN"
fi

# Access admin endpoint with citizen token
ADMIN_ACCESS=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s -o /dev/null -w "%{http_code}" "$API_URL/api/users" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN")
if [ "$ADMIN_ACCESS" = "401" ] || [ "$ADMIN_ACCESS" = "403" ] || [ "$ADMIN_ACCESS" = "404" ]; then
  log_pass "Citizen token cannot access /api/users ($ADMIN_ACCESS)"
else
  log_fail "Citizen token can access admin /api/users! Status: $ADMIN_ACCESS"
fi

# ============================================================================
# 5. IDOR - INSECURE DIRECT OBJECT REFERENCE (A01:2021)
# ============================================================================
log_section "5. IDOR (Insecure Direct Object Reference)"

# Try to access another user's data
IDOR_USER=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s "$API_URL/api/taxpayers/1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN")

if echo "$IDOR_USER" | grep -qi "forbidden\|unauthorized\|403\|not found"; then
  log_pass "IDOR blocked for /api/taxpayers/1"
else
  IDOR_CODE=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s -o /dev/null -w "%{http_code}" "$API_URL/api/taxpayers/1" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $TOKEN")
  if [ "$IDOR_CODE" = "200" ]; then
    log_warn "IDOR possible: citizen can access /api/taxpayers/1 (review if intended)"
  else
    log_pass "IDOR blocked for /api/taxpayers/1 ($IDOR_CODE)"
  fi
fi

# Try to access another user's bills
IDOR_BILLS=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s -o /dev/null -w "%{http_code}" "$API_URL/api/bills/1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN")
if [ "$IDOR_BILLS" = "403" ] || [ "$IDOR_BILLS" = "404" ] || [ "$IDOR_BILLS" = "401" ]; then
  log_pass "IDOR blocked for /api/bills/1 ($IDOR_BILLS)"
else
  log_warn "IDOR possible: citizen can access /api/bills/1 ($IDOR_BILLS) (review scope)"
fi

# ============================================================================
# 6. PATH TRAVERSAL (A01:2021)
# ============================================================================
log_section "6. Path Traversal"

TRAVERSAL1=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s -o /dev/null -w "%{http_code}" "$API_URL/../../etc/passwd")
if [ "$TRAVERSAL1" = "400" ] || [ "$TRAVERSAL1" = "403" ] || [ "$TRAVERSAL1" = "404" ] || [ "$TRAVERSAL1" = "200" ]; then
  # Check if actual /etc/passwd content leaked
  TRAVERSAL_BODY=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s "$API_URL/../../etc/passwd")
  if echo "$TRAVERSAL_BODY" | grep -q "root:"; then
    log_critical "Path traversal exposes /etc/passwd!"
  else
    log_pass "Path traversal blocked (no file content leaked)"
  fi
fi

TRAVERSAL2=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s -o /dev/null -w "%{http_code}" "$API_URL/..%2F..%2Fetc%2Fpasswd")
TRAVERSAL_BODY2=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s "$API_URL/..%2F..%2Fetc%2Fpasswd")
if echo "$TRAVERSAL_BODY2" | grep -q "root:"; then
  log_critical "Encoded path traversal exposes /etc/passwd!"
else
  log_pass "Encoded path traversal blocked"
fi

# ============================================================================
# 7. SECURITY HEADERS (A05:2021 - Security Misconfiguration)
# ============================================================================
log_section "7. Security Headers"

HEADERS=$(curl --retry 10 --retry-delay 1 --retry-all-errors -sI "$API_URL/api/me" -H "Accept: application/json" 2>&1)

# HSTS
if echo "$HEADERS" | grep -qi "Strict-Transport-Security"; then
  log_pass "HSTS header present"
else
  log_fail "HSTS header missing"
fi

# X-Content-Type-Options
if echo "$HEADERS" | grep -qi "X-Content-Type-Options.*nosniff"; then
  log_pass "X-Content-Type-Options: nosniff"
else
  log_fail "X-Content-Type-Options header missing"
fi

# X-Frame-Options
if echo "$HEADERS" | grep -qi "X-Frame-Options"; then
  log_pass "X-Frame-Options present (clickjacking protection)"
else
  log_fail "X-Frame-Options missing (clickjacking risk)"
fi

# X-XSS-Protection
if echo "$HEADERS" | grep -qi "X-XSS-Protection"; then
  log_pass "X-XSS-Protection header present"
else
  log_warn "X-XSS-Protection header missing"
fi

# Server header leakage
SERVER_HEADER=$(echo "$HEADERS" | grep -i "^Server:" | tr -d '\r')
if echo "$SERVER_HEADER" | grep -qiE "nginx/|apache/|php/"; then
  log_warn "Server version exposed: $SERVER_HEADER"
else
  log_pass "Server version not fully exposed"
fi

# X-Powered-By
if echo "$HEADERS" | grep -qi "X-Powered-By"; then
  POWERED=$(echo "$HEADERS" | grep -i "X-Powered-By" | tr -d '\r')
  log_fail "X-Powered-By header exposes tech stack: $POWERED"
else
  log_pass "X-Powered-By header not present (tech stack hidden)"
fi

# ============================================================================
# 8. RATE LIMITING (A04:2021 - Insecure Design)
# ============================================================================
log_section "8. Rate Limiting / Brute Force Protection"

echo -e "${YELLOW}  Testing rate limit (sending 65 rapid requests)...${NC}"
RATE_LIMITED=false
for i in $(seq 1 65); do
  RATE_CODE=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s -o /dev/null -w "%{http_code}" "$API_URL/api/citizen/login" \
    -X POST \
    -H "Content-Type: application/json" \
    -d '{"nik":"0000000000000000","password":"wrong"}')
  if [ "$RATE_CODE" = "429" ]; then
    RATE_LIMITED=true
    log_pass "Rate limiting active (429 at request #$i)"
    break
  fi
done

if [ "$RATE_LIMITED" = false ]; then
  log_fail "No rate limiting detected after 65 requests (brute force possible)"
fi

# ============================================================================
# 9. MASS ASSIGNMENT (A04:2021)
# ============================================================================
log_section "9. Mass Assignment"

MASS_ASSIGN=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s "$API_URL/api/me/update" -X POST \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"is_admin":true,"role":"super_admin","opd_id":1}')

if echo "$MASS_ASSIGN" | grep -qi "is_admin.*true\|role.*super_admin"; then
  log_fail "Mass assignment: is_admin/role accepted in profile update"
else
  log_pass "Mass assignment blocked (is_admin/role not assignable)"
fi

# ============================================================================
# 10. CORS MISCONFIGURATION
# ============================================================================
log_section "10. CORS Misconfiguration"

# Test with evil origin
EVIL_CORS=$(curl --retry 10 --retry-delay 1 --retry-all-errors -sI -X OPTIONS "$API_URL/api/me" \
  -H "Origin: https://evil-hacker.com" \
  -H "Access-Control-Request-Method: GET" 2>&1)

EVIL_ACAO=$(echo "$EVIL_CORS" | grep -i "Access-Control-Allow-Origin:" | tr -d '\r')
if echo "$EVIL_ACAO" | grep -qi "evil-hacker\|\*"; then
  log_fail "CORS allows arbitrary origins: $EVIL_ACAO"
else
  log_pass "CORS rejects evil origin (no wildcard)"
fi

# Test null origin
NULL_CORS=$(curl --retry 10 --retry-delay 1 --retry-all-errors -sI -X OPTIONS "$API_URL/api/me" \
  -H "Origin: null" \
  -H "Access-Control-Request-Method: GET" 2>&1)

NULL_ACAO=$(echo "$NULL_CORS" | grep -i "Access-Control-Allow-Origin:" | tr -d '\r')
if echo "$NULL_ACAO" | grep -qi "null"; then
  log_fail "CORS allows null origin"
else
  log_pass "CORS rejects null origin"
fi

# ============================================================================
# 11. HTTP METHOD TAMPERING
# ============================================================================
log_section "11. HTTP Method Tampering"

# PUT on login
PUT_LOGIN=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s -o /dev/null -w "%{http_code}" "$API_URL/api/citizen/login" \
  -X PUT \
  -H "Content-Type: application/json" \
  -d '{"nik":"test","password":"test"}')
if [ "$PUT_LOGIN" = "405" ] || [ "$PUT_LOGIN" = "404" ]; then
  log_pass "PUT on login endpoint rejected ($PUT_LOGIN)"
else
  log_warn "PUT on login returns $PUT_LOGIN (expected 405)"
fi

# DELETE on user data
DELETE_USER=$(curl --retry 10 --retry-delay 1 --retry-all-errors -s -o /dev/null -w "%{http_code}" "$API_URL/api/me" \
  -X DELETE \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")
if [ "$DELETE_USER" = "405" ] || [ "$DELETE_USER" = "404" ]; then
  log_pass "DELETE on /api/me rejected ($DELETE_USER)"
else
  log_warn "DELETE on /api/me returns $DELETE_USER (review if destructive)"
fi

# ============================================================================
# 12. VERBOSE ERROR HANDLING
# ============================================================================
log_section "12. Verbose Error / Stack Trace Leakage"

# Trigger error with malformed JSON
MALFORMED=$(curl -s "$API_URL/api/citizen/login" -X POST \
  -H "Content-Type: application/json" \
  -d '{invalid json}')

if echo "$MALFORMED" | grep -qiE "stack trace|vendor/|app/Http|Exception|\.php:"; then
  log_fail "Stack trace leaked in error response"
else
  log_pass "No stack trace leakage on malformed JSON"
fi

# Trigger error with extremely long input
LONG_INPUT=$(python3 -c "print('A' * 10000)" 2>/dev/null || printf 'A%.0s' {1..10000})
OVERFLOW=$(curl -s -o /dev/null -w "%{http_code}" "$API_URL/api/citizen/login" -X POST \
  -H "Content-Type: application/json" \
  -d "{\"nik\":\"$LONG_INPUT\",\"password\":\"test\"}")
if [ "$OVERFLOW" != "500" ]; then
  log_pass "Long input handled gracefully (status: $OVERFLOW)"
else
  log_fail "Long input causes 500 error (buffer overflow risk)"
fi

# ============================================================================
# SUMMARY
# ============================================================================
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}  🔒 PENETRATION TEST SUMMARY${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "  ${GREEN}SECURE${NC}:   $PASS"
echo -e "  ${RED}VULN${NC}:     $FAIL"
echo -e "  ${YELLOW}WARN${NC}:     $WARN"
echo -e "  ${MAGENTA}CRITICAL${NC}: $CRITICAL"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

TOTAL=$((PASS + FAIL + WARN + CRITICAL))
if [ "$CRITICAL" -gt 0 ]; then
  echo -e "\n${MAGENTA}🚨 $CRITICAL CRITICAL VULNERABILITY FOUND — IMMEDIATE ACTION REQUIRED${NC}"
  RESULTS+="\n## Summary\n\n🚨 **$CRITICAL CRITICAL VULNERABILITIES FOUND** — Immediate action required.\n\n"
elif [ "$FAIL" -gt 0 ]; then
  echo -e "\n${RED}⚠️  $FAIL VULNERABILITY/IES FOUND — Action recommended${NC}"
  RESULTS+="\n## Summary\n\n⚠️ **$FAIL VULNERABILITIES FOUND** — Action recommended.\n\n"
else
  echo -e "\n${GREEN}🛡️  NO VULNERABILITIES FOUND — Security posture is strong${NC}"
  RESULTS+="\n## Summary\n\n🛡️ **NO VULNERABILITIES FOUND** — Security posture is strong.\n\n"
fi

RESULTS+="- **Secure**: $PASS\n- **Vulnerabilities**: $FAIL\n- **Warnings**: $WARN\n- **Critical**: $CRITICAL\n- **Total Tests**: $TOTAL\n"

mkdir -p results
echo -e "$RESULTS" > "$RESULTS_FILE"
echo -e "\nResults saved to: $RESULTS_FILE"
