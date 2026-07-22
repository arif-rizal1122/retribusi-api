<?php

namespace App\Services\Payment\Snap;

use App\Services\Payment\Snap\Exceptions\SnapValidationException;
use Carbon\CarbonImmutable;

class SnapTimestampValidator
{
    public function validate(?string $timestamp, string $serviceCode = '24'): void
    {
        if (! $timestamp) {
            throw SnapValidationException::missing('X-TIMESTAMP', $serviceCode);
        }

        try {
            $requestTime = CarbonImmutable::parse($timestamp);
        } catch (\Throwable) {
            throw SnapValidationException::invalidFormat('X-TIMESTAMP', $serviceCode);
        }

        $tolerance = (int) config('snap.timestamp_tolerance_seconds', 300);

        if (abs($requestTime->diffInSeconds(now(), false)) > $tolerance) {
            throw SnapValidationException::unauthorized('Unauthorized Timestamp', $serviceCode);
        }
    }
}
