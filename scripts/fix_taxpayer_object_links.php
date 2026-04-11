<?php

/**
 * Final, ultimate script to fix orphan taxpayers by restoring their tax objects.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🚀 Starting Final Restoration (Bapenda ID: 5)...\n";

$mapping = [
    'PATDA_HOTEL_PROFIL' => 193,
    'PATDA_RESTORAN_PROFIL' => 192,
    'PATDA_HIBURAN_PROFIL' => 194,
    'PATDA_REKLAME_PROFIL' => 188,
    'PATDA_MINERAL_PROFIL' => 189,
    'PATDA_AIRBAWAHTANAH_PROFIL' => 197,
    'PATDA_WALET_PROFIL' => 190,
    'PATDA_JALAN_PROFIL' => 196,
    'PATDA_PARKIR_PROFIL' => 172,
];

// Get orphans (Taxpayers with NO Objects)
$orphans = DB::table('taxpayers')
    ->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
              ->from('tax_objects')
              ->whereColumn('tax_objects.taxpayer_id', 'taxpayers.id');
    })
    ->where('name', '!=', 'WP PATDA TEST') // Skip test data
    ->get();

echo "📝 Processing " . $orphans->count() . " orphans.\n";

$restoredCount = 0;

foreach ($orphans as $orphan) {
    if (empty($orphan->name)) continue;

    $name = trim($orphan->name);
    $npwpd = trim($orphan->npwpd ?? '');
    
    echo "🔍 WP [{$orphan->id}]: {$name} ({$npwpd})\n";

    $match = null;
    $targetTable = null;
    $targetClassId = null;

    foreach ($mapping as $table => $classId) {
        // Try exact NPWPD first
        if (!empty($npwpd) && $npwpd !== 'NULL') {
            $match = DB::connection('mysql_legacy')->table($table)
                ->where('CPM_NPWPD', $npwpd)
                ->first();
        }

        // Try fuzzy name match
        if (!$match) {
            $match = DB::connection('mysql_legacy')->table($table)
                ->where('CPM_NAMA_OP', 'LIKE', '%' . $name . '%')
                ->first();
        }

        if ($match) {
            $targetTable = $table;
            $targetClassId = $classId;
            break;
        }
    }

    if ($match) {
        echo "   ✅ Found match in {$targetTable}! Object: {$match->CPM_NAMA_OP}\n";
        
        $typeId = in_array($targetClassId, [186, 187, 188, 189, 190, 191]) ? 16 : 17;

        try {
            DB::table('tax_objects')->insert([
                'taxpayer_id' => $orphan->id,
                'retribution_type_id' => $typeId,
                'retribution_classification_id' => $targetClassId,
                'opd_id' => 5, // BAPENDA
                'name' => $match->CPM_NAMA_OP,
                'address' => $match->CPM_ALAMAT_OP ?? 'Migrasi',
                'nop' => $match->CPM_NOP ?? null,
                'status' => 'approved',
                'transaction_type' => 'migrasi',
                'metadata' => json_encode(['legacy_id' => $match->CPM_ID, 'sync_date' => date('Y-m-d H:i:s')]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "   💰 RESTORED!\n";
            $restoredCount++;
        } catch (\Exception $e) {
            echo "   ❌ ERROR: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ❌ No legacy profile found.\n";
    }
}

echo "\n🏁 Finished! Restored: {$restoredCount}\n";
