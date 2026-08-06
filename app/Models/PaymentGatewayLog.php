<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * PaymentGatewayLog
 *
 * Log audit setiap pemanggilan ke payment gateway (createPayment / checkStatus)
 * pada tabel produksi `payment_gateway_logs`.
 */
class PaymentGatewayLog extends Model
{
    protected $table = 'payment_gateway_logs';

    protected $fillable = [
        'bill_number',
        'endpoint',
        'method',
        'payload_in',
        'payload_out',
        'ip_address',
        'status_code',
    ];

    protected $casts = [
        'payload_in' => 'array',
        'payload_out' => 'array',
    ];
}
