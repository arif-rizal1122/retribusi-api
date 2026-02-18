<?php
 
namespace App\Models;
 
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
 
use App\Traits\Auditable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, Auditable;
 
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'nik',
        'role',
        'phone',
        'address',
        'opd_id',
        'status',
    ];
 
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
 
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the OPD that this user belongs to
     */
    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    /**
     * Role Constants
     */
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_OPD = 'opd';
    const ROLE_PENGAWAS = 'pengawas';
    const ROLE_KABID_PENGAWAS = 'kabid_pengawas';
    const ROLE_KASUBID_PENGAWAS = 'kasubid_pengawas';
    const ROLE_PETUGAS = 'petugas';

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Check if user is OPD admin
     */
    public function isOpd(): bool
    {
        return $this->role === self::ROLE_OPD;
    }

    /**
     * Check if user is Pengawas (General or specific)
     */
    public function isPengawas(): bool
    {
        return in_array($this->role, [
            self::ROLE_PENGAWAS, 
            self::ROLE_KABID_PENGAWAS, 
            self::ROLE_KASUBID_PENGAWAS
        ]);
    }

    /**
     * Check if user is Kabid Pengawas
     */
    public function isKabid(): bool
    {
        return $this->role === self::ROLE_KABID_PENGAWAS;
    }

    /**
     * Check if user is Kasubid Pengawas
     */
    public function isKasubid(): bool
    {
        return $this->role === self::ROLE_KASUBID_PENGAWAS;
    }

    /**
     * Get the retribution assignments for this user
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(UserRetributionAssignment::class);
    }

    public function confirmedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'approved_by');
    }

    /**
     * Taxpayers created by this user
     */
    public function createdTaxpayers(): HasMany
    {
        return $this->hasMany(Taxpayer::class, 'created_by');
    }
}
