echo "--- NGINX CONFIGS ---"
for conf in /etc/nginx/sites-enabled/*; do
  echo "FILE: \$conf"
  grep -E "server_name|root" \$conf
  echo "-------------------"
done
echo "--- LARAVEL LOGS ---"
[ -f storage/logs/laravel.log ] && tail -n 50 storage/logs/laravel.log || echo "No laravel logs"
echo "--- DATABASE CHECK ---"
php artisan migrate:status 2>&1
