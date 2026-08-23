<?php

namespace Tests\Feature;

use App\Models\BphtbSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BphtbRouteContractTest extends TestCase
{
    use DatabaseTransactions;

    public function test_bphtb_root_list_is_backoffice_wide_but_mine_is_owner_scoped(): void
    {
        $firstPpat = User::factory()->create(['role' => 'notaris']);
        $secondPpat = User::factory()->create(['role' => 'notaris']);
        $admin = User::factory()->create(['role' => 'admin']);

        $ownedSubmission = $this->createSubmission([
            'ppat_user_id' => $firstPpat->id,
            'billing_code' => 'BPHTB-OWNED',
            'nib' => 'NIB-OWNED',
        ]);
        $otherSubmission = $this->createSubmission([
            'ppat_user_id' => $secondPpat->id,
            'billing_code' => 'BPHTB-OTHER',
            'nib' => 'NIB-OTHER',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/h2h/bphtb')
            ->assertOk()
            ->assertJsonFragment(['id' => $ownedSubmission->id])
            ->assertJsonFragment(['id' => $otherSubmission->id]);

        Sanctum::actingAs($firstPpat);

        $this->getJson('/api/h2h/bphtb/mine')
            ->assertOk()
            ->assertJsonFragment(['id' => $ownedSubmission->id])
            ->assertJsonMissing(['id' => $otherSubmission->id]);

        $this->getJson('/api/h2h/bphtb')
            ->assertOk()
            ->assertJsonFragment(['id' => $ownedSubmission->id])
            ->assertJsonMissing(['id' => $otherSubmission->id]);
    }

    public function test_legacy_sspd_alias_and_canonical_issue_sspd_route_share_contract(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        Sanctum::actingAs($petugas);

        $legacySubmission = $this->createSubmission([
            'billing_code' => 'BPHTB-LEGACY-SSPD',
            'status' => 'paid',
        ]);
        $canonicalSubmission = $this->createSubmission([
            'billing_code' => 'BPHTB-CANONICAL-SSPD',
            'status' => 'paid',
        ]);

        $this->postJson("/api/h2h/bphtb/{$legacySubmission->id}/sspd")
            ->assertCreated()
            ->assertJsonPath('message', 'SSPD digital berhasil diterbitkan.');

        $this->postJson("/api/h2h/bphtb/{$canonicalSubmission->id}/issue-sspd")
            ->assertCreated()
            ->assertJsonPath('message', 'SSPD digital berhasil diterbitkan.');

        $this->assertDatabaseHas('bphtb_submissions', [
            'id' => $legacySubmission->id,
            'status' => 'sspd_issued',
        ]);
        $this->assertDatabaseHas('bphtb_submissions', [
            'id' => $canonicalSubmission->id,
            'status' => 'sspd_issued',
        ]);
    }

    private function createSubmission(array $attributes = []): BphtbSubmission
    {
        return BphtbSubmission::create(array_merge([
            'nib' => 'NIB-'.fake()->unique()->numerify('########'),
            'nop' => 'NOP-'.fake()->unique()->numerify('########'),
            'nik_wp' => fake()->unique()->numerify('################'),
            'nama_wp' => fake()->name(),
            'ppat_user_id' => User::factory()->create(['role' => 'notaris'])->id,
            'reported_npop' => 100000000,
            'znt_applied' => 100000000,
            'final_npop' => 100000000,
            'status_flag' => 'VALID',
            'billing_code' => 'BPHTB-'.fake()->unique()->numerify('########'),
            'status' => 'submitted',
        ], $attributes));
    }
}
