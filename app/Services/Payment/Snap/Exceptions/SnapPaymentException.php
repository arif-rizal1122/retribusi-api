<?php

namespace App\Services\Payment\Snap\Exceptions;

use RuntimeException;

class SnapPaymentException extends RuntimeException
{
    public function __construct(
        public readonly string $snapCode,
        public readonly string $snapMessage,
        public readonly int $httpStatus = 400
    ) {
        parent::__construct($snapMessage, $httpStatus);
    }
}
