#!/bin/bash
# True Nuclear Nginx Reset Script
# This script WIPES ALL CONFIGS except POS to ensure a clean state.

echo "--- STARTING NUCLEAR NGINX RESET ---"

# 1. PRESERVE POS
POS_TARGET=""
if [ -L /etc/nginx/sites-enabled/posmpad ]; then
    POS_TARGET=$(readlink -f /etc/nginx/sites-enabled/posmpad)
    echo "Preserving POS target: $POS_TARGET"
elif [ -f /etc/nginx/sites-enabled/posmpad ]; then
    echo "POS is a regular file, backing it up..."
    sudo cp /etc/nginx/sites-enabled/posmpad /tmp/posmpad_backup
fi

# 2. WHIPE EVERYTHING ENABLED
echo "Wiping sites-enabled and conf.d..."
sudo rm -rf /etc/nginx/sites-enabled/*
sudo rm -rf /etc/nginx/conf.d/*

# 3. RESTORE POS
if [ ! -z "$POS_TARGET" ]; then
    sudo ln -sf "$POS_TARGET" /etc/nginx/sites-enabled/posmpad
    echo "POS symlink restored."
elif [ -f /tmp/posmpad_backup ]; then
    sudo cp /tmp/posmpad_backup /etc/nginx/sites-enabled/posmpad
    echo "POS file restored from backup."
fi

# 4. DEFINE NEW CLEAN PROD CONFIG
PROD_CONF="/etc/nginx/sites-available/retribusi-api-prod.conf"
echo "Creating unified production config at $PROD_CONF"

sudo bash -c "cat > $PROD_CONF <<EOF
server {
    listen 80;
    server_name apimpad.baubaukota.go.id adminmpad.baubaukota.go.id petugasmpad.baubaukota.go.id;
    return 301 https://\\\$host\\\$request_uri;
}

server {
    listen 443 ssl;
    server_name apimpad.baubaukota.go.id adminmpad.baubaukota.go.id petugasmpad.baubaukota.go.id;
    root /home/sipanda/retribusi-api/public;

    index index.php index.html;
    charset utf-8;

    # SSL (Using existing certificates if available, or placeholder)
    ssl_certificate /etc/letsencrypt/live/apimpad.baubaukota.go.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/apimpad.baubaukota.go.id/privkey.pem;

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

# 5. DEFINE STAGING CONFIG
STAGING_CONF="/etc/nginx/sites-available/retribusi-api-staging.conf"
echo "Creating staging config at $STAGING_CONF"
sudo bash -c "cat > $STAGING_CONF <<EOF
server {
    listen 80;
    server_name api.mpad.online admin.mpad.online petugas.mpad.online;
    root /home/sipanda/retribusi-api-staging/public;
    index index.php index.html;
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

# 6. ENABLE NEW CONFIGS
sudo ln -sf "$PROD_CONF" /etc/nginx/sites-enabled/retribusi-api-prod.conf
sudo ln -sf "$STAGING_CONF" /etc/nginx/sites-enabled/retribusi-api-staging.conf

# 7. FINAL CHOWN & RESTART
sudo chown -R www-data:www-data /home/sipanda/retribusi-api/public
sudo chown -R www-data:www-data /home/sipanda/retribusi-api-staging/public

echo "Final sites-enabled listing:"
ls -la /etc/nginx/sites-enabled/

sudo nginx -t && sudo systemctl restart nginx

echo "--- NUCLEAR RESET COMPLETED ---"
