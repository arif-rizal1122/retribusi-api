<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Achievement Seeder...');

        // 1. Get BAPENDA OPD
        $bapenda = Opd::where('code', 'BAPENDA')->first();
        if (!$bapenda) {
            $this->command->error('OPD BAPENDA not found. Please run BapendaMasterDataSeeder first.');
            return;
        }

        // Use a default user for created_by/user_id
        $admin = User::where('email', 'bapenda@baubaukota.go.id')->first() ?: User::first();

        // 2. Identify Target Classifications (PBJT)
        $classifications = RetributionClassification::where('opd_id', $bapenda->id)
            ->whereIn('code', ['PBJT-MNM', 'PBJT-HTL', 'PBJT-PRK', 'PBJT-HBR', 'PBJT-LIS'])
            ->get();

        if ($classifications->isEmpty()) {
            $this->command->error('Target PBJT Classifications not found. Please run BapendaMasterDataSeeder first.');
            return;
        }

        // 3. Create sample Taxpayers for these classifications
        $this->command->info('Creating taxpayers and objects...');
        $taxpayers = [];
        for ($i = 1; $i <= 20; $i++) {
            $nik = '7472' . str_pad($i, 12, '0', STR_PAD_LEFT);
            $taxpayers[] = Taxpayer::updateOrCreate(
                ['nik' => $nik],
                [
                    'opd_id' => $bapenda->id,
                    'name' => 'Wajib Pajak Utama ' . $i,
                    'npwpd' => 'NPWPD-' . strtoupper(substr(md5($nik), 0, 8)),
                    'phone' => '0821' . str_pad($i, 8, '1', STR_PAD_LEFT),
                    'address' => 'Jl. Jenderal Sudirman No. ' . $i,
                    'is_active' => true,
                    'created_by' => $admin ? $admin->id : null,
                ]
            );
        }

        // 4. Create Tax Objects for each classification
        $objectsData = [];
        foreach ($classifications as $cls) {
            $num = rand(3, 5);
            for ($j = 1; $j <= $num; $j++) {
                $tp = $taxpayers[rand(0, count($taxpayers) - 1)];
                $nop = $tp->npwpd . '-' . $cls->retribution_type_id . '-' . $cls->id . '-' . $j;
                $taxObject = TaxObject::updateOrCreate(
                    ['nop' => $nop],
                    [
                        'taxpayer_id' => $tp->id,
                        'opd_id' => $bapenda->id,
                        'retribution_type_id' => $cls->retribution_type_id,
                        'retribution_classification_id' => $cls->id,
                        'name' => $cls->name . ' - Outlet ' . $j,
                        'address' => $tp->address . ' - Blok ' . $j,
                        'latitude' => -5.4633 + (rand(-50, 50) / 1000),
                        'longitude' => 122.6012 + (rand(-50, 50) / 1000),
                        'status' => 'active',
                    ]
                );
                
                $objectsData[] = [
                    'model' => $taxObject,
                    'cls_code' => $cls->code
                ];
            }
        }

        // 5. Generate Bills and Payments for Q1 2026
        $this->command->info('Generating bills and payments for 2026...');
        $months = [1, 2, 3]; // Jan, Feb, Mar
        $year = 2026;

        foreach ($months as $month) {
            $this->command->info("Processing Month: $month");
            
            foreach ($objectsData as $data) {
                $obj = $data['model'];
                $clsCode = $data['cls_code'];
                
                $baseAmount = match ($clsCode) {
                    'PBJT-MNM' => rand(150, 500) * 1000,
                    'PBJT-HTL' => rand(500, 2000) * 1000,
                    'PBJT-PRK' => rand(50, 150) * 1000,
                    'PBJT-HBR' => rand(300, 1000) * 1000,
                    'PBJT-LIS' => rand(100, 300) * 1000,
                    default => 100000,
                };

                $billCount = rand(1, 2);
                for ($b = 0; $b < $billCount; $b++) {
                    $billDate = Carbon::create($year, $month, rand(1, 28));
                    $status = rand(1, 100) <= 85 ? 'paid' : 'pending';
                    
                    // Generate a stable bill number based on month, object and index to make it idempotent
                    $billNumber = 'PBJT-' . $year . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . $obj->id . '-' . $b;

                    $bill = Bill::updateOrCreate(
                        ['bill_number' => $billNumber],
                        [
                            'taxpayer_id' => $obj->taxpayer_id,
                            'tax_object_id' => $obj->id,
                            'opd_id' => $obj->opd_id,
                            'user_id' => $admin ? $admin->id : null,
                            'retribution_type_id' => $obj->retribution_type_id,
                            'retribution_classification_id' => $obj->retribution_classification_id,
                            'amount' => $baseAmount,
                            'status' => $status,
                            'period' => $billDate->format('F Y'),
                            'period_start' => $billDate->copy()->startOfMonth(),
                            'period_end' => $billDate->copy()->endOfMonth(),
                            'due_date' => $billDate->copy()->addDays(15),
                            'created_at' => $billDate,
                        ]
                    );

                    if ($status === 'paid' && $bill->wasRecentlyCreated) {
                        Payment::updateOrCreate(
                            ['bill_id' => $bill->id],
                            [
                                'taxpayer_id' => $bill->taxpayer_id,
                                'tax_object_id' => $bill->tax_object_id,
                                'amount' => $bill->amount,
                                'payment_method' => rand(0, 10) > 3 ? 'va_sultra' : 'cash',
                                'status' => 'success',
                                'billing_period' => $bill->period,
                                'paid_at' => $billDate->copy()->addDays(rand(1, 10)),
                                'transaction_id' => 'PAY-' . $bill->id . '-' . strtoupper(Str::random(4)),
                                'approved_by' => $admin ? $admin->id : null,
                            ]
                        );
                    }
                }
            }
        }

        $this->command->info('Achievement Seeder completed successfully!');
    }
}
