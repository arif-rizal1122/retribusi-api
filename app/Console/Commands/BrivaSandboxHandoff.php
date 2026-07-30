<?php

namespace App\Console\Commands;

use App\Models\PaymentRequest;
use Illuminate\Console\Command;

class BrivaSandboxHandoff extends Command
{
    protected $signature = 'snap:briva-sandbox-handoff
        {payment_request_id : Pending BRI VA payment request ID}
        {--base-url= : Public HTTPS base URL registered for BRI sandbox}
        {--json : Output safe handoff as JSON}';

    protected $description = 'Print safe BRIVA sandbox handoff data for BRI-assisted inquiry/payment trigger.';

    public function handle(): int
    {
        $paymentRequest = PaymentRequest::with(['items.bill', 'bill'])
            ->find($this->argument('payment_request_id'));

        if (! $paymentRequest) {
            $this->error('Payment request tidak ditemukan.');

            return self::FAILURE;
        }

        if ($paymentRequest->payment_channel !== 'BRI' || $paymentRequest->method !== 'VA') {
            $this->error('Payment request bukan channel BRIVA BRI.');

            return self::FAILURE;
        }

        if ($paymentRequest->status !== 'pending') {
            $this->error('Payment request harus berstatus pending agar bisa di-trigger BRI sandbox.');

            return self::FAILURE;
        }

        if ($paymentRequest->expires_at && $paymentRequest->expires_at->isPast()) {
            $this->error('Payment request sudah melewati expiry.');

            return self::FAILURE;
        }

        if (! $paymentRequest->va_number) {
            $this->error('Payment request belum memiliki nomor VA.');

            return self::FAILURE;
        }

        $handoff = $this->handoffPayload($paymentRequest);

        if ($this->option('json')) {
            $this->line((string) json_encode($handoff, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('BRIVA sandbox handoff - aman dibagikan ke BRI sandbox');
        $this->table(
            ['Field', 'Value'],
            collect($handoff)
                ->except(['callback_urls', 'request_fields', 'notes'])
                ->map(fn ($value, $key) => [$key, is_array($value) ? implode(', ', $value) : (string) $value])
                ->values()
                ->all()
        );

        $this->info('Callback URLs');
        $this->table(
            ['Endpoint', 'URL'],
            collect($handoff['callback_urls'])->map(fn ($value, $key) => [$key, $value])->values()->all()
        );

        $this->info('Suggested request body identifiers');
        $this->table(
            ['Field', 'Value'],
            collect($handoff['request_fields'])->map(fn ($value, $key) => [$key, $value])->values()->all()
        );

        foreach ($handoff['notes'] as $note) {
            $this->warn($note);
        }

        return self::SUCCESS;
    }

    private function handoffPayload(PaymentRequest $paymentRequest): array
    {
        $identifierPrefix = $this->identifierPrefix();
        $customerNo = substr($paymentRequest->va_number, strlen($identifierPrefix));
        $baseUrl = rtrim((string) ($this->option('base-url') ?: config('snap.callback_base_url') ?: config('app.url')), '/');

        $billNumbers = $paymentRequest->items
            ->map(fn ($item) => $item->bill?->bill_number)
            ->filter()
            ->values()
            ->all();

        if ($billNumbers === [] && $paymentRequest->bill?->bill_number) {
            $billNumbers = [$paymentRequest->bill->bill_number];
        }

        return [
            'payment_request_id' => (string) $paymentRequest->id,
            'status' => $paymentRequest->status,
            'va_number' => $paymentRequest->va_number,
            'partner_service_id' => $identifierPrefix,
            'customer_no' => $customerNo,
            'total_amount' => number_format($this->totalAmount($paymentRequest), 2, '.', ''),
            'expires_at' => $paymentRequest->expires_at?->toIso8601String(),
            'bill_numbers' => $billNumbers,
            'callback_urls' => [
                'access_token_b2b' => $baseUrl.'/api/snap/v1.1/access-token/b2b',
                'inquiry_va' => $baseUrl.'/api/snap/v1.0/transfer-va/inquiry',
                'payment_va' => $baseUrl.'/api/snap/v1.0/transfer-va/payment',
            ],
            'request_fields' => [
                'partnerServiceId' => $identifierPrefix,
                'customerNo' => $customerNo,
                'virtualAccountNo' => $paymentRequest->va_number,
            ],
            'notes' => [
                'Data ini tidak memuat credential, token, public key, private key, NIK, atau nomor HP.',
                'Prefix 19960 adalah sandbox-only; BRImo/ATM production tidak dapat dipakai untuk trigger VA ini.',
                'BRI tetap perlu mengirim header SNAP resmi dan signature RSA-SHA256 dari channel sandbox.',
            ],
        ];
    }

    private function identifierPrefix(): string
    {
        $partnerServiceId = preg_replace('/\D/', '', (string) config('snap.briva.partner_service_id'));
        if ($partnerServiceId !== '') {
            return $partnerServiceId;
        }

        return preg_replace('/\D/', '', (string) config('snap.briva.va_prefix'));
    }

    private function totalAmount(PaymentRequest $paymentRequest): float
    {
        return (float) $paymentRequest->amount_snapshot
            + (float) $paymentRequest->admin_fee_snapshot
            + (float) $paymentRequest->penalty_snapshot;
    }
}
