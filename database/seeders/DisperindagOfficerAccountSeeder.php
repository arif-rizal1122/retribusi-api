<?php

namespace Database\Seeders;

use App\Models\MarketOfficerAssignment;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder Akun Petugas Lapangan (Juru Pungut Pasar) — Dinas Perdaginkop
 *
 * Berdasarkan SK Penugasan Kabid Usaha Perdagangan: KASMAN, SE
 * (NIP. 19790205 200604 1 007), tanggal 20 Mei 2026.
 *
 * 7 Juru Pungut Pasar:
 *   - 6 orang di Pasar Wameo (Batupoaro)
 *   - 1 orang di Pasar Karya Baru (Sorawolio)
 *
 * Password default: Pasar@2026 (wajib diganti saat login pertama di produksi).
 * Email placeholder: {nama}.pasar@mpad.online
 */
class DisperindagOfficerAccountSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Menyiapkan akun petugas lapangan Dinas Perdaginkop...');

        // Resolve OPD Disperindag/Perdaginkop
        $disperindag = Opd::where('code', 'DISPERINDAG')
            ->orWhere('code', 'PERDAGINKOP')
            ->orWhere('code', 'DISPERINDAGKOP')
            ->orWhere('name', 'like', '%Perdagangan%')
            ->first();

        if (!$disperindag) {
            $disperindag = Opd::create([
                'code' => 'DISPERINDAG',
                'name' => 'Dinas Perdagangan, Perindustrian, Koperasi, dan UKM',
                'address' => 'Jl. Balai Kota / Jl. Pasar Wameo, Kota Baubau',
                'phone' => '0401-654321',
                'email' => 'disperindag@baubaukota.go.id',
                'status' => 'approved',
                'is_active' => true,
            ]);
        }

        $opdId = $disperindag->id;
        $password = Hash::make('Pasar@2026');

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // 1. ADMIN OPD DISPERINDAGKOP (Dashboard retribusi-admin)
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        User::updateOrCreate(
            ['email' => 'admin.pasar@mpad.online'],
            [
                'name' => 'Admin Dinas Perdaginkop',
                'password' => $password,
                'role' => 'opd',
                'opd_id' => $opdId,
                'phone' => '085241000015',
                'status' => 'active',
                'metadata' => [
                    'role_code' => 'admin_disperindag',
                    'opd_role' => 'Admin OPD Disperindagkop',
                    'role_group' => 'opd',
                    'department' => 'Bidang Usaha Perdagangan',
                    'jabatan' => 'Admin Modul Pasar M-PAD',
                ],
            ]
        );

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // 2. SUPERVISOR / MANDOR PASAR (verifikasi setoran antara)
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        User::updateOrCreate(
            ['email' => 'mandor.pasar@mpad.online'],
            [
                'name' => 'Mandor Pasar Wameo',
                'password' => $password,
                'role' => 'petugas',
                'opd_id' => $opdId,
                'phone' => '085241000016',
                'status' => 'active',
                'metadata' => [
                    'role_code' => 'supervisor_pasar',
                    'sub_role' => 'mandor_pasar',
                    'department' => 'Bidang Usaha Perdagangan',
                    'jabatan' => 'Mandor / Supervisor Pos Pasar Wameo',
                    'can_reset_cash_limit' => true,
                    'can_verify_drop' => true,
                ],
            ]
        );

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // 3. 7 JURU PUNGUT PASAR (Kolektor Karcis Harian & Los/Kios)
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        $officers = [
            [
                'name' => 'Nurfia',
                'email' => 'nurfia.pasar@mpad.online',
                'phone' => '085241100001',
                'market' => 'PASAR WAMEO',
                'areas' => ['Los A', 'Los B', 'Los C', 'Kios Semi Permanen / Los Orens'],
                'collects_daily_pkl' => true,
                'jabatan' => 'Juru Pungut Karcis Harian & Los A/B/C',
            ],
            [
                'name' => 'Nurida',
                'email' => 'nurida.pasar@mpad.online',
                'phone' => '085241100002',
                'market' => 'PASAR WAMEO',
                'areas' => ['Los D', 'Los E', 'Los F', 'Higienis A', 'Los A (RB 64 Los)'],
                'collects_daily_pkl' => false,
                'jabatan' => 'Juru Pungut Los D/E/F & Higienis A',
            ],
            [
                'name' => 'Wa Ode Masria',
                'email' => 'masria.pasar@mpad.online',
                'phone' => '085241100003',
                'market' => 'PASAR WAMEO',
                'areas' => ['Blok A Lama', 'Blok B Lama', 'Kios 23', 'Los C (los Baja Ringan)'],
                'collects_daily_pkl' => false,
                'jabatan' => 'Juru Pungut Blok A/B Lama & Kios 23',
            ],
            [
                'name' => 'Samida',
                'email' => 'samida.pasar@mpad.online',
                'phone' => '085241100004',
                'market' => 'PASAR WAMEO',
                'areas' => ['Gedung Biru', 'Gedung Orens'],
                'collects_daily_pkl' => true,
                'jabatan' => 'Juru Pungut Karcis Harian & Gedung Biru/Orens',
            ],
            [
                'name' => 'Anwar Ndawari',
                'email' => 'anwar.pasar@mpad.online',
                'phone' => '085241100005',
                'market' => 'PASAR WAMEO',
                'areas' => ['Higienis C', 'Los B (Gedung Kios 23)'],
                'collects_daily_pkl' => false,
                'jabatan' => 'Juru Pungut Higienis C & Los B',
            ],
            [
                'name' => 'Sandi',
                'email' => 'sandi.pasar@mpad.online',
                'phone' => '085241100006',
                'market' => 'PASAR WAMEO',
                'areas' => ['Blok E', 'Blok F', 'Higienis B', 'Los B (Gedung Biru)'],
                'collects_daily_pkl' => false,
                'jabatan' => 'Juru Pungut Blok E/F & Higienis B',
            ],
            [
                'name' => 'La Jede',
                'email' => 'lajede.pasar@mpad.online',
                'phone' => '085241100007',
                'market' => 'PASAR KARYA BARU',
                'areas' => ['Karya Baru Tahap I', 'Karya Baru Tahap II'],
                'collects_daily_pkl' => true,
                'jabatan' => 'Juru Pungut Pasar Karya Baru (Tahap I & II)',
            ],
        ];

        $created = 0;
        foreach ($officers as $off) {
            $user = User::updateOrCreate(
                ['email' => $off['email']],
                [
                    'name' => $off['name'],
                    'password' => $password,
                    'role' => 'petugas',
                    'opd_id' => $opdId,
                    'phone' => $off['phone'],
                    'status' => 'active',
                    'metadata' => [
                        'role_code' => 'petugas_disperindag',
                        'sub_role' => 'juru_pungut_pasar',
                        'department' => 'Bidang Usaha Perdagangan',
                        'jabatan' => $off['jabatan'],
                        'assigned_market' => $off['market'],
                        'assigned_areas' => $off['areas'],
                        'collects_daily_pkl' => $off['collects_daily_pkl'],
                    ],
                ]
            );

            // Link ke tabel market_officer_assignments jika tersedia
            if (class_exists(MarketOfficerAssignment::class)) {
                $assignment = MarketOfficerAssignment::where('officer_name', $off['name'])
                    ->where('market_name', $off['market'])
                    ->first();

                if ($assignment && !$assignment->user_id) {
                    $assignment->update(['user_id' => $user->id]);
                    $this->command->info("  ✔ Linked {$off['name']} → assignment #{$assignment->id}");
                }
            }

            $created++;
        }

        $this->command->info('');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info("Akun Petugas Disperindagkop berhasil disiapkan: {$created} juru pungut");
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('');
        $this->command->info('ADMIN OPD:');
        $this->command->info('  admin.pasar@mpad.online / Pasar@2026');
        $this->command->info('');
        $this->command->info('SUPERVISOR:');
        $this->command->info('  mandor.pasar@mpad.online / Pasar@2026');
        $this->command->info('');
        $this->command->info('PETUGAS LAPANGAN (7 Juru Pungut):');
        foreach ($officers as $off) {
            $pkl = $off['collects_daily_pkl'] ? ' [PKL Harian ✓]' : '';
            $this->command->info("  {$off['email']} → {$off['name']} ({$off['market']}){$pkl}");
        }
        $this->command->info('');
        $this->command->info('Password default semua akun: Pasar@2026');
        $this->command->info('⚠ WAJIB diganti saat login pertama di lingkungan produksi.');
    }
}
