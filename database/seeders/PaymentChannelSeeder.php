<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\PaymentChannel::firstOrCreate(
            ['code' => 'sultra'],
            [
                'name' => 'Bank Sultra (Direct)',
                'is_active' => true,
                'credentials' => [
                    'secret' => env('BANK_SULTRA_SECRET', 'secret_sultra_2026'),
                    'allowed_ips' => explode(',', env('BANK_SULTRA_ALLOWED_IPS', '127.0.0.1')),
                ]
            ]
        );

        \App\Models\PaymentChannel::firstOrCreate(
            ['code' => 'qris'],
            [
                'name' => 'QRIS Default (Aggregator)',
                'is_active' => true,
                'credentials' => []
            ]
        );

        \App\Models\PaymentChannel::firstOrCreate(
            ['code' => 'mandiri'],
            [
                'name' => 'QRIS Bank Mandiri',
                'is_active' => true,
                'credentials' => [
                    'api_url' => env('MANDIRI_API_URL', 'https://api-dev.bankmandiri.co.id'),
                    'client_id' => env('MANDIRI_CLIENT_ID', 'default_client_id'),
                    'client_secret' => env('MANDIRI_CLIENT_SECRET', 'default_client_secret'),
                ]
            ]
        );
    }
}
