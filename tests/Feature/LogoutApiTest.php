<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class LogoutApiTest extends TestCase
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

        $this->user = User::create([
            'name' => 'Petugas Lapangan',
            'email' => 'petugas@test.com',
            'password' => bcrypt('password'),
            'role' => 'petugas',
            'status' => 'active',
        ]);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $plainToken = $this->user->createToken('login-token')->plainTextToken;

        $response = $this->postJson('/api/logout', [], [
            'Authorization' => "Bearer {$plainToken}",
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logout berhasil']);
    }

    public function test_logout_revokes_issued_token(): void
    {
        $plainToken = $this->user->createToken('login-token')->plainTextToken;

        $tokenId = $this->user->tokens()->first()->id;
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->postJson('/api/logout', [], [
            'Authorization' => "Bearer {$plainToken}",
        ])->assertStatus(200);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_revoked_token_cannot_be_reused(): void
    {
        $plainToken = $this->user->createToken('login-token')->plainTextToken;

        $this->postJson('/api/logout', [], [
            'Authorization' => "Bearer {$plainToken}",
        ])->assertStatus(200);

        \Illuminate\Support\Facades\Auth::forgetGuards();

        $response = $this->postJson('/api/logout', [], [
            'Authorization' => "Bearer {$plainToken}",
        ]);

        $response->assertStatus(401);
    }

    public function test_logout_without_token_returns_401(): void
    {
        $response = $this->postJson('/api/logout', []);

        $response->assertStatus(401);
    }

    public function test_logout_keeps_other_tokens_intact(): void
    {
        $tokenA = $this->user->createToken('device-a')->plainTextToken;
        $this->user->createToken('device-b');

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $this->postJson('/api/logout', [], [
            'Authorization' => "Bearer {$tokenA}",
        ])->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertEquals('device-b', $this->user->tokens()->first()->name);
    }
}