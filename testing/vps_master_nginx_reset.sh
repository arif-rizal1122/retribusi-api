#!/bin/bash
# Master Nginx Purge & Reset Script for M-PAD
# This script is designed to definitively fix routing between Staging and Production

echo "--- STARTING MASTER NGINX PURGE & RESET ---"

# 1. DEFINE STANDARDS
PROD_DOMAIN="apimpad.baubaukota.go.id"
STAGING_DOMAIN="api.mpad.online"
PROD_ROOT="/home/sipanda/retribusi-api/public"
STAGING_ROOT="/home/sipanda/retribusi-api-staging/public"

# 2. HELPER TO FIX CONFIG
fix_conf() {
    local domain=$1
    local target_root=$2
    local conf_file=$(grep -r "$domain" /etc/nginx/sites-enabled/ -l | head -n 1)

    if [ -n "$conf_file" ]; then
        echo "Found config for $domain in $conf_file"
        
        # Backup
        sudo cp "$conf_file" "${conf_file}.bak_$(date +%s)"
        
        # Aggressive Sed: Replace root
        # Supports both "root /path/to/site;" and "root /path/to/site"
        sudo sed -i "s|root .*|root $target_root;|g" "$conf_file"
        
        # Ensure index.php is priority
        sudo sed -i "s|index .*|index index.php index.html index.htm;|g" "$conf_file"
        
        # Ensure try_files is correct for Laravel
        if ! grep -q "try_files \$uri \$uri/ /index.php?\$query_string" "$conf_file"; then
             echo "Updating try_files for Laravel routing..."
             # This is a bit risky with sed, but necessary if it's currently a static site
             sudo sed -i "s|try_files .*|try_files \$uri \$uri/ /index.php?\$query_string;|g" "$conf_file"
        fi
        
        echo "✅ Updated $domain to $target_root"
    else
        echo "⚠️ No config found for $domain (Grep failed)"
    fi
}

# 3. APPLY TO BOTH
fix_conf "$PROD_DOMAIN" "$PROD_ROOT"
fix_conf "$STAGING_DOMAIN" "$STAGING_ROOT"

# 4. FINAL PERMISSION FIX (Just in case)
sudo chown -R www-data:www-data "$PROD_ROOT/.."
sudo chown -R www-data:www-data "$STAGING_ROOT/.."

# 5. TEST AND RELOAD
echo "Testing Nginx configuration..."
sudo nginx -t && sudo systemctl reload nginx

if [ $? -eq 0 ]; then
    echo "✅ Master Reset Successful. Nginx Reloaded."
else
    echo "❌ Nginx Error. Check /var/log/nginx/error.log"
    exit 1
fi

echo "--- MASTER RESET COMPLETED ---"
