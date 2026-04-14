#!/bin/bash
# High-Fidelity Nginx Recovery & Subdomain Partitioning
# This script eliminates the "Soft Launching" override and restores partitioned M-PAD services.

echo "--- STARTING PARTITIONED NGINX RECOVERY ---"

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
sudo rm -f $API_ROOT/public/index.html
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

echo "Admin Path: $ADMIN_PATH"
echo "Petugas Path: $PETUGAS_PATH"

# 7. CREATE PARTITIONED CONFIGS
PROD_CONF="/etc/nginx/sites-available/mpad-production.conf"
sudo bash -c "cat > $PROD_CONF <<EOF
# --- API SUBDOMAIN ---
server {
    listen 80;
    server_name apimpad.baubaukota.go.id;
    return 301 https://\\\$host\\\$request_uri;
}

server {
    listen 443 ssl;
    server_name apimpad.baubaukota.go.id;
    root $API_ROOT/public;
    index index.php;
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

# --- ADMIN SUBDOMAIN ---
server {
    listen 80;
    server_name adminmpad.baubaukota.go.id;
    return 301 https://\\\$host\\\$request_uri;
}

server {
    listen 443 ssl;
    server_name adminmpad.baubaukota.go.id;
    root $ADMIN_PATH;
    index index.html;
    ssl_certificate /etc/letsencrypt/live/apimpad.baubaukota.go.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/apimpad.baubaukota.go.id/privkey.pem;

    location / {
        try_files \\\$uri \\\$uri/ /index.html;
    }
}

# --- PETUGAS SUBDOMAIN ---
server {
    listen 80;
    server_name petugasmpad.baubaukota.go.id;
    return 301 https://\\\$host\\\$request_uri;
}

server {
    listen 443 ssl;
    server_name petugasmpad.baubaukota.go.id;
    root $PETUGAS_PATH;
    index index.html;
    ssl_certificate /etc/letsencrypt/live/apimpad.baubaukota.go.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/apimpad.baubaukota.go.id/privkey.pem;

    location / {
        try_files \\\$uri \\\$uri/ /index.html;
    }
}
EOF"

# 8. ENABLE & RESTART
sudo ln -sf "$PROD_CONF" /etc/nginx/sites-enabled/mpad-production.conf
sudo nginx -t && sudo systemctl restart nginx

echo "--- PARTITIONED RECOVERY COMPLETED ---"
