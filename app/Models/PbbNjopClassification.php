<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PbbNjopClassification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'class_code',
        'min_value',
        'max_value',
        'njop_value',
    ];

    protected $casts = [
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'njop_value' => 'decimal:2',
    ];
}
