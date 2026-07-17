<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use App\Services\Payment\Utils\BtnSignatureService;
use App\Services\Payment\Drivers\BankBTNDriver;

class BankBTNIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock configurations
        Config::set('services.btn.client_id', 'dummy_client_id');
        Config::set('services.btn.client_secret', 'dummy_secret');
        Config::set('services.btn.partner_id', '99017');
        Config::set('services.btn.base_url', 'https://devapi.btn.co.id');
        
        // Mock a dummy private key path for testing (we won't actually call openssl_sign with a fake path if we mock the service, but let's mock the service directly)
    }

    public function test_get_account_detail_calls_btn_create_va()
    {
        // Mock BtnSignatureService to return dummy signature and token
        $signatureServiceMock = $this->createMock(BtnSignatureService::class);
        $signatureServiceMock->method('getAccessToken')->willReturn('dummy_token');
        $signatureServiceMock->method('generateHmacSignature')->willReturn('dummy_hmac_signature');

        // Bind the mock to the container
        $this->app->instance(BtnSignatureService::class, $signatureServiceMock);

        // Fake HTTP response for BTN Create VA
        Http::fake([
            'https://devapi.btn.co.id/snap/v1/transfer-va/create-va' => Http::response([
                'responseCode' => '2002700',
                'responseMessage' => 'Successful'
            ], 200)
        ]);

        $driver = new BankBTNDriver($signatureServiceMock);
        
        $billNumber = '1234567890';
        $result = $driver->getAccountDetail($billNumber);

        $this->assertEquals('BTN', $result['bank']);
        $this->assertEquals('   990171234567890', $result['virtual_account']);
        
        Http::assertSent(function ($request) {
            return $request->url() == 'https://devapi.btn.co.id/snap/v1/transfer-va/create-va' &&
                   $request->header('Authorization')[0] == 'Bearer dummy_token' &&
                   $request->header('X-SIGNATURE')[0] == 'dummy_hmac_signature';
        });
    }

    public function test_btn_payment_webhook_updates_status()
    {
        // We will mock the verifyHmacSignature to always return true for this test
        $signatureServiceMock = $this->createMock(BtnSignatureService::class);
        $signatureServiceMock->method('verifyHmacSignature')->willReturn(true);
        $this->app->instance(BtnSignatureService::class, $signatureServiceMock);

        // Fake PaymentManager to prevent actual DB updates during basic feature test
        $paymentManagerMock = $this->createMock(\App\Services\Payment\PaymentManager::class);
        
        $driverMock = $this->createMock(BankBTNDriver::class);
        $driverMock->expects($this->once())->method('notify')->with($this->isType('array'));
        
        $paymentManagerMock->method('driver')->with('btn')->willReturn($driverMock);
        $this->app->instance(\App\Services\Payment\PaymentManager::class, $paymentManagerMock);

        $payload = [
            'partnerServiceId' => '99017',
            'customerNo' => '1234567890',
            'virtualAccountNo' => '990171234567890',
            'paymentRequestId' => 'req123',
            'paidAmount' => ['value' => '50000.00', 'currency' => 'IDR'],
        ];

        $response = $this->postJson('/api/snap/v1/transfer-va/payment', $payload, [
            'X-TIMESTAMP' => now()->timezone('Asia/Jakarta')->format('Y-m-d\TH:i:sP'),
            'X-SIGNATURE' => 'valid_signature',
            'Authorization' => 'Bearer token'
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('responseCode', '2002500');
    }
}
