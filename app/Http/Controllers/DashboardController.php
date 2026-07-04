<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\Taxpayer;
use App\Models\Opd;
use App\Models\TaxObject;
use App\Support\SqlDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get KPI statistics for the dashboard with date filtering
     */
    public function getStats(Request $request)
    {
        $user = $request->user();
        if (in_array($user->role, ['citizen', 'wajib_pajak'])) {
            return response()->json(['message' => 'Unauthorized Access'], 403);
        }
        // Allow Super Admin to filter by opd_id query parameter
        // Non-SuperAdmin users are restricted to their own opd_id
        $opdId = $request->query('opd_id');
        if (!$user->isSuperAdmin()) {
            $opdId = $user->opd_id;
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Defaults to current month if no dates provided
        if (!$startDate || !$endDate) {
            $startDate = Carbon::now()->startOfMonth()->toDateString();
            $endDate = Carbon::now()->endOfMonth()->toDateString();
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        // Previous period for trend calculation
        $diff = $start->diffInDays($end) + 1;
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($diff - 1);

        // Revenue query
        $totalRevenue = $this->getRevenue($opdId, $start, $end);
        $prevRevenue = $this->getRevenue($opdId, $prevStart, $prevEnd);
        $revenueTrend = $this->calculateTrend($totalRevenue, $prevRevenue);

        // Pending Bills
        $pendingBillsCount = $this->getPendingCount($opdId, $start, $end);
        $prevPendingCount = $this->getPendingCount($opdId, $prevStart, $prevEnd);
        $pendingTrend = $this->calculateTrend($pendingBillsCount, $prevPendingCount, true); // Lower is better

        // Collection Rate
        $collectionRate = $this->getCollectionRate($opdId, $start, $end);
        $prevCollectionRate = $this->getCollectionRate($opdId, $prevStart, $prevEnd);
        $rateTrend = $this->calculateTrend($collectionRate, $prevCollectionRate);

        // Active taxpayers for operations are taxpayers with at least one approved object.
        $activeTaxpayersCount = Taxpayer::when($opdId, fn($q) => $q->where('opd_id', $opdId))
            ->where('is_active', true)
            ->whereHas('taxObjects', fn($q) => $q->where('status', 'active'))
            ->count();
        
        $prevTaxpayersCount = Taxpayer::when($opdId, fn($q) => $q->where('opd_id', $opdId))
            ->where('is_active', true)
            ->whereHas('taxObjects', fn($q) => $q->where('status', 'active'))
            ->where('created_at', '<', $start)
            ->count();
        $taxpayerTrend = $this->calculateTrend($activeTaxpayersCount, $prevTaxpayersCount);
 
         // Petugas achievement (if logged in user is petugas)
         $petugasAchievement = null;
         if ($user->role === 'petugas') {
             $petugasAchievement = [
                 'collections_count' => Payment::where('approved_by', $user->id)
                     ->whereBetween('paid_at', [$start->startOfDay(), $end->endOfDay()])
                     ->count(),
                 'total_amount' => (float)Payment::where('approved_by', $user->id)
                     ->whereBetween('paid_at', [$start->startOfDay(), $end->endOfDay()])
                     ->sum('payments.amount'),
                 'taxpayers_registered' => \App\Models\Taxpayer::where('created_by', $user->id)
                     ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
                     ->count(),
             ];
         }

        return response()->json([
            'total_revenue' => $totalRevenue,
            'collection_rate' => $collectionRate,
            'pending_bills' => $pendingBillsCount,
            'active_taxpayers' => $activeTaxpayersCount,
            'petugas_achievement' => $petugasAchievement,
            'trends' => [
                'revenue' => ($revenueTrend >= 0 ? '+' : '') . $revenueTrend . '%',
                'collection_rate' => ($rateTrend >= 0 ? '+' : '') . $rateTrend . '%',
                'pending_bills' => ($pendingTrend >= 0 ? '+' : '') . $pendingTrend . '%',
                'active_taxpayers' => ($taxpayerTrend >= 0 ? '+' : '') . $taxpayerTrend . '%'
            ],
            'revenue_by_type' => Payment::join('bills', 'payments.bill_id', '=', 'bills.id')
                ->join('retribution_types', 'bills.retribution_type_id', '=', 'retribution_types.id')
                ->when($opdId, fn($q) => $q->where('bills.opd_id', $opdId))
                ->when($user->retribution_type_id && in_array($user->role, ['admin', 'pengawas']), fn($q) => $q->where('bills.retribution_type_id', $user->retribution_type_id))
                ->when($user->role === 'petugas', function($q) use ($user) {
                    $assignments = $user->assignments;
                    if ($assignments->isNotEmpty()) {
                        $q->where(function($query) use ($assignments) {
                            foreach ($assignments as $assignment) {
                                $query->orWhere(function($sq) use ($assignment) {
                                    $sq->where('bills.retribution_type_id', $assignment->retribution_type_id);
                                    if ($assignment->retribution_classification_id) {
                                        $sq->where('bills.retribution_classification_id', $assignment->retribution_classification_id);
                                    }
                                });
                            }
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->whereBetween('payments.paid_at', [$start->startOfDay(), $end->endOfDay()])
                ->select('retribution_types.name', DB::raw('SUM(payments.amount) as total'))
                ->groupBy('retribution_types.name')
                ->get(),
            'revenue_by_classification' => Payment::join('bills', 'payments.bill_id', '=', 'bills.id')
                ->leftJoin('retribution_classifications', 'bills.retribution_classification_id', '=', 'retribution_classifications.id')
                ->when($opdId, fn($q) => $q->where('bills.opd_id', $opdId))
                ->when($user->retribution_type_id && in_array($user->role, ['admin', 'pengawas']), fn($q) => $q->where('bills.retribution_type_id', $user->retribution_type_id))
                ->when($user->role === 'petugas', function($q) use ($user) {
                    $assignments = $user->assignments;
                    if ($assignments->isNotEmpty()) {
                        $q->where(function($query) use ($assignments) {
                            foreach ($assignments as $assignment) {
                                $query->orWhere(function($sq) use ($assignment) {
                                    $sq->where('bills.retribution_type_id', $assignment->retribution_type_id);
                                    if ($assignment->retribution_classification_id) {
                                        $sq->where('bills.retribution_classification_id', $assignment->retribution_classification_id);
                                    }
                                });
                            }
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->whereBetween('payments.paid_at', [$start->startOfDay(), $end->endOfDay()])
                ->select(
                    DB::raw('COALESCE(retribution_classifications.name, "Lainnya/Belum Terklasifikasi") as name'),
                    DB::raw('SUM(payments.amount) as total')
                )
                ->groupBy('name')
                ->get()
        ]);
    }

    private function getRevenue($opdId, $start, $end)
    {
        return Payment::query()
            ->when($opdId, function ($query) use ($opdId) {
                $query->whereExists(function ($sub) use ($opdId) {
                    $sub->select(DB::raw(1))
                        ->from('bills')
                        ->whereColumn('bills.id', 'payments.bill_id')
                        ->where('bills.opd_id', $opdId);
                });
            })
            ->when(auth()->user()->role === 'petugas', function ($query) {
                $assignments = auth()->user()->assignments;
                if ($assignments->isNotEmpty()) {
                    $query->whereExists(function ($sub) use ($assignments) {
                        $sub->select(DB::raw(1))
                            ->from('bills')
                            ->whereColumn('bills.id', 'payments.bill_id')
                            ->where(function($q) use ($assignments) {
                                foreach ($assignments as $assignment) {
                                    $q->orWhere(function($sq) use ($assignment) {
                                        $sq->where('bills.retribution_type_id', $assignment->retribution_type_id);
                                        if ($assignment->retribution_classification_id) {
                                            $sq->where('bills.retribution_classification_id', $assignment->retribution_classification_id);
                                        }
                                    });
                                }
                            });
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->whereBetween('paid_at', [$start->startOfDay(), $end->endOfDay()])
            ->sum('payments.amount');
    }

    private function getPendingCount($opdId, $start, $end)
    {
        return Bill::where('status', 'pending')
            ->when($opdId, fn($q) => $q->where('opd_id', $opdId))
            ->when(auth()->user()->role === 'petugas', function($q) {
                $typeIds = auth()->user()->assignments->pluck('retribution_type_id')->unique()->toArray();
                $q->whereIn('retribution_type_id', $typeIds);
            })
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->count();
    }

    private function getCollectionRate($opdId, $start, $end)
    {
        $totalBills = Bill::when($opdId, fn($q) => $q->where('opd_id', $opdId))
            ->when(auth()->user()->role === 'petugas', function($q) {
                $typeIds = auth()->user()->assignments->pluck('retribution_type_id')->unique()->toArray();
                $q->whereIn('retribution_type_id', $typeIds);
            })
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->count();
        
        $paidBills = Bill::where('status', 'lunas')
            ->when($opdId, fn($q) => $q->where('opd_id', $opdId))
            ->when(auth()->user()->role === 'petugas', function($q) {
                $typeIds = auth()->user()->assignments->pluck('retribution_type_id')->unique()->toArray();
                $q->whereIn('retribution_type_id', $typeIds);
            })
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->count();

        return $totalBills > 0 ? round(($paidBills / $totalBills) * 100, 1) : 0;
    }

    private function calculateTrend($current, $previous, $lowerIsBetter = false)
    {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        $percentChange = round((($current - $previous) / $previous) * 100, 1);
        return $percentChange;
    }

    /**
     * Get revenue trend data for the chart
     * Supports dynamic date filtering and smart grouping
     */
    public function getRevenueTrend(Request $request)
    {
        $user = $request->user();
        if (in_array($user->role, ['citizen', 'wajib_pajak'])) {
            return response()->json(['message' => 'Unauthorized Access'], 403);
        }
        // Allow Super Admin to filter by opd_id query parameter
        // Non-SuperAdmin users are restricted to their own opd_id
        $opdId = $request->query('opd_id');
        if (!$user->isSuperAdmin()) {
            $opdId = $user->opd_id;
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Default to last 6 months if no dates provided
        if (!$startDate || !$endDate) {
            $start = Carbon::now()->subMonths(5)->startOfMonth();
            $end = Carbon::now()->endOfMonth();
        } else {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
        }

        $diffInDays = $start->diffInDays($end) + 1;

        // Dynamic grouping: If range <= 31 days, group by day; otherwise group by month
        if ($diffInDays <= 31) {
            // Daily grouping for short ranges (day/week/month view)
            $dateSql = SqlDate::date('paid_at');
            $trend = Payment::select(
                DB::raw("$dateSql as date_label"),
                DB::raw('SUM(amount) as amount')
            )
            ->when($opdId, function ($q) use ($opdId) {
                $q->whereExists(function ($sub) use ($opdId) {
                    $sub->select(DB::raw(1))
                        ->from('bills')
                        ->whereColumn('bills.id', 'payments.bill_id')
                        ->where('bills.opd_id', $opdId);
                });
            })
            ->whereBetween('paid_at', [$start, $end])
            ->groupBy('date_label')
            ->orderBy('date_label', 'asc')
            ->get()
            ->map(function ($item) {
                $date = Carbon::parse($item->date_label);
                return [
                    'month' => $date->format('d M'), // e.g., "15 Jan"
                    'amount' => $item->amount,
                ];
            });
        } else {
            // Monthly grouping for longer ranges (yearly view)
            $yearSql = SqlDate::year('paid_at');
            $monthSql = SqlDate::month('paid_at');
            $trend = Payment::select(
                DB::raw("$yearSql as year"),
                DB::raw("$monthSql as month_num"),
                DB::raw('SUM(amount) as amount')
            )
            ->when($opdId, function ($q) use ($opdId) {
                $q->whereExists(function ($sub) use ($opdId) {
                    $sub->select(DB::raw(1))
                        ->from('bills')
                        ->whereColumn('bills.id', 'payments.bill_id')
                        ->where('bills.opd_id', $opdId);
                });
            })
            ->whereBetween('paid_at', [$start, $end])
            ->groupByRaw("$yearSql, $monthSql")
            ->orderByRaw("$yearSql asc")
            ->orderByRaw("$monthSql asc")
            ->limit(12)
            ->get()
            ->map(function ($item) {
                return [
                    'month' => Carbon::create((int) $item->year, (int) $item->month_num, 1)->format('F'),
                    'amount' => $item->amount,
                ];
            });
        }

        return response()->json($trend);
    }

    /**
     * Get geo-potential data for the map
     */
    public function getMapPotentials(Request $request)
    {
        $user = $request->user();
        if (in_array($user->role, ['citizen', 'wajib_pajak'])) {
            return response()->json(['message' => 'Unauthorized Access'], 403);
        }
        // Allow Super Admin to filter by opd_id query parameter
        // Non-SuperAdmin users are restricted to their own opd_id
        $opdId = $request->query('opd_id');
        if (!$user->isSuperAdmin()) {
            $opdId = $user->opd_id;
        }
        
        // 1. Get Zones (Potentials)
        $zones = \App\Models\Zone::with(['opd', 'retributionType'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('status', 'active')
            ->when($opdId, fn($q) => $q->where('opd_id', $opdId))
            ->when($user->role === 'petugas', function($q) use ($user) {
                $assignments = $user->assignments ?? collect();
                if ($assignments->isNotEmpty()) {
                    $q->where(function($query) use ($assignments) {
                        foreach ($assignments as $assignment) {
                            $query->orWhere(function($sq) use ($assignment) {
                                $sq->where('retribution_type_id', $assignment->retribution_type_id);
                                if ($assignment->retribution_classification_id) {
                                    $sq->where('retribution_classification_id', $assignment->retribution_classification_id);
                                }
                            });
                        }
                    });
                }
            })
            ->when($user->retribution_type_id && in_array($user->role, ['admin', 'pengawas']), function($q) use ($user) {
                $q->where('retribution_type_id', $user->retribution_type_id);
            })
            ->get()
            ->map(function($obj) {
                return [
                    'position' => [(float)$obj->latitude, (float)$obj->longitude],
                    'name' => $obj->name . ' (' . ($obj->retributionType->name ?? 'N/A') . ')',
                    'agency' => $obj->opd->name ?? 'N/A',
                    'address' => $obj->description,
                    'status' => 'zone',
                    'icon' => $obj->retributionType->icon ?? null,
                    'retribution_type_id' => $obj->retribution_type_id,
                    'opd_id' => $obj->opd_id,
                ];
            });

        // 2. Get Taxpayers (Tax Objects)
        $taxObjects = \App\Models\TaxObject::with(['taxpayer', 'retributionType', 'opd', 'classification'])
            ->withCount(['bills as pending_bills_count' => function($q) {
                $q->whereNotIn('status', ['paid', 'lunas']);
            }])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('status', 'active')
            ->whereHas('taxpayer', fn($q) => $q->where('is_active', true))
            ->when($opdId, fn($q) => $q->where('opd_id', $opdId))
            ->when($user->role === 'petugas', function($q) use ($user) {
                $assignments = $user->assignments ?? collect();
                if ($assignments->isNotEmpty()) {
                    $q->where(function($query) use ($assignments) {
                        foreach ($assignments as $assignment) {
                            $query->orWhere(function($sq) use ($assignment) {
                                $sq->where('retribution_type_id', $assignment->retribution_type_id);
                                if ($assignment->retribution_classification_id) {
                                    $sq->where('retribution_classification_id', $assignment->retribution_classification_id);
                                }
                            });
                        }
                    });
                }
            })
            ->when($user->retribution_type_id && in_array($user->role, ['admin', 'pengawas']), function($q) use ($user) {
                $q->where('retribution_type_id', $user->retribution_type_id);
            })
            ->get()
            ->map(function($obj) {
                return [
                    'position' => [(float)$obj->latitude, (float)$obj->longitude],
                    'tax_object_id' => $obj->id,
                    'name' => ($obj->taxpayer->name ?? 'N/A') . ' - ' . $obj->name,
                    'agency' => $obj->opd->name ?? 'N/A',
                    'address' => $obj->address,
                    'status' => 'taxpayer',
                    'is_paid' => $obj->pending_bills_count === 0,
                    'classification_name' => $obj->classification->name ?? 'N/A',
                    'classification_icon' => $obj->classification->icon ?? null,
                    'taxpayer_photo' => $obj->taxpayer->metadata['foto_lokasi_open_kamera'] ?? null,
                    'icon' => null,
                    'retribution_type_id' => $obj->retribution_type_id,
                    'retribution_classification_id' => $obj->retribution_classification_id,
                    'opd_id' => $obj->opd_id,
                    'metadata' => $obj->metadata,
                ];
            });

        return response()->json($zones->concat($taxObjects));
    }
}
