<?php

namespace Tests\Unit\Payment\Snap;

use App\Services\Payment\Snap\Exceptions\DuplicateSnapExternalIdException;
use App\Services\Payment\Snap\SnapIdempotencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SnapIdempotencyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_duplicate_external_id(): void
    {
        $service = app(SnapIdempotencyService::class);
        $request = Request::create('/api/snap/v1.0/transfer-va/inquiry', 'POST', [], [], [], [
            'HTTP_X_EXTERNAL_ID' => 'EXT-DUPLICATE-UNIT',
        ], '{"customerNo":"SKRD-UNIT"}');

        $service->reserve($request, '24');

        $this->expectException(DuplicateSnapExternalIdException::class);

        $service->reserve($request, '24');
    }
}
