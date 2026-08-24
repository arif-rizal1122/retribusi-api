<?php

namespace App\Services\Payment\Snap\Exceptions;

use RuntimeException;

class SnapValidationException extends RuntimeException
{
    public function __construct(
        public readonly string $snapCode,
        public readonly string $snapMessage,
        public readonly int $httpStatus = 400
    ) {
        parent::__construct($snapMessage, $httpStatus);
    }

    public static function missing(string $field, string $serviceCode = '24'): self
    {
        return new self("400{$serviceCode}02", "Invalid Mandatory Field {{$field}}", 400);
    }

    public static function invalidFormat(string $field, string $serviceCode = '24'): self
    {
        return new self("400{$serviceCode}01", "Invalid Field Format {{$field}}", 400);
    }

    public static function unauthorized(string $message = 'Unauthorized Signature', string $serviceCode = '24'): self
    {
        $reason = preg_replace('/^Unauthorized\.?\s*/', '', $message) ?: 'Request';

        return new self("401{$serviceCode}00", "Unauthorized. {$reason}", 401);
    }

    public static function invalidToken(string $serviceCode = '24'): self
    {
        return new self("401{$serviceCode}01", 'Invalid Token (B2B)', 401);
    }
}
