<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Opd;
use App\Models\RetributionType;
use Illuminate\Support\Facades\Hash;

class AdminWilayahSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating Admin Wilayah Demo Accounts...');

        $bapenda = Opd::where('code', 'BAPENDA')->first();
        if (!$bapenda) {
            $this->command->error('OPD BAPENDA not found.');
            return;
        }

        $wilayahI = RetributionType::where('name', 'Wilayah I')->first();
        $wilayahII = RetributionType::where('name', 'Wilayah II')->first();

        if (!$wilayahI || !$wilayahII) {
            $this->command->error('Wilayah I or Wilayah II RetributionType not found.');
            return;
        }

        // 1. Admin Wilayah I
        User::updateOrCreate(
            ['email' => 'adminw1@baubaukota.go.id'],
            [
                'name' => 'Admin Wilayah I',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'opd_id' => $bapenda->id,
                'retribution_type_id' => $wilayahI->id,
                'status' => 'active',
            ]
        );

        // 2. Admin Wilayah II
        User::updateOrCreate(
            ['email' => 'adminw2@baubaukota.go.id'],
            [
                'name' => 'Admin Wilayah II',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'opd_id' => $bapenda->id,
                'retribution_type_id' => $wilayahII->id,
                'status' => 'active',
            ]
        );

        $this->command->info('Admin Wilayah Demo Accounts created successfully!');
    }
}
