<?php

namespace App\Http\Controllers\Api\V1\Bank;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;
use App\Models\PaymentGatewayLog;
use Illuminate\Support\Facades\Log;

class BankH2HController extends Controller
{
    protected $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    /**
     * Entry point for Bank Inquiry
     */
    public function inquiry(Request $request)
    {
        $payload = $request->all();
        $billNumber = $request->bill_number;

        try {
            $result = $this->paymentManager->inquiry($billNumber);
            
            $this->logActivity($request, $result);

            return response()->json($result, $result['code'] ?? 200);
        } catch (\Exception $e) {
            Log::error('H2H Inquiry Error:', ['msg' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Entry point for Bank Payment Notification
     */
    public function payment(Request $request)
    {
        $payload = $request->all();

        try {
            $result = $this->paymentManager->notify($payload);
            
            $this->logActivity($request, $result);

            return response()->json($result, $result['code'] ?? 200);
        } catch (\Exception $e) {
            Log::error('H2H Payment Notification Error:', ['msg' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Entry point for Bank Reversal
     */
    public function reversal(Request $request)
    {
        $payload = $request->all();

        try {
            $result = $this->paymentManager->reversal($payload);
            
            $this->logActivity($request, $result);

            return response()->json($result, $result['code'] ?? 200);
        } catch (\Exception $e) {
            Log::error('H2H Reversal Error:', ['msg' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Fetch H2H Activities for Admin Monitoring
     */
    public function logs(Request $request)
    {
        $query = PaymentGatewayLog::query()->latest();

        if ($request->has('bill_number')) {
            $query->where('bill_number', 'like', '%' . $request->bill_number . '%');
        }

        if ($request->has('status_code')) {
            $query->where('status_code', $request->status_code);
        }

        $perPage = $request->per_page ?? 20;
        $logs = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $logs
        ]);
    }

    /**
     * Logic for logging H2H activity to database
     */
    private function logActivity(Request $request, array $result)
    {
        try {
            \App\Models\PaymentGatewayLog::create([
                'bill_number' => $request->bill_number,
                'endpoint' => $request->fullUrl(),
                'method' => $request->method(),
                'payload_in' => $request->all(),
                'payload_out' => $result,
                'ip_address' => $request->ip(),
                'status_code' => $result['code'] ?? 200
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log H2H activity:', ['msg' => $e->getMessage()]);
        }
    }
}
