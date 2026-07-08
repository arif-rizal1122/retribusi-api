<?php

namespace App\Services;

use App\Models\PaymentGatewayLog;
use App\Services\Payment\Snap\SensitivePaymentLogMasker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentAuditService
{
    public function __construct(private readonly SensitivePaymentLogMasker $masker)
    {
    }

    /**
     * Log an incoming/outgoing payment gateway communication
     */
    public function record(
        Request $request,
        array $response,
        ?string $billNumber = null,
        ?string $endpoint = null,
        ?int $statusCode = null
    ): void
    {
        try {
            PaymentGatewayLog::create([
                'bill_number' => $billNumber
                    ?? $request->input('bill_number')
                    ?? $request->input('customerNo')
                    ?? $request->input('virtualAccountNo'),
                'endpoint' => $endpoint ?? $request->path(),
                'method' => $request->method(),
                'payload_in' => $this->masker->mask(array_merge($request->headers->all(), $request->all())),
                'payload_out' => $this->masker->mask($response),
                'status_code' => $statusCode ?? $this->statusCodeFromResponse($response),
                'ip_address' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record Payment Gateway Log:', ['error' => $e->getMessage()]);
        }
    }

    private function statusCodeFromResponse(array $response): int
    {
        if (isset($response['code']) && is_numeric($response['code'])) {
            return (int) $response['code'];
        }

        if (isset($response['responseCode']) && is_string($response['responseCode'])) {
            return (int) substr($response['responseCode'], 0, 3);
        }

        return 200;
    }
}
