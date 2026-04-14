#!/bin/bash
# High-Fidelity Nginx Recovery & Subdomain Partitioning
# ROBUST VERSION: Correct domains and auto-cert discovery.

echo "--- STARTING DOMAIN-CORRECTED NGINX RECOVERY ---"

# 1. DOMAINS
API_DOMAIN="api.sipanda.online"
ADMIN_DOMAIN="adminmpad.baubaukota.go.id"
PETUGAS_DOMAIN="petugasmpad.baubaukota.go.id"

# 2. MINIMAL CONFIG GENERATION
CONF="/tmp/mpad-minimal.conf"
{
  echo "server {"
  echo "    listen 80;"
  echo "    server_name $API_DOMAIN $ADMIN_DOMAIN $PETUGAS_DOMAIN;"
  echo "    root /home/sipanda/retribusi-petugas/dist;"
  echo "    index index.html;"
  echo "    location / {"
  echo "        try_files \$uri \$uri/ /index.html;"
  echo "    }"
  echo "}"
} > $CONF

# 3. ATOMIC APPLY
echo "Applying MINIMAL HTTP configuration..."
sudo rm -rf /etc/nginx/sites-enabled/*
sudo cp $CONF /etc/nginx/sites-available/mpad-minimal.conf
sudo ln -sf /etc/nginx/sites-available/mpad-minimal.conf /etc/nginx/sites-enabled/

# 4. RESTART
sudo nginx -t && sudo systemctl restart nginx

echo "--- PARTITIONED RECOVERY COMPLETED ---"
