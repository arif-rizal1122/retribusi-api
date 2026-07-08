<?php

namespace Tests\Feature\Payment\Snap;

use App\Models\PaymentGatewayLog;

class SnapPaymentLoggingSecurityTest extends SnapFeatureTestCase
{
    public function test_snap_audit_log_masks_sensitive_request_and_response_values(): void
    {
        $body = ['grantType' => 'client_credentials'];

        $this->postJson('/api/snap/v1.1/access-token/b2b', $body, $this->accessTokenHeaders($body))
            ->assertOk();

        $log = PaymentGatewayLog::latest()->firstOrFail();

        $this->assertSame('[MASKED]', $log->payload_in['x-signature'] ?? null);
        $this->assertSame('[MASKED]', $log->payload_in['x-client-key'] ?? null);
        $this->assertSame('[MASKED]', $log->payload_out['accessToken'] ?? null);
    }
}
