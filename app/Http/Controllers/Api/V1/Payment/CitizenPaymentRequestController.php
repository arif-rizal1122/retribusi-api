<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestItem;
use App\Models\Taxpayer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CitizenPaymentRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bill_ids' => ['required', 'array', 'min:1', 'max:20'],
            'bill_ids.*' => ['integer', 'distinct', 'exists:bills,id'],
            'method' => ['required', 'in:bri_va'],
        ]);

        $taxpayer = $this->taxpayer($request);
        $billIds = collect($validated['bill_ids'])->map(fn ($id) => (int) $id)->sort()->values();
        $prefix = preg_replace('/\D/', '', (string) config('snap.briva.va_prefix'));
        $length = (int) config('snap.briva.va_length', 18);
        if ($prefix === '' || strlen($prefix) >= $length) {
            return response()->json(['message' => 'Channel BRIVA belum dikonfigurasi untuk sandbox.'], 422);
        }

        [$paymentRequest, $reused] = DB::transaction(function () use ($billIds, $taxpayer, $prefix, $length) {
            PaymentRequest::where('taxpayer_id', $taxpayer->id)
                ->where('payment_channel', 'BRI')
                ->where('method', 'VA')
                ->where('status', 'pending')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->update(['status' => 'expired']);

            $bills = Bill::with(['taxpayer', 'taxObject'])
                ->where('taxpayer_id', $taxpayer->id)
                ->whereIn('id', $billIds)
                ->whereIn('status', ['pending', 'overdue', 'unpaid'])
                ->lockForUpdate()
                ->get()
                ->sortBy('id')
                ->values();

            if ($bills->count() !== $billIds->count()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'bill_ids' => 'Satu atau lebih tagihan tidak dapat dibayarkan melalui gateway.',
                ]);
            }

            $hasManualClaim = Payment::whereIn('bill_id', $billIds)
                ->whereIn('status', ['pending', 'success'])
                ->exists();

            if ($hasManualClaim) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'bill_ids' => 'Satu atau lebih tagihan sudah lunas atau sedang menunggu verifikasi pembayaran manual.',
                ]);
            }

            $activePaymentRequests = PaymentRequest::with('items.bill')
                ->where('taxpayer_id', $taxpayer->id)
                ->where('payment_channel', 'BRI')
                ->where('method', 'VA')
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->whereHas('items', fn ($query) => $query->whereIn('bill_id', $billIds))
                ->lockForUpdate()
                ->latest('id')
                ->get();

            $existing = $activePaymentRequests
                ->first(fn (PaymentRequest $paymentRequest) => $paymentRequest->items->pluck('bill_id')->sort()->values()->all() === $billIds->all());

            if ($existing) {
                return [$existing, true];
            }

            if ($activePaymentRequests->isNotEmpty()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'bill_ids' => 'Satu atau lebih tagihan masih memiliki pembayaran BRIVA aktif.',
                ]);
            }

            $expiresAt = now()->addMinutes((int) config('snap.briva.payment_request_expiry_minutes', 1440));
            foreach ($bills as $bill) {
                if ($bill->due_date && $bill->due_date->lessThan($expiresAt)) {
                    $expiresAt = $bill->due_date->copy();
                }
            }

            if ($expiresAt->lessThanOrEqualTo(now())) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'bill_ids' => 'Tagihan yang dipilih sudah melewati masa pembayaran.',
                ]);
            }

            $baseAmount = $bills->sum(fn (Bill $bill) => (float) $bill->amount);
            $adminFee = $bills->sum(fn (Bill $bill) => (float) $bill->admin_fee);
            $totalAmount = $bills->sum(fn (Bill $bill) => (float) $bill->total_amount);

            $paymentRequest = PaymentRequest::create([
                'bill_id' => $bills->first()->id,
                'tax_object_id' => $bills->first()->tax_object_id,
                'taxpayer_id' => $taxpayer->id,
                'payment_channel' => 'BRI',
                'method' => 'VA',
                'amount_snapshot' => $baseAmount,
                'admin_fee_snapshot' => $adminFee,
                'penalty_snapshot' => $totalAmount - $baseAmount - $adminFee,
                'expires_at' => $expiresAt,
                'external_id' => 'MOB-BRI-'.Str::upper(Str::random(16)),
                'status' => 'pending',
            ]);

            $paymentRequest->update([
                'va_number' => $prefix.str_pad((string) $paymentRequest->id, $length - strlen($prefix), '0', STR_PAD_LEFT),
            ]);

            foreach ($bills as $bill) {
                PaymentRequestItem::create([
                    'payment_request_id' => $paymentRequest->id,
                    'bill_id' => $bill->id,
                    'amount_snapshot' => $bill->total_amount,
                    'status' => 'pending',
                ]);
            }

            return [$paymentRequest->fresh(['items.bill.taxpayer']), false];
        });

        return response()->json(['data' => $this->payload($paymentRequest)], $reused ? 200 : 201);
    }

    public function show(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $this->assertOwner($request, $paymentRequest);

        return response()->json(['data' => $this->payload($this->refreshExpiry($paymentRequest))]);
    }

    public function refresh(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $this->assertOwner($request, $paymentRequest);

        return response()->json(['data' => $this->payload($this->refreshExpiry($paymentRequest))]);
    }

    public function cancel(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $this->assertOwner($request, $paymentRequest);
        $paymentRequest = $this->refreshExpiry($paymentRequest);

        if ($paymentRequest->status === 'pending') {
            $paymentRequest->update(['status' => 'cancelled']);
        }

        return response()->json(['data' => $this->payload($paymentRequest->fresh(['items.bill.taxpayer']))]);
    }

    private function taxpayer(Request $request): Taxpayer
    {
        $user = $request->user();
        abort_unless($user instanceof Taxpayer, 403, 'Endpoint pembayaran ini hanya untuk wajib pajak.');

        return $user;
    }

    private function assertOwner(Request $request, PaymentRequest $paymentRequest): void
    {
        abort_unless($paymentRequest->taxpayer_id === $this->taxpayer($request)->id, 404);
    }

    private function refreshExpiry(PaymentRequest $paymentRequest): PaymentRequest
    {
        if ($paymentRequest->status === 'pending' && $paymentRequest->expires_at && $paymentRequest->expires_at->isPast()) {
            $paymentRequest->update(['status' => 'expired']);
        }

        return $paymentRequest->fresh(['items.bill.taxpayer']);
    }

    private function payload(PaymentRequest $paymentRequest): array
    {
        $items = $paymentRequest->items;
        $receipts = $this->receipts($paymentRequest, $items);
        $method = strtolower($paymentRequest->payment_channel) === 'bri' && strtoupper($paymentRequest->method) === 'VA'
            ? 'bri_va'
            : strtolower($paymentRequest->method);
        $totalAmount = (float) $paymentRequest->amount_snapshot
            + (float) $paymentRequest->admin_fee_snapshot
            + (float) $paymentRequest->penalty_snapshot;

        return [
            'id' => (string) $paymentRequest->id,
            'external_id' => $paymentRequest->external_id,
            'provider' => $paymentRequest->payment_channel,
            'method' => $method,
            'status' => $paymentRequest->status,
            'status_label' => $this->statusLabel($paymentRequest->status),
            'bill_ids' => $items->pluck('bill_id')->values(),
            'bill_numbers' => $items->map(fn (PaymentRequestItem $item) => $item->bill?->bill_number)->filter()->values(),
            'amount' => (float) $paymentRequest->amount_snapshot,
            'admin_fee' => (float) $paymentRequest->admin_fee_snapshot,
            'total_amount' => $totalAmount,
            'va_number' => $paymentRequest->va_number,
            'expired_at' => $paymentRequest->expires_at?->toIso8601String(),
            'paid_at' => $paymentRequest->paid_at?->toIso8601String(),
            'reference_number' => $paymentRequest->provider_reference,
            'receipt_number' => $receipts->first()['receipt_number'] ?? null,
            'receipt_url' => $receipts->first()['download_path'] ?? null,
            'receipts' => $receipts,
            'instructions' => [
                'Buka BRImo, ATM BRI, BRILink, atau channel pembayaran BRI.',
                'Pilih menu pembayaran BRIVA atau Virtual Account.',
                'Masukkan nomor VA dan pastikan nama serta nominal tagihan sesuai.',
                'Pembayaran akan diperbarui otomatis setelah callback bank diterima.',
            ],
            'can_refresh' => $paymentRequest->status === 'pending',
            'can_cancel' => $paymentRequest->status === 'pending',
        ];
    }

    private function receipts(PaymentRequest $paymentRequest, Collection $items): Collection
    {
        if ($paymentRequest->status !== 'paid') {
            return collect();
        }

        $payments = Payment::where('taxpayer_id', $paymentRequest->taxpayer_id)
            ->whereIn('bill_id', $items->pluck('bill_id'))
            ->where('status', 'success')
            ->whereNotNull('receipt_number')
            ->latest('id')
            ->get()
            ->when($paymentRequest->provider_reference, function (Collection $payments, string $reference) {
                return $payments->filter(fn (Payment $payment) => $payment->reference_number === $reference
                    || str_starts_with((string) $payment->reference_number, "{$reference}-"));
            })
            ->unique('bill_id')
            ->keyBy('bill_id');

        return $items->map(function (PaymentRequestItem $item) use ($payments) {
            $payment = $payments->get($item->bill_id);
            if (! $payment) {
                return null;
            }

            return [
                'bill_id' => $item->bill_id,
                'bill_number' => $item->bill?->bill_number,
                'reference_number' => $payment->reference_number,
                'receipt_number' => $payment->receipt_number,
                'download_path' => "/api/bills/{$item->bill_id}/sspd",
                'file_name' => 'SSPD-'.($item->bill?->bill_number ?? $item->bill_id).'.pdf',
            ];
        })->filter()->values();
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'paid' => 'Pembayaran diterima.',
            'expired' => 'Payment request telah kedaluwarsa.',
            'cancelled' => 'Payment request dibatalkan.',
            default => 'Menunggu pembayaran melalui channel BRI.',
        };
    }
}
