<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BphtbDocument extends Model
{
    protected $fillable = [
        'bphtb_submission_id',
        'document_type',
        'file_path',
        'original_name',
        'uploaded_by',
        'notes',
    ];

    public function submission()
    {
        return $this->belongsTo(BphtbSubmission::class, 'bphtb_submission_id');
    }
}
