<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=============================================\n";
echo "   STG 3: FORMULA SYNCHRONIZATION (STAGING)  \n";
echo "=============================================\n";

$baseUrl = 'https://apimpad.baubaukota.go.id/api';

echo "[1/2] Mengambil Data Klasifikasi & Formula dari Staging...\n";
$formulasRes = Http::get("$baseUrl/tax-formulas");

if ($formulasRes->successful()) {
    $formulas = $formulasRes->json('data');
    echo "  -> ✅ Berhasil mengambil " . count($formulas) . " formula.\n";
    
    echo "[2/2] Menguji Simulasi Perhitungan (Simulate Tax)...\n";
    
    // Test with a sample formula if available
    if (count($formulas) > 0) {
        $sample = $formulas[0];
        echo "  -> Menguji Formula: " . $sample['name'] . " (" . $sample['calculation_formula'] . ")\n";
        
        $payload = [
            'classification_id' => $sample['id'],
            'variables' => [
                'luas' => 100,
                'tarif' => 5000,
                'total' => 500000,
                'price' => 1000,
                'quantity' => 5,
                'omzet' => 1000000,
                'tariff' => 0.1
            ]
        ];
        
        $simRes = Http::post("$baseUrl/simulate-tax", $payload);
        
        if ($simRes->successful()) {
            echo "  -> ✅ Hasil Simulasi: " . $simRes->json('formatted') . " (Raw: " . $simRes->json('result') . ")\n";
        } else {
            echo "  -> ❌ Gagal Simulasi! HTTP: " . $simRes->status() . " - " . $simRes->body() . "\n";
        }
    } else {
        echo "  -> ⚠️ SKIP: Tidak ada formula yang tersedia di database Staging untuk diuji.\n";
    }
} else {
    echo "  -> ❌ Gagal mengambil formula dari Staging (HTTP: " . $formulasRes->status() . ").\n";
}

echo "\n---------------------------------------------\n";
echo "   STATUS STG 3: SELESAI\n";
echo "---------------------------------------------\n\n";
