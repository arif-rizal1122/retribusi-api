<?php

namespace App\Http\Controllers\Api\H2h;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
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

            $externalId = (string) $request->header('x-external-id', '');
            $matchedBy = null;

            // Prioritas 1: x-external-id memuat bill id (format: <YYYYMMDD><0..><bill_id>)
            if (preg_match('/(\d+)$/', $externalId, $mExt) && (int) $mExt[1] > 0) {
                $candidate = Bill::where('id', (int) $mExt[1])
                    ->where('status', 'pending')
                    ->first();
                if ($candidate) {
                    $bill = $candidate;
                    $taxObject = $bill->taxObject;
                    $taxpayerId = $bill->taxpayer_id;
                    $matchedBy = 'x-external-id';
                }
            }

            // Prioritas 2: NOP → taxpayer → bill pending senilai transaksi (hindari settle salah bill)
            if (! $bill && $customerNo) {
                // NOP 18 digit → taxpayer_nops.nop → taxpayer_id → bill pending
                $nopRow = DB::table('taxpayer_nops')->where('nop', $customerNo)->first()
                    ?? DB::table('taxpayer_nops')->where('nop', ltrim((string) $customerNo, '0'))->first();
                if ($nopRow) {
                    $taxpayerId = $nopRow->taxpayer_id;
                    $bill = Bill::where('taxpayer_id', $taxpayerId)
                        ->where('status', 'pending')
                        ->whereRaw('ABS(CAST(amount AS DECIMAL(12,2)) - ?) < 0.01', [(float) $transactionAmount])
                        ->orderByDesc('id')
                        ->first();
                    if ($bill) {
                        $taxObject = $bill->taxObject;
                        $matchedBy = 'nop+amount';
                    }
                }
            }

            // Pengaman: nominal harus sama persis — jangan settle parsial/salah bill
            if ($bill && abs((float) $bill->amount - (float) $transactionAmount) >= 0.01) {
                Log::warning('BankSultra VA callback amount mismatch (skip settle)', [
                    'virtualAccountNo' => $accountNo,
                    'billId' => $bill->id,
                    'billNumber' => $bill->bill_number,
                    'billAmount' => $bill->amount,
                    'paidAmount' => $transactionAmount,
                ]);
                $bill = null;
            }

            Log::info('BankSultra VA callback mapping', [
                'customerNo' => $customerNo,
                'taxpayerId' => $taxpayerId,
                'taxObjectId' => $taxObject?->id,
                'billId' => $bill?->id,
                'billNumber' => $bill?->bill_number,
                'amount' => $transactionAmount,
                'matchedBy' => $matchedBy,
                'matched' => $bill ? true : false,
            ]);

            $aftEnabled = (bool) config('snap.bank_sultra_aft.enabled', false);

            if ($aftEnabled && $bill) {
                $processed = (bool) DB::transaction(function () use ($bill, $transactionAmount, $partnerReferenceNo, $payload) {
                    // Kunci baris bill utk mencegah race condition (dua callback bersamaan)
                    $lockedBill = Bill::whereKey($bill->id)->lockForUpdate()->first();

                    // Idempotency: referenceNo sudah tercatat => callback duplikat (skipped)
                    if ($partnerReferenceNo) {
                        $existing = Payment::where('reference_number', $partnerReferenceNo)->first();
                        if ($existing) {
                            return false;
                        }
                    }

                    // Re-check status setelah lock (jangan double-settle)
                    if ($lockedBill->status === 'lunas') {
                        return false;
                    }

                    $masker = app(\App\Services\Payment\Snap\SensitivePaymentLogMasker::class);

                    $lockedBill->update([
                        'status' => 'lunas',
                        'penalty_at_payment' => $lockedBill->penalty_amount,
                        'bank_code' => 'SULTRA',
                    ]);

                    Payment::create([
                        'bill_id' => $lockedBill->id,
                        'tax_object_id' => $lockedBill->tax_object_id,
                        'taxpayer_id' => $lockedBill->taxpayer_id,
                        'transaction_id' => $partnerReferenceNo ?: 'VA-' . time(),
                        'reference_number' => $partnerReferenceNo ?: 'VA-' . Str::random(12),
                        'receipt_number' => 'NTPD-' . date('Ymd') . '-' . strtoupper(Str::random(10)),
                        'payment_method' => 'va',
                        'channel' => $payload['additionalInfo']['channel'] ?? 'ATM',
                        'amount' => $transactionAmount,
                        'status' => 'success',
                        'billing_period' => $lockedBill->period ?? Carbon::now()->format('Y-m'),
                        'paid_at' => $payload['paymentDate'] ?? Carbon::now(),
                        'raw_callback_data' => $masker->mask($payload),
                    ]);

                    return true;
                });

                if ($processed) {
                    Log::info('BankSultra VA callback DIPROSES (payment dicatat)', [
                        'billNumber' => $bill->bill_number,
                        'amount' => $transactionAmount,
                        'referenceNo' => $partnerReferenceNo,
                    ]);
                } else {
                    Log::info('BankSultra VA callback duplikat/sudah lunas (skipped, idempotent)', [
                        'billNumber' => $bill->bill_number,
                        'referenceNo' => $partnerReferenceNo,
                    ]);
                }
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
