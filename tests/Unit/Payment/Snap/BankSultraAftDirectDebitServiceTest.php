<?php

namespace Tests\Unit\Payment\Snap;

use App\Services\Payment\Snap\BankSultraAftDirectDebitService;
use InvalidArgumentException;
use Tests\TestCase;

class BankSultraAftDirectDebitServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'snap.bank_sultra_aft.selected_product' => 'direct_debit_h2h',
            'snap.bank_sultra_aft.version' => 'v1.0',
            'snap.bank_sultra_aft.source_account_no' => '116010001234567',
            'snap.bank_sultra_aft.destination_account_no' => '116010009876543',
            'snap.partners.BANK_SULTRA.partner_id' => 'SULTRA-PARTNER',
            'snap.partners.BANK_SULTRA.client_key' => 'SULTRA-CLIENT',
            'snap.partners.BANK_SULTRA.channel_id' => '95221',
            'snap.partners.BANK_SULTRA.signature_algorithm' => 'hmac_sha512',
            'snap.partners.BANK_SULTRA.signature_secret' => 'sultra-signature-secret',
        ]);
    }

    public function test_it_builds_direct_debit_h2h_payment_request_with_snap_headers_and_signature(): void
    {
        $timestamp = '2026-08-03T10:15:30+08:00';
        $body = [
            'partnerReferenceNo' => 'AFT-REF-001',
            'merchantId' => 'MRC-001',
            'sourceAccountNo' => '116010001234567',
            'destinationAccountNo' => '116010009876543',
            'amount' => [
                'value' => '10000.00',
                'currency' => 'IDR',
            ],
            'feeAmount' => [
                'value' => '0.00',
                'currency' => 'IDR',
            ],
            'remark' => 'Auto-Debit PBJT',
        ];

        $request = app(BankSultraAftDirectDebitService::class)->buildPaymentRequest(
            [
                'partner_reference_no' => 'AFT-REF-001',
                'merchant_id' => 'MRC-001',
                'amount' => 10000,
                'remark' => 'Auto-Debit PBJT',
            ],
            'token-aft',
            'AFT-EXT-0001',
            $timestamp
        );

        $expectedStringToSign = 'POST:/v1.0/debit/payment-host-to-host:'
            .strtolower(hash('sha256', json_encode($body, JSON_UNESCAPED_SLASHES)))
            .':'.$timestamp;
        $expectedSignature = base64_encode(hash_hmac('sha512', $expectedStringToSign, 'sultra-signature-secret', true));

        $this->assertSame('POST', $request['method']);
        $this->assertSame('/v1.0/debit/payment-host-to-host', $request['path']);
        $this->assertSame($body, $request['body']);
        $this->assertSame('Bearer token-aft', $request['headers']['Authorization']);
        $this->assertSame('SULTRA-PARTNER', $request['headers']['X-PARTNER-ID']);
        $this->assertSame('AFT-EXT-0001', $request['headers']['X-EXTERNAL-ID']);
        $this->assertSame('95221', $request['headers']['CHANNEL-ID']);
        $this->assertSame($timestamp, $request['headers']['X-TIMESTAMP']);
        $this->assertSame($expectedSignature, $request['headers']['X-SIGNATURE']);
    }

    public function test_it_builds_bifast_payment_request_for_selected_product(): void
    {
        config(['snap.bank_sultra_aft.selected_product' => 'direct_debit_bifast']);

        $request = app(BankSultraAftDirectDebitService::class)->buildPaymentRequest(
            [
                'partner_reference_no' => 'AFT-BIFAST-001',
                'e_mandate_reference_id' => 'EMANDATE-001',
                'amount' => '25000.5',
            ],
            'token-aft',
            'AFT-EXT-0002',
            '2026-08-03T10:20:30+08:00'
        );

        $this->assertSame('/v1.0/debit/fast-payment', $request['path']);
        $this->assertSame('EMANDATE-001', $request['body']['eMandateReffId']);
        $this->assertSame('116010001234567', $request['body']['sourceAccountNo']);
        $this->assertSame('116010009876543', $request['body']['beneficiaryAccountNo']);
        $this->assertSame(['value' => '25000.50', 'currency' => 'IDR'], $request['body']['amount']);
    }

    public function test_it_builds_token_headers_without_exposing_credentials(): void
    {
        $timestamp = '2026-08-03T10:25:30+08:00';
        $headers = app(BankSultraAftDirectDebitService::class)->buildAccessTokenHeaders($timestamp);
        $expectedSignature = base64_encode(hash_hmac('sha512', 'SULTRA-CLIENT|'.$timestamp, 'sultra-signature-secret', true));

        $this->assertSame('SULTRA-CLIENT', $headers['X-CLIENT-KEY']);
        $this->assertSame($timestamp, $headers['X-TIMESTAMP']);
        $this->assertSame($expectedSignature, $headers['X-SIGNATURE']);
        $this->assertArrayNotHasKey('client_secret', $headers);
        $this->assertArrayNotHasKey('signature_secret', $headers);
    }

    public function test_it_parses_seven_digit_snap_response_code(): void
    {
        $parsed = app(BankSultraAftDirectDebitService::class)->parseResponse([
            'responseCode' => '2025400',
            'responseMessage' => 'Request In Progress',
        ]);

        $this->assertSame(202, $parsed['http_status']);
        $this->assertSame('54', $parsed['service_code']);
        $this->assertSame('00', $parsed['case_code']);
        $this->assertSame('pending', $parsed['status']);
        $this->assertSame('Request In Progress', $parsed['message']);
    }

    public function test_it_rejects_malformed_response_code(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(BankSultraAftDirectDebitService::class)->parseResponse([
            'responseCode' => '20054000',
            'responseMessage' => 'Invalid rendered sample',
        ]);
    }

    public function test_it_masks_audit_payload_headers_and_bank_accounts(): void
    {
        $masked = app(BankSultraAftDirectDebitService::class)->maskAuditPayload([
            'headers' => [
                'Authorization' => 'Bearer token-aft',
                'X-SIGNATURE' => 'signature-value',
                'X-EXTERNAL-ID' => 'AFT-EXT-0001',
            ],
            'body' => [
                'sourceAccountNo' => '116010001234567',
                'destinationAccountNo' => '116010009876543',
                'amount' => [
                    'value' => '10000.00',
                    'currency' => 'IDR',
                ],
            ],
        ]);

        $this->assertSame('[MASKED]', $masked['headers']['Authorization']);
        $this->assertSame('[MASKED]', $masked['headers']['X-SIGNATURE']);
        $this->assertSame('AFT-EXT-0001', $masked['headers']['X-EXTERNAL-ID']);
        $this->assertSame('***********4567', $masked['body']['sourceAccountNo']);
        $this->assertSame('***********6543', $masked['body']['destinationAccountNo']);
        $this->assertSame(['value' => '10000.00', 'currency' => 'IDR'], $masked['body']['amount']);
    }
}
