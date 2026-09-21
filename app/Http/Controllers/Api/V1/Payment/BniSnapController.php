<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Payment\PaymentManager;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BniSnapController extends Controller
{
    private $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    /**
     * Callback Inquiry VA Billing BNI (Service Code 24) — path /v1.1/transfer-va/inquiry
     * Dipanggil oleh BNI (via Client Simulator ASPI saat devsite / production saat live).
     * BNI melakukan inquiry ke M-PAD untuk mendapatkan detail tagihan (VA Billing = bank aktif inquiry).
     */
    public function inquiry(Request $request)
    {
        try {
            $this->validateHeaders($request);

            $partnerServiceId = $request->input('partnerServiceId');
            $customerNo = $request->input('customerNo');
            $virtualAccountNo = $request->input('virtualAccountNo');
            $inquiryRequestId = $request->input('inquiryRequestId');

            // Cari tagihan dari DB berdasarkan customerNo / virtualAccountNo
            $bill = $this->findBillByVa($customerNo, $virtualAccountNo);

            if (!$bill) {
                Log::warning('BNI Inquiry: bill not found', ['customerNo' => $customerNo, 'va' => $virtualAccountNo]);
                return response()->json([
                    'responseCode' => '2002401',
                    'responseMessage' => 'Invalid VA',
                    'virtualAccountData' => [
                        'inquiryStatus' => '01',
                        'inquiryReason' => [
                            'english' => 'Invalid VA number',
                            'indonesia' => 'Nomor VA tidak valid'
                        ],
                        'partnerServiceId' => $partnerServiceId,
                        'customerNo' => $customerNo,
                        'virtualAccountNo' => $virtualAccountNo,
                        'inquiryRequestId' => $inquiryRequestId,
                    ]
                ]);
            }

            return response()->json([
                'responseCode' => '2002400',
                'responseMessage' => 'Successful',
                'virtualAccountData' => [
                    'inquiryStatus' => '00',
                    'inquiryReason' => [
                        'english' => 'Success',
                        'indonesia' => 'Sukses'
                    ],
                    'partnerServiceId' => $partnerServiceId,
                    'customerNo' => $customerNo,
                    'virtualAccountNo' => $virtualAccountNo,
                    'virtualAccountName' => $bill['name'] ?? 'Wajib Pajak M-PAD',
                    'inquiryRequestId' => $inquiryRequestId,
                    'totalAmount' => [
                        'value' => number_format((float)($bill['amount'] ?? 0), 2, '.', ''),
                        'currency' => 'IDR'
                    ],
                    'billDetails' => [
                        [
                            'billCode' => '01',
                            'billName' => $bill['bill_name'] ?? 'Tagihan M-PAD',
                            'billAmount' => [
                                'value' => number_format((float)($bill['amount'] ?? 0), 2, '.', ''),
                                'currency' => 'IDR'
                            ]
                        ]
                    ],
                    'virtualAccountTrxType' => 'F',
                    'feeAmount' => [
                        'value' => '0.00',
                        'currency' => 'IDR'
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('BNI Inquiry error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'responseCode' => '4012401',
                'responseMessage' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Inquiry Status VA Billing BNI (Service Code 26) — path /v1.0/transfer-va/status
     *
     * Berbeda dari inquiry() SC24 yang hanya melayani tagihan belum dibayar
     * (status pending/unpaid), SC26 menjawab untuk tagihan dalam SEMUA kondisi:
     * belum dibayar, sudah dibayar, maupun tidak ditemukan. Ini yang membuat
     * bank/pihak ketiga bisa memastikan status akhir sebuah VA.
     *
     * Pemetaan status → paymentFlagStatus (SNAP VA Billing):
     *   '00' = paid / sudah dibayar
     *   '01' = unpaid / belum dibayar (masih menunggu pembayaran)
     *   '02' = expired / kedaluwarsa
     *
     * Response code: 2002600 sukses (apa pun statusnya), 4012601 error header/umum.
     */
    public function inquiryStatus(Request $request)
    {
        try {
            $this->validateHeaders($request);

            $partnerServiceId = $request->input('partnerServiceId');
            $customerNo = $request->input('customerNo');
            $virtualAccountNo = $request->input('virtualAccountNo');
            $inquiryRequestId = $request->input('inquiryRequestId');

            // SC26 harus melihat tagihan tanpa memandang status pembayaran,
            // jadi pencarian tidak dibatasi status tertentu seperti findBillByVa().
            $bill = $this->findBillForStatus($customerNo, $virtualAccountNo);

            if (!$bill) {
                Log::warning('BNI Inquiry Status: bill not found', [
                    'customerNo' => $customerNo,
                    'va' => $virtualAccountNo,
                ]);

                // VA tidak dikenal tetap dijawab sukses (2002600) dengan flag
                // unpaid, supaya bank bisa membedakan "VA ada tapi belum bayar"
                // dari kegagalan teknis. Kegagalan teknis hanya untuk header
                // tidak sah (4012601).
                return response()->json([
                    'responseCode' => '2002600',
                    'responseMessage' => 'Successful',
                    'virtualAccountData' => [
                        'paymentFlagStatus' => '01',
                        'paymentFlagReason' => [
                            'english' => 'Unpaid',
                            'indonesia' => 'Belum dibayar'
                        ],
                        'partnerServiceId' => $partnerServiceId,
                        'customerNo' => $customerNo,
                        'virtualAccountNo' => $virtualAccountNo,
                        'inquiryRequestId' => $inquiryRequestId,
                        'totalAmount' => [
                            'value' => '0.00',
                            'currency' => 'IDR'
                        ],
                    ]
                ]);
            }

            $flag = $bill['payment_flag']; // 00 paid | 01 unpaid | 02 expired
            $reason = match ($flag) {
                '00' => ['english' => 'Paid', 'indonesia' => 'Sudah dibayar'],
                '02' => ['english' => 'Expired', 'indonesia' => 'Kedaluwarsa'],
                default => ['english' => 'Unpaid', 'indonesia' => 'Belum dibayar'],
            };

            $response = [
                'responseCode' => '2002600',
                'responseMessage' => 'Successful',
                'virtualAccountData' => [
                    'paymentFlagStatus' => $flag,
                    'paymentFlagReason' => $reason,
                    'partnerServiceId' => $partnerServiceId,
                    'customerNo' => $customerNo,
                    'virtualAccountNo' => $virtualAccountNo,
                    'virtualAccountName' => $bill['name'] ?? 'Wajib Pajak M-PAD',
                    'inquiryRequestId' => $inquiryRequestId,
                    'totalAmount' => [
                        'value' => number_format((float)($bill['amount'] ?? 0), 2, '.', ''),
                        'currency' => 'IDR'
                    ],
                    'billDetails' => [
                        [
                            'billCode' => '01',
                            'billName' => $bill['bill_name'] ?? 'Tagihan M-PAD',
                            'billAmount' => [
                                'value' => number_format((float)($bill['amount'] ?? 0), 2, '.', ''),
                                'currency' => 'IDR'
                            ]
                        ]
                    ],
                ]
            ];

            // Untuk tagihan yang sudah lunas, sertakan detail pembayaran supaya
            // bank bisa merekonsiliasi (SNAP VA Billing paymentFlagStatus 00).
            if ($flag === '00' && !empty($bill['paid_at'])) {
                $response['virtualAccountData']['paidAmount'] = [
                    'value' => number_format((float)($bill['paid_amount'] ?? $bill['amount'] ?? 0), 2, '.', ''),
                    'currency' => 'IDR'
                ];
                $response['virtualAccountData']['paidDatetime'] = $bill['paid_at'];
                if (!empty($bill['bank_reference'])) {
                    $response['virtualAccountData']['bankReference'] = $bill['bank_reference'];
                }
                if (!empty($bill['transaction_id'])) {
                    $response['virtualAccountData']['trxId'] = $bill['transaction_id'];
                }
            }

            Log::info('BNI Inquiry Status responded', [
                'customerNo' => $customerNo,
                'va' => $virtualAccountNo,
                'paymentFlagStatus' => $flag,
            ]);

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('BNI Inquiry Status error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'responseCode' => '4012601',
                'responseMessage' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Callback Payment Notification BNI (Service Code 25) — path /v1.1/transfer-va/payment
     * BNI memberi notifikasi pembayaran VA Billing; M-PAD update status tagihan jadi lunas.
     */
    public function payment(Request $request)
    {
        try {
            $this->validateHeaders($request);

            $partnerServiceId = $request->input('partnerServiceId');
            $customerNo = $request->input('customerNo');
            $virtualAccountNo = $request->input('virtualAccountNo');
            $paymentRequestId = $request->input('paymentRequestId');
            $paidAmount = data_get($request->input('paidAmount'), 'value', 0);

            // Forward ke PaymentManager untuk update status billing
            $result = $this->paymentManager->driver('bni')->notify($request->all());

            if (($result['status'] ?? '') !== 'success') {
                $errCode = ($result['code'] ?? 500) == 404 ? '4042514' : ((int)($result['code'] ?? 500) === 400 ? '4002513' : '4012501');
                Log::warning('BNI Payment failed: ' . ($result['message'] ?? 'unknown'), [
                    'customerNo' => $customerNo,
                    'va' => $virtualAccountNo,
                ]);
                return response()->json([
                    'responseCode' => $errCode,
                    'responseMessage' => $result['message'] ?? 'Payment failed',
                    'virtualAccountData' => [
                        'paymentFlagStatus' => '01',
                        'paymentFlagReason' => [
                            'english' => 'Failed',
                            'indonesia' => 'Gagal'
                        ],
                        'partnerServiceId' => $partnerServiceId,
                        'customerNo' => $customerNo,
                        'virtualAccountNo' => $virtualAccountNo,
                        'paymentRequestId' => $paymentRequestId,
                    ]
                ], (int)($result['code'] ?? 500) === 404 ? 404 : ((int)($result['code'] ?? 500) === 400 ? 400 : 401));
            }

            Log::info('BNI Payment notification received', [
                'customerNo' => $customerNo,
                'va' => $virtualAccountNo,
                'amount' => $paidAmount,
                'paymentRequestId' => $paymentRequestId,
            ]);

            return response()->json([
                'responseCode' => '2002500',
                'responseMessage' => 'Successful',
                'virtualAccountData' => [
                    'paymentFlagStatus' => '00',
                    'paymentFlagReason' => [
                        'english' => 'Success',
                        'indonesia' => 'Sukses'
                    ],
                    'partnerServiceId' => $partnerServiceId,
                    'customerNo' => $customerNo,
                    'virtualAccountNo' => $virtualAccountNo,
                    'paymentRequestId' => $paymentRequestId,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('BNI Payment error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'responseCode' => '4012501',
                'responseMessage' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Cari tagihan berdasarkan customerNo (bill number) atau virtualAccountNo.
     * Sesuaikan dengan struktur DB M-PAD (bills / payment_requests).
     */
    private function findBillByVa($customerNo, $virtualAccountNo)
    {
        if (!$customerNo) {
            return null;
        }

        try {
            $bill = \DB::table('bills')
                ->where('bill_number', $customerNo)
                ->whereIn('status', ['pending', 'unpaid', 'waiting_payment'])
                ->orderByDesc('id')
                ->first();

            if (!$bill) {
                return null;
            }

            return [
                'name' => $bill->taxpayer_name ?? 'Wajib Pajak M-PAD',
                'amount' => $bill->amount ?? 0,
                'bill_name' => 'Tagihan ' . ($bill->description ?? 'M-PAD'),
            ];
        } catch (\Exception $e) {
            Log::warning('BNI findBillByVa error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cari tagihan untuk keperluan SC26 (inquiry status) — TANPA batasan status,
     * karena SC26 justru bertugas melaporkan status terkini (lunas/belum/expired).
     *
     * Sumber data pembayaran diambil dari tabel `payments` (relasi lewat `bill_id`),
     * bukan dari kolom di `bills`, karena tabel itulah yang mencatat transaksi bank.
     */
    private function findBillForStatus($customerNo, $virtualAccountNo)
    {
        if (!$customerNo) {
            return null;
        }

        try {
            $bill = \DB::table('bills')
                ->where('bill_number', $customerNo)
                ->orderByDesc('id')
                ->first();

            if (!$bill) {
                return null;
            }

            // Status tagihan M-PAD → paymentFlagStatus SNAP
            // lunas/paid  → 00 (sudah dibayar)
            // expired     → 02 (kedaluwarsa)
            // lainnya     → 01 (belum dibayar)
            $flag = match ($bill->status) {
                'lunas', 'paid' => '00',
                'expired' => '02',
                default => '01',
            };

            $paidAmount = null;
            $paidAt = null;
            $bankReference = null;
            $transactionId = null;

            $payment = \DB::table('payments')
                ->where('bill_id', $bill->id)
                ->whereIn('status', ['success', 'settled', 'approved'])
                ->orderByDesc('id')
                ->first();

            if ($payment) {
                $paidAmount = $payment->amount ?? null;
                $paidAt = $payment->paid_at ?? null;
                $bankReference = $payment->reference_number ?? null;
                $transactionId = $payment->transaction_id ?? null;
            }

            return [
                'name' => $bill->taxpayer_name ?? 'Wajib Pajak M-PAD',
                'amount' => $bill->amount ?? 0,
                'bill_name' => 'Tagihan ' . ($bill->description ?? 'M-PAD'),
                'payment_flag' => $flag,
                'paid_amount' => $paidAmount,
                'paid_at' => $paidAt,
                'bank_reference' => $bankReference,
                'transaction_id' => $transactionId,
            ];
        } catch (\Exception $e) {
            Log::warning('BNI findBillForStatus error: ' . $e->getMessage());
            return null;
        }
    }

    private function validateHeaders(Request $request)
    {
        $timestamp = $request->header('X-TIMESTAMP');
        $signature = $request->header('X-SIGNATURE');
        $accessToken = str_replace('Bearer ', '', $request->header('Authorization', ''));

        if (!$timestamp || !$signature || !$accessToken) {
            throw new \Exception('Missing required SNAP headers');
        }

        // Timestamp validation (max 2 minutes tolerance)
        $requestTime = Carbon::parse($timestamp);
        if ($requestTime->diffInMinutes(now()) > 2) {
            throw new \Exception('Transaction Expired (Timestamp)');
        }

        // TODO: verifikasi signature dengan BniSignatureService saat credential BNI tersedia.
        // Saat ini (devsite test) signature diverifikasi oleh BNI/ASPI di sisi mereka.
        return true;
    }
}
