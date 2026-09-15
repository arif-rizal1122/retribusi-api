<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\RetributionType;
use App\Models\Bill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaxpayerWithNibSeeder extends Seeder
{
    public function run(): void
    {
        $nik = '7404015307890002';
        $nib = '8123456789012345';
        $nop = '74.72.01.001.001.0001.1';

        $bapenda = Opd::where('code', 'BAPENDA')->first();
        if (!$bapenda) {
            $bapenda = Opd::create(['name' => 'BAPENDA', 'code' => 'BAPENDA']);
        }

        $taxpayer = Taxpayer::updateOrCreate(
            ['nik' => $nik],
            [
                'opd_id' => $bapenda->id,
                'name' => 'Rahmawati S.Pd',
                'address' => 'Jl. kemakmuran No. 88, Kel. Wolio, Kec. Wolio, Kota Baubau',
                'phone' => '085241876320',
                'npwpd' => 'P-2-0000099',
                'password' => Hash::make('password123'),
                'is_active' => true,
                'district' => 'Wolio',
                'sub_district' => 'Wolio',
                'latitude' => -5.47340000,
                'longitude' => 122.59130000,
                'metadata' => [
                    'email' => 'rahmawati@example.com',
                    'mother_name' => 'Siti Aminah',
                ],
            ]
        );

        $pbjtType = RetributionType::where('name', 'like', '%PBJT%')->first();
        if (!$pbjtType) {
            $pbjtType = RetributionType::create([
                'name' => 'PBJT',
                'category' => 'Pajak',
                'icon' => 'file',
                'base_amount' => 0,
                'unit' => 'unit',
                'opd_id' => $bapenda->id,
                'is_active' => true,
            ]);
        }

        $taxObject = TaxObject::updateOrCreate(
            ['nop' => $nop],
            [
                'taxpayer_id' => $taxpayer->id,
                'retribution_type_id' => $pbjtType->id,
                'opd_id' => $bapenda->id,
                'name' => 'Rumah Makan Padang Rahma',
                'address' => 'Jl. Kemakmuran No. 88, Wolio',
                'latitude' => -5.47340000,
                'longitude' => 122.59130000,
                'status' => 'active',
                'approved_at' => Carbon::now()->subMonths(6),
                'metadata' => [
                    'nama_jenis_usaha' => 'Rumah Makan Padang',
                    'omset_penjualan' => 50000000,
                    'keterangan_usaha' => 'Usaha rumah makan padang dengan omzet 50 juta/bulan',
                ],
            ]
        );

        Bill::updateOrCreate(
            ['bill_number' => 'INV-202609-PBJT-099'],
            [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $taxObject->id,
                'opd_id' => $bapenda->id,
                'retribution_type_id' => $pbjtType->id,
                'amount' => 120000,
                'status' => 'pending',
                'period' => 'September 2026',
                'due_date' => Carbon::create(2026, 9, 30),
            ]
        );

        if (DB::getSchemaBuilder()->hasTable('bpn_h2h_mappings')) {
            DB::table('bpn_h2h_mappings')->updateOrInsert(
                ['nib' => $nib],
                [
                    'nop' => $nop,
                    'znt_value' => 1500000,
                    'zona_id' => null,
                    'last_sync_at' => Carbon::now(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
        }

        $this->command->info("✅ Seeder wajib pajak + NIB dibuat:");
        $this->command->info("   NIK     : {$nik}");
        $this->command->info("   Nama    : Rahmawati S.Pd");
        $this->command->info("   Password: password123");
        $this->command->info("   NIB     : {$nib}");
        $this->command->info("   NOP     : {$nop}");
        $this->command->info("   Email   : rahmawati@example.com");
        $this->command->info("   Phone   : 085241876320");
        $this->command->info("   Ibu Kandung: Siti Aminah");
        $this->command->info("");
        $this->command->info("📱 Login Mobile (3 metode):");
        $this->command->info("   1. WhatsApp → Phone: 085241876320 + OTP");
        $this->command->info("   2. NIK (IKD) → NIK: {$nik} + Ibu Kandung: Siti Aminah");
        $this->command->info("   3. Email → Email: rahmawati@example.com + Password: password123");
        $this->command->info("");
        $this->command->info("   Catatan : Tabel bpn_h2h_mappings harus sudah di-migrate");
    }
}
