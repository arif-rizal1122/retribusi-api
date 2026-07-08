<?php

namespace App\Services\Payment\Snap;

use App\Services\Payment\Snap\Exceptions\SnapValidationException;
use Carbon\CarbonImmutable;

class SnapTimestampValidator
{
    public function validate(?string $timestamp): void
    {
        if (!$timestamp) {
            throw SnapValidationException::missing('Bad Request. Missing X-TIMESTAMP.');
        }

        try {
            $requestTime = CarbonImmutable::parse($timestamp);
        } catch (\Throwable) {
            throw new SnapValidationException('4002401', 'Bad Request. Invalid X-TIMESTAMP.', 400);
        }

        $tolerance = (int) config('snap.timestamp_tolerance_seconds', 300);

        if (abs($requestTime->diffInSeconds(now(), false)) > $tolerance) {
            throw SnapValidationException::unauthorized('Unauthorized. Timestamp expired.');
        }
    }
}
