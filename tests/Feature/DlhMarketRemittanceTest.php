<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DlhMarketRemittanceTest extends TestCase
{
    use RefreshDatabase;

    private User $collector;
    private User $treasurer;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('opds')->updateOrInsert(['id' => 4], [
            'name' => 'Dinas Lingkungan Hidup',
            'code' => 'DLH',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->collector = User::create([
            'name' => 'Petugas Juru Pungut Pasar Karya Nugraha',
            'email' => 'pungut.pasar@baubaukota.go.id',
            'password' => bcrypt('password'),
            'role' => 'petugas',
            'opd_id' => 4,
        ]);

        $this->treasurer = User::create([
            'name' => 'Bendahara Penerimaan DLH',
            'email' => 'bendahara.dlh@baubaukota.go.id',
            'password' => bcrypt('password'),
            'role' => 'opd',
            'opd_id' => 4,
        ]);
    }

    public function test_issue_market_tickets_and_check_holding_balance(): void
    {
        Sanctum::actingAs($this->collector, ['*']);

        // 1. Tarik karcis lapak kecil Rp 3.000 tunai
        $ticket1 = $this->postJson('/api/v1/dlh/market/tickets', [
            'market_name' => 'Pasar Karya Nugraha',
            'stall_name_or_number' => 'Blok Sayur No. 12',
            'merchant_name' => 'Ibu Rahma',
            'amount' => 3000,
            'payment_method' => 'TUNAI',
        ]);

        $ticket1->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.amount', 3000)
            ->assertJsonPath('data.payment_method', 'TUNAI');

        // 2. Tarik karcis los besar Rp 6.000 QRIS
        $ticket2 = $this->postJson('/api/v1/dlh/market/tickets', [
            'market_name' => 'Pasar Karya Nugraha',
            'stall_name_or_number' => 'Los Ikan No. 04',
            'merchant_name' => 'Pak Ruslan',
            'amount' => 6000,
            'payment_method' => 'QRIS_INSTANT',
        ]);

        $ticket2->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.amount', 6000);

        // 3. Cek holding balance juru pungut
        $balanceRes = $this->getJson('/api/v1/dlh/market/holding-balance');

        $balanceRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.unremitted_ticket_count', 2)
            ->assertJsonPath('data.holding_balance', 9000)
            ->assertJsonPath('data.tickets_summary.total_cash', 3000)
            ->assertJsonPath('data.tickets_summary.total_qris', 6000);
    }

    public function test_submit_remittance_and_verify(): void
    {
        Sanctum::actingAs($this->collector, ['*']);

        // Terbitkan 2 karcis Rp 3.000
        $this->postJson('/api/v1/dlh/market/tickets', [
            'market_name' => 'Pasar Wameo',
            'amount' => 3000,
            'payment_method' => 'TUNAI',
        ]);
        $this->postJson('/api/v1/dlh/market/tickets', [
            'market_name' => 'Pasar Wameo',
            'amount' => 3000,
            'payment_method' => 'TUNAI',
        ]);

        $currentMonth = (int) date('n');
        $currentYear = (int) date('Y');

        // Ajukan setoran akhir bulan ke Kasda (Submit Remittance)
        $remitRes = $this->postJson('/api/v1/dlh/market/remittance', [
            'period_month' => $currentMonth,
            'period_year' => $currentYear,
            'remitted_amount' => 6000,
            'bank_reference_number' => 'STS-SULTRA-202609-0019',
            'notes' => 'Setoran karcis sampah Pasar Wameo minggu ke-2',
        ]);

        $remitRes->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.total_amount_collected', 6000)
            ->assertJsonPath('data.status', 'SUBMITTED');

        $remittanceId = $remitRes->json('data.id');

        // Pastikan karcis terkunci ke remittance_id tersebut
        $lockedTicketsCount = DB::table('dlh_market_trash_tickets')
            ->where('remittance_id', $remittanceId)
            ->count();
        $this->assertEquals(2, $lockedTicketsCount);

        // Setelah disetor, holding balance menjadi 0
        $balanceAfter = $this->getJson("/api/v1/dlh/market/holding-balance?month={$currentMonth}&year={$currentYear}");
        $balanceAfter->assertJsonPath('data.holding_balance', 0);

        // Bendahara DLH memverifikasi setoran
        Sanctum::actingAs($this->treasurer, ['*']);
        $verifyRes = $this->postJson("/api/v1/dlh/market/remittance/{$remittanceId}/verify");

        $verifyRes->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('dlh_collector_remittances', [
            'id' => $remittanceId,
            'status' => 'RECONCILED',
            'verified_by' => $this->treasurer->id,
        ]);
    }
}
