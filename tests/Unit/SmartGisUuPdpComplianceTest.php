<?php

namespace Tests\Unit;

use App\Support\NopHelper;
use App\Support\SpatialAuditLogger;
use Tests\TestCase;

class SmartGisUuPdpComplianceTest extends TestCase
{
    /**
     * Pilar 1: NOP Masking (18 digit standard).
     */
    public function test_nop_masking_standard()
    {
        $nop = '747201000100100010';
        $masked = NopHelper::mask($nop);

        $this->assertEquals('74.720.***.***.***-001.0', $masked);
    }

    /**
     * Pilar 1: Name Masking for UU PDP No. 27/2022.
     */
    public function test_taxpayer_name_masking()
    {
        $this->assertEquals('M***** F***', NopHelper::maskName('MUHDAN FYAN'));
        $this->assertEquals('B*** S*****', NopHelper::maskName('BUDI SANTOSO'));
        $this->assertEquals('-', NopHelper::maskName(null));
    }

    /**
     * Pilar 3: Haversine distance calculation for geofencing.
     */
    public function test_haversine_distance_calculation()
    {
        // Jarak dari Pusat Kota Baubau (-5.4667, 122.6167) ke titik dekat (~520 m)
        $distance = SpatialAuditLogger::calculateDistanceKm(-5.4667, 122.6167, -5.4700, 122.6200);

        $this->assertGreaterThan(0.4, $distance);
        $this->assertLessThan(0.7, $distance);
    }

    /**
     * Pilar 3: Remote claim detection threshold (> 10 km).
     */
    public function test_remote_claim_detection()
    {
        // Dari Baubau (-5.4667, 122.6167) ke Buton Tengah (-5.3000, 122.5000) > 20 km
        $distance = SpatialAuditLogger::calculateDistanceKm(-5.4667, 122.6167, -5.3000, 122.5000);

        $this->assertGreaterThan(10.0, $distance);
    }
}
