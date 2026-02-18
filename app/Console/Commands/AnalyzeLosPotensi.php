<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TaxObject;
use App\Models\Bill;

class AnalyzeLosPotensi extends Command
{
    protected $signature = 'app:analyze-los-potensi {--opd= : Filter by OPD code}';
    protected $description = 'Analyze lost potential by comparing registered tax objects with potential data';

    public function handle()
    {
        $this->info('🔍 Analyzing Los Potensi...');
        
        // 1. Objects without any bills
        $noBillObjects = TaxObject::doesntHave('bills')
            ->with(['taxpayer', 'retributionType'])
            ->get();
            
        $this->warn("\n[!] " . $noBillObjects->count() . " Objek Pajak terdaftar namun BELUM PERNAH diterbitkan tagihan (Potensi Terbuang):");
        foreach ($noBillObjects->take(10) as $obj) {
            $this->line(" - [{$obj->nop}] {$obj->name} ({$obj->taxpayer->name})");
        }

        // 2. High Potential vs Low Realization (e.g., Omset in metadata > actual bills)
        $potentialGaps = TaxObject::whereNotNull('metadata')
            ->whereHas('bills', function($q) {
                $q->where('status', 'lunas');
            })
            ->with(['bills'])
            ->get()
            ->filter(function($obj) {
                $metadata = $obj->metadata ?? [];
                $potentialOmset = $metadata['omset_penjualan'] ?? 0;
                if ($potentialOmset <= 0) return false;
                
                // Average monthly payment
                $avgPaid = $obj->bills->where('status', 'lunas')->avg('amount') ?: 0;
                
                // If avg paid is less than 50% of expected from potential omset (assuming 10% rate)
                return ($avgPaid < ($potentialOmset * 0.05)); 
            });

        $this->warn("\n[!] " . $potentialGaps->count() . " Objek Pajak dengan realisasi pembayarannya jauh di bawah potensi omset:");
        foreach ($potentialGaps->take(10) as $obj) {
            $potentialOmset = $obj->metadata['omset_penjualan'] ?? 0;
            $this->line(" - [{$obj->nop}] {$obj->name}: Potensi Omset Rp" . number_format($potentialOmset) . " vs Realisasi Rendah");
        }

        $this->info("\n✅ Analisis Los Potensi Selesai.");
    }
}
