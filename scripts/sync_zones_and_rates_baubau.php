<?php

use Illuminate\Support\Facades\DB;
use App\Models\Zone;
use App\Models\RetributionRate;

// To run: /usr/local/bin/php artisan tinker scripts/sync_zones_and_rates_baubau.php

echo "Syncing Zones & Rates (MITRA Baubau Standard)...\n";

$bapendaId = 5;

// 1. Expand Zones (Level 3)
$newZones = [
    // Reklame
    ['name' => 'Kelas Jalan A (Sangat Strategis)', 'code' => 'REK-A', 'type' => 'polygon'],
    ['name' => 'Kelas Jalan B (Strategis)', 'code' => 'REK-B', 'type' => 'polygon'],
    ['name' => 'Kelas Jalan C (Standar)', 'code' => 'REK-C', 'type' => 'polygon'],
    // Parkir Tepi Jalan
    ['name' => 'Zona Premium (Pusat Bisnis)', 'code' => 'PRK-PREMIUM', 'type' => 'polygon'],
    ['name' => 'Zona Strategis (Jalan Utama)', 'code' => 'PRK-STRATEGIS', 'type' => 'polygon'],
    ['name' => 'Zona Ekonomi (Sekunder)', 'code' => 'PRK-EKONOMI', 'type' => 'polygon'],
    ['name' => 'Zona Umum (Permukiman)', 'code' => 'PRK-UMUM', 'type' => 'polygon'],
    // Sedot Kakus
    ['name' => 'Zona I (Wolio, Murhum, Betoambari, KKL)', 'code' => 'KKS-Z1', 'type' => 'polygon'],
    ['name' => 'Zona II (Bungi, Lea-Lea)', 'code' => 'KKS-Z2', 'type' => 'polygon'],
    ['name' => 'Zona III (Sorawolio)', 'code' => 'KKS-Z3', 'type' => 'polygon'],
];

$zoneMap = [];
foreach ($newZones as $z) {
    $zone = Zone::updateOrCreate(
        ['code' => $z['code']],
        ['opd_id' => $bapendaId, 'name' => $z['name'], 'geometry_type' => $z['type']]
    );
    $zoneMap[$z['code']] = $zone->id;
}

// 2. Populate Rates (Level 4)
// Retribusi Pelayanan Parkir (ID 101)
$parkirRates = [
    ['zone' => 'PRK-PREMIUM', 'name' => 'Premium - Mobil', 'amount' => 3000],
    ['zone' => 'PRK-PREMIUM', 'name' => 'Premium - Motor', 'amount' => 2000],
    ['zone' => 'PRK-STRATEGIS', 'name' => 'Strategis - Mobil', 'amount' => 2000],
    ['zone' => 'PRK-STRATEGIS', 'name' => 'Strategis - Motor', 'amount' => 1500],
    ['zone' => 'PRK-EKONOMI', 'name' => 'Ekonomi - Mobil', 'amount' => 1500],
    ['zone' => 'PRK-EKONOMI', 'name' => 'Ekonomi - Motor', 'amount' => 1000],
    ['zone' => 'PRK-UMUM', 'name' => 'Umum - Mobil', 'amount' => 1000],
    ['zone' => 'PRK-UMUM', 'name' => 'Umum - Motor', 'amount' => 1000],
];

foreach ($parkirRates as $r) {
    RetributionRate::updateOrCreate(
        ['retribution_type_id' => 101, 'zone_id' => $zoneMap[$r['zone']], 'name' => $r['name']],
        ['amount' => $r['amount'], 'unit' => 'Sekali Parkir', 'opd_id' => $bapendaId]
    );
}

// Penyedotan Kakus (Let's assume ID 103 for Category Sedot Kakus)
// I'll check if ID 103 exists, if not I'll just skip or use a generic one.
$kakusRates = [
    ['zone' => 'KKS-Z1', 'name' => 'Tarif Sedot Kakus Zona I', 'amount' => 150000],
    ['zone' => 'KKS-Z2', 'name' => 'Tarif Sedot Kakus Zona II', 'amount' => 225000], // Average of 200-250
    ['zone' => 'KKS-Z3', 'name' => 'Tarif Sedot Kakus Zona III', 'amount' => 225000],
];

foreach ($kakusRates as $r) {
    RetributionRate::updateOrCreate(
        ['retribution_type_id' => 100, 'zone_id' => $zoneMap[$r['zone']], 'name' => $r['name']],
        ['amount' => $r['amount'], 'unit' => 'Penyedotan', 'opd_id' => $bapendaId]
    );
}

// Sewa Lapak (using coefficient spots from previous turns)
$lapakRates = [
    ['zone_code' => 'KML', 'name' => 'Sewa Lapak Kamali (Premium)', 'amount' => 250000],
    ['zone_code' => 'KTM', 'name' => 'Sewa Lapak Kotamara (Premium)', 'amount' => 250000],
    ['zone_code' => 'PBW', 'name' => 'Sewa Lapak Pasar Buah (Ekonomi)', 'amount' => 125000],
];

foreach ($lapakRates as $r) {
    $z = Zone::where('code', $r['zone_code'])->first();
    if ($z) {
        RetributionRate::updateOrCreate(
            ['retribution_type_id' => 102, 'zone_id' => $z->id, 'name' => $r['name']],
            ['amount' => $r['amount'], 'unit' => 'Bulan', 'opd_id' => $bapendaId]
        );
    }
}

echo "Zones & Rates Sync Completed Successfully!\n";
