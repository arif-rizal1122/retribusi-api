<?php

namespace App\Services;

use App\Models\AftTransaction;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\Taxpayer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * PaymentRequestService
 *
 * Fondasi alur "Scan QR Pembayaran via Petugas":
 * 1. WP membuat Payment Request (QR).
 * 2. Petugas memindai QR → verifikasi via token.
 * 3. Pembayaran diselesaikan (cash/qris/va) → anti double payment.
 * 4. AFT berjalan otomatis apabila WP telah mengaktifkan AFT.
 */
class PaymentRequestService
{
    public const TTL_MINUTES = 1440; // 24 jam

    public function __construct(
        protected PaymentGatewayService $gateway,
        protected AftSettlementService $aftService,
    ) {
    }

    /**
     * Buat Payment Request milik citizen (taxpayer).
     */
    public function createForCitizen(Taxpayer $taxpayer, array $billIds, string $method): PaymentRequest
    {
        if (!in_array($method, [PaymentRequest::METHOD_BRI_VA, PaymentRequest::METHOD_QRIS, PaymentRequest::METHOD_OFFICER])) {
            throw ValidationException::withMessages([
                'method' => 'Metode pembayaran tidak didukung.',
            ]);
        }

        $billIds = array_values(array_unique(array_map('intval', $billIds)));
        if (empty($billIds)) {
            throw ValidationException::withMessages([
                'bill_ids' => 'Pilih minimal satu tagihan.',
            ]);
        }

        $bills = Bill::whereIn('id', $billIds)
            ->where('taxpayer_id', $taxpayer->id)
            ->get()
            ->keyBy('id');

        // Pastikan semua id milik taxpayer & masih dapat dibayar
        $missing = array_diff($billIds, $bills->keys()->all());
        if (!empty($missing)) {
            throw ValidationException::withMessages([
                'bill_ids' => 'Terdapat tagihan yang tidak valid atau bukan milik Anda.',
            ]);
        }

        foreach ($bills as $bill) {
            if (!in_array($bill->status, ['pending', 'overdue'])) {
                throw ValidationException::withMessages([
                    'bill_ids' => "Tagihan {$bill->bill_number} tidak dapat dibayar (status {$bill->status}).",
                ]);
            }
        }

        // Anti double payment: satu tagihan tidak boleh terikat pada request aktif lain
        foreach ($billIds as $billId) {
            $activeRequest = PaymentRequest::where('taxpayer_id', $taxpayer->id)
                ->whereIn('status', [PaymentRequest::STATUS_PENDING, PaymentRequest::STATUS_PROCESSING_RECEIPT])
                ->whereJsonContains('bill_ids', $billId)
                ->exists();

            if ($activeRequest) {
                throw ValidationException::withMessages([
                    'bill_ids' => 'Salah satu tagihan sudah memiliki payment request yang masih aktif.',
                ]);
            }
        }

        $amountWp = round($bills->sum(fn ($bill) => (float) $bill->amount), 2);
        $adminFee = round($bills->sum(fn ($bill) => (float) ($bill->admin_fee ?? 0)), 2);
        $totalAmount = round($bills->sum(fn ($bill) => (float) $bill->total_amount), 2);

        $expiredAt = Carbon::now()->addMinutes(self::TTL_MINUTES);
        $externalId = null;
        $vaNumber = null;
        $qrisString = null;
        $instructions = [];
        $provider = null;

        if ($method !== PaymentRequest::METHOD_OFFICER && $this->gateway->supports($method)) {
            $gatewayResult = $this->gateway->adapter($method)->createPayment([
                'reference' => PaymentRequest::generateToken(),
                'amount' => $totalAmount,
                'taxpayer_name' => $taxpayer->name,
                'description' => 'Pembayaran Retribusi M-PAD',
            ]);

            $externalId = $gatewayResult['external_id'] ?? null;
            $vaNumber = $gatewayResult['va_number'] ?? null;
            $qrisString = $gatewayResult['qris_string'] ?? null;
            $instructions = $gatewayResult['instructions'] ?? [];
            $provider = $method === PaymentRequest::METHOD_BRI_VA ? 'BRI' : null;
        }

        $qrPayload = null;
        if ($method === PaymentRequest::METHOD_OFFICER) {
            $qrPayload = json_encode([
                'type' => 'payment_request',
                'method' => 'officer',
                'token' => PaymentRequest::generateToken(),
            ]);
        }

        $request = PaymentRequest::create([
            'taxpayer_id' => $taxpayer->id,
            'method' => $method,
            'provider' => $provider,
            'status' => PaymentRequest::STATUS_PENDING,
            'amount' => $amountWp,
            'admin_fee' => $adminFee,
            'total_amount' => $totalAmount,
            'bill_ids' => $billIds,
            'bill_numbers' => $bills->values()->pluck('bill_number')->all(),
            'va_number' => $vaNumber,
            'qris_string' => $qrisString,
            'qr_payload' => $qrPayload,
            'external_id' => $externalId,
            'expired_at' => $expiredAt,
            'instructions' => $instructions,
            'can_refresh' => true,
            'can_cancel' => true,
            'metadata' => [
                'amount_wp' => $amountWp,
                'tax_deduction' => $method === PaymentRequest::METHOD_OFFICER ? $this->aftService->computeTaxDeduction($bills->values()) : 0,
            ],
        ]);

        return $request->load('taxpayer');
    }

