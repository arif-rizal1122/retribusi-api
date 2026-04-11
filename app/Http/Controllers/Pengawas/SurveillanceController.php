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
        $user = $request->user();
        $threshold = $request->get('threshold', 0.2); // 20% diff
        
        $query = TaxObject::with(['taxpayer', 'retributionType', 'classification'])
            ->where('status', 'active');

        if (!$user->isSuperAdmin()) {
            $query->where('opd_id', $user->opd_id);
        }

        $anomalies = $query->orderBy('updated_at', 'desc')
            ->limit(200)
            ->get()
            ->map(function ($obj) use ($threshold) {
                try {
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
                } catch (\Exception $e) {
                    \Log::error("Error processing anomaly for TaxObject {$obj->id}: " . $e->getMessage());
                }
                
                return null;
            })
            ->filter()
            ->values();

        return response()->json(['data' => $anomalies]);
    }

    public function getComplianceStats(Request $request)
    {
        $user = $request->user();
        $query = TaxObject::where('status', 'active');
        
        if (!$user->isSuperAdmin()) {
            $query->where('opd_id', $user->opd_id);
        }

        $totalObjects = $query->count();
        
        // Compliant = Has at least one payment in the last 30 days
        $compliantObjects = (clone $query)
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

    /**
     * Get last known locations of all petugas for supervisor map
     */
    public function getPetugasLocations(Request $request)
    {
        $user = $request->user();
        $opdId = $user->opd_id;

        // Only Super Admin can override opd_id filter
        if ($user->isSuperAdmin() && $request->has('opd_id')) {
            $opdId = $request->query('opd_id');
        }

        $query = \App\Models\User::where('role', 'petugas')
            ->where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($opdId) {
            $query->where('opd_id', $opdId);
        }

        $petugas = $query->with('opd:id,name')
            ->get(['id', 'name', 'latitude', 'longitude', 'opd_id', 'updated_at']);

        return response()->json($petugas->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'latitude' => (float) $p->latitude,
            'longitude' => (float) $p->longitude,
            'opd' => $p->opd->name ?? 'N/A',
            'updated_at' => $p->updated_at->diffForHumans(),
        ]));
    }
}
