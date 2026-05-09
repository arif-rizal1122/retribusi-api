<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\TaxObject;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class BillCreationService
{
    public function __construct(
        private TaxCalculationService $taxCalculationService,
        private BillPeriodService $periodService
    ) {
    }

    public function createForTaxObject(
        TaxObject $taxObject,
        $user = null,
        ?string $period = null,
        array $variables = [],
        array $metadata = [],
        Carbon|string|null $dueDate = null,
        string $source = 'system',
        ?float $amountOverride = null,
        bool $returnExisting = true
    ): Bill {
        $taxObject->loadMissing(['retributionType', 'classification', 'taxpayer']);
        $cycle = $taxObject->retributionType->billing_cycle ?? 'monthly';
        $periodInfo = $this->periodService->resolve($period, $cycle);

        $existing = $this->findExistingBill($taxObject, $periodInfo['period']);
        if ($existing) {
            if ($returnExisting) {
                return $existing;
            }

            throw ValidationException::withMessages([
                'period' => "Tagihan untuk periode {$periodInfo['period']} sudah ada di sistem.",
            ]);
        }

        $calculation = $amountOverride === null
            ? $this->taxCalculationService->calculateForObject($taxObject, $variables)
            : [
                'amount' => $amountOverride,
                'source' => 'amount_override',
                'formula' => null,
                'variables' => $variables,
            ];

        $resolvedDueDate = $dueDate
            ? ($dueDate instanceof Carbon ? $dueDate : Carbon::parse($dueDate))
            : $periodInfo['due_date'];

        $billMetadata = array_merge($metadata, [
            'source' => $metadata['source'] ?? $source,
            'period_label' => $periodInfo['label'],
            'billing_cycle' => $cycle,
            'calculation' => $calculation,
        ]);

        return Bill::create([
            'user_id' => $user?->id,
            'taxpayer_id' => $taxObject->taxpayer_id,
            'tax_object_id' => $taxObject->id,
            'opd_id' => $taxObject->opd_id,
            'retribution_type_id' => $taxObject->retribution_type_id,
            'retribution_classification_id' => $taxObject->retribution_classification_id,
            'bill_number' => 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
            'amount' => (float) $calculation['amount'],
            'status' => 'pending',
            'period' => $periodInfo['period'],
            'period_start' => $periodInfo['period_start'],
            'period_end' => $periodInfo['period_end'],
            'metadata' => $billMetadata,
            'due_date' => $resolvedDueDate,
        ]);
    }

    public function findExistingBill(TaxObject $taxObject, string $period): ?Bill
    {
        return Bill::withoutGlobalScopes()
            ->where('tax_object_id', $taxObject->id)
            ->where('period', $period)
            ->whereIn('status', ['pending', 'overdue', 'unpaid', 'lunas', 'paid'])
            ->first();
    }
}
