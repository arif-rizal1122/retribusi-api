#!/bin/bash
# test_staging_infra.sh - Health check for sipanda.online subdomains

GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

SUBDOMAINS=("api.mpad.online" "admin.mpad.online" "petugas.mpad.online" "mpad.online" "pos.mpad.online")

echo "Starting Staging Infrastructure Health Check..."

for domain in "${SUBDOMAINS[@]}"; do
    CODE=$(curl -s -o /dev/null -w "%{http_code}" "https://$domain")
    if [ "$CODE" == "200" ]; then
        echo -e "${GREEN}✅ $domain -> $CODE${NC}"
    else
        echo -e "${RED}❌ $domain -> $CODE${NC}"
    fi
done

# Check API Health specifically
echo "Checking API Endpoints..."
API_OPDS=$(curl -s -o /dev/null -w "%{http_code}" "https://api.mpad.online/api/opds")
if [ "$API_OPDS" == "200" ]; then
    echo -e "${GREEN}✅ api.mpad.online/api/opds -> 200${NC}"
else
    echo -e "${RED}❌ api.mpad.online/api/opds -> $API_OPDS${NC}"
fi
