<?php

namespace App\Http\Controllers;

use App\Models\AutoDeductLog;
use App\Services\AutoDeductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AutoDeductWebhookController extends Controller
{
    protected AutoDeductService $autoDeductService;

    public function __construct(AutoDeductService $autoDeductService)
    {
        $this->autoDeductService = $autoDeductService;
    }

    /**
     * Webhook dari POS / Payment Gateway
     * Menerima notifikasi pembayaran atau koreksi dari eksternal
     *
     * POST /api/v1/auto-deduct/webhook
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        $event = $payload['event'] ?? 'unknown';

        Log::info('AutoDeduct Webhook Received', ['event' => $event, 'payload' => $payload]);

        return match ($event) {
            'payment.success' => $this->handlePaymentSuccess($payload),
            'payment.failed' => $this->handlePaymentFailed($payload),
            'transaction.void' => $this->handleTransactionVoid($payload),
            'transaction.adjust' => $this->handleTransactionAdjust($payload),
            default => response()->json(['status' => 'ignored', 'message' => 'Unknown event type']),
        };
    }

    /**
     * Notifikasi pembayaran berhasil dari Payment Gateway
     */
    protected function handlePaymentSuccess(array $payload)
    {
        $referenceNumber = $payload['reference_number'] ?? null;
        if (!$referenceNumber) {
            return response()->json(['status' => 'error', 'message' => 'reference_number required'], 400);
        }

        $log = AutoDeductLog::where('reference_number', $referenceNumber)->first();
        if (!$log) {
            return response()->json(['status' => 'error', 'message' => 'Log not found'], 404);
        }

        $log->update([
            'status' => 'success',
            'payment_channel' => $payload['channel'] ?? $log->payment_channel,
            'processed_at' => now(),
            'metadata' => array_merge($log->metadata ?? [], ['webhook_payload' => $payload]),
        ]);

        return response()->json(['status' => 'success', 'log_id' => $log->id]);
    }

    /**
     * Notifikasi pembayaran gagal
     */
    protected function handlePaymentFailed(array $payload)
    {
        $referenceNumber = $payload['reference_number'] ?? null;
        if (!$referenceNumber) {
            return response()->json(['status' => 'error', 'message' => 'reference_number required'], 400);
        }

        $log = AutoDeductLog::where('reference_number', $referenceNumber)->first();
        if ($log) {
            $log->update([
                'status' => 'failed',
                'failure_reason' => $payload['reason'] ?? 'Pembayaran gagal dari gateway',
                'metadata' => array_merge($log->metadata ?? [], ['webhook_payload' => $payload]),
            ]);
        }

        return response()->json(['status' => 'logged']);
    }

    /**
     * Notifikasi void dari POS eksternal
     */
    protected function handleTransactionVoid(array $payload)
    {
        $referenceNumber = $payload['reference_number'] ?? null;
        if (!$referenceNumber) {
            return response()->json(['status' => 'error', 'message' => 'reference_number required'], 400);
        }

        $log = AutoDeductLog::where('reference_number', $referenceNumber)->first();
        if ($log) {
            $log->update([
                'status' => 'voided',
                'correction_type' => 'void',
                'correction_note' => $payload['reason'] ?? 'Void dari eksternal',
                'corrected_at' => now(),
            ]);
        }

        return response()->json(['status' => 'voided']);
    }

    /**
     * Notifikasi adjustment dari POS eksternal
     */
    protected function handleTransactionAdjust(array $payload)
    {
        $referenceNumber = $payload['reference_number'] ?? null;
        if (!$referenceNumber) {
            return response()->json(['status' => 'error', 'message' => 'reference_number required'], 400);
        }

        $newAmount = (float) ($payload['new_amount'] ?? 0);
        $log = AutoDeductLog::where('reference_number', $referenceNumber)->first();
        if (!$log) {
            return response()->json(['status' => 'error', 'message' => 'Log not found'], 404);
        }

        // Recalculate tax
        $taxObject = $log->taxObject;
        $classification = $taxObject?->classification;
        $newTax = 0;
        if ($classification && $classification->calculation_formula) {
            $newTax = $this->calculateTax($classification->calculation_formula, $newAmount);
        }

        $oldAmount = (float) $log->transaction_amount;
        $oldTax = (float) $log->tax_amount;
        $taxDiff = $newTax - $oldTax;

        $log->update([
            'status' => 'adjusted',
            'correction_type' => 'price_adjustment',
            'original_transaction_amount' => $oldAmount,
            'corrected_transaction_amount' => $newAmount,
            'tax_recalculated' => $newTax,
            'tax_difference' => $taxDiff,
            'correction_note' => $payload['reason'] ?? 'Adjustment dari eksternal',
            'corrected_at' => now(),
        ]);

        return response()->json([
            'status' => 'adjusted',
            'details' => [
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'old_tax' => $oldTax,
                'new_tax' => $newTax,
                'tax_difference' => $taxDiff,
            ]
        ]);
    }

    private function calculateTax(?string $formula, float $amount): float
    {
        if (!$formula) return 0;
        if (str_contains($formula, 'omzet') || str_contains($formula, 'tagihan')) {
            preg_match('/\* (\d+\.?\d*)/', $formula, $matches);
            return round($amount * ((float) ($matches[1] ?? 0.1)), 2);
        }
        return 0;
    }
}
