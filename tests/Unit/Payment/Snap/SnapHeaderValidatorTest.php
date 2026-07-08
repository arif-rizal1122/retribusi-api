<?php

namespace Tests\Unit\Payment\Snap;

use App\Services\Payment\Snap\Exceptions\SnapValidationException;
use App\Services\Payment\Snap\SnapHeaderValidator;
use Illuminate\Http\Request;
use Tests\TestCase;

class SnapHeaderValidatorTest extends TestCase
{
    public function test_it_validates_transaction_partner_header(): void
    {
        config(['snap.partner_id' => 'PARTNER-UNIT']);

        $request = Request::create('/api/snap/v1.0/transfer-va/inquiry', 'POST', [], [], [], [
            'HTTP_X_PARTNER_ID' => 'PARTNER-UNIT',
            'HTTP_X_EXTERNAL_ID' => 'EXT-UNIT',
            'HTTP_X_TIMESTAMP' => now()->toIso8601String(),
            'HTTP_X_SIGNATURE' => 'signature',
        ]);

        app(SnapHeaderValidator::class)->validateTransactionHeaders($request);

        $this->assertTrue(true);
    }

    public function test_it_rejects_missing_mandatory_header(): void
    {
        $this->expectException(SnapValidationException::class);

        app(SnapHeaderValidator::class)->validateTransactionHeaders(
            Request::create('/api/snap/v1.0/transfer-va/inquiry', 'POST')
        );
    }
}
