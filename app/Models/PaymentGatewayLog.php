<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewayLog extends Model
{
    protected $fillable = [
        'bill_number',
        'endpoint',
        'method',
        'payload_in',
        'payload_out',
        'ip_address',
        'status_code'
    ];

    protected $casts = [
        'payload_in' => 'array',
        'payload_out' => 'array'
    ];
}
