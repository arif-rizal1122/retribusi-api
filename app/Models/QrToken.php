<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrToken extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'token',
    ];

    public function model()
    {
        return $this->morphTo();
    }
}
