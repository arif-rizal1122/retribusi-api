<?php

namespace App\Services;

use App\Models\TaxObject;
use App\Models\Payment;
use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BillingService
{
    private TaxCalculationService $taxCalculationService;
    private BillPeriodService $periodService;

    public function __construct(
        ?TaxCalculationService $taxCalculationService = null,
        ?BillPeriodService $periodService = null
    ) {
        $this->taxCalculationService = $taxCalculationService ?: app(TaxCalculationService::class);
        $this->periodService = $periodService ?: app(BillPeriodService::class);
    }

    /**
     * Calculate pending billing periods for a tax object
     * 
     * @param TaxObject $taxObject
     * @return Collection
     */
    public function getPendingPeriods(TaxObject $taxObject): Collection
    {
        $type = $taxObject->retributionType;

        // Guard: skip if retributionType relation is null (orphaned tax object)
        if (!$type) {
            return collect();
        }

        $cycle = $type->billing_cycle ?? 'monthly';
        $unit = $this->periodService->getCarbonUnit($cycle);
        
        $startDate = $taxObject->created_at->copy()->startOf($unit);
        $currentDate = Carbon::now()->startOf($unit);
        
        // [PERFORMANCE] Limit virtual arrears calculation to max 24 periods (e.g., 2 years)
        // to prevent heavy processing for very old tax objects.
        $performanceLimit = 24;
        $earliestAllowed = $currentDate->copy()->subMonths($performanceLimit)->startOf($unit);
        
        if ($startDate->lt($earliestAllowed)) {
            $startDate = $earliestAllowed;
        }

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

        // 3. Preload all pending bills for this object (Consolidation)
        $existingBills = Bill::where('tax_object_id', $taxObject->id)
            ->where('status', 'pending')
            ->get()
            ->keyBy('period');
            
        while ($tempDate->lte($currentDate)) {
            $periodString = $this->periodService->getPeriodString($tempDate, $cycle);
            
            if (!in_array($periodString, $paidPeriods)) {
                $existingBill = $existingBills->get($periodString);
                $report = $allReports->get($periodString);

                // Use existing bill data if it exists, otherwise calculate virtual
                if ($existingBill) {
                    $amount = (float) $existingBill->amount;
                    $dueDate = $existingBill->due_date ?? $this->getDueDate($tempDate, $cycle);
                    
                    // Always recalculate penalty to ensure it is up-to-date
                    $currentPenalty = 0;
                    if (Carbon::now()->gt($dueDate)) {
                        $diffInMonths = $dueDate->diffInMonths(Carbon::now());
                        if (Carbon::now()->day > $dueDate->day) $diffInMonths++;
                        if ($diffInMonths === 0) $diffInMonths = 1;

                        $parser = app(\App\Services\FormulaParserService::class);
                        $currentPenalty = $parser->calculatePenalty($amount, $diffInMonths, 'stpd');
                    }
                    
                    // Use the higher value between DB and Recalculated (to avoid regressions)
                    $dbPenalty = (float) $existingBill->penalty_amount + (float) $existingBill->fixed_fine_amount + (float) $existingBill->surcharge_amount;
                    $virtualPenalty = max($dbPenalty, $currentPenalty);
                    
                    $waived = (float) $existingBill->waived_penalty_amount;
                    $effectivePenalty = max(0, $virtualPenalty - $waived);
                    
                    $status = $existingBill->status; // 'pending' or 'overdue'
                    $billId = $existingBill->id;
                    $isFromDB = true;
                } else {
                    $amount = $this->calculateAmountForPeriod($taxObject, $tempDate, $report);
                    $dueDate = $this->periodService->getDueDate($tempDate, $cycle);
                    
                    $virtualPenalty = 0;
                    if (Carbon::now()->gt($dueDate)) {
                        $diffInMonths = $dueDate->diffInMonths(Carbon::now());
                        if (Carbon::now()->day > $dueDate->day) $diffInMonths++;
                        if ($diffInMonths === 0) $diffInMonths = 1;

                        $parser = app(\App\Services\FormulaParserService::class);
                        $virtualPenalty = $parser->calculatePenalty($amount, $diffInMonths, 'stpd');
                    }
                    $status = ($isSelfAssessment && !$report) ? 'required_reporting' : 'unpaid';
                    $billId = null;
                    $isFromDB = false;
                    $effectivePenalty = $virtualPenalty;
                }

                $periods->push([
                    'period' => $periodString,
                    'label' => $this->getPeriodLabel($tempDate, $cycle),
                    'status' => $status,
                    'report_status' => $report ? $report->status : null,
                    'amount' => $amount,
                    'penalty_amount' => $effectivePenalty,
                    'total_amount' => $amount + $effectivePenalty,
                    'due_date' => $dueDate instanceof Carbon ? $dueDate->toDateTimeString() : $dueDate,
                    'bill_id' => $billId,
                    'is_from_db' => $isFromDB
                ]);
            }
            
            $this->periodService->incrementDate($tempDate, $cycle);
        }

        return $periods;
    }

    private function getCarbonUnit(string $cycle): string
    {
        return $this->periodService->getCarbonUnit($cycle);
    }

    private function incrementDate(Carbon $date, string $cycle): void
    {
        $this->periodService->incrementDate($date, $cycle);
    }

    private function getPeriodString(Carbon $date, string $cycle): string
    {
        return $this->periodService->getPeriodString($date, $cycle);
    }

    public function getPeriodLabel(Carbon $date, string $cycle): string
    {
        return $this->periodService->getPeriodLabel($date, $cycle);
    }

    private function getDueDate(Carbon $date, string $cycle): Carbon
    {
        return $this->periodService->getDueDate($date, $cycle);
    }

    /**
     * Calculate the amount for a specific period
     */
    private function calculateAmountForPeriod(TaxObject $taxObject, Carbon $date, $report = null): float
    {
        if ($report && $report->status === 'approved') {
            return (float) $report->tax_amount;
        }

        $cycle = $taxObject->retributionType->billing_cycle ?? 'monthly';

        return $this->taxCalculationService->calculateAmount($taxObject, [
            'period' => $this->periodService->getPeriodString($date, $cycle),
            'period_date' => $date->toDateString(),
        ], $report);
    }

    /**
     * Get total bill amount including penalties and fines
     */
    public function getTotalAmount(\App\Models\Bill $bill): float
    {
        return (float) $bill->total_amount;
    }

    /**
     * Settle a bill (Mark as Lunas, record snapshot penalty, and trigger TTE)
     */
    public function settleBill(\App\Models\Bill $bill, float $currentPenalty, string $bankCode = 'AUTO'): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($bill, $currentPenalty, $bankCode) {
            // 1. Update Bill Status
            $bill->update([
                'status' => 'lunas',
                'penalty_at_payment' => $currentPenalty,
                'bank_code' => $bankCode
            ]);

            // 2. Trigger TTE Hook for Receipt (SSPD/SSRD)
            try {
                $signer = \App\Models\User::whereIn('role', ['super_admin', 'admin', 'kabid_pengawas'])->first();
                if ($signer) {
                    $docService = app(\App\Services\OfficialDocumentService::class);
                    $docService->signDocument('bill', $bill->id, $signer, "Signed automatically via {$bankCode} H2H Integration");
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('BillingService Settle Hook Failed:', [
                    'bill' => $bill->bill_number,
                    'error' => $e->getMessage()
                ]);
            }

            return true;
        });
    }
}
