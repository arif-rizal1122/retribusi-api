#!/bin/bash

# mPaD Payment Gateway H2H Omni-Test
# Purpose: Comprehensive automated testing of H2H Bank Integrations
# Compliance: /noss Standard (No Screenshots)

# Configuration
API_URL="${1:-http://localhost:8000/api/v1/bank}"
SECRET="secret_sultra_2026"
BILL_NUMBER="PBJT-202602-37-1"
AMOUNT="1704000"
TIMESTAMP=$(date +%s)
NTB="TEST-H2H-$(date +%s)"

echo "🚀 Starting mPaD Payment Gateway Omni-Test..."
echo "📍 Target: ${API_URL}"
echo "------------------------------------------------"

# Helper function for HMAC calculation
calc_signature() {
    local data="$1"
    echo -n "$data" | openssl dgst -sha256 -hmac "$SECRET" | sed 's/^.*= //'
}

# 1. SECURITY TEST: Missing Headers
echo -n "🔒 1. Testing Security (Missing Headers)... "
RES=$(curl -s -X POST "${API_URL}/inquiry" \
    -H "Content-Type: application/json" \
    -d "{\"bill_number\": \"${BILL_NUMBER}\"}")
if echo "$RES" | grep -q "Missing security headers"; then
    echo "✅ PASSED (401 Rejected correctly)"
else
    echo "❌ FAILED"
    echo "Response: $RES"
fi

# 2. SECURITY TEST: Invalid Signature
echo -n "🔒 2. Testing Security (Invalid Signature)... "
RES=$(curl -s -X POST "${API_URL}/inquiry" \
    -H "Content-Type: application/json" \
    -H "X-Timestamp: ${TIMESTAMP}" \
    -H "X-Signature: wrong_signature" \
    -d "{\"bill_number\": \"${BILL_NUMBER}\"}")
if echo "$RES" | grep -q "Invalid signature"; then
    echo "✅ PASSED (401 Rejected correctly)"
else
    echo "❌ FAILED"
    echo "Response: $RES"
fi

# 3. FUNCTIONAL TEST: Inquiry
echo -n "🔍 3. Testing Inquiry (Positive)... "
SIG=$(calc_signature "${BILL_NUMBER}${TIMESTAMP}0") # Amount 0 for inquiry usually
RES=$(curl -s -X POST "${API_URL}/inquiry" \
    -H "Content-Type: application/json" \
    -H "X-Timestamp: ${TIMESTAMP}" \
    -H "X-Signature: ${SIG}" \
    -d "{\"bill_number\": \"${BILL_NUMBER}\"}")
if echo "$RES" | grep -q "\"status\":\"success\""; then
    echo "✅ PASSED"
    # Extract total amount if needed (JIT Precision Check)
else
    echo "❌ FAILED"
    echo "Response: $RES"
fi

# 4. FUNCTIONAL TEST: Payment Notification
echo -n "💰 4. Testing Payment Notification... "
# Recalculate signature for payment: bill_number + timestamp + amount
SIG_PAY=$(calc_signature "${BILL_NUMBER}${TIMESTAMP}${AMOUNT}")
RES=$(curl -s -X POST "${API_URL}/payment" \
    -H "Content-Type: application/json" \
    -H "X-Timestamp: ${TIMESTAMP}" \
    -H "X-Signature: ${SIG_PAY}" \
    -d "{\"bill_number\": \"${BILL_NUMBER}\", \"amount_paid\": ${AMOUNT}, \"transaction_id\": \"${NTB}\", \"channel\": \"TELLER\"}")

if echo "$RES" | grep -q "\"status\":\"success\""; then
    echo "✅ PASSED (Payment Recorded)"
else
    echo "❌ FAILED"
    echo "Response: $RES"
fi

# 5. DATA INTEGRITY: Duplicate Payment (NTB Idempotency)
echo -n "🛡️ 5. Testing Duplicate NTB Prevention... "
RES=$(curl -s -X POST "${API_URL}/payment" \
    -H "Content-Type: application/json" \
    -H "X-Timestamp: ${TIMESTAMP}" \
    -H "X-Signature: ${SIG_PAY}" \
    -d "{\"bill_number\": \"${BILL_NUMBER}\", \"amount_paid\": ${AMOUNT}, \"transaction_id\": \"${NTB}\"}")

if echo "$RES" | grep -q "Idempotent Success"; then
    echo "✅ PASSED (Idempotent Success Detected)"
else
    echo "❌ FAILED"
    echo "Response: $RES"
fi

# 6. FUNCTIONAL TEST: Reversal
echo -n "↩️ 6. Testing Reversal... "
# Reversal signature: bill_number + timestamp + 0 (since no amount in request)
SIG_REV=$(calc_signature "${BILL_NUMBER}${TIMESTAMP}0")
RES=$(curl -s -X POST "${API_URL}/reversal" \
    -H "Content-Type: application/json" \
    -H "X-Timestamp: ${TIMESTAMP}" \
    -H "X-Signature: ${SIG_REV}" \
    -d "{\"bill_number\": \"${BILL_NUMBER}\", \"ntb\": \"${NTB}\"}")

if echo "$RES" | grep -q "\"status\":\"success\""; then
    echo "✅ PASSED (Reversal Success)"
else
    echo "❌ FAILED"
    echo "Response: $RES"
fi

echo "------------------------------------------------"
echo "🏁 Omni-Test Completed!"
