<?php

namespace App\Http\Controllers;

use App\Services\PaymentRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(protected PaymentRequestService $service)
    {
    }

    /**
     * POST /api/webhooks/payment
     * Callback dari payment gateway (Midtrans/BRI API dll).
     *
     * Payload contoh (Midtrans):
     * {
     *   "order_id": "MPR-xxxxx",
     *   "transaction_status": "settlement",
     *   "gross_amount": "100000.00"
     * }
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('Payment webhook received', $payload);

        $orderId = $payload['order_id'] ?? $payload['external_id'] ?? null;
        $status = $payload['transaction_status'] ?? $payload['status'] ?? null;

        if (!$orderId || !$status) {
            return response()->json(['success' => false, 'message' => 'Invalid payload'], 400);
        }

        // dukung keduanya: order_id = token (MPR-...) atau external_id gateway
        $externalId = $orderId;
        $token = null;

        if (str_starts_with((string) $orderId, 'MPR-')) {
            $request = \App\Models\PaymentRequest::where('token', $orderId)->first();

            if (!$request) {
                return response()->json(['success' => false, 'message' => 'Payment request tidak ditemukan.'], 404);
            }

            $externalId = $request->external_id;
        }

        if (!$externalId) {
            return response()->json(['success' => false, 'message' => 'External id tidak ditemukan.'], 404);
        }

        $referenceNumber = $payload['payment_code'] ?? $payload['transaction_id'] ?? null;

        $this->service->handleWebhook($externalId, $status, $referenceNumber);

        return response()->json(['success' => true, 'message' => 'Webhook processed.']);
    }
}
