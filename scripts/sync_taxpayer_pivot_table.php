<?php

/**
 * Script to sync taxpayer_retribution_type pivot table from tax_objects data.
 * This ensures that controller filters based on relationships work as expected.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🚀 Starting Pivot Sync...\n";

// 1. Get all tax objects with their taxpayer and retribution info
$objects = DB::table('tax_objects')
    ->select('taxpayer_id', 'retribution_type_id', 'retribution_classification_id')
    ->get();

echo "📊 Found " . $objects->count() . " tax objects to sync.\n";

$syncedCount = 0;

foreach ($objects as $obj) {
    if (!$obj->taxpayer_id || !$obj->retribution_type_id) continue;

    // Check if pivot already exists
    $exists = DB::table('taxpayer_retribution_type')
        ->where('taxpayer_id', $obj->taxpayer_id)
        ->where('retribution_type_id', $obj->retribution_type_id)
        ->where(function($q) use ($obj) {
            if ($obj->retribution_classification_id) {
                $q->where('retribution_classification_id', $obj->retribution_classification_id);
            } else {
                $q->whereNull('retribution_classification_id');
            }
        })
        ->exists();

    if (!$exists) {
        try {
            DB::table('taxpayer_retribution_type')->insert([
                'taxpayer_id' => $obj->taxpayer_id,
                'retribution_type_id' => $obj->retribution_type_id,
                'retribution_classification_id' => $obj->retribution_classification_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $syncedCount++;
        } catch (\Exception $e) {
            echo "⚠️ Failed to sync combo [TP:{$obj->taxpayer_id}, Type:{$obj->retribution_type_id}]: " . $e->getMessage() . "\n";
        }
    }
}

echo "✅ Sync finished! Total synced: {$syncedCount}\n";

// 2. Bonus: Audit created_by for Orphans
echo "\n🔍 Auditing orphaned handlers...\n";
$orphansCount = DB::table('taxpayers')->whereNull('created_by')->count();
if ($orphansCount > 0) {
    echo "⚒️ Found {$orphansCount} orphans. Assigning to Default Petugas (ID: 56)...\n";
    DB::table('taxpayers')->whereNull('created_by')->update([
        'created_by' => 56,
        'updated_at' => now()
    ]);
    echo "✅ Orphanes handled.\n";
} else {
    echo "✨ No orphaned handlers found.\n";
}
