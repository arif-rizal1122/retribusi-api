#!/bin/bash
# High-Fidelity Nginx Recovery & Subdomain Partitioning
# ROBUST VERSION: Correct domains and auto-cert discovery.

echo "--- STARTING DOMAIN-CORRECTED NGINX RECOVERY ---"

# 1. DEFINE PATHS & DOMAINS
API_DOMAIN="api.sipanda.online"
ADMIN_DOMAIN="adminmpad.baubaukota.go.id"
PETUGAS_DOMAIN="petugasmpad.baubaukota.go.id"

API_ROOT="/home/sipanda/retribusi-api"
ADMIN_ROOT="/home/sipanda/retribusi-admin"
PETUGAS_ROOT="/home/sipanda/retribusi-petugas"

# 2. DETECT ROOTS
get_root() {
    local base=$1
    [ -d "$base/dist" ] && echo "$base/dist" && return
    [ -d "$base/build" ] && echo "$base/build" && return
    [ -d "$base/public" ] && echo "$base/public" && return
    echo "$base"
}

API_PATH=$(get_root $API_ROOT)
ADMIN_PATH=$(get_root $ADMIN_ROOT)
PETUGAS_PATH=$(get_root $PETUGAS_ROOT)

# 3. GENERATE CONFIG (FIRST, TO ENSURE NO WIPE-FAILURE)
generate_block() {
    local domain=$1
    local root=$2
    local is_php=$3
    local cert="/etc/letsencrypt/live/$domain/fullchain.pem"
    local key="/etc/letsencrypt/live/$domain/privkey.pem"

    echo "server {"
    echo "    listen 80;"
    echo "    server_name $domain;"
    
    if [ -f "$cert" ]; then
        echo "    listen 443 ssl;"
        echo "    ssl_certificate $cert;"
        echo "    ssl_certificate_key $key;"
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

CONF_TMP="/tmp/mpad-fail-safe.conf"
{
    generate_block "$API_DOMAIN" "$API_PATH" "true"
    generate_block "$ADMIN_DOMAIN" "$ADMIN_PATH" "false"
    generate_block "$PETUGAS_DOMAIN" "$PETUGAS_PATH" "false"
} > $CONF_TMP

# 4. ATOMIC SWAP
echo "Atomic Swapping Nginx Config..."
sudo rm -rf /etc/nginx/sites-enabled/*
sudo cp $CONF_TMP /etc/nginx/sites-available/mpad-production.conf
sudo ln -sf /etc/nginx/sites-available/mpad-production.conf /etc/nginx/sites-enabled/

# 5. RESTART
sudo nginx -t && sudo systemctl restart nginx || echo "Nginx Restart Failed - Check Logs"

echo "--- PARTITIONED RECOVERY COMPLETED ---"
