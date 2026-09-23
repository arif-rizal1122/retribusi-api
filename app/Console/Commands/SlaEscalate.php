<?php

namespace App\Console\Commands;

use App\Services\SlaTrackerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Task 2319 — command eskalasi SLA.
 *
 * Dijalankan berkala (lihat routes/console.php). Menandai penugasan yang
 * melewati ambang eskalasi lalu mencatatnya ke log agar dapat ditindaklanjuti.
 */
class SlaEscalate extends Command
{
    protected $signature = 'sla:escalate {--dry-run : Tampilkan tanpa mengubah data}';

    protected $description = 'Tandai penugasan layanan yang melewati ambang eskalasi SLA';

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $rows = SlaTrackerService::overdueForEscalation();
            if ($rows->isEmpty()) {
                $this->info('Tidak ada penugasan yang perlu dieskalasi.');
                return self::SUCCESS;
            }
            foreach ($rows as $r) {
                $this->line(sprintf(
                    '[DRY] #%d %s (deadline %s)',
                    $r->id,
                    $r->stage,
                    $r->sla_deadline ?? '-'
                ));
            }
            return self::SUCCESS;
        }

        $escalated = SlaTrackerService::escalateDue();

        if (empty($escalated)) {
            $this->info('Tidak ada penugasan yang perlu dieskalasi.');
            return self::SUCCESS;
        }

        foreach ($escalated as $e) {
            Log::warning('SLA ESCALATION', [
                'assignment_id' => $e['id'],
                'stage'         => $e['stage'],
                'label'         => $e['label'],
                'pic'           => $e['pic'],
                'sla_deadline'  => $e['due'],
            ]);
        }

        $this->warn(sprintf('%d penugasan dieskalasi.', count($escalated)));

        return self::SUCCESS;
    }
}
