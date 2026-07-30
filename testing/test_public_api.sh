#!/bin/bash
# Test all public API endpoints for M-PAD production

BASE="https://apimpad.baubaukota.go.id"
RESULTS_FILE="/home/pondokinformatika/public_api_results.txt"

echo "============================================" > "$RESULTS_FILE"
echo " M-PAD PUBLIC API TEST RESULTS" >> "$RESULTS_FILE"
echo " $(date)" >> "$RESULTS_FILE"
echo "============================================" >> "$RESULTS_FILE"
echo "" >> "$RESULTS_FILE"

test_endpoint() {
    local method="$1"
    local url="$2"
    local data="$3"
    local label="$4"
    
    echo "--- Test #$counter: $label ---" >> "$RESULTS_FILE"
    echo "URL: $method $url" >> "$RESULTS_FILE"
    
    local tempfile=$(mktemp)
    local http_code
    
    if [ "$method" = "GET" ]; then
        http_code=$(curl -sk -o "$tempfile" -w "%{http_code}" --max-time 15 "$url" 2>/dev/null)
    elif [ "$method" = "POST" ]; then
        http_code=$(curl -sk -o "$tempfile" -w "%{http_code}" --max-time 15 \
            -H "Content-Type: application/json" \
            -d "$data" \
            "$url" 2>/dev/null)
    fi
    
    local curl_exit=$?
    local body=$(cat "$tempfile" | head -c 200)
    rm -f "$tempfile"
    
    echo "HTTP Status: $http_code" >> "$RESULTS_FILE"
    echo "Curl Exit: $curl_exit" >> "$RESULTS_FILE"
    
    if [ "$http_code" = "000" ] || [ "$curl_exit" -ne 0 ]; then
        echo "Status: ERROR - Connection/Timeout/SSL failure" >> "$RESULTS_FILE"
    elif [ "$http_code" -ge 200 ] && [ "$http_code" -lt 300 ]; then
        echo "Status: SUCCESS (2xx)" >> "$RESULTS_FILE"
    elif [ "$http_code" -ge 300 ] && [ "$http_code" -lt 400 ]; then
        echo "Status: REDIRECT (3xx)" >> "$RESULTS_FILE"
    elif [ "$http_code" -ge 400 ] && [ "$http_code" -lt 500 ]; then
        echo "Status: CLIENT ERROR (4xx)" >> "$RESULTS_FILE"
    elif [ "$http_code" -ge 500 ]; then
        echo "Status: SERVER ERROR (5xx)" >> "$RESULTS_FILE"
    fi
    
    echo "Response (first 200 chars): $body" >> "$RESULTS_FILE"
    echo "" >> "$RESULTS_FILE"
}

counter=1

# 1
test_endpoint "GET" "$BASE/api/public/opds" "" "GET /api/public/opds"
counter=$((counter+1))

# 2
test_endpoint "GET" "$BASE/api/public/retribution-classifications" "" "GET /api/public/retribution-classifications"
counter=$((counter+1))

# 3
test_endpoint "GET" "$BASE/api/public/retribution-types" "" "GET /api/public/retribution-types"
counter=$((counter+1))

# 4
test_endpoint "POST" "$BASE/api/public/register-taxpayer" '{"name":"Test User","email":"test@test.com","phone":"08123456789"}' "POST /api/public/register-taxpayer"
counter=$((counter+1))

# 5
test_endpoint "GET" "$BASE/api/public/taxpayers/check-nik/1234567890123456" "" "GET /api/public/taxpayers/check-nik/1234567890123456"
counter=$((counter+1))

# 6
test_endpoint "GET" "$BASE/api/public/pdf/npwpd/1" "" "GET /api/public/pdf/npwpd/1"
counter=$((counter+1))

# 7
test_endpoint "GET" "$BASE/api/public/pdf/skpd/1" "" "GET /api/public/pdf/skpd/1"
counter=$((counter+1))

# 8
test_endpoint "GET" "$BASE/api/public/pdf/skrd/1" "" "GET /api/public/pdf/skrd/1"
counter=$((counter+1))

# 9
test_endpoint "GET" "$BASE/api/public/pdf/sppt/1" "" "GET /api/public/pdf/sppt/1"
counter=$((counter+1))

# 10
test_endpoint "GET" "$BASE/api/public/pdf/sspd/1" "" "GET /api/public/pdf/sspd/1"
counter=$((counter+1))

# 11
test_endpoint "GET" "$BASE/api/public/pdf/surat-teguran/1" "" "GET /api/public/pdf/surat-teguran/1"
counter=$((counter+1))

# 12
test_endpoint "GET" "$BASE/api/verify/bill/123" "" "GET /api/verify/bill/123"
counter=$((counter+1))

# 13
test_endpoint "GET" "$BASE/api/verify/payment/123" "" "GET /api/verify/payment/123"
counter=$((counter+1))

# 14
test_endpoint "GET" "$BASE/api/pbb/classifications" "" "GET /api/pbb/classifications"
counter=$((counter+1))

# 15
test_endpoint "POST" "$BASE/api/pbb/calculate" '{"op_luas_tanah":100,"op_luas_bangunan":50,"op_njop_tanah":500000,"op_njop_bangunan":750000}' "POST /api/pbb/calculate"
counter=$((counter+1))

# 16
test_endpoint "POST" "$BASE/api/simulate-tax" '{"amount":1000000,"category":"restoran"}' "POST /api/simulate-tax"
counter=$((counter+1))

# 17
test_endpoint "GET" "$BASE/api/dashboard/stats" "" "GET /api/dashboard/stats"
counter=$((counter+1))

# 18
test_endpoint "GET" "$BASE/api/analytics/heatmap" "" "GET /api/analytics/heatmap"
counter=$((counter+1))

# 19
test_endpoint "GET" "$BASE/" "" "GET / (root)"
counter=$((counter+1))

# 20
test_endpoint "POST" "$BASE/api/login" '{"email":"admin.main@sipanda.online","password":"Bapenda2026!"}' "POST /api/login"
counter=$((counter+1))

# 21
test_endpoint "POST" "$BASE/api/citizen/login" '{"nik":"1234567890123456","password":"password"}' "POST /api/citizen/login"

echo "" >> "$RESULTS_FILE"
echo "============================================" >> "$RESULTS_FILE"
echo " ALL TESTS COMPLETE" >> "$RESULTS_FILE"
echo "============================================" >> "$RESULTS_FILE"

cat "$RESULTS_FILE"
