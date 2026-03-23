<?php

namespace App\Jobs;

use App\Models\Bill;
use App\Models\MonthlyReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AnomalyDetectionJob - Tapping Box Surveillance (BAB 4.3 Mpad/E-MITRA Standard)
 *
 * Detects revenue anomalies by comparing reported SPTPD data (self-assessment)
 * against system-calculated billing expectations. Flags discrepancies exceeding
 * the 5% threshold as "anomali_merah" for Pengawas review.
 *
 * Schedule: Daily at 02:00 AM via Laravel Console Kernel.
 *
 * @see audit_enforcement/SKILL.md - Anomaly Detection SOP
 * @see vtax_parity/SKILL.md - 9-Tax classification alignment
 */
class AnomalyDetectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Threshold for flagging anomalies (5% deviation).
     * Per BAB 4.3: ">5% selisih = RED FLAG"
     */
    const ANOMALY_THRESHOLD = 0.05;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[AnomalyDetection] Starting daily anomaly scan...');

        $anomalies = [];
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Get all tax objects that have both:
        // 1. Monthly Report (SPTPD - self-assessed revenue from taxpayer)
        // 2. System Bills (official billing / penetapan)
        $taxObjects = DB::table('tax_objects')
            ->join('taxpayers', 'tax_objects.taxpayer_id', '=', 'taxpayers.id')
            ->join('retribution_types', 'tax_objects.retribution_type_id', '=', 'retribution_types.id')
            ->where('tax_objects.status', 'approved')
            ->select(
                'tax_objects.id as tax_object_id',
                'tax_objects.name as object_name',
                'tax_objects.retribution_type_id',
                'taxpayers.id as taxpayer_id',
                'taxpayers.name as taxpayer_name',
                'taxpayers.npwpd',
                'retribution_types.name as type_name'
            )
            ->get();

        foreach ($taxObjects as $obj) {
            // Get reported revenue (SPTPD) for current period
            $reportedRevenue = DB::table('monthly_reports')
                ->where('taxpayer_id', $obj->taxpayer_id)
                ->whereMonth('period_date', $currentMonth)
                ->whereYear('period_date', $currentYear)
                ->sum('reported_revenue');

            // Get system-expected revenue (from billing)
            $expectedRevenue = DB::table('bills')
                ->where('tax_object_id', $obj->tax_object_id)
                ->whereMonth('period_start', $currentMonth)
                ->whereYear('period_start', $currentYear)
                ->sum('amount');

            // Skip if no data for comparison
            if ($expectedRevenue <= 0 && $reportedRevenue <= 0) {
                continue;
            }

            // Calculate deviation
            $baseline = max($expectedRevenue, $reportedRevenue);
            if ($baseline <= 0) continue;

            $deviation = abs($expectedRevenue - $reportedRevenue) / $baseline;

            if ($deviation > self::ANOMALY_THRESHOLD) {
                $anomalies[] = [
                    'tax_object_id'    => $obj->tax_object_id,
                    'taxpayer_id'      => $obj->taxpayer_id,
                    'taxpayer_name'    => $obj->taxpayer_name,
                    'npwpd'            => $obj->npwpd,
                    'object_name'      => $obj->object_name,
                    'type_name'        => $obj->type_name,
                    'reported_revenue' => $reportedRevenue,
                    'expected_revenue' => $expectedRevenue,
                    'deviation_pct'    => round($deviation * 100, 2),
                    'status'           => 'anomali_merah',
                    'detected_at'      => now()->toDateTimeString(),
                    'period'           => $currentYear . '-' . str_pad($currentMonth, 2, '0', STR_PAD_LEFT),
                ];

                Log::warning("[AnomalyDetection] RED FLAG: {$obj->taxpayer_name} ({$obj->npwpd}) - {$obj->object_name} | Deviation: " . round($deviation * 100, 2) . "%");
            }
        }

        // Store results in a dedicated anomalies table (or log)
        if (!empty($anomalies)) {
            // Upsert anomalies into the surveillance_anomalies table if it exists
            try {
                foreach ($anomalies as $anomaly) {
                    DB::table('surveillance_anomalies')->updateOrInsert(
                        [
                            'tax_object_id' => $anomaly['tax_object_id'],
                            'period'        => $anomaly['period'],
                        ],
                        $anomaly
                    );
                }
            } catch (\Throwable $e) {
                // If table doesn't exist yet, just log the anomalies
                Log::info('[AnomalyDetection] Anomalies table not available. Logging ' . count($anomalies) . ' anomalies to system log only.');
            }
        }

        Log::info('[AnomalyDetection] Scan complete. Found ' . count($anomalies) . ' anomalies (threshold: >' . (self::ANOMALY_THRESHOLD * 100) . '%).');
    }
}
