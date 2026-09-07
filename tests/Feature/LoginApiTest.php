<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\User;
use Tests\TestCase;

class LoginApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $paths = [
            'database/migrations/0001_01_00_000000_create_opds_table.php',
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2026_01_29_093218_create_personal_access_tokens_table.php',
            'database/migrations/2026_02_14_154924_create_audit_logs_table.php',
        ];

        foreach ($paths as $path) {
            $this->artisan('migrate', ['--path' => $path, '--force' => true]);
        }

        $this->opd = Opd::create([
            'name' => 'Dinas Perhubungan Test',
            'code' => 'DISHUB-TEST',
            'status' => 'approved',
        ]);
    }

    public function test_login_success_returns_token_and_user(): void
    {
        $this->makeUser();

        $response = $this->postJson('/api/login', [
            'email' => 'petugas@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'role'],
                'token',
                'token_type',
            ])
            ->assertJsonFragment(['token_type' => 'Bearer']);

        $this->assertNotEmpty($response->json('token'));
        $this->assertArrayNotHasKey('password', $response->json('user'));
    }

    public function test_login_by_nik_instead_of_email_succeeds(): void
    {
        $this->makeUser(['nik' => '8801234567890123']);

        $response = $this->postJson('/api/login', [
            'email' => '8801234567890123',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user', 'token_type']);
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        $this->makeUser();

        $response = $this->postJson('/api/login', [
            'email' => 'petugas@test.com',
            'password' => 'salah-sandi',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Email/NIK atau password salah']);
    }

    public function test_login_with_unknown_email_returns_401(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'tidak-ada@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_without_credentials_returns_422_validation_error(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_with_inactive_user_returns_403(): void
    {
        $this->makeUser(['status' => 'inactive']);

        $response = $this->postJson('/api/login', [
            'email' => 'petugas@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Akun Anda tidak aktif']);
    }

    public function test_login_with_opd_role_and_approved_opd_succeeds(): void
    {
        User::create([
            'name' => 'Kepala Dishub',
            'email' => 'opd@test.com',
            'password' => bcrypt('password'),
            'role' => 'opd',
            'status' => 'active',
            'opd_id' => $this->opd->id,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'opd@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.role', 'opd')
            ->assertJsonPath('user.opd.id', $this->opd->id);
    }

    public function test_login_with_opd_role_and_unapproved_opd_returns_403(): void
    {
        $opdPending = Opd::create([
            'name' => 'Dinas Belum Disetujui',
            'code' => 'OPD-PENDING',
            'status' => 'pending',
        ]);

        User::create([
            'name' => 'Kepala Dinas',
            'email' => 'opd-pending@test.com',
            'password' => bcrypt('password'),
            'role' => 'opd',
            'status' => 'active',
            'opd_id' => $opdPending->id,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'opd-pending@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'OPD Anda belum disetujui oleh admin']);
    }

    public function test_login_does_not_leak_password_hash_in_response(): void
    {
        $user = $this->makeUser();

        $response = $this->postJson('/api/login', [
            'email' => 'petugas@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('password', $response->json('user'));
        $this->assertStringNotContainsString($user->password, $response->getContent());
    }

    private function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Petugas Lapangan',
            'email' => 'petugas@test.com',
            'password' => bcrypt('password'),
            'nik' => '1234567890123456',
            'role' => 'petugas',
            'status' => 'active',
        ], $overrides));
    }
}