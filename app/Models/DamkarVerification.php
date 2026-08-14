<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\Auditable;

/**
 * M43 — Verifikasi teknis proteksi kebakaran oleh Dinas Damkar (opd_id=13).
 *
 * Damkar adalah VERIFIKATOR TEKNIS syarat PBG (UU 28/2002, PP 16/2021),
 * BUKAN pemungut retribusi. Pemungut tetap Bapenda.
 */
class DamkarVerification extends Model
{
    use HasFactory, Auditable;

    protected $table = 'tb_verifikasi_damkar';

    protected $fillable = [
        'tax_object_id',
        'building_name',
        'owner_name',
        'address',
        'status', // pending | approved | rejected
        'notes',
        'rekomendasi_teknis',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Relasi ke objek pajak (jika terhubung).
     */
    public function taxObject(): BelongsTo
    {
        return $this->belongsTo(TaxObject::class, 'tax_object_id');
    }

    /**
     * User Damkar yang melakukan verifikasi.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope: hanya yang menunggu verifikasi.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
