<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxpayerPbbObject extends Model
{
    protected $table = 'taxpayer_pbb_objects';

    protected $fillable = [
        'taxpayer_id',
        'nop',
        'nib_code',
        'znt_zone_code',
        'nir_value',
        'polygon_geom',
        'name_on_sppt',
        'address_on_sppt',
        'kelurahan',
        'kota',
        'is_verified',
        'notes',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'nir_value' => 'decimal:2',
    ];

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }

    public function transactions()
    {
        return TransactionPbb::where('nop', $this->nop);
    }
}
