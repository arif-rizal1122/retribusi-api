<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegionalArea extends Model
{
    protected $table = 'regional_areas';

    public $timestamps = true;

    protected $fillable = [
        'code',
        'name',
        'level',
        'parent_code',
    ];
}
