<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MblbVehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicles = [
            ['name' => 'Dump Truck', 'default_capacity' => 4.00],
            ['name' => 'Pick Up', 'default_capacity' => 1.50],
            ['name' => 'Truk Tronton / Fuso', 'default_capacity' => 8.00],
            ['name' => 'Excavator (Alat Pengambil)', 'default_capacity' => 10.00], // m3/jam for pengambil
            ['name' => 'Lainnya (Modifikasi/Non-Standar)', 'default_capacity' => 0.00],
        ];

        foreach ($vehicles as $vehicle) {
            \App\Models\MblbVehicle::updateOrCreate(
                ['name' => $vehicle['name']],
                ['default_capacity' => $vehicle['default_capacity'], 'is_active' => true]
            );
        }
    }
}
