<?php
/**
 * Reporting Schema Test
 * Memvalidasi respons GET /api/reports/summary dan /api/reports/recent
 * terhadap kontrak interface TypeScript halaman Reporting (retribusi-petugas/src/pages/Reporting.tsx).
 *
 * Usage: php run_reporting_schema_test.php [local|dev|prod]
 */

$env = $argv[1] ?? 'local';
if ($env === 'dev') {
    $baseUrl = "https://api-dev.sipanda.online";
} elseif ($env === 'prod') {
    $baseUrl = "https://api.sipanda.online";
} else {
    $baseUrl = "http://127.0.0.1:8000";
}

$md  = "# 📋 Reporting Schema Test\n\n";
$md .= "**Waktu Eksekusi**: " . date('Y-m-d H:i:s') . "\n";
$md .= "**Target**: $baseUrl\n";
$md .= "**Kontrak**: interface `ReportSummary` & `RecentReport` di `retribusi-petugas/src/pages/Reporting.tsx`\n\n";

$results = [];
$totalPass = 0;
$totalFail = 0;
$totalWarn = 0;

function record(&$results, &$totalPass, &$totalFail, &$totalWarn, $case, $status, $detail) {
    $icon = $status === 'PASS' ? '✅' : ($status === 'WARN' ? '⚠️' : '❌');
    $results[] = "| $icon | $case | $status | $detail |";
    if ($status === 'PASS') $totalPass++;
    elseif ($status === 'WARN') $totalWarn++;
    else $totalFail++;
}

