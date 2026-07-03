<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Taxpayer extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected static function booted()
    {
        static::addGlobalScope(new \App\Models\Scopes\RetributionTypeScope);
    }

    protected $fillable = [
        'opd_id',
        'nik',
        'name',
        'address',
        'district',
        'sub_district',
        'phone',
        'npwpd',
        'object_name',
        'object_address',
        'latitude',
        'longitude',
        'is_active',
        'metadata',
        'created_by',
        'password'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the OPD that owns this taxpayer
     */
    public function opd()
    {
        return $this->belongsTo(Opd::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all retribution types for this taxpayer (many-to-many)
     */
    public function retributionTypes(): BelongsToMany
    {
        return $this->belongsToMany(RetributionType::class, 'taxpayer_retribution_type')
            ->withPivot(['retribution_classification_id', 'custom_amount', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get all retribution classifications for this taxpayer (many-to-many)
     */
    public function retributionClassifications(): BelongsToMany
    {
        return $this->belongsToMany(RetributionClassification::class, 'taxpayer_retribution_type')
            ->withPivot(['retribution_type_id', 'custom_amount', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get all tax objects owned by this taxpayer
     */
    public function taxObjects(): HasMany
    {
        return $this->hasMany(TaxObject::class);
    }

    /**
     * Get all PBB NOPs owned by this taxpayer
     */
    public function nops(): HasMany
    {
        return $this->hasMany(TaxpayerNop::class);
    }

    /**
     * Scope to get only active taxpayers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Citizen is never a super admin
     */
    public function isSuperAdmin()
    {
        return false;
    }

    /**
     * Resolve NPWPD for a taxpayer:
     * 1. If a taxpayer with the same NIK already exists, return their NPWPD.
     * 2. If a manual NPWPD is provided and unique, use it.
     * 3. Otherwise, auto-generate a unique NPWPD.
     *
     * @param string|null $nik
     * @param string|null $manualNpwpd
     * @return string
     */
    public static function resolveNpwpd(?string $nik, ?string $manualNpwpd = null): string
    {
        // 1. Detect existing NPWPD by NIK
        if ($nik) {
            $existing = static::withoutGlobalScopes()
                ->where('nik', $nik)
                ->whereNotNull('npwpd')
                ->where('npwpd', '!=', '')
                ->first();

            if ($existing) {
                return $existing->npwpd;
            }
        }

        // 2. Use manual NPWPD if provided and unique
        if ($manualNpwpd) {
            $isUnique = !static::withoutGlobalScopes()
                ->where('npwpd', $manualNpwpd)
                ->exists();

            if ($isUnique) {
                return $manualNpwpd;
            }
        }

        // 3. Auto-generate NPWPD with format: P.YYYY.XXXXX
        return static::generateNpwpd();
    }

    /**
     * Generate a unique NPWPD with format P.YYYY.XXXXX
     *
     * @return string
     */
    public static function generateNpwpd(): string
    {
        $year = date('Y');
        $prefix = "P.{$year}.";

        $lastSequence = static::withoutGlobalScopes()
            ->where('npwpd', 'like', $prefix . '%')
            ->pluck('npwpd')
            ->map(function ($npwpd) use ($prefix) {
                $sequence = substr((string) $npwpd, strlen($prefix));
                return ctype_digit($sequence) ? (int) $sequence : 0;
            })
            ->max() ?? 0;

        $nextSeq = $lastSequence + 1;

        return $prefix . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);
    }
}
