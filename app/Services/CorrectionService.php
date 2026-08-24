<?php

namespace App\Services;

use App\Models\AutoDeductLog;
use App\Models\CorrectionLog;
use App\Models\NotificationLog;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\TaxTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CorrectionService
{
    protected AutoDeductService $autoDeductService;
    protected WaGatewayService $waGateway;

    public function __construct()
    {
        $this->autoDeductService = app(AutoDeductService::class);
        $this->waGateway = app(WaGatewayService::class);
    }

    /**
     * VOID TRANSAKSI - Batalkan transaksi yang sudah tercatat
     * Transaksi dianggap tidak pernah terjadi, pajak dibatalkan
     */
    public function voidTransaction(
        TaxTransaction $originalTransaction,
        string $reason,
        ?array $details = null
    ): array {
        return DB::transaction(function () use ($originalTransaction, $reason, $details) {
            $taxObject = $originalTransaction->taxObject;
            $taxpayer = $originalTransaction->taxpayer;

            // 1. Buat correction log
            $correctionLog = CorrectionLog::create([
                'tax_transaction_id' => $originalTransaction->id,
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $taxObject->id,
                'type' => 'void',
                'trigger' => 'manual',
                'original_amount' => $originalTransaction->amount,
                'new_amount' => 0,
                'original_tax' => $originalTransaction->tax_amount,
                'new_tax' => 0,
                'difference' => -$originalTransaction->tax_amount,
                'details' => $details,
                'status' => 'applied',
                'reason' => $reason,
                'approved_at' => Carbon::now(),
            ]);

            // 2. Tandai transaksi original sebagai void
            $originalTransaction->update([
                'correction_type' => 'void',
                'corrected_at' => Carbon::now(),
                'metadata' => array_merge($originalTransaction->metadata ?? [], [
                    'void_reason' => $reason,
                    'void_at' => Carbon::now()->toDateTimeString(),
                    'correction_log_id' => $correctionLog->id,
                ]),
            ]);

            // 3. Cari dan void auto_deduct_log terkait
            $deductLog = AutoDeductLog::where('reference_number', $originalTransaction->metadata['reference_number'] ?? null)
                ->orWhere(function ($q) use ($originalTransaction) {
                    $q->where('tax_transaction_id', $originalTransaction->id);
                })
                ->first();

            if ($deductLog) {
                $deductLog->update([
                    'status' => 'voided',
                    'correction_type' => 'void',
                    'correction_note' => $reason,
                    'corrected_at' => Carbon::now(),
                ]);
            }

            // 4. Jika ada bill terkait, batalkan pembayarannya
            if ($originalTransaction->metadata['bill_id'] ?? null) {
                $bill = \App\Models\Bill::find($originalTransaction->metadata['bill_id']);
                if ($bill && $bill->status === 'lunas') {
                    $bill->update(['status' => 'voided']);
                }
            }

            // 5. Notifikasi ke wajib pajak
            if ($taxpayer->phone) {
                $this->sendCorrectionNotification($taxpayer, $taxObject, 'void', $originalTransaction->amount, $reason);
            }

            return [
                'status' => 'success',
                'correction_log_id' => $correctionLog->id,
                'message' => 'Transaksi berhasil dibatalkan (void)',
                'details' => [
                    'original_amount' => (float) $originalTransaction->amount,
                    'tax_refund' => (float) $originalTransaction->tax_amount,
                ]
            ];
        });
    }

    /**
     * KOREKSI HARGA TRANSAKSI - Ubah nominal transaksi
     * Pajak dihitung ulang berdasarkan nominal baru
     */
    public function adjustTransactionPrice(
        TaxTransaction $originalTransaction,
        float $newAmount,
        string $reason,
        ?array $details = null
    ): array {
        return DB::transaction(function () use ($originalTransaction, $newAmount, $reason, $details) {
            $taxObject = $originalTransaction->taxObject;
            $taxpayer = $originalTransaction->taxpayer;
            $classification = $taxObject->classification;

            // 1. Hitung pajak baru berdasarkan nominal baru
            $formula = $classification ? $classification->calculation_formula : null;
            $newTax = $this->calculateTaxFromFormula($formula, $newAmount);
            $oldTax = (float) $originalTransaction->tax_amount;
            $taxDiff = $newTax - $oldTax;

            // 2. Buat correction log
            $correctionLog = CorrectionLog::create([
                'tax_transaction_id' => $originalTransaction->id,
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $taxObject->id,
                'type' => 'price_adjustment',
                'trigger' => 'manual',
                'original_amount' => $originalTransaction->amount,
                'new_amount' => $newAmount,
                'original_tax' => $oldTax,
                'new_tax' => $newTax,
                'difference' => $taxDiff,
                'details' => $details,
                'status' => 'applied',
                'reason' => $reason,
                'approved_at' => Carbon::now(),
            ]);

            // 3. Update transaksi original
            $originalTransaction->update([
                'correction_type' => 'adjustment',
                'original_amount' => $originalTransaction->amount,
                'original_tax_amount' => $oldTax,
                'corrected_amount' => $newAmount,
                'amount' => $newAmount,
                'tax_amount' => $newTax,
                'tax_difference' => $taxDiff,
                'corrected_at' => Carbon::now(),
                'correction_reason' => $reason,
                'metadata' => array_merge($originalTransaction->metadata ?? [], [
                    'price_adjustment' => [
                        'old_amount' => $originalTransaction->amount,
                        'new_amount' => $newAmount,
                        'old_tax' => $oldTax,
                        'new_tax' => $newTax,
                        'reason' => $reason,
                        'adjusted_at' => Carbon::now()->toDateTimeString(),
                    ]
                ]),
            ]);

            // 4. Update auto_deduct_log terkait
            $deductLog = AutoDeductLog::where('tax_transaction_id', $originalTransaction->id)->first();
            if ($deductLog) {
                $deductLog->update([
                    'status' => 'adjusted',
                    'correction_type' => 'price_adjustment',
                    'original_transaction_amount' => $originalTransaction->getOriginal('amount'),
                    'corrected_transaction_amount' => $newAmount,
                    'tax_recalculated' => $newTax,
                    'tax_difference' => $taxDiff,
                    'correction_note' => $reason,
                    'corrected_at' => Carbon::now(),
                ]);
            }

            // 5. Jika ada bill, update bill amount
            if ($originalTransaction->metadata['bill_id'] ?? null) {
                $bill = \App\Models\Bill::find($originalTransaction->metadata['bill_id']);
                if ($bill) {
                    $bill->update([
                        'amount' => $newTax,
                        'metadata' => array_merge($bill->metadata ?? [], [
                            'auto_adjusted' => true,
                            'adjusted_at' => Carbon::now()->toDateTimeString(),
                            'adjustment_reason' => $reason,
                        ]),
                    ]);
                }
            }

            // 6. Notifikasi koreksi
            if ($taxpayer->phone) {
                $diffText = $taxDiff > 0
                    ? "Pajak bertambah Rp " . number_format($taxDiff, 0, ',', '.')
                    : "Pajak berkurang Rp " . number_format(abs($taxDiff), 0, ',', '.');
                $this->sendAdjustmentNotification($taxpayer, $taxObject, $originalTransaction->amount, $newAmount, $oldTax, $newTax, $reason);
            }

            return [
                'status' => 'success',
                'correction_log_id' => $correctionLog->id,
                'message' => 'Harga transaksi berhasil dikoreksi',
                'details' => [
                    'old_amount' => (float) $originalTransaction->getOriginal('amount'),
                    'new_amount' => $newAmount,
                    'old_tax' => $oldTax,
                    'new_tax' => $newTax,
                    'tax_difference' => $taxDiff,
                ]
            ];
        });
    }

    /**
     * REFUND SEBAGIAN - Kembalikan sebagian transaksi
     */
    public function partialRefund(
        TaxTransaction $originalTransaction,
        float $refundAmount,
        string $reason
    ): array {
        $newAmount = (float) $originalTransaction->amount - $refundAmount;
        if ($newAmount < 0) {
            return ['status' => 'error', 'message' => 'Refund melebihi nilai transaksi'];
        }

        return $this->adjustTransactionPrice(
            $originalTransaction,
            $newAmount,
            "Refund sebagian: {$reason}",
            ['refund_amount' => $refundAmount, 'type' => 'partial_refund']
        );
    }

    /**
     * RIWAYAT KOREKSI UNTUK SATU TRANSAKSI
     */
    public function getCorrectionHistory(TaxTransaction $transaction): array
    {
        $corrections = CorrectionLog::where('tax_transaction_id', $transaction->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'transaction' => $transaction,
            'corrections' => $corrections,
            'total_corrections' => $corrections->count(),
            'net_tax_difference' => (float) $corrections->sum('difference'),
        ];
    }

    // ===== PRIVATE HELPERS =====

    private function calculateTaxFromFormula(?string $formula, float $amount): float
    {
        if (!$formula) return 0;

        if (str_contains($formula, 'omzet')) {
            preg_match('/\* (\d+\.?\d*)/', $formula, $matches);
            $rate = (float) ($matches[1] ?? 0.1);
            return round($amount * $rate, 2);
        }
        if (str_contains($formula, 'tagihan')) {
            preg_match('/\* (\d+\.?\d*)/', $formula, $matches);
            $rate = (float) ($matches[1] ?? 0.1);
            return round($amount * $rate, 2);
        }

        return 0;
    }

    private function sendCorrectionNotification(
        Taxpayer $taxpayer, TaxObject $taxObject,
        string $type, float $amount, string $reason
    ): void {
        $amtStr = number_format($amount, 0, ',', '.');
        $typeLabel = $type === 'void' ? 'PEMBATALAN' : 'KOREKSI';

        $message = "KOREKSI TRANSAKSI - {$typeLabel}\n\n"
            . "Halo " . $taxpayer->name . ",\n\n"
            . "Transaksi berikut telah dibatalkan:\n"
            . "- " . $taxObject->name . "\n"
            . "Nilai: Rp " . $amtStr . "\n"
            . "Alasan: " . $reason . "\n"
            . "Waktu: " . Carbon::now()->format('d M Y H:i') . "\n\n"
            . "Pajak terkait transaksi ini juga dibatalkan.\n"
            . "Jika ada pertanyaan, hubungi Bapenda Kota Baubau.";

        try {
            $this->waGateway->sendMessage($taxpayer->phone, $message);
            $this->logNotification($taxpayer, 'correction_notice', $message);
        } catch (\Exception $e) {
            Log::error('Correction WA Notification Error: ' . $e->getMessage());
        }
    }

    private function sendAdjustmentNotification(
        Taxpayer $taxpayer, TaxObject $taxObject,
        float $oldAmount, float $newAmount,
        float $oldTax, float $newTax, string $reason
    ): void {
        $oldAmtStr = number_format($oldAmount, 0, ',', '.');
        $newAmtStr = number_format($newAmount, 0, ',', '.');
        $oldTaxStr = number_format($oldTax, 0, ',', '.');
        $newTaxStr = number_format($newTax, 0, ',', '.');

        $message = "KOREKSI TRANSAKSI - Penyesuaian Harga\n\n"
            . "Halo " . $taxpayer->name . ",\n\n"
            . "Transaksi berikut telah disesuaikan:\n"
            . "- " . $taxObject->name . "\n\n"
            . "Nilai sebelumnya: Rp " . $oldAmtStr . "\n"
            . "Nilai baru:       Rp " . $newAmtStr . "\n"
            . "Pajak sebelumnya: Rp " . $oldTaxStr . "\n"
            . "Pajak baru:       Rp " . $newTaxStr . "\n"
            . "Alasan: " . $reason . "\n"
            . "Waktu: " . Carbon::now()->format('d M Y H:i') . "\n\n"
            . "Cek riwayat di https://sipanda.online";

        try {
            $this->waGateway->sendMessage($taxpayer->phone, $message);
            $this->logNotification($taxpayer, 'correction_notice', $message);
        } catch (\Exception $e) {
            Log::error('Adjustment WA Notification Error: ' . $e->getMessage());
        }
    }

    private function logNotification(Taxpayer $taxpayer, string $type, string $message): void
    {
        NotificationLog::create([
            'taxpayer_id' => $taxpayer->id,
            'phone' => $taxpayer->phone,
            'channel' => 'whatsapp',
            'type' => $type,
            'message_preview' => Str::limit($message, 200),
            'status' => 'sent',
            'sent_at' => Carbon::now(),
        ]);
    }
}
