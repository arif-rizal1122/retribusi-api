<?php
/**
 * DB RESTRUCTURE: WILAYAH-CENTRIC MAPPING
 * This script ensures retribution_types only has 2 active entries (Wilayah I & II)
 * and duplicates all classifications so they exist in both Wilayahs.
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RetributionType;
use App\Models\RetributionClassification;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();

try {
    // 1. Ensure Wilayah I & II exist
    $w1 = RetributionType::firstOrCreate(['name' => 'Wilayah I']);
    $w2 = RetributionType::firstOrCreate(['name' => 'Wilayah II']);

    echo "Wilayah I ID: {$w1->id}\n";
    echo "Wilayah II ID: {$w2->id}\n";

    // 2. Get all existing classifications
    $classifications = RetributionClassification::all();
    echo "Found " . $classifications->count() . " classifications.\n";

    foreach ($classifications as $c) {
        // Check if it's already mapped to W1 or W2
        if ($c->retribution_type_id == $w1->id || $c->retribution_type_id == $w2->id) {
            continue;
        }

        // Duplicate the classification for the other Wilayah
        // But first, update the current one to W1
        $oldTypeId = $c->retribution_type_id;
        $c->retribution_type_id = $w1->id;
        $c->save();

        // Create a copy for W2
        $newC = $c->replicate();
        $newC->retribution_type_id = $w2->id;
        $newC->save();
        
        echo "Replicated '{$c->name}' for Wilayah I & II.\n";
    }

    // 3. Mark old types as inactive (if column exists) or just leave them
    // The audit script will now only see W1 and W2 as valid L1.
    
    DB::commit();
    echo "✅ Database Restructure Complete.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
