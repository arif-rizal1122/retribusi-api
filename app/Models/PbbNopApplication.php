<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbbNopApplication extends Model
{
    protected $fillable = [
        'user_id',
        'nik',
        'name',
        'address',
        'land_area',
        'building_area',
        'ktp_file_path',
        'akte_file_path',
        'imb_file_path',
        'status',
        'survey_notes',
        'survey_photo_path',
        'nop',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
