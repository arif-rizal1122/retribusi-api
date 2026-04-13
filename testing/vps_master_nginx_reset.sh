#!/bin/bash
# Master Nginx Purge & Reset Script (Surgical Version)
# This script eliminates conflicting server blocks for M-PAD domains

echo "--- STARTING SURGICAL NGINX PURGE ---"

# 1. DEFINE STANDARDS
PROD_DOMAIN="apimpad.baubaukota.go.id"
STAGING_DOMAIN="api.mpad.online"
PROD_ROOT="/home/sipanda/retribusi-api/public"
STAGING_ROOT="/home/sipanda/retribusi-api-staging/public"

# 3. NUCLEAR RESET OF CATCH-ALLS
echo "Nuclear Reset: Checking for default_server and catch-alls..."
# Find files with default_server or no server_name
local catchalls=$(grep -rE "default_server|server_name _" /etc/nginx/sites-enabled/ -l)
for f in $catchalls; do
    echo "Disabling catch-all: $f"
    sudo rm -f "$f"
done

# 4. SURGICAL PURGE OF CONFLICTS (EXCLUDING POS)
purge_conflicts() {
    local domain=$1
    echo "Scanning sites-enabled for conflicts with $domain..."
    local files=$(grep -r "$domain" /etc/nginx/sites-enabled/ -l)
    for f in $files; do
        if [[ "$domain" == *"apimpad"* ]] || [[ "$domain" == *"adminmpad"* ]] || [[ "$domain" == *"petugasmpad"* ]]; then
            echo "Removing conflicting config: $f"
            sudo rm -f "$f"
        fi
    done
}

purge_conflicts "$PROD_DOMAIN"
purge_conflicts "adminmpad.baubaukota.go.id"
purge_conflicts "petugasmpad.baubaukota.go.id"
purge_conflicts "$STAGING_DOMAIN"

# 5. RE-ESTABLISH CORRECT CONFIGS
fix_conf() {
    local domain=$1
    local target_root=$2
    local conf_name="retribusi-api-prod.conf"
    [ "$domain" == "$STAGING_DOMAIN" ] && conf_name="retribusi-api-staging.conf"
    
    local conf_path="/etc/nginx/sites-available/$conf_name"
    
    # Force creation of a fresh, guaranteed config if missing or corrupted
    if [ ! -f "$conf_path" ] || [ "$domain" == "$PROD_DOMAIN" ]; then
        echo "Creating fresh config for $domain"
        sudo bash -c "cat > $conf_path <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $domain;
    root $target_root;

    add_header X-Frame-Options \"SAMEORIGIN\";
    add_header X-XSS-Protection \"1; mode=block\";
    add_header X-Content-Type-Options \"nosniff\";

    index index.php;
    charset utf-8;

    location / {
        try_files \\\$uri \\\$uri/ /index.php?\\\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \\\$realpath_root\\\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF"
    fi
    
    sudo ln -sf "$conf_path" "/etc/nginx/sites-enabled/$conf_name"
}

fix_conf "$PROD_DOMAIN" "$PROD_ROOT"
fix_conf "$STAGING_DOMAIN" "$STAGING_ROOT"

# 6. FINAL PERMISSIONS & RELOAD
sudo chown -R www-data:www-data "$PROD_ROOT/.."
sudo chown -R www-data:www-data "$STAGING_ROOT/.."
echo "Current sites-enabled:"
ls -la /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

echo "--- SURGICAL RESET COMPLETED ---"