    /**
     * Ambil Payment Request milik citizen dengan pengecekan kedaluwarsa otomatis.
     */
    public function getForCitizen(Taxpayer $taxpayer, int $id): PaymentRequest
    {
        $request = PaymentRequest::where('id', $id)
            ->where('taxpayer_id', $taxpayer->id)
            ->firstOrFail();

        $this->applyExpiry($request);

        return $request->load('taxpayer');
    }

    /**
     * Refresh status Payment Request (cek ulang ke gateway).
     */
    public function refreshForCitizen(Taxpayer $taxpayer, int $id): PaymentRequest
    {
        $request = $this->getForCitizen($taxpayer, $id);

        if ($request->status === PaymentRequest::STATUS_PENDING && $request->external_id) {
            try {
                $result = $this->gateway->adapter($request->method)->checkStatus($request->external_id);

                if (($result['status'] ?? 'pending') === 'paid') {
                    $this->markAsPaid($request, $result['reference_number'] ?? null);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Gateway refresh failed', [
                    'payment_request_id' => $request->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $request->fresh('taxpayer');
    }

    /**
     * Batalkan Payment Request milik citizen.
     */
    public function cancelForCitizen(Taxpayer $taxpayer, int $id): PaymentRequest
    {
        $request = $this->getForCitizen($taxpayer, $id);

        if (!$request->can_cancel) {
            throw ValidationException::withMessages([
                'request' => 'Payment request tidak dapat dibatalkan pada status ini.',
            ]);
        }

        if (!in_array($request->status, [
            PaymentRequest::STATUS_DRAFT,
            PaymentRequest::STATUS_PENDING,
            PaymentRequest::STATUS_PROCESSING_RECEIPT,
        ])) {
            throw ValidationException::withMessages([
                'request' => 'Payment request sudah berstatus terminal dan tidak dapat dibatalkan.',
            ]);
        }

        $request->update([
            'status' => PaymentRequest::STATUS_CANCELLED,
            'can_cancel' => false,
            'can_refresh' => false,
        ]);

        return $request->fresh('taxpayer');
    }

    /**
     * Verifikasi QR yang dipindai petugas.
     */
    public function verifyByToken(string $token): array
    {
        $request = PaymentRequest::where('token', $token)->with('taxpayer')->first();

        if (!$request) {
            throw new ModelNotFoundException('Payment request tidak ditemukan.');
        }

        $this->applyExpiry($request);

        $taxpayer = $request->taxpayer;
        $bills = $request->bills()
            ->load(['retributionType', 'classification', 'taxObject'])
            ->sortByDesc('id');

        $paymentHistory = Payment::with(['bill', 'taxObject', 'retributionType'])
            ->where('taxpayer_id', $taxpayer->id)
            ->where('status', 'success')
            ->latest()
            ->limit(10)
            ->get();

        $registration = $taxpayer->aftRegistration;

        return [
            'payment_request' => $request->toApiArray(),
            'taxpayer' => [
                'id' => $taxpayer->id,
                'nik' => $taxpayer->nik,
                'npwpd' => $taxpayer->npwpd,
                'name' => $taxpayer->name,
                'address' => $taxpayer->address,
                'district' => $taxpayer->district,
                'sub_district' => $taxpayer->sub_district,
                'phone' => $taxpayer->phone,
                'object_name' => $taxpayer->object_name,
                'object_address' => $taxpayer->object_address,
            ],
            'active_bills' => $bills->map(function (Bill $bill) {
                return [
                    'id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'period' => $bill->period,
                    'amount' => (float) $bill->amount,
                    'admin_fee' => (float) ($bill->admin_fee ?? 0),
                    'penalty_amount' => (float) ($bill->penalty_amount ?? 0),
                    'total_amount' => (float) $bill->total_amount,
                    'status' => $bill->status,
                    'due_date' => $bill->due_date?->toDateString(),
                    'retribution_type' => $bill->retributionType?->only(['id', 'name', 'icon', 'tariff_percent']),
                    'classification' => $bill->classification?->only(['id', 'name', 'code']),
                    'tax_object' => $bill->taxObject?->only(['id', 'name', 'nop']),
                ];
            })->values()->all(),
            'payment_history' => $paymentHistory->map(function (Payment $payment) {
                return [
                    'id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'payment_method' => $payment->payment_method,
                    'amount' => (float) $payment->amount,
                    'status' => $payment->status,
                    'status_label' => $payment->status === 'success' ? 'Lunas' : ucfirst($payment->status),
                    'billing_period' => $payment->billing_period,
                    'paid_at' => $payment->paid_at?->toISOString(),
                    'bill' => $payment->bill?->only(['id', 'bill_number', 'period']),
                    'tax_object' => $payment->taxObject?->only(['id', 'name']),
                    'retribution_type' => $payment->retributionType?->only(['id', 'name', 'icon']),
                ];
            })->values()->all(),
            'aft' => [
                'enabled' => (bool) $taxpayer->aft_enabled,
                'approval_status' => $registration?->approval_status,
                'status' => $registration?->status,
                'bank' => $registration?->bank,
                'beneficiary_account' => $registration?->beneficiary_account,
                'beneficiary_name' => $registration?->beneficiary_name,
            ],
        ];
    }

    /**
     * Selesaikan pembayaran oleh petugas (cash/qris/va).
     *
     * @return array [payment_request, amount_wp, tax_deduction, total_paid, payment_method, va_number, qris_string, transaction_reference, aft_transaction]
     */
    public function completeByOfficer(string $token, string $paymentMethod, array $payload = []): array
    {
        if (!in_array($paymentMethod, ['cash', 'qris', 'va'])) {
            throw ValidationException::withMessages([
                'payment_method' => 'Metode pembayaran harus cash, qris, atau va.',
            ]);
        }

        $referenceNumber = 'TRX-' . date('Ymd') . '-' . strtoupper(Str::random(8));
        $payments = collect();

        // Row lock (lockForUpdate) di dalam transaksi mencegah double payment
        // bila dua request complete datang secara bersamaan.
        $request = DB::transaction(function () use ($token, $paymentMethod, $payload, $referenceNumber, &$payments) {
            $request = PaymentRequest::where('token', $token)->lockForUpdate()->first();

            if (!$request) {
                throw new ModelNotFoundException('Payment request tidak ditemukan.');
            }

            $this->applyExpiry($request);

            // Anti double payment
            if ($request->status === PaymentRequest::STATUS_PAID) {
                throw ValidationException::withMessages([
                    'request' => 'Payment request ini sudah dibayar. Dilarang pembayaran ganda.',
                ]);
            }

            if (in_array($request->status, [
                PaymentRequest::STATUS_CANCELLED,
                PaymentRequest::STATUS_FAILED,
                PaymentRequest::STATUS_EXPIRED,
            ])) {
                throw ValidationException::withMessages([
                    'request' => "Payment request berstatus {$request->status} dan tidak dapat diproses.",
                ]);
            }

            $billIds = array_map('intval', $request->bill_ids ?? []);
            $bills = Bill::whereIn('id', $billIds)->get();

            $officerId = $payload['officer_id'] ?? null;

            foreach ($bills as $bill) {
                if (in_array($bill->status, ['lunas', 'paid'])) {
                    throw ValidationException::withMessages([
                        'request' => "Tagihan {$bill->bill_number} sudah lunas.",
                    ]);
                }

                $payment = Payment::create([
                    'bill_id' => $bill->id,
                    'payment_request_id' => $request->id,
                    'taxpayer_id' => $bill->taxpayer_id,
                    'tax_object_id' => $bill->tax_object_id,
                    'transaction_id' => 'PAY-' . date('Ymd') . '-' . strtoupper(Str::random(8)),
                    'payment_method' => $paymentMethod,
                    'amount' => (float) $bill->total_amount,
                    'status' => 'success',
                    'billing_period' => $bill->period ?: ($bill->period_start?->format('Y-m') ?: date('Y-m')),
                    'paid_at' => Carbon::now(),
                    'approved_by' => $officerId,
                    'proof_url' => $payload['proof_url'] ?? null,
                    'tendered_amount' => $payload['tendered_amount'] ?? null,
                    'change_amount' => $payload['change_amount'] ?? null,
                    'metadata' => [
                        'source' => AftTransaction::SOURCE_OFFICER,
                        'payment_request_id' => $request->id,
                        'reference_number' => $referenceNumber,
                    ],
                ]);

                $bill->update(['status' => 'lunas']);

                $payments->push($payment);
            }

            $receipts = $bills->map(function (Bill $bill) use ($referenceNumber) {
                return [
                    'bill_id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'reference_number' => $referenceNumber,
                    'receipt_number' => $referenceNumber,
                    'download_path' => '/api/public/pdf/sspd/' . $bill->id,
                    'file_name' => 'SSPD-' . ($bill->bill_number ?: $bill->id) . '.pdf',
                ];
            })->values()->all();

            $request->update([
                'status' => PaymentRequest::STATUS_PAID,
                'paid_at' => Carbon::now(),
                'reference_number' => $referenceNumber,
                'receipts' => $receipts,
                'can_cancel' => false,
                'can_refresh' => false,
            ]);

            return $request;
        });

        $bills = $request->bills();
        $taxDeduction = $this->aftService->computeTaxDeduction($bills);
        $totalPaid = round($payments->sum(fn ($payment) => (float) $payment->amount), 2);
        $amountWp = round($totalPaid - $taxDeduction, 2);

        $aftTransaction = $this->aftService->process($request, $payments, $payload['officer_id'] ?? null);

        $request->refresh()->load('taxpayer');

        return [
            'payment_request' => $request->toApiArray(),
            'amount_wp' => $amountWp,
            'tax_deduction' => $taxDeduction,
            'total_paid' => $totalPaid,
            'payment_method' => $paymentMethod,
            'va_number' => $request->va_number,
            'qris_string' => $request->qris_string,
            'transaction_reference' => $request->reference_number,
            'aft_transaction' => $aftTransaction ? [
                'id' => $aftTransaction->id,
                'status' => $aftTransaction->status,
                'transaction_amount' => (float) $aftTransaction->transaction_amount,
                'tax_amount' => (float) $aftTransaction->tax_amount,
                'beneficiary_account' => $aftTransaction->beneficiary_account,
                'beneficiary_bank' => $aftTransaction->beneficiary_bank,
                'settled_at' => $aftTransaction->settled_at?->toISOString(),
            ] : null,
        ];
    }

    /**
     * Proses callback webhook dari payment gateway.
     */
    public function handleWebhook(string $externalId, string $status, ?string $referenceNumber = null): PaymentRequest
    {
        $request = PaymentRequest::where('external_id', $externalId)->first();

        if (!$request) {
            throw new ModelNotFoundException('Payment request untuk external_id tidak ditemukan.');
        }

        $normalized = strtolower($status);

        if (in_array($normalized, ['paid', 'success', 'settled', 'settlement', 'capture', 'completed'])) {
            if ($request->status !== PaymentRequest::STATUS_PAID) {
                $this->markAsPaid($request, $referenceNumber);
            }
        } elseif (in_array($normalized, ['expired', 'expire'])) {
            $request->update(['status' => PaymentRequest::STATUS_EXPIRED, 'can_refresh' => false, 'can_cancel' => false]);
        } elseif (in_array($normalized, ['failed', 'failure', 'error', 'declined'])) {
            $request->update(['status' => PaymentRequest::STATUS_FAILED, 'can_refresh' => false]);
        }

        return $request->fresh('taxpayer');
    }

    public function applyExpiry(PaymentRequest $request): void
    {
        if ($request->isExpired() && $request->status !== PaymentRequest::STATUS_EXPIRED) {
            $request->update([
                'status' => PaymentRequest::STATUS_EXPIRED,
                'can_refresh' => false,
                'can_cancel' => false,
            ]);
        }
    }

    protected function markAsPaid(PaymentRequest $request, ?string $referenceNumber = null): void
    {
        $referenceNumber = $referenceNumber ?: 'TRX-' . date('Ymd') . '-' . strtoupper(Str::random(8));

        $payments = collect();

        // Row lock + transaksi: webhook bisa dikirim ulang, sehingga pembayaran
        // dan bill tidak boleh dibuat dua kali.
        DB::transaction(function () use ($request, $referenceNumber, &$payments) {
            $locked = PaymentRequest::whereKey($request->id)->lockForUpdate()->first();

            if (!$locked || $locked->status === PaymentRequest::STATUS_PAID) {
                return;
            }

            $bills = $locked->bills();

            foreach ($bills as $bill) {
                if (in_array($bill->status, ['lunas', 'paid'])) {
                    continue;
                }

                $payment = Payment::create([
                    'bill_id' => $bill->id,
                    'payment_request_id' => $locked->id,
                    'taxpayer_id' => $bill->taxpayer_id,
                    'tax_object_id' => $bill->tax_object_id,
                    'transaction_id' => 'PAY-' . date('Ymd') . '-' . strtoupper(Str::random(8)),
                    'payment_method' => $this->resolveGatewayPaymentMethod($locked->method),
                    'amount' => (float) $bill->total_amount,
                    'status' => 'success',
                    'billing_period' => $bill->period ?: ($bill->period_start?->format('Y-m') ?: date('Y-m')),
                    'paid_at' => Carbon::now(),
                    'metadata' => [
                        'source' => 'gateway_webhook',
                        'payment_request_id' => $locked->id,
                        'reference_number' => $referenceNumber,
                    ],
                ]);

                $bill->update(['status' => 'lunas']);

                $payments->push($payment);
            }

            $receipts = $bills->map(function (Bill $bill) use ($referenceNumber) {
                return [
                    'bill_id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'reference_number' => $referenceNumber,
                    'receipt_number' => $referenceNumber,
                    'download_path' => '/api/public/pdf/sspd/' . $bill->id,
                    'file_name' => 'SSPD-' . ($bill->bill_number ?: $bill->id) . '.pdf',
                ];
            })->values()->all();

            $locked->update([
                'status' => PaymentRequest::STATUS_PAID,
                'paid_at' => Carbon::now(),
                'reference_number' => $referenceNumber,
                'receipts' => $receipts,
                'can_cancel' => false,
                'can_refresh' => false,
            ]);
        });

        if ($payments->isNotEmpty()) {
            $this->aftService->process($request, $payments);
        }
    }

    /**
     * Peta metode payment request (bri_va/qris) ke nilai payment_method pada
     * tabel payments (va/qris) sesuai konvensi alur petugas.
     */
    protected function resolveGatewayPaymentMethod(string $method): string
    {
        return match ($method) {
            PaymentRequest::METHOD_BRI_VA => 'va',
            PaymentRequest::METHOD_QRIS => 'qris',
            default => $method,
        };
    }
}
