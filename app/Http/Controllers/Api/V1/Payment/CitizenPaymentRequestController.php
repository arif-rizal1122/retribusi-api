<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestItem;
use App\Models\Taxpayer;
use App\Services\Payment\Snap\SnapBrivaReadiness;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CitizenPaymentRequestController extends Controller
{
    public function __construct(private readonly SnapBrivaReadiness $brivaReadiness) {}

    public function store(Request $request): JsonResponse
    {
        \Illuminate\Support\Facades\Log::info('CitizenPaymentRequest::store incoming', [
            'payload' => $request->all(),
            'user_id' => $request->user()?->id,
            'user_class' => $request->user() ? get_class($request->user()) : null,
        ]);

        try {
            $validated = $request->validate([
                'bill_ids' => ['required', 'array', 'min:1', 'max:20'],
                'bill_ids.*' => ['integer', 'distinct', 'exists:bills,id'],
                'method' => ['required', 'in:bri_va,sultra_va'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            \Illuminate\Support\Facades\Log::warning('CitizenPaymentRequest::store validation error', [
                'errors' => $ve->errors(),
                'payload' => $request->all(),
            ]);
            throw $ve;
        }

        $taxpayer = $this->taxpayer($request);
        $billIds = collect($validated['bill_ids'])->map(fn ($id) => (int) $id)->sort()->values();
        $isSultra = $validated['method'] === 'sultra_va';
        $channel = $isSultra ? 'Bank Sultra' : 'BRI';

        if (! $isSultra && ! $this->brivaReadiness->available()) {
            return response()->json(['message' => $this->brivaReadiness->publicMessage()], 422);
        }

        $partnerServiceId = preg_replace('/\D/', '', (string) config('snap.briva.partner_service_id'));
        $prefix = preg_replace('/\D/', '', (string) config('snap.briva.va_prefix'));
        $customerNoLength = (int) config('snap.briva.customer_no_length', 20);
        $length = (int) config('snap.briva.va_length', 28);

        [$paymentRequest, $reused] = DB::transaction(function () use ($billIds, $taxpayer, $isSultra, $channel, $partnerServiceId, $prefix, $customerNoLength, $length) {
            PaymentRequest::where('taxpayer_id', $taxpayer->id)
                ->where('payment_channel', $channel)
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

            // Validasi FIFO untuk tagihan rusunawa (Disperkim, opd_id: 11)
            $rusunBills = $bills->filter(fn (Bill $b) => (int) $b->opd_id === 11);
            if ($rusunBills->isNotEmpty()) {
                app(\App\Services\RusunBillingService::class)->validateFifoPayment($taxpayer, $billIds);
            }

            $activePaymentRequests = PaymentRequest::with('items.bill')
                ->where('taxpayer_id', $taxpayer->id)
                ->where('method', 'VA')
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->whereHas('items', fn ($query) => $query->whereIn('bill_id', $billIds))
                ->lockForUpdate()
                ->latest('id')
                ->get();

            $existing = $activePaymentRequests
                ->where('payment_channel', $channel)
                ->first(fn (PaymentRequest $paymentRequest) => $paymentRequest->items->pluck('bill_id')->sort()->values()->all() === $billIds->all());

            if ($existing) {
                return [$existing, true];
            }

            // Batalkan payment request pending lama yang tumpang tindih untuk tagihan-tagihan ini
            // agar wajib pajak tidak terkunci (error 422) saat mengubah pilihan tagihan atau channel pembayaran.
            foreach ($activePaymentRequests as $stalePaymentRequest) {
                $stalePaymentRequest->update(['status' => 'cancelled']);
                $stalePaymentRequest->items()->where('status', 'pending')->update(['status' => 'cancelled']);
            }

            $expiresAt = now()->addMinutes((int) config('snap.briva.payment_request_expiry_minutes', 1440));
            foreach ($bills as $bill) {
                if ($bill->due_date && $bill->due_date->isFuture() && $bill->due_date->lessThan($expiresAt)) {
                    $expiresAt = $bill->due_date->copy();
                }
            }

            $baseAmount = $bills->sum(fn (Bill $bill) => (float) $bill->amount);
            $adminFee = $bills->sum(fn (Bill $bill) => (float) $bill->admin_fee);
            $totalAmount = $bills->sum(fn (Bill $bill) => (float) $bill->total_amount);

            $paymentRequest = PaymentRequest::create([
                'bill_id' => $bills->first()->id,
                'tax_object_id' => $bills->first()->tax_object_id,
                'taxpayer_id' => $taxpayer->id,
                'payment_channel' => $channel,
                'method' => 'VA',
                'amount_snapshot' => $baseAmount,
                'admin_fee_snapshot' => $adminFee,
                'penalty_snapshot' => $totalAmount - $baseAmount - $adminFee,
                'expires_at' => $expiresAt,
                'external_id' => ($isSultra ? 'MOB-SULTRA-' : 'MOB-BRI-').Str::upper(Str::random(16)),
                'status' => 'pending',
            ]);

            $vaNumber = $isSultra
                ? $this->buildSultraVaNumber($paymentRequest->id, $bills->first())
                : $this->buildVaNumber(
                    $paymentRequest->id,
                    $partnerServiceId,
                    $prefix,
                    $customerNoLength,
                    $length
                );

            $paymentRequest->update([
                'va_number' => $vaNumber,
            ]);

            foreach ($bills as $bill) {
                PaymentRequestItem::create([
                    'payment_request_id' => $paymentRequest->id,
                    'bill_id' => $bill->id,
                    'amount_snapshot' => $bill->total_amount,
                    'status' => 'pending',
                ]);
            }

            return [$paymentRequest->fresh(['items.bill.taxpayer', 'items.bill.retributionType', 'items.bill.opd', 'items.bill.taxObject.opd']), false];
        });

        return response()->json(['data' => $this->payload($paymentRequest)], $reused ? 200 : 201);
    }

    private function buildSultraVaNumber(int $paymentRequestId, ?Bill $primaryBill = null): string
    {
        $prefix = (string) config('snap.partners.BANK_SULTRA.partner_id', '7472');

        if ($primaryBill && (int) $primaryBill->opd_id === 11) {
            $metadata = is_array($primaryBill->metadata) ? $primaryBill->metadata : json_decode((string) $primaryBill->metadata, true);
            $complexCode = $metadata['complex_code'] ?? null;
            $prefix = match ($complexCode) {
                'mahasiswa' => '7401',
                'wameo'     => '7402',
                'asn'       => '7403',
                'kotamara'  => '7404',
                default     => $prefix,
            };
        }

        $partnerId = preg_replace('/\D/', '', $prefix) ?: '7472';
        $partnerServiceId = str_pad(substr($partnerId, 0, 8), 8, '0', STR_PAD_LEFT);
        $customerNo = str_pad((string) $paymentRequestId, 8, '0', STR_PAD_LEFT);

        return $partnerServiceId.$customerNo;
    }

    private function buildVaNumber(
        int $paymentRequestId,
        string $partnerServiceId,
        string $legacyPrefix,
        int $customerNoLength,
        int $vaLength
    ): string {
        if ($partnerServiceId !== '') {
            $customerNo = str_pad((string) $paymentRequestId, $customerNoLength, '0', STR_PAD_LEFT);
            $vaNumber = $partnerServiceId.$customerNo;
        } else {
            $vaNumber = $legacyPrefix.str_pad(
                (string) $paymentRequestId,
                $vaLength - strlen($legacyPrefix),
                '0',
                STR_PAD_LEFT
            );
        }

        if (! preg_match('/^\d+$/', $vaNumber) || strlen($vaNumber) > $vaLength) {
            throw new \RuntimeException('BRIVA VA format is invalid or exceeds configured length.');
        }

        return $vaNumber;
    }

    public function show(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $this->assertOwner($request, $paymentRequest);

        return response()->json(['data' => $this->payload($this->refreshExpiry($paymentRequest))]);
    }

    public function refresh(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $this->assertOwner($request, $paymentRequest);

        $paymentRequest = $this->refreshExpiry($paymentRequest);

        if ($paymentRequest->status === 'pending') {
            $paymentRequest = $paymentRequest->fresh(['items.bill.taxpayer', 'items.bill.retributionType', 'items.bill.opd', 'items.bill.taxObject.opd']);
        }

        return response()->json(['data' => $this->payload($paymentRequest)]);
    }

    public function cancel(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $this->assertOwner($request, $paymentRequest);
        $paymentRequest = $this->refreshExpiry($paymentRequest);

        if ($paymentRequest->status === 'pending') {
            $paymentRequest->update(['status' => 'cancelled']);
        }

        return response()->json(['data' => $this->payload($paymentRequest->fresh(['items.bill.taxpayer', 'items.bill.retributionType', 'items.bill.opd', 'items.bill.taxObject.opd']))]);
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
        $channelUpper = strtoupper((string) $paymentRequest->payment_channel);
        $isSultra = in_array($channelUpper, ['BANK_SULTRA', 'BANK SULTRA', 'SULTRA'], true);
        $isBri = in_array($channelUpper, ['BRI', 'BANK BRI'], true);

        $method = ($isBri && strtoupper($paymentRequest->method) === 'VA')
            ? 'bri_va'
            : (($isSultra && strtoupper($paymentRequest->method) === 'VA')
                ? 'sultra_va'
                : strtolower($paymentRequest->method));
        $totalAmount = (float) $paymentRequest->amount_snapshot
            + (float) $paymentRequest->admin_fee_snapshot
            + (float) $paymentRequest->penalty_snapshot;

        $instructions = $isSultra ? [
            'Buka aplikasi Mobile Banking Bank Sultra atau kunjungi ATM / Teller Bank Sultra.',
            'Pilih menu Pembayaran Virtual Account / Pajak & Retribusi Daerah Baubau.',
            'Masukkan nomor VA Bank Sultra dan pastikan nama serta nominal tagihan sesuai.',
            'Selesaikan transaksi dan simpan bukti pembayaran. Status tagihan terverifikasi secara realtime.',
        ] : [
            'Buka BRImo, ATM BRI, BRILink, atau channel pembayaran BRI.',
            'Pilih menu pembayaran BRIVA atau Virtual Account.',
            'Masukkan nomor VA dan pastikan nama serta nominal tagihan sesuai.',
            'Pembayaran akan diperbarui otomatis setelah callback bank diterima.',
        ];

        return [
            'id' => (string) $paymentRequest->id,
            'external_id' => $paymentRequest->external_id,
            'provider' => $isSultra ? 'Bank Sultra' : $paymentRequest->payment_channel,
            'method' => $method,
            'status' => $paymentRequest->status,
            'status_label' => $this->statusLabel($paymentRequest->status, $isSultra),
            'bill_ids' => $items->pluck('bill_id')->values(),
            'bill_numbers' => $items->map(fn (PaymentRequestItem $item) => $item->bill?->bill_number)->filter()->values(),
            'bills' => $items->map(fn (PaymentRequestItem $item) => [
                'bill_id' => $item->bill_id,
                'bill_number' => $item->bill?->bill_number,
                'label' => $item->bill?->taxObject?->name
                    ?: $item->bill?->classification?->name
                    ?: $item->bill?->retributionType?->name
                    ?: 'Tagihan',
                'tax_object_name' => $item->bill?->taxObject?->name ?: null,
                'address' => $item->bill?->taxObject?->address ?: null,
                'tax_type' => $item->bill?->taxObject?->retributionType?->name
                    ?: $item->bill?->classification?->name
                    ?: $item->bill?->retributionType?->name
                    ?: null,
                'period_start' => $item->bill?->period_start?->toDateString(),
                'period_end' => $item->bill?->period_end?->toDateString(),
                'due_date' => $item->bill?->due_date?->toDateString(),
                'amount' => (float) $item->amount_snapshot,
                'penalty' => (float) $item->bill?->penalty_amount,
                'admin_fee' => (float) $item->bill?->admin_fee,
                'billing_cycle' => $item->bill?->retributionType?->billing_cycle
                    ?: $item->bill?->retributionType?->category
                    ?: null,
                'unit_name' => $item->bill?->opd?->name
                    ?: $item->bill?->taxObject?->opd?->name
                    ?: null,
            ])->values(),
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
            'instructions' => $instructions,
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

    private function statusLabel(string $status, bool $isSultra = false): string
    {
        return match ($status) {
            'paid' => 'Pembayaran diterima.',
            'expired' => 'Payment request telah kedaluwarsa.',
            'cancelled' => 'Payment request dibatalkan.',
            default => $isSultra
                ? 'Menunggu pembayaran melalui channel Bank Sultra.'
                : 'Menunggu pembayaran melalui channel BRI.',
        };
    }
}
