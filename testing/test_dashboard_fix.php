<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Opd;

// Act as Super Admin (email: admin@mpad.online based on common setups or found in DB)
$user = User::where('role', 'super_admin')->first();
if (!$user) {
    // Fallback to any admin
    $user = User::where('role', 'admin')->first();
}

if (!$user) {
    die("No suitable admin user found to test.\n");
}

echo "Testing as user: " . $user->email . " (Role: " . $user->role . ")\n";

// Target OPD 5 (Badan Pendapatan Daerah)
$targetOpdId = 5;
$opd = Opd::find($targetOpdId);
echo "Filtering for OPD: " . ($opd ? $opd->name : "ID 5 not found") . "\n";

$request = Request::create('/api/dashboard/stats', 'GET', [
    'start_date' => '2026-03-31',
    'end_date' => '2026-04-29',
    'opd_id' => $targetOpdId
]);

$request->headers->set('Accept', 'application/json');
$request->setUserResolver(fn() => $user);
Auth::setUser($user);

$response = app()->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
$data = json_decode($response->getContent(), true);

if ($response->getStatusCode() !== 200) {
    echo "ERROR RESPONSE: " . $response->getContent() . "\n";
    exit(1);
}

echo "Revenue: " . ($data['total_revenue'] ?? '0') . "\n";
echo "Revenue by Classification (first 2):\n";
if (!empty($data['revenue_by_classification'])) {
    foreach (array_slice($data['revenue_by_classification'], 0, 5) as $item) {
        echo " - " . $item['name'] . ": " . $item['total'] . "\n";
    }
} else {
    echo " - NONE\n";
}

// Check for unclassified
$foundUnclassified = false;
foreach ($data['revenue_by_classification'] as $item) {
    if ($item['name'] === 'Lainnya/Belum Terklasifikasi') {
        $foundUnclassified = true;
        break;
    }
}
echo "Has Unclassified Group: " . ($foundUnclassified ? "YES" : "NO") . "\n";

echo "\nVerification of Super Admin Filter:\n";
// Now test without opd_id (should be global for super_admin)
$requestGlobal = Request::create('/api/dashboard/stats', 'GET', [
    'start_date' => '2026-03-31',
    'end_date' => '2026-04-29'
]);
$requestGlobal->setUserResolver(fn() => $user);
$responseGlobal = app()->handle($requestGlobal);
$dataGlobal = json_decode($responseGlobal->getContent(), true);
echo "Global Revenue: " . ($dataGlobal['total_revenue'] ?? '0') . "\n";

if ($dataGlobal['total_revenue'] !== $data['total_revenue']) {
    echo "SUCCESS: Filtering works (Global vs Filtered OPD)\n";
} else {
    // If they are the same, it might be because only one OPD has data, but let's check counts
    echo "NOTE: Global and Filtered revenue are same. Check if other OPDs have data.\n";
    $otherOpdId = Opd::where('id', '!=', $targetOpdId)->value('id');
    if ($otherOpdId) {
        $reqOther = Request::create('/api/dashboard/stats', 'GET', ['opd_id' => $otherOpdId]);
        $reqOther->setUserResolver(fn() => $user);
        $resOther = app()->handle($reqOther);
        $dataOther = json_decode($resOther->getContent(), true);
        echo "Other OPD ($otherOpdId) Revenue: " . ($dataOther['total_revenue'] ?? '0') . "\n";
    }
}
