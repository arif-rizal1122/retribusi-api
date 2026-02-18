<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\User;
use App\Models\SignedDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TTEIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_kadis_can_sign_bill_electronically()
    {
        $user = User::factory()->create(['role' => 'kadis']);
        $bill = Bill::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/tte/sign', [
            'bill_id' => $bill->id,
            'notes' => 'Test signing notes'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Dokumen berhasil ditandatangani secara elektronik.');

        $this->assertDatabaseHas('signed_documents', [
            'document_id' => $bill->id,
            'signed_by' => $user->id,
            'status' => 'signed'
        ]);

        $this->assertTrue($bill->fresh()->is_signed);
    }

    public function test_petugas_cannot_sign_bill()
    {
        $user = User::factory()->create(['role' => 'petugas']);
        $bill = Bill::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/tte/sign', [
            'bill_id' => $bill->id
        ]);

        $response->assertStatus(403);
    }

    public function test_public_can_verify_signed_document()
    {
        $user = User::factory()->create();
        $bill = Bill::factory()->create();
        
        // Manual creation of signed doc for test
        $signedDoc = SignedDocument::create([
            'document_type' => Bill::class,
            'document_id' => $bill->id,
            'document_number' => $bill->bill_number,
            'signature_hash' => 'testhash123',
            'signed_by' => $user->id,
            'signed_at' => now(),
            'status' => 'signed',
        ]);

        $response = $this->getJson("/api/tte/verify/{$bill->bill_number}");

        $response->assertStatus(200)
            ->assertJson([
                'is_valid' => true,
                'status' => 'DOKUMEN VALID'
            ]);
    }
}
