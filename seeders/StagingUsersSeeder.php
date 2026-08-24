<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Taxpayer;
use App\Models\Opd;
use Illuminate\Support\Facades\Hash;

/**
 * StagingUsersSeeder
 * 
 * Seeder for creating specific staging accounts on mpad.online.
 * 1. Admin: admin.staging@mpad.online / password123
 * 2. Petugas: petugas.staging@mpad.online / password123
 * 3. Mobile/Citizen: NIK 0000000000000001 / password123
 */
class StagingUsersSeeder extends Seeder
{
    public function run(): void
    {
        $bapenda = Opd::where('code', 'BAPENDA')->first();
        $opdId = $bapenda ? $bapenda->id : null;

        // 1. Admin Staging (admin.mpad.online)
        User::updateOrCreate(
            ['email' => 'admin.staging@mpad.online'],
            [
                'name' => 'Staging Admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'opd_id' => $opdId,
                'status' => 'active',
            ]
        );

        // 2. Petugas Staging (petugas.mpad.online)
        User::updateOrCreate(
            ['email' => 'petugas.staging@mpad.online'],
            [
                'name' => 'Staging Petugas',
                'password' => Hash::make('password123'),
                'role' => 'petugas',
                'opd_id' => $opdId,
                'status' => 'active',
            ]
        );

        // 3. Mobile/Citizen Staging (mpad.online)
        Taxpayer::updateOrCreate(
            ['nik' => '0000000000000001'],
            [
                'name' => 'Staging Mobile User',
                'password' => Hash::make('password123'),
                'address' => 'Kota Baubau Staging',
                'phone' => '081234567890',
                'is_active' => true,
                'opd_id' => $opdId,
            ]
        );

        $this->command->info('Staging accounts created successfully.');
    }
}
