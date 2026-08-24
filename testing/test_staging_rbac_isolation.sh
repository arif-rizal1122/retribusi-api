#!/bin/bash
# test_staging_rbac_isolation.sh - Verify RBAC and data isolation on staging

GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

API_URL="https://apimpad.baubaukota.go.id/api"

echo "Starting RBAC isolation testing on $API_URL..."

# 1. Test Admin Login and unauthorized access to other roles
ADMIN_RESP=$(curl -s -X POST "$API_URL/login" -H "Content-Type: application/json" -d '{"email":"bapenda@baubaukota.go.id", "password":"password123"}')
ADMIN_TOKEN=$(echo "$ADMIN_RESP" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -n "$ADMIN_TOKEN" ]; then
    echo -e "${GREEN}✅ Admin Login Successful${NC}"
else
    echo -e "${RED}❌ Admin Login Failed${NC}"
    exit 1
fi

# 2. Test Citizen Login
CITIZEN_RESP=$(curl -s -X POST "$API_URL/citizen/login" -H "Content-Type: application/json" -d '{"nik":"0000000000000001", "password":"password123"}')
CITIZEN_TOKEN=$(echo "$CITIZEN_RESP" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -n "$CITIZEN_TOKEN" ]; then
    echo -e "${GREEN}✅ Citizen Login Successful${NC}"
else
    echo -e "${RED}❌ Citizen Login Failed${NC}"
fi

# 3. RBAC Check: Citizen trying to access Admin list-users
RBAC_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$API_URL/users" -H "Authorization: Bearer $CITIZEN_TOKEN")
if [ "$RBAC_CODE" == "403" ]; then
    echo -e "${GREEN}✅ RBAC: Citizen access to /users correctly forbidden (403)${NC}"
else
    echo -e "${RED}❌ RBAC: Citizen access to /users returned $RBAC_CODE (expected 403)${NC}"
fi
