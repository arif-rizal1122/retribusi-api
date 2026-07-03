<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class BankConfig extends Model
{
    protected $fillable = [
        'kode_bank',
        'nama_bank',
        'nama_singkat',
        'tipe_driver',
        'api_endpoint',
        'auth_type',
        'client_id',
        'client_secret',
        'api_key',
        'api_secret',
        'hmac_secret',
        'allowed_ips',
        'kode_va_prefix',
        'fee_persen',
        'is_active',
        'is_sandbox',
        'metadata',
    ];

    protected $casts = [
        'allowed_ips' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_sandbox' => 'boolean',
        'fee_persen' => 'decimal:2',
    ];

    protected $hidden = [
        'client_id',
        'client_secret',
        'api_key',
        'api_secret',
        'hmac_secret',
    ];

    // --- Accessors: Decrypt otomatis ---

    public function getClientIdAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setClientIdAttribute($value)
    {
        $this->attributes['client_id'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getClientSecretAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setClientSecretAttribute($value)
    {
        $this->attributes['client_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getApiKeyAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setApiKeyAttribute($value)
    {
        $this->attributes['api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getApiSecretAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setApiSecretAttribute($value)
    {
        $this->attributes['api_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getHmacSecretAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setHmacSecretAttribute($value)
    {
        $this->attributes['hmac_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeProduction($query)
    {
        return $query->where('is_sandbox', false);
    }

    public function scopeByDriver($query, string $driverType)
    {
        return $query->where('tipe_driver', $driverType);
    }
}