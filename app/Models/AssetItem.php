<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetItem extends Model
{
    protected $fillable = [
        'category_id',
        'opd_id',
        'name',
        'code',
        'merk_type',
        'spesifikasi',
        'kondisi',
        'status_operasional',
        'lokasi',
        'pic',
        'tarif_default',
        'satuan_tarif',
        'buffer_default',
        'is_demo',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'tarif_default' => 'decimal:2',
        'buffer_default' => 'integer',
        'is_demo' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(AssetRental::class, 'asset_item_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status_operasional', 'Operasional');
    }
}
