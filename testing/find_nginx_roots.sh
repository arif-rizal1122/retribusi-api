echo "--- NGINX CONFIG AUDIT ---"
for conf in /etc/nginx/sites-enabled/*; do
  echo "FILE: $conf"
  grep -E "server_name|root" "$conf"
  echo "-------------------"
done
echo "--- HOME FOLDERS OWNERSHIP ---"
ls -ld /home/sipanda/* 2>/dev/null
