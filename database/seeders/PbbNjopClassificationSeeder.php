<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PbbNjopClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            // NJOP Bumi (Tanah) - Lampiran I
            ['type' => 'bumi', 'class_code' => '001', 'min_value' => 67390000, 'max_value' => 69700000, 'njop_value' => 68545000],
            ['type' => 'bumi', 'class_code' => '002', 'min_value' => 65120000, 'max_value' => 67390000, 'njop_value' => 66255000],
            ['type' => 'bumi', 'class_code' => '003', 'min_value' => 62890000, 'max_value' => 65120000, 'njop_value' => 64005000],
            ['type' => 'bumi', 'class_code' => '010', 'min_value' => 48400000, 'max_value' => 50350000, 'njop_value' => 49375000],
            ['type' => 'bumi', 'class_code' => '050', 'min_value' => 3200000, 'max_value' => 3550000, 'njop_value' => 3375000],
            ['type' => 'bumi', 'class_code' => '100', 'min_value' => 0, 'max_value' => 170000, 'njop_value' => 140000],
            
            // NJOP Bangunan - Lampiran II
            ['type' => 'bangunan', 'class_code' => '001', 'min_value' => 14700000, 'max_value' => 15800000, 'njop_value' => 15250000],
            ['type' => 'bangunan', 'class_code' => '002', 'min_value' => 13600000, 'max_value' => 14700000, 'njop_value' => 14150000],
            ['type' => 'bangunan', 'class_code' => '020', 'min_value' => 1366000, 'max_value' => 1666000, 'njop_value' => 1516000],
            ['type' => 'bangunan', 'class_code' => '040', 'min_value' => 0, 'max_value' => 52000, 'njop_value' => 50000],
        ];

        foreach ($data as $row) {
            \App\Models\PbbNjopClassification::updateOrCreate(
                ['type' => $row['type'], 'class_code' => $row['class_code']],
                $row
            );
        }
    }
}
