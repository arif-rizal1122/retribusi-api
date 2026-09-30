<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class StagingUserSeeder extends Seeder
{
    /**
     * Seed user akun untuk keperluan testing di staging.
     * ⚠️ JANGAN jalankan di production.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('⛔ StagingUserSeeder TIDAK BOLEH dijalankan di production!');
            return;
        }

        $users = [
            [
                'name'       => 'Super Admin Staging',
                'email'      => 'superadmin@m-pad.online',
                'password'   => Hash::make('password'),
                'role'       => 'super_admin',
            ],
            [
                'name'       => 'Admin Wilayah I',
                'email'      => 'adminw1@baubaukota.go.id',
                'password'   => Hash::make('password'),
                'role'       => 'admin',
            ],
            [
                'name'       => 'Petugas Lapangan 1',
                'email'      => 'petugas1@test.com',
                'password'   => Hash::make('password'),
                'role'       => 'petugas',
            ],
            [
                'name'       => 'Petugas Lapangan 2',
                'email'      => 'petugas2@test.com',
                'password'   => Hash::make('password'),
                'role'       => 'petugas',
            ],
            [
                'name'       => 'Wajib Pajak Test',
                'email'      => 'pentest_wp@test.com',
                'password'   => Hash::make('password'),
                'role'       => 'wajib_pajak',
            ],
            [
                'name'       => 'Citizen Test',
                'email'      => 'citizen.test@m-pad.online',
                'password'   => Hash::make('password'),
                'role'       => 'citizen',
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                $data
            );
            $this->command->info("✅ User seeded: {$data['email']} [{$data['role']}]");
        }

        $this->command->info('');
        $this->command->info('🎉 StagingUserSeeder selesai. Semua akun testing siap.');
    }
}
