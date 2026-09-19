<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\User;
use App\Services\OfficialDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class BillDocumentOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private Opd $firstOpd;

    private Opd $secondOpd;

    private RetributionType $firstType;

    private RetributionType $secondType;

    private RetributionClassification $firstClassification;

    private RetributionClassification $secondClassification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->firstOpd = Opd::factory()->create();
        $this->secondOpd = Opd::factory()->create();
        $this->firstType = RetributionType::factory()->create(['opd_id' => $this->firstOpd->id]);
        $this->secondType = RetributionType::factory()->create(['opd_id' => $this->secondOpd->id]);
        $this->firstClassification = RetributionClassification::factory()->create([
            'opd_id' => $this->firstOpd->id,
            'retribution_type_id' => $this->firstType->id,
        ]);
        $this->secondClassification = RetributionClassification::factory()->create([
            'opd_id' => $this->secondOpd->id,
            'retribution_type_id' => $this->secondType->id,
        ]);
    }

    public function test_taxpayer_can_read_own_sspd_but_cannot_read_another_taxpayers_bill_documents(): void
    {
        $owner = Taxpayer::factory()->create(['opd_id' => $this->firstOpd->id]);
        $otherTaxpayer = Taxpayer::factory()->create(['opd_id' => $this->secondOpd->id]);
        $ownBill = $this->createBill($owner, $this->firstOpd, $this->firstType, $this->firstClassification);
        $otherBill = $this->createBill($otherTaxpayer, $this->secondOpd, $this->secondType, $this->secondClassification);

        $documentService = Mockery::mock(OfficialDocumentService::class);
        $documentService->shouldReceive('generateSSPD')
            ->once()
            ->andReturn($this->sspdViewData($ownBill));
        $this->app->instance(OfficialDocumentService::class, $documentService);

        Sanctum::actingAs($owner);

        $this->get('/api/bills/'.$ownBill->id.'/sspd')
            ->assertOk()
            ->assertSee($ownBill->bill_number);

        foreach (['skrd', 'sspd', 'sppt'] as $format) {
            $this->get('/api/bills/'.$otherBill->id.'/'.$format)->assertForbidden();
        }
    }

    public function test_internal_user_can_read_documents_only_inside_their_opd(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_OPD,
            'opd_id' => $this->firstOpd->id,
        ]);
        $sameOpdBill = $this->createBill(
            Taxpayer::factory()->create(['opd_id' => $this->firstOpd->id]),
            $this->firstOpd,
            $this->firstType,
            $this->firstClassification
        );
        $otherOpdBill = $this->createBill(
            Taxpayer::factory()->create(['opd_id' => $this->secondOpd->id]),
            $this->secondOpd,
            $this->secondType,
            $this->secondClassification
        );

        $documentService = Mockery::mock(OfficialDocumentService::class);
        $documentService->shouldReceive('generateSSPD')
            ->once()
            ->andReturn($this->sspdViewData($sameOpdBill));
        $this->app->instance(OfficialDocumentService::class, $documentService);

        Sanctum::actingAs($staff);

        $this->get('/api/bills/'.$sameOpdBill->id.'/sspd')->assertOk();

        foreach (['skrd', 'sspd', 'sppt'] as $format) {
            $this->get('/api/bills/'.$otherOpdBill->id.'/'.$format)->assertForbidden();
        }
    }

    public function test_global_internal_role_can_read_a_bill_from_another_opd(): void
    {
        $administrator = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $taxpayer = Taxpayer::factory()->create(['opd_id' => $this->secondOpd->id]);
        $bill = $this->createBill($taxpayer, $this->secondOpd, $this->secondType, $this->secondClassification);

        $documentService = Mockery::mock(OfficialDocumentService::class);
        $documentService->shouldReceive('generateSSPD')
            ->once()
            ->andReturn($this->sspdViewData($bill));
        $this->app->instance(OfficialDocumentService::class, $documentService);

        Sanctum::actingAs($administrator);

        $this->get('/api/bills/'.$bill->id.'/sspd')->assertOk();
    }

    private function createBill(
        Taxpayer $taxpayer,
        Opd $opd,
        RetributionType $type,
        RetributionClassification $classification
    ): Bill {
        $taxObject = TaxObject::factory()->create([
            'opd_id' => $opd->id,
            'taxpayer_id' => $taxpayer->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
        ]);

        return Bill::factory()->create([
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
            'status' => 'paid',
            'period' => '2026-09',
        ]);
    }

    private function sspdViewData(Bill $bill): array
    {
        return [
            'title' => $bill->retributionType?->name ?? 'Retribusi Daerah',
            'number' => $bill->bill_number,
            'taxpayer' => $bill->taxpayer?->name ?? 'Wajib Pajak',
            'address' => $bill->taxpayer?->address ?? 'Kota Baubau',
            'unit_name' => $bill->opd?->name ?? 'BAPENDA Kota Baubau',
            'period' => $bill->period,
            'amount' => (float) $bill->amount,
            'penalty_amount' => 0,
            'fixed_fine_amount' => 0,
            'surcharge_amount' => 0,
            'total_amount' => (float) $bill->amount,
            'terbilang' => 'Seratus Ribu Rupiah',
            'paid_at' => now(),
            'payment_method' => 'Transfer Manual',
            'qr_url' => 'https://example.test/verify/'.$bill->bill_number,
        ];
    }
}
