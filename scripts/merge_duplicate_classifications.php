<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$mapping = [
    178 => 186, // PBB-P2
    180 => 187, // BPHTB
    182 => 191, // Opsen
    144 => 188, // Reklame
    130 => 189, // MBLB
    152 => 190, // Walet
    140 => 193, // Hotel
    138 => 192, // Makan
    142 => 194, // Hiburan
    154 => 196, // Listrik
    146 => 197, // Air Tanah
    148 => 198, // Sampah
    150 => 199, // Parkir Tepi
    184 => 200, // PKD
];

$icons = [
    186 => 'assets/icons/classifications/property_set.png',
    187 => 'assets/icons/classifications/property_set.png',
    191 => 'assets/icons/classifications/utility_set.png',
    188 => 'assets/icons/classifications/utility_set.png',
    189 => 'assets/icons/classifications/resource_set.png',
    190 => 'assets/icons/classifications/resource_set.png',
    193 => 'assets/icons/classifications/consumption_set.png',
    192 => 'assets/icons/classifications/consumption_set.png',
    194 => 'assets/icons/classifications/consumption_set.png',
    196 => 'assets/icons/classifications/utility_set.png',
    197 => 'assets/icons/classifications/resource_set.png',
    198 => 'assets/icons/classifications/utility_set.png',
    199 => 'assets/icons/classifications/consumption_set.png',
    200 => 'assets/icons/classifications/consumption_set.png',
    195 => 'assets/icons/classifications/consumption_set.png', // Hiburan Malam
];

DB::transaction(function () use ($mapping, $icons) {
    echo "Starting Merge and Sync Process...\n";

    foreach ($mapping as $oldId => $newId) {
        echo "Merging ID $oldId into $newId...\n";

        // Update Tax Objects
        $countObj = DB::table('tax_objects')->where('retribution_classification_id', $oldId)->update(['retribution_classification_id' => $newId]);
        echo "- Updated $countObj Tax Objects\n";

        // Update Bills
        $countBill = DB::table('bills')->where('retribution_classification_id', $oldId)->update(['retribution_classification_id' => $newId]);
        echo "- Updated $countBill Bills\n";

        // Delete Old Classification
        DB::table('retribution_classifications')->where('id', $oldId)->delete();
        echo "- Deleted Legacy ID $oldId\n";
    }

    echo "\nUpdating Icons and Self-Assessment Flags...\n";
    foreach ($icons as $id => $path) {
        $cls = DB::table('retribution_classifications')->where('id', $id)->first();
        if ($cls) {
            $isSelf = ($cls->retribution_type_id == 17 && !str_starts_with($cls->code, 'RET-'));
            DB::table('retribution_classifications')->where('id', $id)->update([
                'icon' => $path,
                'is_self_assessment' => $isSelf
            ]);
            echo "- Updated ID $id: $path (Self: " . ($isSelf ? 'YES' : 'NO') . ")\n";
        }
    }

    echo "\nCleanup Finished.\n";
});
