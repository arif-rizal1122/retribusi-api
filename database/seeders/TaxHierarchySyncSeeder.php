<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use App\Models\RetributionRate;
use App\Models\PbbNjopClassification;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sync ALL classifications with:
 *   - Absolute Cloudinary icons (Mandatory for Admin UI visibility)
 *   - Consolidated Wilayah I & II Hierarchy (Perwali 8/2025)
 *   - calculation_formula (Perda 1/2024)
 */
class TaxHierarchySyncSeeder extends Seeder
{
    const ICON_PAJAK   = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg';
    const ICON_REKLAME = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855480/retribusi/icons/airqm7ydazqqpsqezlrv.jpg';
    const ICON_MBLB    = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855474/retribusi/icons/pl1ag8vgja8jwzabaavc.jpg';
    const ICON_WALET   = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855486/retribusi/icons/agqc0orhv7i9wg4a7x1t.jpg';
    const ICON_BPHTB   = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855470/retribusi/icons/tjhkxpabcvlhjegvnzyf.jpg';
    const ICON_RETRIB  = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855483/retribusi/icons/mqbtlhf4modvik6ikhvi.jpg';

    private array $commonReqs = [
        ['key' => 'foto_lokasi_open_kamera', 'label' => 'Dokumentasi Open Kamera', 'required' => true],
        ['key' => 'formulir_data_dukung', 'label' => 'Upload Formulir Data Dukung', 'required' => true],
    ];

    public function run(): void
    {
        $bapenda = Opd::where('code', 'BAPENDA')->first();
        if (!$bapenda) {
            if ($this->command) $this->command->error('BAPENDA OPD not found.');
            return;
        }

        // ─── Step 1: Consolidate Types ──────────
        $this->syncMasterTypes($bapenda);

        $w1 = RetributionType::where('opd_id', $bapenda->id)->where('name', 'Wilayah I')->first();
        $w2 = RetributionType::where('opd_id', $bapenda->id)->where('name', 'Wilayah II')->first();

        // ─── Step 2: Wilayah I Classifications (Official Assessment) ──────────
        $this->command->info('Configuring Wilayah I...');
        
        // PBB-P2
        $this->syncClassification($bapenda, $w1, 'PBB-P2', [
            'code' => 'PBB', 'icon' => self::ICON_PAJAK, 'formula' => '(njop - 10000000) * 0.003'
        ]);

        $this->syncClassification($bapenda, $w1, 'BPHTB', [
            'code' => 'BPHTB', 'icon' => self::ICON_BPHTB, 'formula' => '(npop - npoptkp) * 0.05'
        ]);

        $this->syncClassification($bapenda, $w1, 'Pajak Reklame', [
            'code' => 'REKLAME', 'icon' => self::ICON_REKLAME, 'formula' => 'nsr * 0.25'
        ]);

        $this->syncClassification($bapenda, $w1, 'Pajak MBLB', [
            'code' => 'MBLB', 'icon' => self::ICON_MBLB, 'formula' => '(volume * harga) * 0.15'
        ]);

        $this->syncClassification($bapenda, $w1, 'Sarang Burung Walet', [
            'code' => 'WALET', 'icon' => self::ICON_WALET, 'formula' => 'nilai_jual * 0.10'
        ]);

        $this->syncClassification($bapenda, $w1, 'Opsen PKB/BBNKB', [
            'code' => 'OPSEN', 'icon' => self::ICON_PAJAK, 'formula' => 'pokok * 0.66'
        ]);

        // ─── Step 3: Wilayah II Classifications (Self Assessment / Service) ───
        $this->command->info('Configuring Wilayah II...');

        $this->syncClassification($bapenda, $w2, 'PBJT - Makan dan Minum', [
            'code' => 'PBJT-MNM', 'icon' => self::ICON_PAJAK, 'formula' => 'omzet * 0.10'
        ]);

        $this->syncClassification($bapenda, $w2, 'PBJT - Jasa Perhotelan', [
            'code' => 'PBJT-HTL', 'icon' => self::ICON_PAJAK, 'formula' => 'omzet * 0.10'
        ]);

        $this->syncClassification($bapenda, $w2, 'PBJT - Kesenian dan Hiburan', [
            'code' => 'PBJT-HBR', 'icon' => self::ICON_PAJAK, 'formula' => 'omzet * 0.10'
        ]);

        $this->syncClassification($bapenda, $w2, 'PBJT - Parkir', [
            'code' => 'PBJT-PRK', 'icon' => self::ICON_PAJAK, 'formula' => 'omzet * 0.10'
        ]);

        $this->syncClassification($bapenda, $w2, 'PBJT - Tenaga Listrik', [
            'code' => 'PBJT-LIS', 'icon' => self::ICON_PAJAK, 'formula' => 'tagihan * 0.10'
        ]);

        $this->syncClassification($bapenda, $w2, 'Pajak Air Tanah', [
            'code' => 'PAT', 'icon' => self::ICON_PAJAK, 'formula' => '(volume * hda) * 0.20'
        ]);

        $this->syncClassification($bapenda, $w2, 'Retribusi Persampahan', [
            'code' => 'SAMPAH', 'icon' => self::ICON_RETRIB, 'formula' => 'tarif_flat'
        ]);

        $this->syncClassification($bapenda, $w2, 'Retribusi PKD', [
            'code' => 'PKD', 'icon' => self::ICON_RETRIB, 'formula' => 'tarif_kios'
        ]);

        $this->syncClassification($bapenda, $w2, 'PBG (Building Permit)', [
            'code' => 'PBG', 'icon' => self::ICON_RETRIB, 'formula' => 'luas * indeks'
        ]);

        $this->command->info('✅ Master Data Sync Complete: 2 Wilayah structure active.');
    }

    private function syncMasterTypes($opd)
    {
        // ─── HARD CLEANUP ───
        // 1. Deactivate ALL other types for ALL OPDs to ensure clean slate
        RetributionType::where('name', '!=', 'Wilayah I')
            ->where('name', '!=', 'Wilayah II')
            ->update(['is_active' => false]);

        // 2. Delete ALL other classifications to strictly follow "sisanya di hapus"
        RetributionClassification::whereHas('retributionType', function($q) {
            $q->where('name', '!=', 'Wilayah I')
              ->where('name', '!=', 'Wilayah II');
        })->delete();

        $names = ['Wilayah I', 'Wilayah II'];
        $keepIds = [];

        foreach ($names as $name) {
            $type = RetributionType::updateOrCreate(
                ['name' => $name, 'opd_id' => $opd->id],
                [
                    'category' => 'Pajak',
                    'icon' => self::ICON_PAJAK,
                    'is_active' => true,
                ]
            );
            $keepIds[] = $type->id;
        }
    }

    private function syncClassification($opd, $type, $name, $config)
    {
        RetributionClassification::updateOrCreate(
            ['opd_id' => $opd->id, 'name' => $name],
            [
                'retribution_type_id' => $type->id,
                'code' => $config['code'],
                'icon' => $config['icon'], // Ensure Absolute Cloudinary URL
                'calculation_formula' => $config['formula'],
                'is_self_assessment' => str_contains($name, 'PBJT') || str_contains($name, 'MBLB'),
                'requirements' => $this->commonReqs,
                'form_schema' => [
                    ['key' => 'omzet', 'label' => 'Total Omzet/Nilai', 'type' => 'number', 'required' => true]
                ]
            ]
        );
    }
}
