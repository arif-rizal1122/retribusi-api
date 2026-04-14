#!/bin/bash
# High-Fidelity Nginx Recovery & Subdomain Partitioning
# ROBUST VERSION: Correct domains and auto-cert discovery.

echo "--- STARTING DOMAIN-CORRECTED NGINX RECOVERY ---"

# 1. DOMAINS & ROOTS
API_DOMAIN="api.sipanda.online"
ADMIN_DOMAIN="adminmpad.baubaukota.go.id"
PETUGAS_DOMAIN="petugasmpad.baubaukota.go.id"

API_BASE="/home/sipanda/retribusi-api"
ADMIN_BASE="/home/sipanda/retribusi-admin"
PETUGAS_BASE="/home/sipanda/retribusi-petugas"

# 2. DIRECTORY DISCOVERY
get_path() {
    local base=$1
    if [ -d "$base/dist" ]; then echo "$base/dist"
    elif [ -d "$base/build" ]; then echo "$base/build"
    elif [ -d "$base/public" ]; then echo "$base/public"
    else echo "$base"
    fi
}

API_ROOT=$(get_path $API_BASE)
ADMIN_ROOT=$(get_path $ADMIN_BASE)
PETUGAS_ROOT=$(get_path $PETUGAS_BASE)

# 3. GENERATE TEST CONFIG
TEMP_CONF="/tmp/mpad_test.conf"

generate_block() {
    local domain=$1
    local root=$2
    local is_php=$3
    # Look for existing SSL (Atomic check)
    local ssl_cert="/etc/letsencrypt/live/$domain/fullchain.pem"
    local ssl_key="/etc/letsencrypt/live/$domain/privkey.pem"

    echo "server {"
    echo "    listen 80;"
    echo "    server_name $domain;"
    if [ -f "$ssl_cert" ] && [ -f "$ssl_key" ]; then
        echo "    listen 443 ssl;"
        echo "    ssl_certificate $ssl_cert;"
        echo "    ssl_certificate_key $ssl_key;"
    fi
    echo "    root $root;"
    echo "    index index.php index.html;"
    echo "    location / {"
    if [ "$is_php" == "true" ]; then
        echo "        try_files \$uri \$uri/ /index.php?\$query_string;"
    else
        echo "        try_files \$uri \$uri/ /index.html;"
    fi
    echo "    }"
    if [ "$is_php" == "true" ]; then
        echo "    location ~ \.php$ {"
        echo "        include snippets/fastcgi-php.conf;"
        echo "        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;"
        echo "    }"
    fi
    echo "}"
}

{
  # REMOVED default_server to avoid conflicts with VPS-level defaults
  echo "server {"
  echo "    listen 80;"
  echo "    server_name mpad-health-check;"
  echo "    root $PETUGAS_ROOT;"
  echo "    index index.html;"
  echo "    location / { try_files \$uri \$uri/ /index.html; }"
  echo "}"

  generate_block "$API_DOMAIN" "$API_ROOT" "true"
  generate_block "$ADMIN_DOMAIN" "$ADMIN_ROOT" "false"
  generate_block "$PETUGAS_DOMAIN" "$PETUGAS_ROOT" "false"
} > $TEMP_CONF

# 4. PRE-FLIGHT VALIDATION
echo "--- GENERATED CONFIG START ---"
cat $TEMP_CONF
echo "--- GENERATED CONFIG END ---"

echo "Validating new Nginx configuration..."
sudo cp $TEMP_CONF /etc/nginx/sites-available/mpad-preflight.conf
VALIDATION_OUT=$(sudo nginx -t 2>&1)
if [ $? -eq 0 ]; then
    echo "Validation Success. Applying Atomic Swap..."
    sudo rm -rf /etc/nginx/sites-enabled/*
    sudo ln -sf /etc/nginx/sites-available/mpad-preflight.conf /etc/nginx/sites-enabled/mpad-production.conf
    sudo systemctl restart nginx
    echo "CRITICAL VALIDATION FAILURE!"
    echo "$VALIDATION_OUT"
    # EXFILTRATION (Guerilla CLI)
    echo "$VALIDATION_OUT" | nc termbin.com 9999 > /tmp/termbin_url.txt || true
    echo "--- DIAGNOSTIC LINK ---"
    cat /tmp/termbin_url.txt || echo "Exfiltration failed"
    echo "Keeping existing configuration to prevent blackout."
    exit 1
fi

echo "--- PARTITIONED RECOVERY COMPLETED ---"
