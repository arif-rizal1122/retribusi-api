<?php

namespace App\Services;

use App\Models\TaxObject;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BillingService
{
    protected $taxCalculation;

    public function __construct(TaxCalculationService $taxCalculation)
    {
        $this->taxCalculation = $taxCalculation;
    }
    /**
     * Calculate pending billing periods for a tax object
     * 
     * @param TaxObject $taxObject
     * @return Collection
     */
    public function getPendingPeriods(TaxObject $taxObject, array $inputData = []): Collection
    {
        $type = $taxObject->retributionType;

        // Guard: skip if retributionType relation is null (orphaned tax object)
        if (!$type) {
            return collect();
        }

        $cycle = $type->billing_cycle ?? 'monthly';
        
        $startDate = $taxObject->created_at->startOf($this->getCarbonUnit($cycle));
        $currentDate = Carbon::now()->startOf($this->getCarbonUnit($cycle));
        
        $periods = collect();
        $tempDate = $startDate->copy();
        
        // 1. Preload all successful payments for this object
        $paidPeriods = Payment::where('tax_object_id', $taxObject->id)
            ->where('status', 'success')
            ->pluck('billing_period')
            ->toArray();

        // 2. Preload all monthly reports for this object (for self-assessment)
        $classification = $taxObject->classification;
        $isSelfAssessment = $classification ? $classification->is_self_assessment : false;
        $allReports = collect();
        if ($isSelfAssessment) {
            $allReports = \App\Models\MonthlyReport::where('tax_object_id', $taxObject->id)
                ->get()
                ->keyBy('period');
        }

        while ($tempDate->lte($currentDate)) {
            $periodString = $this->getPeriodString($tempDate, $cycle);
            
            if (!in_array($periodString, $paidPeriods)) {
                $report = $allReports->get($periodString);

                $amount = $this->taxCalculation->calculate($taxObject, $inputData, $report);
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
     * Get total bill amount including penalties and fines
     */
    public function getTotalAmount(\App\Models\Bill $bill): float
    {
        return (float) $bill->amount + (float) $bill->penalty_amount + (float) $bill->fixed_fine_amount + (float) $bill->surcharge_amount;
    }
}
