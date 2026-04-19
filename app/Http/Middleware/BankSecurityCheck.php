<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class BankSecurityCheck
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $driver = config('payment.default', 'sultra');
        $config = config("payment.drivers.{$driver}");

        // 1. IP Whitelisting
        $allowedIps = $config['allowed_ips'] ?? [];
        if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps) && $request->ip() !== '127.0.0.1') {
            Log::warning('H2H Unauthorized IP:', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Unauthorized IP'], 403);
        }

        // 2. Signature Verification
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');
        $billNumber = $request->bill_number;
        $amount = $request->amount_paid ?? $request->amount ?? 0;
        
        if (!$signature || !$timestamp) {
            return response()->json(['message' => 'Missing security headers'], 401);
        }

        $secret = $config['secret'] ?? '';
        $expectedSignature = hash_hmac('sha256', $billNumber . $timestamp . $amount, $secret);

        if ($signature !== $expectedSignature) {
            Log::warning('H2H Invalid Signature:', [
                'received' => $signature,
                'expected' => $expectedSignature,
                'data' => $billNumber . $timestamp . $amount
            ]);
            // For production, return 401. For testing, maybe more detail.
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
