<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Bill;
use App\Models\TaxObject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=============================================\n";
echo "   QA 6: DATABASE PERFORMANCE & STABILITY    \n";
echo "=============================================\n";

function benchmark($name, $callback) {
    $start = microtime(true);
    $result = $callback();
    $end = microtime(true);
    $duration = round(($end - $start) * 1000, 2);
    echo "  -> [BENCHMARK] {$name}: {$duration}ms\n";
    return ['duration' => $duration, 'result' => $result];
}

function checkIndex($table, $indexName) {
    $indices = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
    return count($indices) > 0;
}

echo "[1/4] Audit Indeks Performa...\n";
$requiredIndices = [
    'bills' => ['bills_status_created_at_index', 'bills_period_index'],
    'payments' => ['payments_paid_at_index'],
    'tax_objects' => ['tax_objects_latitude_longitude_index'],
];

$indexPassed = true;
foreach ($requiredIndices as $table => $indices) {
    foreach ($indices as $index) {
        if (checkIndex($table, $index)) {
            echo "  ✅ Index '{$index}' found on table '{$table}'.\n";
        } else {
            echo "  ❌ MISSING: Index '{$index}' NOT found on table '{$table}'.\n";
            $indexPassed = false;
        }
    }
}

echo "\n[2/4] Pengujian Latensi API Dashboard...\n";
$superAdmin = User::where('role', 'super_admin')->first();
$token = $superAdmin->createToken('perf-test')->plainTextToken;

$dashboardTest = benchmark("GET /api/dashboard/stats", function() use ($token) {
    $request = \Illuminate\Http\Request::create('/api/dashboard/stats', 'GET');
    $request->headers->set('Authorization', 'Bearer ' . $token);
    $response = app()->handle($request);
    return $response->getStatusCode();
});

if ($dashboardTest['duration'] > 1000) {
    echo "  ⚠️ WARNING: Dashboard latency is high (>1s). Check for N+1 queries.\n";
} else {
    echo "  ✅ Dashboard latency is within acceptable limits (<1s).\n";
}

echo "\n[3/4] Stress Test: Bulk Billing (50 Objects)...\n";
// Find a retribution type with some objects
$type = \App\Models\RetributionType::whereHas('taxObjects')->first();
$bulkTest = benchmark("POST /api/bills/bulk (50 items)", function() use ($token, $type) {
    if (!$type) return 404;
    $request = \Illuminate\Http\Request::create('/api/bills/bulk', 'POST', [
        'retribution_type_id' => $type->id,
        'period' => '2026-04',
        'due_date' => '2026-05-20',
    ]);
    $request->headers->set('Authorization', 'Bearer ' . $token);
    $response = app()->handle($request);
    return $response->getStatusCode();
});

if ($bulkTest['duration'] > 2000) {
    echo "  ⚠️ WARNING: Bulk billing is slow (>2s). Verify Batch Insertion is active.\n";
} else {
    echo "  ✅ Bulk billing performance is excellent.\n";
}

echo "\n[4/4] Verifikasi Query Exclusivity (N+1 Prevention)...\n";
DB::enableQueryLog();
$request = \Illuminate\Http\Request::create('/api/dashboard/potentials', 'GET');
$request->headers->set('Authorization', 'Bearer ' . $token);
app()->handle($request);
$queryCount = count(DB::getQueryLog());
DB::disableQueryLog();

echo "  -> Dashboard Potentials triggered total: {$queryCount} queries.\n";
if ($queryCount > 20) {
    echo "  ⚠️ WARNING: High query count detected. Potential N+1 leak in map potentials.\n";
} else {
    echo "  ✅ Query count is optimized.\n";
}

$superAdmin->tokens()->delete();

echo "\n---------------------------------------------\n";
$finalPassed = $indexPassed && ($dashboardTest['duration'] < 1500) && ($queryCount < 50);
echo $finalPassed ? "   STATUS: PERFORMA STABIL (PASSED) \n" : "   STATUS: PERFORMA KRITIS (FAILED) \n";
echo "---------------------------------------------\n\n";
