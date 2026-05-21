<?php

namespace Tests\Feature;

use App\Models\PbbNjopClassification;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\Opd;
use App\Models\User;
use App\Services\PbbCalculationService;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PbbCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pbb_njop_lookup()
    {
        PbbNjopClassification::create(['type' => 'bumi', 'class_code' => '050', 'min_value' => 3200000, 'max_value' => 3550000, 'njop_value' => 3375000]);
        
        $service = new PbbCalculationService();
        $this->assertEquals(3375000, $service->getNjopValue('bumi', '050'));
    }

    public function test_pbb_total_calculation()
    {
        PbbNjopClassification::create(['type' => 'bumi', 'class_code' => '050', 'min_value' => 3200000, 'max_value' => 3550000, 'njop_value' => 3375000]);
        PbbNjopClassification::create(['type' => 'bangunan', 'class_code' => '020', 'min_value' => 1366000, 'max_value' => 1666000, 'njop_value' => 1516000]);

        $service = new PbbCalculationService();
        $result = $service->calculate(100, '050', 50, '020', 10000000, 0.001);

        // Bumi: 100 * 3,375,000 = 337,500,000
        // Bangunan: 50 * 1,516,000 = 75,800,000
        // Total: 413,300,000
        // NJOP Dasar: 413,300,000 - 10,000,000 = 403,300,000
        // PBB: 403,300,000 * 0.001 = 403,300

        $this->assertEquals(403300, $result['pbb_terhutang']);
    }

    public function test_pbb_billing_integration()
    {
        $opd = Opd::create(['name' => 'BAPENDA', 'code' => 'BAPENDA']);
        $type = RetributionType::create([
            'opd_id' => $opd->id,
            'name' => 'Pajak Bumi dan Bangunan (PBB)',
            'category' => 'Pajak',
            'base_amount' => 0
        ]);

        PbbNjopClassification::create(['type' => 'bumi', 'class_code' => '050', 'min_value' => 3200000, 'max_value' => 3550000, 'njop_value' => 3375000]);
        PbbNjopClassification::create(['type' => 'bangunan', 'class_code' => '020', 'min_value' => 1366000, 'max_value' => 1666000, 'njop_value' => 1516000]);

        $taxpayer = Taxpayer::create(['name' => 'Test', 'nik' => '123', 'opd_id' => $opd->id]);
        
        $obj = TaxObject::create([
            'nop' => '123.456',
            'name' => 'Objek Pajak Test',
            'taxpayer_id' => $taxpayer->id,
            'retribution_type_id' => $type->id,
            'opd_id' => $opd->id,
            'metadata' => [
                'luas_bumi' => 100,
                'kelas_bumi' => '050',
                'luas_bangunan' => 50,
                'kelas_bangunan' => '020'
            ],
            'status' => 'active'
        ]);

        $billingService = app(\App\Services\BillingService::class);
        $periods = $billingService->getPendingPeriods($obj);

        $this->assertGreaterThan(0, $periods->count());
        $this->assertEquals(403300, $periods->first()['amount']);
    }
}
