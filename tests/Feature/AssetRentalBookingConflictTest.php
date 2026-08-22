<?php

namespace Tests\Feature;

use App\Models\AssetCategory;
use App\Models\AssetItem;
use App\Models\AssetRental;
use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\Taxpayer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetRentalBookingConflictTest extends TestCase
{
    use RefreshDatabase;

    protected Taxpayer $taxpayer;
    protected Opd $opd;
    protected AssetCategory $category;
    protected AssetItem $asset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opd = Opd::factory()->create([
            'name' => 'Dinas PUPR Kota Baubau',
        ]);

        $this->taxpayer = Taxpayer::factory()->create([
            'opd_id' => $this->opd->id,
            'name' => 'PT Baubau Konstruksi Mandiri',
            'nik' => '7472010101900001',
        ]);

        $this->category = AssetCategory::create([
            'name' => 'Alat Berat',
            'slug' => 'alat-berat',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->asset = AssetItem::create([
            'category_id' => $this->category->id,
            'opd_id' => $this->opd->id,
            'name' => 'Excavator PC200 Komatsu',
            'code' => 'EXC-01-PUPR',
            'merk_type' => 'Komatsu PC200-8M0',
            'kondisi' => 'Baik',
            'status_operasional' => 'Operasional',
            'tarif_default' => 1500000,
            'satuan_tarif' => 'per hari',
            'buffer_default' => 1,
            'is_active' => true,
            'is_demo' => false,
        ]);
    }

    /**
     * Skenario 1: Sewa normal tersedia (bebas konflik & buffer) -> HTTP 201 Created.
     */
    public function test_scenario_1_normal_available_booking_succeeds(): void
    {
        // Existing booking: 10 - 12 hari ke depan (Buffer: 9 & 13)
        $existingStart = Carbon::today()->addDays(10);
        $existingEnd = Carbon::today()->addDays(12);

        AssetRental::create([
            'rental_code' => 'AST-EXISTING-001',
            'taxpayer_id' => $this->taxpayer->id,
            'asset_item_id' => $this->asset->id,
            'tanggal_mulai' => $existingStart->toDateString(),
            'tanggal_selesai' => $existingEnd->toDateString(),
            'lama_sewa' => 3,
            'satuan_sewa' => 'per hari',
            'buffer_hari' => 1,
            'tarif_per_satuan' => 1500000,
            'total_biaya' => 4500000,
            'status' => 'Disetujui',
            'lokasi_penggunaan' => 'Jl. Betoambari No. 10',
        ]);

        // Sewa Baru: 15 - 17 hari ke depan (Buffer: 14 & 18) -> Tidak ada irisan
        $newStart = Carbon::today()->addDays(15)->toDateString();
        $newEnd = Carbon::today()->addDays(17)->toDateString();

        $response = $this->actingAs($this->taxpayer)
            ->postJson('/api/citizen/asset/rentals', [
                'asset_item_id' => $this->asset->id,
                'tanggal_mulai' => $newStart,
                'tanggal_selesai' => $newEnd,
                'satuan_sewa' => 'per hari',
                'lokasi_penggunaan' => 'Kelurahan Bataraguru, Kec. Wolio',
                'jenis_pekerjaan' => 'Pengerukan Saluran Drainase',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'rental_code',
                    'asset_item_id',
                    'tanggal_mulai',
                    'tanggal_selesai',
                    'lama_sewa',
                    'buffer_hari',
                    'total_biaya',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('asset_rentals', [
            'asset_item_id' => $this->asset->id,
            'tanggal_mulai' => $newStart,
            'tanggal_selesai' => $newEnd,
            'status' => 'Diajukan',
            'buffer_hari' => 1,
        ]);
    }

    /**
     * Skenario 2: Tabrakan tanggal sama persis / beririsan -> HTTP 422 Conflict.
     */
    public function test_scenario_2_direct_date_overlap_returns_422_conflict(): void
    {
        $existingStart = Carbon::today()->addDays(10);
        $existingEnd = Carbon::today()->addDays(12);

        AssetRental::create([
            'rental_code' => 'AST-EXISTING-002',
            'taxpayer_id' => $this->taxpayer->id,
            'asset_item_id' => $this->asset->id,
            'tanggal_mulai' => $existingStart->toDateString(),
            'tanggal_selesai' => $existingEnd->toDateString(),
            'lama_sewa' => 3,
            'satuan_sewa' => 'per hari',
            'buffer_hari' => 1,
            'tarif_per_satuan' => 1500000,
            'total_biaya' => 4500000,
            'status' => 'Disetujui',
            'lokasi_penggunaan' => 'Jl. Pahlawan',
        ]);

        // Sewa Baru: 11 - 13 hari ke depan (irisan langsung di tanggal 11 & 12)
        $newStart = Carbon::today()->addDays(11)->toDateString();
        $newEnd = Carbon::today()->addDays(13)->toDateString();

        $response = $this->actingAs($this->taxpayer)
            ->postJson('/api/citizen/asset/rentals', [
                'asset_item_id' => $this->asset->id,
                'tanggal_mulai' => $newStart,
                'tanggal_selesai' => $newEnd,
                'lokasi_penggunaan' => 'Kecamatan Kokalukuna',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'SCHEDULE_CONFLICT')
            ->assertJsonStructure([
                'success',
                'error_code',
                'message',
                'suggested_dates' => ['start', 'end'],
            ]);
    }

    /**
     * Skenario 3: Tabrakan buffer depan (tanggal_mulai jatuh persis di hari buffer selesai sewa sebelumnya) -> HTTP 422 Conflict.
     */
    public function test_scenario_3_front_buffer_collision_returns_422(): void
    {
        // Booking A: 10 - 12 (buffer berakhir di hari ke-13)
        $existingStart = Carbon::today()->addDays(10);
        $existingEnd = Carbon::today()->addDays(12);

        AssetRental::create([
            'rental_code' => 'AST-EXISTING-003',
            'taxpayer_id' => $this->taxpayer->id,
            'asset_item_id' => $this->asset->id,
            'tanggal_mulai' => $existingStart->toDateString(),
            'tanggal_selesai' => $existingEnd->toDateString(),
            'lama_sewa' => 3,
            'satuan_sewa' => 'per hari',
            'buffer_hari' => 1,
            'status' => 'Diverifikasi',
            'lokasi_penggunaan' => 'Jl. Sultan Murhum',
        ]);

        // Sewa Baru mulai hari ke-13 (hari buffer mobilisasi sewa sebelumnya)
        $newStart = Carbon::today()->addDays(13)->toDateString();
        $newEnd = Carbon::today()->addDays(14)->toDateString();

        $response = $this->actingAs($this->taxpayer)
            ->postJson('/api/citizen/asset/rentals', [
                'asset_item_id' => $this->asset->id,
                'tanggal_mulai' => $newStart,
                'tanggal_selesai' => $newEnd,
                'lokasi_penggunaan' => 'Kecamatan Murhum',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'SCHEDULE_CONFLICT');
    }

    /**
     * Skenario 4: Tabrakan buffer belakang (tanggal_selesai jatuh persis di hari buffer mulai sewa berikutnya) -> HTTP 422 Conflict.
     */
    public function test_scenario_4_back_buffer_collision_returns_422(): void
    {
        // Booking B: 15 - 17 (buffer mobilisasi mulai di hari ke-14)
        $existingStart = Carbon::today()->addDays(15);
        $existingEnd = Carbon::today()->addDays(17);

        AssetRental::create([
            'rental_code' => 'AST-EXISTING-004',
            'taxpayer_id' => $this->taxpayer->id,
            'asset_item_id' => $this->asset->id,
            'tanggal_mulai' => $existingStart->toDateString(),
            'tanggal_selesai' => $existingEnd->toDateString(),
            'lama_sewa' => 3,
            'satuan_sewa' => 'per hari',
            'buffer_hari' => 1,
            'status' => 'Diajukan',
            'lokasi_penggunaan' => 'Kecamatan Lea-Lea',
        ]);

        // Sewa Baru selesai di hari ke-14 (menabrak buffer sewa B)
        $newStart = Carbon::today()->addDays(12)->toDateString();
        $newEnd = Carbon::today()->addDays(14)->toDateString();

        $response = $this->actingAs($this->taxpayer)
            ->postJson('/api/citizen/asset/rentals', [
                'asset_item_id' => $this->asset->id,
                'tanggal_mulai' => $newStart,
                'tanggal_selesai' => $newEnd,
                'lokasi_penggunaan' => 'Kecamatan Bungi',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'SCHEDULE_CONFLICT');
    }

    /**
     * Skenario 5: Alat berat dalam kondisi rusak / tidak operasional -> HTTP 422.
     */
    public function test_scenario_5_broken_or_non_operational_asset_returns_422(): void
    {
        $this->asset->update([
            'status_operasional' => 'Tidak Operasional',
            'kondisi' => 'Tidak Operasional',
        ]);

        $newStart = Carbon::today()->addDays(5)->toDateString();
        $newEnd = Carbon::today()->addDays(7)->toDateString();

        $response = $this->actingAs($this->taxpayer)
            ->postJson('/api/citizen/asset/rentals', [
                'asset_item_id' => $this->asset->id,
                'tanggal_mulai' => $newStart,
                'tanggal_selesai' => $newEnd,
                'lokasi_penggunaan' => 'Jl. Wolio',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Alat berat sedang dalam perbaikan / tidak dapat disewa saat ini.');
    }

    /**
     * Skenario 6: Concurrency / Race Condition Guard Simulation
     * Dua permohonan berurutan untuk slot yang sama:
     * Request 1 berhasil (201 Created), Request 2 langsung ditolak (422 Schedule Conflict).
     *
     * Catatan Concurrency:
     * Pada level database MySQL/PostgreSQL, Pessimistic Locking (`lockForUpdate()`) di dalam
     * `DB::transaction` mengunci baris `asset_items` dan baris `asset_rentals`, sehingga transaksi
     * yang berjalan paralel dieksekusi secara serial dan mencegah timbulnya phantom read atau double-booking.
     */
    public function test_scenario_6_concurrency_race_condition_guard(): void
    {
        $targetStart = Carbon::today()->addDays(20)->toDateString();
        $targetEnd = Carbon::today()->addDays(22)->toDateString();

        $payload = [
            'asset_item_id' => $this->asset->id,
            'tanggal_mulai' => $targetStart,
            'tanggal_selesai' => $targetEnd,
            'lokasi_penggunaan' => 'Jl. Anoa, Kelurahan Kadolo',
            'jenis_pekerjaan' => 'Pembersihan Lahan',
        ];

        // Request pertama dari Pemohon A -> Sukses 201
        $response1 = $this->actingAs($this->taxpayer)
            ->postJson('/api/citizen/asset/rentals', $payload);

        $response1->assertStatus(201)
            ->assertJsonPath('success', true);

        // Request kedua dari Pemohon B untuk slot & alat yang sama -> Ditolak 422
        $taxpayerB = Taxpayer::factory()->create(['opd_id' => $this->opd->id]);

        $response2 = $this->actingAs($taxpayerB)
            ->postJson('/api/citizen/asset/rentals', $payload);

        $response2->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'SCHEDULE_CONFLICT');
    }

    /**
     * Verifikasi endpoint availability GET /api/citizen/asset/items/{id}/availability.
     */
    public function test_availability_endpoint_returns_calendar_data(): void
    {
        $month = Carbon::today()->format('Y-m');

        $response = $this->actingAs($this->taxpayer)
            ->getJson("/api/citizen/asset/items/{$this->asset->id}/availability?month={$month}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.asset_id', $this->asset->id)
            ->assertJsonPath('data.is_operational', true)
            ->assertJsonPath('data.month', $month)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'asset_id',
                    'asset_name',
                    'asset_code',
                    'condition',
                    'is_operational',
                    'month',
                    'days',
                ],
            ]);
    }
}
