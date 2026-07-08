<?php

namespace Tests\Feature\Payment\Snap;

class SnapAccessTokenTest extends SnapFeatureTestCase
{
    public function test_bank_can_request_snap_access_token_with_valid_signature(): void
    {
        $body = ['grantType' => 'client_credentials'];

        $response = $this->postJson(
            '/api/snap/v1.1/access-token/b2b',
            $body,
            $this->accessTokenHeaders($body)
        );

        $response->assertOk()
            ->assertJsonPath('responseCode', '2007300')
            ->assertJsonPath('tokenType', 'Bearer')
            ->assertJsonStructure(['accessToken', 'expiresIn']);
    }
}
