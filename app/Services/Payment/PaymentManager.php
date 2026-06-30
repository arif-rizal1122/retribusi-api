<?php

namespace App\Services\Payment;

use Illuminate\Support\Manager;
use App\Services\Payment\Drivers\BankSultraDriver;
use App\Services\BillingService;

class PaymentManager extends Manager
{
    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('payment.default', 'sultra');
    }

    /**
     * Get credentials for a specific channel from Database.
     */
    protected function getChannelConfig(string $code): array
    {
        $channel = \Illuminate\Support\Facades\Cache::remember("payment_channel_{$code}", 86400, function () use ($code) {
            return \App\Models\PaymentChannel::where('code', $code)->where('is_active', true)->first();
        });

        if (!$channel) {
            throw new \Exception("Payment channel [{$code}] is not active or not configured.");
        }

        return $channel->credentials ?? [];
    }

    /**
     * Create the Bank Sultra driver.
     */
    public function createSultraDriver(): BankSultraDriver
    {
        $config = $this->getChannelConfig('sultra');
        return new BankSultraDriver(app(BillingService::class), $config);
    }

    /**
     * Create the QRIS driver.
     */
    public function createQrisDriver(): \App\Services\Payment\Drivers\QRISDriver
    {
        $config = $this->getChannelConfig('qris');
        return new \App\Services\Payment\Drivers\QRISDriver(app(BillingService::class), $config);
    }

    /**
     * Create the Bank Mandiri QRIS driver.
     */
    public function createMandiriDriver(): \App\Services\Payment\Drivers\BankMandiriDriver
    {
        $config = $this->getChannelConfig('mandiri');
        return new \App\Services\Payment\Drivers\BankMandiriDriver(app(BillingService::class), $config);
    }
}
