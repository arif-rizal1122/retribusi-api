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
        $this->baseUrl = config('services.wa_gateway.url', 'http://localhost:3001');
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
            
            // Format phone number to start with 62
            if (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            }
            
            $response = Http::timeout(10)->post("{$this->baseUrl}/send-message", [
                'number' => $phone . '@s.whatsapp.net',
                'message' => $message,
            ]);

            if ($response->successful()) {
                Log::info("OTP sent to {$phone} successfully.");
                return true;
            }

            Log::error("Failed to send OTP to {$phone}. WA Gateway returned: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("WA Gateway Exception: " . $e->getMessage());
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
            if (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            }

            $response = Http::timeout(10)->post("{$this->baseUrl}/send-message", [
                'number' => $phone . '@s.whatsapp.net',
                'message' => $message,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("WA Gateway Exception: " . $e->getMessage());
            return false;
        }
    }
}
