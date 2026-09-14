<?php

namespace Tests\Feature;

use App\Models\AssetCategory;
use App\Models\AssetItem;
use App\Models\AssetRental;
use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\Taxpayer;
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
}
