<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use App\Services\PaymentRequestService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OfficerPaymentController extends Controller
{
    public function __construct(protected PaymentRequestService $service)
    {
    }

    /**
     * GET /api/officer/payment-requests/{token}
     * Verifikasi QR yang dipindai petugas.
     */
    public function verify(Request $request, string $token)
    {
        try {
            $data = $this->service->verifyByToken($token);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'QR tidak dikenal. Payment request tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment request ditemukan.',
            'data' => $data,
        ]);
    }

    /**
     * POST /api/officer/payment-requests/{token}/complete
     * Selesaikan pembayaran di lapangan (cash/qris/va). Anti double payment.
     */
    public function complete(Request $request, string $token)
    {
        $request->validate([
            'payment_method' => 'required|string|in:cash,qris,va',
            'tendered_amount' => 'nullable|numeric|min:0',
            'change_amount' => 'nullable|numeric|min:0',
            'proof_url' => 'nullable|string|max:2048',
        ]);

        try {
            $result = $this->service->completeByOfficer($token, $request->input('payment_method'), [
                'officer_id' => $request->user()?->id,
                'tendered_amount' => $request->input('tendered_amount'),
                'change_amount' => $request->input('change_amount'),
                'proof_url' => $request->input('proof_url'),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Payment request tidak ditemukan.'], 404);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Officer payment failed', ['token' => $token, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Gagal memproses pembayaran. Silakan coba lagi.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil diselesaikan.',
            'data' => $result,
        ]);
    }

    /**
     * GET /api/officer/payment-requests
     * Riwayat payment request yang diproses petugas (untuk menu transaksi lapangan).
     */
    public function history(Request $request)
    {
        $query = PaymentRequest::with('taxpayer:id,name,npwpd')
            ->where('method', PaymentRequest::METHOD_OFFICER)
            ->whereNotNull('paid_at');

        if ($request->user()?->role === 'petugas') {
            $query->whereHas('payments', function ($q) use ($request) {
                $q->where('approved_by', $request->user()->id);
            });
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('reference_number', 'like', '%' . $request->search . '%')
                    ->orWhereHas('taxpayer', fn ($t) => $t->where('name', 'like', '%' . $request->search . '%'));
            });
        }

        $items = $query->orderByDesc('paid_at')
            ->limit(50)
            ->get()
            ->map(function (PaymentRequest $paymentRequest) {
                return [
                    'id' => $paymentRequest->id,
                    'reference_number' => $paymentRequest->reference_number,
                    'taxpayer_name' => $paymentRequest->taxpayer?->name,
                    'npwpd' => $paymentRequest->taxpayer?->npwpd,
                    'total_amount' => (float) $paymentRequest->total_amount,
                    'payment_method' => $paymentRequest->payments()->first()?->payment_method,
                    'paid_at' => $paymentRequest->paid_at?->toISOString(),
                ];
            });

        return response()->json(['success' => true, 'data' => $items]);
    }
}
