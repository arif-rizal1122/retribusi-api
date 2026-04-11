<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Script: unified_tax_classification_cleanup.php
 * Purpose: Restructure tax classifications to Wilson I/II and eliminate duplicates.
 * Based on: Perwali 8/2025 and Perda 1/2024.
 */

$mapping = [
    // Survivor Master ID => [Trash IDs to migrate from]
    138 => [122, 123, 158], // PBJT Makan dan Minum -> Wilayah II
    140 => [124, 125, 159], // PBJT Jasa Perhotelan -> Wilayah II
    142 => [126, 127, 160, 173], // PBJT Kesenian dan Hiburan -> Wilayah II
    144 => [128, 129, 161], // Pajak Reklame -> Wilayah I
    146 => [132, 133],      // Pajak Air Tanah -> Wilayah II
    148 => [134, 135, 166], // Retribusi Persampahan -> Wilayah II
    150 => [136, 137, 167], // Retribusi Pelayanan Parkir -> Wilayah II
    178 => [170],           // PBB-P2 -> Wilayah I
];

$wilayah1_ids = [130, 144, 152, 178, 180, 182, 156]; // Aset & Properti
$wilayah2_ids = [138, 140, 142, 146, 148, 150, 154, 172, 184]; // Konsumsi & Service

$legacy_type_ids = [63, 64, 65, 66, 67, 69, 71, 72, 100, 101];

DB::beginTransaction();

try {
    echo "🚀 Memulai Restrukturisasi Klasifikasi Pajak...\n";

    // 1. Move Tax Objects to Master IDs
    foreach ($mapping as $masterId => $trashIds) {
        $count = DB::table('tax_objects')
            ->whereIn('retribution_classification_id', $trashIds)
            ->update(['retribution_classification_id' => $masterId]);
        
        if ($count > 0) {
            echo "✅ Memindahkan $count objek ke Master ID $masterId.\n";
        }
    }

    // 2. Set Parent for Wilayah I (Aset)
    $w1_count = DB::table('retribution_classifications')
        ->whereIn('id', $wilayah1_ids)
        ->update(['retribution_type_id' => 16]);
    echo "✅ Menyeimbangkan Wilayah I (Aset): $w1_count Klasifikasi.\n";

    // 3. Set Parent for Wilayah II (Konsumsi)
    $w2_count = DB::table('retribution_classifications')
        ->whereIn('id', $wilayah2_ids)
        ->update(['retribution_type_id' => 17]);
    echo "✅ Menyeimbangkan Wilayah II (Konsumsi): $w2_count Klasifikasi.\n";

    // 4. Cleanup Trash Classifications
    $all_trash_ids = [];
    foreach ($mapping as $list) {
        $all_trash_ids = array_merge($all_trash_ids, $list);
    }
    // Add known empty trash IDs
    $all_trash_ids[] = 161; // Duplicate Reklame

    $del_count = DB::table('retribution_classifications')
        ->whereIn('id', $all_trash_ids)
        ->delete();
    echo "🗑️ Menghapus $del_count Klasifikasi duplikat.\n";

    // 5. Cleanup Legacy Types
    $type_del_count = DB::table('retribution_types')
        ->whereIn('id', $legacy_type_ids)
        ->delete();
    echo "🗑️ Menghapus $type_del_count Tipe Retribusi warisan.\n";

    DB::commit();
    echo "🏁 Restrukturisasi Selesai Secara Sempurna.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
