<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Payment\Utils\BtnSignatureService;
use App\Services\Payment\PaymentManager;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BtnSnapController extends Controller
{
    private $signatureService;
    private $paymentManager;

    public function __construct(BtnSignatureService $signatureService, PaymentManager $paymentManager)
    {
        $this->signatureService = $signatureService;
        $this->paymentManager = $paymentManager;
    }

    public function inquiry(Request $request)
    {
        try {
            $this->validateHeaders($request);
            // In a real BTN H2H, inquiry provides billing amount. 
            // We just return success since Create VA already handled it.
            return response()->json([
                'responseCode' => '2002400',
                'responseMessage' => 'Successful',
                'virtualAccountData' => [
                    'inquiryStatus' => '00',
                    'inquiryReason' => [
                        'english' => 'Success',
                        'indonesia' => 'Sukses'
                    ],
                    'partnerServiceId' => $request->partnerServiceId,
                    'customerNo' => $request->customerNo,
                    'virtualAccountNo' => $request->virtualAccountNo,
                    'virtualAccountName' => 'Wajib Pajak', // Need to fetch from DB in real impl
                    'inquiryRequestId' => $request->inquiryRequestId,
                    'totalAmount' => [
                        'value' => "0.00",
                        'currency' => "IDR"
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('BTN Inquiry error: ' . $e->getMessage());
            return response()->json([
                'responseCode' => '4012401', // Example error
                'responseMessage' => $e->getMessage()
            ], 401);
        }
    }

    public function payment(Request $request)
    {
        try {
            $this->validateHeaders($request);

            // Forward to PaymentManager to update billing status
            $this->paymentManager->driver('btn')->notify($request->all());

            return response()->json([
                'responseCode' => '2002500',
                'responseMessage' => 'Successful',
                'virtualAccountData' => [
                    'paymentFlagStatus' => '00',
                    'paymentFlagReason' => [
                        'english' => 'Success',
                        'indonesia' => 'Sukses'
                    ],
                    'partnerServiceId' => $request->partnerServiceId,
                    'customerNo' => $request->customerNo,
                    'virtualAccountNo' => $request->virtualAccountNo,
                    'paymentRequestId' => $request->paymentRequestId
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('BTN Payment error: ' . $e->getMessage());
            return response()->json([
                'responseCode' => '4012501',
                'responseMessage' => $e->getMessage()
            ], 401);
        }
    }

    private function validateHeaders(Request $request)
    {
        $timestamp = $request->header('X-TIMESTAMP');
        $signature = $request->header('X-SIGNATURE');
        
        // Use authorization bearer token as the access token parameter for signature validation
        $accessToken = str_replace('Bearer ', '', $request->header('Authorization', ''));

        if (!$timestamp || !$signature || !$accessToken) {
            throw new \Exception('Missing required SNAP headers');
        }

        // Timestamp validation (max 2 minutes tolerance)
        $requestTime = Carbon::parse($timestamp);
        if ($requestTime->diffInMinutes(now()) > 2) {
            throw new \Exception('Transaction Expired (Timestamp)');
        }

        $isValid = $this->signatureService->verifyHmacSignature(
            $request->method(),
            '/' . ltrim($request->path(), '/'),
            $accessToken,
            $request->getContent(),
            $timestamp,
            $signature
        );

        if (!$isValid) {
            throw new \Exception('Access Token Invalid / Signature Mismatch');
        }
    }
}
