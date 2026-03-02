<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Billing;
use Illuminate\Support\Facades\Log;

class PaymentGatewayController extends Controller
{
    /**
     * Mock generating a payment token/link/QRIS
     */
    public function generatePayment(Request $request)
    {
        $request->validate([
            'billing_id' => 'required|exists:billings,id',
            'method' => 'required|in:qris,va_bca,va_mandiri,va_bri'
        ]);

        $billing = Billing::findOrFail($request->billing_id);

        if ($billing->status === 'lunas') {
            return response()->json(['message' => 'Tagihan sudah lunas.'], 400);
        }

        // Mock response data
        $responseData = [
            'transaction_id' => 'TRX-' . time(),
            'order_id' => $billing->bill_number,
            'gross_amount' => $billing->total_amount ?? $billing->amount,
            'payment_type' => $request->method,
        ];

        if ($request->method === 'qris') {
            $responseData['qr_string'] = '00020101021126670016COM.GO-JEK.WWW01189360091431039864220215ID10200210080643...'; // Dummy QRIS
            $responseData['qr_image_url'] = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=DUMMY_QRIS';
        } else {
            $responseData['va_number'] = '88000' . str_pad($billing->taxpayer_id ?? rand(1000, 9999), 10, '0', STR_PAD_LEFT);
        }

        return response()->json([
            'message' => 'Payment generated successfully',
            'data' => $responseData
        ]);
    }

    /**
     * Mock Webhook receiver (e.g., from Midtrans)
     */
    public function webhook(Request $request)
    {
        // Example Payload
        // {
        //   "transaction_time": "2024-05-20 15:12:10",
        //   "transaction_status": "settlement",
        //   "order_id": "SPTPD-20240520-XXXX",
        //   "gross_amount": "100000.00"
        // }

        $payload = $request->all();
        
        Log::info('Payment Webhook Received:', $payload);

        if (!isset($payload['order_id']) || !isset($payload['transaction_status'])) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        if ($payload['transaction_status'] === 'settlement' || $payload['transaction_status'] === 'capture') {
            $billing = Billing::where('bill_number', $payload['order_id'])->first();

            if ($billing && $billing->status !== 'lunas') {
                $billing->status = 'lunas';
                // You could also create a Payment record here if the DB structure supports it
                $billing->save();

                Log::info("Billing {$billing->bill_number} marked as LUNAS via webhook.");
                return response()->json(['message' => 'Payment processed, billing updated to lunas.']);
            }
        }

        return response()->json(['message' => 'Webhook received, no action taken.']);
    }
}
