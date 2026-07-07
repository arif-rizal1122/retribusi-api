<?php

namespace App\Services;

use App\Models\AutoDeductLog;
use App\Models\Bill;
use App\Models\NotificationLog;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\TaxTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AutoDeductService
{
    protected BillingService $billingService;
    protected WaGatewayService $waGateway;
    protected $paymentManager;

    public function __construct()
    {
        $this->billingService = app(BillingService::class);
        $this->waGateway = app(WaGatewayService::class);
        $this->paymentManager = app(PaymentManager::class);
    }

    public function recordTransaction(
        TaxObject $taxObject,
        float $transactionAmount,
        string $source = 'pos',
        ?string $description = null,
        ?Taxpayer $overrideTaxpayer = null
    ): array {
        $taxpayer = $overrideTaxpayer ?? $taxObject->taxpayer;
        $classification = $taxObject->classification;

        if (!$classification || !$classification->is_self_assessment) {
            return [
                'status' => 'skipped',
                'message' => 'Objek pajak ini bukan self-assessment, tidak ada auto-deduct'
            ];
        }

        $formula = $classification->calculation_formula;
        $taxAmount = $this->calculateTax($formula, $transactionAmount);

        $taxTransaction = TaxTransaction::create([
            'opd_id' => $taxObject->opd_id,
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'transaction_date' => Carbon::now(),
            'amount' => $transactionAmount,
            'tax_amount' => $taxAmount,
            'source' => "auto_deduct_{$source}",
            'description' => $description ?? ('Auto-deduct transaksi ' . $taxObject->name),
            'metadata' => [
                'classification' => $classification->name,
                'formula' => $formula,
            ],
        ]);

        $log = AutoDeductLog::create([
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'source' => $source,
            'transaction_type' => 'sale',
            'transaction_amount' => $transactionAmount,
            'tax_amount' => $taxAmount,
            'deducted_amount' => $taxAmount,
            'status' => 'success',
            'reference_number' => 'TRX-' . time() . '-' . Str::random(6),
            'metadata' => [
                'tax_transaction_id' => $taxTransaction->id,
                'description' => $description,
            ],
            'processed_at' => Carbon::now(),
        ]);

        $paymentResult = $this->generateInstantPayment($taxObject, $taxAmount, $source);
        $this->sendTransactionNotification($taxpayer, $taxObject, $transactionAmount, $taxAmount, $paymentResult);

        return [
            'status' => 'success',
            'tax_transaction_id' => $taxTransaction->id,
            'auto_deduct_log_id' => $log->id,
            'transaction_amount' => $transactionAmount,
            'tax_amount' => $taxAmount,
            'payment' => $paymentResult,
        ];
    }

    public function generateInstantPayment(TaxObject $taxObject, float $taxAmount, string $channel): array
    {
        $result = ['qr_string' => null, 'va_number' => null, 'expiry_at' => null];

        try {
            if ($channel === 'qris' || $channel === 'pos') {
                $qrisDriver = $this->paymentManager->driver('qris');
                $inquiryResult = $qrisDriver->inquiry('AUTO-' . Str::random(8));
                if (($inquiryResult['status'] ?? '') === 'success') {
                    $result['qr_string'] = $inquiryResult['data']['qr_string'] ?? null;
                    $result['expiry_at'] = $inquiryResult['data']['expiry_at'] ?? null;
                }
            }

            if (in_array($channel, ['va', 'transfer'])) {
                $sultraDriver = $this->paymentManager->driver('sultra');
                $vaResult = $sultraDriver->getAccountDetail('AUTO-' . Str::random(8));
                if (($vaResult['status'] ?? '') === 'success') {
                    $result['va_number'] = $vaResult['data']['va_number'] ?? null;
                }
            }
        } catch (\Exception $e) {
            Log::error('AutoDeduct GeneratePayment Error: ' . $e->getMessage());
        }

        return $result;
    }

    public function processAutoPayment(TaxObject $taxObject, float $amount, string $paymentMethod = 'va'): array
    {
        $taxpayer = $taxObject->taxpayer;

        try {
            $period = Carbon::now()->format('Y-m');
            $bill = Bill::firstOrCreate(
                [
                    'tax_object_id' => $taxObject->id,
                    'period' => $period,
                    'status' => 'pending',
                ],
                [
                    'taxpayer_id' => $taxpayer->id,
                    'opd_id' => $taxObject->opd_id,
                    'retribution_type_id' => $taxObject->retribution_type_id,
                    'retribution_classification_id' => $taxObject->retribution_classification_id,
                    'bill_number' => 'AUTO-' . strtoupper(Str::random(10)),
                    'amount' => $amount,
                    'due_date' => Carbon::now()->addDays(30),
                    'metadata' => ['source' => 'auto_deduct'],
                ]
            );

            $bill->amount = $amount;
            $bill->save();

            $paymentData = [
                'bill_number' => $bill->bill_number,
                'amount_paid' => $amount,
                'transaction_id' => 'AUTO-' . time() . '-' . Str::random(6),
                'channel' => $paymentMethod,
            ];

            if ($paymentMethod === 'va') {
                $driver = $this->paymentManager->driver('sultra');
                $result = $driver->notify($paymentData);
            } else {
                $driver = $this->paymentManager->driver('qris');
                $result = $driver->notify($paymentData);
            }

            if (($result['status'] ?? '') === 'success') {
                $this->sendPaymentReceipt($taxpayer, $taxObject, $amount, $result['data']['ntpd'] ?? null);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('AutoPayment Error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function sendTransactionNotification(
        Taxpayer $taxpayer,
        TaxObject $taxObject,
        float $transactionAmount,
        float $taxAmount,
        array $paymentInfo = []
    ): void {
        if (!$taxpayer->phone) return;

        $paymentLine = '';
        if (!empty($paymentInfo['qr_string'])) {
            $paymentLine = "\n\nBayar pajak via QRIS di M-PAD atau mBanking.";
        } elseif (!empty($paymentInfo['va_number'])) {
            $paymentLine = "\n\nBayar via VA: " . $paymentInfo['va_number'] . " (Bank Sultra)";
        }

        $classifName = $taxObject->classification ? $taxObject->classification->name : 'Self-Assessment';
        $transAmt = number_format($transactionAmount, 0, ',', '.');
        $taxAmt = number_format($taxAmount, 0, ',', '.');
        $timeStr = Carbon::now()->format('d M Y H:i');

        $message = "Auto-Deduct M-PAD\n\n"
            . "Halo " . $taxpayer->name . ",\n\n"
            . "Transaksi baru tercatat:\n"
            . "- " . $taxObject->name . "\n"
            . "Nilai: Rp " . $transAmt . "\n"
            . "Pajak (" . $classifName . "): Rp " . $taxAmt . "\n"
            . "Waktu: " . $timeStr
            . $paymentLine
            . "\n\nTerima kasih telah patuh pajak";

        try {
            $this->waGateway->sendMessage($taxpayer->phone, $message);
            $this->logNotification($taxpayer, $taxpayer->phone, 'auto_deduct_receipt', $message, 'sent');
        } catch (\Exception $e) {
            $this->logNotification($taxpayer, $taxpayer->phone, 'auto_deduct_receipt', $message, 'failed', $e->getMessage());
            Log::error('WA Notification Error: ' . $e->getMessage());
        }
    }

    public function sendBillNotification(Bill $bill): void
    {
        $taxpayer = $bill->taxpayer;
        if (!$taxpayer || !$taxpayer->phone) return;

        $dueDays = (int) Carbon::now()->diffInDays($bill->due_date, false);
        if ($dueDays <= 3) {
            $urgency = 'SEGERA';
        } elseif ($dueDays <= 7) {
            $urgency = 'MENDESAK';
        } else {
            $urgency = 'PENGINGAT';
        }

        $total = $bill->amount + ($bill->penalty_amount ?? 0);
        $totalStr = number_format($total, 0, ',', '.');
        $dueStr = $bill->due_date->format('d M Y');

        $message = $urgency . "\n\n"
            . "Halo " . $taxpayer->name . ",\n\n"
            . "Tagihan pajak Anda:\n"
            . "No: " . $bill->bill_number . "\n"
            . "Rp " . $totalStr . "\n"
            . "Jatuh tempo: " . $dueStr;

        if ($bill->penalty_amount > 0) {
            $penaltyStr = number_format($bill->penalty_amount, 0, ',', '.');
            $message .= "\nDenda: Rp " . $penaltyStr;
        }

        $message .= "\n\nBayar via:\n"
            . "- Aplikasi M-PAD\n"
            . "- Teller Bank Sultra\n"
            . "- QRIS/VA\n"
            . "- https://sipanda.online\n\n"
            . "Bayar sebelum jatuh tempo ya";

        try {
            $this->waGateway->sendMessage($taxpayer->phone, $message);
            $this->logNotification($taxpayer, $taxpayer->phone, 'bill_reminder', $message, 'sent');
        } catch (\Exception $e) {
            $this->logNotification($taxpayer, $taxpayer->phone, 'bill_reminder', $message, 'failed', $e->getMessage());
        }
    }

    public function sendPaymentReceipt(Taxpayer $taxpayer, TaxObject $taxObject, float $amount, ?string $ntpd): void
    {
        if (!$taxpayer->phone) return;

        $amtStr = number_format($amount, 0, ',', '.');
        $timeStr = Carbon::now()->format('d M Y H:i');
        $ntpdLine = $ntpd ? "\nNTPD: " . $ntpd : '';

        $message = "PEMBAYARAN BERHASIL\n\n"
            . "Halo " . $taxpayer->name . ",\n\n"
            . "Pembayaran pajak diterima:\n"
            . "- " . $taxObject->name . "\n"
            . "Rp " . $amtStr
            . $ntpdLine
            . "\nWaktu: " . $timeStr
            . "\n\nPajak Anda sudah lunas\n"
            . "Cek riwayat: https://sipanda.online";

        try {
            $this->waGateway->sendMessage($taxpayer->phone, $message);
            $this->logNotification($taxpayer, $taxpayer->phone, 'payment_receipt', $message, 'sent');
        } catch (\Exception $e) {
            $this->logNotification($taxpayer, $taxpayer->phone, 'payment_receipt', $message, 'failed', $e->getMessage());
        }
    }

    public function sendBulkBillReminders(): array
    {
        $now = Carbon::now();
        $sent = 0;
        $failed = 0;

        $bills = Bill::where('status', 'pending')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$now->copy()->addDays(1), $now->copy()->addDays(7)])
            ->with(['taxpayer', 'taxObject'])
            ->get();

        foreach ($bills as $bill) {
            try {
                $this->sendBillNotification($bill);
                $sent++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        $overdueBills = Bill::where('status', 'pending')
            ->where('due_date', '<', $now)
            ->where('penalty_amount', '>', 0)
            ->with(['taxpayer', 'taxObject'])
            ->get();

        foreach ($overdueBills as $bill) {
            try {
                $this->sendBillNotification($bill);
                $sent++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'total' => count($bills) + count($overdueBills)];
    }

    private function calculateTax(?string $formula, float $amount): float
    {
        if (!$formula) return 0;

        if (str_contains($formula, 'omzet')) {
            $rate = $this->extractRate($formula);
            return round($amount * $rate, 2);
        }
        if (str_contains($formula, 'tagihan')) {
            $rate = $this->extractRate($formula);
            return round($amount * $rate, 2);
        }
        if (str_contains($formula, 'volume')) {
            return 0;
        }

        try {
            $parser = app(\App\Services\FormulaParserService::class);
            return (float) $parser->calculate($formula, ['omzet' => $amount, 'tagihan' => $amount]);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function extractRate(string $formula): float
    {
        preg_match('/\\* (\\d+\\.?\\d*)/', $formula, $matches);
        return (float) ($matches[1] ?? 0.1);
    }

    private function logNotification(Taxpayer $taxpayer, string $phone, string $type, string $message, string $status, ?string $error = null): void
    {
        NotificationLog::create([
            'taxpayer_id' => $taxpayer->id,
            'phone' => $phone,
            'channel' => 'whatsapp',
            'type' => $type,
            'message_preview' => Str::limit($message, 200),
            'status' => $status,
            'error_message' => $error,
            'sent_at' => $status === 'sent' ? Carbon::now() : null,
        ]);
    }
}
