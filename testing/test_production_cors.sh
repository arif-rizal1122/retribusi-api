#!/bin/bash
API_URL="https://api.sipanda.online/api/me"
ORIGIN="https://sipanda.online"

echo "Testing CORS preflight for $API_URL from $ORIGIN..."
curl -v -X OPTIONS "$API_URL" \
  -H "Origin: $ORIGIN" \
  -H "Access-Control-Request-Method: GET" \
  -H "Access-Control-Request-Headers: Content-Type, Authorization" 2>&1 | grep -E "< HTTP/|< Access-Control-Allow-"

echo -e "\nTesting actual GET request..."
curl -v -X GET "$API_URL" \
  -H "Origin: $ORIGIN" 2>&1 | grep -E "< HTTP/|< Access-Control-Allow-"
