<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Jobs\ExecuteAutoDebitAFT;
use App\Models\AutoDeductLog;
use App\Models\Bill;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Services\Payment\Snap\BankSultraAftReadiness;
use App\Services\TaxCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KalkulatorBisnisController extends Controller
{
    /**
     * Menerima input omzet harian dari Kalkulator Bisnis.
     * Mengkalkulasi pajak PBJT dari tarif backend dan membuat antrean AFT.
     */
    public function submitOmzet(
        Request $request,
        BankSultraAftReadiness $readiness,
        TaxCalculationService $taxCalculationService
    ) {
        $validated = $request->validate([
            'transaction_amount' => 'required|numeric|min:1',
            'tax_object_id' => 'required|integer|exists:tax_objects,id',
            'bill_id' => 'required|integer|exists:bills,id',
        ]);

        $taxpayer = $request->user();

        if (! $taxpayer instanceof Taxpayer || ! $this->isAuthorizedMerchant($taxpayer)) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint AFT hanya untuk wajib pajak merchant.',
            ], 403);
        }

        if (! $readiness->available()) {
            return response()->json([
                'success' => false,
                'message' => $readiness->publicMessage(),
            ], 503);
        }

        $taxObject = TaxObject::with(['retributionType', 'classification'])
            ->whereKey($validated['tax_object_id'])
            ->where('taxpayer_id', $taxpayer->id)
            ->first();
        $bill = Bill::with(['retributionType', 'classification'])
            ->whereKey($validated['bill_id'])
            ->where('taxpayer_id', $taxpayer->id)
            ->when($taxObject, fn ($query) => $query->where('tax_object_id', $taxObject->id))
            ->first();

        $this->validateMerchantAftContract($taxpayer, $taxObject, $bill);

        $omzet = (float) $validated['transaction_amount'];
        $calculation = $taxCalculationService->calculateForObject($taxObject, [
            'turnover_amount' => $omzet,
            'omzet' => $omzet,
        ]);
        $taxAmount = round((float) $calculation['amount'], 2);

        $this->validateOfficialAmount($bill, $taxAmount);

        $contract = $this->aftContract($taxpayer, $taxObject, $bill);

        $log = AutoDeductLog::create([
            'city_id' => $contract['city_id'],
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'bill_id' => $bill->id,
            'source' => 'm-pad_mobile_calculator',
            'transaction_type' => 'pbjt_tax_deduction',
            'transaction_amount' => $omzet,
            'tax_amount' => $taxAmount,
            'deducted_amount' => $taxAmount,
            'reference_number' => 'AFT-'.strtoupper(Str::random(10)),
            'payment_channel' => 'BPD_SULTRA_DIRECT_DEBIT',
            'metadata' => [
                'tax_calculation' => [
                    'source' => 'backend_tariff',
                    'engine_source' => $calculation['source'] ?? null,
                    'formula' => $calculation['formula'] ?? null,
                    'rate_id' => $calculation['rate_id'] ?? null,
                ],
                'bank_sultra_aft' => $contract,
            ],
            'status' => 'pending',
            'escrow_settlement_status' => 'pending',
        ]);

        ExecuteAutoDebitAFT::dispatch($log->id);

        return response()->json([
            'success' => true,
            'message' => 'Omzet berhasil dicatat. Proses AFT telah dimulai.',
            'data' => [
                'reference_number' => $log->reference_number,
                'tax_amount' => $taxAmount,
                'omzet' => $omzet,
                'bill_id' => $bill->id,
                'tax_object_id' => $taxObject->id,
            ],
        ], 201);
    }

    private function validateMerchantAftContract(Taxpayer $taxpayer, ?TaxObject $taxObject, ?Bill $bill): void
    {
        $errors = [];

        if (! $taxObject) {
            $errors['tax_object_id'][] = 'Objek pajak tidak ditemukan untuk wajib pajak ini.';
        } elseif (! $this->isApprovedActiveTaxObject($taxObject)) {
            $errors['tax_object_id'][] = 'Objek pajak belum aktif atau belum disetujui untuk AFT.';
        }

        if (! $bill) {
            $errors['bill_id'][] = 'SKDR/tagihan tidak ditemukan untuk objek pajak ini.';
        } elseif (! $this->isApprovedSkdr($bill)) {
            $errors['bill_id'][] = 'SKDR/tagihan belum disetujui untuk AFT Bank Sultra.';
        }

        if ($taxObject && $bill) {
            $contract = $this->aftContract($taxpayer, $taxObject, $bill);

            if ($contract['merchant_id'] === '') {
                $errors['tax_object_id'][] = 'Merchant ID resmi Bank Sultra belum tersedia.';
            }

            if ($contract['city_id'] === null) {
                $errors['tax_object_id'][] = 'City resmi AFT belum tersedia pada profil objek/SKDR.';
            }

            if ($contract['contract_reference'] === '') {
                $errors['bill_id'][] = 'Referensi kontrak SKDR AFT belum tersedia.';
            }

            if ($contract['source_profile_ref'] === '') {
                $errors['bill_id'][] = 'Referensi rekening sumber merchant belum tersedia pada profil/kontrak resmi.';
            }

            if ($contract['destination_profile_ref'] === '') {
                $errors['bill_id'][] = 'Referensi rekening tujuan penampung belum tersedia pada profil/kontrak resmi.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function validateOfficialAmount(Bill $bill, float $taxAmount): void
    {
        if ($taxAmount <= 0) {
            throw ValidationException::withMessages([
                'transaction_amount' => ['Nominal pajak hasil tarif backend harus lebih dari nol.'],
            ]);
        }

        if (abs((float) $bill->amount - $taxAmount) > 0.01) {
            throw ValidationException::withMessages([
                'transaction_amount' => ['Nominal pajak tidak sesuai dengan SKDR/tagihan resmi.'],
            ]);
        }
    }

    private function isAuthorizedMerchant(Taxpayer $taxpayer): bool
    {
        if (! $taxpayer->is_active) {
            return false;
        }

        return strtolower((string) data_get($taxpayer->metadata, 'role')) === 'merchant'
            && filter_var(data_get($taxpayer->metadata, 'aft_enabled'), FILTER_VALIDATE_BOOLEAN);
    }

    private function isApprovedActiveTaxObject(TaxObject $taxObject): bool
    {
        return $taxObject->is_active
            && strtolower((string) $taxObject->status) === 'active'
            && $taxObject->approved_at !== null;
    }

    private function isApprovedSkdr(Bill $bill): bool
    {
        $rawStatus = strtolower((string) $bill->getRawOriginal('status'));
        $skdrStatus = strtolower((string) (
            data_get($bill->metadata, 'skdr_status')
            ?? data_get($bill->metadata, 'skdr.status')
            ?? data_get($bill->metadata, 'official_document.skdr_status')
        ));

        return $rawStatus === 'pending'
            && in_array($skdrStatus, ['approved', 'issued', 'signed'], true);
    }

    private function aftContract(Taxpayer $taxpayer, TaxObject $taxObject, Bill $bill): array
    {
        return [
            'merchant_id' => $this->firstFilled([
                data_get($bill->metadata, 'bank_sultra_aft.merchant_id'),
                data_get($taxObject->metadata, 'bank_sultra_aft.merchant_id'),
                data_get($taxpayer->metadata, 'bank_sultra_aft.merchant_id'),
            ]),
            'contract_reference' => $this->firstFilled([
                data_get($bill->metadata, 'bank_sultra_aft.contract_reference'),
                data_get($bill->metadata, 'skdr.number'),
                $bill->bill_number,
            ]),
            'source_profile_ref' => $this->firstFilled([
                data_get($bill->metadata, 'bank_sultra_aft.source_profile_ref'),
                data_get($taxObject->metadata, 'bank_sultra_aft.source_profile_ref'),
                data_get($taxpayer->metadata, 'bank_sultra_aft.source_profile_ref'),
            ]),
            'destination_profile_ref' => $this->firstFilled([
                data_get($bill->metadata, 'bank_sultra_aft.destination_profile_ref'),
                data_get($taxObject->metadata, 'bank_sultra_aft.destination_profile_ref'),
                data_get($bill->classification?->bank_accounts, '0.account_ref'),
            ]),
            'city_id' => $this->cityId($taxpayer, $taxObject, $bill),
            'skdr_number' => $bill->bill_number,
        ];
    }

    private function cityId(Taxpayer $taxpayer, TaxObject $taxObject, Bill $bill): ?int
    {
        $cityId = $this->firstFilled([
            data_get($bill->metadata, 'bank_sultra_aft.city_id'),
            data_get($taxObject->metadata, 'bank_sultra_aft.city_id'),
            data_get($taxpayer->metadata, 'bank_sultra_aft.city_id'),
        ]);

        return ctype_digit($cityId) ? (int) $cityId : null;
    }

    private function firstFilled(array $values): string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * Mengambil riwayat AFT (Auto Deduct Log) milik Wajib Pajak ini.
     */
    public function getAftAvailability(Request $request, BankSultraAftReadiness $readiness)
    {
        $this->merchantTaxpayerOrAbort($request);
        $available = $readiness->available();

        return response()->json([
            'success' => true,
            'data' => [
                'available' => $available,
                'status' => $available ? 'available' : 'unavailable',
                'can_submit' => $available,
                'message' => $available ? 'AFT Bank Sultra tersedia.' : $readiness->publicMessage(),
                'required_fields' => ['tax_object_id', 'bill_id', 'transaction_amount'],
            ],
        ]);
    }

    /**
     * Mengambil riwayat AFT (Auto Deduct Log) milik Wajib Pajak ini.
     */
    public function getAftHistory(Request $request, BankSultraAftReadiness $readiness)
    {
        $taxpayer = $this->merchantTaxpayerOrAbort($request);

        $history = AutoDeductLog::with(['bill:id,bill_number,period,due_date', 'taxObject:id,name,nop'])
            ->where('taxpayer_id', $taxpayer->id)
            ->where('source', 'm-pad_mobile_calculator')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalTaxDeductedThisMonth = AutoDeductLog::where('taxpayer_id', $taxpayer->id)
            ->where('source', 'm-pad_mobile_calculator')
            ->where('status', 'success')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('deducted_amount');

        $totalOmzet = AutoDeductLog::where('taxpayer_id', $taxpayer->id)
            ->where('source', 'm-pad_mobile_calculator')
            ->sum('transaction_amount');

        $statusCounts = $history
            ->map(fn (AutoDeductLog $log) => $this->mobileAftStatus($log))
            ->countBy();

        $available = $readiness->available();

        return response()->json([
            'success' => true,
            'data' => [
                'availability' => [
                    'available' => $available,
                    'status' => $available ? 'available' : 'unavailable',
                    'can_submit' => $available,
                    'message' => $available ? 'AFT Bank Sultra tersedia.' : $readiness->publicMessage(),
                ],
                'history' => $history
                    ->map(fn (AutoDeductLog $log) => $this->mobileAftHistoryPayload($log))
                    ->values(),
                'stats' => [
                    'total_tax_this_month' => (float) $totalTaxDeductedThisMonth,
                    'total_omzet' => (float) $totalOmzet,
                    'pending_count' => (int) ($statusCounts['pending'] ?? 0),
                    'processing_count' => (int) ($statusCounts['processing'] ?? 0),
                    'success_count' => (int) ($statusCounts['success'] ?? 0),
                    'failed_count' => (int) ($statusCounts['failed'] ?? 0),
                    'cancelled_count' => (int) ($statusCounts['cancelled'] ?? 0),
                ],
            ],
        ]);
    }

    private function merchantTaxpayerOrAbort(Request $request): Taxpayer
    {
        $taxpayer = $request->user();
        if (! $taxpayer instanceof Taxpayer || ! $this->isAuthorizedMerchant($taxpayer)) {
            abort(response()->json([
                'success' => false,
                'message' => 'Endpoint AFT hanya untuk wajib pajak merchant.',
            ], 403));
        }

        return $taxpayer;
    }

    private function mobileAftHistoryPayload(AutoDeductLog $log): array
    {
        $status = $this->mobileAftStatus($log);

        return [
            'reference_number' => $log->reference_number,
            'status' => $status,
            'status_label' => $this->mobileAftStatusLabel($status),
            'created_at' => $log->created_at?->toIso8601String(),
            'processed_at' => $log->processed_at?->toIso8601String(),
            'transaction_amount' => (float) $log->transaction_amount,
            'tax_amount' => (float) $log->tax_amount,
            'bill_reference' => $log->bill?->bill_number,
            'period' => $log->bill?->period,
            'tax_object' => $log->taxObject ? [
                'name' => $log->taxObject->name,
                'nop' => $log->taxObject->nop,
            ] : null,
            'can_retry' => false,
            'can_cancel' => false,
        ];
    }

    private function mobileAftStatus(AutoDeductLog $log): string
    {
        $status = strtolower((string) $log->status);

        if (in_array($status, ['success', 'settled', 'paid'], true)) {
            return 'success';
        }

        if (in_array($status, ['failed', 'error', 'rejected'], true)) {
            return 'failed';
        }

        if (in_array($status, ['cancelled', 'canceled', 'void', 'voided'], true)) {
            return 'cancelled';
        }

        if (
            data_get($log->metadata, 'bank_sultra_aft.payment_request') !== null
            || data_get($log->metadata, 'bank_sultra_aft.payment_response') !== null
            || filled($log->failure_reason)
        ) {
            return 'processing';
        }

        return 'pending';
    }

    private function mobileAftStatusLabel(string $status): string
    {
        return match ($status) {
            'processing' => 'Sedang diproses Bank Sultra',
            'success' => 'Berhasil',
            'failed' => 'Gagal',
            'cancelled' => 'Dibatalkan',
            default => 'Menunggu diproses',
        };
    }
}
