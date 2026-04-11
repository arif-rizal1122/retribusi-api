<?php

use Illuminate\Support\Facades\DB;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use App\Models\TaxObject;

// To run: /usr/local/bin/php artisan tinker scripts/migrate_to_wilayah_complex_hierarchy.php

echo "Starting Wilayah-Centric Restructuring (Mass Migration)...\n";

$wilayah1Id = 16;
$wilayah2Id = 17;

// District Mapping
$w1Districts = ['Wolio', 'Murhum', 'Betoambari', 'Batupoaro'];
// Any other district is W2.

// 1. Clone Tax Types to Classifications under Wilayahs
$oldTypes = RetributionType::where('id', '>', 60)
    ->whereNotIn('id', [$wilayah1Id, $wilayah2Id])
    ->get();

$classMap = []; // [old_type_id][wilayah_id] => new_class_id

foreach ($oldTypes as $type) {
    echo "Processing Category: {$type->name}...\n";
    
    // Create for Wilayah I
    $w1Class = RetributionClassification::updateOrCreate(
        ['retribution_type_id' => $wilayah1Id, 'name' => $type->name],
        [
            'opd_id' => $type->opd_id,
            'code' => $type->code,
            'icon' => $type->icon,
            'calculation_formula' => $type->calculation_formula,
            'form_schema' => $type->form_schema,
            'is_self_assessment' => 1
        ]
    );
    $classMap[$type->id][$wilayah1Id] = $w1Class->id;

    // Create for Wilayah II
    $w2Class = RetributionClassification::updateOrCreate(
        ['retribution_type_id' => $wilayah2Id, 'name' => $type->name],
        [
            'opd_id' => $type->opd_id,
            'code' => $type->code,
            'icon' => $type->icon,
            'calculation_formula' => $type->calculation_formula,
            'form_schema' => $type->form_schema,
            'is_self_assessment' => 1
        ]
    );
    $classMap[$type->id][$wilayah2Id] = $w2Class->id;
}

// 2. Mass Re-parenting of 76,000+ Objects
echo "Re-parenting Tax Objects (this may take a minute)...\n";

// We process in chunks to avoid memory issues
TaxObject::with('taxpayer')->chunk(5000, function ($objects) use ($wilayah1Id, $wilayah2Id, $w1Districts, $classMap) {
    foreach ($objects as $obj) {
        $oldTypeId = $obj->retribution_type_id;
        
        // Skip if already shifted to Wilayah 1/2
        if ($oldTypeId == $wilayah1Id || $oldTypeId == $wilayah2Id) continue;
        if (!isset($classMap[$oldTypeId])) continue;

        $district = $obj->taxpayer->district ?? '';
        $targetWilayahId = in_array($district, $w1Districts) ? $wilayah1Id : $wilayah2Id;
        
        $obj->retribution_type_id = $targetWilayahId;
        $obj->retribution_classification_id = $classMap[$oldTypeId][$targetWilayahId];
        $obj->save();
    }
});

echo "Re-parenting User Assignments...\n";
// 3. User Assignments Sync
$assignments = DB::table('user_retribution_assignments')->get();
foreach ($assignments as $as) {
    if (isset($classMap[$as->retribution_type_id])) {
        // Assign to BOTH Wilayahs to ensure no access loss, or use mapping if available
        // For safety, we'll map to W1 as default but the system usually handles multiple
        DB::table('user_retribution_assignments')->where('id', $as->id)->update([
            'retribution_type_id' => $wilayah1Id,
            'retribution_classification_id' => $classMap[$as->retribution_type_id][$wilayah1Id]
        ]);
        
        // Optional: Link to W2 as well if they were city-wide
        DB::table('user_retribution_assignments')->insert([
            'user_id' => $as->user_id,
            'retribution_type_id' => $wilayah2Id,
            'retribution_classification_id' => $classMap[$as->retribution_type_id][$wilayah2Id],
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}

echo "Re-parenting Taxpayer Links...\n";
// 4. Taxpayer Links Sync
$wpLinks = DB::table('taxpayer_retribution_type')->get();
foreach ($wpLinks as $link) {
    if (isset($classMap[$link->retribution_type_id])) {
        // Find which wilayah based on taxpayer
        $tp = DB::table('taxpayers')->where('id', $link->taxpayer_id)->first();
        $targetWilayahId = in_array($tp->district ?? '', $w1Districts) ? $wilayah1Id : $wilayah2Id;
        
        DB::table('taxpayer_retribution_type')->where('id', $link->id)->update([
            'retribution_type_id' => $targetWilayahId,
            'retribution_classification_id' => $classMap[$link->retribution_type_id][$targetWilayahId]
        ]);
    }
}

// 5. Cleanup
echo "Deactivating old Level 1 Tax Types...\n";
DB::table('retribution_types')->where('id', '>', 60)
    ->whereNotIn('id', [$wilayah1Id, $wilayah2Id])
    ->update(['deleted_at' => now()]);

echo "Restructuring Completed Successfully!\n";
