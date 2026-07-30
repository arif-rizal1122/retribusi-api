<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\Bill;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TestingSeeder0000000000000001 extends Seeder
{
    public function run(): void
    {
        $nik = '0000000000000001';
        
        $bapenda = Opd::firstOrCreate(['code' => 'BAPENDA'], ['name' => 'BAPENDA']);
        
        $taxpayer = Taxpayer::updateOrCreate(
            ['nik' => $nik],
            [
                'opd_id' => $bapenda->id,
                'name' => 'Wajib Pajak Model Testing',
                'address' => 'Jl. Testing No. 1, Baubau',
                'phone' => '081111111111',
                'npwpd' => 'P-2-1111111',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        // Retribution Classifications that already exist and appear on Homepage
        $pbbClass = RetributionClassification::where('name', 'PBB-P2')->first();
        $parkirClass = RetributionClassification::where('name', 'Retribusi Pelayanan Parkir')->first();
        $pbjtClass = RetributionClassification::where('name', 'PBJT - Makan dan Minum')->first();

        // Fallbacks if they somehow don't exist
        if (!$pbbClass || !$parkirClass || !$pbjtClass) {
            $this->command->error("Standard classifications not found. Please run classification seeders first.");
            return;
        }

        // Tax Objects
        $pbbObject = TaxObject::updateOrCreate(
            ['nop' => 'PBB-01-001'],
            [
                'taxpayer_id' => $taxpayer->id,
                'retribution_type_id' => $pbbClass->retribution_type_id,
                'retribution_classification_id' => $pbbClass->id,
                'opd_id' => $bapenda->id,
                'name' => 'Rumah Tinggal Testing',
                'address' => 'Jl. Testing No. 1',
                'status' => 'active',
                'approved_at' => Carbon::now()->subMonths(24),
            ]
        );

        $parkirObject = TaxObject::updateOrCreate(
            ['nop' => 'PRK-01-001'],
            [
                'taxpayer_id' => $taxpayer->id,
                'retribution_type_id' => $parkirClass->retribution_type_id,
                'retribution_classification_id' => $parkirClass->id,
                'opd_id' => $bapenda->id,
                'name' => 'Lahan Parkir Toko Testing',
                'address' => 'Jl. Testing No. 2',
                'status' => 'active',
                'approved_at' => Carbon::now()->subMonths(12),
            ]
        );

        $pbjtObject = TaxObject::updateOrCreate(
            ['nop' => 'RST-01-001'],
            [
                'taxpayer_id' => $taxpayer->id,
                'retribution_type_id' => $pbjtClass->retribution_type_id,
                'retribution_classification_id' => $pbjtClass->id,
                'opd_id' => $bapenda->id,
                'name' => 'Warung Kopi Testing',
                'address' => 'Jl. Testing No. 3',
                'status' => 'active',
                'approved_at' => Carbon::now()->subMonths(6),
            ]
        );

        // BILLS - PBB
        // 2024 Lunas
        $billPbb2024 = Bill::updateOrCreate(
            ['bill_number' => 'INV-2024-PBB-01'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $pbbObject->id,
                'opd_id' => $bapenda->id,
                'retribution_type_id' => $pbbClass->retribution_type_id,
                'retribution_classification_id' => $pbbClass->id,
                'amount' => 500000,
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
                'transaction_id' => 'TRX-PBB-2024-01',
                'payment_method' => 'VA_BPD',
                'amount' => 500000,
                'status' => 'success',
                'billing_period' => 'Tahun 2024',
                'paid_at' => Carbon::create(2024, 9, 10),
            ]
        );

        // 2025 Pending
        Bill::updateOrCreate(
            ['bill_number' => 'INV-2025-PBB-01'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $pbbObject->id,
                'opd_id' => $bapenda->id,
                'retribution_type_id' => $pbbClass->retribution_type_id,
                'retribution_classification_id' => $pbbClass->id,
                'amount' => 550000,
                'status' => 'pending',
                'period' => 'Tahun 2025',
                'due_date' => Carbon::create(2025, 9, 30),
            ]
        );

        // 2023 Overdue
        Bill::updateOrCreate(
            ['bill_number' => 'INV-2023-PBB-01'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $pbbObject->id,
                'opd_id' => $bapenda->id,
                'retribution_type_id' => $pbbClass->retribution_type_id,
                'retribution_classification_id' => $pbbClass->id,
                'amount' => 450000,
                'penalty_amount' => 90000,
                'status' => 'overdue',
                'period' => 'Tahun 2023',
                'due_date' => Carbon::create(2023, 9, 30),
            ]
        );

        // BILLS - PARKIR
        // Bulan Lalu - Lunas
        $lastMonth = Carbon::now()->subMonth();
        $billParkirLalu = Bill::updateOrCreate(
            ['bill_number' => 'INV-PRK-'.$lastMonth->format('Ym').'-01'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $parkirObject->id,
                'opd_id' => $bapenda->id,
                'retribution_type_id' => $parkirClass->retribution_type_id,
                'retribution_classification_id' => $parkirClass->id,
                'amount' => 150000,
                'status' => 'paid',
                'period' => $lastMonth->translatedFormat('F Y'),
                'due_date' => $lastMonth->copy()->endOfMonth(),
            ]
        );
        Payment::updateOrCreate(
            ['bill_id' => $billParkirLalu->id],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $parkirObject->id,
                'transaction_id' => 'TRX-PRK-'.$lastMonth->format('Ym').'-01',
                'payment_method' => 'QRIS',
                'amount' => 150000,
                'status' => 'success',
                'billing_period' => $lastMonth->translatedFormat('F Y'),
                'paid_at' => $lastMonth->copy()->addDays(2),
            ]
        );

        // Bulan Ini - Pending
        $thisMonth = Carbon::now();
        Bill::updateOrCreate(
            ['bill_number' => 'INV-PRK-'.$thisMonth->format('Ym').'-01'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $parkirObject->id,
                'opd_id' => $bapenda->id,
                'retribution_type_id' => $parkirClass->retribution_type_id,
                'retribution_classification_id' => $parkirClass->id,
                'amount' => 150000,
                'status' => 'pending',
                'period' => $thisMonth->translatedFormat('F Y'),
                'due_date' => $thisMonth->copy()->endOfMonth(),
            ]
        );

        // BILLS - PBJT (Resto)
        // Bulan Ini - Pending
        Bill::updateOrCreate(
            ['bill_number' => 'INV-PBJT-'.$thisMonth->format('Ym').'-01'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $pbjtObject->id,
                'opd_id' => $bapenda->id,
                'retribution_type_id' => $pbjtClass->retribution_type_id,
                'retribution_classification_id' => $pbjtClass->id,
                'amount' => 300000,
                'status' => 'pending',
                'period' => $thisMonth->translatedFormat('F Y'),
                'due_date' => $thisMonth->copy()->endOfMonth(),
            ]
        );

        // Set previous dummy objects for NIK 01 to deleted so they don't clutter the view
        TaxObject::where('taxpayer_id', $taxpayer->id)
            ->whereNotIn('id', [$pbbObject->id, $parkirObject->id, $pbjtObject->id])
            ->delete();

        $this->command->info('✅ Seeder TestingSeeder0000000000000001: Data berhasil diperbarui. Terhubung dengan layanan beranda dan menghapus data dummy lama.');
    }
}