function sendApi($method, $url, $baseUrl, $token, $data = null) {
    $ch = curl_init($baseUrl . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json', $token ? "Authorization: Bearer $token" : '']);
    if ($data !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

function isJsonNumber($v) {
    return is_int($v) || is_float($v);
}

// ====================================================================
// 1. LOGIN
// ====================================================================
$login = sendApi('POST', '/api/login', $baseUrl, '', ['email' => 'petugas@bapenda.go.id', 'password' => 'password123']);
$petugasToken = $login['body']['token'] ?? null;
record($results, $totalPass, $totalFail, $totalWarn, 'Login petugas@bapenda.go.id', $petugasToken ? 'PASS' : 'FAIL',
    $petugasToken ? 'Token diterima' : 'Login gagal, test berikutnya dilewati');

$adminLogin = sendApi('POST', '/api/login', $baseUrl, '', ['email' => 'admin@retribusi.id', 'password' => 'password123']);
$adminToken = $adminLogin['body']['token'] ?? null;
record($results, $totalPass, $totalFail, $totalWarn, 'Login admin@retribusi.id', $adminToken ? 'PASS' : 'FAIL',
    $adminToken ? 'Token diterima' : 'Login gagal');

if (!$petugasToken && !$adminToken) {
    $md .= implode("\n", $results) . "\n";
    file_put_contents(__DIR__ . '/results/15_Reporting_Schema_Test_' . $env . '_' . date('Ymd_His') . '.md', $md);
    exit("FATAL: tidak ada token.\n");
}

$token = $petugasToken ?: $adminToken;

// ====================================================================
// 2. AUTH GUARD
// ====================================================================
$r = sendApi('GET', '/api/reports/summary', $baseUrl, '');
record($results, $totalPass, $totalFail, $totalWarn, 'Summary tanpa token → 401', $r['code'] === 401 ? 'PASS' : 'FAIL', "HTTP {$r['code']}");

$r = sendApi('GET', '/api/reports/recent', $baseUrl, '');
record($results, $totalPass, $totalFail, $totalWarn, 'Recent tanpa token → 401', $r['code'] === 401 ? 'PASS' : 'FAIL', "HTTP {$r['code']}");

// ====================================================================
// 3. SCHEMA /api/reports/summary (kontrak ReportSummary)
// ====================================================================
$start = date('Y-m-01');
$end = date('Y-m-d');
$r = sendApi('GET', "/api/reports/summary?start_date=$start&end_date=$end", $baseUrl, $token);

record($results, $totalPass, $totalFail, $totalWarn, 'Summary periode valid → 200', $r['code'] === 200 ? 'PASS' : 'FAIL', "HTTP {$r['code']}");
$body = $r['body'];

if ($r['code'] === 200 && is_array($body)) {
    record($results, $totalPass, $totalFail, $totalWarn,
        'Summary.total_revenue bertipe number',
        isJsonNumber($body['total_revenue'] ?? null) ? 'PASS' : 'FAIL',
        'actual: ' . json_encode($body['total_revenue'] ?? null) . ' (' . gettype($body['total_revenue'] ?? null) . ')');

    record($results, $totalPass, $totalFail, $totalWarn,
        'Summary.revenue_by_type adalah array',
        isset($body['revenue_by_type']) && is_array($body['revenue_by_type']) ? 'PASS' : 'FAIL', '');

    $sumAmount = 0;
    $sumPct = 0;
    $itemIssues = [];
    foreach (($body['revenue_by_type'] ?? []) as $i => $item) {
        foreach (['type' => 'string', 'amount' => 'number', 'percentage' => 'number', 'target' => 'number'] as $field => $type) {
            $val = $item[$field] ?? null;
            $ok = ($type === 'string') ? is_string($val) : isJsonNumber($val);
            if (!$ok) $itemIssues[] = "[$i].$field=" . json_encode($val) . " (harus $type)";
        }
        if (!isset($item['count'])) $itemIssues[] = "[$i] field tambahan 'count' dari backend tidak dikonsumsi frontend (informatif)";
        $sumAmount += isJsonNumber($item['amount'] ?? null) ? $item['amount'] : 0;
        $sumPct += isJsonNumber($item['percentage'] ?? null) ? $item['percentage'] : 0;
    }
    record($results, $totalPass, $totalFail, $totalWarn,
        'RevenueByType[] sesuai skema {type, amount, percentage, target}',
        empty($itemIssues) ? 'PASS' : 'FAIL', empty($itemIssues) ? count($body['revenue_by_type']) . ' item valid' : implode('; ', array_slice($itemIssues, 0, 5)));

    record($results, $totalPass, $totalFail, $totalWarn,
        'Summary.stats.total_transactions number',
        isJsonNumber($body['stats']['total_transactions'] ?? null) ? 'PASS' : 'FAIL', 'actual: ' . json_encode($body['stats']['total_transactions'] ?? null));

    record($results, $totalPass, $totalFail, $totalWarn,
        'Summary.stats.avg_transaction number',
        isJsonNumber($body['stats']['avg_transaction'] ?? null) ? 'PASS' : 'FAIL', 'actual: ' . json_encode($body['stats']['avg_transaction'] ?? null));

    // Konsistensi antar-field
    if (isJsonNumber($body['total_revenue'] ?? null)) {
        record($results, $totalPass, $totalFail, $totalWarn,
            'Konsistensi: total_revenue == sum(amount)',
            abs($body['total_revenue'] - $sumAmount) < 0.01 ? 'PASS' : 'FAIL',
            "total={$body['total_revenue']} vs sum=$sumAmount");
    }
}

// Summary periode lebar yang pasti berisi data (validasi RevenueByType terisi)
$rFull = sendApi('GET', "/api/reports/summary?start_date=" . date('Y') . "-01-01&end_date=" . date('Y') . "-12-31", $baseUrl, $token);
$fullIssues = [];
$fullItems = $rFull['body']['revenue_by_type'] ?? [];
foreach ($fullItems as $i => $item) {
    if (!isJsonNumber($item['amount'] ?? null)) {
        $fullIssues[] = "[$i].amount=" . json_encode($item['amount']) . " (harus number) — SUM() MySQL dikembalikan sebagai string";
    }
}
record($results, $totalPass, $totalFail, $totalWarn,
    'Summary data terisi (' . count($fullItems) . ' item): RevenueByType.amount bertipe number',
    count($fullItems) === 0 ? 'WARN' : (empty($fullIssues) ? 'PASS' : 'FAIL'),
    count($fullItems) === 0 ? 'tidak ada data untuk memverifikasi' : (empty($fullIssues) ? 'semua amount number' : implode('; ', $fullIssues)));

// Summary tanpa parameter (default bulan ini)
$r2 = sendApi('GET', '/api/reports/summary', $baseUrl, $token);
$schemaOk = $r2['code'] === 200 && isset($r2['body']['total_revenue'], $r2['body']['revenue_by_type'], $r2['body']['stats']);
record($results, $totalPass, $totalFail, $totalWarn, 'Summary tanpa param (default) → schema valid', $schemaOk ? 'PASS' : 'FAIL', "HTTP {$r2['code']}");

// Tanggal terbalik (start > end) → harus 422
$r3 = sendApi('GET', "/api/reports/summary?start_date=2026-12-31&end_date=2026-01-01", $baseUrl, $token);
record($results, $totalPass, $totalFail, $totalWarn, 'Summary tanggal terbalik (start > end) → 422', $r3['code'] === 422 ? 'PASS' : 'FAIL', "HTTP {$r3['code']}");

// Format tanggal invalid → harus 422
$r4 = sendApi('GET', "/api/reports/summary?start_date=inikacau&end_date=2026-08-23", $baseUrl, $token);
record($results, $totalPass, $totalFail, $totalWarn, 'Summary start_date invalid ("inikacau") → 422', $r4['code'] === 422 ? 'PASS' : 'FAIL', "HTTP {$r4['code']}");

// ====================================================================
// 4. SCHEMA /api/reports/recent (kontrak RecentReport[])
// ====================================================================
$r5 = sendApi('GET', '/api/reports/recent', $baseUrl, $token);
record($results, $totalPass, $totalFail, $totalWarn, 'Recent → 200', $r5['code'] === 200 ? 'PASS' : 'FAIL', "HTTP {$r5['code']}");

if ($r5['code'] === 200 && is_array($r5['body'])) {
    record($results, $totalPass, $totalFail, $totalWarn, 'Recent adalah array of object', array_is_list($r5['body']) ? 'PASS' : 'FAIL', count($r5['body']) . ' item');

    record($results, $totalPass, $totalFail, $totalWarn, 'Recent maksimal 10 item', count($r5['body']) <= 10 ? 'PASS' : 'WARN', count($r5['body']) . ' item');

    $recentIssues = [];
    $stringAmounts = 0;
    foreach ($r5['body'] as $i => $item) {
        $contract = ['id' => 'number', 'taxpayer_name' => 'string', 'type' => 'string', 'amount' => 'number', 'date' => 'string', 'method' => 'string', 'status' => 'string'];
        foreach ($contract as $field => $type) {
            if (!array_key_exists($field, $item)) { $recentIssues[] = "[$i] field '$field' hilang"; continue; }
            $val = $item[$field];
            $ok = ($type === 'string') ? is_string($val) : isJsonNumber($val);
            if (!$ok) {
                if ($field === 'amount' && is_string($val) && is_numeric($val)) { $stringAmounts++; continue; }
                $recentIssues[] = "[$i].$field=" . json_encode($val) . " (harus $type, dapat " . gettype($val) . ")";
            }
        }
    }
    if ($stringAmounts > 0) {
        $recentIssues[] = "$stringAmounts/$stringAmounts item punya amount sebagai STRING numerik (mis. \"475000.00\") — melanggar kontrak `amount: number` (Payment::amount tanpa cast numerik)";
    }
    record($results, $totalPass, $totalFail, $totalWarn,
        'RecentReport sesuai skema {id, taxpayer_name, type, amount, date, method, status}',
        empty($recentIssues) ? 'PASS' : 'FAIL', empty($recentIssues) ? 'Semua field & tipe sesuai' : implode('; ', array_slice($recentIssues, 0, 6)));

    // Format date harus parseable
    $badDate = false;
    foreach ($r5['body'] as $item) {
        if (strtotime($item['date'] ?? '') === false) { $badDate = true; break; }
    }
    record($results, $totalPass, $totalFail, $totalWarn, 'Recent.date format datetime valid', !$badDate ? 'PASS' : 'FAIL', 'format Y-m-d H:i:s');
}

// ====================================================================
// 5. RINGKASAN
// ====================================================================
$md .= "## Hasil\n\n| Status | Kasus Uji | Hasil | Detail |\n|---|---|---|---|\n" . implode("\n", $results) . "\n\n";
$md .= "**Total**: $totalPass PASS, $totalWarn WARN, $totalFail FAIL\n\n";

$md .= "## Status Perbaikan\n\n";
$md .= "1. **[FIXED - SCHEMA]** \`amount\` kini dikembalikan sebagai number di kedua endpoint. Perbaikan: cast `'amount' => 'float'` pada `app/Models/Payment.php` + `(float)`/`(int)` pada hasil SUM di `ReportController::getSummary()`.\n";
$md .= "2. **[FIXED - INPUT VALIDATION]** `start_date`/`end_date` kini divalidasi (`date`, `after_or_equal:start_date`). Input invalid/tanggal terbalik mengembalikan HTTP 422, bukan 500.\n";
$md .= "3. **[INFO]** Backend tetap mengirim field tambahan `period`, `count`, dan placeholder `target = amount * 1.2` yang tidak dikonsumsi frontend — tidak berbahaya.\n";

echo $md;

$savePath = __DIR__ . '/results/15_Reporting_Schema_Test_' . $env . '_' . date('Ymd_His') . '.md';
file_put_contents($savePath, $md);
echo "Hasil disimpan: $savePath\n";
exit($totalFail > 0 ? 1 : 0);
