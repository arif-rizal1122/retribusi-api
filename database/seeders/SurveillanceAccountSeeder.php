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
        User::create([
            'name' => 'Kabid Pengawas',
            'email' => 'kabid@retribusi.id',
            'password' => Hash::make('password123'),
            'role' => 'kabid_pengawas',
            'status' => 'active',
        ]);

        // 2. Akun Kasubid Pengawas (Pemeriksaan / Teknis)
        User::create([
            'name' => 'Kasubid Pengawas',
            'email' => 'kasubid@retribusi.id',
            'password' => Hash::make('password123'),
            'role' => 'kasubid_pengawas',
            'status' => 'active',
        ]);

        $this->command->info('Surveillance accounts created successfully!');
        $this->command->info('Kabid: kabid@retribusi.id / password123');
        $this->command->info('Kasubid: kasubid@retribusi.id / password123');
    }
}
