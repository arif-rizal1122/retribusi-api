<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TteLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_number',
        'endpoint',
        'request_payload',
        'response_code',
        'response_body',
        'duration_ms',
        'status',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_body' => 'array',
        'duration_ms' => 'integer',
        'response_code' => 'integer',
    ];
}
