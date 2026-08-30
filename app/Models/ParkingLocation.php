<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParkingLocation extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'opd_id',
        'classification_id',
        'rate_r2',
        'rate_r4',
        'base_qris_payload',
        'nmid',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'rate_r2' => 'decimal:2',
        'rate_r4' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
    ];

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForOpd($query, int $opdId)
    {
        return $query->where('opd_id', $opdId);
    }

    // ── Relations ──

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function classification(): BelongsTo
    {
        return $this->belongsTo(RetributionClassification::class, 'classification_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ParkingSession::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(JukirWallet::class);
    }

    // ── Helpers ──

    public function rateFor(string $vehicleType): float
    {
        return $vehicleType === 'r4' ? (float) $this->rate_r4 : (float) $this->rate_r2;
    }
}
