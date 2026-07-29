<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\WaGatewayService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WaGatewayController extends Controller
{
    protected WaGatewayService $waGatewayService;

    public function __construct(WaGatewayService $waGatewayService)
    {
        $this->waGatewayService = $waGatewayService;
    }

    /**
     * Get WA Gateway status & active session / QR
     */
    public function status(): JsonResponse
    {
        $status = $this->waGatewayService->getStatus();
        return response()->json($status);
    }

    /**
     * Get QR Code image data URL
     */
    public function qr(): JsonResponse
    {
        $qrData = $this->waGatewayService->getQrCode();
        return response()->json($qrData);
    }

    /**
     * Send test message via WA Gateway
     */
    public function sendTest(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
            'type' => 'nullable|string|in:custom,otp,billing'
        ]);

        $phone = $request->input('phone');
        $message = $request->input('message');
        $type = $request->input('type', 'custom');

        if ($type === 'otp') {
            $otpCode = rand(100000, 999999);
            $success = $this->waGatewayService->sendOtp($phone, (string)$otpCode);
            $sentMessage = "OTP Code: {$otpCode}";
        } else {
            $success = $this->waGatewayService->sendMessage($phone, $message);
            $sentMessage = $message;
        }

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Pesan WhatsApp berhasil dikirim',
                'detail' => $sentMessage
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengirim pesan WhatsApp. Pastikan WA Gateway terhubung.'
        ], 500);
    }

    /**
     * Logout / disconnect current WhatsApp session
     */
    public function logout(): JsonResponse
    {
        $result = $this->waGatewayService->logoutSession();
        return response()->json($result);
    }
}
