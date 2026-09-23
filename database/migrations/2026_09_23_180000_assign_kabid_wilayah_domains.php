<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Penyesuaian penugasan Kabid Pajak Daerah Wilayah I & II M-PAD.
 *
 * Konteks:
 * - Akun Kabid Wilayah sebelumnya ber-role `kepala_bapenda` sehingga memiliki
 *   kewenangan setara Kepala Bapenda (pelanggaran Segregation of Duties).
 * - metadata akun Wilayah I (id 298) KOSONG sehingga TaxDomainHelper::getDomain()
 *   fail-open ke DOMAIN_ALL — Kabid Wilayah I bisa mengakses data Wilayah II.
 * - metadata akun Wilayah II (id 302) memakai format lama `wilayah_2_pbjt`.
 *
 * Perbaikan TANPA mengubah skema DB — hanya update baris `users` memakai kolom
 * yang sudah ada (role, metadata). Konsisten dengan pola RoleResolver::ROLE_MATRIX
 * dan TaxDomainHelper::DOMAIN_CONFIG.
 */
return new class extends Migration
{
    /**
     * Akun Kabid Pajak Daerah. Key = email (stabil), bukan id (auto-increment).
     */
    private const TARGETS = [
        'kabid_pajak@mpad.online' => [
            'role' => 'kabid_wilayah_1',
            'metadata' => [
                'role_code' => 'kabid_wilayah_1',
                'opd_role' => 'Kabid Pajak Daerah Wilayah I',
                'role_group' => 'admin',
                'domain' => 'wilayah_1',
                'portfolio' => 'PBB-P2, BPHTB, Reklame, MBLB, Sarang Burung Walet, Opsen PKB/BBNKB',
            ],
        ],
        'kabid_wilayah2@mpad.online' => [
            'role' => 'kabid_wilayah_2',
            'metadata' => [
                'role_code' => 'kabid_wilayah_2',
                'opd_role' => 'Kabid Pajak Daerah Wilayah II',
                'role_group' => 'admin',
                'domain' => 'wilayah_2',
                'portfolio' => 'PBJT (Makanan/Minuman, Perhotelan, Hiburan, Parkir, Kesenian, Listrik), Pajak Air Tanah',
            ],
        ],
    ];

    public function up(): void
    {
        foreach (self::TARGETS as $email => $payload) {
            DB::table('users')
                ->where('email', $email)
                ->update([
                    'role' => $payload['role'],
                    'metadata' => json_encode($payload['metadata'], JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Kembalikan ke kondisi semula (role kepala_bapenda; metadata Wilayah I NULL,
        // Wilayah II format lama). Hanya menyentuh baris yang memang kita ubah.
        DB::table('users')
            ->where('email', 'kabid_pajak@mpad.online')
            ->update([
                'role' => 'kepala_bapenda',
                'metadata' => null,
                'updated_at' => now(),
            ]);

        DB::table('users')
            ->where('email', 'kabid_wilayah2@mpad.online')
            ->update([
                'role' => 'kepala_bapenda',
                'metadata' => json_encode([
                    'role_code' => 'bapenda',
                    'opd_role' => 'Badan Pendapatan Daerah',
                    'role_group' => 'admin',
                    'domain' => 'wilayah_2_pbjt',
                    'portfolio' => 'PBJT & Pajak Air Tanah',
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }
};
