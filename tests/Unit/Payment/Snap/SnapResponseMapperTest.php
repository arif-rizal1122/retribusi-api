<?php

namespace Tests\Unit\Payment\Snap;

use App\Services\Payment\Snap\SnapResponseMapper;
use Tests\TestCase;

class SnapResponseMapperTest extends TestCase
{
    public function test_it_maps_snap_access_token_response(): void
    {
        $response = app(SnapResponseMapper::class)->accessToken('token-value', 900);

        $this->assertSame('2007300', $response['responseCode']);
        $this->assertSame('Bearer', $response['tokenType']);
        $this->assertSame('900', $response['expiresIn']);
    }
}
