<?php

namespace App\Services;

use App\Models\AftRegistration;
use App\Models\AftTransaction;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\Taxpayer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AftSettlementService
 *
 * Menangani proses Auto Fund Transfer (AFT):
 * dana pembayaran diterima terlebih dahulu oleh rekening WP, kemudian bagian
 * Pajak/Retribusi diteruskan otomatis ke rekening tujuan sesuai konfigurasi.
 *
 * Penerusan dana aktual ke bank (mis. Bank Sultra) membutuhkan integrasi
 * direkt debet; service ini membuat catatan (AftTransaction) dan menandai
 * status settlement sebagai simulasi. Integrasi produksi dicolokkan di sini.
 */
class AftSettlementService
{
    /**
     * Tarif default PBJT saat tidak ada tariff_percent pada retribution type.
     */
    public const DEFAULT_PBJT_RATE = 0.10;

    public function getActiveRegistration(Taxpayer $taxpayer): ?AftRegistration
    {
        if (!$taxpayer->aft_enabled) {
            return null;
        }

        return AftRegistration::where('taxpayer_id', $taxpayer->id)
            ->where('approval_status', AftRegistration::APPROVAL_APPROVED)
            ->latest('id')
            ->first();
    }

    /**
     * Hitung bagian Pajak/Retribusi yang menjadi kewajiban dari sekumpulan tagihan.
     */
    public function computeTaxDeduction(Collection $bills): float
    {
        $total = 0.0;

        foreach ($bills as $bill) {
            if (!$bill instanceof Bill) {
                continue;
            }

            $tariff = (float) ($bill->retributionType->tariff_percent ?? 0);
            if ($tariff <= 0) {
                // Fallback PBJT untuk jenis self-assessment
                $classification = $bill->classification;
                if ($classification && $classification->is_self_assessment) {
                    $tariff = self::DEFAULT_PBJT_RATE * 100;
                }
            }

            if ($tariff <= 0) {
                continue;
            }

            $total += ((float) $bill->total_amount) * ($tariff / 100);
        }

        return round($total, 2);
    }

    /**
     * Jalankan AFT setelah pembayaran berhasil.
     *
     * @param PaymentRequest $request
     * @param Collection|Payment[] $payments
     */
    public function process(PaymentRequest $request, Collection $payments, ?int $approvedBy = null): ?AftTransaction
    {
        $taxpayer = $request->taxpayer;
        $registration = $this->getActiveRegistration($taxpayer);

        if (!$registration) {
            return null;
        }

        $bills = $request->bills();
        $transactionAmount = round($payments->sum(fn ($payment) => (float) $payment->amount), 2);
        $taxAmount = $this->computeTaxDeduction($bills);

        if ($transactionAmount <= 0) {
            return null;
        }

        $transaction = null;

        DB::transaction(function () use ($request, $payments, $registration, $transactionAmount, $taxAmount, $approvedBy, &$transaction) {
            // Idempotensi: satu payment request hanya boleh menghasilkan satu AFT.
            if (AftTransaction::where('payment_request_id', $request->id)->exists()) {
                return;
            }

            $firstBill = $request->bills()->first();

            $transaction = AftTransaction::create([
                'payment_id' => $payments->first()?->id,
                'payment_request_id' => $request->id,
                'taxpayer_id' => $request->taxpayer_id,
                'tax_object_id' => $firstBill?->tax_object_id,
                'bill_id' => $firstBill?->id,
                'source' => $this->resolveSource($request->method),
                'transaction_type' => 'payment',
                'transaction_amount' => $transactionAmount,
                'tax_amount' => $taxAmount,
                'deducted_amount' => $taxAmount,
                'beneficiary_account' => $registration->beneficiary_account,
                'beneficiary_bank' => $registration->bank,
                'reference_number' => $request->reference_number,
                'payment_channel' => $request->method,
                'status' => AftTransaction::STATUS_SUCCESS,
                'escrow_settlement_status' => AftTransaction::ESCROW_SETTLED,
                'settled_at' => now(),
                'processed_at' => now(),
                'metadata' => [
                    'payment_request_id' => $request->id,
                    'payment_ids' => $payments->pluck('id')->all(),
                    'approved_by' => $approvedBy,
                    'source' => AftTransaction::SOURCE_OFFICER,
                ],
            ]);

            Log::info('AFT settled', [
                'auto_deduct_log_id' => $transaction->id,
                'taxpayer_id' => $request->taxpayer_id,
                'transaction_amount' => $transactionAmount,
                'tax_amount' => $taxAmount,
            ]);
        });

        return $transaction;
    }

    /**
     * Peta metode payment request ke vocabulary source pada auto_deduct_logs
     * (pos, qris, va, transfer, manual).
     */
    protected function resolveSource(string $method): string
    {
        return match ($method) {
            PaymentRequest::METHOD_BRI_VA => 'va',
            PaymentRequest::METHOD_QRIS => 'qris',
            PaymentRequest::METHOD_OFFICER => 'pos',
            default => 'manual',
        };
    }

    /**
     * Catat omzet harian merchant (Business Calculator) dan jalankan AFT otomatis.
     */
    public function recordOmzet(Taxpayer $taxpayer, float $transactionAmount): ?AftTransaction
    {
        $registration = $this->getActiveRegistration($taxpayer);
        if (!$registration) {
            return null;
        }

        // Pakai tarif PBJT default 10% bila belum ada konfigurasi tariff
        $taxRate = self::DEFAULT_PBJT_RATE;
        $taxAmount = round($transactionAmount * $taxRate, 2);

        return AftTransaction::create([
            'payment_id' => null,
            'taxpayer_id' => $taxpayer->id,
            'source' => 'manual',
            'transaction_type' => 'sale',
            'transaction_amount' => $transactionAmount,
            'tax_amount' => $taxAmount,
            'deducted_amount' => $taxAmount,
            'beneficiary_account' => $registration->beneficiary_account,
            'beneficiary_bank' => $registration->bank,
            'status' => AftTransaction::STATUS_SUCCESS,
            'escrow_settlement_status' => AftTransaction::ESCROW_SETTLED,
            'settled_at' => now(),
            'processed_at' => now(),
            'metadata' => [
                'source' => AftTransaction::SOURCE_MERCHANT_OMZET,
            ],
        ]);
    }
}
