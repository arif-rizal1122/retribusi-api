<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\Opd;
use App\Support\SqlDate;
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
        $user = auth()->user();
        $retributionTypeId = $user->retribution_type_id;

        // 1. Calculate realization (Total Paid vs Total Billed)
        $totalBilled = Bill::whereYear('created_at', $year)
            ->when($retributionTypeId, fn($q) => $q->where('retribution_type_id', $retributionTypeId))
            ->sum('amount');
            
        $totalPaid = Payment::whereYear('paid_at', $year)
            ->where('payments.status', 'success')
            ->when($retributionTypeId, function($q) use ($retributionTypeId) {
                $q->whereExists(function($sub) use ($retributionTypeId) {
                    $sub->select(DB::raw(1))
                        ->from('bills')
                        ->whereColumn('bills.id', 'payments.bill_id')
                        ->where('bills.retribution_type_id', $retributionTypeId);
                });
            })
            ->sum('amount');

        $realizationPercent = $totalBilled > 0 ? ($totalPaid / $totalBilled) * 100 : 0;

        // 2. Performance by OPD
        $opdPerformance = Opd::withCount(['bills as total_billed' => function($q) use ($year, $retributionTypeId) {
                $q->whereYear('bills.created_at', $year)
                    ->when($retributionTypeId, fn($sq) => $sq->where('retribution_type_id', $retributionTypeId))
                    ->select(DB::raw('SUM(bills.amount)'));
            }])
            ->withCount(['payments as total_paid' => function($q) use ($year, $retributionTypeId) {
                $q->whereYear('payments.paid_at', $year)
                    ->where('payments.status', 'success')
                    ->when($retributionTypeId, function($sq) use ($retributionTypeId) {
                        $sq->whereExists(function($sub) use ($retributionTypeId) {
                            $sub->select(DB::raw(1))
                                ->from('bills')
                                ->whereColumn('bills.id', 'payments.bill_id')
                                ->where('bills.retribution_type_id', $retributionTypeId);
                        });
                    })
                    ->select(DB::raw('SUM(payments.amount)'));
            }])
            ->get()
            ->map(function($opd) {
                $billed = (float) ($opd->total_billed ?? 0);
                $paid = (float) ($opd->total_paid ?? 0);
                return [
                    'name' => $opd->name,
                    'billed' => $billed,
                    'paid' => $paid,
                    'realization' => $billed > 0 ? round(($paid / $billed) * 100, 2) : 0,
                ];
            });

        // 3. Monthly realization trend
        $monthSql = SqlDate::month('paid_at');
        $monthlyTrend = Payment::selectRaw("$monthSql as month, SUM(amount) as total")
            ->whereYear('paid_at', $year)
            ->where('payments.status', 'success')
            ->when($retributionTypeId, function($q) use ($retributionTypeId) {
                $q->whereExists(function($sub) use ($retributionTypeId) {
                    $sub->select(DB::raw(1))
                        ->from('bills')
                        ->whereColumn('bills.id', 'payments.bill_id')
                        ->where('bills.retribution_type_id', $retributionTypeId);
                });
            })
            ->groupByRaw($monthSql)
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

        $bapendaId = Opd::where('code', 'BAPENDA')->first()->id ?? 46;
        $territoryPerformance = DB::table('retribution_types')
            ->whereIn('name', ['Wilayah I', 'Wilayah II'])
            ->where('opd_id', $bapendaId)
            ->when($retributionTypeId, fn($q) => $q->where('id', $retributionTypeId))
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
        $user = auth()->user();
        $retributionTypeId = $user->retribution_type_id;

        // 1. Get all Tax Objects with coordinates
        $taxObjects = \App\Models\TaxObject::with(['taxpayer', 'retributionType', 'classification'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('status', 'active')
            ->when($retributionTypeId, fn($q) => $q->where('retribution_type_id', $retributionTypeId))
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
                    'name' => ($obj->taxpayer->name ?? 'WP N/A') . ' - ' . $obj->name,
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
            ->when($retributionTypeId, fn($q) => $q->where('retribution_type_id', $retributionTypeId))
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

    /**
     * Get detailed financial performance/achievement per tax object
     */
    public function getObjectPerformance(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $search = $request->get('q');
        $retributionTypeId = $request->get('retribution_type_id');
        $opdId = $request->get('opd_id');

        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            $opdId = $user->opd_id;
        }

        // Subquery for total billed per object
        $billedSub = DB::table('bills')
            ->select('tax_object_id', DB::raw('SUM(amount) as total_target'))
            ->whereYear('created_at', $year)
            ->groupBy('tax_object_id');

        // Subquery for total paid per object
        $paidSub = DB::table('payments')
            ->select('tax_object_id', DB::raw('SUM(amount) as total_realization'))
            ->where('status', 'success')
            ->whereYear('paid_at', $year)
            ->groupBy('tax_object_id');

        $query = DB::table('tax_objects as to')
            ->join('taxpayers as tp', 'to.taxpayer_id', '=', 'tp.id')
            ->leftJoin('retribution_classifications as rc', 'to.retribution_classification_id', '=', 'rc.id')
            ->leftJoin('retribution_types as rt', 'to.retribution_type_id', '=', 'rt.id')
            ->leftJoinSub($billedSub, 'b', 'to.id', '=', 'b.tax_object_id')
            ->leftJoinSub($paidSub, 'p', 'to.id', '=', 'p.tax_object_id')
            ->select(
                'to.id',
                'to.name',
                'tp.name as taxpayer_name',
                'rt.name as type_name',
                'rt.icon as type_icon',
                'to.last_photo_url',
                'rc.name as classification_name',
                DB::raw('COALESCE(b.total_target, 0) as target'),
                DB::raw('COALESCE(p.total_realization, 0) as realization'),
                DB::raw('CASE WHEN COALESCE(b.total_target, 0) > 0 
                             THEN ROUND((COALESCE(p.total_realization, 0) / b.total_target) * 100, 2) 
                             ELSE 0 END as percentage')
            )
            ->when($opdId, fn($q) => $q->where('to.opd_id', $opdId))
            ->when($retributionTypeId, fn($q) => $q->where('to.retribution_type_id', $retributionTypeId))
            ->when($search, function($q) use ($search) {
                $q->where(function($sq) use ($search) {
                    $sq->where('to.name', 'like', "%{$search}%")
                       ->orWhere('tp.name', 'like', "%{$search}%");
                });
            });

        // Show all objects including those with zero achievements
        // as requested by user to maintain transparency.

        $performance = $query->orderBy('percentage', 'desc')
            ->paginate($request->get('limit', 15));

        return response()->json($performance);
    }

    public function getClassificationPerformance(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $search = $request->get('q');

        // Subquery for total billed per classification
        $billedSub = DB::table('bills as b')
            ->join('tax_objects as to', 'b.tax_object_id', '=', 'to.id')
            ->select('to.retribution_classification_id', DB::raw('SUM(b.amount) as total_target'))
            ->whereYear('b.created_at', $year)
            ->groupBy('to.retribution_classification_id');

        // Subquery for total paid per classification
        $paidSub = DB::table('payments as p')
            ->join('tax_objects as to', 'p.tax_object_id', '=', 'to.id')
            ->select('to.retribution_classification_id', DB::raw('SUM(p.amount) as total_realization'))
            ->where('p.status', 'success')
            ->whereYear('p.paid_at', $year)
            ->groupBy('to.retribution_classification_id');

        $query = DB::table('retribution_classifications as rc')
            ->leftJoinSub($billedSub, 'b', 'rc.id', '=', 'b.retribution_classification_id')
            ->leftJoinSub($paidSub, 'p', 'rc.id', '=', 'p.retribution_classification_id')
            ->select(
                'rc.id',
                'rc.name',
                'rc.code',
                DB::raw('COALESCE(b.total_target, 0) as target'),
                DB::raw('COALESCE(p.total_realization, 0) as realization'),
                DB::raw('CASE WHEN COALESCE(b.total_target, 0) > 0 
                             THEN ROUND((COALESCE(p.total_realization, 0) / b.total_target) * 100, 2) 
                             ELSE 0 END as percentage')
            )
            ->when($search, fn($q) => $q->where('rc.name', 'like', "%{$search}%"));

        $performance = $query->orderBy('percentage', 'desc')->get();

        return response()->json(['data' => $performance]);
    }

    /**
     * Get LRA (Laporan Realisasi Anggaran) data
     * Used by Kepala Bapenda for real-time revenue tracking before EOD bank reconciliation
     */
    public function getLraReport(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month'); // Optional: filter by specific month
        $user = auth()->user();
        $retributionTypeId = $user->retribution_type_id;

        $monthSql = SqlDate::month('payments.paid_at');
        $yearSql = SqlDate::year('payments.paid_at');

        // Main LRA query: Revenue grouped by retribution type and month
        $query = Payment::join('bills', 'payments.bill_id', '=', 'bills.id')
            ->join('retribution_types as rt', 'bills.retribution_type_id', '=', 'rt.id')
            ->where('payments.status', 'success')
            ->whereRaw("$yearSql = ?", [$year])
            ->when($retributionTypeId, fn($q) => $q->where('bills.retribution_type_id', $retributionTypeId))
            ->when($month, fn($q) => $q->whereRaw("$monthSql = ?", [$month]));

        $lraData = $query->select(
                'rt.id as retribution_type_id',
                'rt.name as retribution_type_name',
                DB::raw("$monthSql as bulan"),
                DB::raw('COUNT(DISTINCT payments.id) as jumlah_transaksi'),
                DB::raw('SUM(payments.amount) as realisasi')
            )
            ->groupBy('rt.id', 'rt.name', DB::raw($monthSql))
            ->orderBy('rt.name')
            ->orderBy(DB::raw($monthSql))
            ->get();

        // Target (billed) per retribution type for the year
        $targets = Bill::join('retribution_types as rt', 'bills.retribution_type_id', '=', 'rt.id')
            ->whereYear('bills.created_at', $year)
            ->when($retributionTypeId, fn($q) => $q->where('bills.retribution_type_id', $retributionTypeId))
            ->select(
                'rt.id as retribution_type_id',
                'rt.name as retribution_type_name',
                DB::raw('SUM(bills.amount) as target_anggaran')
            )
            ->groupBy('rt.id', 'rt.name')
            ->get()
            ->keyBy('retribution_type_id');

        // Grand totals
        $grandRealisasi = $lraData->sum('realisasi');
        $grandTarget = $targets->sum('target_anggaran');
        $grandTransaksi = $lraData->sum('jumlah_transaksi');

        // Group LRA by type for summary view
        $summaryByType = $lraData->groupBy('retribution_type_id')->map(function ($items) use ($targets) {
            $typeId = $items->first()->retribution_type_id;
            $typeName = $items->first()->retribution_type_name;
            $totalRealisasi = $items->sum('realisasi');
            $targetAnggaran = (float) ($targets[$typeId]->target_anggaran ?? 0);

            return [
                'retribution_type_id' => $typeId,
                'retribution_type_name' => $typeName,
                'target_anggaran' => $targetAnggaran,
                'realisasi' => $totalRealisasi,
                'sisa' => $targetAnggaran - $totalRealisasi,
                'persentase' => $targetAnggaran > 0 ? round(($totalRealisasi / $targetAnggaran) * 100, 2) : 0,
                'jumlah_transaksi' => $items->sum('jumlah_transaksi'),
                'monthly_breakdown' => $items->map(fn($i) => [
                    'bulan' => (int) $i->bulan,
                    'realisasi' => (float) $i->realisasi,
                    'jumlah_transaksi' => (int) $i->jumlah_transaksi,
                ])->values(),
            ];
        })->values();

        return response()->json([
            'tahun' => (int) $year,
            'bulan_filter' => $month ? (int) $month : null,
            'grand_total' => [
                'target_anggaran' => (float) $grandTarget,
                'realisasi' => (float) $grandRealisasi,
                'sisa' => (float) ($grandTarget - $grandRealisasi),
                'persentase' => $grandTarget > 0 ? round(($grandRealisasi / $grandTarget) * 100, 2) : 0,
                'jumlah_transaksi' => (int) $grandTransaksi,
            ],
            'data' => $summaryByType,
        ]);
    }
}
