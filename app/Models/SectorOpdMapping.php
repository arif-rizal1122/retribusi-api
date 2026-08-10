<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * M37-2 — Mapping sektor usaha → OPD pengampu (Dok 143).
 */
class SectorOpdMapping extends Model
{
    protected $fillable = [
        'sector_id',
        'opd_id',
        'is_primary',
        'source_note',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }
}
