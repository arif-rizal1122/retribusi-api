<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\Bill;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SultanPaymentTestingSeeder extends Seeder
{
    public function run(): void
    {
        $nik = '9999999999999999';
        
        // 1. Ensure OPDs exist
        $bapenda = Opd::firstOrCreate(['code' => 'BAPENDA'], ['name' => 'BAPENDA']);
        
        // 2. Create/Update Taxpayer
        $taxpayer = Taxpayer::updateOrCreate(
            ['nik' => $nik],
            [
                'opd_id' => $bapenda->id,
                'name' => 'Sultan Hasanuddin (Tester Kaya)',
                'address' => 'Jl. Boulevard No. 1, Baubau',
                'phone' => '089999999999',
                'npwpd' => 'P-2-9999999',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        // Fetch Classifications
        $pbbClass = RetributionClassification::where('name', 'PBB-P2')->first();
        $hotelClass = RetributionClassification::where('name', 'PBJT - Jasa Perhotelan')->first();
        $restoClass = RetributionClassification::where('name', 'PBJT - Makan dan Minum')->first();

        if (!$pbbClass || !$hotelClass || !$restoClass) {
            $this->command->error("Standard classifications not found. Please run classification seeders first.");
            return;
        }

        // Object 1: PBJT Hotel Bintang 5
        $hotelType = RetributionType::find($hotelClass->retribution_type_id);
        $hotelObject = TaxObject::updateOrCreate(
            ['nop' => 'HTL-99-001'],
            [
                'taxpayer_id' => $taxpayer->id,
                'retribution_type_id' => $hotelType->id,
                'retribution_classification_id' => $hotelClass?->id,
                'opd_id' => $bapenda->id,
                'name' => 'Hotel Baubau Grand',
                'address' => 'Jl. Boulevard No. 1',
                'status' => 'active',
                'approved_at' => Carbon::now()->subMonths(12),
            ]
        );

        // Object 2: PBJT Restoran Mewah
        $restoType = RetributionType::find($restoClass->retribution_type_id);
        $restoObject = TaxObject::updateOrCreate(
            ['nop' => 'RST-99-001'],
            [
                'taxpayer_id' => $taxpayer->id,
                'retribution_type_id' => $restoType->id,
                'retribution_classification_id' => $restoClass?->id,
                'opd_id' => $bapenda->id,
                'name' => 'Restoran Sky View',
                'address' => 'Jl. Boulevard No. 1 Lt. 10',
                'status' => 'active',
                'approved_at' => Carbon::now()->subMonths(12),
            ]
        );

        // Object 3: PBB-P2 
        $pbbType = RetributionType::find($pbbClass->retribution_type_id);
        $pbbObject = TaxObject::updateOrCreate(
            ['nop' => 'PBB-99-001'],
            [
                'taxpayer_id' => $taxpayer->id,
                'retribution_type_id' => $pbbType->id,
                'retribution_classification_id' => $pbbClass?->id,
                'opd_id' => $bapenda->id,
                'name' => 'Gedung Hotel Baubau Grand',
                'address' => 'Jl. Boulevard No. 1',
                'status' => 'active',
                'approved_at' => Carbon::now()->subMonths(12),
                'metadata' => ['luas_bumi' => 5000, 'luas_bangunan' => 15000]
            ]
        );

        // 4. Create Bills and Payments (Lengkap)
        
        // PBB 2024 - LUNAS
        $billPbb2024 = Bill::updateOrCreate(
            ['bill_number' => 'INV-2024-PBB-99'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $pbbObject->id,
                'opd_id' => $bapenda->id,
                'retribution_type_id' => $pbbType->id,
                'retribution_classification_id' => $pbbClass?->id,
                'amount' => 15000000,
                'status' => 'paid',
                'period' => 'Tahun 2024',
                'due_date' => Carbon::create(2024, 9, 30),
            ]
        );
        Payment::updateOrCreate(
            ['bill_id' => $billPbb2024->id],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $pbbObject->id,
                'transaction_id' => 'TRX-PBB-2024-99',
                'payment_method' => 'VA_BPD',
                'amount' => 15000000,
                'status' => 'success',
                'billing_period' => 'Tahun 2024',
                'paid_at' => Carbon::create(2024, 8, 15),
            ]
        );

        // PBB 2025 - PENDING
        Bill::updateOrCreate(
            ['bill_number' => 'INV-2025-PBB-99'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $pbbObject->id,
                'opd_id' => $bapenda->id,
                'retribution_type_id' => $pbbType->id,
                'retribution_classification_id' => $pbbClass?->id,
                'amount' => 15500000,
                'status' => 'pending',
                'period' => 'Tahun 2025',
                'due_date' => Carbon::create(2025, 9, 30),
            ]
        );

        // Hotel - 3 Bulan Terakhir LUNAS
        for ($i = 3; $i >= 1; $i--) {
            $month = Carbon::now()->subMonths($i);
            $billHotel = Bill::updateOrCreate(
                ['bill_number' => 'INV-HTL-' . $month->format('Ym') . '-99'],
                [
                    'taxpayer_id' => $taxpayer->id,
                    'tax_object_id' => $hotelObject->id,
                    'opd_id' => $bapenda->id,
                    'retribution_type_id' => $hotelType->id,
                    'retribution_classification_id' => $hotelClass?->id,
                    'amount' => 50000000, // 50 juta sebulan
                    'status' => 'paid',
                    'period' => $month->translatedFormat('F Y'),
                    'due_date' => $month->copy()->endOfMonth(),
                ]
            );
            Payment::updateOrCreate(
                ['bill_id' => $billHotel->id],
                [
                    'taxpayer_id' => $taxpayer->id,
                    'tax_object_id' => $hotelObject->id,
                    'transaction_id' => 'TRX-HTL-' . $month->format('Ym') . '-99',
                    'payment_method' => 'VA_BPD',
                    'amount' => 50000000,
                    'status' => 'success',
                    'billing_period' => $month->translatedFormat('F Y'),
                    'paid_at' => $month->copy()->addDays(5),
                ]
            );
        }

        // Hotel - Bulan Ini PENDING
        $month = Carbon::now();
        Bill::updateOrCreate(
            ['bill_number' => 'INV-HTL-' . $month->format('Ym') . '-99'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $hotelObject->id,
                'opd_id' => $bapenda->id,
                'retribution_type_id' => $hotelType->id,
                'retribution_classification_id' => $hotelClass?->id,
                'amount' => 50000000, // 50 juta sebulan
                'status' => 'pending',
                'period' => $month->translatedFormat('F Y'),
                'due_date' => $month->copy()->endOfMonth(),
            ]
        );

        $this->command->info('✅ Seeder SultanPaymentTestingSeeder: Data Wajib Pajak Potensi Besar beserta Histori Pembayaran Lunas & Pending berhasil dibuat.');
    }
}
