<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardRevenueTrendApiTest extends TestCase
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

    private function seedBill(int $opdId, int $taxpayerId, int $typeId, float $amount, string $status, Carbon $date): int
    {
        return DB::table('bills')->insertGetId([
            'taxpayer_id' => $taxpayerId,
            'tax_object_id' => null,
            'retribution_type_id' => $typeId,
            'retribution_classification_id' => null,
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

        return $this->getJson("/api/dashboard/revenue-trend{$query}", [
            'Authorization' => "Bearer {$token}",
        ]);
    }

    public function test_admin_can_access_revenue_trend_with_date_scope(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Parkir');
        $taxpayer = $this->seedTaxpayer($opd, 'Kios A');

        $billJan = $this->seedBill($opd, $taxpayer, $type, 100000, 'lunas', Carbon::parse('2025-01-05'));
        $this->seedPayment($billJan, 100000, Carbon::parse('2025-01-05 09:00:00'));

        $billMay = $this->seedBill($opd, $taxpayer, $type, 70000, 'lunas', Carbon::parse('2025-05-20'));
        $this->seedPayment($billMay, 70000, Carbon::parse('2025-05-20 09:00:00'));

        $admin = $this->seedUser(['role' => 'admin']);
        $response = $this->authedRequest($admin, '?start_date=2025-01-01&end_date=2025-01-31');

        $response->assertStatus(200)->assertOk();

        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('month', $data[0]);
        $this->assertArrayHasKey('amount', $data[0]);
        $this->assertEquals('05 Jan', $data[0]['month']);
        $this->assertEquals(100000, (float) $data[0]['amount']);
    }

    public function test_short_range_returns_daily_grouped_points(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Parkir');
        $taxpayer = $this->seedTaxpayer($opd, 'Kios A');

        $bill1 = $this->seedBill($opd, $taxpayer, $type, 50000, 'lunas', Carbon::parse('2025-01-05'));
        $this->seedPayment($bill1, 50000, Carbon::parse('2025-01-05 09:00:00'));

        $bill2 = $this->seedBill($opd, $taxpayer, $type, 25000, 'lunas', Carbon::parse('2025-01-07'));
        $this->seedPayment($bill2, 25000, Carbon::parse('2025-01-07 10:30:00'));

        $response = $this->authedRequest(
            $this->seedUser(['role' => 'admin']),
            '?start_date=2025-01-01&end_date=2025-01-10'
        );

        $response->assertOk();

        $data = $response->json();
        $this->assertCount(2, $data);
        $this->assertEquals('05 Jan', $data[0]['month']);
        $this->assertEquals(50000, (float) $data[0]['amount']);
        $this->assertEquals('07 Jan', $data[1]['month']);
        $this->assertEquals(25000, (float) $data[1]['amount']);
    }

    public function test_long_range_returns_monthly_grouped_points(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Parkir');
        $taxpayer = $this->seedTaxpayer($opd, 'Kios A');

        $billJan = $this->seedBill($opd, $taxpayer, $type, 50000, 'lunas', Carbon::parse('2025-01-10'));
        $this->seedPayment($billJan, 50000, Carbon::parse('2025-01-10 09:00:00'));

        $billFeb = $this->seedBill($opd, $taxpayer, $type, 30000, 'lunas', Carbon::parse('2025-02-10'));
        $this->seedPayment($billFeb, 30000, Carbon::parse('2025-02-10 09:00:00'));

        $response = $this->authedRequest(
            $this->seedUser(['role' => 'admin']),
            '?start_date=2025-01-01&end_date=2025-03-31'
        );

        $response->assertOk();
        $this->assertGreaterThan(0, count($response->json()));
    }

    public function test_default_range_returns_last_six_months(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Parkir');
        $taxpayer = $this->seedTaxpayer($opd, 'Kios A');
        $now = Carbon::now();

        $bill = $this->seedBill($opd, $taxpayer, $type, 100000, 'lunas', $now);
        $this->seedPayment($bill, 100000, $now);

        $response = $this->authedRequest($this->seedUser(['role' => 'admin']));

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, count($response->json()));
    }

    public function test_opd_user_only_sees_own_opd_data(): void
    {
        $opdA = $this->seedOpd('OPD-A');
        $opdB = $this->seedOpd('OPD-B');

        $typeA = $this->seedType($opdA, 'Parkir A');
        $txA = $this->seedTaxpayer($opdA, 'Kios A');
        $nowA = Carbon::parse('2025-01-05');
        $billA = $this->seedBill($opdA, $txA, $typeA, 10000, 'lunas', $nowA);
        $this->seedPayment($billA, 10000, Carbon::parse('2025-01-05 08:00:00'));

        $typeB = $this->seedType($opdB, 'Parkir B');
        $txB = $this->seedTaxpayer($opdB, 'Kios B');
        $nowB = Carbon::parse('2025-01-06');
        $billB = $this->seedBill($opdB, $txB, $typeB, 999999, 'lunas', $nowB);
        $this->seedPayment($billB, 999999, Carbon::parse('2025-01-06 08:00:00'));

        $opdUser = $this->seedUser(['role' => 'opd', 'opd_id' => $opdA]);
        $response = $this->authedRequest($opdUser, '?start_date=2025-01-01&end_date=2025-01-31');

        $response->assertOk();

        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals(10000, (float) $data[0]['amount']);
    }

    public function test_no_payments_in_range_returns_empty_array(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Parkir');
        $taxpayer = $this->seedTaxpayer($opd, 'Kios A');

        $bill = $this->seedBill($opd, $taxpayer, $type, 50000, 'pending', Carbon::parse('2025-06-10'));

        $response = $this->authedRequest(
            $this->seedUser(['role' => 'admin']),
            '?start_date=2025-01-01&end_date=2025-01-31'
        );

        $response->assertOk();
        $response->assertJson([]);
    }

    public function test_citizen_role_is_forbidden(): void
    {
        $citizen = $this->seedUser(['role' => 'citizen']);
        $response = $this->authedRequest($citizen);

        $response->assertStatus(403);
    }

    public function test_wajib_pajak_role_is_forbidden(): void
    {
        $wp = $this->seedUser(['role' => 'wajib_pajak']);
        $response = $this->authedRequest($wp);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/dashboard/revenue-trend');
        $response->assertStatus(401);
    }

    public function test_malformed_date_range_returns_422(): void
    {
        $admin = $this->seedUser(['role' => 'admin']);
        $response = $this->authedRequest($admin, '?start_date=2025-13-45&end_date=2025-abc');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }
}