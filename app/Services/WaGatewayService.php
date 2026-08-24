<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaGatewayService
{
    /**
     * The WA Gateway URL base endpoint.
     */
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.wa_gateway.url', 'http://localhost:5001');
    }

    /**
     * Get WA Gateway Connection Status & QR Code
     *
     * @return array
     */
    public function getStatus(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/status");
            if ($response->successful()) {
                return $response->json();
            }
            return [
                'status' => 'OFFLINE',
                'connected' => false,
                'user' => null,
                'qr' => null,
                'error' => 'WA Gateway service un-reachable'
            ];
        } catch (\Exception $e) {
            Log::warning("WA Gateway Connection Error: " . $e->getMessage());
            return [
                'status' => 'OFFLINE',
                'connected' => false,
                'user' => null,
                'qr' => null,
                'error' => 'Service Gateway offline atau tidak aktif'
            ];
        }
    }

    /**
     * Get QR Code Data URL
     *
     * @return array
     */
    public function getQrCode(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/qr");
            if ($response->successful()) {
                return $response->json();
            }
            return ['status' => 'OFFLINE', 'qr' => null];
        } catch (\Exception $e) {
            return ['status' => 'OFFLINE', 'qr' => null];
        }
    }

    /**
     * Send OTP via WhatsApp
     *
     * @param string $phone
     * @param string $otp
     * @return bool
     */
    public function sendOtp(string $phone, string $otp): bool
    {
        try {
            $message = "Halo! Ini adalah kode OTP M-PAD Kota Baubau Anda: *$otp*\n\nKode ini bersifat rahasia. JANGAN BERIKAN kepada siapapun termasuk petugas Bapenda.\n\nBerlaku selama 5 menit.";
            return $this->sendMessage($phone, $message);
        } catch (\Exception $e) {
            Log::error("WA Gateway Exception (OTP): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send general message via WhatsApp
     *
     * @param string $phone
     * @param string $message
     * @return bool
     */
    public function sendMessage(string $phone, string $message): bool
    {
        try {
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '62' . substr($cleanPhone, 1);
            }

            $response = Http::timeout(10)->post("{$this->baseUrl}/send-message", [
                'number' => $cleanPhone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                Log::info("WhatsApp message sent to +{$cleanPhone} successfully.");
                return true;
            }

            Log::error("Failed to send WhatsApp message to +{$cleanPhone}: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("WA Gateway Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Logout / Disconnect current session
     *
     * @return array
     */
    public function logoutSession(): array
    {
        try {
            $response = Http::timeout(5)->post("{$this->baseUrl}/logout");
            if ($response->successful()) {
                return $response->json();
            }
            return ['success' => false, 'error' => 'Gagal memutus koneksi gateway'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
