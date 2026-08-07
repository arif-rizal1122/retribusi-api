<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NibNopMapping extends Model
{
    protected $fillable = [
        'nib',
        'nop',
        'taxpayer_name',
        'land_area_m2',
        'building_area_m2',
        'znt',
        'match_status',
        'source',
        'last_synced_at',
    ];

    protected $casts = [
        'land_area_m2'    => 'decimal:2',
        'building_area_m2' => 'decimal:2',
        'last_synced_at'  => 'datetime',
    ];
}
