#!/bin/bash
# 1. Hide Nginx Version
sudo sed -i 's/# server_tokens off;/server_tokens off;/' /etc/nginx/nginx.conf

# 2. Add Security Headers to api.sipanda.online config
CONFIG_FILE="/etc/nginx/sites-available/retribusi-api"

# Check if headers already exist
if grep -q "Strict-Transport-Security" "$CONFIG_FILE"; then
    echo "Headers already exist. Skipping injection."
else
    # Insert headers right after ssl_certificate_key line using sed
    sudo sed -i '/ssl_certificate_key/a \
    \n    # Security Headers\
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;\
    add_header X-Content-Type-Options "nosniff" always;\
    add_header X-Frame-Options "SAMEORIGIN" always;\
    add_header X-XSS-Protection "1; mode=block" always;\
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;' "$CONFIG_FILE"
    echo "Security headers injected."
fi

# 3. Block sensitive files access (.env, .git)
if grep -q "well-known" "$CONFIG_FILE"; then
    echo "Sensitive config block seems to exist."
else
    # Insert before the last closing brace
    sudo sed -i '/^}/i \
    \n    location ~ /\\.(?!well-known).* {\n        deny all;\n    }' "$CONFIG_FILE"
    echo "Sensitive files block injected."
fi

# Test and reload
sudo nginx -t && sudo systemctl reload nginx
