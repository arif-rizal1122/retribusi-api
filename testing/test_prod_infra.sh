#!/bin/bash
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

SUBDOMAINS=("api.sipanda.online" "mpad.baubaukota.go.id" "adminmpad.baubaukota.go.id" "petugasmpad.baubaukota.go.id")

echo "Starting Production Infrastructure Health Check..."

for domain in "${SUBDOMAINS[@]}"; do
    CODE=$(curl -s -o /dev/null -w "%{http_code}" "https://$domain")
    if [ "$CODE" == "200" ]; then
        echo -e "${GREEN}✅ $domain -> $CODE${NC}"
    else
        echo -e "${RED}❌ $domain -> $CODE${NC}"
    fi
done

# Check if api.mpad.baubaukota.go.id exists
API_GO_ID=$(curl -s -o /dev/null -w "%{http_code}" "https://api.mpad.baubaukota.go.id")
echo -e "Check api.mpad.baubaukota.go.id -> $API_GO_ID"
