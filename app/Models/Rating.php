<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    protected $fillable = [
        'taxpayer_id',
        'category',
        'sub_item',
        'score',
        'issue',
        'message',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }
}
