<?php

namespace App\Models;

use App\Services\BusinessDayCalculator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceApplication extends Model
{
    use SoftDeletes;

    protected $table = 'service_applications';

    protected $fillable = [
        'application_no', 'user_id', 'service_code', 'service_name', 'payload', 'status',
        'contact_name', 'contact_phone',
        'assigned_officer_id', 'assigned_at', 'field_checked_at', 'field_notes',
        'sla_deadline', 'is_sla_breached', 'sla_breached_at', 'completed_at',
        'permit_no', 'billing_id', 'rejection_reason',
    ];

    /**
     * ⚠️ PENTING — app.timezone = UTC, tapi kolom waktu tabel ini bermakna WITA.
     *
     * Nilai disimpan sebagai string WITA polos (mis. '2026-09-28 11:00:00' artinya
     * 11:00 WITA). Kalau dibaca sebagai UTC, SLA bergeser 8 jam.
     *
     * Karena itu semua kolom waktu ditandai $timezoneWita dan dibaca lewat
     * accessor yang menempelkan zona Asia/Makassar.
     */
    protected $casts = [
        'payload'         => 'array',
        'is_sla_breached' => 'boolean',
    ];

    /** Kolom waktu yang bermakna WITA. */
    public const WITA_COLUMNS = [
        'assigned_at', 'field_checked_at', 'sla_deadline', 'sla_breached_at', 'completed_at',
    ];

    /**
     * Baca kolom waktu sebagai Carbon ber-zona WITA.
     * Dipakai otomatis lewat accessor di bawah.
     */
    protected function readWita(string $column): ?Carbon
    {
        $raw = $this->getRawOriginal($column);
        if (blank($raw)) {
            return null;
        }
        // Buang timezone bila ada, perlakukan sebagai waktu lokal WITA
        $str = preg_replace('/[+-]\d{2}:?\d{2}$|Z$/', '', (string) $raw);
        return Carbon::parse(trim($str), BusinessDayCalculator::TZ);
    }

    public function getSlaDeadlineAttribute(): ?Carbon
    {
        return $this->readWita('sla_deadline');
    }

    public function getAssignedAtAttribute(): ?Carbon
    {
        return $this->readWita('assigned_at');
    }

    public function getFieldCheckedAtAttribute(): ?Carbon
    {
        return $this->readWita('field_checked_at');
    }

    public function getSlaBreachedAtAttribute(): ?Carbon
    {
        return $this->readWita('sla_breached_at');
    }

    public function getCompletedAtAttribute(): ?Carbon
    {
        return $this->readWita('completed_at');
    }

    /**
     * Tulis kolom waktu sebagai string WITA polos (cegah konversi ke UTC).
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return Carbon::instance($date)
            ->setTimezone(BusinessDayCalculator::TZ)
            ->format('Y-m-d H:i:s');
    }

    /**
     * Setter: pastikan nilai WITA tersimpan apa adanya.
     */
    public function setSlaDeadlineAttribute($value): void
    {
        $this->attributes['sla_deadline'] = $this->toWitaString($value);
    }

    public function setAssignedAtAttribute($value): void
    {
        $this->attributes['assigned_at'] = $this->toWitaString($value);
    }

    public function setFieldCheckedAtAttribute($value): void
    {
        $this->attributes['field_checked_at'] = $this->toWitaString($value);
    }

    public function setSlaBreachedAtAttribute($value): void
    {
        $this->attributes['sla_breached_at'] = $this->toWitaString($value);
    }

    public function setCompletedAtAttribute($value): void
    {
        $this->attributes['completed_at'] = $this->toWitaString($value);
    }

    protected function toWitaString($value): ?string
    {
        if (blank($value)) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)
                ->setTimezone(BusinessDayCalculator::TZ)
                ->format('Y-m-d H:i:s');
        }
        // String: perlakukan sebagai WITA, buang timezone bila menyertakannya
        $str = preg_replace('/[+-]\d{2}:?\d{2}$|Z$/', '', (string) $value);
        return Carbon::parse(trim($str), BusinessDayCalculator::TZ)->format('Y-m-d H:i:s');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ServiceApplicationLog::class, 'application_id')->orderBy('created_at');
    }

    public function officer()
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    public function applicant()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function statusLabels(): array
    {
        return [
            'submitted'       => 'Diajukan',
            'ptsp_review'     => 'Verifikasi PTSP',
            'field_assigned'  => 'Ditugaskan ke Petugas',
            'field_checked'   => 'Cek Lapangan Selesai',
            'bapenda_review'  => 'Verifikasi Bapenda',
            'billing_issued'  => 'SKPD Terbit',
            'paid'            => 'Dibayar',
            'permit_issued'   => 'Izin Terbit',
            'revision_needed' => 'Perlu Perbaikan',
            'rejected'        => 'Ditolak',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }
}
