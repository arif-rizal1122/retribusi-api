<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\Auditable;

class AssetItem extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'opd_id',
        'code',
        'name',
        'category',
        'merk_type',
        'spesifikasi',
        'kondisi',
        'status_operasional',
        'lokasi',
        'tarif',
        'satuan_tarif',
        'wajib_tronton',
        'image_url',
        'pic',
        'metadata',
        'is_active',
        'is_demo',
    ];

    protected $casts = [
        'tarif' => 'float',
        'wajib_tronton' => 'boolean',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_demo' => 'boolean',
    ];

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(AssetRental::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}