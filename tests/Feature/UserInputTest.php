<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\BuildsUserSchema;
use Tests\TestCase;

class UserInputTest extends TestCase
{
    use BuildsUserSchema;

    protected $opd;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildUserSchema();

        $this->opd = Opd::create([
            'name' => 'OPD Test',
            'code' => 'OPDT',
            'status' => 'approved',
        ]);
    }

    protected function tearDown(): void
    {
        $this->tearDownUserSchema();
        parent::tearDown();
    }

    private function makeUser(string $role, ?int $opdId = null): User
    {
        return User::create([
            'name' => 'Admin Test',
            'email' => strtolower($role) . '_' . uniqid() . '@test.com',
            'password' => 'password',
            'role' => $role,
            'opd_id' => $opdId,
            'status' => 'active',
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi User',
            'email' => 'budi_' . uniqid() . '@test.com',
            'password' => 'password123',
            'role' => 'verifikator',
            'opd_id' => $this->opd->id,
            'status' => 'active',
            'nik' => '1234567890123456',
            'phone' => '081234567890',
        ], $overrides);
    }

    // ------------------------------------------------------------------
    // Authorization
    // ------------------------------------------------------------------

    public function test_unauthenticated_cannot_create_user(): void
    {
        $response = $this->postJson('/api/users', $this->validPayload());
        $response->assertStatus(401);
    }

    public function test_petugas_cannot_create_user(): void
    {
        $petugas = $this->makeUser('petugas', $this->opd->id);
        Sanctum::actingAs($petugas);

        $response = $this->postJson('/api/users', $this->validPayload());
        $response->assertStatus(403);
    }

    public function test_super_admin_can_create_user(): void
    {
        $admin = $this->makeUser('super_admin');
        Sanctum::actingAs($admin);

        $payload = $this->validPayload();
        $response = $this->postJson('/api/users', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('name', $payload['name']);
        $this->assertDatabaseHas('users', ['email' => $payload['email']]);
    }

    // ------------------------------------------------------------------
    // Validation
    // ------------------------------------------------------------------

    public function test_name_is_required(): void
    {
        $admin = $this->makeUser('super_admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', $this->validPayload(['name' => '']));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_email_is_required_and_valid(): void
    {
        $admin = $this->makeUser('super_admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', $this->validPayload(['email' => 'not-an-email']));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');

        $response2 = $this->postJson('/api/users', $this->validPayload(['email' => '']));
        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors('email');
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $admin = $this->makeUser('super_admin');
        Sanctum::actingAs($admin);

        $email = 'duplicate_' . uniqid() . '@test.com';
        User::create([
            'name' => 'Existing',
            'email' => $email,
            'password' => 'password',
            'role' => 'viewer',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/users', $this->validPayload(['email' => $email]));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_password_is_required_with_min_length(): void
    {
        $admin = $this->makeUser('super_admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', $this->validPayload(['password' => '']));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');

        $response2 = $this->postJson('/api/users', $this->validPayload(['password' => 'short']));
        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors('password');
    }

    public function test_invalid_role_is_rejected(): void
    {
        $admin = $this->makeUser('super_admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', $this->validPayload(['role' => 'hacker']));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('role');
    }

    public function test_status_is_required_and_must_be_valid_enum(): void
    {
        $admin = $this->makeUser('super_admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', $this->validPayload(['status' => '']));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');

        $response2 = $this->postJson('/api/users', $this->validPayload(['status' => 'banned']));
        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors('status');
    }

    public function test_invalid_opd_id_is_rejected(): void
    {
        $admin = $this->makeUser('super_admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', $this->validPayload(['opd_id' => 999999]));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('opd_id');
    }

    // ------------------------------------------------------------------
    // Non-super_admin restrictions (OPD admin / opd role)
    // ------------------------------------------------------------------

    public function test_non_super_admin_cannot_elevate_role_to_super_admin(): void
    {
        $opdAdmin = $this->makeUser('opd', $this->opd->id);
        Sanctum::actingAs($opdAdmin);

        $email = 'elevate_sa_' . uniqid() . '@test.com';
        $response = $this->postJson('/api/users', $this->validPayload([
            'email' => $email,
            'role' => 'super_admin',
            'opd_id' => $this->opd->id,
        ]));
        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'role' => 'viewer',
        ]);
    }

    public function test_non_super_admin_cannot_elevate_role_to_opd(): void
    {
        $opdAdmin = $this->makeUser('opd', $this->opd->id);
        Sanctum::actingAs($opdAdmin);

        $email = 'elevate_opd_' . uniqid() . '@test.com';
        $response = $this->postJson('/api/users', $this->validPayload([
            'email' => $email,
            'role' => 'opd',
            'opd_id' => $this->opd->id,
        ]));
        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'role' => 'viewer',
        ]);
    }

    public function test_non_super_admin_forced_to_same_opd(): void
    {
        $otherOpd = Opd::create([
            'name' => 'OPD Lain',
            'code' => 'OPDL',
            'status' => 'approved',
        ]);

        $opdAdmin = $this->makeUser('opd', $this->opd->id);
        Sanctum::actingAs($opdAdmin);

        $email = 'forced_opd_' . uniqid() . '@test.com';
        $response = $this->postJson('/api/users', $this->validPayload([
            'email' => $email,
            'role' => 'verifikator',
            'opd_id' => $otherOpd->id,
        ]));
        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'opd_id' => $this->opd->id,
        ]);
    }

    // ------------------------------------------------------------------
    // Assignments for petugas
    // ------------------------------------------------------------------

    public function test_assignments_are_created_for_petugas(): void
    {
        $admin = $this->makeUser('super_admin');
        Sanctum::actingAs($admin);

        $type = RetributionType::create([
            'opd_id' => $this->opd->id,
            'name' => 'Retribusi Test',
            'category' => 'Test',
            'base_amount' => 1000,
        ]);
        $classification = RetributionClassification::create([
            'opd_id' => $this->opd->id,
            'retribution_type_id' => $type->id,
            'name' => 'Klasifikasi Test',
            'code' => 'KLST',
        ]);

        $email = 'petugas_assign_' . uniqid() . '@test.com';
        $response = $this->postJson('/api/users', $this->validPayload([
            'email' => $email,
            'role' => 'petugas',
            'opd_id' => $this->opd->id,
            'assignments' => [
                [
                    'retribution_type_id' => $type->id,
                    'retribution_classification_id' => $classification->id,
                ],
            ],
        ]));
        $response->assertStatus(201);
        $response->assertJsonCount(1, 'assignments');
        $this->assertDatabaseHas('user_retribution_assignments', [
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
        ]);
    }

    public function test_assignments_are_ignored_for_non_petugas(): void
    {
        $admin = $this->makeUser('super_admin');
        Sanctum::actingAs($admin);

        $type = RetributionType::create([
            'opd_id' => $this->opd->id,
            'name' => 'Retribusi Test',
            'category' => 'Test',
            'base_amount' => 1000,
        ]);

        $email = 'verif_assign_' . uniqid() . '@test.com';
        $response = $this->postJson('/api/users', $this->validPayload([
            'email' => $email,
            'role' => 'verifikator',
            'opd_id' => $this->opd->id,
            'assignments' => [
                ['retribution_type_id' => $type->id],
            ],
        ]));
        $response->assertStatus(201);
        $this->assertDatabaseMissing('user_retribution_assignments', [
            'user_id' => $response->json('id'),
        ]);
    }

    public function test_duplicate_nik_is_rejected(): void
    {
        $admin = $this->makeUser('super_admin');
        Sanctum::actingAs($admin);

        $nik = '1011223344556677';
        User::create([
            'name' => 'Existing',
            'email' => 'existing_' . uniqid() . '@test.com',
            'password' => 'password',
            'role' => 'viewer',
            'status' => 'active',
            'nik' => $nik,
        ]);

        $response = $this->postJson('/api/users', $this->validPayload(['nik' => $nik]));
        // nik is UNIQUE at the DB level but the controller has no unique:users rule for it.
        // Expected BUG: produces a 500 DB error instead of a clean 422 validation rejection.
        $response->assertStatus(422);
    }

    public function test_admin_role_not_creatable_through_store(): void
    {
        $admin = $this->makeUser('super_admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', $this->validPayload(['role' => 'admin']));
        // 'admin' is a real internal role but absent from store whitelist -> rejected
        $response->assertStatus(422);
        $this->assertDatabaseCount('users', 1);
    }

    // ------------------------------------------------------------------
    // Password hashing
    // ------------------------------------------------------------------

    public function test_password_is_hashed_stored(): void
    {
        $admin = $this->makeUser('super_admin');
        Sanctum::actingAs($admin);

        $email = 'hash_check_' . uniqid() . '@test.com';
        $response = $this->postJson('/api/users', $this->validPayload([
            'email' => $email,
            'password' => 'secretpass123',
        ]));
        $response->assertStatus(201);

        $stored = User::where('email', $email)->first();
        $this->assertNotEquals('secretpass123', $stored->password);
        $this->assertTrue(password_verify('secretpass123', $stored->password));
    }
}
