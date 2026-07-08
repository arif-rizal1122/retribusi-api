<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SnapIdempotencyKey extends Model
{
    protected $fillable = [
        'external_id',
        'endpoint',
        'request_hash',
        'response_payload',
        'status_code',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'response_payload' => 'array',
        'expires_at' => 'datetime',
    ];
}
