<?php

namespace App\Services;

use App\Models\AssetRental;
use App\Models\AssetRentalInspection;
use App\Models\Bill;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Penagihan overtime sewa alat berat PUPR.
 *
 * Dasar regulasi:
 * - SOP UPTD Workshop PUPR & skema "Sewa Alat Berat" (retribusi-admin/docs/05_modul_aplikasi/aset/sewa_alat_berat.md):
 *   billing aktual dihitung dari hour meter pra/pasca operasi dan diberlakukan
 *   "capping harian otomatis" bila jam operasional melebihi booking.
 * - Standar jam operasional alat berat = 8 jam/hari (default capping).
 * - Selisih melebihi jam booking diterbitkan otomatis sebagai SKRD Denda (bill).
 */
class PuprOvertimeService
{
    public const DAILY_OPERATIONAL_HOURS = 8;

    public const DENDA_CLASSIFICATION_CODE = 'DENDA-OVERTIME-SEWA-ALAT';

    /**
     * Evaluasi overtime berdasarkan hour meter pra vs pasca operasi.
     *
     * @return array{is_overtime: bool, actual_hours: float, booked_hours: float, overtime_hours: float, overtime_rate: float, overtime_amount: float}
     */
    public function evaluate(AssetRental $rental, float $postHourMeter): array
    {
        $pre = $rental->inspections()
            ->where('inspection_type', 'pre_operation')
            ->orderByDesc('inspected_at')
            ->first();

        $actualHours = $pre ? max(0, $postHourMeter - (float) $pre->hour_meter) : 0;
        $bookedHours = $this->bookedHours($rental);
        $overtimeHours = max(0, $actualHours - $bookedHours);
        $rate = $this->hourlyRate($rental);
        $amount = $overtimeHours * $rate;

        return [
            'is_overtime' => $overtimeHours > 0,
            'actual_hours' => round($actualHours, 2),
            'booked_hours' => round($bookedHours, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'overtime_rate' => round($rate, 2),
            'overtime_amount' => round($amount, 2),
        ];
    }

    /**
     * Jam ter-book sesuai lama sewa dengan capping harian otomatis (8 jam/hari).
     */
    public function bookedHours(AssetRental $rental): float
    {
        $lama = (float) ($rental->lama_sewa ?: 1);

        if (str_starts_with(strtolower((string) $rental->satuan_sewa), 'per jam')) {
            return $lama;
        }

        return $lama * self::DAILY_OPERATIONAL_HOURS;
    }

    /**
     * Tarif per jam. Sewa harian dikonversi ke jam dengan pembagi capping harian (8 jam).
     */
    public function hourlyRate(AssetRental $rental): float
    {
        $tarif = (float) $rental->tarif_per_satuan;

        if (str_starts_with(strtolower((string) $rental->satuan_sewa), 'per jam')) {
            return $tarif;
        }

        return self::DAILY_OPERATIONAL_HOURS > 0 ? $tarif / self::DAILY_OPERATIONAL_HOURS : 0;
    }

    /**
     * Terbitkan SKRD Denda overtime (idempotent - satu tagihan pending per sewa).
     */
    public function createDendaBill(
        AssetRental $rental,
        array $overtime,
        AssetRentalInspection $inspection,
        int $issuerUserId
    ): Bill {
        $existing = $this->findPendingDendaBill($rental);

        if ($existing) {
            return $existing;
        }

        $type = $rental->retributionType
            ?: RetributionType::withoutGlobalScopes()->where('opd_id', $rental->opd_id)->first();
        $classification = $rental->classification
            ?: RetributionClassification::withoutGlobalScopes()
                ->where('code', self::DENDA_CLASSIFICATION_CODE)
                ->first();

        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        $metadata = [
            'source' => 'pupr_overtime',
            'asset_rental_id' => $rental->id,
            'asset_rental_code' => $rental->rental_code,
            'inspection_id' => $inspection->id,
            'overtime_hours' => (float) $overtime['overtime_hours'],
            'overtime_rate' => (float) $overtime['overtime_rate'],
            'period_label' => 'Denda Overtime Sewa Alat Berat ' . $rental->rental_code,
        ];

        return Bill::create([
            'user_id' => $issuerUserId,
            'taxpayer_id' => $rental->taxpayer_id,
            'tax_object_id' => null,
            'opd_id' => $rental->opd_id,
            'retribution_type_id' => $type?->id,
            'retribution_classification_id' => $classification?->id,
            'bill_number' => 'OVT-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
            'amount' => (float) $overtime['overtime_amount'],
            'status' => 'pending',
            'period' => Carbon::now()->format('Y-m'),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'metadata' => $metadata,
            'due_date' => Carbon::now()->addDays(7),
        ]);
    }

    public function findPendingDendaBill(AssetRental $rental): ?Bill
    {
        return Bill::withoutGlobalScopes()
            ->where('metadata->source', 'pupr_overtime')
            ->where('metadata->asset_rental_id', $rental->id)
            ->whereIn('status', ['pending', 'overdue', 'unpaid'])
            ->latest()
            ->first();
    }
}