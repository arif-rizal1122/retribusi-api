<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\User;
use App\Models\UserRetributionAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder Akun Petugas Lapangan Rusunawa — Dinas Perumahan, Kawasan Permukiman dan Pertanahan (DISPERKIM)
 *
 * Mengelola operasional hunian Rusunawa:
 *   - Rusunawa Wameo (Blok A & B)
 *   - Rusunawa Kotamara
 *   - Rusunawa ASN Palagimata
 *
 * Password default: password123
 */
class PerkimOfficerAccountSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Menyiapkan akun petugas lapangan Disperkim (Rusunawa)...');

        // 1. Resolve OPD Disperkim (ID 11)
        $perkim = Opd::where('id', 11)
            ->orWhere('code', 'PERKIM')
            ->orWhere('code', 'DISPERKIM')
            ->orWhere('name', 'like', '%Perumahan%')
            ->first();

        if (! $perkim) {
            $perkim = Opd::create([
                'id' => 11,
                'code' => 'PERKIM',
                'name' => 'Dinas Perumahan, Kawasan Permukiman dan Pertanahan',
                'address' => 'Jl. Balai Kota No. 1, Kota Baubau',
                'phone' => '0401-321654',
                'email' => 'perkim@baubaukota.go.id',
                'status' => 'approved',
                'is_active' => true,
            ]);
        }

        $opdId = $perkim->id;
        $password = Hash::make('password123');

        // 2. Ensure Retribution Type for Rusunawa
        $rusunType = RetributionType::updateOrCreate(
            [
                'opd_id' => $opdId,
                'name' => 'Retribusi Pemakaian Kekayaan Daerah - Sewa Rusunawa',
            ],
            [
                'category' => 'Sewa Aset',
                'icon' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703394/retribusi/mobile/icons/imb.png',
                'base_amount' => 125000,
                'unit' => 'per bulan',
                'is_active' => true,
            ]
        );

        // 3. Daftar Petugas Lapangan Rusunawa Disperkim
        $officers = [
            [
                'name' => 'Petugas Lapangan Disperkim',
                'email' => 'petugas.perkim@baubaukota.go.id',
                'phone' => '085241200000',
                'lokasi' => 'Semua Kompleks Rusunawa',
                'jabatan' => 'Petugas Verifikator & Juru Pungut Rusunawa',
                'sub_role' => 'petugas_umum',
            ],
            [
                'name' => 'La Ode Ruslan, S.Sos',
                'email' => 'ruslan.rusun@mpad.online',
                'phone' => '085241200001',
                'lokasi' => 'Rusunawa Wameo Blok A & B',
                'jabatan' => 'Petugas Lapangan Rusunawa Wameo',
                'sub_role' => 'petugas_rusun_wameo',
            ],
            [
                'name' => 'Wa Ode Farida, A.Md',
                'email' => 'farida.rusun@mpad.online',
                'phone' => '085241200002',
                'lokasi' => 'Rusunawa Kotamara',
                'jabatan' => 'Petugas Lapangan Rusunawa Kotamara',
                'sub_role' => 'petugas_rusun_kotamara',
            ],
            [
                'name' => 'Syamsul Bahri, SE',
                'email' => 'syamsul.rusun@mpad.online',
                'phone' => '085241200003',
                'lokasi' => 'Rusunawa ASN Palagimata',
                'jabatan' => 'Petugas Lapangan Rusunawa ASN Palagimata',
                'sub_role' => 'petugas_rusun_palagimata',
            ],
        ];

        foreach ($officers as $officerData) {
            $user = User::updateOrCreate(
                ['email' => $officerData['email']],
                [
                    'name' => $officerData['name'],
                    'password' => $password,
                    'role' => 'petugas',
                    'opd_id' => $opdId,
                    'phone' => $officerData['phone'],
                    'status' => 'active',
                    'metadata' => [
                        'role_code' => 'petugas_perkim',
                        'sub_role' => $officerData['sub_role'],
                        'opd_code' => 'PERKIM',
                        'department' => 'UPTD Pengelolaan Rusunawa',
                        'jabatan' => $officerData['jabatan'],
                        'lokasi_tugas' => $officerData['lokasi'],
                        'can_collect_offline' => true,
                        'can_survey_unit' => true,
                    ],
                ]
            );

            // Assignment ke Retribution Type
            UserRetributionAssignment::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'retribution_type_id' => $rusunType->id,
                    'retribution_classification_id' => null,
                ],
                [
                    'is_active' => true,
                ]
            );

            $this->command->info("  ✓ {$officerData['name']} ({$officerData['email']})");
        }

        $this->command->info('Sinkronisasi akun Petugas Disperkim selesai!');
    }
}
