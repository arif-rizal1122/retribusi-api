<?php

namespace App\Services;

use App\Models\PaymentGatewayLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentAuditService
{
    /**
     * Log an incoming/outgoing payment gateway communication
     */
    public function record(Request $request, array $response, string $billNumber = null): void
    {
        try {
            PaymentGatewayLog::create([
                'bill_number' => $billNumber ?? $request->bill_number,
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'payload_in' => $this->maskSensitiveData($request->all()),
                'payload_out' => $this->maskSensitiveData($response),
                'status_code' => $response['code'] ?? 200,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'signature_verified' => true // Only calls this if middleware passed
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record Payment Gateway Log:', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Mask sensitive fields in payloads (secrets, tokens, etc)
     */
    protected function maskSensitiveData(array $data): array
    {
        $sensitiveFields = ['secret', 'password', 'token', 'signature', 'client_secret'];
        
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $data[$key] = '********';
            } elseif (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            }
        }
        
        return $data;
    }
}
