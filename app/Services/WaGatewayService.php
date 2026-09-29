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
                'error' => 'WA Gateway service un-reachable',
            ];
        } catch (\Exception $e) {
            Log::warning('WA Gateway Connection Error: '.$e->getMessage());

            return [
                'status' => 'OFFLINE',
                'connected' => false,
                'user' => null,
                'qr' => null,
                'error' => 'Service Gateway offline atau tidak aktif',
            ];
        }
    }

    /**
     * Get QR Code Data URL
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
     * Send OTP via WhatsApp (Matches NOTIF-WRG-001 official template)
     */
    public function sendOtp(string $phone, string $otp): bool
    {
        try {
            $message = "*[M-PAD KOTA BAUBAU - KODE KEAMANAN]*\n\n"
                     . "Yth. Warga Kota Baubau,\n\n"
                     . "Berikut adalah Kode Keamanan (OTP) Anda untuk masuk ke sistem M-PAD:\n\n"
                     . "👉 *{$otp}* 👈\n\n"
                     . "Kode ini berlaku selama *5 menit*.\n\n"
                     . "⚠️ *PERINGATAN KEAMANAN:*\n\n"
                     . "JANGAN PERNAH membagikan kode ini kepada siapa pun, termasuk pihak yang mengaku sebagai petugas Bapenda atau Pemkot Baubau. Petugas resmi tidak pernah meminta kode OTP Anda.\n\n"
                     . "Sistem M-PAD Badan Pendapatan Daerah Kota Baubau.";

            $sent = $this->sendMessage($phone, $message, 'high');

            try {
                \App\Models\NotificationLog::create([
                    'phone' => $phone,
                    'channel' => 'whatsapp',
                    'type' => 'NOTIF-WRG-001',
                    'template_code' => 'NOTIF-WRG-001',
                    'reference_type' => 'auth_otp',
                    'message_preview' => mb_substr($message, 0, 500),
                    'status' => $sent ? 'success' : 'failed',
                    'sent_at' => $sent ? now() : null,
                    'delivered_at' => $sent ? now() : null,
                ]);
            } catch (\Throwable $logEx) {
                Log::warning('WaGatewayService: Failed to log OTP to notification_logs: '.$logEx->getMessage());
            }

            if (! $sent && app()->environment('local', 'testing')) {
                Log::info("[DEV WA OTP] Nomor: {$phone} | Kode OTP: {$otp}");

                return true;
            }

            return $sent;
        } catch (\Exception $e) {
            Log::error('WA Gateway Exception (OTP): '.$e->getMessage());
            if (app()->environment('local', 'testing')) {
                Log::info("[DEV WA OTP Fallback] Nomor: {$phone} | Kode OTP: {$otp}");

                return true;
            }

            return false;
        }
    }

    /**
     * Send general message via WhatsApp
     */
    public function sendMessage(string $phone, string $message, string $priority = 'high'): bool
    {
        try {
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '62'.substr($cleanPhone, 1);
            }

            $response = Http::timeout(20)->post("{$this->baseUrl}/send-message", [
                'number' => $cleanPhone,
                'message' => $message,
                'priority' => $priority,
            ]);

            if ($response->successful()) {
                Log::info("WhatsApp message sent to +{$cleanPhone} successfully (Priority: {$priority}).");

                return true;
            }

            Log::error("Failed to send WhatsApp message to +{$cleanPhone}: ".$response->body());

            return false;
        } catch (\Exception $e) {
            Log::error('WA Gateway Exception: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Logout / Disconnect current session
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
