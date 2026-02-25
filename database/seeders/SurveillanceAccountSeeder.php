<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SurveillanceAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Akun Kabid Pengawas (Approval & Penindakan)
        User::updateOrCreate(
            ['email' => 'kabid@retribusi.id'],
            [
                'name' => 'Kabid Pengawas',
                'nik' => '9999999999999901',
                'password' => Hash::make('password123'),
                'role' => 'kabid_pengawas',
                'status' => 'active',
                'opd_id' => 64, // BAPENDA
            ]
        );

        // 2. Akun Kasubid Pengawas (Pemeriksaan / Teknis)
        User::updateOrCreate(
            ['email' => 'kasubid@retribusi.id'],
            [
                'name' => 'Kasubid Pengawas',
                'nik' => '9999999999999902',
                'password' => Hash::make('password123'),
                'role' => 'kasubid_pengawas',
                'status' => 'active',
                'opd_id' => 64, // BAPENDA
            ]
        );

        $this->command->info('Surveillance accounts created/updated successfully!');
        $this->command->info('Kabid: kabid@retribusi.id / password123');
        $this->command->info('Kasubid: kasubid@retribusi.id / password123');
    }
}
