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
 * Seeder for creating specific staging accounts on sipanda.online.
 * 1. Admin: admin.staging@sipanda.online / password123
 * 2. Petugas: petugas.staging@sipanda.online / password123
 * 3. Mobile/Citizen: NIK 0000000000000001 / password123
 */
class StagingUsersSeeder extends Seeder
{
    public function run(): void
    {
        $bapenda = Opd::where('code', 'BAPENDA')->first();
        $opdId = $bapenda ? $bapenda->id : null;

        // 1. Admin Staging (admin.sipanda.online)
        User::updateOrCreate(
            ['email' => 'admin.staging@sipanda.online'],
            [
                'name' => 'Staging Admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'opd_id' => $opdId,
                'status' => 'active',
            ]
        );

        // 2. Petugas Staging (petugas.sipanda.online)
        User::updateOrCreate(
            ['email' => 'petugas.staging@sipanda.online'],
            [
                'name' => 'Staging Petugas',
                'password' => Hash::make('password123'),
                'role' => 'petugas',
                'opd_id' => $opdId,
                'status' => 'active',
            ]
        );

        // 3. Mobile/Citizen Staging (sipanda.online)
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
