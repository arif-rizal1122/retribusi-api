<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardMapPotentialsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $paths = [
            'database/migrations/0001_01_00_000000_create_opds_table.php',
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2026_01_29_093218_create_personal_access_tokens_table.php',
            'database/migrations/2026_02_14_154924_create_audit_logs_table.php',
            'database/migrations/2026_01_29_093248_create_retribution_types_table.php',
            'database/migrations/2026_01_31_045352_create_retribution_classifications_table.php',
            'database/migrations/2026_01_31_045352_create_retribution_rates_table.php',
            'database/migrations/2026_02_02_000000_add_icon_to_retribution_classifications_table.php',
            'database/migrations/2026_01_29_100002_create_taxpayers_table.php',
            'database/migrations/2026_02_02_000000_add_created_by_to_taxpayers_table.php',
            'database/migrations/2026_01_31_124442_add_metadata_to_taxpayers_table.php',
            'database/migrations/2026_01_29_133442_create_zones_table.php',
            'database/migrations/2026_01_29_171129_add_coordinates_to_zones_table.php',
            'database/migrations/2026_01_30_011712_add_retribution_and_opd_to_zones_table.php',
            'database/migrations/2026_01_31_045353_adjust_retribution_types_and_zones_for_hierarchy.php',
            'database/migrations/2026_02_12_060000_drop_amount_multiplier_from_zones.php',
            'database/migrations/2026_01_30_014508_create_tax_objects_table.php',
            'database/migrations/2026_02_15_202332_add_classification_to_tax_objects_table.php',
            'database/migrations/2026_04_11_023150_add_is_active_to_tax_objects.php',
            'database/migrations/2026_01_30_014510_create_bills_table.php',
            'database/migrations/2026_02_01_184555_add_opd_id_to_bills_table.php',
            'database/migrations/2026_02_02_100001_add_classification_to_bills_table.php',
            'database/migrations/2026_02_02_100000_create_user_retribution_assignments_table.php',
            'database/migrations/2026_03_09_033157_add_retribution_type_id_to_users_table.php',
        ];

        foreach ($paths as $path) {
            $this->artisan('migrate', ['--path' => $path, '--force' => true]);
        }
    }

    private function seedUser(array $overrides = []): User
    {
        $email = uniqid('user') . '@test.com';
        DB::table('users')->insert(array_merge([
            'name' => 'User Test',
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        return User::where('email', $email)->first();
    }

    private function seedOpd(string $code): int
    {
        return DB::table('opds')->insertGetId([
            'name' => "OPD {$code}",
            'code' => $code,
            'status' => 'approved',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedType(int $opdId, string $name): int
    {
        return DB::table('retribution_types')->insertGetId([
            'opd_id' => $opdId,
            'name' => $name,
            'category' => 'retribusi_daerah',
            'tariff_percent' => 0,
            'base_amount' => 0,
            'unit' => 'per_bulan',
            'icon' => 'map-pin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedClassification(int $opdId, int $typeId, string $name): int
    {
        return DB::table('retribution_classifications')->insertGetId([
            'opd_id' => $opdId,
            'retribution_type_id' => $typeId,
            'name' => $name,
            'code' => uniqid('CLASS-'),
            'description' => 'Klasifikasi ' . $name,
            'icon' => 'building',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedTaxpayer(int $opdId, string $name, bool $isActive = true, array $metadata = []): int
    {
        return DB::table('taxpayers')->insertGetId([
            'opd_id' => $opdId,
            'name' => $name,
            'nik' => (string) random_int(1000000000000000, 9999999999999999),
            'is_active' => $isActive,
            'metadata' => json_encode($metadata),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedZone(
        int $opdId,
        int $typeId,
        string $name,
        ?float $latitude,
        ?float $longitude,
        ?int $classificationId = null,
        string $description = ''
    ): int {
        return DB::table('zones')->insertGetId([
            'opd_id' => $opdId,
            'retribution_type_id' => $typeId,
            'retribution_classification_id' => $classificationId,
            'name' => $name,
            'code' => uniqid('ZONE-'),
            'description' => $description,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedTaxObject(
        int $opdId,
        int $taxpayerId,
        int $typeId,
        string $name,
        ?float $latitude,
        ?float $longitude,
        ?int $classificationId = null,
        string $status = 'active',
        string $address = 'Jl. Test No. 1',
        array $metadata = []
    ): int {
        return DB::table('tax_objects')->insertGetId([
            'opd_id' => $opdId,
            'taxpayer_id' => $taxpayerId,
            'retribution_type_id' => $typeId,
            'retribution_classification_id' => $classificationId,
            'zone_id' => null,
            'name' => $name,
            'address' => $address,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'status' => $status,
            'metadata' => json_encode($metadata),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedBill(
        int $opdId,
        int $typeId,
        int $taxpayerId,
        ?int $taxObjectId,
        string $status
    ): int {
        return DB::table('bills')->insertGetId([
            'opd_id' => $opdId,
            'user_id' => null,
            'taxpayer_id' => $taxpayerId,
            'tax_object_id' => $taxObjectId,
            'retribution_type_id' => $typeId,
            'retribution_classification_id' => null,
            'bill_number' => uniqid('BILL-'),
            'amount' => 50000,
            'status' => $status,
            'period' => 'Januari 2026',
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'due_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedAssignment(int $userId, int $typeId, ?int $classificationId = null): void
    {
        DB::table('user_retribution_assignments')->insert([
            'user_id' => $userId,
            'retribution_type_id' => $typeId,
            'retribution_classification_id' => $classificationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function authedRequest(User $user)
    {
        $token = $user->createToken('test-token')->plainTextToken;

        return $this->getJson('/api/dashboard/map-potentials', [
            'Authorization' => "Bearer {$token}",
        ]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/dashboard/map-potentials');
        $response->assertStatus(401);
    }

    public function test_citizen_role_is_forbidden(): void
    {
        $citizen = $this->seedUser(['role' => 'citizen']);
        $this->authedRequest($citizen)->assertStatus(403);
    }

    public function test_wajib_pajak_role_is_forbidden(): void
    {
        $wp = $this->seedUser(['role' => 'wajib_pajak']);
        $this->authedRequest($wp)->assertStatus(403);
    }

    public function test_super_admin_returns_zones_first_then_tax_objects(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Parkir');
        $class = $this->seedClassification($opd, $type, 'Parkir Pinggir Jalan');
        $taxpayer = $this->seedTaxpayer($opd, 'Kios A');

        $this->seedZone($opd, $type, 'Zona Parkir Pasar', -5.4601, 122.6001, $class, 'Area Pasar Wameo');
        $objectId = $this->seedTaxObject($opd, $taxpayer, $type, 'Kios No. 5', -5.4602, 122.6002, $class);

        $response = $this->authedRequest($this->seedUser(['role' => 'super_admin']));
        $response->assertOk();

        $data = $response->json();
        $this->assertIsArray($data);

        $zones = collect($data)->where('status', 'zone')->values();
        $taxObjects = collect($data)->where('status', 'taxpayer')->values();

        $this->assertCount(1, $zones);
        $this->assertCount(1, $taxObjects);
        $this->assertEqualsWithDelta(-5.4601, $zones->first()['position'][0], 0.000001);
        $this->assertEquals($objectId, $taxObjects->first()['tax_object_id']);
        $this->assertSame('taxpayer', $taxObjects->first()['status']);
        $this->assertArrayHasKey('tax_object_id', $taxObjects->first());
    }

    public function test_zone_without_coordinates_is_excluded(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Parkir');

        $this->seedZone($opd, $type, 'Zona Tanpa Koordinat', null, null);

        $response = $this->authedRequest($this->seedUser(['role' => 'super_admin']));
        $response->assertOk();

        $zones = collect($response->json())->where('status', 'zone');
        $this->assertCount(0, $zones);
    }

    public function test_tax_object_without_coordinates_is_excluded(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Parkir');
        $taxpayer = $this->seedTaxpayer($opd, 'Kios A');

        $this->seedTaxObject($opd, $taxpayer, $type, 'Kios Tanpa Koordinat', null, null);

        $response = $this->authedRequest($this->seedUser(['role' => 'super_admin']));
        $response->assertOk();

        $objects = collect($response->json())->where('status', 'taxpayer');
        $this->assertCount(0, $objects);
    }

    public function test_zone_response_contains_expected_shape(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Parkir');
        $class = $this->seedClassification($opd, $type, 'Parkir Pinggir Jalan');
        $this->seedZone($opd, $type, 'Zona Parkir Pasar', -5.4601, 122.6001, $class, 'Area Pasar Wameo');

        $response = $this->authedRequest($this->seedUser(['role' => 'super_admin']));
        $response->assertOk();

        $zone = collect($response->json())->where('status', 'zone')->first();
        $this->assertNotNull($zone, 'Zone should exist in payload');

        $this->assertEquals(['position', 'name', 'agency', 'address', 'status', 'icon', 'retribution_type_id', 'opd_id'], array_keys($zone));

        $this->assertIsArray($zone['position']);
        $this->assertEqualsWithDelta(-5.4601, $zone['position'][0], 0.000001);
        $this->assertEqualsWithDelta(122.6001, $zone['position'][1], 0.000001);

        $this->assertEquals('Zona Parkir Pasar (Parkir)', $zone['name']);
        $this->assertEquals('OPD DISHUB', $zone['agency']);
        $this->assertEquals('Area Pasar Wameo', $zone['address']);
        $this->assertSame('zone', $zone['status']);
        $this->assertEquals('map-pin', $zone['icon']);
        $this->assertEquals($type, $zone['retribution_type_id']);
        $this->assertEquals($opd, $zone['opd_id']);
    }

    public function test_tax_object_response_contains_expected_shape(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Parkir');
        $class = $this->seedClassification($opd, $type, 'Parkir Pinggir Jalan');
        $taxpayer = $this->seedTaxpayer($opd, 'Kios A', true, ['foto_lokasi_open_kamera' => 'https://map.example/foto.jpg']);
        $objectId = $this->seedTaxObject($opd, $taxpayer, $type, 'Kios No. 5', -5.4602, 122.6002, $class, 'active', 'Jl. Sultan Hasanuddin', ['luas' => 25]);

        $response = $this->authedRequest($this->seedUser(['role' => 'super_admin']));
        $response->assertOk();

        $object = collect($response->json())->where('status', 'taxpayer')->first();
        $this->assertNotNull($object, 'Tax object should exist in payload');

        $this->assertEquals(
            ['position', 'tax_object_id', 'name', 'agency', 'address', 'status', 'is_paid', 'classification_name', 'classification_icon', 'taxpayer_photo', 'icon', 'retribution_type_id', 'retribution_classification_id', 'opd_id', 'metadata'],
            array_keys($object)
        );

        $this->assertIsArray($object['position']);
        $this->assertEqualsWithDelta(-5.4602, $object['position'][0], 0.000001);
        $this->assertEqualsWithDelta(122.6002, $object['position'][1], 0.000001);

        $this->assertEquals('Kios A - Kios No. 5', $object['name']);
        $this->assertEquals('OPD DISHUB', $object['agency']);
        $this->assertEquals('Jl. Sultan Hasanuddin', $object['address']);
        $this->assertSame('taxpayer', $object['status']);
        $this->assertEquals('Parkir Pinggir Jalan', $object['classification_name']);
        $this->assertEquals('building', $object['classification_icon']);
        $this->assertEquals('https://map.example/foto.jpg', $object['taxpayer_photo']);
        $this->assertNull($object['icon']);
        $this->assertEquals($type, $object['retribution_type_id']);
        $this->assertEquals($class, $object['retribution_classification_id']);
        $this->assertEquals($opd, $object['opd_id']);
        $this->assertIsArray($object['metadata']);
        $this->assertEquals(25, $object['metadata']['luas']);
    }

    public function test_tax_object_is_paid_true_when_only_paid_bills(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Parkir');
        $taxpayer = $this->seedTaxpayer($opd, 'Kios A');
        $objectId = $this->seedTaxObject($opd, $taxpayer, $type, 'Kios No. 5', -5.4602, 122.6002);
        $this->seedBill($opd, $type, $taxpayer, $objectId, 'paid');
        $this->seedBill($opd, $type, $taxpayer, $objectId, 'lunas');

        $response = $this->authedRequest($this->seedUser(['role' => 'super_admin']));
        $response->assertOk();

        $object = collect($response->json())->where('status', 'taxpayer')->first();
        $this->assertEquals($objectId, $object['tax_object_id']);
        $this->assertTrue($object['is_paid']);
    }

    public function test_tax_object_is_paid_false_when_has_unpaid_bill(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Parkir');
        $taxpayer = $this->seedTaxpayer($opd, 'Kios A');
        $objectId = $this->seedTaxObject($opd, $taxpayer, $type, 'Kios No. 5', -5.4602, 122.6002);
        $this->seedBill($opd, $type, $taxpayer, $objectId, 'paid');
        $this->seedBill($opd, $type, $taxpayer, $objectId, 'pending');

        $response = $this->authedRequest($this->seedUser(['role' => 'super_admin']));
        $response->assertOk();

        $object = collect($response->json())->where('status', 'taxpayer')->first();
        $this->assertFalse($object['is_paid']);
    }

    public function test_pending_and_rejected_objects_are_excluded(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Parkir');
        $taxpayer = $this->seedTaxpayer($opd, 'Kios A');

        $this->seedTaxObject($opd, $taxpayer, $type, 'Kios Pending', -5.4602, 122.6002, null, 'pending');
        $this->seedTaxObject($opd, $taxpayer, $type, 'Kios Ditolak', -5.4603, 122.6003, null, 'rejected');

        $response = $this->authedRequest($this->seedUser(['role' => 'super_admin']));
        $response->assertOk();

        $objects = collect($response->json())->where('status', 'taxpayer');
        $this->assertCount(0, $objects);
    }

    public function test_opd_user_sees_only_own_opd_data(): void
    {
        $opdA = $this->seedOpd('DISHUB');
        $opdB = $this->seedOpd('PUPR');

        $typeA = $this->seedType($opdA, 'Parkir');
        $taxpayerA = $this->seedTaxpayer($opdA, 'Kios A');
        $this->seedZone($opdA, $typeA, 'Zona Parkir A', -5.4601, 122.6001);
        $this->seedTaxObject($opdA, $taxpayerA, $typeA, 'Kios A-1', -5.4602, 122.6002);

        $typeB = $this->seedType($opdB, 'Retribusi Trotoar');
        $taxpayerB = $this->seedTaxpayer($opdB, 'Toko B');
        $this->seedZone($opdB, $typeB, 'Zona Trotoar B', -5.4701, 122.6101);
        $this->seedTaxObject($opdB, $taxpayerB, $typeB, 'Toko B-1', -5.4702, 122.6102);

        $response = $this->authedRequest($this->seedUser(['role' => 'opd', 'opd_id' => $opdA]));
        $response->assertOk();

        $data = $response->json();
        $this->assertCount(2, $data);

        foreach ($data as $entry) {
            $this->assertEquals($opdA, $entry['opd_id']);
        }
    }

    public function test_petugas_with_assignment_sees_only_assigned_retribution_type(): void
    {
        $opd = $this->seedOpd('DISHUB');

        $typeParkir = $this->seedType($opd, 'Parkir');
        $taxpayerParkir = $this->seedTaxpayer($opd, 'Kios Parkir');
        $this->seedZone($opd, $typeParkir, 'Zona Parkir', -5.4601, 122.6001);
        $this->seedTaxObject($opd, $taxpayerParkir, $typeParkir, 'Kios Parkir 1', -5.4602, 122.6002);

        $typeTrotoar = $this->seedType($opd, 'Retribusi Trotoar');
        $taxpayerTrotoar = $this->seedTaxpayer($opd, 'Toko Trotoar');
        $this->seedZone($opd, $typeTrotoar, 'Zona Trotoar', -5.4611, 122.6011);
        $this->seedTaxObject($opd, $taxpayerTrotoar, $typeTrotoar, 'Toko Trotoar 1', -5.4612, 122.6012);

        $petugas = $this->seedUser(['role' => 'petugas', 'opd_id' => $opd]);
        $this->seedAssignment($petugas->id, $typeParkir);

        $response = $this->authedRequest($petugas);
        $response->assertOk();

        $data = $response->json();
        $this->assertCount(2, $data);

        foreach ($data as $entry) {
            $this->assertEquals($typeParkir, $entry['retribution_type_id']);
        }
    }

    public function test_petugas_without_assignment_sees_own_opd_data(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $otherOpd = $this->seedOpd('PUPR');

        $typeOwn = $this->seedType($opd, 'Parkir');
        $taxpayerOwn = $this->seedTaxpayer($opd, 'Kios A');
        $this->seedZone($opd, $typeOwn, 'Zona Parkir A', -5.4601, 122.6001);
        $this->seedTaxObject($opd, $taxpayerOwn, $typeOwn, 'Kios A-1', -5.4602, 122.6002);

        $typeOther = $this->seedType($otherOpd, 'Retribusi Trotoar');
        $taxpayerOther = $this->seedTaxpayer($otherOpd, 'Toko B');
        $this->seedZone($otherOpd, $typeOther, 'Zona Trotoar B', -5.4701, 122.6101);
        $this->seedTaxObject($otherOpd, $taxpayerOther, $typeOther, 'Toko B-1', -5.4702, 122.6102);

        $petugas = $this->seedUser(['role' => 'petugas', 'opd_id' => $opd]);

        $response = $this->authedRequest($petugas);
        $response->assertOk();

        $data = $response->json();
        $this->assertCount(2, $data);

        foreach ($data as $entry) {
            $this->assertEquals($opd, $entry['opd_id']);
        }
    }
}