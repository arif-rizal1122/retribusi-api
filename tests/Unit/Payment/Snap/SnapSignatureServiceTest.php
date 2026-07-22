<?php

namespace Tests\Unit\Payment\Snap;

use App\Services\Payment\Snap\Exceptions\SnapValidationException;
use App\Services\Payment\Snap\SnapCanonicalRequest;
use App\Services\Payment\Snap\SnapSignatureService;
use Illuminate\Http\Request;
use Tests\TestCase;

class SnapSignatureServiceTest extends TestCase
{
    public function test_it_verifies_valid_asymmetric_signature(): void
    {
        [$privateKey, $publicKey] = $this->keys();
        config(['snap.partners.BRI.public_key' => $publicKey]);

        $timestamp = now()->toIso8601String();
        $body = ['customerNo' => 'SKRD-UNIT-001'];
        $canonicalBody = app(SnapCanonicalRequest::class)->canonicalBodyFromArray($body);
        $stringToSign = app(SnapCanonicalRequest::class)->stringToSign(
            'POST',
            '/api/snap/v1.0/transfer-va/inquiry',
            $canonicalBody,
            $timestamp
        );

        openssl_sign($stringToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $request = Request::create(
            '/api/snap/v1.0/transfer-va/inquiry',
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_TIMESTAMP' => $timestamp,
                'HTTP_X_SIGNATURE' => base64_encode($signature),
            ],
            $canonicalBody
        );
        $request->attributes->set('snap_bank_code', 'BRI');

        app(SnapSignatureService::class)->verify($request);

        $this->assertTrue(true);
    }

    public function test_it_rejects_invalid_signature(): void
    {
        [, $publicKey] = $this->keys();
        config(['snap.partners.BRI.public_key' => $publicKey]);

        $this->expectException(SnapValidationException::class);

        $request = Request::create(
            '/api/snap/v1.0/transfer-va/inquiry',
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_TIMESTAMP' => now()->toIso8601String(),
                'HTTP_X_SIGNATURE' => 'invalid',
            ],
            '{"customerNo":"SKRD-UNIT-001"}'
        );
        $request->attributes->set('snap_bank_code', 'BRI');

        app(SnapSignatureService::class)->verify($request);
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
