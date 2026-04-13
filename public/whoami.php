<?php
header('Content-Type: text/plain');
echo "I AM IN: " . realpath(__DIR__) . "\n";
echo "SERVER ADDR: " . $_SERVER['SERVER_ADDR'] . "\n";
echo "HTTP HOST: " . $_SERVER['HTTP_HOST'] . "\n";
echo "PHP USER: " . `whoami` . "\n";
echo "\n--- NGINX SITES ENABLED ---\n";
echo `ls -la /etc/nginx/sites-enabled/`;
echo "\n--- CATCH-ALLS ---\n";
echo `grep -r "default_server" /etc/nginx/sites-enabled/`;
echo "\n--- NGINX CONFIG TEST ---\n";
echo `sudo nginx -t 2>&1`;
