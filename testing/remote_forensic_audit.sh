echo \"--- NGINX FORENSICS ---\"
for f in /etc/nginx/sites-enabled/*; do
    echo \"FILE: \$f\"
    grep -E \"server_name|root\" \$f
done
echo \"--- DIRECTORY LISTING --- \"
ls -ld /home/sipanda/*
ls -la /home/sipanda/retribusi-api
ls -la /home/sipanda/retribusi-admin
ls -la /home/sipanda/retribusi-petugas
