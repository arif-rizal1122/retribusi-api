<?php

namespace Database\Seeders;

use App\Models\AssetItem;
use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Master data modul Aset PUPR: Sewa Alat Berat, Kendaraan, dan Sedot Kakus.
 * Dasar: Perda 1/2024 (Pemanfaatan Kekayaan Daerah) + SOP UPTD Workshop PUPR.
 */
class PuprAssetSeeder extends Seeder
{
    public function run(): void
    {
        $pupr = Opd::updateOrCreate(
            ['code' => 'PUPR'],
            [
                'name' => 'Dinas Pekerjaan Umum dan Penataan Ruang',
                'address' => 'Jl. Balai Kota No. 1, Bau-Bau',
                'email' => 'pupr@baubaukota.go.id',
                'status' => 'approved',
                'is_active' => true,
            ]
        );

        // Akun petugas lapangan PUPR (inspeksi pra/pasca)
        $petugas = User::updateOrCreate(
            ['email' => 'pupr.petugas@baubaukota.go.id'],
            [
                'name' => 'Petugas UPTD Workshop PUPR',
                'password' => Hash::make('password123'),
                'role' => 'petugas',
                'opd_id' => $pupr->id,
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'pupr.opd@baubaukota.go.id'],
            [
                'name' => 'Admin OPD PUPR',
                'password' => Hash::make('password123'),
                'role' => 'opd',
                'opd_id' => $pupr->id,
                'status' => 'active',
            ]
        );

        // Retribusi tipe: Sewa Alat Berat
        $type = RetributionType::updateOrCreate(
            ['opd_id' => $pupr->id, 'name' => 'Sewa Alat Berat'],
            [
                'category' => 'Pemanfaatan Kekayaan Daerah',
                'base_amount' => 0,
                'unit' => 'per jam',
                'is_active' => true,
            ]
        );

        // Klasifikasi tagihan denda overtime
        RetributionClassification::updateOrCreate(
            ['opd_id' => $pupr->id, 'code' => 'DENDA-OVERTIME-SEWA-ALAT'],
            [
                'retribution_type_id' => $type->id,
                'name' => 'Denda Overtime Sewa Alat',
                'description' => 'Tagihan otomatis bila jam operasional melebihi jam ter-book (capping 8 jam/hari).',
                'is_self_assessment' => false,
            ]
        );

        // Unit aset awal (demo + realistis)
        $assets = [
            ['ALAT-01', 'Excavator Komatsu PC200', 'alat-berat', 'Komatsu PC200-8', 'Excavator kapasitas 0.8 m3, dump truck 10 ton', 350000, 'per jam', true, 'Gudang Alat Berat PUPR'],
            ['ALAT-02', 'Bulldozer D65E', 'alat-berat', 'Komatsu D65E-12', 'Track type bulldozer dozer shovel', 450000, 'per jam', true, 'Gudang Alat Berat PUPR'],
            ['ALAT-03', 'Wheel Loader WA500', 'alat-berat', 'Komatsu WA500', 'Loader 3.5 m3 bucket, forward/dump', 300000, 'per jam', true, 'Gudang Alat Berat PUPR'],
            ['ALAT-04', 'Vibro Roller 8 Ton', 'alat-berat', 'Sakai SW800', 'Single drum vibratory roller 8 ton', 250000, 'per jam', true, 'Gudang Alat Berat PUPR'],
            ['ALAT-05', 'Dump Truck 10 Ton', 'kendaraan', 'Mitsubishi Fuso HD', 'Dump truck 10 ton, bak dump', 400000, 'per jam', true, 'Pool Kendaraan PUPR'],
            ['ALAT-06', 'Sedot Kakus / Mobil Tinja', 'sedot-kakus', 'Izusu NPR Dropbox', 'Armada sedot limbah tinja 3000 liter', 250000, 'per rit', false, 'Pool UPTD Workshop PUPR'],
            ['ALAT-07', 'Tronton 20 Ton (Mobilisasi)', 'kendaraan', 'Hino FM 260', 'Tronton untuk mobilisasi alat berat antar lokasi', 500000, 'per jam', true, 'Pool Kendaraan PUPR'],
            ['ALAT-08', 'Mesin Molen 0.5 m3', 'alat-berat', 'Dongi', 'Concrete mixer 0.5 m3, diesel engine', 150000, 'per jam', true, 'Gudang Alat Berat PUPR'],
        ];

        foreach ($assets as [$code, $name, $category, $merk, $spec, $tarif, $satuan, $wajibTronton, $lokasi]) {
            $item = AssetItem::withoutGlobalScopes()
                ->where('opd_id', $pupr->id)
                ->where('code', $code)
                ->first();

            if (!$item) {
                AssetItem::create([
                    'opd_id' => $pupr->id,
                    'code' => $code,
                    'name' => $name,
                    'category' => $category,
                    'merk_type' => $merk,
                    'spesifikasi' => $spec,
                    'kondisi' => 'Baik',
                    'status_operasional' => 'Tersedia',
                    'lokasi' => $lokasi,
                    'tarif' => $tarif,
                    'satuan_tarif' => $satuan,
                    'wajib_tronton' => $wajibTronton,
                    'pic' => 'Kepala UPTD Workshop PUPR',
                    'is_active' => true,
                    'is_demo' => false,
                ]);
            }
        }

        // ---------------------------------------------------------------------
        // Wajib pajak tanggungan petugas UPTD Workshop PUPR.
        // Direlasikan via kolom created_by (petugas hanya melihat WP ciptaannya
        // + retribusi tipe Sewa Alat Berat pada pivot taxpayer_retribution_type).
        // ---------------------------------------------------------------------
        $wajibPajakTanggungan = [
            [
                'nik' => '7471011503890001',
                'name' => 'CV. Karya Bangun Baubau',
                'address' => 'Jl. Betoambari No. 88, Kel. Batulo, Kec. Wolio, Baubau',
                'district' => 'Wolio',
                'sub_district' => 'Batulo',
                'phone' => '081234567890',
                'object_name' => 'Sewa Excavator & Dump Truck - Proyek Pengerukan Drainase Stadion',
                'object_address' => 'Jl. Stadion, Kel. Bataraguru, Baubau',
                'latitude' => '-5.46310000',
                'longitude' => '122.60670000',
            ],
            [
                'nik' => '7471016205900002',
                'name' => 'Andi Muhammad Rais',
                'address' => 'Jl. Pahlawan No. 12, Kel. Wale, Kec. Wolio, Baubau',
                'district' => 'Wolio',
                'sub_district' => 'Wale',
                'phone' => '082199887766',
                'object_name' => 'Jasa Sedot Kakus / Mobil Tinja - Rumah Tinggal',
                'object_address' => 'Jl. Nusantara No. 45, Kel. Bone-Bone, Baubau',
                'latitude' => '-5.45180000',
                'longitude' => '122.59620000',
            ],
        ];

        foreach ($wajibPajakTanggungan as $data) {
            $taxpayer = Taxpayer::firstOrNew(['nik' => $data['nik']]);

            $taxpayer->fill([
                'opd_id' => $pupr->id,
                'name' => $data['name'],
                'address' => $data['address'],
                'district' => $data['district'],
                'sub_district' => $data['sub_district'],
                'phone' => $data['phone'],
                'object_name' => $data['object_name'],
                'object_address' => $data['object_address'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'is_active' => true,
                'created_by' => $petugas->id,
                'npwpd' => $taxpayer->npwpd ?: Taxpayer::resolveNpwpd($data['nik']),
            ]);

            $taxpayer->save();

            $taxpayer->retributionTypes()->syncWithoutDetaching([$type->id]);

            // Tax object (sumber marker peta) supaya wajib pajak muncul di GPS/map.
            TaxObject::updateOrCreate(
                ['nop' => 'AST-' . $data['nik']],
                [
                    'taxpayer_id' => $taxpayer->id,
                    'retribution_type_id' => $type->id,
                    'opd_id' => $pupr->id,
                    'name' => $data['object_name'],
                    'address' => $data['object_address'],
                    'district' => $data['district'],
                    'sub_district' => $data['sub_district'],
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'transaction_type' => 'perekaman_data',
                    'status' => 'active',
                    'is_active' => true,
                ]
            );
        }

        $infoWp = collect($wajibPajakTanggungan)->pluck('name')->implode(', ');
        $this->command?->info("Seeded PUPR Asset: {$pupr->name}, " . count($assets) . ' unit aset.');
        $this->command?->info("Wajib pajak tanggungan petugas PUPR: {$infoWp}.");
    }
}