<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DashboardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Core Data
        $opds = [
            'DISHUB' => \App\Models\Opd::where('code', 'DISHUB')->first(),
            'DISPERINDAG' => \App\Models\Opd::where('code', 'DISPERINDAG')->first(),
            'DLH' => \App\Models\Opd::where('code', 'DLH')->first(),
            'BAPENDA' => \App\Models\Opd::where('code', 'BAPENDA')->first(),
        ];

        $wilayahI = \App\Models\RetributionType::where('name', 'Wilayah I')->first();
        $wilayahII = \App\Models\RetributionType::where('name', 'Wilayah II')->first();

        if (!$wilayahI || !$wilayahII) {
            $this->command->error('Wilayah I/II not found. Run CleanRetributionTypesSeeder first.');
            return;
        }

        // 2. Create 50 Taxpayers
        $this->command->info('Creating taxpayers...');
        $taxpayers = [];
        for ($i = 1; $i <= 50; $i++) {
            $taxpayers[] = \App\Models\Taxpayer::create([
                'opd_id' => $opds['BAPENDA']->id,
                'name' => 'Wajib Pajak Seeder ' . $i,
                'nik' => '7472' . str_pad($i, 12, '0', STR_PAD_LEFT),
                'phone' => '0812' . str_pad($i, 8, '0', STR_PAD_LEFT),
                'address' => 'Jl. Baubau No. ' . $i,
                'is_active' => true,
            ]);
        }

        // 3. Create 200 Tax Objects spread across Wilayah I & II
        $this->command->info('Creating tax objects...');
        $objects = [];
        foreach ($taxpayers as $tp) {
            $numObjects = rand(1, 3);
            for ($j = 0; $j < $numObjects; $j++) {
                $type = rand(0, 1) ? $wilayahI : $wilayahII;
                // Pick a random OPD for variety beyond just BAPENDA
                $opdKeys = array_keys($opds);
                $opd = $opds[$opdKeys[rand(0, count($opdKeys) - 1)]];

                $objects[] = \App\Models\TaxObject::create([
                    'taxpayer_id' => $tp->id,
                    'retribution_type_id' => $type->id,
                    'opd_id' => $opd->id,
                    'name' => 'Objek ' . $tp->name . ' - ' . ($j + 1),
                    'address' => $tp->address . ' - Unit ' . ($j + 1),
                    'latitude' => -5.4633 + (rand(-100, 100) / 1000),
                    'longitude' => 122.6012 + (rand(-100, 100) / 1000),
                    'status' => 'active',
                ]);
            }
        }

        // 4. Generate Bills and Payments for 2025 and 2026
        $this->command->info('Generating bills and payments...');
        $years = [2025, 2026];
        $currentMonth = (int) date('m');
        $currentYear = (int) date('Y');

        foreach ($years as $year) {
            for ($month = 1; $month <= 12; $month++) {
                // Skip future months in current year
                if ($year == $currentYear && $month > $currentMonth) continue;

                $numBills = rand(30, 60);
                for ($k = 0; $k < $numBills; $k++) {
                    $obj = $objects[rand(0, count($objects) - 1)];
                    $amount = rand(5, 50) * 10000;
                    
                    // 70% chance of being paid
                    $status = rand(1, 100) <= 70 ? 'paid' : 'pending';
                    
                    $billDate = \Carbon\Carbon::create($year, $month, rand(1, 28));
                    
                    $bill = \App\Models\Bill::create([
                        'taxpayer_id' => $obj->taxpayer_id,
                        'tax_object_id' => $obj->id,
                        'opd_id' => $obj->opd_id,
                        'retribution_type_id' => $obj->retribution_type_id,
                        'bill_number' => 'BILL-' . $year . $month . '-' . \Illuminate\Support\Str::random(6),
                        'amount' => $amount,
                        'status' => $status,
                        'period' => $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT),
                        'due_date' => $billDate->copy()->addDays(15),
                        'created_at' => $billDate,
                    ]);

                    if ($status === 'paid') {
                        \App\Models\Payment::create([
                            'bill_id' => $bill->id,
                            'taxpayer_id' => $bill->taxpayer_id,
                            'tax_object_id' => $bill->tax_object_id,
                            'amount' => $amount,
                            'payment_method' => rand(0, 1) ? 'va_sultra' : 'qris',
                            'status' => 'success',
                            'billing_period' => $bill->period,
                            'paid_at' => $billDate->copy()->addDays(rand(1, 10)),
                            'transaction_id' => 'TRX-' . \Illuminate\Support\Str::random(10),
                        ]);
                    }
                }
            }
        }

        $this->command->info('Dashboard data seeded successfully!');
    }
}
