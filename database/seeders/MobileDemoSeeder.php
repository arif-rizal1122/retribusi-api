<?php

namespace Database\Seeders;

use App\Models\Taxpayer;
use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use App\Models\TaxObject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MobileDemoSeeder extends Seeder
{
    public function run(): void
    {
        $bapenda = Opd::where('code', 'BAPENDA')->first();
        if (!$bapenda) return;

        $type = RetributionType::where('name', 'Wilayah II')->first();
        $classificationParkir = RetributionClassification::where('code', 'PBJT-PRK')->first();
        $classificationMNM = RetributionClassification::where('code', 'PBJT-MNM')->first();

        // 1. Budi (Parkir/PBB)
        $budi = Taxpayer::updateOrCreate(
            ['nik' => '1234567890123456'],
            [
                'opd_id' => $bapenda->id,
                'name' => 'Budi Santoso',
                'address' => 'Jl. Jend. Sudirman No. 10, Baubau',
                'district' => 'Wolio',
                'sub_district' => 'Bataraguru',
                'phone' => '081234567890',
                'npwpd' => 'NPWPD-BUDI123',
                'object_name' => 'Parkir Budi',
                'object_address' => 'Jl. Jend. Sudirman No. 10',
                'latitude' => -5.4640,
                'longitude' => 122.6080,
                'is_active' => true,
                'password' => Hash::make('password'),
            ]
        );

        if ($type && $classificationParkir) {
            $budi->retributionTypes()->syncWithoutDetaching([
                $type->id => ['retribution_classification_id' => $classificationParkir->id]
            ]);

            TaxObject::updateOrCreate(
                ['nop' => '1234567890123456-PRK'],
                [
                    'taxpayer_id' => $budi->id,
                    'retribution_type_id' => $type->id,
                    'retribution_classification_id' => $classificationParkir->id,
                    'opd_id' => $bapenda->id,
                    'name' => 'Parkir Budi Santoso',
                    'address' => 'Jl. Jend. Sudirman No. 10',
                    'latitude' => -5.4640,
                    'longitude' => 122.6080,
                    'status' => 'active',
                ]
            );
        }

        // 2. Ani (Kios/Sampah)
        $ani = Taxpayer::updateOrCreate(
            ['nik' => '1234567890123457'],
            [
                'opd_id' => $bapenda->id,
                'name' => 'Ani Rahayu',
                'address' => 'Jl. Dayanu Ikhsanuddin, Baubau',
                'district' => 'Murhum',
                'sub_district' => 'Baadia',
                'phone' => '081234567891',
                'npwpd' => 'NPWPD-ANI123',
                'object_name' => 'Kios Ani',
                'object_address' => 'Jl. Dayanu Ikhsanuddin',
                'latitude' => -5.4510,
                'longitude' => 122.5975,
                'is_active' => true,
                'password' => Hash::make('password'),
            ]
        );

        if ($type && $classificationMNM) {
            $ani->retributionTypes()->syncWithoutDetaching([
                $type->id => ['retribution_classification_id' => $classificationMNM->id]
            ]);

            TaxObject::updateOrCreate(
                ['nop' => '1234567890123457-MNM'],
                [
                    'taxpayer_id' => $ani->id,
                    'retribution_type_id' => $type->id,
                    'retribution_classification_id' => $classificationMNM->id,
                    'opd_id' => $bapenda->id,
                    'name' => 'RM Ani Rahayu',
                    'address' => 'Jl. Dayanu Ikhsanuddin',
                    'latitude' => -5.4510,
                    'longitude' => 122.5975,
                    'status' => 'active',
                ]
            );
        }
    }
}
