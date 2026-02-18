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

        // 1. Aggregate revenue per tax object location
        $taxObjects = DB::table('tax_objects')
            ->join('taxpayers', 'tax_objects.taxpayer_id', '=', 'taxpayers.id')
            ->join('bills', 'tax_objects.id', '=', 'bills.tax_object_id')
            ->join('payments', 'bills.id', '=', 'payments.bill_id')
            ->join('retribution_types', 'tax_objects.retribution_type_id', '=', 'retribution_types.id')
            ->whereYear('payments.paid_at', $year)
            ->where('payments.status', 'success')
            ->select(
                'tax_objects.id',
                DB::raw("CONCAT(taxpayers.name, ' - ', tax_objects.name) as name"),
                'tax_objects.latitude',
                'tax_objects.longitude',
                'retribution_types.icon',
                DB::raw('SUM(payments.amount) as total_revenue')
            )
            ->whereNotNull('tax_objects.latitude')
            ->whereNotNull('tax_objects.longitude')
            ->groupBy('tax_objects.id', 'taxpayers.name', 'tax_objects.name', 'tax_objects.latitude', 'tax_objects.longitude', 'retribution_types.icon')
            ->get();

        // 2. Include Zones as well
        $zones = \App\Models\Zone::with(['retributionType'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function($z) {
                return [
                    'id' => 'zone-' . $z->id,
                    'name' => $z->name . ' (Zona)',
                    'latitude' => (float)$z->latitude,
                    'longitude' => (float)$z->longitude,
                    'icon' => $z->retributionType->icon ?? null,
                    'is_zone' => true,
                    'total_revenue' => 0
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
