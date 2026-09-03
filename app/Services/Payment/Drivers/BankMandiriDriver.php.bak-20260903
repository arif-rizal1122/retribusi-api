<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Bill;
use App\Models\Payment;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BankMandiriDriver implements PaymentGatewayInterface
{
    protected $billingService;
    protected $config;

    public function __construct(BillingService $billingService, array $config = [])
    {
        $this->billingService = $billingService;
        $this->config = $config;
    }

    /**
     * Helper to get HTTP client with forced TLS 1.3
     */
    protected function getHttpClient()
    {
        return Http::withOptions([
            'verify' => true, // Ensure SSL is verified
            'curl' => [
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_3 // Force TLS 1.3
            ]
        ]);
    }

    /**
     * Get Token (Instruksi 1 Bank Mandiri)
     */
    protected function getToken(): ?string
    {
        $apiUrl = $this->config['api_url'] ?? 'https://api-dev.bankmandiri.co.id';
        $clientId = $this->config['client_id'] ?? '';
        $clientSecret = $this->config['client_secret'] ?? '';

        try {
            $response = $this->getHttpClient()
                ->asForm()
                ->post($apiUrl . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }
            Log::error('Mandiri Get Token Failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Mandiri Token Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Inquiry (Get Dynamic QRIS for a bill) -> Instruksi 2 Bank Mandiri
     */
    public function inquiry(string $billNumber): array
    {
        $bill = Bill::where('bill_number', $billNumber)->first();

        if (!$bill) {
            return ['status' => 'error', 'message' => 'Tagihan tidak ditemukan', 'code' => 404];
        }

        if ($bill->status === 'lunas') {
            return ['status' => 'error', 'message' => 'Tagihan sudah lunas', 'code' => 422];
        }

        // Recalculate penalty JIT
        $taxObject = $bill->taxObject;
        $penalty = $bill->penalty_amount;
        if ($taxObject) {
            $pendingPeriods = $this->billingService->getPendingPeriods($taxObject);
            $currentPeriod = $pendingPeriods->firstWhere('period', $bill->period);
            if ($currentPeriod) {
                $penalty = $currentPeriod['penalty_amount'];
            }
        }

        $total = (float) ($bill->amount + $penalty);

        // Get OAuth Token First
        $token = $this->getToken();
        if (!$token) {
            return ['status' => 'error', 'message' => 'Failed to retrieve Mandiri API Token', 'code' => 500];
        }

        $apiUrl = $this->config['api_url'] ?? 'https://api-dev.bankmandiri.co.id';
        
        // This is a placeholder payload for Mandiri QRIS Generate
        // Adjust the payload structure based on official Mandiri docs
        $payload = [
            'partnerReferenceNo' => $bill->bill_number,
            'amount' => [
                'value' => number_format($total, 2, '.', ''),
                'currency' => 'IDR'
            ],
            'merchantId' => $this->config['merchant_id'] ?? 'MANDIRI_MERCHANT_ID',
            'terminalId' => $this->config['terminal_id'] ?? 'T01',
            'validityPeriod' => Carbon::now()->addMinutes(30)->toIso8601String()
        ];

        try {
            $response = $this->getHttpClient()
                ->withToken($token)
                ->post($apiUrl . '/v1.0/qr/qr-mpm-generate', $payload); // Standard SNAP QRIS Endpoint

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'status' => 'success',
                    'code' => 200,
                    'data' => [
                        'bill_number' => $bill->bill_number,
                        'taxpayer_name' => $bill->taxpayer->name ?? 'N/A',
                        'amount' => $total,
                        'qr_string' => $data['qrContent'] ?? $data['qrString'] ?? '',
                        'expiry_at' => Carbon::now()->addMinutes(30)->toDateTimeString(),
                    ]
                ];
            }
            Log::error('Mandiri Generate QR Failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Mandiri Generate QR Exception: ' . $e->getMessage());
        }

        return ['status' => 'error', 'message' => 'Gagal membuat QRIS Mandiri', 'code' => 500];
    }

    /**
     * Notify (QRIS Payment Success Callback) -> Instruksi 3 Bank Mandiri
     */
    public function notify(array $payload): array
    {
        $billNumber = $payload['partnerReferenceNo'] ?? $payload['bill_number'] ?? null;
        $amountPaid = (float) ($payload['amount']['value'] ?? $payload['amount_paid'] ?? 0);
        $rrn = $payload['customerReference'] ?? $payload['rrn'] ?? $payload['transaction_id'] ?? null;

        if (!$billNumber || !$amountPaid || !$rrn) {
            return ['status' => 'error', 'message' => 'Invalid Mandiri QRIS payload', 'code' => 400];
        }

        $bill = Bill::where('bill_number', $billNumber)->first();
        if (!$bill) {
            return ['status' => 'error', 'message' => 'Tagihan tidak ditemukan', 'code' => 404];
        }

        return DB::transaction(function () use ($bill, $amountPaid, $rrn, $payload) {
            if ($bill->status === 'lunas') {
                return ['status' => 'success', 'message' => 'Tagihan sudah lunas (Idempotent)', 'code' => 200];
            }

            // Final Snapshot Penalty
            $taxObject = $bill->taxObject;
            $currentPenalty = $bill->penalty_amount;
            if ($taxObject) {
                $pendingPeriods = $this->billingService->getPendingPeriods($taxObject);
                $periodData = $pendingPeriods->firstWhere('period', $bill->period);
                if ($periodData) {
                    $currentPenalty = $periodData['penalty_amount'];
                }
            }

            $bill->update([
                'status' => 'lunas',
                'penalty_at_payment' => $currentPenalty,
                'bank_code' => 'MANDIRI_QRIS'
            ]);

            $ntpd = 'NTPD-MND-' . date('Ymd') . '-' . strtoupper(Str::random(8));
            Payment::create([
                'bill_id' => $bill->id,
                'tax_object_id' => $bill->tax_object_id,
                'taxpayer_id' => $bill->taxpayer_id,
                'transaction_id' => $rrn,
                'reference_number' => $rrn,
                'receipt_number' => $ntpd,
                'payment_method' => 'qris',
                'channel' => 'Bank Mandiri QRIS',
                'amount' => $amountPaid,
                'status' => 'success',
                'billing_period' => $bill->period ?? Carbon::now()->format('Y-m'),
                'paid_at' => Carbon::now(),
                'raw_callback_data' => $payload
            ]);

            // [HOOK] TTE Automated Signing
            try {
                $signer = \App\Models\User::whereIn('role', ['super_admin', 'admin', 'kabid_pengawas'])->first();
                if ($signer) {
                    $docService = app(\App\Services\OfficialDocumentService::class);
                    $docService->signDocument('bill', $bill->id, $signer, 'Signed automatically via Mandiri QRIS Callback');
                }
            } catch (\Exception $e) {
                Log::error('Mandiri QRIS TTE Hook Failed:', ['bill' => $bill->bill_number, 'error' => $e->getMessage()]);
            }

            return [
                'status' => 'success',
                'code' => 200,
                'data' => [
                    'ntpd' => $ntpd,
                    'status' => 'LUNAS'
                ]
            ];
        });
    }

    /**
     * Reversal
     */
    public function reversal(array $payload): array
    {
        return ['status' => 'error', 'message' => 'QRIS does not support automated reversal', 'code' => 405];
    }
}
