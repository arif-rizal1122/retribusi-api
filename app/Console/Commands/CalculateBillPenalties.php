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
        
        $query = \App\Models\Bill::where('status', 'pending')
            ->whereNotNull('due_date')
            ->where('due_date', '<', \Carbon\Carbon::now())
            ->with(['retributionType', 'classification', 'taxpayer']);

        $totalCount = $query->count();
        if ($totalCount === 0) {
            $this->comment('No unpaid bills past due date found.');
            return;
        }

        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $parser = app(\App\Services\FormulaParserService::class);
        $count = 0;

        $query->chunk(100, function ($unpaidBills) use ($parser, &$count, $bar) {
            foreach ($unpaidBills as $bill) {
                $now = \Carbon\Carbon::now();
                $dueDate = $bill->due_date;
                
                // 1. Determine if it's PBB for specific logic
                $isPBB = ($bill->retributionType && stripos($bill->retributionType->name, 'PBB') !== false);
                
                $effectiveDueDate = $dueDate;
                if ($isPBB) {
                    // Add grace period: 6 months after registration
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
                if ($diffInMonths === 0) $diffInMonths = 1;

                // 1. Calculate Interest (Bunga) based on penalty_type
                $penaltyType = $bill->penalty_type ?: 'stpd';
                $interest = $parser->calculatePenalty($bill->amount, $diffInMonths, $penaltyType);

                // 2. Calculate Fixed Fine (Denda) for late reporting (Self Assessment only)
                $fixedFine = 0;
                if ($bill->retributionType && $bill->retributionType->name === 'Wilayah II') {
                    $metadata = $bill->metadata ?? [];
                    if (isset($metadata['needs_reporting']) && $metadata['needs_reporting'] === true) {
                        if (!isset($metadata['reported_at'])) {
                            $fixedFine = $parser->getFixedFineForNoReporting();
                        }
                    }
                }

                $bill->penalty_amount = $interest;
                $bill->fixed_fine_amount = $fixedFine;
                $bill->save();

                $count++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("✅ Successfully updated $count bills with penalties/fines.");
    }
}
