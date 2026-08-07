<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxObjectChangeRequest extends Model
{
    use HasFactory;

    /**
     * Status alur change request.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'tax_object_id',
        'taxpayer_id',
        'field_changes',
        'documents',
        'status',
        'reviewer_id',
        'review_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'field_changes' => 'array',
        'documents' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Unit pajak yang dimohon perubahannya.
     */
    public function taxObject(): BelongsTo
    {
        return $this->belongsTo(TaxObject::class);
    }

    /**
     * Wajib pajak pengaju.
     */
    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }

    /**
     * Admin Bapenda yang melakukan review.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
