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
     * Create the Bank Sultra driver.
     */
    public function createSultraDriver(): BankSultraDriver
    {
        return new BankSultraDriver(app(BillingService::class));
    }

    /**
     * Create the QRIS driver.
     */
    public function createQrisDriver(): \App\Services\Payment\Drivers\QRISDriver
    {
        return new \App\Services\Payment\Drivers\QRISDriver(app(BillingService::class));
    }

    /**
     * Custom driver support if needed in the future
     */
}
