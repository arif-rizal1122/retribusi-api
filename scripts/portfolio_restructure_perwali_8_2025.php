<?php

/**
 * Migration Script: Portfolio Restructure (Perwali Baubau 8/2025)
 * 
 * Paradigma: 
 * Wilayah I (ID 16) -> Aset & Properti (PBB, BPHTB, Reklame, MBLB, Walet, Opsen)
 * Wilayah II (ID 17) -> Konsumsi & Self-Assessment (PBJT, PAT, Retribusi Aset)
 */

use App\Models\RetributionType;
use App\Models\RetributionClassification;
use App\Models\TaxObject;
use Illuminate\Support\Facades\DB;

try {
    DB::beginTransaction();

    echo "🚀 Memulai Restrukturisasi Portofolio (Perwali 8/2025)...\n";

    // 1. Rename Wilayah Parents
    $w1 = RetributionType::find(16);
    if ($w1) {
        $w1->update(['name' => 'Pendapatan Wilayah I (Aset & Properti)']);
        echo "✅ Wilayah I diubah menjadi: {$w1->name}\n";
    }

    $w2 = RetributionType::find(17);
    if ($w2) {
        $w2->update(['name' => 'Pendapatan Wilayah II (Konsumsi & Self-Assessment)']);
        echo "✅ Wilayah II diubah menjadi: {$w2->name}\n";
    }

    // 2. Define Portfolio Mapping
    $portfolio = [
        'W1' => ['PBB', 'BPHTB', 'Reklame', 'Sarang Burung Walet', 'MBLB', 'Opsen'],
        'W2' => ['PBJT', 'Air Tanah', 'Makan dan Minum', 'Perhotelan', 'Parkir', 'Hiburan', 'Listrik', 'Lapak', 'Pasar', 'Persampahan']
    ];

    // 3. Automated Re-parenting & Consolidation logic
    $classifications = RetributionClassification::whereIn('retribution_type_id', [16, 17])->get();

    foreach ($classifications as $class) {
        $name = $class->name;
        $targetType = null;

        foreach ($portfolio['W1'] as $keyword) {
            if (stripos($name, $keyword) !== false) {
                $targetType = 16;
                break;
            }
        }

        if (!$targetType) {
            foreach ($portfolio['W2'] as $keyword) {
                if (stripos($name, $keyword) !== false) {
                    $targetType = 17;
                    break;
                }
            }
        }

        if ($targetType && $class->retribution_type_id != $targetType) {
            echo "🔄 Memindahkan '{$name}' ke Wilayah " . ($targetType == 16 ? "I" : "II") . "\n";
            $class->update(['retribution_type_id' => $targetType]);
        }
    }

    // 4. Consolidate Exact Duplicates (Merge W1/W2 names)
    // Example: "PBB-P2" existing in both W1 and W2.
    $duplicates = RetributionClassification::select('name', DB::raw('count(*) as count'))
        ->groupBy('name')
        ->having('count', '>', 1)
        ->get();

    foreach ($duplicates as $dup) {
        $name = $dup->name;
        $items = RetributionClassification::where('name', $name)->orderBy('id')->get();
        
        $survivor = $items->first();
        $victims = $items->slice(1);

        foreach ($victims as $victim) {
            echo "🧹 Menggabungkan '{$name}' (ID {$victim->id}) ke Survivor (ID {$survivor->id})...\n";
            
            // Move tax objects
            $affected = TaxObject::where('retribution_classification_id', $victim->id)
                ->update(['retribution_classification_id' => $survivor->id]);
            
            echo "   📦 Objek yang dipindah: {$affected}\n";
            
            // Delete the victim (soft/hard depending on model)
            $victim->delete();
        }
    }

    DB::commit();
    echo "✨ Restrukturisasi Berhasil. Sistem kini sinkron dengan Perwali 8/2025.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
