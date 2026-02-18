<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\EnforcementNotice;
use App\Models\RetributionType;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\Opd;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveillanceRoleTest extends TestCase
{
    use RefreshDatabase;

    protected $kabid;
    protected $kasubid;
    protected $enforcement;

    protected function setUp(): void
    {
        parent::setUp();

        // Create OPD
        $opd = Opd::create([
            'name' => 'Test OPD',
            'code' => 'TOPD',
            'status' => 'approved'
        ]);

        // Create Retribution Type
        $type = RetributionType::create([
            'opd_id' => $opd->id,
            'name' => 'Test Retribution',
            'category' => 'Test',
            'base_amount' => 1000
        ]);

        // Create Taxpayer
        $taxpayer = Taxpayer::create([
            'opd_id' => $opd->id,
            'nik' => '1234567890123456',
            'name' => 'Test WP',
            'password' => bcrypt('password')
        ]);

        // Create Tax Object
        $taxObject = TaxObject::create([
            'opd_id' => $opd->id,
            'taxpayer_id' => $taxpayer->id,
            'retribution_type_id' => $type->id,
            'name' => 'Test Object',
            'status' => 'approved'
        ]);

        // Create Users
        $this->kabid = User::create([
            'name' => 'Kabid Test',
            'email' => 'kabid@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_KABID_PENGAWAS,
            'status' => 'active'
        ]);

        $this->kasubid = User::create([
            'name' => 'Kasubid Test',
            'email' => 'kasubid@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_KASUBID_PENGAWAS,
            'status' => 'active'
        ]);

        // Create Enforcement Notice
        $this->enforcement = EnforcementNotice::create([
            'opd_id' => $opd->id,
            'tax_object_id' => $taxObject->id,
            'number' => 'SURAT/001',
            'type' => 'teguran_1',
            'status' => 'draft',
            'created_by' => $this->kasubid->id
        ]);
    }

    public function test_kabid_can_approve_enforcement_notice()
    {
        $response = $this->actingAs($this->kabid)
            ->postJson("/api/pengawas/enforcements/{$this->enforcement->id}/approve");

        $response->assertStatus(200);
        $this->assertEquals('approved', $this->enforcement->fresh()->status);
    }

    public function test_kasubid_cannot_approve_enforcement_notice()
    {
        $response = $this->actingAs($this->kasubid)
            ->postJson("/api/pengawas/enforcements/{$this->enforcement->id}/approve");

        $response->assertStatus(403);
        $this->assertEquals('draft', $this->enforcement->fresh()->status);
    }

    public function test_kabid_can_issue_skpdkb()
    {
        // Must be approved first
        $this->enforcement->update(['status' => 'approved']);

        $response = $this->actingAs($this->kabid)
            ->postJson("/api/pengawas/penindakan/issue-skpdkb", [
                'enforcement_notice_id' => $this->enforcement->id,
                'deficit_amount' => 500000
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'bill' => ['bill_number', 'amount']]);
    }

    public function test_kasubid_cannot_issue_skpdkb()
    {
        $this->enforcement->update(['status' => 'approved']);

        $response = $this->actingAs($this->kasubid)
            ->postJson("/api/pengawas/penindakan/issue-skpdkb", [
                'enforcement_notice_id' => $this->enforcement->id,
                'deficit_amount' => 500000
            ]);

        $response->assertStatus(403);
    }
}
