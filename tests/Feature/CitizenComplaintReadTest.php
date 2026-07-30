<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Taxpayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CitizenComplaintReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_read_citizen_complaints(): void
    {
        $this->getJson('/api/citizen/complaints')->assertUnauthorized();
    }

    public function test_citizen_complaint_history_is_owner_scoped(): void
    {
        $taxpayer = Taxpayer::factory()->create();
        $otherTaxpayer = Taxpayer::factory()->create();
        $ownComplaint = $this->createComplaint($taxpayer, 'Milik saya');
        $this->createComplaint($otherTaxpayer, 'Milik orang lain');

        Sanctum::actingAs($taxpayer);

        $response = $this->getJson('/api/citizen/complaints');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownComplaint->id)
            ->assertJsonPath('data.0.complaint_text', 'Milik saya');
    }

    public function test_internal_user_cannot_read_citizen_complaints(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'super_admin']));

        $this->getJson('/api/citizen/complaints')->assertForbidden();
    }

    private function createComplaint(Taxpayer $taxpayer, string $text): Complaint
    {
        return Complaint::create([
            'taxpayer_id' => $taxpayer->id,
            'name' => $taxpayer->name,
            'email' => $taxpayer->email,
            'category' => 'Layanan Lambat',
            'complaint_text' => $text,
            'status' => 'pending',
            'attachments' => [],
        ]);
    }
}
