<?php

namespace App\Services\Payment\Snap\Exceptions;

use App\Models\SnapIdempotencyKey;
use RuntimeException;

class DuplicateSnapExternalIdException extends RuntimeException
{
    public function __construct(public readonly SnapIdempotencyKey $record)
    {
        parent::__construct('Duplicate X-EXTERNAL-ID.');
    }
}
