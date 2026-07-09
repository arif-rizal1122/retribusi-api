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

    public static function missing(string $message = 'Bad Request. Missing Mandatory Field.', string $code = '4002402'): self
    {
        return new self($code, $message, 400);
    }

    public static function unauthorized(string $message = 'Unauthorized.'): self
    {
        return new self('4012400', $message, 401);
    }

    public static function invalidToken(string $message = 'Unauthorized. Invalid Token.'): self
    {
        return new self('4017300', $message, 401);
    }
}
