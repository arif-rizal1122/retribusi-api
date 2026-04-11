<?php

namespace App\Console\Commands;

use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\MonthlyReport;
use App\Services\SimpadKoneksiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyMass extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-legacy-mass {--type=restoran : The tax type to migrate} {--chunk=500 : Batch size per chunk} {--limit= : Total limit (optional)} {--dry-run : Only simulate the migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Full-scale mass migration command for legacy SW_PATDA data with chunking';

    protected $service;

    public function __construct(SimpadKoneksiService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $chunkSize = (int) $this->option('chunk');
        $limit = $this->option('limit');
        $isDryRun = $this->option('dry-run');

        $this->info("Starting MASS migration for type: $type (Chunk: $chunkSize)");
        if ($isDryRun) {
            $this->warn("!!! DRY RUN MODE - No changes will be saved !!!");
        }

        $opd = Opd::where('code', 'BAPENDA')->first();
        if (!$opd) {
            $this->error("OPD BAPENDA not found.");
            return 1;
        }

        $query = $this->service->getLegacyObjects($type);
        if ($limit) {
            $query->limit((int) $limit);
        }

        $total = $limit ?: $query->count();
        $this->info("Total records to process: $total");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // Using chunk for memory efficiency on large tables
        $query->orderBy('CPM_ID')->chunk($chunkSize, function ($legacyObjects) use ($type, $opd, $isDryRun, $bar) {
            foreach ($legacyObjects as $legacy) {
                if (!$isDryRun) {
                    $this->executeMigration($legacy, $type, $opd);
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Mass migration for $type completed!");
        
        return 0;
    }

    protected function executeMigration($legacy, $type, $opd)
    {
        DB::transaction(function () use ($legacy, $type, $opd) {
            $npwpd = $legacy->CPM_NPWPD;
            $legacyWp = $this->service->getLegacyTaxpayer($npwpd);

            if (!$legacyWp) {
                return;
            }

            // A. Create/Update Taxpayer with Multi-Key Deduplication
            $taxpayerData = $this->service->mapTaxpayer($legacyWp);
            $taxpayerData['opd_id'] = $opd->id;
            
            $taxpayer = Taxpayer::where('npwpd', $npwpd)
                ->orWhere(function($query) use ($taxpayerData) {
                    if (!empty($taxpayerData['nik'])) {
                        $query->where('nik', $taxpayerData['nik']);
                    }
                })
                ->orWhere(function($query) use ($taxpayerData) {
                    if (!empty($taxpayerData['phone'])) {
                        $query->where('phone', $taxpayerData['phone']);
                    }
                })
                ->first();

            if ($taxpayer) {
                $taxpayer->update($taxpayerData);
            } else {
                $taxpayer = Taxpayer::create($taxpayerData);
            }

            // B. Mapping to Official PBJT Classification
            $mapping = [
                'restoran' => ['type_id' => 17, 'class_id' => 192],
                'hotel' => ['type_id' => 17, 'class_id' => 193],
                'hiburan' => ['type_id' => 17, 'class_id' => 194],
                'parkir' => ['type_id' => 17, 'class_id' => 172],
                'reklame' => ['type_id' => 16, 'class_id' => 188],
            ];

            $target = $mapping[strtolower($type)] ?? ['type_id' => 17, 'class_id' => 192];

            // C. Create/Update Tax Object
            $objectData = $this->service->mapTaxObject($legacy, $type);
            $objectData['taxpayer_id'] = $taxpayer->id;
            $objectData['retribution_type_id'] = $target['type_id'];
            $objectData['retribution_classification_id'] = $target['class_id'];
            $objectData['opd_id'] = $opd->id;
            
            $taxObject = TaxObject::updateOrCreate(
                ['nop' => $objectData['nop']],
                $objectData
            );

            // D. Migrate latest report from DOC
            $this->migrateLatestReport($legacy->CPM_ID, $taxpayer, $taxObject, $type);
        });
    }

    protected function migrateLatestReport($legacyProfileId, $taxpayer, $taxObject, $type)
    {
        $tableName = 'PATDA_' . strtoupper($type) . '_DOC';
        
        $latestDoc = DB::connection('mysql_legacy')
            ->table($tableName)
            ->where('CPM_ID_PROFIL', $legacyProfileId)
            ->where('CPM_TOTAL_OMZET', '>', 0)
            ->orderBy('TIMESTAMP', 'DESC')
            ->first();

        if ($latestDoc) {
            MonthlyReport::updateOrCreate(
                [
                    'taxpayer_id' => $taxpayer->id,
                    'tax_object_id' => $taxObject->id,
                    'period' => $latestDoc->CPM_MASA_PAJAK . '-' . $latestDoc->CPM_TAHUN_PAJAK
                ],
                [
                    'turnover_amount' => $latestDoc->CPM_TOTAL_OMZET,
                    'tax_amount' => $latestDoc->CPM_TOTAL_PAJAK,
                    'status' => 'approved',
                    'validated_at' => $latestDoc->TIMESTAMP,
                    'notes' => 'Migrated from Legacy System DOC ID: ' . $latestDoc->CPM_ID
                ]
            );
        }
    }
}
