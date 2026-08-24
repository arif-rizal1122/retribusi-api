<?php
/**
 * OMNI-TEST-REMOTE: Production Verification
 * Target: https://api.mpad.online
 */

$apiUrl = 'https://api.mpad.online';
$report = "# 🛡️ PRODUCTION OMNI-TEST REPORT\n\n";
$report .= "**Timestamp**: " . date('Y-m-d H:i:s') . "\n";
$report .= "**Basics**: Health check on production endpoints.\n\n";

function probe($method, $path, $token = null, $data = []) {
    global $apiUrl;
    $ch = curl_init("$apiUrl$path");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    $headers = ['Accept: application/json'];
    if ($token) $headers[] = "Authorization: Bearer $token";
    if ($data) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return ['code' => $code, 'body' => json_decode($res, true)];
}

// 1. PUBLIC PROBE
echo "Checking Public Endpoints...\n";
$res = probe('GET', '/api/up');
$status = ($res['code'] === 200) ? '✅ PASS' : '❌ FAIL (404 expected if route not explicitly defined)';
$report .= "### Public Availability\n- **GET /api/up**: " . $status . " (Status: {$res['code']})\n";

// 2. AUTH PROBE
echo "Checking Authentication Enforcement...\n";
$res = probe('GET', '/api/me');
$status = ($res['code'] === 401) ? '✅ PASS' : '❌ FAIL (Security Gap!)';
$report .= "### Authentication Probe\n- **GET /api/me (No Token)**: " . $status . " (Status: {$res['code']})\n";

// 3. RBAC & DATA ISOLATION (PROBE ONLY)
echo "Checking Data Isolation Routes...\n";
$res = probe('GET', '/api/taxpayers');
$status = ($res['code'] === 401) ? '✅ PASS' : '⚠️ WARNING (Non-standard status: ' . $res['code'] . ')';
$report .= "### RBAC Probe\n- **GET /api/taxpayers (Unauthorized)**: " . $status . "\n";

// Final Result
echo "Done. Writing report to testing/results/production_omni_report.md\n";
file_put_contents(__DIR__ . '/results/production_omni_report.md', $report);
 echo $report;
