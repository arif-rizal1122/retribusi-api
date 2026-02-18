<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.wa_gateway.url', 'http://localhost:3001');
    }

    /**
     * Send a WhatsApp message
     * 
     * @param string $phone Must be in international format (e.g. 62812...)
     * @param string $message
     * @return bool
     */
    public function sendMessage(string $phone, string $message): bool
    {
        try {
            // Clean phone number
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            }

            $response = Http::post("{$this->baseUrl}/send-message", [
                'phone' => $phone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error("WA Gateway Error: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("WA Service Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check gateway status
     */
    public function getStatus(): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/status");
            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 'offline', 'error' => $e->getMessage()];
        }
    }
}
