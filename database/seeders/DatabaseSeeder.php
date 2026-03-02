<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\Taxpayer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Super Admin
        User::updateOrCreate(
            ['email' => 'admin@retribusi.id'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                'role' => 'super_admin',
                'status' => 'active',
            ]
        );

        // Create Dev Super Admin
        User::updateOrCreate(
            ['email' => 'superadmin@sipanda.online'],
            [
                'name' => 'Dev Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'status' => 'active',
            ]
        );

        // Create sample OPDs with approved status
        $dishub = Opd::updateOrCreate(
            ['code' => 'DISHUB'],
            [
                'name' => 'Dinas Perhubungan',
                'address' => 'Jl. Protokol No. 1',
                'phone' => '0401-123456',
                'email' => 'dishub@baubau.go.id',
                'status' => 'approved',
                'is_active' => true,
            ]
        );

        $disperindag = Opd::updateOrCreate(
            ['code' => 'DISPERINDAG'],
            [
                'name' => 'Dinas Perindustrian dan Perdagangan',
                'address' => 'Jl. Pasar No. 2',
                'phone' => '0401-654321',
                'email' => 'disperindag@baubau.go.id',
                'status' => 'approved',
                'is_active' => true,
            ]
        );

        $dlh = Opd::updateOrCreate(
            ['code' => 'DLH'],
            [
                'name' => 'Dinas Lingkungan Hidup',
                'address' => 'Jl. Hijau No. 3',
                'phone' => '0401-111222',
                'email' => 'dlh@baubau.go.id',
                'status' => 'approved',
                'is_active' => true,
            ]
        );

        $bapenda = Opd::updateOrCreate(
            ['code' => 'BAPENDA'],
            [
                'name' => 'Badan Pendapatan Daerah',
                'address' => 'Jl. Bapenda No. 1',
                'phone' => '0401-999888',
                'email' => 'bapenda@baubau.go.id',
                'status' => 'approved',
                'is_active' => true,
            ]
        );

        // Create OPD admin users
        User::updateOrCreate(
            ['email' => 'dishub@retribusi.id'],
            [
                'name' => 'Admin Dishub',
                'password' => Hash::make('password123'),
                'role' => 'opd',
                'opd_id' => $dishub->id,
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'disperindag@retribusi.id'],
            [
                'name' => 'Admin Disperindag',
                'password' => Hash::make('password123'),
                'role' => 'opd',
                'opd_id' => $disperindag->id,
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'dlh@retribusi.id'],
            [
                'name' => 'Admin DLH',
                'password' => Hash::make('password123'),
                'role' => 'opd',
                'opd_id' => $dlh->id,
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'bapenda@baubaukota.go.id'],
            [
                'name' => 'Admin BAPENDA',
                'password' => Hash::make('password123'),
                'role' => 'opd',
                'opd_id' => $bapenda->id,
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'petugas@bapenda.go.id'],
            [
                'name' => 'Petugas BAPENDA',
                'password' => Hash::make('password123'),
                'role' => 'petugas',
                'opd_id' => $bapenda->id,
                'status' => 'active',
            ]
        );

        // Create retribution types for Dishub
        $parkirMobil = RetributionType::create([
            'opd_id' => $dishub->id,
            'name' => 'Retribusi Parkir Mobil',
            'category' => 'Parkir',
            'icon' => 'car',
            'base_amount' => 5000,
            'unit' => 'per jam',
            'is_active' => true,
        ]);

        $parkirMotor = RetributionType::create([
            'opd_id' => $dishub->id,
            'name' => 'Retribusi Parkir Motor',
            'category' => 'Parkir',
            'icon' => 'bike',
            'base_amount' => 2000,
            'unit' => 'per jam',
            'is_active' => true,
        ]);

        $terminal = RetributionType::create([
            'opd_id' => $dishub->id,
            'name' => 'Retribusi Terminal',
            'category' => 'Terminal',
            'icon' => 'bus',
            'base_amount' => 10000,
            'unit' => 'per bus',
            'is_active' => true,
        ]);

        // Create retribution types for Disperindag
        $kios = RetributionType::create([
            'opd_id' => $disperindag->id,
            'name' => 'Retribusi Kios Pasar',
            'category' => 'Pasar',
            'icon' => 'store',
            'base_amount' => 150000,
            'unit' => 'per bulan',
            'is_active' => true,
        ]);

        $los = RetributionType::create([
            'opd_id' => $disperindag->id,
            'name' => 'Retribusi Los Pasar',
            'category' => 'Pasar',
            'icon' => 'market',
            'base_amount' => 50000,
            'unit' => 'per bulan',
            'is_active' => true,
        ]);

        // Create retribution types for DLH
        $sampah = RetributionType::create([
            'opd_id' => $dlh->id,
            'name' => 'Retribusi Persampahan',
            'category' => 'Kebersihan',
            'icon' => 'trash',
            'base_amount' => 30000,
            'unit' => 'per bulan',
            'is_active' => true,
        ]);

        // Run BAPENDA Master Data Seeder
        $this->call(BapendaMasterDataSeeder::class);
        $this->call(BapendaTestingSeeder::class);
        $this->call(SurveillanceAccountSeeder::class);
        $this->call(TestingScenarioSeeder::class);

        $this->command->info('Database seeded successfully!');
        $this->command->info('Super Admin: superadmin@sipanda.online / Sipanda123#');
        $this->command->info('OPD Bapenda: bapenda@baubaukota.go.id / password123');
        $this->command->info('OPD Dishub: dishub@retribusi.id / password123');
        $this->command->info('OPD Disperindag: disperindag@retribusi.id / password123');
        $this->command->info('OPD DLH: dlh@retribusi.id / password123');
    }
}
