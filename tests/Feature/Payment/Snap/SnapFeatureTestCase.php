<?php

namespace Tests\Feature\Payment\Snap;

use App\Models\Bill;
use App\Models\Opd;
use App\Models\PaymentRequest;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Services\Payment\Snap\SnapCanonicalRequest;
use App\Services\Payment\Snap\SnapTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

abstract class SnapFeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected string $privateKey;
    protected string $publicKey;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->privateKey, $this->publicKey] = $this->keys();

        config([
            'snap.partners.BRI.partner_id' => 'BRI-PARTNER-TEST',
            'snap.partners.BRI.client_key' => 'BRI-CLIENT-TEST',
            'snap.partners.BRI.public_key' => $this->publicKey,
            'snap.security.require_bearer_token' => true,
            'snap.timestamp_tolerance_seconds' => 300,
            'snap.token_ttl_seconds' => 900,
        ]);
    }

    protected function accessTokenHeaders(array $body, array $overrides = []): array
    {
        $timestamp = $overrides['X-TIMESTAMP'] ?? now()->toIso8601String();

        $headers = [
            'X-CLIENT-KEY' => config('snap.partners.BRI.client_key'),
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $this->signature('/api/snap/v1.1/access-token/b2b', $body, $timestamp),
        ];

        return array_merge($headers, $overrides);
    }

    protected function transactionHeaders(string $path, array $body, array $overrides = []): array
    {
        $timestamp = $overrides['X-TIMESTAMP'] ?? now()->toIso8601String();

        $headers = [
            'X-PARTNER-ID' => config('snap.partners.BRI.partner_id'),
            'X-EXTERNAL-ID' => 'EXT-' . Str::uuid()->toString(),
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $this->signature($path, $body, $timestamp),
            'Authorization' => 'Bearer ' . $this->issueToken(),
        ];

        return array_merge($headers, $overrides);
    }

    protected function createOpenBill(array $overrides = []): Bill
    {
        $opd = Opd::factory()->create();
        $type = RetributionType::factory()->create(['opd_id' => $opd->id]);
        $classification = RetributionClassification::factory()->create([
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
        ]);
        $taxpayer = Taxpayer::factory()->create([
            'opd_id' => $opd->id,
            'name' => 'SNAP Test Taxpayer',
            'phone' => '081234567890',
        ]);
        $taxObject = TaxObject::factory()->create([
            'opd_id' => $opd->id,
            'taxpayer_id' => $taxpayer->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
        ]);

        return Bill::factory()->create(array_merge([
            'bill_number' => 'SKRD-SNAP-' . Str::upper(Str::random(8)),
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
            'amount' => 100000,
            'admin_fee' => 0,
            'penalty_amount' => 0,
            'fixed_fine_amount' => 0,
            'surcharge_amount' => 0,
            'waived_penalty_amount' => 0,
            'status' => 'pending',
            'period' => '2026-07',
            'due_date' => now()->addMonth(),
        ], $overrides));
    }

    protected function createPaymentRequest(Bill $bill, ?string $vaNumber = null): PaymentRequest
    {
        return PaymentRequest::create([
            'bill_id' => $bill->id,
            'taxpayer_id' => $bill->taxpayer_id,
            'tax_object_id' => $bill->tax_object_id,
            'payment_channel' => 'BRI',
            'method' => 'VA',
            'va_number' => $vaNumber ?? '777' . $bill->bill_number,
            'amount_snapshot' => $bill->amount,
            'admin_fee_snapshot' => $bill->admin_fee ?? 0,
            'penalty_snapshot' => $bill->total_amount - $bill->amount - ($bill->admin_fee ?? 0),
            'expires_at' => now()->addDay(),
            'status' => 'pending',
        ]);
    }

    protected function inquiryBody(Bill $bill, string $vaNumber): array
    {
        return [
            'partnerServiceId' => '777',
            'customerNo' => $bill->bill_number,
            'virtualAccountNo' => $vaNumber,
        ];
    }

    protected function paymentBody(Bill $bill, string $vaNumber, ?string $amount = null): array
    {
        return array_merge($this->inquiryBody($bill, $vaNumber), [
            'paidAmount' => [
                'value' => $amount ?? number_format($bill->total_amount, 2, '.', ''),
                'currency' => 'IDR',
            ],
            'referenceNo' => 'BRI-REF-' . Str::upper(Str::random(6)),
        ]);
    }

    private function signature(string $path, array $body, string $timestamp): string
    {
        $canonical = app(SnapCanonicalRequest::class)->canonicalBodyFromArray($body);
        $stringToSign = app(SnapCanonicalRequest::class)->stringToSign('POST', $path, $canonical, $timestamp);

        openssl_sign($stringToSign, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }

    private function issueToken(): string
    {
        return app(SnapTokenService::class)->issue((string) config('snap.partners.BRI.client_key'), 'BRI')['access_token'];
    }

    private function keys(): array
    {
        return [
            <<<'PEM'
-----BEGIN RSA PRIVATE KEY-----
MIIEogIBAAKCAQEA07DJKQnztn0mbTvf4ARVhvNHBRi/UHOXCFURiiOlatwVewgB
FFuDT4Xf3+aMHH3in3IRgAoX6/IbKgxatr7xFDiMx45/OBz8jC/rR37L/MbyzpLK
55gqAU0nVS8tc96JlVfQPWGQdIpW8X7lTQMdkeLScbQcoRWEdjb2nKYo34xs8Enx
Hn0+C7psvGzWEIIGS45Hoz584Ox1tstWQ5GUNLd9rDfpNZDR9X9Izs4mKTcXuZVG
/qT/ZE9wREv4qMgFJcWiXqUbhe3Tlu7XvLjrmYGnEbtBQD3LIOcOehK+n7VoP4BX
zPg5UrHpcOFf7h3qE0WO3i2xyaf1VpulqZWf9wIDAQABAoIBACfg8+RRRaIpLWYC
k4gmCN6lUcm6AcBsJhWhwO4fDPh2gW1t8pYdLz154NTTH632YQzcdkOCo0MFluxy
61vl2JCQnqSxSXIOs9zM4ivTzSXPbMpRiPvcBH2+RRydCJj9YTnLEyWdDZOGxB42
Y4gAgD+NVUuqPIJ0BUIn1IlqG+UQvVCP69QtOdBohZ2SEXad743HGwlJr+7UQU2O
4M9nmTZz+aV0Oue6CuEXA1Y68C2kQdR9OB61sJGIcpdmElgKhLoSsV6HEKKJ0v6C
ArTTgfyI9EnXZQht7TNoNG6gTs9YkBaXNHkYzhC0THhA8W/+mHj3IBHqe6YRF/0M
ZcGdQtECgYEA9uIUguJ7/ArkMplA7FvERMtfydBnQzckUuUiE9SY0MUjlQ4LLNYp
XV2fyzcPf63V5xO8dSE1Aq8L2thNnCYWadI/A03XfPGqH8zQa06yjkC5FFUSGcNT
WE7O8axmXKATTcD4dNEIAExgzUud/DSBSntrB4FGUzyX+jJ4xm+0rVUCgYEA24IC
6TGS52WarKL90TlWEzRRT2jTd7FolZKpv+CNV0dTizIapaXCuGqMKqqmVwL7BEIr
qoKz+RMiEpWTP1AKNtR4kwOGBDuMc7XG+kjSLWfRY7FDSJXchZ7ucASwn+FfWNe1
O9mdjA9EWqF7F2Yr7+50CFt7eJVXWfDUJLsp+BsCgYBhboe5v7g+l+3HKkQ9A1pJ
7Bk5hE28cR6cuGDigpxsh+CrCofOghaBClnt0SUEto4cS+WsNBa/oGWFUKgQX9eo
m5jSrP3GCXmiYyo9ryk4isKAC7LBCBz0VOXG6sra8zGrFeT39Sa7N2lcm+MVjYMY
6ewrYhFm+BriWtjfN2aOoQKBgB+nqRnN71x1V4/r1WSVuyb71Xn5KP3K1MU1KEum
a1uZyp95M0SOGf2UR/BjOae0o1Ri4n8taBzUOIarkVeBCGgNzfGNgYccu014emBf
nge0QAr7ZjOSgQG4ALSgyIPV8XUTbmxaHpEzJm1XejPOpSltnsgRwUWLa9RpmX5O
i2ffAoGAZ2w5Ln+q5vneVg6nmVxjUvqH19jcvA7Ow/p1q+99oOw2BA6J0JxAdzLj
mVMCOaToEgQK7QPabSawcGDrd6XQDE9kapoeOsdvXnMBNTRjPj5TVNX4o/ZtrJXw
uClDnKlG9MU9SSFE4Q29Gz8W6mz5pe9WVeC+ZFLC2fYYE83vcW0=
-----END RSA PRIVATE KEY-----
PEM,
            <<<'PEM'
-----BEGIN RSA PUBLIC KEY-----
MIIBCgKCAQEA07DJKQnztn0mbTvf4ARVhvNHBRi/UHOXCFURiiOlatwVewgBFFuD
T4Xf3+aMHH3in3IRgAoX6/IbKgxatr7xFDiMx45/OBz8jC/rR37L/MbyzpLK55gq
AU0nVS8tc96JlVfQPWGQdIpW8X7lTQMdkeLScbQcoRWEdjb2nKYo34xs8EnxHn0+
C7psvGzWEIIGS45Hoz584Ox1tstWQ5GUNLd9rDfpNZDR9X9Izs4mKTcXuZVG/qT/
ZE9wREv4qMgFJcWiXqUbhe3Tlu7XvLjrmYGnEbtBQD3LIOcOehK+n7VoP4BXzPg5
UrHpcOFf7h3qE0WO3i2xyaf1VpulqZWf9wIDAQAB
-----END RSA PUBLIC KEY-----
PEM,
        ];
    }
}
