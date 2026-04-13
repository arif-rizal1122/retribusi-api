#!/bin/bash
# test_staging_golden_path.sh - Complete E2E flow simulation on staging

GREEN='\033[0;32m'
NC='\033[0m'

API_URL="https://api.mpad.online/api"

echo "Starting Golden Path (E2E) on $API_URL..."

# Step 1: Login Admin
ADMIN_RESP=$(curl -s -X POST "$API_URL/login" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"email":"admin.main@sipanda.online", "password":"password123"}')
ADMIN_TOKEN=$(echo "$ADMIN_RESP" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
echo "Step 1: Admin Logged In."

# Step 2: Get a Taxpayer
TP_LIST=$(curl -s -H "Authorization: Bearer $ADMIN_TOKEN" "$API_URL/taxpayers?limit=1")
TP_ID=$(echo "$TP_LIST" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
echo "Step 2: Selected Taxpayer ID $TP_ID."

# Step 3: Create a temporary bill for testing
BILL_RESP=$(curl -s -X POST "$API_URL/bills" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"taxpayer_id\":$TP_ID, \"tax_object_id\":1, \"retribution_type_id\":7, \"amount\":100000, \"period\":\"Maret 2026\", \"due_date\":\"2026-04-11\"}")

BILL_ID=$(echo "$BILL_RESP" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
echo "Step 3: Created temporary Bill ID $BILL_ID."

# Step 4: Login Petugas
PETUGAS_RESP=$(curl -s -X POST "$API_URL/login" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"email":"petugas.main@sipanda.online", "password":"password123"}')
PETUGAS_TOKEN=$(echo "$PETUGAS_RESP" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
echo "Step 4: Petugas Logged In."

# Step 5: Pay the Bill
PAY_RESP=$(curl -s -X POST "$API_URL/bills/$BILL_ID/pay" \
  -H "Authorization: Bearer $PETUGAS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"amount":100000, "payment_method":"cash", "notes":"UAT Testing Golden Path"}')

echo "Step 5: Payment processed. Result code: $?"

# Final Check
FINAL_STATUS=$(curl -s -H "Authorization: Bearer $ADMIN_TOKEN" "$API_URL/bills/$BILL_ID" | grep -o '"status":"[^"]*"' | cut -d'"' -f4)
echo -e "Final Bill Status: ${GREEN}$FINAL_STATUS${NC}"
