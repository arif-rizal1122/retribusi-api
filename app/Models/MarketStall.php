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

    protected $appends = [
        'latitude',
        'longitude',
        'is_claimed',
        'claim_status',
    ];

    public function getLatitudeAttribute()
    {
        $baseLat = $this->taxObject?->latitude 
            ?? $this->zone?->latitude 
            ?? $this->market?->latitude;
            
        if ($baseLat === null) return null;
        
        $num = (int) preg_replace('/\D/', '', (string) $this->stall_number) ?: 1;
        $offset = (($num % 7) - 3) * 0.000035;
        return (float) $baseLat + $offset;
    }

    public function getLongitudeAttribute()
    {
        $baseLng = $this->taxObject?->longitude 
            ?? $this->zone?->longitude 
            ?? $this->market?->longitude;
            
        if ($baseLng === null) return null;
        
        $num = (int) preg_replace('/\D/', '', (string) $this->stall_number) ?: 1;
        $offset = (floor($num / 7) % 7 - 3) * 0.000035;
        return (float) $baseLng + $offset;
    }

    public function getIsClaimedAttribute(): bool
    {
        return !empty($this->metadata['is_claimed']);
    }

    public function getClaimStatusAttribute(): ?string
    {
        return $this->metadata['claim_status'] ?? null;
    }

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
