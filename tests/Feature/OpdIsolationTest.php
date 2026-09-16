<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\PetugasTask;
use App\Models\Scopes\RetributionTypeScope;
use App\Models\Taxpayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class OpdIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Opd $opdA;
    protected Opd $opdB;
    protected User $superAdmin;
    protected User $admin;
    protected User $petugasA;
    protected User $petugasB;
    protected User $pengawasA;
    protected User $opdAdminA;
    protected Taxpayer $taxpayerA;
    protected Taxpayer $taxpayerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opdA = Opd::factory()->create(['status' => 'approved']);
        $this->opdB = Opd::factory()->create(['status' => 'approved']);

        $this->superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'status' => 'active',
        ]);

        // "Admin_Kota" dipetakan ke role admin yang sudah ada di sistem.
        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => 'active',
        ]);

        $this->petugasA = User::factory()->create([
            'role' => User::ROLE_PETUGAS,
            'opd_id' => $this->opdA->id,
            'status' => 'active',
        ]);
        $this->petugasB = User::factory()->create([
            'role' => User::ROLE_PETUGAS,
            'opd_id' => $this->opdB->id,
            'status' => 'active',
        ]);
        $this->pengawasA = User::factory()->create([
            'role' => User::ROLE_PENGAWAS,
            'opd_id' => $this->opdA->id,
            'status' => 'active',
        ]);
        $this->opdAdminA = User::factory()->create([
            'role' => User::ROLE_OPD,
            'opd_id' => $this->opdA->id,
            'status' => 'active',
        ]);

        $this->taxpayerA = Taxpayer::factory()->create([
            'opd_id' => $this->opdA->id,
            'created_by' => $this->petugasA->id,
        ]);
        $this->taxpayerB = Taxpayer::factory()->create([
            'opd_id' => $this->opdB->id,
            'created_by' => $this->petugasB->id,
        ]);
    }

    protected function tearDown(): void
    {
        RetributionTypeScope::resetUser();
        parent::tearDown();
    }

    private function ids(?array $collection): Collection
    {
        return collect($collection ?? [])->pluck('id');
    }

    private function createTask(User $assignee, User $creator): PetugasTask
    {
        return PetugasTask::create([
            'user_id' => $assignee->id,
            'task_type' => 'general',
            'status' => 'pending',
            'due_date' => now()->addDay(),
            'created_by' => $creator->id,
        ]);
    }

    /* ============ Unit: Global Query Scope (model layer) ============ */

    public function test_global_scope_filters_taxpayers_by_opd()
    {
        RetributionTypeScope::setAuthenticatedUser($this->petugasA);

        $ids = Taxpayer::query()->pluck('id')->all();

        $this->assertContains((int) $this->taxpayerA->id, $ids);
        $this->assertNotContains((int) $this->taxpayerB->id, $ids);
    }

    public function test_global_scope_filters_petugas_tasks_via_user_relation()
    {
        $taskA = $this->createTask($this->petugasA, $this->pengawasA);
        $taskB = $this->createTask($this->petugasB, $this->pengawasA);

        RetributionTypeScope::setAuthenticatedUser($this->pengawasA);

        $ids = PetugasTask::query()->pluck('id')->all();

        $this->assertContains((int) $taskA->id, $ids);
        $this->assertNotContains((int) $taskB->id, $ids);
    }

    public function test_global_scope_bypasses_super_admin()
    {
        RetributionTypeScope::setAuthenticatedUser($this->superAdmin);

        $ids = Taxpayer::query()->pluck('id')->all();

        $this->assertContains((int) $this->taxpayerA->id, $ids);
        $this->assertContains((int) $this->taxpayerB->id, $ids);
    }

    public function test_global_scope_bypasses_admin_role()
    {
        RetributionTypeScope::setAuthenticatedUser($this->admin);

        $ids = Taxpayer::query()->pluck('id')->all();

        $this->assertContains((int) $this->taxpayerA->id, $ids);
        $this->assertContains((int) $this->taxpayerB->id, $ids);
    }

    public function test_global_scope_noop_when_user_not_resolved()
    {
        RetributionTypeScope::resetUser();

        $ids = Taxpayer::query()->pluck('id')->all();

        $this->assertContains((int) $this->taxpayerA->id, $ids);
        $this->assertContains((int) $this->taxpayerB->id, $ids);
    }

    /* ============ Integrasi: Dual-Layer Authorization (HTTP) ============ */

    public function test_petugas_list_only_sees_own_opd_taxpayers()
    {
        $response = $this->actingAs($this->petugasA)
            ->getJson('/api/taxpayers')
            ->assertOk();

        $ids = $this->ids($response->json('data'));

        $this->assertTrue($ids->contains((int) $this->taxpayerA->id));
        $this->assertFalse($ids->contains((int) $this->taxpayerB->id));
    }

    public function test_opd_admin_list_only_sees_own_opd_taxpayers()
    {
        $response = $this->actingAs($this->opdAdminA)
            ->getJson('/api/taxpayers')
            ->assertOk();

        $ids = $this->ids($response->json('data'));

        $this->assertTrue($ids->contains((int) $this->taxpayerA->id));
        $this->assertFalse($ids->contains((int) $this->taxpayerB->id));
    }

    public function test_petugas_can_show_taxpayer_of_same_opd()
    {
        $this->actingAs($this->petugasA)
            ->getJson("/api/taxpayers/{$this->taxpayerA->id}")
            ->assertOk();
    }

    public function test_petugas_cannot_show_taxpayer_of_other_opd()
    {
        // Route-model binding resolves before scope_user middleware runs,
        // so the inline controller guard returns 403 (not 404).
        $this->actingAs($this->petugasA)
            ->getJson("/api/taxpayers/{$this->taxpayerB->id}")
            ->assertForbidden();
    }

    public function test_petugas_cannot_update_taxpayer_of_other_opd()
    {
        $this->actingAs($this->petugasA)
            ->putJson("/api/taxpayers/{$this->taxpayerB->id}", ['name' => 'Anas B'])
            ->assertForbidden();
    }

    public function test_petugas_cannot_delete_taxpayer_of_other_opd()
    {
        $this->actingAs($this->petugasA)
            ->deleteJson("/api/taxpayers/{$this->taxpayerB->id}")
            ->assertForbidden();
    }

    public function test_super_admin_can_show_taxpayer_of_any_opd()
    {
        $this->actingAs($this->superAdmin)
            ->getJson("/api/taxpayers/{$this->taxpayerB->id}")
            ->assertOk();
    }

    public function test_admin_can_show_taxpayer_of_any_opd()
    {
        $this->actingAs($this->admin)
            ->getJson("/api/taxpayers/{$this->taxpayerB->id}")
            ->assertOk();
    }

    public function test_pengawas_task_list_is_scoped_per_opd()
    {
        $taskA = $this->createTask($this->petugasA, $this->pengawasA);
        $taskB = $this->createTask($this->petugasB, $this->pengawasA);

        $response = $this->actingAs($this->pengawasA)
            ->getJson('/api/petugas-tasks')
            ->assertOk();

        $ids = $this->ids($response->json('data'));

        $this->assertTrue($ids->contains((int) $taskA->id));
        $this->assertFalse($ids->contains((int) $taskB->id));
    }
}