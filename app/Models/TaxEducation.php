<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxEducation extends Model
{
    protected $table = 'tax_educations';

    protected $fillable = [
        'title',
        'category',
        'description',
        'material_url',
        'event_date',
        'location',
        'participant_count',
        'feedback_summary',
        'created_by',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
