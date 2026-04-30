<?php

namespace Database\Seeders;

use App\Models\RetributionClassification;
use App\Models\RetributionRate;
use Illuminate\Database\Seeder;

class Perda12024Seeder extends Seeder
{
    /**
     * Run the database seeds.
     * Logic based on Perda No. 1 Year 2024 Baubau City
     */
    public function run(): void
    {
        // 1. PBJT Formulas (Pajak Barang dan Jasa Tertentu)
        $pbjtClassifications = [
            'PBJT - Makan dan Minum' => 'omzet_penjualan * 0.1',
            'PBJT - Tenaga Listrik' => 'tagihan * 0.1',
            'PBJT - Jasa Perhotelan' => 'nilai_pembayaran * 0.1',
            'PBJT - Jasa Parkir' => 'nilai_pembayaran * 0.1',
            'PBJT - Jasa Kesenian dan Hiburan' => 'harga_tiket * 0.1',
            'PBJT - Jasa Catering' => 'omzet_penjualan * 0.1',
            'PBJT - Jasa Event/Lainnya' => 'omzet_penjualan * 0.1',
        ];

        foreach ($pbjtClassifications as $name => $formula) {
            RetributionClassification::where('name', 'LIKE', "%$name%")->update([
                'calculation_formula' => $formula
            ]);
        }

        // 2. Special Rates for Entertainment (Hiburan Khusus Diskotek/Karaoke/Spa: 40%)
        $hiburanCls = RetributionClassification::where('name', 'LIKE', '%Jasa Kesenian dan Hiburan%')->first();
        if ($hiburanCls) {
            RetributionRate::where('retribution_classification_id', $hiburanCls->id)
                ->where('name', 'LIKE', '%Khusus%')
                ->update([
                    'calculation_formula' => 'total_charge * 0.4'
                ]);
        }

        // 3. Other Taxes
        $otherTaxes = [
            'Pajak Reklame' => 'nsr * 0.25',
            'Air Tanah' => 'volume * npa * 0.2',
            'Pajak MBLB' => 'volume * harga_patokan * 0.15',
            'Sarang Burung Walet' => 'nilai_jual * 0.1',
        ];

        foreach ($otherTaxes as $name => $formula) {
            RetributionClassification::where('name', 'LIKE', "%$name%")->update([
                'calculation_formula' => $formula
            ]);
        }

        // 4. PBB-P2 (Lahan Produksi Pangan & Ternak 0.25%, Umum 0.3%)
        $pbbCls = RetributionClassification::where('name', 'LIKE', '%PBB%')->first();
        if ($pbbCls) {
            RetributionRate::where('retribution_classification_id', $pbbCls->id)
                ->where('name', 'LIKE', '%Pangan%')
                ->update(['calculation_formula' => '(njop - njoptkp) * 0.0025']);
                
            RetributionRate::where('retribution_classification_id', $pbbCls->id)
                ->where('name', 'NOT LIKE', '%Pangan%')
                ->update(['calculation_formula' => '(njop - njoptkp) * 0.003']);
        }

        // 5. BPHTB (5%)
        RetributionClassification::where('name', 'LIKE', '%BPHTB%')->update([
            'calculation_formula' => '(npop - npoptkp) * 0.05'
        ]);

        $this->command->info('Perda 1/2024 Formulas Seeded Successfully.');
    }
}
