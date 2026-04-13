<?php
/**
 * PRODUCTION-VERIFY-REMOTE
 * Targets: https://api.mpad.online
 */

$baseUrl = 'https://api.mpad.online';
$results = [];

echo "========================================\n";
echo "🔍 REMOTE PRODUCTION VERIFICATION: $baseUrl\n";
echo "========================================\n";

// 1. Health Check
echo "[1/3] Health Check (/api/up)... ";
$ch = curl_init("$baseUrl/api/up");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($code === 200) {
    echo "✅ 200 OK\n";
} else {
    echo "❌ FAIL (Status: $code)\n";
}

// 2. Auth Probe (Unauthorized)
echo "[2/3] Auth Probe (/api/me - Unauthorized)... ";
$ch = curl_init("$baseUrl/api/me");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($code === 401) {
    echo "✅ 401 Unauthorized (Correct Behavior)\n";
} else {
    echo "❌ UNEXPECTED (Status: $code)\n";
}

// 3. Document Integrity Check (Static Assets)
echo "[3/3] Asset Check (Logo)... ";
$ch = curl_init("https://adminmpad.baubaukota.go.id/mitra-logo.png");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($code === 200) {
    echo "✅ 200 OK\n";
} else {
    echo "⚠️ WARNING (Status: $code)\n";
}

echo "\nVerification Finished.\n";
