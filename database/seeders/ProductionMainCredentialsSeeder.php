<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Taxpayer;
use App\Models\Opd;
use Illuminate\Support\Facades\Hash;

class ProductionMainCredentialsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure BAPENDA OPD exists
        $bapenda = Opd::updateOrCreate(
            ['code' => 'BAPENDA'],
            [
                'name' => 'Badan Pendapatan Daerah',
                'address' => 'Kota Baubau',
                'status' => 'approved',
                'is_active' => true,
            ]
        );

        // 2. Admin Main
        User::updateOrCreate(
            ['email' => 'admin.main@sipanda.online'],
            [
                'name' => 'Admin Main',
                'password' => Hash::make('Bapenda2026!'),
                'role' => 'admin',
                'opd_id' => $bapenda->id,
                'status' => 'active',
            ]
        );

        // 3. Petugas Main
        User::updateOrCreate(
            ['email' => 'petugas.main@sipanda.online'],
            [
                'name' => 'Petugas Main',
                'password' => Hash::make('Bapenda2026!'),
                'role' => 'petugas',
                'opd_id' => $bapenda->id,
                'status' => 'active',
            ]
        );

        // 4. Mobile Citizen (Taxpayer)
        Taxpayer::updateOrCreate(
            ['nik' => '7404011234560001'],
            [
                'name' => 'Citizen Main',
                'password' => Hash::make('Bapenda2026!'),
                'address' => 'Kota Baubau',
                'phone' => '081234567890',
                'is_active' => true,
                'opd_id' => $bapenda->id,
            ]
        );

        $this->command->info('Production credentials created successfully.');
    }
}
