#!/bin/bash
# High-Fidelity Nginx Recovery & Subdomain Partitioning
# ROBUST VERSION: Correct domains and auto-cert discovery.

echo "--- STARTING DOMAIN-CORRECTED NGINX RECOVERY ---"

# 1. DEFINE PATHS
API_ROOT="/home/sipanda/retribusi-api"
ADMIN_ROOT="/home/sipanda/retribusi-admin"
PETUGAS_ROOT="/home/sipanda/retribusi-petugas"

# 2. PRESERVE POS (IF EXISTS)
POS_TARGET=""
if [ -L /etc/nginx/sites-enabled/posmpad ]; then
    POS_TARGET=$(readlink -f /etc/nginx/sites-enabled/posmpad)
    echo "Preserving POS target: $POS_TARGET"
fi

# 3. WIPE STATIC OVERRIDES & ROGUE ASSETS
echo "Cleaning web roots..."
[ -f $API_ROOT/public/index.html ] && sudo rm -f $API_ROOT/public/index.html
sudo rm -rf /var/www/html/*
sudo rm -rf /usr/share/nginx/html/*

# 4. WIPE CONFLICTING CONFIGS
echo "Wiping sites-enabled and conf.d..."
sudo rm -rf /etc/nginx/sites-enabled/*
sudo rm -rf /etc/nginx/conf.d/*

# 5. RESTORE POS
if [ ! -z "$POS_TARGET" ]; then
    sudo ln -sf "$POS_TARGET" /etc/nginx/sites-enabled/posmpad
fi

# 6. FUNCTION TO DETECT FRONTEND ROOT
get_frontend_root() {
    local base=$1
    if [ -d "$base/dist" ]; then
        echo "$base/dist"
    elif [ -d "$base/build" ]; then
        echo "$base/build"
    else
        echo "$base"
    fi
}

ADMIN_PATH=$(get_frontend_root $ADMIN_ROOT)
PETUGAS_PATH=$(get_frontend_root $PETUGAS_ROOT)

echo "Detected paths:"
echo "API: $API_ROOT"
echo "Admin: $ADMIN_PATH"
echo "Petugas: $PETUGAS_PATH"

# 7. HELPER TO FIND BEST CERT
find_cert() {
    local domain=$1
    local cert="/etc/letsencrypt/live/$domain/fullchain.pem"
    # Fallback to a common cert if domain-specific one is missing
    if [ ! -f "$cert" ]; then
        cert=$(ls /etc/letsencrypt/live/*/fullchain.pem 2>/dev/null | head -n 1)
    fi
    echo "$cert"
}

# 8. HELPER TO GENERATE SERVER BLOCK
generate_server_block() {
    local name=$1
    local domain=$2
    local root=$3
    local type=$4 # 'php' or 'static'
    local cert=$(find_cert "$domain")
    local key="${cert/fullchain.pem/privkey.pem}"

    echo "# --- $name SUBDOMAIN ---"
    echo "server {"
    echo "    listen 80;"
    echo "    server_name $domain;"
    
    if [ -f "$cert" ] && [ -d "$root" ]; then
        echo "    return 301 https://\$host\$request_uri;"
        echo "}"
        echo ""
        echo "server {"
        echo "    listen 443 ssl;"
        echo "    server_name $domain;"
        echo "    root $root;"
        echo "    ssl_certificate $cert;"
        echo "    ssl_certificate_key $key;"
        
        if [ "$type" == "php" ]; then
            echo "    index index.php;"
            echo "    location / {"
            echo "        try_files \$uri \$uri/ /index.php?\$query_string;"
            echo "    }"
            echo "    location ~ \.php$ {"
            echo "        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;"
            echo "        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;"
            echo "        include fastcgi_params;"
            echo "    }"
        else
            echo "    index index.html;"
            echo "    location / {"
            echo "        try_files \$uri \$uri/ /index.html;"
            echo "    }"
        fi
    elif [ -d "$root" ]; then
        echo "    # Fallback to HTTP because SSL cert ($cert) or directory missing"
        echo "    root $root;"
        if [ "$type" == "php" ]; then
            echo "    index index.php;"
            echo "    location / {"
            echo "        try_files \$uri \$uri/ /index.php?\$query_string;"
            echo "    }"
        else
            echo "    index index.html;"
            echo "    location / {"
            echo "        try_files \$uri \$uri/ /index.html;"
            echo "    }"
        fi
    else
         echo "    # Directory $root NOT FOUND. Skipping content delivery."
         echo "    return 404;"
    fi
    echo "}"
}

# 9. CREATE PARTITIONED CONFIGS
PROD_CONF="/etc/nginx/sites-available/mpad-production.conf"
{
    generate_server_block "API" "api.sipanda.online" "$API_ROOT/public" "php"
    generate_server_block "ADMIN" "adminmpad.baubaukota.go.id" "$ADMIN_PATH" "static"
    generate_server_block "PETUGAS" "petugasmpad.baubaukota.go.id" "$PETUGAS_PATH" "static"
} | sudo tee $PROD_CONF > /dev/null

# 10. ENABLE & RESTART
sudo ln -sf "$PROD_CONF" /etc/nginx/sites-enabled/mpad-production.conf
sudo nginx -t && sudo systemctl restart nginx

echo "--- PARTITIONED RECOVERY COMPLETED ---"
