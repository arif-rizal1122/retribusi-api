<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxpayerNop extends Model
{
    use HasFactory;

    protected $fillable = [
        'taxpayer_id',
        'nop',
        'name',
        'address',
    ];

    /**
     * Get the taxpayer that owns the NOP.
     */
    public function taxpayer()
    {
        return $this->belongsTo(Taxpayer::class);
    }
}
