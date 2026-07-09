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
     * Webhook Notifikasi Saldo Masuk dari BPD
     * Dipanggil oleh Bank Sultra saat ada pembayaran konsumen masuk ke rekening Merchant
     */
    public function incomingTransfer(Request $request)
    {
        // 1. Validasi Keamanan (Signature, Token) akan dilakukan di Middleware
        $payload = $request->all();

        Log::info('Webhook Incoming Transfer BPD Received', $payload);

        // 2. Ambil data transaksi
        $accountNo = $payload['accountNo'] ?? null;
        $transactionAmount = $payload['transactionAmount']['value'] ?? 0;
        $partnerReferenceNo = $payload['partnerReferenceNo'] ?? null;

        if (! $accountNo || $transactionAmount <= 0) {
            return response()->json([
                'responseCode' => '4000000',
                'responseMessage' => 'Bad Request: Missing account or amount',
            ], 400);
        }

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
