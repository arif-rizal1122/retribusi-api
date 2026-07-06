<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentGatewayLog;
use App\Services\Payment\Drivers\BankBRIDriver;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SnapBIController extends Controller
{
    protected BankBRIDriver $briDriver;

    public function __construct(BankBRIDriver $briDriver)
    {
        $this->briDriver = $briDriver;
    }

    private function validateSignature(Request $request)
    {
        // Placeholder for SNAP BI Asymmetric/Symmetric signature validation
        // Using X-SIGNATURE, X-TIMESTAMP, X-PARTNER-ID, X-CLIENT-KEY
        $signature = $request->header('X-SIGNATURE');
        $timestamp = $request->header('X-TIMESTAMP');
        
        // For Sandbox / Development, we bypass if signature is not strictly enforced,
        // but in production, we MUST validate according to SNAP standard.
        if (!$signature) {
            return false;
        }
        
        return true;
    }

    private function logRequest(Request $request, $endpoint, $status, $response)
    {
        PaymentGatewayLog::create([
            'bill_number' => $request->input('customerNo') ?? $request->input('bill_number') ?? null,
            'endpoint' => $endpoint,
            'method' => $request->method(),
            'payload_in' => $request->all(),
            'payload_out' => $response,
            'status_code' => is_array($response) ? ($response['responseCode'] ?? '200') : '200',
            'ip_address' => $request->ip(),
        ]);
    }

    public function getAccessToken(Request $request)
    {
        // standard B2B Auth Token
        $clientId = $request->header('X-CLIENT-KEY') ?? $request->input('grantType');
        
        // Return dummy success for Sandbox Open Firewall
        $response = [
            'responseCode' => '2007300',
            'responseMessage' => 'Successful',
            'accessToken' => Str::random(40),
            'tokenType' => 'Bearer',
            'expiresIn' => '900',
        ];
        
        $this->logRequest($request, '/snap/v1.1/access-token/b2b', 'SUCCESS', $response);
        
        return response()->json($response, 200);
    }

    public function qrisNotify(Request $request)
    {
        if (!$this->validateSignature($request)) {
            return response()->json(['responseCode' => '4017300', 'responseMessage' => 'Unauthorized. Signature invalid.'], 401);
        }

        $payload = $request->all();
        $result = $this->briDriver->notify($payload);
        
        $response = [
            'responseCode' => '2002500',
            'responseMessage' => 'Successful',
        ];

        $this->logRequest($request, '/snap/v1.1/qr/qr-mpm-notify', 'SUCCESS', $response);
        return response()->json($response, 200);
    }

    public function brivaInquiry(Request $request)
    {
        if (!$this->validateSignature($request)) {
            return response()->json(['responseCode' => '4017300', 'responseMessage' => 'Unauthorized.'], 401);
        }

        $partnerServiceId = $request->input('partnerServiceId');
        $customerNo = $request->input('customerNo');
        $virtualAccountNo = $request->input('virtualAccountNo', $partnerServiceId . $customerNo);
        
        $result = $this->briDriver->inquiry($virtualAccountNo);
        
        if ($result['status'] === 'error') {
            $response = [
                'responseCode' => '4042412',
                'responseMessage' => 'Bill not found / Invalid VA',
                'virtualAccountData' => null
            ];
            $this->logRequest($request, '/snap/v1.0/transfer-va/inquiry', 'ERROR', $response);
            return response()->json($response, 404);
        }

        // Mock success SNAP response
        $response = [
            'responseCode' => '2002400',
            'responseMessage' => 'Successful',
            'virtualAccountData' => [
                'partnerServiceId' => $partnerServiceId,
                'customerNo' => $customerNo,
                'virtualAccountNo' => $virtualAccountNo,
                'virtualAccountName' => $result['data']['taxpayer_name'] ?? 'Wajib Pajak',
                'virtualAccountEmail' => '',
                'virtualAccountPhone' => '',
                'totalAmount' => [
                    'value' => number_format($result['data']['total_amount'] ?? 0, 2, '.', ''),
                    'currency' => 'IDR'
                ],
                'billDetails' => [
                    [
                        'billDescription' => 'Tagihan Retribusi',
                        'billAmount' => [
                            'value' => number_format($result['data']['total_amount'] ?? 0, 2, '.', ''),
                            'currency' => 'IDR'
                        ]
                    ]
                ],
                'inquiryStatus' => '00',
                'inquiryReason' => 'Success'
            ]
        ];

        $this->logRequest($request, '/snap/v1.0/transfer-va/inquiry', 'SUCCESS', $response);
        return response()->json($response, 200);
    }

    public function brivaPayment(Request $request)
    {
        if (!$this->validateSignature($request)) {
            return response()->json(['responseCode' => '4017300', 'responseMessage' => 'Unauthorized.'], 401);
        }

        $payload = $request->all();
        $result = $this->briDriver->notify($payload);

        $response = [
            'responseCode' => '2002400',
            'responseMessage' => 'Successful',
            'virtualAccountData' => [
                'partnerServiceId' => $request->input('partnerServiceId'),
                'customerNo' => $request->input('customerNo'),
                'virtualAccountNo' => $request->input('virtualAccountNo'),
                'virtualAccountName' => $request->input('virtualAccountName'),
                'paymentFlagStatus' => '00',
                'paymentFlagReason' => 'Success'
            ]
        ];

        $this->logRequest($request, '/snap/v1.0/transfer-va/payment', 'SUCCESS', $response);
        return response()->json($response, 200);
    }
}
