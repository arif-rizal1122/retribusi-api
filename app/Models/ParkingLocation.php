<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\Auditable;

class ParkingLocation extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'code',
        'name',
        'category',
        'opd_id',
        'retribution_type_id',
        'retribution_classification_id',
        'rate_r2',
        'rate_r4',
        'base_qris_payload',
        'nmid',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'rate_r2' => 'integer',
        'rate_r4' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function retributionType(): BelongsTo
    {
        return $this->belongsTo(RetributionType::class);
    }

    public function classification(): BelongsTo
    {
        return $this->belongsTo(RetributionClassification::class, 'retribution_classification_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(ParkingShift::class, 'parking_location_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ParkingSession::class, 'parking_location_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}