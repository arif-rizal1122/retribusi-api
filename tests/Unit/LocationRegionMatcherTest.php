<?php

namespace Tests\Unit;

use App\Http\Controllers\LocationController;
use PHPUnit\Framework\TestCase;

class LocationRegionMatcherTest extends TestCase
{
    public function test_region_normalizer_removes_administrative_prefixes(): void
    {
        $this->assertSame('kadolo', LocationController::normalizeRegionName('Kelurahan Kadolo'));
        $this->assertSame('kadia', LocationController::normalizeRegionName('Kel. Kadia'));
        $this->assertSame('betoambari', LocationController::normalizeRegionName('Kecamatan Betoambari'));
        $this->assertSame('lipu', LocationController::normalizeRegionName('Desa Lipu'));
    }

    public function test_region_similarity_accepts_safe_matches_and_rejects_false_positive(): void
    {
        $this->assertGreaterThanOrEqual(85, LocationController::regionSimilarity('Kelurahan Kadolo', 'Kadolo'));
        $this->assertGreaterThanOrEqual(85, LocationController::regionSimilarity('Kec. Betoambari', 'Betoambari'));
        $this->assertLessThan(85, LocationController::regionSimilarity('Kadolo', 'Kadia'));
    }
}
