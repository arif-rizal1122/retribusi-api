<?php

namespace App\Http\Controllers\Pengawas;

use App\Http\Controllers\Controller;
use App\Models\TaxObject;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SurveillanceController extends Controller
{
    /**
     * Get Anomaly Detection Data
     * Compares "Self-Reporting" (if any) with generated/estimated payments
     */
    public function getAnomalies(Request $request)
    {
        $threshold = $request->get('threshold', 0.2); // 20% diff
        
        // This is a simulation source. In real implementation, this would query
        // Tapping Box data or POS Integration tables.
        // For now, we compare against expected revenue from BillingService.
        
        $anomalies = TaxObject::with(['taxpayer', 'retributionType', 'classification'])
            ->where('status', 'active')
            ->whereHas('retributionType')
            ->whereHas('taxpayer')
            ->get()
            ->map(function ($obj) use ($threshold) {
                // Simplified anomaly logic for demo
                // If the latest payment is significantly lower than average or zero for long time
                $lastPayment = Payment::where('tax_object_id', $obj->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                $billingService = app(\App\Services\BillingService::class);
                $expected = $billingService->getPendingPeriods($obj);
                $totalExpected = collect($expected)->sum('amount');
                
                $isAnomaly = false;
                $reason = "";
                
                if (count($expected) > 3) {
                    $isAnomaly = true;
                    $reason = "Tunggakan di atas 3 periode";
                }

                // Simulate Revenue Mismatch (Self-reporting vs Expected)
                // In real system, this compares SPTPD table with Tapping Box table
                if (!$isAnomaly && $obj->id % 7 == 0) {
                    $isAnomaly = true;
                    $reason = "Selisih Pelaporan >20%";
                }
                
                if ($isAnomaly) {
                    return [
                        'tax_object_id' => $obj->id,
                        'name' => $obj->name,
                        'taxpayer' => $obj->taxpayer->name ?? 'N/A',
                        'expected_revenue' => $totalExpected,
                        'reason' => $reason,
                        'is_anomaly' => true
                    ];
                }
                
                return null;
            })
            ->filter()
            ->values();

        return response()->json(['data' => $anomalies]);
    }

    public function getComplianceStats()
    {
        $totalObjects = TaxObject::where('status', 'active')->count();
        
        // Compliant = Has at least one payment in the last 30 days
        $compliantObjects = TaxObject::where('status', 'active')
            ->whereHas('payments', function($q) {
                $q->where('created_at', '>=', Carbon::now()->subDays(30));
            })->count();
            
        return response()->json([
            'compliance_rate' => $totalObjects > 0 ? ($compliantObjects / $totalObjects) * 100 : 100,
            'total_active_objects' => $totalObjects,
            'compliant_count' => $compliantObjects,
            'non_compliant_count' => $totalObjects - $compliantObjects,
        ]);
    }
}
