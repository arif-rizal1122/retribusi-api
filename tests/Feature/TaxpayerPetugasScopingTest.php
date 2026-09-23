<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\User;
use App\Models\UserRetributionAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Scoping daftar Wajib Pajak untuk role petugas.
 *
 * Aturan (lihat TaxpayerController::index + docs/mpad_rbac/02_... bagian A):
 * - Petugas HANYA melihat WP yang layanannya (tax_objects / pivot
 *   taxpayer_retribution_type) cocok dengan user_retribution_assignments.
 * - `created_by` TIDAK dipakai sebagai basis visibilitas.
 * - Petugas tanpa assignment melihat daftar kosong.
 */
class TaxpayerPetugasScopingTest extends TestCase
{
    use RefreshDatabase;

    private Opd $opd;
    private RetributionType $type;
    private RetributionClassification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opd = Opd::factory()->create();
        $this->type = RetributionType::factory()->create(['opd_id' => $this->opd->id]);
        $this->classification = RetributionClassification::factory()->create([
            'opd_id' => $this->opd->id,
            'retribution_type_id' => $this->type->id,
        ]);
    }

    private function makePetugas(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'petugas',
            'opd_id' => $this->opd->id,
        ], $attrs));
    }

    private function assign(User $petugas, ?int $classificationId = null): UserRetributionAssignment
    {
        return UserRetributionAssignment::create([
            'user_id' => $petugas->id,
            'retribution_type_id' => $this->type->id,
            'retribution_classification_id' => $classificationId,
        ]);
    }

    private function ids($response): array
    {
        return collect($response->json('data'))->pluck('id')->all();
    }

    /** Petugas melihat WP yang objek pajaknya cocok dengan assignment-nya. */
    public function test_petugas_sees_taxpayer_with_matching_tax_object(): void
    {
        $petugas = $this->makePetugas();
        $this->assign($petugas, $this->classification->id);

        $wpCocok = Taxpayer::factory()->create(['opd_id' => $this->opd->id]);
        TaxObject::factory()->create([
            'taxpayer_id' => $wpCocok->id,
            'opd_id' => $this->opd->id,
            'retribution_type_id' => $this->type->id,
            'retribution_classification_id' => $this->classification->id,
        ]);

        $response = $this->actingAs($petugas)->getJson('/api/taxpayers');

        $response->assertOk();
        $this->assertContains($wpCocok->id, $this->ids($response));
    }

    /** WP dengan layanan lain TIDAK tampil untuk petugas. */
    public function test_petugas_does_not_see_taxpayer_with_other_service(): void
    {
        $petugas = $this->makePetugas();
        $this->assign($petugas, $this->classification->id);

        $typeLain = RetributionType::factory()->create(['opd_id' => $this->opd->id]);
        $clsLain = RetributionClassification::factory()->create([
            'opd_id' => $this->opd->id,
            'retribution_type_id' => $typeLain->id,
        ]);

        $wpLain = Taxpayer::factory()->create(['opd_id' => $this->opd->id]);
        TaxObject::factory()->create([
            'taxpayer_id' => $wpLain->id,
            'opd_id' => $this->opd->id,
            'retribution_type_id' => $typeLain->id,
            'retribution_classification_id' => $clsLain->id,
        ]);

        $response = $this->actingAs($petugas)->getJson('/api/taxpayers');

        $response->assertOk();
        $this->assertNotContains($wpLain->id, $this->ids($response));
    }

    /**
     * `created_by` tidak lagi jadi basis visibilitas: WP yang dibuat petugas
     * tapi layanannya tidak cocok dengan assignment -> tidak tampil.
     */
    public function test_created_by_alone_does_not_grant_visibility(): void
    {
        $petugas = $this->makePetugas();
        $this->assign($petugas, $this->classification->id);

        $typeLain = RetributionType::factory()->create(['opd_id' => $this->opd->id]);
        $wpDibuat = Taxpayer::factory()->create([
            'opd_id' => $this->opd->id,
            'created_by' => $petugas->id,
        ]);
        TaxObject::factory()->create([
            'taxpayer_id' => $wpDibuat->id,
            'opd_id' => $this->opd->id,
            'retribution_type_id' => $typeLain->id,
            'retribution_classification_id' => null,
        ]);

        $response = $this->actingAs($petugas)->getJson('/api/taxpayers');

        $response->assertOk();
        $this->assertNotContains($wpDibuat->id, $this->ids($response));
    }

    /** WP yang memilih layanan lewat pivot (tanpa tax_object) tetap tampil. */
    public function test_petugas_sees_taxpayer_via_pivot_selection(): void
    {
        $petugas = $this->makePetugas();
        $this->assign($petugas, $this->classification->id);

        $wpPivot = Taxpayer::factory()->create(['opd_id' => $this->opd->id]);
        $wpPivot->retributionTypes()->attach($this->type->id, [
            'retribution_classification_id' => $this->classification->id,
        ]);

        $response = $this->actingAs($petugas)->getJson('/api/taxpayers');

        $response->assertOk();
        $this->assertContains($wpPivot->id, $this->ids($response));
    }

    /** Petugas tanpa assignment -> daftar kosong. */
    public function test_petugas_without_assignments_sees_empty_list(): void
    {
        $petugas = $this->makePetugas();

        $wp = Taxpayer::factory()->create([
            'opd_id' => $this->opd->id,
            'created_by' => $petugas->id,
        ]);
        TaxObject::factory()->create([
            'taxpayer_id' => $wp->id,
            'opd_id' => $this->opd->id,
            'retribution_type_id' => $this->type->id,
            'retribution_classification_id' => $this->classification->id,
        ]);

        $response = $this->actingAs($petugas)->getJson('/api/taxpayers');

        $response->assertOk();
        $this->assertSame([], $this->ids($response));
    }
}
