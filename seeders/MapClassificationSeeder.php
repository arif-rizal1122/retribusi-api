<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class MapClassificationSeeder extends Seeder
{
    public function run(): void
    {
        $bapenda = Opd::where('code', 'BAPENDA')->first();
        if (!$bapenda) return;

        $retributionType = RetributionType::where('name', 'PBJT')->first();
        if (!$retributionType) return;

        // Ensure Classifications exist for testing
        $classifications = [
            'Hotel Kelas 1' => ['code' => 'H-K1', 'lat' => -5.465, 'lng' => 122.605],
            'Restoran Kelas 2' => ['code' => 'R-K2', 'lat' => -5.475, 'lng' => 122.595],
            'Parkir Kelas 3' => ['code' => 'P-K3', 'lat' => -5.480, 'lng' => 122.610],
            'Hiburan Kelas 1' => ['code' => 'HBR-K1', 'lat' => -5.460, 'lng' => 122.590],
        ];

        foreach ($classifications as $name => $data) {
            $cls = RetributionClassification::updateOrCreate(
                ['retribution_type_id' => $retributionType->id, 'name' => $name],
                [
                    'opd_id' => $bapenda->id,
                    'code' => $data['code'],
                    'form_schema' => [],
                    'requirements' => [],
                ]
            );

            // Create Zones near the classification coordinates
            Zone::updateOrCreate(
                ['name' => 'Zona ' . $name, 'opd_id' => $bapenda->id],
                [
                    'retribution_type_id' => $retributionType->id,
                    'retribution_classification_id' => $cls->id,
                    'code' => 'Z-' . $data['code'],
                    'latitude' => $data['lat'],
                    'longitude' => $data['lng'],
                ]
            );
        }
    }
}
