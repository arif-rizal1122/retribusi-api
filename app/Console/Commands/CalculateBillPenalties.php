<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CalculateBillPenalties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bills:calculate-penalties';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically calculate late payment penalties (bunga) and fixed fines (denda) for unpaid bills';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting penalty calculation for unpaid bills...');
        
        $unpaidBills = \App\Models\Bill::where('status', 'pending')
            ->whereNotNull('due_date')
            ->where('due_date', '<', \Carbon\Carbon::now())
            ->with(['retributionType', 'classification'])
            ->get();

        if ($unpaidBills->isEmpty()) {
            $this->comment('No unpaid bills past due date found.');
            return;
        }

        $bar = $this->output->createProgressBar(count($unpaidBills));
        $bar->start();

        $parser = app(\App\Services\FormulaParserService::class);
        $count = 0;

        foreach ($unpaidBills as $bill) {
            // 1. Determine if it's PBB for specific logic
            $isPBB = ($bill->retributionType && stripos($bill->retributionType->name, 'PBB') !== false);
            
            $effectiveDueDate = $dueDate;
            if ($isPBB) {
                // PBB specific: Default due date Nov 10 of current year if not set
                if ($dueDate->month < 11 || ($dueDate->month == 11 && $dueDate->day < 10)) {
                    // It's before Nov 10, maybe we should use the registration date logic instead
                }
                
                // Add grace period: 6 months after registration
                // We use bill creation or taxpayer creation as proxy for "pendaftaran"
                $registrationDate = ($bill->taxpayer ? $bill->taxpayer->created_at : $bill->created_at);
                $gracePeriodEnd = $registrationDate->copy()->addMonths(6);
                
                if ($gracePeriodEnd->isAfter($effectiveDueDate)) {
                    $effectiveDueDate = $gracePeriodEnd;
                }
            }

            if ($now->isBefore($effectiveDueDate)) {
                $bar->advance();
                continue;
            }

            // Calculate months late (rounding up as per regulation: "Bagian dari bulan dihitung penuh 1 bulan")
            $diffInMonths = $effectiveDueDate->diffInMonths($now);
            if ($now->day > $effectiveDueDate->day) {
                $diffInMonths++;
            }
            if ($diffInMonths === 0) $diffInMonths = 1; // Even 1 day late counts as 1 month

            // 1. Calculate Interest (Bunga) based on penalty_type
            $penaltyType = $bill->penalty_type ?: 'stpd';
            $interest = $parser->calculatePenalty($bill->amount, $diffInMonths, $penaltyType);

            // 2. Calculate Fixed Fine (Denda) for late reporting (Self Assessment only)
            // Check if it's a "Self Assessment" type (Wilayah II / Pajak category)
            // Logic: if metadata['is_reported'] is false and it's self-assessment
            $fixedFine = 0;
            if ($bill->retributionType && $bill->retributionType->name === 'Wilayah II') {
                $metadata = $bill->metadata ?? [];
                if (isset($metadata['needs_reporting']) && $metadata['needs_reporting'] === true) {
                    if (!isset($metadata['reported_at'])) {
                        $fixedFine = $parser->getFixedFineForNoReporting();
                    }
                }
            }

            // Update the bill
            // Important: We only update penalty_amount if it's NOT fully waived
            // In a real scenario, we might want to store "calculated_penalty" separately
            // but for now we subtract waived_amount to get the effective penalty
            
            $bill->penalty_amount = $interest;
            $bill->fixed_fine_amount = $fixedFine;
            
            // If there's a waiver, the effectively shown 'penalty_amount' 
            // is handled by the model's total_amount attribute logic,
            // but we keep the raw calculated interest here.
            
            $bill->save();

            $count++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Successfully updated $count bills with penalties/fines.");
    }
}
