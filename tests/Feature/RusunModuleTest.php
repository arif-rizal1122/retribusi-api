<?php

namespace Tests\Feature;

use App\Models\AssetItem;
use App\Models\AssetRental;
use App\Models\Bill;
use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\RusunRoom;
use App\Models\Taxpayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RusunModuleTest extends TestCase
{
    use RefreshDatabase;

    private Opd $perkimOpd;
    private User $adminUser;
    private Taxpayer $taxpayer;
    private AssetItem $kotamaraLt1;
    private RusunRoom $room101;
    private RetributionType $retType;
    private RetributionClassification $retClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->perkimOpd = Opd::firstOrCreate(
            ['id' => 11],
            [
                'name' => 'Dinas Perumahan, Kawasan Permukiman dan Pertanahan',
                'code' => 'DISPERKIM',
                'status' => 'approved',
                'is_active' => true,
            ]
        );

        $this->adminUser = User::factory()->create([
            'role' => 'opd',
            'opd_id' => 11,
        ]);

        $this->taxpayer = Taxpayer::create([
            'nik' => '7472010101900001',
            'name' => 'Warga Rusun Test',
            'phone' => '081234567890',
            'email' => 'warga.rusun@example.com',
            'is_active' => true,
        ]);

        $this->retType = RetributionType::firstOrCreate(
            ['name' => 'Retribusi Jasa Usaha'],
            [
                'opd_id' => $this->perkimOpd->id,
                'category' => 'retribusi_daerah',
                'is_active' => true,
            ]
        );

        $this->retClass = RetributionClassification::firstOrCreate(
            ['name' => 'Pemanfaatan Aset Daerah'],
            [
                'opd_id' => $this->perkimOpd->id,
                'retribution_type_id' => $this->retType->id,
                'code' => 'JU-ASET',
            ]
        );

        $this->kotamaraLt1 = AssetItem::where('code', 'RUSUN-KOTAMARA-I')->first();
        $this->room101 = RusunRoom::where('asset_item_id', $this->kotamaraLt1->id)
            ->where('room_number', '101')
            ->first();
    }

    /**
     * Test hierarchical selectors: complexes, floors, available rooms.
     */
    public function test_hierarchical_selectors_return_valid_data(): void
    {
        Sanctum::actingAs($this->taxpayer);

        $resComplex = $this->getJson('/api/citizen/rusun/complexes');
        $resComplex->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['code' => 'kotamara']);

        $resFloors = $this->getJson('/api/citizen/rusun/floors?complex=kotamara');
        $resFloors->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['floor' => 1, 'label' => 'Lantai 1']);

        $resRooms = $this->getJson('/api/citizen/rusun/rooms?complex=kotamara&floor=1');
        $resRooms->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['room_number' => '101', 'status' => 'available']);
    }

    /**
     * Test citizen rental submission and admin approval with room lock.
     */
    public function test_citizen_can_rent_room_and_admin_approval_occupies_room(): void
    {
        Sanctum::actingAs($this->taxpayer);

        $payload = [
            'asset_item_id' => $this->kotamaraLt1->id,
            'room_id' => $this->room101->id,
            'tanggal_mulai' => now()->toDateString(),
            'is_guarantee_paid' => true,
        ];

        $submitRes = $this->postJson('/api/citizen/asset/rentals', $payload);

        $submitRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.room_id', $this->room101->id)
            ->assertJsonPath('data.is_guarantee_paid', true);

        $rentalId = $submitRes->json('data.id');

        // Before approval, room is still 'available'
        $this->assertEquals('available', $this->room101->fresh()->status);

        // Admin approves rental via /api/asset/rentals/{id}/approve
        Sanctum::actingAs($this->adminUser);
        $approveRes = $this->postJson("/api/asset/rentals/{$rentalId}/approve");

        if ($approveRes->status() !== 200) {
            dump('Approve Error: ' . $approveRes->getContent());
        }

        $approveRes->assertOk();

        // After approval, room must be 'occupied' and current_rental_id linked
        $this->room101->refresh();
        $this->assertEquals('occupied', $this->room101->status);
        $this->assertEquals($rentalId, $this->room101->current_rental_id);
    }

    /**
     * Test FIFO payment validation blocks skipping earlier unpaid bills.
     */
    public function test_fifo_validation_blocks_payment_of_newer_bill_before_older_bill(): void
    {
        // Bill 1: Agustus 2026
        $bill1 = Bill::create([
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 11,
            'retribution_type_id' => $this->retType->id,
            'bill_number' => 'SKRD-RSN-202608-0001',
            'amount' => 250000,
            'status' => 'pending',
            'due_date' => now()->addDays(20),
            'period' => '2026-08',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'metadata' => ['complex_code' => 'kotamara'],
        ]);

        // Bill 2: September 2026
        $bill2 = Bill::create([
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 11,
            'retribution_type_id' => $this->retType->id,
            'bill_number' => 'SKRD-RSN-202609-0001',
            'amount' => 250000,
            'status' => 'pending',
            'due_date' => now()->addDays(20),
            'period' => '2026-09',
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'metadata' => ['complex_code' => 'kotamara'],
        ]);

        Sanctum::actingAs($this->taxpayer);

        // Trying to pay Bill 2 alone should fail FIFO validation (422)
        $failRes = $this->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill2->id],
            'payment_channel' => 'BANK_SULTRA',
            'method' => 'sultra_va',
        ]);

        $failRes->assertStatus(422)
            ->assertJsonValidationErrors(['bill_ids']);

        // Paying Bill 1 alone or both together [bill1, bill2] should succeed
        $successRes = $this->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill1->id],
            'payment_channel' => 'BANK_SULTRA',
            'method' => 'sultra_va',
        ]);

        $successRes->assertStatus(201);
    }

    /**
     * Test Virtual Ledger prefix for Bank Sultra VA number.
     */
    public function test_bank_sultra_va_uses_rusun_complex_prefix(): void
    {
        $bill = Bill::create([
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 11,
            'retribution_type_id' => $this->retType->id,
            'bill_number' => 'SKRD-RSN-KOTAMARA-001',
            'amount' => 250000,
            'status' => 'pending',
            'due_date' => now()->addDays(20),
            'period' => '2026-09',
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'metadata' => ['complex_code' => 'kotamara'],
        ]);

        Sanctum::actingAs($this->taxpayer);

        $res = $this->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'payment_channel' => 'BANK_SULTRA',
            'method' => 'sultra_va',
        ]);

        $res->assertStatus(201);
        $vaNumber = $res->json('data.va_number');

        // Kotamara prefix should be 7404 padded to 8 digits -> 00007404...
        $this->assertStringStartsWith('00007404', $vaNumber);
    }

    /**
     * Test check-out is blocked if resident has unpaid bills.
     */
    public function test_checkout_is_blocked_when_unpaid_bills_exist(): void
    {
        // Setup occupied room and approved rental
        $rental = AssetRental::create([
            'rental_code' => 'RSN-TEST-001',
            'taxpayer_id' => $this->taxpayer->id,
            'asset_item_id' => $this->kotamaraLt1->id,
            'room_id' => $this->room101->id,
            'retribution_type_id' => $this->retType->id,
            'retribution_classification_id' => $this->retClass->id,
            'tanggal_mulai' => now()->subMonths(2)->toDateString(),
            'status' => 'Disetujui',
            'total_biaya' => 250000,
        ]);

        $this->room101->update([
            'status' => 'occupied',
            'current_rental_id' => $rental->id,
        ]);

        // Unpaid bill exists
        $unpaidBill = Bill::create([
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 11,
            'retribution_type_id' => $this->retType->id,
            'bill_number' => 'SKRD-RSN-UNPAID',
            'amount' => 250000,
            'status' => 'pending',
            'due_date' => now()->addDays(20),
        ]);

        // Attempt checkout via complete endpoint
        Sanctum::actingAs($this->adminUser);
        $completeRes = $this->postJson("/api/asset/rentals/{$rental->id}/complete", [
            'tanggal_pengembalian' => now()->toDateString(),
            'kondisi_pengembalian' => 'Baik',
        ]);

        $completeRes->assertStatus(422)
            ->assertJsonValidationErrors(['checkout']);

        // Mark bill paid
        $unpaidBill->update(['status' => 'paid']);

        // Now checkout should succeed
        $completeRes2 = $this->postJson("/api/asset/rentals/{$rental->id}/complete", [
            'tanggal_pengembalian' => now()->toDateString(),
            'kondisi_pengembalian' => 'Baik',
        ]);

        $completeRes2->assertOk();

        // Room should now be available again
        $this->assertEquals('available', $this->room101->fresh()->status);
        $this->assertNull($this->room101->fresh()->current_rental_id);
    }

    /**
     * Test artisan command rusun:generate-bills generates SKRD for active occupied rooms.
     */
    public function test_generate_monthly_bills_artisan_command(): void
    {
        // Setup 1 occupied room with approved rental
        $rental = AssetRental::create([
            'rental_code' => 'RSN-OCCUPIED-01',
            'taxpayer_id' => $this->taxpayer->id,
            'asset_item_id' => $this->kotamaraLt1->id,
            'room_id' => $this->room101->id,
            'retribution_type_id' => $this->retType->id,
            'retribution_classification_id' => $this->retClass->id,
            'tanggal_mulai' => now()->startOfMonth()->toDateString(),
            'satuan_sewa' => 'per bulan',
            'tarif_per_satuan' => 250000,
            'status' => 'Disetujui',
            'total_biaya' => 250000,
        ]);

        $this->room101->update([
            'status' => 'occupied',
            'current_rental_id' => $rental->id,
        ]);

        $this->artisan('rusun:generate-bills', ['--month' => '2026-10'])
            ->expectsOutputToContain('1 tagihan e-SKRD berhasil diterbitkan.')
            ->assertSuccessful();

        $bill = Bill::where('taxpayer_id', $this->taxpayer->id)
            ->where('period', '2026-10')
            ->first();

        $this->assertNotNull($bill);
        $this->assertEquals(250000, (float) $bill->amount);
        $this->assertEquals('pending', $bill->status);
    }
}
