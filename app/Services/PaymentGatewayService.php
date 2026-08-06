<?php

namespace App\Services;

use App\Models\PaymentRequest;
use App\Services\Contracts\PaymentGatewayAdapter;
use App\Services\Gateways\BriVaGateway;
use App\Services\Gateways\QrisGateway;
use InvalidArgumentException;

/**
 * PaymentGatewayService
 *
 * Mendistribusikan pembayaran ke adapter yang sesuai dengan metode.
 * Integrasi gateway produksi cukup dilakukan dengan mengganti implementasi
 * adapter (BriVaGateway, QrisGateway) — business logic tidak berubah.
 */
class PaymentGatewayService
{
    private const ADAPTERS = [
        PaymentRequest::METHOD_BRI_VA => BriVaGateway::class,
        PaymentRequest::METHOD_QRIS => QrisGateway::class,
    ];

    public function adapter(string $method): PaymentGatewayAdapter
    {
        $adapterClass = self::ADAPTERS[$method] ?? null;

        if (!$adapterClass) {
            throw new InvalidArgumentException("Gateway tidak dikenali untuk metode: {$method}");
        }

        return app($adapterClass);
    }

    public function supports(string $method): bool
    {
        return array_key_exists($method, self::ADAPTERS);
    }
}
