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
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;

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

        if ($bill->isSettled()) {
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

        $bill = Bill::where('bill_number', $billNumber)->first();
        if (!$bill) {
            return ['status' => 'error', 'message' => 'Tagihan tidak ditemukan', 'code' => 404];
        }

        $masker = app(\App\Services\Payment\Snap\SensitivePaymentLogMasker::class);

        // Idempotency + status check DILAKUKAN DI DALAM transaksi dgn lockForUpdate,
        // agar dua callback bersamaan tidak double-process bill yang sama.
        return DB::transaction(function () use ($bill, $amountPaid, $ntb, $payload, $masker) {
            // Kunci baris bill utk mencegah race condition (dua callback bersamaan)
            $lockedBill = Bill::whereKey($bill->id)->lockForUpdate()->first();

            // Idempotency check: reference_number sudah ada => callback duplikat
            $existing = Payment::where('reference_number', $ntb)->first();
            if ($existing) {
                return [
                    'status' => 'success',
                    'message' => 'Transaksi duplikat (Idempotent Success)',
                    'code' => 200,
                    'data' => [
                        'ntpd' => $existing->receipt_number ?? 'N/A',
                        'status' => 'LUNAS'
                    ]
                ];
            }

            // Status check bill (re-check setelah lock — kalau sudah lunas, jangan double update)
            if ($lockedBill->isSettled()) {
                return ['status' => 'error', 'message' => 'Tagihan sudah lunas', 'code' => 422];
            }

            // Final JIT Sync
            $taxObject = $lockedBill->taxObject;
            $currentPenalty = $lockedBill->penalty_amount;
            if ($taxObject) {
                $pendingPeriods = $this->billingService->getPendingPeriods($taxObject);
                $periodData = $pendingPeriods->firstWhere('period', $lockedBill->period);
                if ($periodData) {
                    $currentPenalty = $periodData['penalty_amount'];
                }
            }

            $expectedTotal = (float) ($lockedBill->amount + $currentPenalty);
            
            // Validation: amount must match (No partial payment for H2H yet)
            if (abs($amountPaid - $expectedTotal) > 0.01) {
                return [
                    'status' => 'error', 
                    'message' => "Nominal tidak sesuai. Diharapkan: $expectedTotal, Terbayar: $amountPaid", 
                    'code' => 422
                ];
            }

            // Update Bill
            $lockedBill->update([
                'status' => 'lunas',
                'penalty_at_payment' => $currentPenalty,
                'bank_code' => 'SULTRA'
            ]);

            // Create Payment Record
            $ntpd = 'NTPD-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(10));
            $payment = Payment::create([
                'bill_id' => $lockedBill->id,
                'tax_object_id' => $lockedBill->tax_object_id,
                'taxpayer_id' => $lockedBill->taxpayer_id,
                'transaction_id' => 'H2H-' . time(),
                'reference_number' => $ntb,
                'receipt_number' => $ntpd,
                'payment_method' => 'va',
                'channel' => $payload['channel'] ?? 'Teller',
                'amount' => $amountPaid,
                'status' => 'success',
                'billing_period' => $lockedBill->period ?? Carbon::now()->format('Y-m'),
                'paid_at' => Carbon::now(),
                'raw_callback_data' => $masker->mask($payload)
            ]);

            // [HOOK] TTE Automated Signing for Receipt (SSPD/SSRD/Aset)
            try {
                app(\App\Services\AutoTteHookService::class)->handlePaidBill($lockedBill, 'va', $ntpd);
            } catch (\Throwable $e) {
                Log::error('H2H TTE Hook Failed:', ['bill' => $lockedBill->bill_number, 'error' => $e->getMessage()]);
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
     * Spek Bank Sultra (TERVERIFIKASI 26-27 Agu 2026):
     * - TANPA X-CLIENT-KEY
     * - Signature: OAEP-SHA256 encrypt dengan PUBLIC key BANK atas
     *   stringToSign = METHOD:EndpointUrl:DOUBLEHEX(sha256(minify(body))):timestamp
     *   (hash di-hex DUA KALI — 128 char; bukan single hex!)
     * - Endpoint: {base_url}/api/v1.0/transfer-va/create-va (port 15009)
     * - customerNo = NOP 18 digit | billNo = 9 digit | virtualAccountNo = SPID(8)+last8
     */
    public function createVA(string $billNumber, ?Carbon $expiredAt = null): array
    {
        $bill = Bill::where('bill_number', $billNumber)->first();
        if (!$bill) {
            return ['status' => 'error', 'message' => 'Tagihan tidak ditemukan', 'code' => 404];
        }
        if ($bill->isSettled()) {
            return ['status' => 'error', 'message' => 'Tagihan sudah lunas', 'code' => 422];
        }

        $partnerId = config('snap.partners.BANK_SULTRA.partner_id', '');
        $channelId = config('snap.partners.BANK_SULTRA.channel_id', '');
        $baseUrl = rtrim((string) config('snap.bank_sultra_aft.base_url', ''), '/');
        $privateKeyPath = (string) config('snap.security.mpad_private_key_path', '');
        $publicBankKeyPath = (string) config('snap.bank_sultra_aft.bank_public_key_path', storage_path('app/keys/bank_sultra_public_key.pem'));

        if (empty($partnerId) || empty($channelId) || empty($baseUrl) || empty($privateKeyPath) || !file_exists($privateKeyPath) || !file_exists($publicBankKeyPath)) {
            Log::error('BankSultra createVA: konfigurasi tidak lengkap', [
                'partnerId' => $partnerId, 'channelId' => $channelId,
                'baseUrl' => $baseUrl, 'keyExists' => file_exists($privateKeyPath),
                'bankPublicKeyExists' => file_exists($publicBankKeyPath),
            ]);
            return ['status' => 'error', 'message' => 'Konfigurasi Bank Sultra belum lengkap', 'code' => 500];
        }

        $taxObject = $bill->taxObject;
        $taxpayer = $bill->taxpayer;
        // customerNo = NOP 18 digit (konfirmasi bank 26 Agu: "diset 18 digit aja" dari bank = customerNo, bukan billNo!)
        $rawNop = preg_replace('/\D/', '', (string) ($taxObject->nop ?? $bill->id));
        $customerNo = str_pad($rawNop, 18, '0', STR_PAD_LEFT);
        $total = (float) ($bill->amount + (float) $bill->penalty_amount);
        $totalStr = number_format($total, 2, '.', '');
        $vaName = mb_substr($taxpayer->name ?? 'Wajib Pajak M-PAD', 0, 30);

        $partnerServiceId = str_pad($partnerId, 8, ' ', STR_PAD_LEFT);
        // virtualAccountNo = partnerServiceId(8) + suffix 8 digit (BUKAN + customerNo penuh)
        $virtualAccountNo = $partnerServiceId . substr($customerNo, -8);

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
                'billNo' => substr($customerNo, -9),
            ]],
            'customerNo' => $customerNo,
'expiredDate' => ($expiredAt ?? Carbon::now()->addDay())->format('Y-m-d\TH:i:sP'),
            'feeAmount' => ['currency' => 'IDR', 'value' => '0.00'],
            'partnerServiceId' => $partnerServiceId,
            'totalAmount' => ['currency' => 'IDR', 'value' => $totalStr],
            'trxId' => (string) Str::uuid(),
            'virtualAccountName' => $vaName,
            'virtualAccountNo' => $virtualAccountNo,
            'virtualAccountPhone' => '-',
            'virtualAccountTrxType' => '1',
        ];

        $timestamp = Carbon::now()->timezone('Asia/Makassar')->format('Y-m-d\\TH:i:sP');
        $endpoint = '/api/v1.0/transfer-va/create-va';
        $canonicalBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // stringToSign formula Bank Sultra (TERVERIFIKASI 26 Agu 20:42):
        //   hash1 = sha256(minify(body)) hex (64 char)
        //   hash2 = hex(hash1) lagi — DOUBLE HEX (128 char) — INI KUNCI!
        $hash1 = strtolower(hash('sha256', $canonicalBody));
        $hash2 = bin2hex($hash1); // hex di-hex lagi
        $stringToSign = 'POST:' . $endpoint . ':' . $hash2 . ':' . $timestamp;

        // X-SIGNATURE = OAEP-SHA256 encrypt stringToSign dgn PUBLIC key BANK (4096).
        // BUKAN openssl_sign (PKCS1 private M-PAD) — itu selalu "oaep decoding error".
        // PHP openssl_public_encrypt OAEP default SHA-1 TIDAK cukup → pakai phpseclib3.
        $bankPublicKey = file_get_contents($publicBankKeyPath);
        try {
            $rsa = PublicKeyLoader::load($bankPublicKey)
                ->withHash('sha256')
                ->withMGFHash('sha256')
                ->withPadding(RSA::ENCRYPTION_OAEP);
            $signature = $rsa->encrypt($stringToSign);
        } catch (\Throwable $e) {
            Log::error('BankSultra createVA: gagal OAEP encrypt public key bank', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Gagal buat signature: ' . $e->getMessage(), 'code' => 500];
        }

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

        if ($response->successful() && ($result['responseCode'] ?? '') === '2002700') {
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
            $ntb = $tx['ntb'] ?? $tx['reference_number'] ?? $tx['transaction_id'] ?? null;
            $amount = (float) ($tx['amount'] ?? $tx['amount_paid'] ?? 0);

            $payment = $ntb
                ? Payment::with('bill')->where('reference_number', $ntb)->first()
                : null;
            $paymentStatus = $payment
                ? strtolower(trim((string) $payment->getRawOriginal('status')))
                : null;
            $billSettled = $payment?->bill?->isSettled() ?? false;
            $amountMatches = $payment !== null && abs((float) $payment->amount - $amount) < 0.01;

            if ($payment && $paymentStatus === 'success' && $billSettled && $amountMatches) {
                $results['matched']++;
            } else {
                $results['mismatch']++;
                $results['details'][] = [
                    'ntb' => $ntb,
                    'bank_amount' => $amount,
                    'system_amount' => $payment ? $payment->amount : 0,
                    'status' => $this->reconciliationMismatchStatus(
                        $payment,
                        $paymentStatus,
                        $billSettled,
                        $amountMatches,
                    ),
                ];
            }
        }

        return [
            'status' => 'success',
            'data' => $results
        ];
    }

    private function reconciliationMismatchStatus(
        ?Payment $payment,
        ?string $paymentStatus,
        bool $billSettled,
        bool $amountMatches,
    ): string {
        if (!$payment) {
            return 'Tidak Ditemukan';
        }

        if ($paymentStatus !== 'success') {
            return 'Status pembayaran bukan success';
        }

        if (!$billSettled) {
            return 'Bill belum settled';
        }

        return $amountMatches ? 'Tidak cocok' : 'Nominal Berbeda';
    }
}
