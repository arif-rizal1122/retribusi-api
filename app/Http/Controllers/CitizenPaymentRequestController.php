<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use App\Models\Taxpayer;
use App\Services\PaymentRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CitizenPaymentRequestController extends Controller
{
    public function __construct(protected PaymentRequestService $service)
    {
    }

    /**
     * POST /api/citizen/payment-requests
     * Buat Payment Request baru (VA/QRIS/offline petugas).
     */
    public function store(Request $request)
    {
        $request->validate([
            'bill_ids' => 'required|array|min:1',
            'bill_ids.*' => 'integer',
            'method' => 'required|string|in:bri_va,qris,officer',
        ]);

        $taxpayer = $request->user();

        if (!$taxpayer instanceof Taxpayer) {
            return response()->json(['message' => 'Hanya akun wajib pajak yang dapat membuat payment request.'], 403);
        }

        $paymentRequest = $this->service->createForCitizen(
            $taxpayer,
            $request->input('bill_ids'),
            $request->input('method'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment request berhasil dibuat.',
            'data' => $paymentRequest->toApiArray(),
        ], 201);
    }

    /**
     * GET /api/citizen/payment-requests/{id}
     */
    public function show(Request $request, $id)
    {
        $taxpayer = $request->user();

        if (!$taxpayer instanceof Taxpayer) {
            return response()->json(['message' => 'Hanya akun wajib pajak.'], 403);
        }

        $paymentRequest = $this->service->getForCitizen($taxpayer, (int) $id);

        return response()->json([
            'success' => true,
            'data' => $paymentRequest->toApiArray(),
        ]);
    }

    /**
     * POST /api/citizen/payment-requests/{id}/refresh
     */
    public function refresh(Request $request, $id)
    {
        $taxpayer = $request->user();

        if (!$taxpayer instanceof Taxpayer) {
            return response()->json(['message' => 'Hanya akun wajib pajak.'], 403);
        }

        $paymentRequest = $this->service->refreshForCitizen($taxpayer, (int) $id);

        return response()->json([
            'success' => true,
            'data' => $paymentRequest->toApiArray(),
        ]);
    }

    /**
     * POST /api/citizen/payment-requests/{id}/cancel
     */
    public function cancel(Request $request, $id)
    {
        $taxpayer = $request->user();

        if (!$taxpayer instanceof Taxpayer) {
            return response()->json(['message' => 'Hanya akun wajib pajak.'], 403);
        }

        try {
            $paymentRequest = $this->service->cancelForCitizen($taxpayer, (int) $id);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment request dibatalkan.',
            'data' => $paymentRequest->toApiArray(),
        ]);
    }

    /**
     * GET /api/citizen/payment-requests/{id}/qr
     * Gambar QR untuk metode petugas. QR dihasilkan server dari qr_payload,
     * sesuai prinsip API sebagai sumber kebenaran (tidak dibuat di frontend).
     */
    public function qrImage(Request $request, $id)
    {
        $taxpayer = $request->user();

        if (!$taxpayer instanceof Taxpayer) {
            return response()->json(['message' => 'Hanya akun wajib pajak.'], 403);
        }

        $paymentRequest = $this->service->getForCitizen($taxpayer, (int) $id);

        if ($paymentRequest->method !== PaymentRequest::METHOD_OFFICER) {
            return response()->json(['message' => 'QR hanya tersedia untuk metode bayar melalui petugas.'], 422);
        }

        $payload = $paymentRequest->qr_payload ?: 'MPAD://pay/' . $paymentRequest->token;

        try {
            $svg = (string) QrCode::format('svg')->size(240)->margin(1)->generate($payload);
        } catch (\Throwable $e) {
            Log::warning('QR image generation failed.', [
                'payment_request_id' => $paymentRequest->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Gagal membuat gambar QR.'], 500);
        }

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * GET /api/citizen/payment-requests
     * Daftar payment request milik wajib pajak.
     */
    public function index(Request $request)
    {
        $taxpayer = $request->user();

        if (!$taxpayer instanceof Taxpayer) {
            return response()->json(['message' => 'Hanya akun wajib pajak.'], 403);
        }

        $items = \App\Models\PaymentRequest::where('taxpayer_id', $taxpayer->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->each(function ($paymentRequest) {
                app(PaymentRequestService::class)->applyExpiry($paymentRequest);
            })
            ->map(fn ($paymentRequest) => $paymentRequest->toApiArray());

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }
}
