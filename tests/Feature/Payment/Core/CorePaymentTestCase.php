<?php

namespace Tests\Feature\Payment\Core;

use App\Models\Bill;
use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

abstract class CorePaymentTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createCoreBill(array $overrides = []): Bill
    {
        $opd = Opd::factory()->create();
        $type = RetributionType::factory()->create(['opd_id' => $opd->id]);
        $classification = RetributionClassification::factory()->create([
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
        ]);
        $taxpayer = Taxpayer::factory()->create(['opd_id' => $opd->id]);
        $taxObject = TaxObject::factory()->create([
            'opd_id' => $opd->id,
            'taxpayer_id' => $taxpayer->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
        ]);

        return Bill::factory()->create(array_merge([
            'bill_number' => 'SKRD-CORE-' . Str::upper(Str::random(8)),
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
            'amount' => 50000,
            'admin_fee' => 0,
            'penalty_amount' => 0,
            'fixed_fine_amount' => 0,
            'surcharge_amount' => 0,
            'waived_penalty_amount' => 0,
            'status' => 'pending',
            'period' => '2026-07',
            'due_date' => now()->addMonth(),
        ], $overrides));
    }
}
