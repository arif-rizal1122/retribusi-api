<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use App\Models\RetributionRate;
use App\Models\Opd;

class VehicleEquipmentRateSeeder extends Seeder
{
    public function run(): void
    {
        // Find or create the OPD for PU (Dinas Pekerjaan Umum)
        $opd = Opd::where('name', 'LIKE', '%Pekerjaan Umum%')->first();
        if (!$opd) {
            $opd = Opd::first(); // Fallback to first OPD
        }
        if (!$opd) {
            $this->command->error('No OPD found. Please create an OPD first.');
            return;
        }

        // Find or create the retribution type
        $type = RetributionType::firstOrCreate(
            ['name' => 'Kendaraan dan Alat Berat Kekayaan Daerah', 'opd_id' => $opd->id],
            [
                'category' => 'Retribusi Pemakaian Kekayaan Daerah',
                'base_amount' => 0,
                'unit' => 'unit',
                'is_active' => true,
            ]
        );

        // Find or create classification
        $classification = RetributionClassification::firstOrCreate(
            ['name' => 'Kendaraan dan Alat Berat', 'retribution_type_id' => $type->id],
            [
                'opd_id' => $opd->id,
                'code' => 'KAB',
                'description' => 'Pemakaian kendaraan dan alat berat milik pemerintah daerah',
            ]
        );

        $rates = [
            ['name' => 'Excavator 138 HP/Komatsu PC200-8M0', 'amount' => 330000, 'unit' => 'Per Jam'],
            ['name' => 'Excavator 138 HP/Komatsu PC200-8M0+Breaker Komatsu JTHB 210', 'amount' => 360000, 'unit' => 'Per Jam'],
            ['name' => 'Wheel Loader 123 HP/Komatsu WA200-5', 'amount' => 250000, 'unit' => 'Per Jam'],
            ['name' => 'Tandem Roller 28,4 HP/4T/Bomag BW131AD-3', 'amount' => 170000, 'unit' => 'Per Jam'],
            ['name' => 'Dum Truck Toyota Dyna 110', 'amount' => 350000, 'unit' => 'Per Hari'],
            ['name' => 'Flat Deck Truck Toyota Dyna 110', 'amount' => 350000, 'unit' => 'Per Hari'],
            ['name' => 'Dum Truck Toyota Dyna 130', 'amount' => 400000, 'unit' => 'Per Hari'],
            ['name' => 'Concrete Cutter', 'amount' => 250000, 'unit' => 'Per Hari'],
            ['name' => 'Jack Hammer', 'amount' => 250000, 'unit' => 'Per Hari'],
            ['name' => 'Digital Theodolit', 'amount' => 150000, 'unit' => 'Per Hari'],
            ['name' => 'Automatic Level Waterpass', 'amount' => 150000, 'unit' => 'Per Hari'],
            ['name' => 'Asphalt Sprayer', 'amount' => 300000, 'unit' => 'Per Hari'],
            ['name' => 'Bulldozer 80 HP/Komatsu D31Ex', 'amount' => 300000, 'unit' => 'Per Jam'],
            ['name' => 'Self Loader Truck/Trailer/Tronton (0-20 Km)', 'amount' => 450000, 'unit' => 'Per Km'],
            ['name' => 'Self Loader Truck/Trailer/Tronton (20-40 Km)', 'amount' => 900000, 'unit' => 'Per Km'],
            ['name' => 'Self Loader Truck/Trailer/Tronton (40-60 Km)', 'amount' => 1350000, 'unit' => 'Per Km'],
            ['name' => 'Self Loader Truck/Trailer/Tronton (60-80 Km)', 'amount' => 1800000, 'unit' => 'Per Km'],
            ['name' => 'Self Loader Truck/Trailer/Tronton (80-100 Km)', 'amount' => 2700000, 'unit' => 'Per Km'],
            ['name' => 'Self Loader Truck/Trailer/Tronton (Lebih dari 100 Km)', 'amount' => 2250000, 'unit' => 'Per Km'],
            ['name' => 'Backhoe Loader 92 HP/Caterpillar CAT 416F2', 'amount' => 250000, 'unit' => 'Per Jam'],
            ['name' => 'Single Drum Rollers 132 HP/13T/Bomag BW211D', 'amount' => 250000, 'unit' => 'Per Jam'],
            ['name' => 'Tandem Roller 74 HP/7,6T/Bomag BW151AD', 'amount' => 250000, 'unit' => 'Per Jam'],
        ];

        foreach ($rates as $rateData) {
            RetributionRate::firstOrCreate(
                [
                    'name' => $rateData['name'],
                    'retribution_type_id' => $type->id,
                    'retribution_classification_id' => $classification->id,
                ],
                [
                    'opd_id' => $opd->id,
                    'amount' => $rateData['amount'],
                    'unit' => $rateData['unit'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('✅ Seeded ' . count($rates) . ' vehicle/equipment rates');
    }
}
