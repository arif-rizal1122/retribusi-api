echo "--- SYSTEM IDENT ---"
whoami
hostname
id
echo "--- NGINX CONFIGS ---"
grep -r "root" /etc/nginx/sites-enabled/ 2>/dev/null
echo "--- DIRECTORY AUDIT ---"
ls -ld /home/sipanda/retribusi-*
echo "--- LARAVEL LOGS (MPAD) ---"
[ -f storage/logs/laravel.log ] && tail -n 50 storage/logs/laravel.log || echo "No laravel logs"
