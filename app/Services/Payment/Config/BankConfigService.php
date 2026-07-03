<?php

namespace App\Services\Payment\Config;

use App\Models\BankConfig;
use Illuminate\Support\Collection;

class BankConfigService
{
    /**
     * Get all active banks
     */
    public function getActiveBanks(): Collection
    {
        return BankConfig::active()->get();
    }

    /**
     * Get bank config by kode_bank
     */
    public function getByKode(string $kodeBank): ?BankConfig
    {
        return BankConfig::active()->where('kode_bank', $kodeBank)->first();
    }

    /**
     * Get bank config by driver type
     */
    public function getByDriver(string $driverType): Collection
    {
        return BankConfig::active()->byDriver($driverType)->get();
    }

    /**
     * Get production banks (non-sandbox)
     */
    public function getProductionBanks(): Collection
    {
        return BankConfig::active()->production()->get();
    }

    /**
     * Check if bank is ready for production
     */
    public function isProductionReady(string $kodeBank): bool
    {
        $bank = $this->getByKode($kodeBank);
        if (!$bank) return false;

        return !$bank->is_sandbox
            && $bank->api_endpoint
            && ($bank->api_key || $bank->client_id || $bank->hmac_secret);
    }

    /**
     * Register or update bank config
     */
    public function register(array $data): BankConfig
    {
        return BankConfig::updateOrCreate(
            ['kode_bank' => $data['kode_bank']],
            $data
        );
    }

    /**
     * Toggle bank between sandbox/production
     */
    public function toggleMode(string $kodeBank, bool $isSandbox): ?BankConfig
    {
        $bank = $this->getByKode($kodeBank);
        if ($bank) {
            $bank->update(['is_sandbox' => $isSandbox]);
        }
        return $bank;
    }

    /**
     * Get list of all available banks with their status
     */
    public function getStatusAll(): array
    {
        return $this->getActiveBanks()->map(fn($bank) => [
            'kode' => $bank->kode_bank,
            'nama' => $bank->nama_singkat,
            'driver' => $bank->tipe_driver,
            'mode' => $bank->is_sandbox ? '🟡 Sandbox' : '🟢 Production',
            'api_endpoint' => $bank->api_endpoint ?? '—',
            'auth' => $bank->auth_type,
            'va_prefix' => $bank->kode_va_prefix ?? '—',
        ])->toArray();
    }
}