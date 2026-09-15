<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\RetributionType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PublicRetributionTypesTest extends TestCase
{
    use DatabaseTransactions;

    protected $opd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opd = Opd::create([
            'name' => 'Dinas Lingkungan Hidup',
            'code' => 'DLH-001'
        ]);
    }

    private function makeType(string $name, bool $isActive): RetributionType
    {
        return RetributionType::create([
            'opd_id' => $this->opd->id,
            'name' => $name,
            'unit' => 'Bulan',
            'base_amount' => 10000,
            'is_active' => $isActive,
        ]);
    }

    /** @test */
    public function it_returns_only_active_types_by_default()
    {
        $this->makeType('Retribusi Aktif A', true);
        $this->makeType('Retribusi Aktif B', true);
        $this->makeType('Retribusi Non Aktif', false);

        $response = $this->getJson('/api/public/retribution-types');

        $response->assertOk();
        $names = collect($response->json())->pluck('name');
        $this->assertContains('Retribusi Aktif A', $names);
        $this->assertContains('Retribusi Aktif B', $names);
        $this->assertNotContains('Retribusi Non Aktif', $names);
    }

    /** @test */
    public function it_only_returns_active_types_when_is_active_equals_1()
    {
        $this->makeType('Retribusi Aktif', true);
        $this->makeType('Retribusi Non Aktif', false);

        $response = $this->getJson('/api/public/retribution-types?is_active=1');

        $response->assertOk();
        $names = collect($response->json())->pluck('name');
        $this->assertContains('Retribusi Aktif', $names);
        $this->assertNotContains('Retribusi Non Aktif', $names);
    }

    /** @test */
    public function it_returns_empty_array_when_no_types_exist()
    {
        $response = $this->getJson('/api/public/retribution-types?is_active=1');

        $response->assertOk();
        $this->assertEmpty($response->json());
    }

    /** @test */
    public function it_includes_the_opd_relation_and_sorts_by_name()
    {
        $this->makeType('Zebra Retribusi', true);
        $this->makeType('Alpha Retribusi', true);

        $response = $this->getJson('/api/public/retribution-types');

        $response->assertOk();
        $data = $response->json();

        $this->assertSame('Alpha Retribusi', $data[0]['name']);
        $this->assertSame('Zebra Retribusi', $data[1]['name']);
        $this->assertArrayHasKey('opd', $data[0]);
        $this->assertSame($this->opd->name, $data[0]['opd']['name'] ?? null);
    }
}
