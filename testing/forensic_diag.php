<?php
header('Content-Type: text/plain');
echo "--- PHP FORENSIC REPORT ---\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Current File: " . __FILE__ . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "User: " . posix_getpwuid(posix_geteuid())['name'] . "\n";

echo "\n--- NGINX CONFIGS ---\n";
echo shell_exec('grep -r "server_name\|root" /etc/nginx/sites-enabled/ 2>&1');

echo "\n--- DIRECTORY LISTING (/home/sipanda) ---\n";
echo shell_exec('ls -la /home/sipanda/ 2>&1');

echo "\n--- DIRECTORY LISTING (/var/www) ---\n";
echo shell_exec('ls -la /var/www/ 2>&1');

echo "\n--- .env PREVIEW ---\n";
if (file_exists('../.env')) {
    echo shell_exec('grep "DB_" ../.env');
} else {
    echo ".env not found in " . realpath('../');
}
