<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketStall extends Model
{
    use HasFactory;

    protected $fillable = [
        'market_id',
        'zone_id',
        'tax_object_id',
        'taxpayer_id',
        'market_name',
        'building_name',
        'stall_type',
        'stall_number',
        'full_code',
        'merchant_name',
        'tariff_amount',
        'billing_cycle',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tariff_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function taxObject(): BelongsTo
    {
        return $this->belongsTo(TaxObject::class);
    }

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeSealed($query)
    {
        return $query->where('status', 'tersegel');
    }

    public function scopeVacant($query)
    {
        return $query->where('status', 'kosong');
    }

    public function scopeKios($query)
    {
        return $query->where('stall_type', 'kios');
    }

    public function scopeLos($query)
    {
        return $query->where('stall_type', 'los');
    }
}
