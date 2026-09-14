<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\ParkingLocation;
use App\Models\RetributionType;
use App\Models\User;
use App\Models\UserRetributionAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Titik parkir Dishub Quick-Tap — zonasi tarif Perda No. 1 Tahun 2024.
 * Zona Premium  R2 2.000 / R4 3.000
 * Zona Strategis R2 1.500 / R4 2.000
 * Zona Ekonomi  R2 1.000 / R4 1.500
 * Zona Umum     R2 1.000 / R4 1.000
 * Tarif tetap: truk_bus 5.000, inap_truk 25.000 (Pasal 88); proxy_gt 1.000-10.000/24jam (Pasal 91).
 */
class ParkingLocationSeeder extends Seeder
{
    public function run(): void
    {
        $dishub = Opd::where('code', 'DISHUB')->first()
            ?? Opd::create([
                'name' => 'Dinas Perhubungan',
                'code' => 'DISHUB',
                'address' => 'Jl. Balai Kota No. 1, Bau-Bau',
                'email' => 'dishub@baubaukota.go.id',
                'status' => 'approved',
                'is_active' => true,
            ]);

        $parkirType = RetributionType::updateOrCreate(
            ['opd_id' => $dishub->id, 'name' => 'Retribusi Parkir'],
            [
                'category' => 'Parkir',
                'base_amount' => 2000,
                'unit' => 'per kali',
                'is_active' => true,
            ]
        );

        $locations = [
            // Zona Premium (r2 2000 / r4 3000)
            ['code' => 'JL-001', 'name' => 'Parkir Tepi Jalan Jl. Nusantara', 'lat' => -5.4633, 'lng' => 122.6012, 'rl' => 2000, 'r4' => 3000],
            ['code' => 'JL-002', 'name' => 'Parkir Tepi Jalan Jl. Sultan Hasanuddin', 'lat' => -5.4601, 'lng' => 122.5983, 'rl' => 2000, 'r4' => 3000],
            ['code' => 'JL-003', 'name' => 'Parkir Tepi Jalan Jl. Lasolangi', 'lat' => -5.4687, 'lng' => 122.6064, 'rl' => 2000, 'r4' => 3000],
            // Zona Strategis (r2 1500 / r4 2000)
            ['code' => 'JL-004', 'name' => 'Parkir Tepi Jalan Jl. Dayanu Ikhsanuddin', 'lat' => -5.4510, 'lng' => 122.5975, 'rl' => 1500, 'r4' => 2000],
            ['code' => 'JL-005', 'name' => 'Parkir Tepi Jalan Jl. Wangkanapi', 'lat' => -5.4695, 'lng' => 122.6070, 'rl' => 1500, 'r4' => 2000],
            ['code' => 'JL-006', 'name' => 'Parkir Pasar Wameo', 'lat' => -5.4573, 'lng' => 122.6035, 'rl' => 1500, 'r4' => 2000],
            // Zona Ekonomi (r2 1000 / r4 1500)
            ['code' => 'JL-007', 'name' => 'Parkir Tepi Jalan Jl. Kartini', 'lat' => -5.4650, 'lng' => 122.6100, 'rl' => 1000, 'r4' => 1500],
            ['code' => 'JL-008', 'name' => 'Parkir Tepi Jalan Jl. Betoambari', 'lat' => -5.4720, 'lng' => 122.6150, 'rl' => 1000, 'r4' => 1500],
            // Khusus: Terminal & Pelabuhan
            ['code' => 'TRM-001', 'name' => 'Terminal Bataraguru', 'lat' => -5.4870, 'lng' => 122.6220, 'rl' => 1000, 'r4' => 2000],
            ['code' => 'PEL-001', 'name' => 'Pelabuhan Murhum (Jembatan Batu)', 'lat' => -5.4802, 'lng' => 122.6108, 'rl' => 1000, 'r4' => 2000],
        ];

        foreach ($locations as $loc) {
            ParkingLocation::updateOrCreate(
                ['code' => $loc['code']],
                [
                    'name' => $loc['name'],
                    'category' => str_starts_with($loc['code'], 'PEL') || str_starts_with($loc['code'], 'TRM')
                        ? 'retribusi_khusus'
                        : 'retribusi_umum',
                    'opd_id' => $dishub->id,
                    'retribution_type_id' => $parkirType->id,
                    'rate_r2' => $loc['rl'],
                    'rate_r4' => $loc['r4'],
                    'latitude' => $loc['lat'],
                    'longitude' => $loc['lng'],
                    'is_active' => true,
                ]
            );
        }

        $this->ensureSampleJukir($dishub, $parkirType, [
            'email' => 'jukir.parkir@dishub.baubaukota.go.id',
            'name' => 'Arif Rizal (Jukir Dishub)',
        ]);

        $this->command->info('ParkingLocationSeeder: ' . count($locations) . ' titik parkir Dishub tersimpan.');
    }

    private function ensureSampleJukir(Opd $dishub, RetributionType $parkirType, array $attrs): void
    {
        $jukir = User::updateOrCreate(
            ['email' => $attrs['email']],
            [
                'name' => $attrs['name'],
                'password' => Hash::make('password123'),
                'nik' => '7472010101960001',
                'role' => 'petugas',
                'opd_id' => $dishub->id,
                'retribution_type_id' => $parkirType->id,
                'status' => 'active',
                'phone' => '081234567890',
                'surat_tugas_no' => 'ST.DISHUB/PKR/2026/013',
                'surat_tugas_expired_at' => now()->addMonths(6),
            ]
        );

        UserRetributionAssignment::updateOrCreate(
            ['user_id' => $jukir->id, 'retribution_type_id' => $parkirType->id],
            ['retribution_classification_id' => null]
        );
    }
}