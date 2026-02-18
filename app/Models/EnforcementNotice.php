<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnforcementNotice extends Model
{
    protected $fillable = [
        'tax_object_id',
        'type',
        'number',
        'status',
        'notes',
        'created_by',
        'approved_by',
        'due_date',
        'lat',
        'lng',
        'photo_path',
    ];

    public function taxObject(): BelongsTo
    {
        return $this->belongsTo(TaxObject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
