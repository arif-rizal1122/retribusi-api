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

# 2. PRESERVE POS
POS_TARGET=""
if [ -L /etc/nginx/sites-enabled/posmpad ]; then
    POS_TARGET=$(readlink -f /etc/nginx/sites-enabled/posmpad)
fi

# 3. WIPE STATIC OVERRIDES
sudo rm -rf /var/www/html/*
sudo rm -rf /usr/share/nginx/html/*
sudo rm -rf /etc/nginx/sites-enabled/*
sudo rm -rf /etc/nginx/conf.d/*

# 4. RESTORE POS
[ ! -z "$POS_TARGET" ] && sudo ln -sf "$POS_TARGET" /etc/nginx/sites-enabled/posmpad

# 5. DETECT ROOTS
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

# 6. SSL ISSUANCE (CRITICAL FOR CONNECTION REFUSED)
issue_ssl() {
    local domain=$1
    if [ ! -f "/etc/letsencrypt/live/$domain/fullchain.pem" ]; then
        echo "Attempting to issue SSL for $domain..."
        sudo certbot certonly --nginx -d "$domain" --non-interactive --agree-tos -m admin@sipanda.online || echo "SSL issuance failed for $domain"
    fi
}

issue_ssl "$API_DOMAIN"
issue_ssl "$ADMIN_DOMAIN"
issue_ssl "$PETUGAS_DOMAIN"

# 7. GENERATE CONFIG
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
        echo "    return 301 https://\$host\$request_uri;"
        echo "}"
        echo "server {"
        echo "    listen 443 ssl;"
        echo "    server_name $domain;"
        echo "    root $root;"
        echo "    ssl_certificate $cert;"
        echo "    ssl_certificate_key $key;"
    else
        echo "    root $root;"
    fi

    echo "    index index.php index.html;"
    echo "    location / {"
    echo "        try_files \$uri \$uri/ /index.php?\$query_string;"
    if [ "$is_php" != "true" ]; then
        echo "        # Static override for react/vite"
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

CONF="/etc/nginx/sites-available/mpad-restoration.conf"
{
    generate_block "$API_DOMAIN" "$API_PATH" "true"
    generate_block "$ADMIN_DOMAIN" "$ADMIN_PATH" "false"
    generate_block "$PETUGAS_DOMAIN" "$PETUGAS_PATH" "false"
} | sudo tee $CONF > /dev/null

sudo ln -sf $CONF /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl restart nginx

echo "--- PARTITIONED RECOVERY COMPLETED ---"
