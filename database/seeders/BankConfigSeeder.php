<?php

namespace Database\Seeders;

use App\Models\BankConfig;
use Illuminate\Database\Seeder;

class BankConfigSeeder extends Seeder
{
    public function run(): void
    {
        $banks = [
            [
                'kode_bank' => 'SULTRA',
                'nama_bank' => 'Bank Pembangunan Daerah Sulawesi Tenggara',
                'nama_singkat' => 'Bank Sultra',
                'tipe_driver' => 'sultra',
                'auth_type' => 'hmac',
                'kode_va_prefix' => '99',
                'fee_persen' => 0,
                'is_active' => true,
                'is_sandbox' => false,
                'metadata' => ['existing' => true, 'note' => 'Driver existing, sudah production'],
            ],
            [
                'kode_bank' => 'MANDIRI',
                'nama_bank' => 'Bank Mandiri (Persero) Tbk',
                'nama_singkat' => 'Mandiri',
                'tipe_driver' => 'mandiri',
                'auth_type' => 'oauth2',
                'kode_va_prefix' => '88000',
                'fee_persen' => 0.50,
                'is_active' => true,
                'is_sandbox' => true,
                'metadata' => ['note' => 'Stub driver — siap integrasi. Sandbox aktif.'],
            ],
            [
                'kode_bank' => 'BRI',
                'nama_bank' => 'Bank Rakyat Indonesia (Persero) Tbk',
                'nama_singkat' => 'BRI',
                'tipe_driver' => 'bri',
                'auth_type' => 'oauth2',
                'kode_va_prefix' => '88000',
                'fee_persen' => 0.50,
                'is_active' => true,
                'is_sandbox' => true,
                'metadata' => ['note' => 'Stub driver — siap integrasi. Sandbox aktif.'],
            ],
            [
                'kode_bank' => 'BNI',
                'nama_bank' => 'Bank Negara Indonesia (Persero) Tbk',
                'nama_singkat' => 'BNI',
                'tipe_driver' => 'bni',
                'auth_type' => 'oauth2',
                'kode_va_prefix' => '88000',
                'fee_persen' => 0.50,
                'is_active' => true,
                'is_sandbox' => true,
                'metadata' => ['note' => 'Stub driver — siap integrasi. Sandbox aktif.'],
            ],
            [
                'kode_bank' => 'BSI',
                'nama_bank' => 'Bank Syariah Indonesia Tbk',
                'nama_singkat' => 'BSI',
                'tipe_driver' => 'bsi',
                'auth_type' => 'oauth2',
                'kode_va_prefix' => '88000',
                'fee_persen' => 0.30,
                'is_active' => true,
                'is_sandbox' => true,
                'metadata' => ['note' => 'Stub driver — siap integrasi. Sandbox aktif.'],
            ],
            [
                'kode_bank' => 'QRIS',
                'nama_bank' => 'QRIS National (ASPI)',
                'nama_singkat' => 'QRIS',
                'tipe_driver' => 'qris',
                'auth_type' => 'api_key',
                'fee_persen' => 0.30,
                'is_active' => true,
                'is_sandbox' => true,
                'metadata' => ['note' => 'QRIS aggregator — siap upgrade ke real API.'],
            ],
        ];

        foreach ($banks as $bank) {
            BankConfig::updateOrCreate(
                ['kode_bank' => $bank['kode_bank']],
                $bank
            );
        }

        $this->command->info('✅ BankConfigSeeder: ' . count($banks) . ' bank berhasil ditambahkan/diupdate.');
    }
}