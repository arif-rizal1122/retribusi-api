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

        // 4. Create Bills - ALL PENDING (Belum Lunas)
        // ==========================================
        // PARKIR BILLS (Monthly - DISHUB)
        // ==========================================

        // Parkir - Jan 2025
        Bill::updateOrCreate(
            ['bill_number' => 'INV-202501-PRK-001'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $parkirObject->id,
                'opd_id' => $dishub->id,
                'retribution_type_id' => $parkirType->id,
                'amount' => 50000,
                'status' => 'pending',
                'period' => 'Januari 2025',
                'due_date' => Carbon::create(2025, 1, 31),
            ]
        );

        // Parkir - Feb 2025
        Bill::updateOrCreate(
            ['bill_number' => 'INV-202502-PRK-001'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $parkirObject->id,
                'opd_id' => $dishub->id,
                'retribution_type_id' => $parkirType->id,
                'amount' => 50000,
                'status' => 'pending',
                'period' => 'Februari 2025',
                'due_date' => Carbon::create(2025, 2, 28),
            ]
        );

        // Parkir - Mar 2025
        Bill::updateOrCreate(
            ['bill_number' => 'INV-202503-PRK-001'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $parkirObject->id,
                'opd_id' => $dishub->id,
                'retribution_type_id' => $parkirType->id,
                'amount' => 50000,
                'status' => 'pending',
                'period' => 'Maret 2025',
                'due_date' => Carbon::create(2025, 3, 31),
            ]
        );

        // ==========================================
        // PBB BILLS (Yearly - BAPENDA)
        // ==========================================

        // PBB - Tahun 2024
        Bill::updateOrCreate(
            ['bill_number' => 'INV-2024-PBB-001'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $pbbObject->id,
                'opd_id' => $bapenda->id,
                'retribution_type_id' => $pbbType->id,
                'amount' => 750000,
                'status' => 'pending',
                'period' => 'Tahun 2024',
                'due_date' => Carbon::create(2024, 9, 30),
            ]
        );

        // PBB - Tahun 2025
        Bill::updateOrCreate(
            ['bill_number' => 'INV-2025-PBB-001'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $pbbObject->id,
                'opd_id' => $bapenda->id,
                'retribution_type_id' => $pbbType->id,
                'amount' => 800000,
                'status' => 'pending',
                'period' => 'Tahun 2025',
                'due_date' => Carbon::create(2025, 9, 30),
            ]
        );

        // ==========================================
        // SAMPAH BILLS (Monthly - DLH)
        // ==========================================

        // Sampah - Nov 2024
        Bill::updateOrCreate(
            ['bill_number' => 'INV-202411-SMP-001'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $sampahObject->id,
                'opd_id' => $dlh->id,
                'retribution_type_id' => $sampahType->id,
                'amount' => 25000,
                'penalty_amount' => 5000,
                'status' => 'pending',
                'period' => 'November 2024',
                'due_date' => Carbon::create(2024, 11, 30),
            ]
        );

        // Sampah - Des 2024
        Bill::updateOrCreate(
            ['bill_number' => 'INV-202412-SMP-001'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $sampahObject->id,
                'opd_id' => $dlh->id,
                'retribution_type_id' => $sampahType->id,
                'amount' => 25000,
                'penalty_amount' => 5000,
                'status' => 'pending',
                'period' => 'Desember 2024',
                'due_date' => Carbon::create(2024, 12, 31),
            ]
        );

        // Sampah - Jan 2025
        Bill::updateOrCreate(
            ['bill_number' => 'INV-202501-SMP-001'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $sampahObject->id,
                'opd_id' => $dlh->id,
                'retribution_type_id' => $sampahType->id,
                'amount' => 25000,
                'status' => 'pending',
                'period' => 'Januari 2025',
                'due_date' => Carbon::create(2025, 1, 31),
            ]
        );

        // Sampah - Feb 2025
        Bill::updateOrCreate(
            ['bill_number' => 'INV-202502-SMP-001'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $sampahObject->id,
                'opd_id' => $dlh->id,
                'retribution_type_id' => $sampahType->id,
                'amount' => 25000,
                'status' => 'pending',
                'period' => 'Februari 2025',
                'due_date' => Carbon::create(2025, 2, 28),
            ]
        );

        // Sampah - Mar 2025
        Bill::updateOrCreate(
            ['bill_number' => 'INV-202503-SMP-001'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $sampahObject->id,
                'opd_id' => $dlh->id,
                'retribution_type_id' => $sampahType->id,
                'amount' => 25000,
                'status' => 'pending',
                'period' => 'Maret 2025',
                'due_date' => Carbon::create(2025, 3, 31),
            ]
        );

        $this->command->info('✅ Seeder NIK 1234567890123456: 3 objek pajak, 10 tagihan (semua BELUM LUNAS).');
    }
}
