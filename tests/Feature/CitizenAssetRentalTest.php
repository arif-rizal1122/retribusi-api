<?php

namespace Tests\Feature;

use App\Models\AssetCategory;
use App\Models\AssetItem;
use App\Models\AssetRental;
use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\Taxpayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CitizenAssetRentalTest extends TestCase
{
    use RefreshDatabase;

    private Taxpayer $citizen;

    private AssetItem $asset;

    protected function setUp(): void
    {
        parent::setUp();
        $opd = Opd::factory()->create();
        $this->citizen = Taxpayer::factory()->create(['opd_id' => $opd->id]);
        $category = AssetCategory::create(['name' => 'Citizen test category', 'slug' => 'citizen-test', 'is_active' => true]);
        $this->asset = AssetItem::create([
            'category_id' => $category->id, 'opd_id' => $opd->id, 'name' => 'Citizen test unit',
            'code' => 'CITIZEN-TEST-UNIT', 'status_operasional' => 'Operasional',
            'tarif_default' => 100000, 'satuan_tarif' => 'per hari', 'is_active' => true, 'buffer_default' => 1,
        ]);
        $type = RetributionType::factory()->create(['opd_id' => $opd->id, 'name' => 'Retribusi Jasa Usaha']);
        RetributionClassification::create([
            'opd_id' => $opd->id, 'retribution_type_id' => $type->id, 'code' => 'CITIZEN-TEST',
            'name' => 'Pemanfaatan Aset Daerah', 'is_active' => true,
        ]);
    }

    private function rental(array $overrides = []): AssetRental
    {
        return AssetRental::create(array_merge([
            'rental_code' => 'CITIZEN-RENTAL-TEST', 'taxpayer_id' => $this->citizen->id,
            'asset_item_id' => $this->asset->id, 'tanggal_mulai' => '2026-09-21',
            'tanggal_selesai' => '2026-09-23', 'tanggal_pengembalian' => '2026-09-24',
            'survey_submitted_at' => '2026-09-20 10:00:00', 'status' => 'Diajukan',
        ], $overrides));
    }

    private function booking(array $overrides = []): array
    {
        return array_merge([
            'asset_item_id' => $this->asset->id, 'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(9)->toDateString(), 'lokasi_penggunaan' => 'Lokasi uji citizen',
        ], $overrides);
    }

    public static function timezones(): array
    {
        return [['Asia/Makassar'], ['UTC'], ['America/Los_Angeles']];
    }

    #[DataProvider('timezones')]
    public function test_calendar_dates_are_stable_in_list_and_detail(string $timezone): void
    {
        $previous = date_default_timezone_get();
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
        try {
            $rental = $this->rental();
            $storedStart = $rental->fresh()->getRawOriginal('tanggal_mulai');
            $this->actingAs($this->citizen);
            foreach (['/api/citizen/asset/rentals' => 'data.0', '/api/citizen/asset/rentals/'.$rental->id => 'data'] as $url => $key) {
                $this->getJson($url)->assertOk()
                    ->assertJsonPath($key.'.tanggal_mulai', '2026-09-21')
                    ->assertJsonPath($key.'.tanggal_selesai', '2026-09-23')
                    ->assertJsonPath($key.'.tanggal_pengembalian', '2026-09-24')
                    ->assertJsonPath($key.'.survey_submitted_at', $rental->survey_submitted_at->toJSON());
            }
            $this->assertDatabaseHas('asset_rentals', ['id' => $rental->id, 'tanggal_mulai' => $storedStart]);
        } finally {
            date_default_timezone_set($previous);
        }
    }

    public function test_nullable_calendar_dates_remain_null(): void
    {
        $rental = $this->rental(['tanggal_selesai' => null, 'tanggal_pengembalian' => null]);
        $this->actingAs($this->citizen)->getJson('/api/citizen/asset/rentals/'.$rental->id)
            ->assertOk()->assertJsonPath('data.tanggal_selesai', null)->assertJsonPath('data.tanggal_pengembalian', null);
    }

    public function test_create_response_uses_the_submitted_calendar_dates(): void
    {
        $payload = $this->booking();
        $this->actingAs($this->citizen)->postJson('/api/citizen/asset/rentals', $payload)
            ->assertCreated()->assertJsonPath('data.tanggal_mulai', $payload['tanggal_mulai'])
            ->assertJsonPath('data.tanggal_selesai', $payload['tanggal_selesai']);
    }

    public function test_guest_cannot_read_or_submit_rentals(): void
    {
        $rental = $this->rental();
        $this->getJson('/api/citizen/asset/rentals')->assertUnauthorized();
        $this->getJson('/api/citizen/asset/rentals/'.$rental->id)->assertUnauthorized();
        $this->postJson('/api/citizen/asset/rentals', $this->booking())->assertUnauthorized();
        $this->assertDatabaseCount('asset_rentals', 1);
    }

    public function test_internal_user_with_matching_numeric_id_cannot_use_citizen_rentals(): void
    {
        $rental = $this->rental();
        $internal = User::factory()->create(['id' => $this->citizen->id, 'role' => 'super_admin']);
        $this->actingAs($internal);
        $this->getJson('/api/citizen/asset/rentals')->assertForbidden();
        $this->getJson('/api/citizen/asset/rentals/'.$rental->id)->assertForbidden();
        $this->postJson('/api/citizen/asset/rentals', $this->booking())->assertForbidden();
        // Authorization precedes input validation, including invalid asset IDs.
        $this->postJson('/api/citizen/asset/rentals', [])->assertForbidden();
        $this->assertDatabaseCount('asset_rentals', 1);
    }

    public function test_other_citizen_cannot_read_rental_even_with_owner_id_in_query(): void
    {
        $rental = $this->rental();
        $other = Taxpayer::factory()->create(['opd_id' => $this->citizen->opd_id]);
        $this->actingAs($other);
        $this->getJson('/api/citizen/asset/rentals?taxpayer_id='.$this->citizen->id)
            ->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/citizen/asset/rentals/'.$rental->id.'?taxpayer_id='.$this->citizen->id)
            ->assertNotFound();
    }

    public function test_submission_owner_comes_from_authenticated_citizen(): void
    {
        $other = Taxpayer::factory()->create(['opd_id' => $this->citizen->opd_id]);
        $response = $this->actingAs($this->citizen)->postJson('/api/citizen/asset/rentals', $this->booking([
            'taxpayer_id' => $other->id, 'user_id' => $other->id,
        ]))->assertCreated()->assertJsonPath('data.taxpayer_id', $this->citizen->id);
        $this->assertDatabaseHas('asset_rentals', ['id' => $response->json('data.id'), 'taxpayer_id' => $this->citizen->id]);
    }

    public function test_septic_service_defaults_customer_name_to_authenticated_citizen(): void
    {
        $this->asset->update(['code' => 'SK-CITIZEN-TEST']);
        $this->actingAs($this->citizen)->postJson('/api/citizen/asset/rentals', $this->booking())
            ->assertCreated()->assertJsonPath('data.metadata.nama_konsumen', $this->citizen->name);
    }
}
