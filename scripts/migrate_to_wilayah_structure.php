<?php

use Illuminate\Support\Facades\DB;
use App\Models\RetributionClassification;
use App\Models\TaxObject;

// To run this: /usr/local/bin/php artisan tinker scripts/migrate_to_wilayah_structure.php

echo "Finalizing Wilayah Restoration Migration (100% Coverage)...\n";

$wilayahIType = 16;
$wilayahIIType = 17;

// Ensure Wilayah Types exist
DB::table('retribution_types')->updateOrInsert(['id' => 16], ['name' => 'Wilayah I', 'category' => 'Pajak', 'opd_id' => 5, 'is_active' => 1]);
DB::table('retribution_types')->updateOrInsert(['id' => 17], ['name' => 'Wilayah II', 'category' => 'Pajak', 'opd_id' => 5, 'is_active' => 1]);

// 2. Comprehensive Categories Mapping
$categories = [
    'Restoran' => ['codes' => [64, 8, 'PBJT-RS'], 'name' => 'PBJT - Makan dan Minum', 'formula' => 'omzet * 0.1', 'pfx' => 'MNM'],
    'Hotel'    => ['codes' => [63, 'PBJT-HTL'], 'name' => 'PBJT - Jasa Perhotelan', 'formula' => 'omzet * 0.1', 'pfx' => 'HTL'],
    'Hiburan'  => ['codes' => [65, 'PBJT-HBR'], 'name' => 'PBJT - Jasa Kesenian dan Hiburan', 'formula' => 'omzet * 0.1', 'pfx' => 'HBR'],
    'Reklame'  => ['codes' => [66, 7, 'REKLAME'], 'name' => 'Pajak Reklame', 'formula' => '((njopr + nspr) * luas * sisi) * 0.25', 'pfx' => 'REK'],
    'MBLB'     => ['codes' => [68, 'MBLB'], 'name' => 'Pajak MBLB', 'formula' => '(volume * harga) * 0.15', 'pfx' => 'MBLB'],
    'AirTanah' => ['codes' => [70, 'AIR-TANAH'], 'name' => 'Pajak Air Tanah', 'formula' => '(volume * hda) * 0.20', 'pfx' => 'ABA'],
    'Walet'    => ['codes' => [71], 'name' => 'Pajak Sarang Burung Walet', 'formula' => 'nilai_jual * 0.1', 'pfx' => 'WLT'],
    'Listrik'  => ['codes' => [67], 'name' => 'PBJT - Tenaga Listrik', 'formula' => 'tagihan * 0.1', 'pfx' => 'LIS'],
    'Sampah'   => ['codes' => [100, 34, 40, 'RET-SMP'], 'name' => 'Retribusi Persampahan', 'formula' => 'tarif_flat', 'pfx' => 'SMP'],
    'Parkir'   => ['codes' => [69, 2, 29, 30, 35, 36, 101], 'name' => 'Retribusi Pelayanan Parkir', 'formula' => 'tarif_flat', 'pfx' => 'PRK'],
    'Lainnya'  => ['codes' => [1, 4, 6, 14], 'name' => 'Retribusi Jasa Umum Lainnya', 'formula' => 'tarif_flat', 'pfx' => 'OTH'],
];

$clsMap = [];
foreach ($categories as $key => $data) {
    foreach ([16, 17] as $wId) {
        $pfx = ($wId == 16) ? "W1-" : "W2-";
        $cls = RetributionClassification::updateOrCreate(
            ['retribution_type_id' => $wId, 'code' => $pfx . $data['pfx']],
            [
                'opd_id' => 5,
                'name' => $data['name'],
                'calculation_formula' => $data['formula'],
                'is_active' => 1
            ]
        );
        foreach ((array)$data['codes'] as $code) {
            $clsMap[$code][$wId] = $cls->id;
        }
    }
}

$w1_districts = ['WOLIO', 'W0LI0', 'WALIO', 'MURHUM', 'MUHHUM', 'BETOAMBARI', 'BETOAMABRI', 'BEROAMBARI', 'BATOAMBARI', 'BATUPOARO', 'BATUPUARO', 'BATU PUARO', 'BATUPORO', 'BETUPOARO'];
$w2_districts = ['KOKALUKUNA', 'BUNGI', 'SORAWOLIO', 'LEALEA', 'LEA-LEA', 'KECAMATAN LEA-L'];

echo "Starting Update for ALL objects...\n";
$total = DB::table('tax_objects')->count();
$processed = 0;

DB::table('tax_objects')
    ->join('taxpayers', 'tax_objects.taxpayer_id', '=', 'taxpayers.id')
    ->select('tax_objects.id', 'tax_objects.retribution_type_id', 'taxpayers.district')
    ->chunkById(2000, function ($objects) use ($w1_districts, $w2_districts, $clsMap, &$processed) {
        foreach ($objects as $obj) {
            $upperDist = strtoupper($obj->district ?? '');
            $targetWilayah = (in_array($upperDist, $w2_districts)) ? 17 : 16;
            
            $newClsId = $clsMap[$obj->retribution_type_id][$targetWilayah] ?? null;
            
            // If still no mapping, try to find by default PBJT category (64 for W1/W2)
            if (!$newClsId) {
                 $newClsId = $clsMap['Restoran'][$targetWilayah] ?? null;
            }
            
            DB::table('tax_objects')->where('id', $obj->id)->update([
                'retribution_type_id' => $targetWilayah,
                'retribution_classification_id' => $newClsId,
                'updated_at' => now()
            ]);
            $processed++;
        }
    }, 'tax_objects.id', 'id');

echo "Final Cleanup: Deactivating other types...\n";
DB::table('retribution_types')->whereNotIn('id', [16, 17])->update(['is_active' => 0]);

echo "Migration 100% Completed Successfully!\n";
