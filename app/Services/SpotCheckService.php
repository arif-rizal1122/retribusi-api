<?php

namespace App\Services;

use App\Models\SpotCheck;
use Illuminate\Support\Facades\DB;

class SpotCheckService
{
    /**
     * Hitung rata-rata estimasi nilai transaksi per hari berdasarkan data observasi uji petik
     * untuk menentukan besaran Penetapan Pajak Secara Jabatan.
     * Membedakan rata-rata observasi di hari-hari biasa dan akhir pekan (is_weekend flag).
     */
    public function calculateEstimatedMonthlyRevenue($taxObjectId)
    {
        // Rata-rata transaksi harian "Hari Biasa" (Senin-Jumat)
        $avgWeekday = DB::table('spot_check_items')
            ->join('spot_checks', 'spot_check_items.spot_check_id', '=', 'spot_checks.id')
            ->where('spot_checks.tax_object_id', $taxObjectId)
            ->where('spot_checks.status', 'approved')
            ->where('spot_checks.is_weekend', false)
            ->sum('spot_check_items.estimated_value');

        $countWeekdayChecks = SpotCheck::where('tax_object_id', $taxObjectId)
            ->where('status', 'approved')
            ->where('is_weekend', false)
            ->count();

        $dailyWeekdayAvg = $countWeekdayChecks > 0 ? $avgWeekday / $countWeekdayChecks : 0;

        // Rata-rata transaksi harian "Akhir Pekan" (Sabtu-Minggu)
        $avgWeekend = DB::table('spot_check_items')
            ->join('spot_checks', 'spot_check_items.spot_check_id', '=', 'spot_checks.id')
            ->where('spot_checks.tax_object_id', $taxObjectId)
            ->where('spot_checks.status', 'approved')
            ->where('spot_checks.is_weekend', true)
            ->sum('spot_check_items.estimated_value');

        $countWeekendChecks = SpotCheck::where('tax_object_id', $taxObjectId)
            ->where('status', 'approved')
            ->where('is_weekend', true)
            ->count();

        $dailyWeekendAvg = $countWeekendChecks > 0 ? $avgWeekend / $countWeekendChecks : 0;

        // Dynamic calendar split: Calculate actual weekdays and weekends in current month
        $now = now();
        $daysInMonth = $now->daysInMonth;
        $weekdays = 0;
        $weekends = 0;

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = $now->copy()->day($i);
            if ($date->isWeekend()) {
                $weekends++;
            } else {
                $weekdays++;
            }
        }

        $estimatedMonthly = ($dailyWeekdayAvg * $weekdays) + ($dailyWeekendAvg * $weekends);

        return [
            'month' => $now->format('F Y'),
            'calendar_days' => $daysInMonth,
            'weekdays_count' => $weekdays,
            'weekends_count' => $weekends,
            'daily_weekday_average' => $dailyWeekdayAvg,
            'daily_weekend_average' => $dailyWeekendAvg,
            'total_weekday_check_days' => $countWeekdayChecks,
            'total_weekend_check_days' => $countWeekendChecks,
            'estimated_monthly_revenue' => $estimatedMonthly,
        ];
    }
}
