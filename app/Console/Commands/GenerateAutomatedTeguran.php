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
            // 2. Check for existing notices for this specific object
            $lastNotice = EnforcementNotice::where('tax_object_id', $bill->tax_object_id)
                ->latest()
                ->first();

            $newType = 'teguran_1';
            $shouldCreate = false;

            if (!$lastNotice) {
                // No notice yet at all -> Create Teguran 1
                $shouldCreate = true;
                $newType = 'teguran_1';
            } else if ($lastNotice->type === 'teguran_1' && in_array($lastNotice->status, ['approved', 'sent'])) {
                // Teguran 1 exists, check if it's been 14 days
                $noticeDate = $lastNotice->created_at;
                if ($noticeDate->diffInDays(Carbon::now()) >= 14) {
                    $shouldCreate = true;
                    $newType = 'teguran_2';
                }
            } else if ($lastNotice->type === 'teguran_2' && in_array($lastNotice->status, ['approved', 'sent'])) {
                // Teguran 2 exists, check if it's been 7 days to escalate to field action
                $noticeDate = $lastNotice->created_at;
                if ($noticeDate->diffInDays(Carbon::now()) >= 7) {
                    $shouldCreate = true;
                    $newType = 'penindakan';
                }
            }

            if (!$shouldCreate) {
                continue;
            }

            // 3. Generate Notice Draft
            $typeName = ($newType === 'teguran_1' ? 'KESATU' : 'KEDUA');
            $typeCode = ($newType === 'teguran_1' ? 'TEG1' : 'TEG2');

            $notice = EnforcementNotice::create([
                'tax_object_id' => $bill->tax_object_id,
                'type' => $newType,
                'number' => "{$typeCode}-" . date('Ymd') . "-" . strtoupper(Str::random(6)),
                'status' => 'draft',
                'created_by' => 1,
                'due_date' => Carbon::now()->addDays(7),
                'notes' => "Otomatis dibuat oleh sistem (Eskalasi {$newType}) karena tunggakan pada periode {$bill->period}",
                'amount_at_issue' => $bill->amount + $bill->penalty_amount,
                'bill_id' => $bill->id,
            ]);

            // 4. Notify taxpayer
            if ($taxpayer = $bill->taxObject->taxpayer) {
                $waService = app(\App\Services\WhatsAppService::class);
                $message = "⚠️ *PERINGATAN {$typeName}*\n\nHalo {$taxpayer->name},\n\nSistem kami mendeteksi Anda belum melakukan pelunasan tunggakan setelah himbauan sebelumnya.\n\nObjek: {$bill->taxObject->name}\nPeriode: {$bill->period}\nKami telah menerbitkan draf *SURAT TEGURAN {$typeName}* (Nomor: {$notice->number}).\n\nMohon segera melakukan pelunasan untuk menghindari tindakan penagihan paksa.\n\nTerima kasih.";
                $waService->sendMessage($taxpayer->phone ?? '', $message);
            }

            $count++;
        }

        $this->info("✅ Generated $count draft Teguran 1 notices successfully.");
    }
}
