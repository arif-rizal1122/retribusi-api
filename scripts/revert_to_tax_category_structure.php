<?php

use Illuminate\Support\Facades\DB;
use App\Models\RetributionType;
use App\Models\RetributionClassification;

// To run this: /usr/local/bin/php artisan tinker scripts/revert_to_tax_category_structure.php

echo "Reverting to Tax-Category Hierarchy & Restoring Icons...\n";

$bapendaId = 5;

// 1. Define Standard Types with Icons
$icons = [
    'standard' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg',
    'reklame'  => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855480/retribusi/icons/airqm7ydazqqpsqezlrv.jpg',
    'mblb'     => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855474/retribusi/icons/pl1ag8vgja8jwzabaavc.jpg',
    'retribusi' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855483/retribusi/icons/mqbtlhf4modvik6ikhvi.jpg',
];

$standardTypes = [
    63 => ['name' => 'PBJT - Jasa Perhotelan', 'cat' => 'Pajak', 'icon' => $icons['standard']],
    64 => ['name' => 'PBJT - Makan dan Minum', 'cat' => 'Pajak', 'icon' => $icons['standard']],
    65 => ['name' => 'PBJT - Jasa Kesenian dan Hiburan', 'cat' => 'Pajak', 'icon' => $icons['standard']],
    66 => ['name' => 'Pajak Reklame', 'cat' => 'Pajak', 'icon' => $icons['reklame']],
    67 => ['name' => 'PBJT - Tenaga Listrik', 'cat' => 'Pajak', 'icon' => $icons['standard']],
    68 => ['name' => 'Pajak MBLB', 'cat' => 'Pajak', 'icon' => $icons['mblb']],
    69 => ['name' => 'PBJT - Jasa Parkir', 'cat' => 'Pajak', 'icon' => $icons['standard']],
    70 => ['name' => 'Pajak Air Tanah', 'cat' => 'Pajak', 'icon' => $icons['standard']],
    71 => ['name' => 'Pajak Sarang Burung Walet', 'cat' => 'Pajak', 'icon' => $icons['standard']],
    100 => ['name' => 'Retribusi Persampahan', 'cat' => 'Retribusi', 'icon' => $icons['retribusi']],
    101 => ['name' => 'Retribusi Pelayanan Parkir', 'cat' => 'Retribusi', 'icon' => $icons['retribusi']],
    102 => ['name' => 'Retribusi PKD (Kios/Pasar)', 'cat' => 'Retribusi', 'icon' => $icons['retribusi']],
];

foreach ($standardTypes as $id => $info) {
    DB::table('retribution_types')->updateOrInsert(
        ['id' => $id],
        [
            'opd_id' => $bapendaId,
            'name' => $info['name'],
            'category' => $info['cat'],
            'icon' => $info['icon'],
            'is_active' => 1,
            'updated_at' => now()
        ]
    );
}

// 2. Setup Standard Classifications
$categories = [
    'MNM' => ['type_id' => 64, 'name' => 'Restoran dan Rumah Makan', 'code' => 'PBJT-RS', 'formula' => 'omzet * 0.1'],
    'HTL' => ['type_id' => 63, 'name' => 'Hotel dan Penginapan', 'code' => 'PBJT-HTL', 'formula' => 'omzet * 0.1'],
    'HBR' => ['type_id' => 65, 'name' => 'Kesenian dan Hiburan Umum', 'code' => 'PBJT-HBR', 'formula' => 'omzet * 0.1'],
    'REK' => ['type_id' => 66, 'name' => 'Reklame Papan/Bilboard', 'code' => 'REKLAME', 'formula' => '((njopr + nspr) * luas * sisi) * 0.25'],
    'MBLB' => ['type_id' => 68, 'name' => 'Galian C / MBLB', 'code' => 'MBLB', 'formula' => '(volume * harga) * 0.15'],
    'ABA' => ['type_id' => 70, 'name' => 'Pengambilan Air Tanah', 'code' => 'AIR-TANAH', 'formula' => '(volume * hda) * 0.20'],
    'WLT' => ['type_id' => 71, 'name' => 'Pajak Sarang Burung Walet', 'code' => 'WALET', 'formula' => 'nilai_jual * 0.1'],
    'LIS' => ['type_id' => 67, 'name' => 'PBJT - Tenaga Listrik', 'code' => 'PBJT-LIS', 'formula' => 'tagihan * 0.1'],
    'SMP' => ['type_id' => 100, 'name' => 'Retribusi Persampahan General', 'code' => 'RET-SMP', 'formula' => 'tarif_flat'],
    'PRK' => ['type_id' => 101, 'name' => 'Retribusi Pelayanan Parkir General', 'code' => 'RET-PRK', 'formula' => 'tarif_flat'],
    'PKD' => ['type_id' => 102, 'name' => 'Retribusi PKD (Pasar)', 'code' => 'RET-PKD', 'formula' => 'tarif_flat'],
    'OTH' => ['type_id' => 102, 'name' => 'Retribusi Jasa Umum Lainnya', 'code' => 'RET-OTH', 'formula' => 'tarif_flat'],
];

$clsMapByCodePrefix = []; // [W1-MNM => cls_id, W2-MNM => cls_id]
foreach ($categories as $pfx => $data) {
    $cls = RetributionClassification::updateOrCreate(
        ['retribution_type_id' => $data['type_id'], 'name' => $data['name']],
        [
            'opd_id' => $bapendaId,
            'code' => $data['code'],
            'calculation_formula' => $data['formula'],
            'is_active' => 1
        ]
    );
    // Link W1-MNM and W2-MNM (current codes) to this new standard classification
    $clsMapByCodePrefix['W1-'.$pfx] = $cls->id;
    $clsMapByCodePrefix['W2-'.$pfx] = $cls->id;
    $clsMapByCodePrefix[$pfx] = $cls->id; // Direct fallback
}

// 3. REVERSE MASS MIGRATION
echo "Processing 76,000+ objects back to Tax Types...\n";
$total = DB::table('tax_objects')->count();
$processed = 0;

DB::table('tax_objects')
    ->join('retribution_classifications', 'tax_objects.retribution_classification_id', '=', 'retribution_classifications.id')
    ->select('tax_objects.id', 'retribution_classifications.code')
    ->chunkById(5000, function ($objects) use ($clsMapByCodePrefix, $standardTypes, &$processed, $total) {
        foreach ($objects as $obj) {
            $newClsId = $clsMapByCodePrefix[$obj->code] ?? null;
            if ($newClsId) {
                // Find parent Type from standardTypes
                $typeId = null;
                foreach (DB::table('retribution_classifications')->where('id', $newClsId)->get() as $c) {
                    $typeId = $c->retribution_type_id;
                }

                if ($typeId) {
                    DB::table('tax_objects')->where('id', $obj->id)->update([
                        'retribution_type_id' => $typeId,
                        'retribution_classification_id' => $newClsId,
                        'updated_at' => now()
                    ]);
                }
            }
            $processed++;
        }
        echo "Progress: $processed / $total ...\n";
    }, 'tax_objects.id', 'id');

// 4. Cleanup
echo "Deactivating Wilayah I & II types...\n";
DB::table('retribution_types')->whereIn('id', [16, 17])->update(['is_active' => 0]);

echo "Restoration Completed Successfully!\n";
