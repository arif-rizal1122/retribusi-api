<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SpotCheckItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'details' => 'array',
        'estimated_value' => 'decimal:2',
    ];

    public function spotCheck()
    {
        return $this->belongsTo(SpotCheck::class);
    }
}
