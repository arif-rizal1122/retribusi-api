<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\EnforcementNotice;
use App\Models\TaxObject;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateAutomatedTeguran extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enforcements:generate-drafts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically generate draft Teguran 1 for bills overdue by more than 7 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Checking for overdue bills to generate Teguran 1...');

        // 1. Find pending bills that are overdue by at least 7 days
        $sevenDaysAgo = Carbon::now()->subDays(7);
        
        $overdueBills = Bill::where('status', 'pending')
            ->whereNotNull('due_date')
            ->where('due_date', '<', $sevenDaysAgo)
            ->with(['taxObject', 'retributionType'])
            ->get();

        if ($overdueBills->isEmpty()) {
            $this->comment('No bills found overdue by > 7 days.');
            return;
        }

        $count = 0;
        foreach ($overdueBills as $bill) {
            // Check if there is already an active (draft or approved) enforcement notice for this object
            // To avoid duplicate Teguran 1 for the same object
            $existingNotice = EnforcementNotice::where('tax_object_id', $bill->tax_object_id)
                ->whereIn('type', ['teguran_1', 'teguran_2'])
                ->whereIn('status', ['draft', 'approved', 'sent'])
                ->exists();

            if ($existingNotice) {
                continue;
            }

            // Generate Teguran 1 Draft
            $notice = EnforcementNotice::create([
                'tax_object_id' => $bill->tax_object_id,
                'type' => 'teguran_1',
                'number' => 'TEG1-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'status' => 'draft',
                'created_by' => 1, // System / Admin
                'due_date' => Carbon::now()->addDays(7), // Give another 7 days to pay after Teguran 1
                'notes' => 'Otomatis dibuat oleh sistem karena tunggakan pada periode ' . $bill->period,
            ]);

            // Notify taxpayer
            if ($taxpayer = $bill->taxObject->taxpayer) {
                $waService = app(\App\Services\WhatsAppService::class);
                $message = "Halo {$taxpayer->name},\n\nSistem kami mendeteksi keterlambatan pembayaran tagihan Retribusi/Pajak untuk objek: " . ($bill->taxObject->name ?? $bill->taxObject->code) . ".\n\nPeriode: {$bill->period}\nKami telah menerbitkan draf SURAT TEGURAN 1 (Nomor: {$notice->number}).\n\nMohon segera melakukan pelunasan untuk menghindari tindakan penagihan lebih lanjut.\n\nTerima kasih.";
                $waService->sendMessage($taxpayer->phone ?? '', $message);
            }

            $count++;
        }

        $this->info("✅ Generated $count draft Teguran 1 notices successfully.");
    }
}
