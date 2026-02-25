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
        // Temukan ID BAPENDA secara dinamis
        $bapenda = \App\Models\Opd::where('name', 'like', '%Badan Pendapatan Daerah%')->first();
        $opdId = $bapenda ? $bapenda->id : null;

        // 1. Akun Kabid Pengawas (Approval & Penindakan)
        User::updateOrCreate(
            ['email' => 'kabid@retribusi.id'],
            [
                'name' => 'Kabid Pengawas',
                'nik' => '9999999999999901',
                'password' => Hash::make('password123'),
                'role' => 'kabid_pengawas',
                'status' => 'active',
                'opd_id' => $opdId,
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
                'opd_id' => $opdId,
            ]
        );

        $this->command->info('Surveillance accounts created/updated successfully!');
        if ($opdId) {
            $this->command->info("Linked to OPD: {$bapenda->name} (ID: {$opdId})");
        } else {
            $this->command->warn('BAPENDA OPD not found, linked to null OPD.');
        }
        $this->command->info('Kabid: kabid@retribusi.id / password123');
        $this->command->info('Kasubid: kasubid@retribusi.id / password123');
    }
}
