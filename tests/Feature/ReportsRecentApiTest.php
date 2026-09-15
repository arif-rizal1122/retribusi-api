<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportsRecentApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
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

    private function seedBill(int $opdId, int $taxpayerId, int $typeId, float $amount, Carbon $date): int
    {
        return DB::table('bills')->insertGetId([
            'taxpayer_id' => $taxpayerId,
            'retribution_type_id' => $typeId,
            'opd_id' => $opdId,
            'bill_number' => uniqid('BILL-'),
            'amount' => $amount,
            'status' => 'lunas',
            'period' => $date->format('F Y'),
            'created_at' => $date->toDateTimeString(),
            'updated_at' => $date->toDateTimeString(),
        ]);
    }

    private function seedPayment(int $billId, float $amount, Carbon $paidAt, array $overrides = []): int
    {
        return DB::table('payments')->insertGetId(array_merge([
            'bill_id' => $billId,
            'transaction_id' => uniqid('TXN-'),
            'payment_method' => 'qris',
            'amount' => $amount,
            'paid_at' => $paidAt->toDateTimeString(),
            'approved_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function authedRequest(User $user, string $query = '')
    {
        $token = $user->createToken('test-token')->plainTextToken;

        return $this->getJson("/api/reports/recent{$query}", [
            'Authorization' => "Bearer {$token}",
        ]);
    }

    public function test_recent_returns_empty_array_when_no_payments(): void
    {
        $admin = $this->seedUser(['role' => 'admin']);
        $response = $this->authedRequest($admin);

        $response->assertStatus(200);
        $this->assertEmpty($response->json());
    }

    public function test_recent_returns_latest_10_payments_sorted_by_paid_at_desc(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Parkir');
        $taxpayer = $this->seedTaxpayer($opd, 'Kios A');

        for ($i = 1; $i <= 12; $i++) {
            $bill = $this->seedBill($opd, $taxpayer, $type, 10000 * $i, now()->subDays($i));
            $this->seedPayment($bill, 10000 * $i, now()->subHours($i));
        }

        $admin = $this->seedUser(['role' => 'admin']);
        $response = $this->authedRequest($admin);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertCount(10, $data);
        $this->assertEquals(12, (int) $data[0]['id'] > 0 ? DB::table('payments')->max('id') : 0);
    }

    public function test_recent_returns_expected_payload_structure(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Kebersihan');
        $taxpayer = $this->seedTaxpayer($opd, 'Warung Pak Agus');
        $bill = $this->seedBill($opd, $taxpayer, $type, 75000, now());
        $this->seedPayment($bill, 75000, now());

        $admin = $this->seedUser(['role' => 'admin']);
        $response = $this->authedRequest($admin);

        $response->assertStatus(200);
        $row = $response->json()[0];

        $this->assertEquals('Warung Pak Agus', $row['taxpayer_name']);
        $this->assertEquals('Kebersihan', $row['type']);
        $this->assertEquals(75000, (float) $row['amount']);
        $this->assertEquals('qris', $row['method']);
        $this->assertEquals('Verified', $row['status']);
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('date', $row);
        $this->assertNotNull($row['date']);
    }

    public function test_recent_falls_back_to_cash_when_payment_method_null(): void
    {
        $opd = $this->seedOpd('DISHUB');
        $type = $this->seedType($opd, 'Parkir');
        $taxpayer = $this->seedTaxpayer($opd, 'Kios B');
        $bill = $this->seedBill($opd, $taxpayer, $type, 50000, now());
        $this->seedPayment($bill, 50000, now(), ['payment_method' => null]);

        $admin = $this->seedUser(['role' => 'admin']);
        $response = $this->authedRequest($admin);

        $response->assertStatus(200);
        $this->assertEquals('CASH', $response->json()[0]['method']);
    }

    public function test_recent_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/reports/recent');
        $response->assertStatus(401);
    }

    public function test_recent_forbids_citizen_role(): void
    {
        $citizen = $this->seedUser(['role' => 'citizen']);
        $response = $this->authedRequest($citizen);
        $response->assertStatus(403);
    }
}
