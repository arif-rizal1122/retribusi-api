<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BpnH2hMapping extends Model
{
    protected $fillable = [
        'nib',
        'nop',
        'znt_value',
        'zona_id',
        'last_sync_at',
    ];
}
