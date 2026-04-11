<?php

use Illuminate\Support\Facades\DB;
use App\Models\RetributionClassification;
use App\Models\TaxObject;

// To run this: /usr/local/bin/php artisan tinker scripts/repair_wilayah_classifications.php

echo "Repairing 76,000+ NULL Classifications...\n";

$clss = []; // [wilayah_id => [slug => id]]
foreach ([16, 17] as $wId) {
    $clss[$wId]['MNM'] = DB::table('retribution_classifications')->where('retribution_type_id', $wId)->where('name', 'PBJT - Makan dan Minum')->value('id');
    $clss[$wId]['HTL'] = DB::table('retribution_classifications')->where('retribution_type_id', $wId)->where('name', 'PBJT - Jasa Perhotelan')->value('id');
    $clss[$wId]['REK'] = DB::table('retribution_classifications')->where('retribution_type_id', $wId)->where('name', 'Pajak Reklame')->value('id');
}

echo "Starting Heuristic Repair...\n";
$processed = 0;
$total = DB::table('tax_objects')->whereNull('retribution_classification_id')->count();

DB::table('tax_objects')
    ->whereNull('retribution_classification_id')
    ->select('id', 'name', 'retribution_type_id')
    ->chunkById(5000, function ($objects) use ($clss, &$processed, $total) {
        foreach ($objects as $obj) {
            $wId = $obj->retribution_type_id;
            $name = strtoupper($obj->name);
            $targetCls = $clss[$wId]['MNM']; // Default to Makan dan Minum (Majority)
            
            if (str_contains($name, 'HOTEL') || str_contains($name, 'WISMA') || str_contains($name, 'LOSMEN') || str_contains($name, 'PENGINAPAN')) {
                $targetCls = $clss[$wId]['HTL'];
            } elseif (str_contains($name, 'REKLAME') || str_contains($name, 'PAPAN') || str_contains($name, 'BILBOARD')) {
                $targetCls = $clss[$wId]['REK'];
            }
            
            if ($targetCls) {
                DB::table('tax_objects')->where('id', $obj->id)->update([
                    'retribution_classification_id' => $targetCls,
                    'updated_at' => now()
                ]);
            }
            $processed++;
        }
        echo "Progress: $processed / $total ...\n";
    });

echo "Repair Completed Successfully!\n";
