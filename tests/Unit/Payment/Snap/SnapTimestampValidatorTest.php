<?php

namespace Tests\Unit\Payment\Snap;

use App\Services\Payment\Snap\Exceptions\SnapValidationException;
use App\Services\Payment\Snap\SnapTimestampValidator;
use Tests\TestCase;

class SnapTimestampValidatorTest extends TestCase
{
    public function test_it_accepts_timestamp_inside_tolerance(): void
    {
        config(['snap.timestamp_tolerance_seconds' => 300]);

        app(SnapTimestampValidator::class)->validate(now()->toIso8601String());

        $this->assertTrue(true);
    }

    public function test_it_rejects_expired_timestamp(): void
    {
        config(['snap.timestamp_tolerance_seconds' => 60]);

        $this->expectException(SnapValidationException::class);

        app(SnapTimestampValidator::class)->validate(now()->subMinutes(5)->toIso8601String());
    }
}
