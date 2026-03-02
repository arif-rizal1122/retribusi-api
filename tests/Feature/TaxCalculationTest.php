<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use App\Models\RetributionRate;
use App\Models\TaxObject;
use App\Services\FormulaParserService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaxCalculationTest extends TestCase
{
    use RefreshDatabase;
    
    protected $formulaParser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formulaParser = app(FormulaParserService::class);
    }

    public function test_pbjt_makanan_minuman_calculation()
    {
        $opd = Opd::factory()->create();
        $type = RetributionType::factory()->create([
            'opd_id' => $opd->id,
            'name' => 'PBJT'
        ]);
        $cls = RetributionClassification::factory()->create([
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'name' => 'Makanan & Minuman'
        ]);
        $rate = RetributionRate::factory()->create([
            'opd_id' => $opd->id,
            'retribution_classification_id' => $cls->id,
            'amount' => 10 // 10%
        ]);

        $variables = ['omzet' => 1000000, 'tariff' => $rate->amount / 100];
        $result = $variables['omzet'] * $variables['tariff'];
        
        $this->assertEquals(100000, $result);
    }

    public function test_reklame_formula_calculation()
    {
        $opd = Opd::factory()->create();
        $type = RetributionType::factory()->create(['opd_id' => $opd->id]);
        $cls = RetributionClassification::factory()->create([
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'code' => 'REKLAME',
            'calculation_formula' => 'nsr * 0.25'
        ]);
        
        $variables = ['nsr' => 2000000];
        $result = $this->formulaParser->calculate($cls->calculation_formula, $variables);
        
        $this->assertEquals(500000, $result);
    }

    public function test_pbg_complex_formula_calculation()
    {
        $opd = Opd::factory()->create();
        $type = RetributionType::factory()->create(['opd_id' => $opd->id]);
        $cls = RetributionClassification::factory()->create([
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'code' => 'PBG',
            'calculation_formula' => 'luas_lantai * (indeks_lokalitas * shst) * indeks_terintegrasi * indeks_bg'
        ]);
        
        $variables = [
            'luas_lantai' => 100,
            'indeks_lokalitas' => 0.005,
            'shst' => 5000000,
            'indeks_terintegrasi' => 1.2,
            'indeks_bg' => 1.0
        ];
        
        $result = $this->formulaParser->calculate($cls->calculation_formula, $variables);
        
        $this->assertEquals(3000000, $result);
    }

    public function test_penalty_stpd_calculation()
    {
        $amount = 1000000;
        $months = 2;
        $penalty = $this->formulaParser->calculatePenalty($amount, $months, 'stpd');
        
        // 1% per month -> 2% of 1,000,000 = 20,000
        $this->assertEquals(20000, $penalty);
    }

    public function test_penalty_skpdkb_calculation()
    {
        $amount = 1000000;
        $months = 2;
        $penalty = $this->formulaParser->calculatePenalty($amount, $months, 'skpdkb');
        
        // 1.8% per month -> 3.6% of 1,000,000 = 36,000
        $this->assertEquals(36000, $penalty);
    }
}
