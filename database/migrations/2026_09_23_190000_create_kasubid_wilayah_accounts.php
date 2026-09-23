<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Akun Kasubid Bidang Pajak Daerah M-PAD — pembagian tupoksi per wilayah.
 *
 * Melengkapi penugasan tingkat Kabid (lihat migration 2026_09_23_180000).
 * Mengikuti prinsip Segregation of Duties:
 * - Siklus Hulu  : Pendaftaran, Pendataan & Penetapan
 * - Siklus Hilir : Penagihan, Verifikasi & Pelaporan
 *
 * TANPA mengubah skema DB — hanya menambah baris `users` memakai kolom yang
 * sudah ada (role, metadata). Pembatasan wilayah ditegakkan lewat
 * metadata.tax_domain yang dibaca TaxDomainHelper::getDomain().
 */
return new class extends Migration
{
    /** OPD Bapenda (kolom opds.code). */
    private const OPD_CODE = 'BAPENDA';

    /**
     * Key = email (stabil). Value = atribut akun.
     */
    private const ACCOUNTS = [
        'kasubid.pendataan.w1@mpad.online' => [
            'name' => 'Kasubid Pendataan & Penetapan Wilayah I',
            'role' => 'kasubid_pendataan_wil1',
            'portfolio' => 'PBB-P2, BPHTB, Reklame, MBLB, Sarang Burung Walet, Opsen PKB/BBNKB',
            'domain' => 'wilayah_1',
            'siklus' => 'Hulu — verifikasi berkas, validasi NOPD/spasial, kalkulasi tarif, draf ketetapan',
        ],
        'kasubid.penagihan.w1@mpad.online' => [
            'name' => 'Kasubid Penagihan & Pelaporan Wilayah I',
            'role' => 'kasubid_penagihan_wil1',
            'portfolio' => 'PBB-P2, BPHTB, Reklame, MBLB, Sarang Burung Walet, Opsen PKB/BBNKB',
            'domain' => 'wilayah_1',
            'siklus' => 'Hilir — monitoring pembayaran, piutang/denda, surat teguran, rekonsiliasi kasda',
        ],
        'kasubid.pendataan.w2@mpad.online' => [
            'name' => 'Kasubid Pendataan & Penetapan Wilayah II',
            'role' => 'kasubid_pendataan_wil2',
            'portfolio' => 'PBJT (Makanan/Minuman, Perhotelan, Hiburan, Parkir, Kesenian, Listrik), Pajak Air Tanah',
            'domain' => 'wilayah_2',
            'siklus' => 'Hulu — verifikasi berkas, validasi NOPD/spasial, kalkulasi tarif, draf ketetapan',
        ],
        'kasubid.penagihan.w2@mpad.online' => [
            'name' => 'Kasubid Penagihan & Pelaporan Wilayah II',
            'role' => 'kasubid_penagihan_wil2',
            'portfolio' => 'PBJT (Makanan/Minuman, Perhotelan, Hiburan, Parkir, Kesenian, Listrik), Pajak Air Tanah',
            'domain' => 'wilayah_2',
            'siklus' => 'Hilir — monitoring pembayaran, piutang/denda, STPD, rekonsiliasi kasda',
        ],
        'kasubid.pengembangan@mpad.online' => [
            'name' => 'Kasubid Koordinasi, Kerjasama & Pengembangan',
            'role' => 'kasubid_pengembangan',
            'portfolio' => 'Integrasi bank H2H, simulator tarif, pemetaan objek baru, evaluasi regulasi',
            'domain' => 'all',
            'siklus' => 'Lintas wilayah — konektivitas API, potensi objek baru, proyeksi penerimaan',
        ],
    ];

    public function up(): void
    {
        $opdId = DB::table('opds')->where('code', self::OPD_CODE)->value('id');
        if (! $opdId) {
            throw new RuntimeException('OPD '.self::OPD_CODE.' tidak ditemukan — migration dibatalkan.');
        }

        foreach (self::ACCOUNTS as $email => $a) {
            DB::table('users')->updateOrInsert(
                ['email' => $email],
                [
                    'name' => $a['name'],
                    'password' => Hash::make('password123'),
                    'role' => $a['role'],
                    'opd_id' => $opdId,
                    'status' => 'active',
                    'metadata' => json_encode([
                        'role_code' => $a['role'],
                        'opd_role' => $a['name'],
                        'role_group' => 'admin',
                        'domain' => $a['domain'],
                        'portfolio' => $a['portfolio'],
                        'siklus' => $a['siklus'],
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('users')->whereIn('email', array_keys(self::ACCOUNTS))->delete();
    }
};
