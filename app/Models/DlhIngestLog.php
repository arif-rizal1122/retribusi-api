<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DlhIngestLog extends Model
{
    use HasFactory;

    protected $table = 'dlh_ingest_logs';

    protected $fillable = [
        'source_file',
        'row_index',
        'raw_data',
        'status',
        'anomaly_reason',
        'tax_object_id',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'row_index' => 'integer',
        'raw_data' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function taxObject(): BelongsTo
    {
        return $this->belongsTo(TaxObject::class, 'tax_object_id');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
