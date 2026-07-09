<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Taxpayer;
use Illuminate\Support\Facades\Hash;

class MerchantAFTSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan akun NIK: 0000000000000001 ada untuk testing Merchant AFT
        Taxpayer::updateOrCreate(
            ['nik' => '0000000000000001'],
            [
                'name' => 'Budi Merchant (AFT Demo)',
                'phone' => '081234567890',
                'address' => 'Jl. Merdeka No. 1, Baubau',
                'password' => Hash::make('password123'),
                'is_active' => true,
                'metadata' => json_encode(['role' => 'merchant', 'aft_enabled' => true]),
            ]
        );

        $this->command->info('Merchant AFT Demo Account created successfully (NIK: 0000000000000001 | Pass: password123)');
    }
}
