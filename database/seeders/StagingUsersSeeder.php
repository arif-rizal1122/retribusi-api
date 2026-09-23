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
            ['email' => 'wajibpajak@mpad.online'],
            [
                'nik' => '0000000000000001',
                'name' => 'Staging Mobile User',
                'password' => Hash::make('password123'),
                'address' => 'Kota Baubau Staging',
                'phone' => '081234567890',
                'is_active' => true,
                'opd_id' => $opdId,
            ]
        );

        // 4. Kasubid Pendataan / Ibu Salma (kasubid_pendataan@mpad.online)
        User::updateOrCreate(
            ['email' => 'kasubid_pendataan@mpad.online'],
            [
                'name' => 'Kasubid Pendataan (Ibu Salma)',
                'password' => Hash::make('password123'),
                'role' => 'kasubid_pendataan',
                'opd_id' => $opdId,
                'status' => 'active',
            ]
        );

        // 5. Kabid Pajak Wilayah I / Ibu Ochi (kabid_pajak@mpad.online)
        User::updateOrCreate(
            ['email' => 'kabid_pajak@mpad.online'],
            [
                'name' => 'Kabid Pajak Wilayah I (Ibu Ochi)',
                'password' => Hash::make('password123'),
                'role' => 'kepala_bapenda',
                'opd_id' => $opdId,
                'status' => 'active',
                'metadata' => [
                    'tax_domain' => 'wilayah_1',
                    'portfolio' => 'PBB-P2, BPHTB, Reklame, MBLB, Walet, Opsen',
                    'role_code' => 'bapenda',
                    'opd_role' => 'Bidang Pajak Daerah Wilayah I',
                    'role_group' => 'admin',
                ],
            ]
        );

        // 6. Kabid Pajak Wilayah II (kabid_wilayah2@mpad.online)
        User::updateOrCreate(
            ['email' => 'kabid_wilayah2@mpad.online'],
            [
                'name' => 'Kabid Pajak Wilayah II',
                'password' => Hash::make('password123'),
                'role' => 'kepala_bapenda',
                'opd_id' => $opdId,
                'status' => 'active',
                'metadata' => [
                    'tax_domain' => 'wilayah_2',
                    'portfolio' => 'PBJT-FOOD, PBJT-HTL, PBJT-HBR, PBJT-PRK, PBJT-LISTRIK, Air Tanah',
                    'role_code' => 'bapenda',
                    'opd_role' => 'Bidang Pajak Daerah Wilayah II',
                    'role_group' => 'admin',
                ],
            ]
        );

        // 7. Staf Pelayanan Loket / Ibu Yani (staf_pelayanan@mpad.online)
        User::updateOrCreate(
            ['email' => 'staf_pelayanan@mpad.online'],
            [
                'name' => 'Staf Pelayanan Bapenda (Ibu Yani)',
                'password' => Hash::make('password123'),
                'role' => 'opd',
                'opd_id' => $opdId,
                'status' => 'active',
            ]
        );

        $this->command->info('Staging accounts created successfully.');
    }
}
