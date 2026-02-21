<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Zone;
use App\Models\Opd;
use App\Models\RetributionType;

$opd = Opd::first();
$type = RetributionType::first();

echo "Testing Zone CRUD...\n";

// 1. Create
try {
    $zone = Zone::create([
        'opd_id' => $opd->id,
        'retribution_type_id' => $type->id,
        'name' => 'Test Zone CRUD',
        'code' => 'TEST-CRUD',
        'description' => 'Testing CRUD through script',
        'geometry_type' => 'point',
        'latitude' => -5.4677,
        'longitude' => 122.6048
    ]);
    echo "Created Zone ID: " . $zone->id . "\n";
} catch (\Exception $e) {
    echo "Create failed: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Read
$zone = Zone::find($zone->id);
if ($zone && $zone->name === 'Test Zone CRUD') {
    echo "Read successful.\n";
} else {
    echo "Read failed.\n";
    exit(1);
}

// 3. Update
try {
    $zone->update([
        'name' => 'Updated Test Zone CRUD',
        'latitude' => -5.5000
    ]);
    if ($zone->fresh()->name === 'Updated Test Zone CRUD') {
        echo "Update successful.\n";
    } else {
        echo "Update failed.\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "Update failed: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Delete
try {
    $zone->delete();
    if (!Zone::find($zone->id)) {
        echo "Delete successful.\n";
    } else {
        echo "Delete failed.\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "Delete failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Zone CRUD Test PASSED!\n";
