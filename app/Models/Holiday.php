<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $table = 'holidays';

    protected $fillable = ['holiday_date', 'name', 'is_joint_leave'];

    protected $casts = [
        'holiday_date'   => 'date',
        'is_joint_leave' => 'boolean',
    ];
}
