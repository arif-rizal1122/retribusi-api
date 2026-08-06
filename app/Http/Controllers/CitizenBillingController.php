<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\Taxpayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CitizenBillingController extends Controller
{
    /**
     * GET /api/citizen/bills
     *
     * Autentikasi (token sanctum) → tagihan wajib pajak, paginated + enriched
     * (payment_options, active_payment_request, dll).
     * Legacy publik `?nik=...` → daftar tagihan berdasarkan NIK (untuk demo/verifikasi).
     */
    public function bills(Request $request)
    {
        $taxpayer = Auth::guard('sanctum')->user();

        if ($taxpayer instanceof Taxpayer) {
            return $this->authenticatedBills($request, $taxpayer);
        }

        if ($request->has('nik')) {
            return $this->legacyPublicBills($request);
        }

        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    /**
     * GET /api/citizen/payments/history
     */
    public function paymentHistory(Request $request)
    {
        $taxpayer = $request->user();

        if (!$taxpayer instanceof Taxpayer) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $perPage = (int) $request->get('per_page', 15);

        $payments = Payment::with(['bill', 'taxObject', 'retributionType'])
            ->where('taxpayer_id', $taxpayer->id)
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => collect($payments->items())->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'reference_number' => $payment->metadata['reference_number'] ?? null,
                'receipt_number' => $payment->metadata['reference_number'] ?? null,
                'payment_method' => $payment->payment_method,
                'channel' => $payment->payment_method === 'bri_va' ? 'BRI Virtual Account' : ($payment->payment_method === 'officer' ? 'Petugas' : strtoupper($payment->payment_method)),
                'amount' => (float) $payment->amount,
                'status' => $payment->status === 'success' ? 'success' : $payment->status,
                'status_label' => $payment->status === 'success' ? 'Lunas' : ucfirst($payment->status),
                'billing_period' => $payment->billing_period,
                'paid_at' => $payment->paid_at?->toISOString(),
                'created_at' => $payment->created_at?->toISOString(),
                'proof_url' => $payment->proof_url,
                'bill' => $payment->bill?->only(['id', 'bill_number', 'status', 'period', 'total_amount']),
                'tax_object' => $payment->taxObject?->only(['id', 'name']),
                'retribution_type' => $payment->retributionType?->only(['id', 'name', 'icon']),
            ])->values()->all(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    protected function authenticatedBills(Request $request, Taxpayer $taxpayer)
    {
        $bills = Bill::with(['retributionType', 'opd', 'taxObject', 'classification'])
            ->where('taxpayer_id', $taxpayer->id)
            ->orderByDesc('id')
            ->paginate((int) $request->get('per_page', 15));

        $activeRequests = PaymentRequest::where('taxpayer_id', $taxpayer->id)
            ->whereIn('status', [PaymentRequest::STATUS_PENDING, PaymentRequest::STATUS_PROCESSING_RECEIPT])
            ->get();

        return response()->json([
            'data' => collect($bills->items())->map(function (Bill $bill) use ($activeRequests) {
                $activeRequest = $activeRequests->first(function ($request) use ($bill) {
                    return in_array($bill->id, array_map('intval', $request->bill_ids ?? []));
                });

                return $this->billPayload($bill, $activeRequest);
            })->values()->all(),
            'meta' => [
                'current_page' => $bills->currentPage(),
                'last_page' => $bills->lastPage(),
                'per_page' => $bills->perPage(),
                'total' => $bills->total(),
            ],
        ]);
    }

    protected function legacyPublicBills(Request $request)
    {
        $bills = Bill::with(['retributionType', 'opd', 'taxObject', 'classification'])
            ->whereHas('taxpayer', fn ($q) => $q->where('nik', $request->input('nik')))
            ->latest()
            ->get();

        return response()->json([
            'data' => $bills->map(fn (Bill $bill) => $this->billPayload($bill))->values(),
        ]);
    }

    protected function billPayload(Bill $bill, ?PaymentRequest $activeRequest = null): array
    {
        $bankAccounts = $bill->classification?->bank_accounts ?? [];
        $adminFee = (float) ($bill->admin_fee ?? 0);
        $isPayable = in_array($bill->status, ['pending', 'overdue']);
        $expiresAt = $activeRequest?->expired_at?->toISOString();

        return [
            'id' => $bill->id,
            'bill_number' => $bill->bill_number,
            'amount' => (float) $bill->amount,
            'total_amount' => (float) $bill->total_amount,
            'admin_fee' => $adminFee,
            'penalty_amount' => (float) ($bill->penalty_amount ?? 0),
            'period' => $bill->period,
            'status' => $bill->status,
            'status_label' => match ($bill->status) {
                'overdue' => 'Jatuh tempo',
                'lunas' => 'Lunas',
                default => 'Menunggu pembayaran',
            },
            'can_pay' => $isPayable,
            'payment_options' => [
                'manual_transfer' => [
                    'available' => $isPayable,
                    'bank_accounts' => collect($bankAccounts)->map(function ($account) {
                        return [
                            'bank_name' => $account['bank_name'] ?? '',
                            'account_number' => $account['account_number'] ?? '',
                            'account_name' => $account['account_name'] ?? null,
                            'qr_image_url' => $account['qr_image_url'] ?? null,
                        ];
                    })->values()->all(),
                    'admin_fee' => $adminFee,
                    'total_amount' => (float) $bill->total_amount,
                    'expires_at' => $expiresAt,
                    'instructions' => $activeRequest?->instructions ?: [],
                ],
                'bri_va' => [
                    'available' => $isPayable,
                    'message' => null,
                ],
            ],
            'active_payment_request' => $activeRequest ? [
                'id' => (string) $activeRequest->id,
                'provider' => $activeRequest->provider ?: 'BRI',
                'method' => $activeRequest->method,
                'status' => $activeRequest->status,
                'status_label' => $activeRequest->status_label,
                'va_number' => $activeRequest->va_number,
                'expired_at' => $expiresAt,
                'total_amount' => (float) $activeRequest->total_amount,
                'can_refresh' => $activeRequest->can_refresh,
                'can_cancel' => $activeRequest->can_cancel,
            ] : null,
            'tax_object_id' => $bill->tax_object_id,
            'retribution_type' => $bill->retributionType?->only(['id', 'name', 'icon']),
            'tax_object' => $bill->taxObject?->only(['id', 'name']),
            'classification' => $bill->classification ? [
                'id' => $bill->classification->id,
                'name' => $bill->classification->name,
                'bank_accounts' => collect($bankAccounts)->values()->all(),
            ] : null,
        ];
    }
}
