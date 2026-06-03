<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\User;
use App\Models\UserRetributionAssignment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoLoginSeeder extends Seeder
{
    public function run(): void
    {
        $bapenda = $this->ensureOpd('BAPENDA', 'Badan Pendapatan Daerah');
        $dishub = $this->ensureOpd('DISHUB', 'Dinas Perhubungan');
        $disperindag = $this->ensureOpd('DISPERINDAG', 'Dinas Perindustrian dan Perdagangan');
        $dlh = $this->ensureOpd('DLH', 'Dinas Lingkungan Hidup');

        $this->ensurePortalUsers($bapenda);

        $parkir = $this->ensureType($dishub, 'Retribusi Parkir Motor', 'Parkir', 'bike', 2000);
        $kios = $this->ensureType($disperindag, 'Retribusi Kios Pasar', 'Pasar', 'store', 150000);
        $sampah = $this->ensureType($dlh, 'Retribusi Persampahan', 'Kebersihan', 'trash', 30000);
        $pbb = $this->ensureType($bapenda, 'PBB-P2', 'Pajak Bumi dan Bangunan', 'home', 0);
        $reklame = $this->ensureType($bapenda, 'Pajak Reklame', 'Pajak', 'image', 0);
        $pbjt = $this->ensureType($bapenda, 'PBJT', 'Pajak', 'file', 0);
        $this->ensurePetugasAssignment('petugas@bapenda.go.id', $pbb);
        $petugas = User::where('email', 'petugas@bapenda.go.id')->first();

        $budi = Taxpayer::updateOrCreate(
            ['nik' => '1234567890123456'],
            [
                'opd_id' => $bapenda->id,
                'created_by' => $petugas?->id,
                'name' => 'Budi Santoso',
                'address' => 'Jl. Wolter Monginsidi No. 12, Baubau',
                'phone' => '081234567890',
                'npwpd' => 'P-2-0000001',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $ani = Taxpayer::updateOrCreate(
            ['nik' => '1234567890123457'],
            [
                'opd_id' => $bapenda->id,
                'created_by' => $petugas?->id,
                'name' => 'Ani Lestari',
                'address' => 'Jl. Pahlawan No. 45, Baubau',
                'phone' => '081234567899',
                'npwpd' => 'P-2-0000002',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $budiParkir = $this->ensureObject($budi, $dishub, $parkir, 'DEMO-PRK-BUDI', 'Lahan Parkir Toko Budi', 'Jl. Merdeka No. 5', -5.4633, 122.6012);
        $budiPbb = $this->ensureObject($budi, $bapenda, $pbb, 'DEMO-PBB-BUDI', 'Rumah Tinggal Budi', 'Jl. Wolter Monginsidi No. 12', -5.4645, 122.6025);
        $budiReklame = $this->ensureObject($budi, $bapenda, $reklame, 'DEMO-RKL-BUDI', 'Papan Reklame Toko Budi', 'Jl. Merdeka No. 5', -5.4633, 122.6012);
        $budiPbjt = $this->ensureObject($budi, $bapenda, $pbjt, 'DEMO-PBJT-BUDI', 'Warung Makan Budi', 'Jl. Wolter Monginsidi No. 12', -5.4645, 122.6025);
        $budiKios = $this->ensureObject($budi, $disperindag, $kios, 'DEMO-KIO-BUDI', 'Kios Sentra Kuliner Budi', 'Pasar Karya No. 10', -5.4621, 122.6042);
        $budiSampah = $this->ensureObject($budi, $dlh, $sampah, 'DEMO-SMP-BUDI', 'Ruko Budi (Sampah)', 'Jl. Ahmad Yani No. 8', -5.4633, 122.6012);

        $aniKios = $this->ensureObject($ani, $disperindag, $kios, 'DEMO-KIO-ANI', 'Kios Sembako Ani', 'Pasar Karya No. 10', -5.4621, 122.6042);
        $aniSampah = $this->ensureObject($ani, $dlh, $sampah, 'DEMO-SMP-ANI', 'Rumah Ani - Retribusi Sampah', 'Jl. Pahlawan No. 45', -5.4612, 122.6071);

        $this->ensureBill($budi, $budiParkir, $dishub, $parkir, 'DEMO-INV-PRK-BUDI-2026-01', 50000, 'Januari 2026', Carbon::create(2026, 1, 31));
        $this->ensureBill($budi, $budiPbb, $bapenda, $pbb, 'DEMO-INV-PBB-BUDI-2026', 750000, 'Tahun 2026', Carbon::create(2026, 9, 30));
        $this->ensureBill($budi, $budiReklame, $bapenda, $reklame, 'DEMO-INV-RKL-BUDI-2026', 350000, 'Tahun 2026', Carbon::create(2026, 10, 31));
        $this->ensureBill($budi, $budiPbjt, $bapenda, $pbjt, 'DEMO-INV-PBJT-BUDI-2026-01', 120000, 'Januari 2026', Carbon::create(2026, 1, 31));
        $this->ensureBill($budi, $budiKios, $disperindag, $kios, 'DEMO-INV-KIO-BUDI-2026-01', 150000, 'Januari 2026', Carbon::create(2026, 1, 31));
        $this->ensureBill($budi, $budiSampah, $dlh, $sampah, 'DEMO-INV-SMP-BUDI-2026-01', 25000, 'Januari 2026', Carbon::create(2026, 1, 31));

        $this->ensureBill($ani, $aniKios, $disperindag, $kios, 'DEMO-INV-KIO-ANI-2026-01', 150000, 'Januari 2026', Carbon::create(2026, 1, 31));
        $this->ensureBill($ani, $aniSampah, $dlh, $sampah, 'DEMO-INV-SMP-ANI-2026-01', 30000, 'Januari 2026', Carbon::create(2026, 1, 31));

        $this->command?->info('Demo login accounts synchronized.');
    }

    private function ensureOpd(string $code, string $name): Opd
    {
        return Opd::updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'status' => 'approved',
                'is_active' => true,
            ]
        );
    }

    private function ensurePortalUsers(Opd $bapenda): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@sipanda.online'],
            [
                'name' => 'Dev Super Admin',
                'password' => Hash::make('Mpad123#'),
                'role' => 'super_admin',
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
            ['email' => 'admin@bapenda.go.id'],
            [
                'name' => 'Admin Bapenda Demo',
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
    }

    private function ensureType(Opd $opd, string $name, string $category, string $icon, float $baseAmount): RetributionType
    {
        return RetributionType::updateOrCreate(
            ['opd_id' => $opd->id, 'name' => $name],
            [
                'category' => $category,
                'icon' => $icon,
                'base_amount' => $baseAmount,
                'unit' => 'unit',
                'is_active' => true,
            ]
        );
    }

    private function ensurePetugasAssignment(string $email, RetributionType $type): void
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            return;
        }

        UserRetributionAssignment::updateOrCreate(
            [
                'user_id' => $user->id,
                'retribution_type_id' => $type->id,
                'retribution_classification_id' => null,
            ],
            []
        );
    }

    private function ensureObject(Taxpayer $taxpayer, Opd $opd, RetributionType $type, string $nop, string $name, string $address, float $lat, float $lng): TaxObject
    {
        return TaxObject::updateOrCreate(
            ['nop' => $nop],
            [
                'taxpayer_id' => $taxpayer->id,
                'opd_id' => $opd->id,
                'retribution_type_id' => $type->id,
                'name' => $name,
                'address' => $address,
                'latitude' => $lat,
                'longitude' => $lng,
                'status' => 'active',
                'approved_at' => now(),
            ]
        );
    }

    private function ensureBill(Taxpayer $taxpayer, TaxObject $object, Opd $opd, RetributionType $type, string $number, float $amount, string $period, Carbon $dueDate): Bill
    {
        return Bill::updateOrCreate(
            ['bill_number' => $number],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $object->id,
                'opd_id' => $opd->id,
                'retribution_type_id' => $type->id,
                'amount' => $amount,
                'status' => 'pending',
                'period' => $period,
                'due_date' => $dueDate,
            ]
        );
    }
}
