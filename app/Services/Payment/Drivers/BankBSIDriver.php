<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Services\BillingService;
use App\Services\Payment\Traits\HasMockResponse;

class BankBSIDriver implements PaymentGatewayInterface
{
    use HasMockResponse;

    protected BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    public function inquiry(string $billNumber): array
    {
        if ($this->isSandbox()) return $this->mockInquiry($billNumber);
        return ['status' => 'error', 'code' => 501, 'message' => 'BSI driver: production endpoint belum diimplementasi.'];
    }

    public function notify(array $payload): array
    {
        if ($this->isSandbox()) return $this->mockNotify($payload);
        return ['status' => 'error', 'code' => 501, 'message' => 'BSI driver: production endpoint belum diimplementasi.'];
    }

    public function reversal(array $payload): array
    {
        if ($this->isSandbox()) return $this->mockReversal($payload);
        return ['status' => 'error', 'code' => 501, 'message' => 'BSI driver: reversal endpoint belum diimplementasi.'];
    }

    public function getAccountDetail(string $billNumber): array
    {
        if ($this->isSandbox()) return $this->mockAccountDetail($billNumber);
        return ['status' => 'error', 'code' => 501, 'message' => 'BSI driver: VA generation belum diimplementasi.'];
    }

    public function reconcile(array $transactions): array
    {
        if ($this->isSandbox()) return $this->mockReconcile($transactions);
        return ['status' => 'error', 'code' => 501, 'message' => 'BSI driver: reconciliation belum diimplementasi.'];
    }
}