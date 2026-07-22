<?php

namespace Tests\Unit\Payment\Snap;

use App\Services\Payment\Snap\Exceptions\SnapValidationException;
use App\Services\Payment\Snap\SnapTokenService;
use Tests\TestCase;

class SnapTokenServiceTest extends TestCase
{
    public function test_it_issues_and_validates_bearer_token(): void
    {
        config(['snap.security.require_bearer_token' => true]);

        $token = app(SnapTokenService::class)->issue('client-unit', 'BRI');

        app(SnapTokenService::class)->validateAuthorizationHeader('Bearer '.$token['access_token'], 'BRI');

        $this->assertSame(900, $token['expires_in']);
    }

    public function test_it_rejects_unknown_bearer_token(): void
    {
        config(['snap.security.require_bearer_token' => true]);

        $this->expectException(SnapValidationException::class);

        app(SnapTokenService::class)->validateAuthorizationHeader('Bearer missing-token', 'BRI');
    }

    public function test_it_rejects_token_issued_for_another_bank(): void
    {
        config(['snap.security.require_bearer_token' => true]);
        $token = app(SnapTokenService::class)->issue('client-unit', 'BRI');

        $this->expectException(SnapValidationException::class);

        app(SnapTokenService::class)->validateAuthorizationHeader('Bearer '.$token['access_token'], 'BTN');
    }
}
