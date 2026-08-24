<?php
/**
 * SimpadKoneksi Verification Script
 * Validates: Service logic, Mapper, Controller existence
 */
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\SimpadKoneksiService;
use App\Http\Controllers\Api\SimpadKoneksiController;
use Illuminate\Support\Facades\Route;

$passed = 0;
$failed = 0;

function test($label, $result, &$passed, &$failed) {
    if ($result) {
        echo "  ✅ PASS: $label\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: $label\n";
        $failed++;
    }
}

echo "\n🛠️ SIMPAD KONEKSI VERIFICATION\n";
echo str_repeat('=', 60) . "\n\n";

// 1. Service Verification
echo "📋 Part 1: Service Verification\n";
$service = new SimpadKoneksiService();
test('SimpadKoneksiService class exists', $service instanceof SimpadKoneksiService, $passed, $failed);

// Test Status Mapping
$statusReflector = new ReflectionMethod(SimpadKoneksiService::class, 'mapStatus');
$statusReflector->setAccessible(true);
test('mapStatus(0) -> draft', $statusReflector->invoke($service, 0) === 'draft', $passed, $failed);
test('mapStatus(1) -> proses', $statusReflector->invoke($service, 1) === 'proses', $passed, $failed);
test('mapStatus(2) -> disetujui', $statusReflector->invoke($service, 2) === 'disetujui', $passed, $failed);
test('mapStatus(3) -> ditolak', $statusReflector->invoke($service, 3) === 'ditolak', $passed, $failed);

// Test Taxpayer Mapping
$legacyWP = (object)[
    'CPM_NPWPD' => '1.2.3.4',
    'CPM_NAMA' => 'John Doe',
    'CPM_ALAMAT' => 'Jl. Merdeka 123',
    'CPM_TELEPON' => '0812345678',
    'CPM_STATUS' => 2
];
$mappedWP = $service->mapTaxpayer($legacyWP);
test('mapTaxpayer: maps name correctly', $mappedWP['name'] === 'John Doe', $passed, $failed);
test('mapTaxpayer: maps status 2 to disetujui', $mappedWP['status'] === 'disetujui', $passed, $failed);

echo "\n";

// 2. Controller & Routes Verification
echo "📋 Part 2: Controller & Routes Verification\n";
$controller = app(SimpadKoneksiController::class);
test('SimpadKoneksiController class exists', $controller instanceof SimpadKoneksiController, $passed, $failed);

$routes = collect(Route::getRoutes())->map(function($r) { return $r->uri(); });
test('Route /api/simpad-koneksi/taxpayers/{npwpd} exists', $routes->contains('api/simpad-koneksi/taxpayers/{npwpd}'), $passed, $failed);
test('Route /api/simpad-koneksi/objects/{type} exists', $routes->contains('api/simpad-koneksi/objects/{type}'), $passed, $failed);
test('Route /api/simpad-koneksi/officers exists', $routes->contains('api/simpad-koneksi/officers'), $passed, $failed);

echo "\n";

// Summary
echo str_repeat('=', 60) . "\n";
$total = $passed + $failed;
echo "📊 SUMMARY: {$passed}/{$total} PASSED\n";
echo str_repeat('=', 60) . "\n";

if ($failed > 0) exit(1);
exit(0);
