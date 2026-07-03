<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransactionLog extends Model
{
    protected $fillable = [
        'bank_config_id',
        'bill_id',
        'tipe',
        'request_payload',
        'response_payload',
        'status',
        'reference_number',
        'error_message',
        'ip_address',
        'waktu_request',
        'waktu_response',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'waktu_request' => 'datetime',
        'waktu_response' => 'datetime',
    ];

    public function bankConfig(): BelongsTo
    {
        return $this->belongsTo(BankConfig::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    // --- Scope ---

    public function scopeByBank($query, $kodeBank)
    {
        return $query->whereHas('bankConfig', fn($q) => $q->where('kode_bank', $kodeBank));
    }
}