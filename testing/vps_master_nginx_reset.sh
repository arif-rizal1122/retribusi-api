#!/bin/bash
# Master Nginx Purge & Reset Script (Surgical Version)
# This script eliminates conflicting server blocks for M-PAD domains

echo "--- STARTING SURGICAL NGINX PURGE ---"

# 1. DEFINE STANDARDS
PROD_DOMAIN="apimpad.baubaukota.go.id"
STAGING_DOMAIN="api.mpad.online"
PROD_ROOT="/home/sipanda/retribusi-api/public"
STAGING_ROOT="/home/sipanda/retribusi-api-staging/public"

# 3. NUCLEAR RESET OF CATCH-ALLS & SSL OVERRIDES
echo "Nuclear Reset: Checking for default_server, catch-alls, and SSL overrides..."
# Find all enabled sites
local sites=$(ls /etc/nginx/sites-enabled/)
for f in $sites; do
    local fpath="/etc/nginx/sites-enabled/$f"
    # We strictly AVOID touching anything related to posmpad
    if [[ "$f" == *"posmpad"* ]]; then
        echo "Preserving POS config: $f"
        continue
    fi
    
    # If it's a default server or contains SSL for the target domains but is not our controlled file
    if grep -qE "default_server|server_name _" "$fpath" || \
       (grep -qE "ssl|listen 443" "$fpath" && grep -qE "$PROD_DOMAIN|adminmpad|petugasmpad" "$fpath"); then
        if [[ "$f" != "retribusi-api-prod.conf" ]] && [[ "$f" != "retribusi-api-staging.conf" ]]; then
            echo "Nuclear Removal: $f"
            sudo rm -f "$fpath"
        fi
    fi
done

# 4. SURGICAL PURGE OF CONFLICTS (EXCLUDING POS)
purge_conflicts() {
    local domain=$1
    echo "Scanning sites-enabled for conflicts with $domain..."
    local files=$(grep -r "$domain" /etc/nginx/sites-enabled/ -l)
    for f in $files; do
        if [[ "$f" == *"posmpad"* ]]; then continue; fi
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

# 5. RE-ESTABLISH CORRECT CONFIGS (PORT 80 & 443 BRIDGE)
fix_conf() {
    local domain=$1
    local target_root=$2
    local conf_name="retribusi-api-prod.conf"
    [ "$domain" == "$STAGING_DOMAIN" ] && conf_name="retribusi-api-staging.conf"
    
    local conf_path="/etc/nginx/sites-available/$conf_name"
    
    echo "Creating fresh config for $domain at $conf_path"
    sudo bash -c "cat > $conf_path <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $domain;
    root $target_root;

    # Basic catch-all for this domain on port 80
    index index.php index.html;
    charset utf-8;

    location / {
        try_files \\\$uri \\\$uri/ /index.php?\\\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \\\$realpath_root\\\$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOF"
    
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
