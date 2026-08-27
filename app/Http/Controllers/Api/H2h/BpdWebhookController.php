<?php

namespace App\Http\Controllers\Api\H2h;

use App\Http\Controllers\Controller;
use App\Jobs\ExecuteAutoDebitAFT;
use App\Models\AutoDeductLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BpdWebhookController extends Controller
{
    /**
     * Webhook Notifikasi Saldo Masuk dari BPD / Callback VA Bank Sultra
     * Dipanggil Bank Sultra saat pembayaran masuk (VA payment notification)
     */
    public function incomingTransfer(Request $request)
    {
        $payload = $request->all();

        // Selalu catat payload MENTAH + header (untuk verifikasi signature & analisa format callback)
        Log::info('Webhook Incoming Transfer BPD Received', [
            'payload' => $payload,
            'headers' => collect($request->headers->all())
                ->map(fn ($v) => is_array($v) ? ($v[0] ?? null) : $v)
                ->filter()
                ->toArray(),
        ]);

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

        // Fase uji callback VA Bank Sultra (27 Agu): cukup catat & ACK.
        // Mapping NOP/taxpayer & alur AFT untuk VA Sultra belum diverifikasi —
        // JANGAN trigger AutoDebit otomatis sebelum alur final disepakati dengan bank.
        $isSultraVa = isset($payload['virtualAccountNo']) || isset($payload['paidAmount']) || isset($payload['partnerServiceId']);

        if ($isSultraVa) {
            Log::info('BankSultra VA callback diterima (fase uji)', [
                'virtualAccountNo' => $accountNo,
                'amount' => $transactionAmount,
                'trxId' => $partnerReferenceNo,
                'paymentDate' => $paymentDate,
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
