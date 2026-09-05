<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbbNopApplication extends Model
{
    protected $fillable = [
        'user_id',
        'nik',
        'nib',
        'znt_applied',
        'nir_applied',
        'polygon_coordinates',
        'is_bpn_verified',
        'name',
        'address',
        'latitude',
        'longitude',
        'land_area',
        'building_area',
        'ktp_file_path',
        'akte_file_path',
        'imb_file_path',
        'status',
        'survey_notes',
        'survey_photo_path',
        'nop',
        'registered_for',
        'owner_name',
        'owner_address',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'polygon_coordinates' => 'array',
        'is_bpn_verified' => 'boolean',
        'nir_applied' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
