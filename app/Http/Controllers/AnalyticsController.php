<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\Opd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get revenue realization summary
     */
    public function getRealization(Request $request)
    {
        $year = $request->get('year', date('Y'));

        // 1. Calculate realization (Total Paid vs Total Billed)
        $totalBilled = Bill::whereYear('created_at', $year)->sum('amount');
        $totalPaid = Payment::whereYear('paid_at', $year)
            ->where('status', 'success')
            ->sum('amount');

        $realizationPercent = $totalBilled > 0 ? ($totalPaid / $totalBilled) * 100 : 0;

        // 2. Performance by OPD
        $opdPerformance = Opd::withCount(['bills as total_billed' => function($q) use ($year) {
                $q->whereYear('bills.created_at', $year)->select(DB::raw('SUM(bills.amount)'));
            }])
            ->withCount(['payments as total_paid' => function($q) use ($year) {
                $q->whereYear('payments.paid_at', $year)->where('payments.status', 'success')->select(DB::raw('SUM(payments.amount)'));
            }])
            ->get()
            ->map(function($opd) {
                $billed = (float) ($opd->total_billed_count ?? 0);
                $paid = (float) ($opd->total_paid_count ?? 0);
                return [
                    'name' => $opd->name,
                    'billed' => $billed,
                    'paid' => $paid,
                    'realization' => $billed > 0 ? round(($paid / $billed) * 100, 2) : 0,
                ];
            });

        // 3. Monthly realization trend
        $monthlyTrend = DB::table('payments')
            ->select(DB::raw('MONTH(paid_at) as month'), DB::raw('SUM(amount) as total'))
            ->whereYear('paid_at', $year)
            ->where('status', 'success')
            ->groupBy('month')
            ->get()
            ->pluck('total', 'month')
            ->all();

        // Fill missing months
        $trend = [];
        for ($i = 1; $i <= 12; $i++) {
            $trend[] = [
                'month' => date('M', mktime(0, 0, 0, $i, 1)),
                'total' => (float) ($monthlyTrend[$i] ?? 0),
            ];
        }

        // 4. Performance by Territory (Wilayah I & II)
        $territoryPerformance = DB::table('retribution_types')
            ->whereIn('name', ['Wilayah I', 'Wilayah II'])
            ->where('opd_id', 46) // BAPENDA
            ->get()
            ->map(function($type) use ($year) {
                $billed = Bill::where('retribution_type_id', $type->id)
                    ->whereYear('created_at', $year)
                    ->sum('amount');
                
                $paid = Payment::join('bills', 'payments.bill_id', '=', 'bills.id')
                    ->where('bills.retribution_type_id', $type->id)
                    ->whereYear('payments.paid_at', $year)
                    ->where('payments.status', 'success')
                    ->sum('payments.amount');

                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'billed' => (float) $billed,
                    'paid' => (float) $paid,
                    'realization' => $billed > 0 ? round(($paid / $billed) * 100, 2) : 0,
                ];
            });

        return response()->json([
            'summary' => [
                'total_billed' => (float) $totalBilled,
                'total_paid' => (float) $totalPaid,
                'realization_percent' => round($realizationPercent, 2),
            ],
            'opd_performance' => $opdPerformance,
            'territory_performance' => $territoryPerformance,
            'monthly_trend' => $trend,
        ]);
    }

    /**
     * Get data for Geographic Revenue Heatmap
     */
    public function getHeatmapData(Request $request)
    {
        $year = $request->get('year', date('Y'));

        // 1. Get all Tax Objects with coordinates
        $taxObjects = \App\Models\TaxObject::with(['taxpayer', 'retributionType', 'classification'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function($obj) use ($year) {
                // Calculate total revenue for this object in the specified year
                $totalRevenue = Payment::join('bills', 'payments.bill_id', '=', 'bills.id')
                    ->where('bills.tax_object_id', $obj->id)
                    ->whereYear('payments.paid_at', $year)
                    ->where('payments.status', 'success')
                    ->sum('payments.amount');

                // Check if there are any pending bills for this tax object
                $hasUnpaidBills = \App\Models\Bill::where('tax_object_id', $obj->id)
                    ->where('status', 'pending')
                    ->exists();

                return [
                    'id' => $obj->id,
                    'name' => $obj->taxpayer->name . ' - ' . $obj->name,
                    'latitude' => (float)$obj->latitude,
                    'longitude' => (float)$obj->longitude,
                    'icon' => $obj->retributionType->icon ?? null,
                    'total_revenue' => (float)$totalRevenue,
                    'status' => 'taxpayer',
                    'is_paid' => !$hasUnpaidBills,
                    'classification_name' => $obj->classification->name ?? 'N/A',
                    'taxpayer_photo' => $obj->taxpayer->metadata['foto_lokasi_open_kamera'] ?? null,
                ];
            });

        // 2. Include Zones as well
        $zones = \App\Models\Zone::with(['retributionType'])
            ->get()
            ->map(function($z) {
                return [
                    'id' => 'zone-' . $z->id,
                    'name' => $z->name . ' (Zona)',
                    'latitude' => (float)($z->latitude ?? ($z->coordinates[0][0] ?? 0)),
                    'longitude' => (float)($z->longitude ?? ($z->coordinates[0][1] ?? 0)),
                    'icon' => $z->retributionType->icon ?? null,
                    'is_zone' => true,
                    'total_revenue' => 0,
                    'status' => 'zone',
                    'is_paid' => true,
                    'geometry_type' => $z->geometry_type,
                    'coordinates' => $z->coordinates,
                ];
            });

        return response()->json([
            'data' => $taxObjects->concat($zones),
            'center' => [
                'lat' => -5.4633, // Baubau center
                'lng' => 122.6012
            ]
        ]);
    }
}
