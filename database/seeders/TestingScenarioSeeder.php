<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Verification;
use App\Models\ObjectVerification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TestingScenarioSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get existing OPDs (Assumed created by DatabaseSeeder)
        $bapenda = Opd::where('code', 'BAPENDA')->first();
        $dishub = Opd::where('code', 'DISHUB')->first();

        if (!$bapenda || !$dishub) {
            $this->command->error('BAPENDA or DISHUB OPD not found. Run DatabaseSeeder first.');
            return;
        }

        // 2. Create Taxpayer "Budi Santoso"
        $budi = Taxpayer::updateOrCreate(
            ['nik' => '1234567890123456'],
            [
                'opd_id' => $bapenda->id,
                'name' => 'Budi Santoso',
                'address' => 'Jl. Wolter Monginsidi No. 12, Baubau',
                'phone' => '081234567890',
                'npwpd' => 'P-2-0000001',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]
        );

        // 3. Create Taxpayer "Ani Lestari"
        $ani = Taxpayer::updateOrCreate(
            ['nik' => '1234567890123457'],
            [
                'opd_id' => $bapenda->id,
                'name' => 'Ani Lestari',
                'address' => 'Jl. Pahlawan No. 45, Baubau',
                'phone' => '081234567899',
                'npwpd' => 'P-2-0000002',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]
        );

        // 4. Create Tax Objects for Budi
        $parkirType = RetributionType::where('name', 'Retribusi Parkir Mobil')->first() 
            ?? RetributionType::where('name', 'like', '%Parkir%')->first();
            
        $parkirObject = TaxObject::updateOrCreate(
            ['nop' => 'PRK-74-001'],
            [
                'taxpayer_id' => $budi->id,
                'retribution_type_id' => $parkirType->id ?? 1,
                'opd_id' => $dishub->id,
                'name' => 'Lahan Parkir Toko Budi',
                'address' => 'Jl. Merdeka No. 5',
                'latitude' => -5.463300,
                'longitude' => 122.601200,
                'status' => 'active',
                'approved_at' => Carbon::now()->subMonths(2),
            ]
        );

        $pbbType = RetributionType::where('name', 'Wilayah I')->first() 
            ?? RetributionType::where('name', 'like', '%Wilayah%')->first();
        
        $pbbClass = \App\Models\RetributionClassification::where('name', 'PBB')->first();
        
        $pbbObject = TaxObject::updateOrCreate(
            ['nop' => 'PBB-74-001'],
            [
                'taxpayer_id' => $budi->id,
                'retribution_type_id' => $pbbType->id ?? 1,
                'retribution_classification_id' => $pbbClass->id ?? null,
                'opd_id' => $bapenda->id,
                'name' => 'Rumah Tinggal Budi',
                'address' => 'Jl. Wolter Monginsidi No. 12',
                'latitude' => -5.464500,
                'longitude' => 122.602500,
                'status' => 'active',
                'approved_at' => Carbon::now()->subMonths(6),
                'metadata' => ['luas_bumi' => 200, 'luas_bangunan' => 100]
            ]
        );

        // 5. Create Tax Objects for Ani
        $kiosType = RetributionType::where('name', 'Retribusi Kios Pasar')->first() 
            ?? RetributionType::where('name', 'like', '%Kios%')->first();
            
        $kiosObject = TaxObject::updateOrCreate(
            ['nop' => 'KIO-74-001'],
            [
                'taxpayer_id' => $ani->id,
                'retribution_type_id' => $kiosType->id ?? 1,
                'opd_id' => Opd::where('code', 'DISPERINDAG')->first()->id ?? $bapenda->id,
                'name' => 'Kios Sembako Ani',
                'address' => 'Pasar Karya No. 10',
                'status' => 'active',
                'approved_at' => Carbon::now()->subMonth(),
            ]
        );

        $sampahType = RetributionType::where('name', 'Retribusi Persampahan')->first() 
            ?? RetributionType::where('name', 'like', '%Sampah%')->first();
            
        $sampahObject = TaxObject::updateOrCreate(
            ['nop' => 'SMP-74-001'],
            [
                'taxpayer_id' => $ani->id,
                'retribution_type_id' => $sampahType->id ?? 1,
                'opd_id' => Opd::where('code', 'DLH')->first()->id ?? $bapenda->id,
                'name' => 'Rumah Ani (Retribusi Sampah)',
                'address' => 'Jl. Pahlawan No. 45',
                'status' => 'active',
                'approved_at' => Carbon::now()->subMonths(3),
            ]
        );

        // 6. Create Bills & Payments
        $paidBill = Bill::updateOrCreate(
            ['bill_number' => 'INV-202501-001'],
            [
                'taxpayer_id' => $budi->id,
                'tax_object_id' => $parkirObject->id,
                'opd_id' => $dishub->id,
                'retribution_type_id' => $parkirType->id ?? 1,
                'amount' => 50000,
                'status' => 'lunas',
                'period' => '2025-01',
                'due_date' => Carbon::now()->subMonth(),
            ]
        );

        Payment::updateOrCreate(
            ['bill_id' => $paidBill->id],
            [
                'taxpayer_id' => $budi->id,
                'tax_object_id' => $parkirObject->id,
                'amount' => 50000,
                'payment_method' => 'transfer',
                'status' => 'success',
                'billing_period' => '2025-01',
                'paid_at' => Carbon::now()->subWeeks(2),
                'transaction_id' => 'TRANS-' . strtoupper(Str::random(10)),
            ]
        );

        // Pending Bill (PBB)
        Bill::updateOrCreate(
            ['bill_number' => 'INV-202502-002'],
            [
                'taxpayer_id' => $budi->id,
                'tax_object_id' => $pbbObject->id,
                'opd_id' => $bapenda->id,
                'retribution_type_id' => $pbbType->id,
                'amount' => 750000,
                'status' => 'pending',
                'period' => '2025-02',
                'due_date' => Carbon::now()->addDays(15),
            ]
        );

        // 7. Create Pending Verification for Ani (Testing Approval Flow)
        $newObject = TaxObject::create([
            'taxpayer_id' => $ani->id,
            'retribution_type_id' => $kiosType->id ?? 4,
            'opd_id' => Opd::where('code', 'DISPERINDAG')->first()->id ?? 2,
            'name' => 'Kios Pakaian Baru',
            'address' => 'Pasar Karya Blok B',
            'status' => 'pending',
        ]);

        Verification::create([
            'opd_id' => $newObject->opd_id,
            'user_id' => null, // Submitter is a Taxpayer, not an Admin User
            'taxpayer_id' => $ani->id,
            'tax_object_id' => $newObject->id,
            'document_number' => 'REG-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
            'taxpayer_name' => $ani->name,
            'type' => 'object_registration',
            'amount' => 0,
            'status' => 'pending',
            'submitted_at' => Carbon::now()->subDay(),
        ]);

        $this->command->info('Testing Scenarios Seeded: Budi (Parkir, PBB) & Ani (Kios, Sampah, 1 Pending).');
    }
}
