<?php

namespace App\Services\Payment\Traits;

use App\Models\BankConfig;
use Carbon\Carbon;
use Illuminate\Support\Str;

trait HasMockResponse
{
    protected BankConfig $bankConfig;

    /**
     * Set bank config for this driver
     */
    public function setBankConfig(BankConfig $config): void
    {
        $this->bankConfig = $config;
    }

    /**
     * Check if this driver is in sandbox mode
     */
    public function isSandbox(): bool
    {
        return $this->bankConfig->is_sandbox ?? true;
    }

    /**
     * Generate mock inquiry response for testing
     */
    protected function mockInquiry(string $billNumber): array
    {
        return [
            'status' => 'success',
            'code' => 200,
            'data' => [
                'bill_number' => $billNumber,
                'taxpayer_name' => 'WAJIB PAJAK DUMMY (SANDBOX)',
                'tax_object' => 'OBJEK PAJAK DUMMY',
                'period' => Carbon::now()->format('Y-m'),
                'amount_pokok' => 100000,
                'penalty_amount' => 0,
                'total_amount' => 100000,
                'due_date' => Carbon::now()->addDays(30)->toDateTimeString(),
                'sandbox_mode' => true,
                'bank' => $this->bankConfig->nama_singkat ?? 'Sandbox',
            ],
            'message' => 'Sandbox mode — data dummy untuk testing arsitektur H2H',
        ];
    }

    /**
     * Generate mock payment notification response for testing
     */
    protected function mockNotify(array $payload): array
    {
        $ntpd = 'NTPD-SBX-' . date('Ymd') . '-' . strtoupper(Str::random(10));

        return [
            'status' => 'success',
            'code' => 200,
            'data' => [
                'ntpd' => $ntpd,
                'bill_number' => $payload['bill_number'] ?? 'DUMMY',
                'status' => 'LUNAS',
                'sandbox_mode' => true,
                'bank' => $this->bankConfig->nama_singkat ?? 'Sandbox',
            ],
            'message' => 'Sandbox mode — payment berhasil diproses (dummy)',
        ];
    }

    /**
     * Generate mock reversal response for testing
     */
    protected function mockReversal(array $payload): array
    {
        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Sandbox mode — reversal berhasil diproses (dummy)',
            'data' => [
                'sandbox_mode' => true,
                'bank' => $this->bankConfig->nama_singkat ?? 'Sandbox',
            ],
        ];
    }

    /**
     * Generate mock VA number for testing
     */
    protected function mockAccountDetail(string $billNumber): array
    {
        $prefix = $this->bankConfig->kode_va_prefix ?? '99';

        return [
            'status' => 'success',
            'code' => 200,
            'data' => [
                'bank_name' => $this->bankConfig->nama_bank ?? 'Sandbox Bank',
                'va_number' => $prefix . $billNumber,
                'sandbox_mode' => true,
            ],
            'message' => 'Sandbox mode — VA number dummy',
        ];
    }

    /**
     * Generate mock reconcile response for testing
     */
    protected function mockReconcile(array $transactions): array
    {
        return [
            'status' => 'success',
            'code' => 200,
            'data' => [
                'matched' => count($transactions),
                'mismatch' => 0,
                'details' => [],
                'sandbox_mode' => true,
                'bank' => $this->bankConfig->nama_singkat ?? 'Sandbox',
            ],
            'message' => 'Sandbox mode — semua transaksi cocok (dummy)',
        ];
    }
}