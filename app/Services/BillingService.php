<?php

namespace App\Services;

use App\Models\TaxObject;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BillingService
{
    /**
     * Calculate pending billing periods for a tax object
     * 
     * @param TaxObject $taxObject
     * @return Collection
     */
    public function getPendingPeriods(TaxObject $taxObject): Collection
    {
        $startDate = $taxObject->created_at->startOfMonth();
        $currentDate = Carbon::now()->startOfMonth();
        
        $periods = collect();
        $tempDate = $startDate->copy();
        
        // Load successful payments for this object
        $paidPeriods = Payment::where('tax_object_id', $taxObject->id)
            ->where('status', 'success')
            ->pluck('billing_period')
            ->toArray();

        while ($tempDate->lte($currentDate)) {
            $periodString = $tempDate->format('Y-m');
            
            if (!in_array($periodString, $paidPeriods)) {
                $periods->push([
                    'period' => $periodString,
                    'label' => $tempDate->translatedFormat('F Y'),
                    'status' => 'unpaid',
                    'amount' => $this->calculateAmountForPeriod($taxObject, $tempDate),
                ]);
            }
            
            $tempDate->addMonth();
        }

        return $periods;
    }

    /**
     * Calculate the amount for a specific period
     */
    private function calculateAmountForPeriod(TaxObject $taxObject, Carbon $date): float
    {
        $formulaParser = app(\App\Services\FormulaParserService::class);
        
        // 1. Try to find a specific rate for this classification and zone
        $rate = \App\Models\RetributionRate::where('retribution_type_id', $taxObject->retribution_type_id)
            ->where('retribution_classification_id', $taxObject->retribution_classification_id)
            ->where(function($q) use ($taxObject) {
                if ($taxObject->zone_id) {
                    $q->where('zone_id', $taxObject->zone_id);
                } else {
                    $q->whereNull('zone_id');
                }
            })
            ->where('is_active', true)
            ->first();

        // 2. Determine base variables for formula
        $variables = array_merge(
            $taxObject->metadata ?? [], 
            [
                'amount' => $rate ? $rate->amount : 0,
                'tariff' => $rate ? ($rate->amount / 100) : 0,
            ]
        );

        // 3. Check for dynamic formula in Rate first
        if ($rate && $rate->calculation_formula) {
            return $formulaParser->calculate($rate->calculation_formula, $variables);
        }

        // 4. Check for dynamic formula in Classification
        $classification = $taxObject->classification;
        if ($classification && $classification->calculation_formula) {
            return $formulaParser->calculate($classification->calculation_formula, $variables);
        }

        // 5. Fallback to fixed rate amount
        if ($rate) {
            return $rate->amount;
        }

        // 6. Final fallback to base amount of the type
        return (float) ($taxObject->retributionType->base_amount ?? 0);
    }
}
