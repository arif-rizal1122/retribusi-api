echo "--- VPS INFRASTRUCTURE AUDIT ---"
echo "Date: $(date)"
echo "Whoami: $(whoami)"
echo "PWD: $(pwd)"
echo "--- Directory Scan (/home) ---"
ls -la /home
echo "--- Checking Sipanda dir ---"
ls -la /home/sipanda 2>/dev/null || echo "/home/sipanda not accessible"
echo "--- Checking Mpad dir ---"
ls -la /home/mpad 2>/dev/null || echo "/home/mpad not accessible"
echo "--- Checking Nginx Sites ---"
ls -la /etc/nginx/sites-enabled/ 2>/dev/null
echo "--- Checking API Staging folder structure ---"
ls -R /home/sipanda/retribusi-api-staging 2>/dev/null | head -n 50
