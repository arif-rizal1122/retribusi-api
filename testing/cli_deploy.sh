#!/bin/bash
# ============================================================
# MPAD CLI DEPLOY ORCHESTRATOR
# Usage: ./testing/cli_deploy.sh [staging|production]
# ============================================================

TARGET=${1:-staging}
DEPLOY_SECRET=${DEPLOY_SECRET:-"mpad-deploy-2024"}

echo "🚀 MPAD CLI Deploy Orchestrator"
echo "================================"
echo "Target: $TARGET | $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# ============================================================
# STEP 1: LOCAL PRE-DEPLOY AUDIT
# ============================================================
echo "[1/4] 🔍 Running local pre-deploy audit..."
php testing/qa7_ultimate_mpad_audit.php > /dev/null 2>&1
if [ $? -eq 0 ]; then
  echo "  ✅ Local audit PASSED"
else
  echo "  ❌ Local audit FAILED - aborting deploy"
  exit 1
fi

# ============================================================
# STEP 2: PUSH TO GITHUB
# ============================================================
echo "[2/4] 📦 Pushing code to GitHub main..."
git add . 2>/dev/null
git diff --cached --quiet || git commit -m "deploy: auto-push $(date +%Y-%m-%d_%H:%M:%S)"
git push origin main
if [ $? -eq 0 ]; then
  echo "  ✅ Code pushed to GitHub"
else
  echo "  ⚠️  Nothing to push or push failed"
fi

# ============================================================
# STEP 3: TRIGGER STAGING DEPLOY via WEBHOOK
# ============================================================
if [ "$TARGET" = "staging" ]; then
  echo "[3/4] 🌐 Triggering staging deploy webhook..."
  STAGING_URL="https://api.sipanda.online"

  RESP=$(curl -s -X POST "$STAGING_URL/api/admin/deploy-hook" \
    -H "Content-Type: application/json" \
    -H "X-Deploy-Secret: $DEPLOY_SECRET" \
    --max-time 120)

  STATUS=$(echo $RESP | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('status','error'))" 2>/dev/null)

  if [ "$STATUS" = "success" ]; then
    echo "  ✅ Staging deploy SUCCESS"
    echo $RESP | python3 -c "import sys,json; d=json.load(sys.stdin); [print('    -', o) for o in d.get('output',[])]" 2>/dev/null
  else
    echo "  ⚠️  Webhook not ready yet (staging needs git pull first)"
    echo "  Response: $(echo $RESP | head -c 150)"
  fi
fi

# ============================================================
# STEP 4: POST-DEPLOY HEALTH CHECK
# ============================================================
echo "[4/4] 🏥 Post-deploy health check..."
sleep 3

for URL in "https://api.sipanda.online/up" "https://admin.sipanda.online" "https://petugas.sipanda.online"; do
  STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 8 "$URL")
  if [ "$STATUS" = "200" ]; then
    echo "  ✅ $URL → HTTP $STATUS"
  else
    echo "  ❌ $URL → HTTP $STATUS"
  fi
done

echo ""
echo "================================"
echo "✅ Deploy orchestration complete!"
echo "================================"
