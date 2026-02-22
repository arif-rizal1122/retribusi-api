<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\Bill;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;

class NIK1234567890123456Seeder extends Seeder
{
    public function run(): void
    {
        $nik = '1234567890123456';
        
        // 1. Ensure OPDs exist
        $bapenda = Opd::where('code', 'BAPENDA')->first();
        $dishub = Opd::where('code', 'DISHUB')->first();
        $dlh = Opd::where('code', 'DLH')->first();

        if (!$bapenda) {
            $bapenda = Opd::create(['name' => 'BAPENDA', 'code' => 'BAPENDA']);
        }
        if (!$dishub) {
            $dishub = Opd::create(['name' => 'DISHUB', 'code' => 'DISHUB']);
        }
        if (!$dlh) {
            $dlh = Opd::create(['name' => 'DLH', 'code' => 'DLH']);
        }

        // 2. Create/Update Taxpayer
        $taxpayer = Taxpayer::updateOrCreate(
            ['nik' => $nik],
            [
                'opd_id' => $bapenda->id,
                'name' => 'Budi Santoso',
                'address' => 'Jl. Wolter Monginsidi No. 12, Baubau',
                'phone' => '081234567890',
                'npwpd' => 'P-2-0000001',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        // 3. Create Multiple Tax Objects for this NIK
        // Object 1: Parkir (DISHUB)
        $parkirType = RetributionType::where('name', 'like', '%Parkir%')->first() ?? RetributionType::factory()->create(['name' => 'Retribusi Parkir', 'opd_id' => $dishub->id]);
        $parkirObject = TaxObject::updateOrCreate(
            ['nop' => 'PRK-74-123'],
            [
                'taxpayer_id' => $taxpayer->id,
                'retribution_type_id' => $parkirType->id,
                'opd_id' => $dishub->id,
                'name' => 'Lahan Parkir Toko Budi',
                'address' => 'Jl. Merdeka No. 5',
                'latitude' => -5.463300,
                'longitude' => 122.601200,
                'status' => 'active',
                'approved_at' => Carbon::now()->subMonths(3),
            ]
        );

        // Object 2: PBB (BAPENDA)
        $pbbType = RetributionType::where('name', 'like', '%PBB%')->first() ?? RetributionType::factory()->create(['name' => 'PBB-P2', 'opd_id' => $bapenda->id]);
        $pbbObject = TaxObject::updateOrCreate(
            ['nop' => 'PBB-74-123'],
            [
                'taxpayer_id' => $taxpayer->id,
                'retribution_type_id' => $pbbType->id,
                'opd_id' => $bapenda->id,
                'name' => 'Rumah Tinggal Budi',
                'address' => 'Jl. Wolter Monginsidi No. 12',
                'latitude' => -5.464500,
                'longitude' => 122.602500,
                'status' => 'active',
                'approved_at' => Carbon::now()->subMonths(12),
                'metadata' => ['luas_bumi' => 200, 'luas_bangunan' => 100]
            ]
        );

        // Object 3: Sampah (DLH)
        $sampahType = RetributionType::where('name', 'like', '%Sampah%')->first() ?? RetributionType::factory()->create(['name' => 'Retribusi Pelayanan Persampahan', 'opd_id' => $dlh->id]);
        $sampahObject = TaxObject::updateOrCreate(
            ['nop' => 'SMP-74-123'],
            [
                'taxpayer_id' => $taxpayer->id,
                'retribution_type_id' => $sampahType->id,
                'opd_id' => $dlh->id,
                'name' => 'Ruko Budi (Sampah)',
                'address' => 'Jl. Ahmad Yani No. 8',
                'status' => 'active',
                'approved_at' => Carbon::now()->subMonths(6),
            ]
        );

        // 4. Create Bills
        // Paid Bill
        $paidBill = Bill::updateOrCreate(
            ['bill_number' => 'INV-202501-123'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $parkirObject->id,
                'opd_id' => $dishub->id,
                'retribution_type_id' => $parkirType->id,
                'amount' => 50000,
                'status' => 'paid',
                'period' => '2025-01',
                'due_date' => Carbon::now()->subMonth(),
            ]
        );

        Payment::updateOrCreate(
            ['bill_id' => $paidBill->id],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $parkirObject->id,
                'amount' => 50000,
                'payment_method' => 'VA_BCA',
                'status' => 'success',
                'billing_period' => '2025-01',
                'paid_at' => Carbon::now()->subWeeks(2),
                'transaction_id' => 'TRANS-' . strtoupper(Str::random(10)),
            ]
        );

        // Pending Bill
        Bill::updateOrCreate(
            ['bill_number' => 'INV-202502-124'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $pbbObject->id,
                'opd_id' => $bapenda->id,
                'retribution_type_id' => $pbbType->id,
                'amount' => 150000,
                'status' => 'pending',
                'period' => '2025-02',
                'due_date' => Carbon::now()->addDays(20),
            ]
        );

        // Overdue Bill
        Bill::updateOrCreate(
            ['bill_number' => 'INV-202412-125'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $sampahObject->id,
                'opd_id' => $dlh->id,
                'retribution_type_id' => $sampahType->id,
                'amount' => 25000,
                'status' => 'pending',
                'period' => '2024-12',
                'due_date' => Carbon::now()->subMonths(2),
            ]
        );

        $this->command->info('Seeder for NIK 1234567890123456 completed successfully.');
    }
}
