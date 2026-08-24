<?php

namespace Tests\Unit\Payment\Snap;

use App\Services\Payment\Snap\SensitivePaymentLogMasker;
use Tests\TestCase;

class SensitivePaymentLogMaskerTest extends TestCase
{
    public function test_it_masks_sensitive_payment_payload_fields_recursively(): void
    {
        $masked = (new SensitivePaymentLogMasker())->mask([
            'X-SIGNATURE' => 'raw-signature',
            'clientSecret' => 'secret-value',
            'nested' => [
                'accessToken' => 'token-value',
                'amount' => 100000,
            ],
        ]);

        $this->assertSame('[MASKED]', $masked['X-SIGNATURE']);
        $this->assertSame('[MASKED]', $masked['clientSecret']);
        $this->assertSame('[MASKED]', $masked['nested']['accessToken']);
        $this->assertSame(100000, $masked['nested']['amount']);
    }
}
