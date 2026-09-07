<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardStatsApiTest extends TestCase
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
            'database/migrations/2026_01_29_100002_create_taxpayers_table.php',
            'database/migrations/2026_02_02_000000_add_created_by_to_taxpayers_table.php',
            'database/migrations/2026_01_30_014508_create_tax_objects_table.php',
            'database/migrations/2026_01_30_014510_create_bills_table.php',
            'database/migrations/2026_02_01_184555_add_opd_id_to_bills_table.php',
            'database/migrations/2026_02_02_100001_add_classification_to_bills_table.php',
            'database/migrations/2026_01_30_014511_create_payments_table.php',
            'database/migrations/2026_02_01_195456_add_verification_to_payments_table.php',
            'database/migrations/2026_02_02_100000_create_user_retribution_assignments_table.php',
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

    private function seedOpd(string $code, string $status = 'approved'): int
    {
        return DB::table('opds')->insertGetId([
            'name' => "OPD {$code}",
            'code' => $code,
            'status' => $status,
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedTaxpayer(int $opdId, string $name): int
    {
        return DB::table('taxpayers')->insertGetId([
            'opd_id' => $opdId,
            'name' => $name,
            'nik' => (string) random_int(1000000000000000, 9999999999999999),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedBill(int $opdId, int $taxpayerId, int $typeId, ?int $classId, float $amount, string $status, Carbon $date): int
    {
        return DB::table('bills')->insertGetId([
            'taxpayer_id' => $taxpayerId,
            'tax_object_id' => null,
            'retribution_type_id' => $typeId,
            'retribution_classification_id' => $classId,
            'opd_id' => $opdId,
            'bill_number' => uniqid('BILL-'),
            'amount' => $amount,
            'status' => $status,
            'period' => $date->format('F Y'),
            'period_start' => $date->toDateString(),
            'period_end' => $date->toDateString(),
            'due_date' => $date->toDateString(),
            'created_at' => $date->toDateTimeString(),
            'updated_at' => $date->toDateTimeString(),
        ]);
    }

    private function seedPayment(int $billId, float $amount, Carbon $paidAt): int
    {
        return DB::table('payments')->insertGetId([
            'bill_id' => $billId,
            'transaction_id' => uniqid('TXN-'),
            'payment_method' => 'qris',
            'amount' => $amount,
            'paid_at' => $paidAt->toDateTimeString(),
            'approved_by' => null,
            'created_at' => $paidAt->toDateTimeString(),
            'updated_at' => $paidAt->toDateTimeString(),
        ]);
    }

    private function authedRequest(User $user, string $query = '')
    {
        $token = $user->createToken('test-token')->plainTextToken;

        return $this->getJson("/api/dashboard/stats{$query}", [
            'Authorization' => "Bearer {$token}",
        ]);
    }

    public function test_admin_can_access_stats_structure_with_seeded_data(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Parkir');
        $class = $this->seedClassification($opd, $type, 'Parkir Tepi Jalan');
        $taxpayer = $this->seedTaxpayer($opd, 'Kios A');
        $now = Carbon::now();
        $bill = $this->seedBill($opd, $taxpayer, $type, $class, 100000, 'lunas', $now);
        $this->seedPayment($bill, 100000, $now);
        $this->seedBill($opd, $taxpayer, $type, $class, 50000, 'pending', $now);

        $admin = $this->seedUser(['role' => 'admin']);
        $response = $this->authedRequest($admin);

        $response->assertStatus(200)
            ->assertJsonPath('total_revenue', 100000)
            ->assertJsonPath('pending_bills', 1)
            ->assertJsonPath('active_taxpayers', 1)
            ->assertJsonPath('petugas_achievement', null)
            ->assertJsonStructure([
                'trends' => ['revenue', 'collection_rate', 'pending_bills', 'active_taxpayers'],
                'revenue_by_type',
                'revenue_by_classification',
            ]);

        $this->assertEquals(50, (float) $response->json('collection_rate'));

        $byType = $response->json('revenue_by_type');
        $this->assertCount(1, $byType);
        $this->assertEquals('Parkir', $byType[0]['name']);
        $this->assertEquals(100000, (float) $byType[0]['total']);

        $byClass = $response->json('revenue_by_classification');
        $this->assertCount(1, $byClass);
        $this->assertEquals('Parkir Tepi Jalan', $byClass[0]['name']);
        $this->assertEquals(100000, (float) $byClass[0]['total']);
    }

    public function test_default_filter_limits_to_current_month(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Parkir');
        $class = $this->seedClassification($opd, $type, 'Parkir');
        $taxpayer = $this->seedTaxpayer($opd, 'Kios A');

        $now = Carbon::now();
        $billNow = $this->seedBill($opd, $taxpayer, $type, $class, 250000, 'lunas', $now);
        $this->seedPayment($billNow, 250000, $now);

        $billOld = $this->seedBill($opd, $taxpayer, $type, $class, 900000, 'lunas', $now->copy()->subMonths(3));
        $this->seedPayment($billOld, 900000, $now->copy()->subMonths(3));

        $response = $this->authedRequest($this->seedUser(['role' => 'admin']));

        $response->assertStatus(200);
        $this->assertEquals(250000, (float) $response->json('total_revenue'));
    }

    public function test_stats_respects_provided_date_range(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Parkir');
        $class = $this->seedClassification($opd, $type, 'Parkir');
        $taxpayer = $this->seedTaxpayer($opd, 'Kios A');

        $billJan = $this->seedBill($opd, $taxpayer, $type, $class, 50000, 'lunas', Carbon::parse('2025-01-10'));
        $this->seedPayment($billJan, 50000, Carbon::parse('2025-01-10 09:00:00'));

        $billMay = $this->seedBill($opd, $taxpayer, $type, $class, 70000, 'lunas', Carbon::parse('2025-05-20'));
        $this->seedPayment($billMay, 70000, Carbon::parse('2025-05-20 09:00:00'));

        $response = $this->authedRequest(
            $this->seedUser(['role' => 'admin']),
            '?start_date=2025-01-01&end_date=2025-01-31'
        );

        $response->assertStatus(200);
        $this->assertEquals(50000, (float) $response->json('total_revenue'));
        $this->assertEquals(0, $response->json('pending_bills'));
        $this->assertEquals(100.0, $response->json('collection_rate'));
    }

    public function test_malformed_date_range_returns_422(): void
    {
        $admin = $this->seedUser(['role' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->getJson('/api/dashboard/stats?start_date=2025-13-45&end_date=2025-abc', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    public function test_opd_user_only_sees_own_opd_data(): void
    {
        $opdA = $this->seedOpd('OPD-A');
        $opdB = $this->seedOpd('OPD-B');

        $typeA = $this->seedType($opdA, 'Parkir A');
        $classA = $this->seedClassification($opdA, $typeA, 'Parkir A');
        $txA = $this->seedTaxpayer($opdA, 'Kios A');
        $now = Carbon::now();
        $billA = $this->seedBill($opdA, $txA, $typeA, $classA, 10000, 'lunas', $now);
        $this->seedPayment($billA, 10000, $now);

        $typeB = $this->seedType($opdB, 'Parkir B');
        $classB = $this->seedClassification($opdB, $typeB, 'Parkir B');
        $txB = $this->seedTaxpayer($opdB, 'Kios B');
        $this->seedTaxpayer($opdB, 'Kios B2');
        $billB = $this->seedBill($opdB, $txB, $typeB, $classB, 999999, 'lunas', $now);
        $this->seedPayment($billB, 999999, $now);

        $opdUser = $this->seedUser(['role' => 'opd', 'opd_id' => $opdA]);
        $response = $this->authedRequest($opdUser);

        $response->assertStatus(200);
        $this->assertEquals(10000, (float) $response->json('total_revenue'));
        $this->assertEquals(1, $response->json('active_taxpayers'));
    }

    public function test_petugas_achievement_present_for_petugas_without_assignments(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $petugas = $this->seedUser(['role' => 'petugas', 'opd_id' => $opd]);
        $response = $this->authedRequest($petugas);

        $response->assertStatus(200)
            ->assertJsonPath('petugas_achievement.collections_count', 0)
            ->assertJsonPath('petugas_achievement.total_amount', 0)
            ->assertJsonPath('petugas_achievement.taxpayers_registered', 0);

        $this->assertEmpty($response->json('revenue_by_type'));
    }

    public function test_citizen_role_is_forbidden(): void
    {
        $citizen = $this->seedUser(['role' => 'citizen']);
        $response = $this->authedRequest($citizen);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/dashboard/stats');

        $response->assertStatus(401);
    }
}