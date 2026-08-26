<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\TaxObject;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BankSultraDriver implements PaymentGatewayInterface
{
    protected $billingService;
    protected $config;

    public function __construct(BillingService $billingService, array $config = [])
    {
        $this->billingService = $billingService;
        $this->config = $config;
    }

    /**
     * Inquiry (Bank checks bill detail via bill_number)
     */
    public function inquiry(string $billNumber): array
    {
        $bill = Bill::where('bill_number', $billNumber)->first();

        if (!$bill) {
            return [
                'status' => 'error',
                'message' => 'Tagihan tidak ditemukan',
                'code' => 404
            ];
        }

        if ($bill->status === 'lunas') {
            return [
                'status' => 'error',
                'message' => 'Tagihan sudah lunas',
                'code' => 422
            ];
        }

        // Logic JIT: Recalculate penalty real-time
        $taxObject = $bill->taxObject;
        $penalty = $bill->penalty_amount;
        $total = $bill->amount + $penalty;

        if ($taxObject) {
            $pendingPeriods = $this->billingService->getPendingPeriods($taxObject);
            $currentPeriod = $pendingPeriods->firstWhere('period', $bill->period);
            
            if ($currentPeriod) {
                $penalty = $currentPeriod['penalty_amount'];
                $total = $currentPeriod['total_amount'];
            }
        }

        return [
            'status' => 'success',
            'code' => 200,
            'data' => [
                'bill_number' => $bill->bill_number,
                'taxpayer_name' => $bill->taxpayer->name ?? 'N/A',
                'tax_object' => $taxObject ? $taxObject->name : 'N/A',
                'period' => $bill->period,
                'amount_pokok' => (float) $bill->amount,
                'penalty_amount' => (float) $penalty,
                'total_amount' => (float) $total,
                'due_date' => $bill->due_date->toDateTimeString(),
            ]
        ];
    }

    /**
     * Notify (Bank sends payment confirmation)
     */
    public function notify(array $payload): array
    {
        $billNumber = $payload['bill_number'] ?? null;
        $amountPaid = (float) ($payload['amount_paid'] ?? 0);
        $ntb = $payload['transaction_id'] ?? $payload['ntb'] ?? null;

        if (!$billNumber || !$amountPaid || !$ntb) {
            return ['status' => 'error', 'message' => 'Invalid payload', 'code' => 400];
        }

        // 1. Idempotency check: Check if NTB already exists in payments
        $exists = Payment::where('reference_number', $ntb)->exists();
        if ($exists) {
            return [
                'status' => 'success', 
                'message' => 'Transaksi duplikat (Idempotent Success)', 
                'code' => 200,
                'data' => [
                    'ntpd' => Payment::where('reference_number', $ntb)->first()->receipt_number ?? 'N/A',
                    'status' => 'LUNAS'
                ]
            ];
        }

        $bill = Bill::where('bill_number', $billNumber)->first();
        if (!$bill) {
            return ['status' => 'error', 'message' => 'Tagihan tidak ditemukan', 'code' => 404];
        }

        // 2. Bill status check
        if ($bill->status === 'lunas') {
            return ['status' => 'error', 'message' => 'Tagihan sudah lunas', 'code' => 422];
        }

        return DB::transaction(function () use ($bill, $amountPaid, $ntb, $payload) {
            // Final JIT Sync
            $taxObject = $bill->taxObject;
            $currentPenalty = $bill->penalty_amount;
            if ($taxObject) {
                $pendingPeriods = $this->billingService->getPendingPeriods($taxObject);
                $periodData = $pendingPeriods->firstWhere('period', $bill->period);
                if ($periodData) {
                    $currentPenalty = $periodData['penalty_amount'];
                }
            }

            $expectedTotal = (float) ($bill->amount + $currentPenalty);
            
            // Validation: amount must match (No partial payment for H2H yet)
            if (abs($amountPaid - $expectedTotal) > 0.01) {
                return [
                    'status' => 'error', 
                    'message' => "Nominal tidak sesuai. Diharapkan: $expectedTotal, Terbayar: $amountPaid", 
                    'code' => 422
                ];
            }

            // Update Bill
            $bill->update([
                'status' => 'lunas',
                'penalty_at_payment' => $currentPenalty,
                'bank_code' => 'SULTRA'
            ]);

            // Create Payment Record
            $ntpd = 'NTPD-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(10));
            $payment = Payment::create([
                'bill_id' => $bill->id,
                'tax_object_id' => $bill->tax_object_id,
                'taxpayer_id' => $bill->taxpayer_id,
                'transaction_id' => 'H2H-' . time(),
                'reference_number' => $ntb,
                'receipt_number' => $ntpd,
                'payment_method' => 'va',
                'channel' => $payload['channel'] ?? 'Teller',
                'amount' => $amountPaid,
                'status' => 'success',
                'billing_period' => $bill->period ?? Carbon::now()->format('Y-m'),
                'paid_at' => Carbon::now(),
                'raw_callback_data' => $payload
            ]);

            // [HOOK] TTE Automated Signing for Receipt (SSPD/SSRD)
            try {
                $signer = \App\Models\User::whereIn('role', ['super_admin', 'admin', 'kabid_pengawas'])->first();
                if ($signer) {
                    $docService = app(\App\Services\OfficialDocumentService::class);
                    $docService->signDocument('bill', $bill->id, $signer, 'Signed automatically via Bank H2H Callback');
                }
            } catch (\Exception $e) {
                Log::error('H2H TTE Hook Failed:', ['bill' => $bill->bill_number, 'error' => $e->getMessage()]);
                // We don't roll back the payment just because TTE failed
            }

            return [
                'status' => 'success',
                'code' => 200,
                'data' => [
                    'ntpd' => $ntpd,
                    'bill_number' => $bill->bill_number,
                    'status' => 'LUNAS'
                ]
            ];
        });
    }

    /**
     * Reversal (Bank cancels payment)
     */
    public function reversal(array $payload): array
    {
        $ntb = $payload['ntb'] ?? $payload['transaction_id'] ?? null;
        
        if (!$ntb) {
            return ['status' => 'error', 'message' => 'Reference number (NTB) required', 'code' => 400];
        }

        $payment = Payment::where('reference_number', $ntb)->first();
        if (!$payment) {
            return ['status' => 'error', 'message' => 'Transaksi tidak ditemukan', 'code' => 404];
        }

        return DB::transaction(function () use ($payment, $payload) {
            $bill = $payment->bill;
            if ($bill) {
                $bill->update(['status' => 'pending']);
            }

            $payment->update([
                'status' => 'failed',
                'raw_callback_data' => array_merge($payment->raw_callback_data ?? [], [
                    'reversal_at' => Carbon::now()->toDateTimeString(),
                    'reversal_reason' => $payload['reason'] ?? 'Reversal by Bank'
                ])
            ]);

            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Reversal berhasil diproses'
            ];
        });
    }

    /**
     * Create Virtual Account di Bank Sultra (SNAP VA)
     * Spek Bank Sultra (konfirmasi 26 Agu 2026):
     * - TANPA X-CLIENT-KEY (beda dgn BRI envelope)
     * - Signature: SHA256withRSA (private key M-PAD) atas
     *   stringToSign = METHOD:EndpointUrl:lowercase(hex(sha256(minify(body)))):timestamp
     * - Endpoint: {base_url}/api/v1.0/transfer-va/create-va (port 15009)
     */
    public function createVA(string $billNumber): array
    {
        $bill = Bill::where('bill_number', $billNumber)->first();
        if (!$bill) {
            return ['status' => 'error', 'message' => 'Tagihan tidak ditemukan', 'code' => 404];
        }
        if ($bill->status === 'lunas') {
            return ['status' => 'error', 'message' => 'Tagihan sudah lunas', 'code' => 422];
        }

        $partnerId = config('snap.partners.BANK_SULTRA.partner_id', '');
        $channelId = config('snap.partners.BANK_SULTRA.channel_id', '');
        $baseUrl = rtrim((string) config('snap.bank_sultra_aft.base_url', ''), '/');
        $privateKeyPath = (string) config('snap.security.mpad_private_key_path', '');

        if (empty($partnerId) || empty($channelId) || empty($baseUrl) || empty($privateKeyPath) || !file_exists($privateKeyPath)) {
            Log::error('BankSultra createVA: konfigurasi tidak lengkap', [
                'partnerId' => $partnerId, 'channelId' => $channelId,
                'baseUrl' => $baseUrl, 'keyExists' => file_exists($privateKeyPath),
            ]);
            return ['status' => 'error', 'message' => 'Konfigurasi Bank Sultra belum lengkap', 'code' => 500];
        }

        $taxObject = $bill->taxObject;
        $taxpayer = $bill->taxpayer;
        // customerNo WAJIB numerik ≤20 digit (konfirmasi bank 26 Agu: contoh "   80101000000002" = SPID 8 + customerNo 9 digit)
        $customerNo = str_pad((string) $bill->id, 9, '0', STR_PAD_LEFT);
        $total = (float) ($bill->amount + (float) $bill->penalty_amount);
        $totalStr = number_format($total, 2, '.', '');
        $vaName = mb_substr($taxpayer->name ?? 'Wajib Pajak M-PAD', 0, 30);

        $partnerServiceId = str_pad($partnerId, 8, ' ', STR_PAD_LEFT);
        $virtualAccountNo = $partnerServiceId . $customerNo;

        $body = [
            'additionalInfo' => [
                'channel' => $channelId,
                'kodePajak' => '7472',
            ],
            'billDetails' => [[
                'billAmount' => ['currency' => 'IDR', 'value' => $totalStr],
                'billCode' => '01',
                'billDescription' => ['indonesia' => 'Pembayaran PBB'],
                'billName' => $customerNo,
                'billNo' => $customerNo,
            ]],
            'customerNo' => $customerNo,
            'expiredDate' => Carbon::now()->addDay()->format('Y-m-d\TH:i:sP'),
            'feeAmount' => ['currency' => 'IDR', 'value' => '0.00'],
            'partnerServiceId' => $partnerServiceId,
            'totalAmount' => ['currency' => 'IDR', 'value' => $totalStr],
            'trxId' => (string) Str::uuid(),
            'virtualAccountName' => $vaName,
            'virtualAccountNo' => $virtualAccountNo,
            'virtualAccountPhone' => '-',
            'virtualAccountTrxType' => '1',
        ];

        $timestamp = Carbon::now()->timezone('Asia/Makassar')->format('Y-m-d\TH:i:sP');
        $endpoint = '/api/v1.0/transfer-va/create-va';
        $canonicalBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // stringToSign formula Bank Sultra
        $stringToSign = 'POST:' . $endpoint . ':' . strtolower(hash('sha256', $canonicalBody)) . ':' . $timestamp;

        $privateKey = file_get_contents($privateKeyPath);
        $pkey = openssl_pkey_get_private($privateKey);
        if (!$pkey) {
            Log::error('BankSultra createVA: gagal baca private key');
            return ['status' => 'error', 'message' => 'Private key invalid', 'code' => 500];
        }
        openssl_sign($stringToSign, $signature, $pkey, OPENSSL_ALGO_SHA256);

        $externalId = date('Ymd') . str_pad((string) $bill->id, 12, '0', STR_PAD_LEFT);

        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'channel-id' => $channelId,
                'content-type' => 'application/json',
                'x-external-id' => $externalId,
                'x-partner-id' => $partnerId,
                'x-signature' => base64_encode($signature),
                'x-timestamp' => $timestamp,
            ])->timeout(20)->withoutVerifying()->post($baseUrl . $endpoint, $body);
        } catch (\Exception $e) {
            Log::error('BankSultra createVA: koneksi gagal', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Koneksi ke Bank Sultra gagal: ' . $e->getMessage(), 'code' => 503];
        }

        Log::info('BankSultra createVA response', [
            'http' => $response->status(),
            'body' => $response->json(),
        ]);

        $result = $response->json() ?? [];

        if ($response->successful() && ($result['responseCode'] ?? '') === '2007300') {
            return [
                'status' => 'success',
                'code' => 200,
                'data' => [
                    'trxId' => $result['trxId'] ?? $body['trxId'],
                    'virtualAccountNo' => $result['virtualAccountNo'] ?? $virtualAccountNo,
                    'virtualAccountName' => $result['virtualAccountName'] ?? $vaName,
                    'totalAmount' => $result['totalAmount'] ?? $body['totalAmount'],
                    'expiredDate' => $result['expiredDate'] ?? $body['expiredDate'],
                    'raw' => $result,
                ],
            ];
        }

        return [
            'status' => 'error',
            'code' => $response->status(),
            'message' => 'Create VA ditolak Bank Sultra',
            'data' => $result,
        ];
    }

    /**
     * Get account details (VA number) for a bill
     */
    public function getAccountDetail(string $billNumber): array
    {
        // Buat VA di Bank Sultra (SNAP VA)
        $result = $this->createVA($billNumber);
        if (($result['status'] ?? '') === 'success') {
            return [
                'status' => 'success',
                'data' => [
                    'bank_name' => 'Bank Sultra',
                    'va_number' => $result['data']['virtualAccountNo'] ?? '',
                    'expired_date' => $result['data']['expiredDate'] ?? null,
                    'bill_info' => $this->inquiry($billNumber)['data'] ?? [],
                ],
            ];
        }

        return $result;
    }

    /**
     * Reconcile daily transactions with bank report
     */
    public function reconcile(array $transactions): array
    {
        $results = [
            'matched' => 0,
            'mismatch' => 0,
            'details' => []
        ];

        foreach ($transactions as $tx) {
            $ntb = $tx['ntb'] ?? null;
            $amount = (float) ($tx['amount'] ?? 0);
            
            $payment = Payment::where('reference_number', $ntb)->first();
            
            if ($payment && abs((float)$payment->amount - $amount) < 0.01) {
                $results['matched']++;
            } else {
                $results['mismatch']++;
                $results['details'][] = [
                    'ntb' => $ntb,
                    'bank_amount' => $amount,
                    'system_amount' => $payment ? $payment->amount : 0,
                    'status' => $payment ? 'Nominal Berbeda' : 'Tidak Ditemukan'
                ];
            }
        }

        return [
            'status' => 'success',
            'data' => $results
        ];
    }
}
