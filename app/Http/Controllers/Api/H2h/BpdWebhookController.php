<?php

namespace App\Http\Controllers\Api\H2h;

use App\Http\Controllers\Controller;
use App\Jobs\ExecuteAutoDebitAFT;
use App\Models\AutoDeductLog;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\TaxObject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;

class BpdWebhookController extends Controller
{
    /**
     * Webhook Notifikasi Saldo Masuk dari BPD / Callback VA Bank Sultra
     * Dipanggil Bank Sultra saat pembayaran masuk (VA payment notification)
     */
    public function incomingTransfer(Request $request)
    {
        $payload = $request->all();

        // Selalu catat payload MENTAH + header + RAW BODY (untuk verifikasi signature & analisa format callback)
        $rawBody = $request->getContent();
        Log::info('Webhook Incoming Transfer BPD Received', [
            'payload' => $payload,
            'raw_body' => $rawBody,
            'headers' => collect($request->headers->all())
                ->map(fn ($v) => is_array($v) ? ($v[0] ?? null) : $v)
                ->filter()
                ->toArray(),
        ]);

        // ===== Verifikasi X-SIGNATURE callback (Bank Sultra SNAP, TERBUKTI 27 Agu) =====
        // Bank ENCRYPT stringToSign dengan PUBLIC key M-PAD (RSA 2048) → kita DECRYPT dgn PRIVATE key M-PAD (OAEP-SHA256).
        // stringToSign = POST:/api/v1.0/transfer-va/payment:{doublehex(sha256(rawBody))}:{x-timestamp}
        $signatureValid = null; // null = tidak bisa diverifikasi
        $xSignature = $request->header('x-signature');
        $xTimestamp = $request->header('x-timestamp');

        if ($xSignature && $xTimestamp) {
            try {
                $privPath = (string) config('snap.security.mpad_private_key_path', '');
                if ($privPath && file_exists($privPath)) {
                    $rsa = PublicKeyLoader::load(file_get_contents($privPath))
                        ->withHash('sha256')
                        ->withMGFHash('sha256')
                        ->withPadding(RSA::ENCRYPTION_OAEP);
                    $plain = $rsa->decrypt(base64_decode($xSignature));
                    $doubleHex = bin2hex(strtolower(hash('sha256', $rawBody)));
                    $expected = 'POST:/api/v1.0/transfer-va/payment:' . $doubleHex . ':' . $xTimestamp;
                    $signatureValid = hash_equals($expected, $plain);
                    Log::info('BankSultra callback signature check', [
                        'valid' => $signatureValid,
                        'xTimestamp' => $xTimestamp,
                        'rawBodyLen' => strlen($rawBody),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('BankSultra callback signature check GAGAL decrypt', ['error' => $e->getMessage()]);
            }
        }

        // Format callback Bank Sultra (SNAP VA payment notification):
        //   virtualAccountNo, paidAmount.value, trxId/referenceNo, paymentDate, partnerServiceId
        // Format lama BPD (account credit):
        //   accountNo, transactionAmount.value, partnerReferenceNo
        $accountNo = $payload['accountNo'] ?? ($payload['virtualAccountNo'] ?? ($payload['customerNo'] ?? null));
        $transactionAmount = $payload['transactionAmount']['value']
            ?? ($payload['paidAmount']['value'] ?? ($payload['totalAmount']['value'] ?? 0));
        $partnerReferenceNo = $payload['partnerReferenceNo'] ?? ($payload['referenceNo'] ?? ($payload['trxId'] ?? null));
        $paymentDate = $payload['paymentDate'] ?? null;

        if (! $accountNo || $transactionAmount <= 0) {
            Log::warning('Callback BPD/BankSultra payload belum lengkap — dicatat untuk analisa', [
                'payload' => $payload,
            ]);
            return response()->json([
                'responseCode' => '4000000',
                'responseMessage' => 'Bad Request: Missing account or amount',
            ], 400);
        }

        // Fase uji callback VA Bank Sultra (27 Agu): catat + mapping NOP.
        // Mapping NOP → tax object → bill pending. Pembayaran hanya dicatat
        // saat config('snap.bank_sultra_aft.enabled') = true (alur final disepakati).
        // AFT tetap TIDAK di-trigger otomatis dari sini (guard enabled).
        $isSultraVa = isset($payload['virtualAccountNo']) || isset($payload['paidAmount']) || isset($payload['partnerServiceId']);

        if ($isSultraVa) {
            $customerNo = $payload['customerNo'] ?? null;
            $taxObject = null;
            $bill = null;
            $taxpayerId = null;

            if ($customerNo) {
                // NOP 18 digit → taxpayer_nops.nop → taxpayer_id → bill pending
                $nopRow = DB::table('taxpayer_nops')->where('nop', $customerNo)->first()
                    ?? DB::table('taxpayer_nops')->where('nop', ltrim((string) $customerNo, '0'))->first();
                if ($nopRow) {
                    $taxpayerId = $nopRow->taxpayer_id;
                    $bill = Bill::where('taxpayer_id', $taxpayerId)
                        ->where('status', 'pending')
                        ->orderByDesc('id')
                        ->first();
                    if ($bill) {
                        $taxObject = $bill->taxObject;
                    }
                }
            }

            Log::info('BankSultra VA callback mapping', [
                'customerNo' => $customerNo,
                'taxpayerId' => $taxpayerId,
                'taxObjectId' => $taxObject?->id,
                'billId' => $bill?->id,
                'billNumber' => $bill?->bill_number,
                'amount' => $transactionAmount,
                'matched' => $bill ? true : false,
            ]);

            $aftEnabled = (bool) config('snap.bank_sultra_aft.enabled', false);

            if ($aftEnabled && $bill) {
                DB::transaction(function () use ($bill, $transactionAmount, $partnerReferenceNo, $payload) {
                    $bill->update([
                        'status' => 'lunas',
                        'penalty_at_payment' => $bill->penalty_amount,
                        'bank_code' => 'SULTRA',
                    ]);

                    Payment::create([
                        'bill_id' => $bill->id,
                        'tax_object_id' => $bill->tax_object_id,
                        'taxpayer_id' => $bill->taxpayer_id,
                        'transaction_id' => $partnerReferenceNo ?: 'VA-' . time(),
                        'reference_number' => $partnerReferenceNo ?: 'VA-' . Str::random(12),
                        'receipt_number' => 'NTPD-' . date('Ymd') . '-' . strtoupper(Str::random(10)),
                        'payment_method' => 'va',
                        'channel' => $payload['additionalInfo']['channel'] ?? 'ATM',
                        'amount' => $transactionAmount,
                        'status' => 'success',
                        'billing_period' => $bill->period ?? Carbon::now()->format('Y-m'),
                        'paid_at' => $payload['paymentDate'] ?? Carbon::now(),
                        'raw_callback_data' => $payload,
                    ]);
                });

                Log::info('BankSultra VA callback DIPROSES (payment dicatat)', [
                    'billNumber' => $bill->bill_number,
                    'amount' => $transactionAmount,
                    'referenceNo' => $partnerReferenceNo,
                ]);
            }

            Log::info('BankSultra VA callback diterima (fase uji)', [
                'virtualAccountNo' => $accountNo,
                'amount' => $transactionAmount,
                'trxId' => $partnerReferenceNo,
                'paymentDate' => $paymentDate,
                'mapped' => $bill ? true : false,
                'aftEnabled' => $aftEnabled,
                'signatureValid' => $signatureValid,
            ]);
            return response()->json([
                'responseCode' => '2003300',
                'responseMessage' => 'Successful',
                'partnerReferenceNo' => $partnerReferenceNo,
            ], 200);
        }

        // ===== Alur lama: BPD account credit (accountNo) =====
        // Asumsi: 10% dari transaksi adalah Pajak (PBJT)
        $taxAmount = $transactionAmount * 0.10;

        // 3. Catat di Log AFT
        $autoDeductLog = AutoDeductLog::create([
            'city_id' => 1, // Baubau default
            'taxpayer_id' => 1, // Akan dicari berdasarkan $accountNo
            'source' => 'bpd_webhook',
            'transaction_type' => 'income_transfer',
            'transaction_amount' => $transactionAmount,
            'tax_amount' => $taxAmount,
            'deducted_amount' => $taxAmount, // Rencana yang akan dideduct
            'reference_number' => 'AFT-'.time().'-'.rand(1000, 9999),
            'payment_channel' => 'BPD',
            'status' => 'pending',
            'metadata' => $payload,
        ]);

        // 4. Trigger Job Queue untuk menembak balik API Auto-Debit Bank Sultra
        ExecuteAutoDebitAFT::dispatch($autoDeductLog->id)->delay(now()->addSeconds(5));

        return response()->json([
            'responseCode' => '2000000',
            'responseMessage' => 'Successful',
            'partnerReferenceNo' => $partnerReferenceNo,
        ], 200);
    }
}
