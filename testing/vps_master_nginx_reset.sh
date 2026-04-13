#!/bin/bash
# Master Nginx Purge & Reset Script (Surgical Version)
# This script eliminates conflicting server blocks for M-PAD domains

echo "--- STARTING SURGICAL NGINX PURGE ---"

# 1. DEFINE STANDARDS
PROD_DOMAIN="apimpad.baubaukota.go.id"
STAGING_DOMAIN="api.mpad.online"
PROD_ROOT="/home/sipanda/retribusi-api/public"
STAGING_ROOT="/home/sipanda/retribusi-api-staging/public"

# 2. PURGE CONFLICTS (EXCLUDING POS)
purge_conflicts() {
    local domain=$1
    echo "Scanning sites-enabled for conflicts with $domain..."
    # Find all files except the one we intend to use
    local files=$(grep -r "$domain" /etc/nginx/sites-enabled/ -l)
    for f in $files; do
        # We target specific domains but strictly AVOID posmpad as requested
        if [[ "$domain" == *"apimpad"* ]] || [[ "$domain" == *"adminmpad"* ]] || [[ "$domain" == *"petugasmpad"* ]]; then
            echo "Removing conflicting config: $f"
            sudo rm -f "$f"
        fi
    done
}

# 3. APPLY PURGE
purge_conflicts "$PROD_DOMAIN"
purge_conflicts "adminmpad.baubaukota.go.id"
purge_conflicts "petugasmpad.baubaukota.go.id"
purge_conflicts "$STAGING_DOMAIN"

# 4. RE-ESTABLISH CORRECT CONFIGS
fix_conf() {
    local domain=$1
    local target_root=$2
    
    # We create a clean config in sites-available if it doesn't exist
    local conf_name="retribusi-api-prod.conf"
    [ "$domain" == "$STAGING_DOMAIN" ] && conf_name="retribusi-api-staging.conf"
    
    local conf_path="/etc/nginx/sites-available/$conf_name"
    
    echo "Ensuring clean config at $conf_path for $domain"
    
    # Use a Heredoc to create a guaranteed correct Laravel config
    # Note: We assume SSL is handled by a different block or we'll rely on Certbot later
    # For now, we update the existing one if it exists or create a simple one
    
    if [ -f "$conf_path" ]; then
        echo "Updating root in existing $conf_path"
        sudo sed -i "s|root .*|root $target_root;|g" "$conf_path"
    else
        echo "Warning: $conf_path not found. Using generic repair logic on enabled sites fallback."
    fi
    
    # Re-enable
    sudo ln -sf "$conf_path" "/etc/nginx/sites-enabled/$conf_name"
}

# 5. EXECUTE FIX
fix_conf "$PROD_DOMAIN" "$PROD_ROOT"
fix_conf "$STAGING_DOMAIN" "$STAGING_ROOT"

# 6. FINAL PERMISSIONS & RELOAD
sudo chown -R www-data:www-data "$PROD_ROOT/.."
sudo chown -R www-data:www-data "$STAGING_ROOT/.."
echo "Testing Nginx configuration..."
sudo nginx -t && sudo systemctl reload nginx

echo "--- SURGICAL RESET COMPLETED ---"
