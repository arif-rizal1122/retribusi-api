<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\Auditable;

class ParkingDeposit extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'payment_method',
        'reference',
        'balance_after',
        'remark',
    ];

    protected $casts = [
        'amount' => 'float',
        'balance_after' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Saldo deposit jukir (topup positif, potongan RKUD negatif) — Quadruple-Lock.
     */
    public static function balanceFor(int $userId): float
    {
        return (float) static::where('user_id', $userId)->sum('amount');
    }
}