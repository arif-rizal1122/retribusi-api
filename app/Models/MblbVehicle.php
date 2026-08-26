<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MblbVehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'default_capacity',
        'is_active'
    ];

    protected $casts = [
        'default_capacity' => 'decimal:2',
        'is_active' => 'boolean'
    ];
}
