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
        $type = $taxObject->retributionType;
        $cycle = $type->billing_cycle ?? 'monthly';
        
        $startDate = $taxObject->created_at->startOf($this->getCarbonUnit($cycle));
        $currentDate = Carbon::now()->startOf($this->getCarbonUnit($cycle));
        
        $periods = collect();
        $tempDate = $startDate->copy();
        
        // Load successful payments for this object
        $paidPeriods = Payment::where('tax_object_id', $taxObject->id)
            ->where('status', 'success')
            ->pluck('billing_period')
            ->toArray();

        while ($tempDate->lte($currentDate)) {
            $periodString = $this->getPeriodString($tempDate, $cycle);
            
            if (!in_array($periodString, $paidPeriods)) {
                $classification = $taxObject->classification;
                $isSelfAssessment = $classification ? $classification->is_self_assessment : false;
                
                $report = null;
                if ($isSelfAssessment) {
                    $report = \App\Models\MonthlyReport::where('tax_object_id', $taxObject->id)
                        ->where('period', $periodString)
                        ->first();
                }

                $amount = $this->calculateAmountForPeriod($taxObject, $tempDate, $report);
                $dueDate = $this->getDueDate($tempDate, $cycle);
                
                $virtualPenalty = 0;
                if (Carbon::now()->gt($dueDate)) {
                    $diffInMonths = $dueDate->diffInMonths(Carbon::now());
                    if (Carbon::now()->day > $dueDate->day) $diffInMonths++;
                    if ($diffInMonths === 0) $diffInMonths = 1;

                    $parser = app(\App\Services\FormulaParserService::class);
                    $virtualPenalty = $parser->calculatePenalty($amount, $diffInMonths, 'stpd');
                }

                $periods->push([
                    'period' => $periodString,
                    'label' => $this->getPeriodLabel($tempDate, $cycle),
                    'status' => ($isSelfAssessment && !$report) ? 'required_reporting' : 'unpaid',
                    'report_status' => $report ? $report->status : null,
                    'amount' => $amount,
                    'penalty_amount' => $virtualPenalty,
                    'total_amount' => $amount + $virtualPenalty,
                    'due_date' => $dueDate->toDateTimeString(),
                ]);
            }
            
            $this->incrementDate($tempDate, $cycle);
        }

        return $periods;
    }

    private function getCarbonUnit(string $cycle): string
    {
        return match ($cycle) {
            'daily' => 'day',
            'weekly' => 'week',
            'yearly' => 'year',
            default => 'month',
        };
    }

    private function incrementDate(Carbon $date, string $cycle): void
    {
        match ($cycle) {
            'daily' => $date->addDay(),
            'weekly' => $date->addWeek(),
            'yearly' => $date->addYear(),
            default => $date->addMonth(),
        };
    }

    private function getPeriodString(Carbon $date, string $cycle): string
    {
        return match ($cycle) {
            'daily' => $date->format('Y-m-d'),
            'weekly' => $date->format('Y') . '-W' . $date->format('W'),
            'yearly' => $date->format('Y'),
            default => $date->format('Y-m'),
        };
    }

    public function getPeriodLabel(Carbon $date, string $cycle): string
    {
        return match ($cycle) {
            'daily' => $date->translatedFormat('d F Y'),
            'weekly' => 'Minggu ke-' . $date->format('W') . ', ' . $date->format('Y'),
            'yearly' => 'Tahun ' . $date->format('Y'),
            default => $date->translatedFormat('F Y'),
        };
    }

    private function getDueDate(Carbon $date, string $cycle): Carbon
    {
        return match ($cycle) {
            'daily' => $date->copy()->endOfDay(),
            'weekly' => $date->copy()->endOfWeek(),
            'yearly' => $date->copy()->endOfYear(),
            default => $date->copy()->endOfMonth(),
        };
    }

    /**
     * Calculate the amount for a specific period
     */
    private function calculateAmountForPeriod(TaxObject $taxObject, Carbon $date, $report = null): float
    {
        if ($report && $report->status === 'approved') {
            return (float) $report->tax_amount;
        }

        $type = $taxObject->retributionType;

        // 0. Handle PBB-P2 Special Calculation
        if (str_contains(strtolower($type->name), 'pbb') || str_contains(strtolower($type->category), 'pajak bumi')) {
            $pbbService = app(\App\Services\PbbCalculationService::class);
            $metadata = $taxObject->metadata ?? [];
            
            $luasBumi = (float) ($metadata['luas_bumi'] ?? $metadata['luas_tanah'] ?? 0);
            $kelasBumi = (string) ($metadata['kelas_bumi'] ?? '');
            $luasBangunan = (float) ($metadata['luas_bangunan'] ?? 0);
            $kelasBangunan = (string) ($metadata['kelas_bangunan'] ?? '');
            
            // Allow overrides from metadata for NJOPTKP and Tariff
            $njoptkp = (float) ($metadata['njoptkp'] ?? 10000000);
            $tariff = (float) ($metadata['tariff'] ?? 0.001);

            $result = $pbbService->calculate($luasBumi, $kelasBumi, $luasBangunan, $kelasBangunan, $njoptkp, $tariff);
            return (float) $result['pbb_terhutang'];
        }

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
        $baseAmount = (float) ($type->base_amount ?? 0);
        
        return $baseAmount;
    }

    /**
     * Get total bill amount including penalties and fines
     */
    public function getTotalAmount(\App\Models\Bill $bill): float
    {
        return (float) $bill->amount + (float) $bill->penalty_amount + (float) $bill->fixed_fine_amount + (float) $bill->surcharge_amount;
    }
}
