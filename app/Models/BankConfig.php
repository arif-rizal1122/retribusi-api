<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * BankConfig
 *
 * Konfigurasi bank/payment gateway yang di-manage di luar branch dev
 * (tabel produksi `bank_configs`). Adapter gateway membaca konfigurasi aktif
 * dari sini (prefix VA, fee, sandbox) alih-alih nilai hardcoded.
 */
class BankConfig extends Model
{
    protected $table = 'bank_configs';

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
        'is_active' => 'boolean',
        'is_sandbox' => 'boolean',
        'fee_persen' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
