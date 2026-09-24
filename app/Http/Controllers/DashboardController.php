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
     * Role lintas-OPD (kota) — sinkron dengan GLOBAL_ROLES di
     * App\Http\Middleware\EnsureOpdScope.
     *
     * Role ini boleh melihat agregat SELURUH OPD (mis. Bapenda sebagai
     * pengendali pendapatan daerah, kepala_bapenda, walikota/pengawas).
     * Sedangkan role opd/petugas/verifikator tetap terkunci ke opd_id sendiri.
     *
     * Sebelumnya gate di sini hardcode `isSuperAdmin()`, sehingga role
     * `admin`/`kepala_bapenda` (yang di middleware sudah dianggap global)
     * tetap dipaksa ter-scope ke OPD-nya sendiri — dashboard Bapenda jadi
     * identik dengan dashboard dinas biasa.
     */
    private const CITY_WIDE_ROLES = [
        'super_admin',
        'admin',
        'walikota',
        'kepala_bapenda',
        'viewer',
        'pengawas',
        'kabid_pengawas',
        'kasubid_pengawas',
    ];

    /**
     * Tentukan filter opd_id untuk agregat dashboard.
     *
     * - Role lintas-OPD: null (= semua OPD) kecuali minta ?opd_id= tertentu.
     * - Role lain     : dikunci ke opd_id miliknya sendiri.
     */
    private function resolveScopeOpdId(Request $request): ?int
    {
        $user = $request->user();

        if (in_array($user->role, self::CITY_WIDE_ROLES, true)) {
            $requested = $request->query('opd_id');

            return ($requested !== null && $requested !== '' && is_numeric($requested))
                ? (int) $requested
                : null;
        }

        return $user->opd_id ? (int) $user->opd_id : null;
    }

    /**
     * Scope DOMAIN WILAYAH untuk role bidang pajak (Kabid/Kasubid).
     *
     * Tanpa ini, endpoint dasbor hanya menyaring `opd_id` sehingga Kabid
     * Wilayah I melihat seluruh layanan Bapenda termasuk milik Wilayah II
     * (PBJT Parkir, Air Tanah, dst) — angka lintas wilayah rawan masuk laporan.
     *
     * ⚠️ Helper ini semula hanya ada di Api\DashboardController sehingga
     * pemanggilan dari controller ini melempar
     * "Call to undefined method ...::classIdsForRole()" -> HTTP 500.
     * Disalin ke sini agar kedua controller punya.
     *
     * @return int[]|null null = tidak dibatasi (role non-bidang atau domain all)
     */
    private function classIdsForRole(?\App\Models\User $user): ?array
    {
        if (! $user || ! \App\Support\ServiceAccessMatrix::isServiceScopedRole($user->role ?? null)) {
            return null;
        }

        $domain = \App\Support\TaxDomainHelper::getDomain($user);
        $ids = \App\Support\TaxDomainHelper::getClassificationIds($domain);

        return empty($ids) ? null : $ids;
    }

    /**
     * Get KPI statistics for the dashboard with date filtering
     */
    public function getStats(Request $request)
    {
        $user = $request->user();
        if (in_array($user->role, ['citizen', 'wajib_pajak'])) {
            return response()->json(['message' => 'Unauthorized Access'], 403);
        }
        // Filter OPD: role lintas-OPD boleh lihat seluruh OPD (?opd_id= opsional),
        // role OPD/petugas/verifikator tetap terkunci ke opd_id sendiri.
        $opdId = $this->resolveScopeOpdId($request);

        // ── Scope DOMAIN WILAYAH ──
        // Role ber-scope layanan (Kabid/Kasubid Bidang Pajak) hanya boleh
        // melihat angka untuk layanan wilayahnya. Tanpa ini, Kabid Wilayah I
        // melihat realisasi PBJT Parkir & Air Tanah milik Wilayah II —
        // pembagian wilayah jadi tidak bermakna dan rawan salah lapor.
        $classIds = null;
        if ($user && \App\Support\ServiceAccessMatrix::isServiceScopedRole($user->role ?? null)) {
            $domainUser = \App\Support\TaxDomainHelper::getDomain($user);
            $ids = \App\Support\TaxDomainHelper::getClassificationIds($domainUser);
            if (! empty($ids)) {
                $classIds = $ids;
            }
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
        $totalRevenue = $this->getRevenue($opdId, $start, $end, $classIds);
        $prevRevenue = $this->getRevenue($opdId, $prevStart, $prevEnd, $classIds);
        $revenueTrend = $this->calculateTrend($totalRevenue, $prevRevenue);

        // Pending Bills
        $pendingBillsCount = $this->getPendingCount($opdId, $start, $end, $classIds);
        $prevPendingCount = $this->getPendingCount($opdId, $prevStart, $prevEnd, $classIds);
        $pendingTrend = $this->calculateTrend($pendingBillsCount, $prevPendingCount, true); // Lower is better

        // Collection Rate
        $collectionRate = $this->getCollectionRate($opdId, $start, $end, $classIds);
        $prevCollectionRate = $this->getCollectionRate($opdId, $prevStart, $prevEnd, $classIds);
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
                // Scope domain wilayah: role bidang pajak hanya melihat
                // realisasi layanan wilayahnya, bukan seluruh Bapenda.
                ->when($classIds, fn($q) => $q->whereIn('bills.retribution_classification_id', $classIds))
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

    private function getRevenue($opdId, $start, $end, ?array $classIds = null)
    {
        return Payment::query()
            ->when($classIds, function ($query) use ($classIds) {
                $query->whereExists(function ($sub) use ($classIds) {
                    $sub->select(DB::raw(1))
                        ->from('bills')
                        ->whereColumn('bills.id', 'payments.bill_id')
                        ->whereIn('bills.retribution_classification_id', $classIds);
                });
            })
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

    private function getPendingCount($opdId, $start, $end, ?array $classIds = null)
    {
        return Bill::where('status', 'pending')
            ->when($classIds, fn($q) => $q->whereIn('retribution_classification_id', $classIds))
            ->when($opdId, fn($q) => $q->where('opd_id', $opdId))
            ->when(auth()->user()->role === 'petugas', function($q) {
                $typeIds = auth()->user()->assignments->pluck('retribution_type_id')->unique()->toArray();
                $q->whereIn('retribution_type_id', $typeIds);
            })
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->count();
    }

    private function getCollectionRate($opdId, $start, $end, ?array $classIds = null)
    {
        $totalBills = Bill::when($classIds, fn($q) => $q->whereIn('retribution_classification_id', $classIds))
            ->when($opdId, fn($q) => $q->where('opd_id', $opdId))
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
        // Filter OPD: role lintas-OPD boleh lihat seluruh OPD (?opd_id= opsional),
        // role OPD/petugas/verifikator tetap terkunci ke opd_id sendiri.
        $opdId = $this->resolveScopeOpdId($request);

        // Scope domain wilayah (role bidang pajak) — lihat classIdsForRole().
        $classIds = $this->classIdsForRole($user);

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
            ->when($opdId || $classIds, function ($q) use ($opdId, $classIds) {
                $q->whereExists(function ($sub) use ($opdId, $classIds) {
                    $sub->select(DB::raw(1))
                        ->from('bills')
                        ->whereColumn('bills.id', 'payments.bill_id');
                    if ($opdId) {
                        $sub->where('bills.opd_id', $opdId);
                    }
                    if ($classIds) {
                        $sub->whereIn('bills.retribution_classification_id', $classIds);
                    }
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
            ->when($opdId || $classIds, function ($q) use ($opdId, $classIds) {
                $q->whereExists(function ($sub) use ($opdId, $classIds) {
                    $sub->select(DB::raw(1))
                        ->from('bills')
                        ->whereColumn('bills.id', 'payments.bill_id');
                    if ($opdId) {
                        $sub->where('bills.opd_id', $opdId);
                    }
                    if ($classIds) {
                        $sub->whereIn('bills.retribution_classification_id', $classIds);
                    }
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
        // Filter OPD: role lintas-OPD boleh lihat seluruh OPD (?opd_id= opsional),
        // role OPD/petugas/verifikator tetap terkunci ke opd_id sendiri.
        $opdId = $this->resolveScopeOpdId($request);

        // Scope domain wilayah (role bidang pajak) — lihat classIdsForRole().
        $classIds = $this->classIdsForRole($user);

        // 1. Get Zones (Potentials)
        $zones = \App\Models\Zone::with(['opd', 'retributionType'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($classIds, fn($q) => $q->whereIn('retribution_classification_id', $classIds))
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

        // 3. Get Field Survey Tasks (Penugasan Survei Lapangan Reklame & Objek Pajak)
        $surveyTasksQuery = \App\Models\PetugasTask::with(['taxObject.classification', 'taxObject.taxpayer', 'taxObject.opd', 'taxpayer', 'user'])
            ->where('status', 'pending')
            ->whereHas('taxObject', function($q) {
                $q->whereNotNull('latitude')->whereNotNull('longitude');
            });

        if ($user->role === 'petugas') {
            $surveyTasksQuery->where('user_id', $user->id);
        } elseif ($opdId) {
            $surveyTasksQuery->whereHas('taxObject', fn($q) => $q->where('opd_id', $opdId));
        }

        $surveyTasks = $surveyTasksQuery->get()->map(function($task) {
            $obj = $task->taxObject;
            return [
                'position' => [(float)$obj->latitude, (float)$obj->longitude],
                'task_id' => $task->id,
                'tax_object_id' => $obj->id,
                'name' => '📍 [SURVEI] ' . ($obj->name ?? 'Objek Baru'),
                'agency' => $obj->opd->name ?? 'BAPENDA',
                'address' => $obj->address,
                'status' => 'field_survey',
                'is_paid' => false,
                'classification_name' => $obj->classification->name ?? 'Pajak Reklame',
                'classification_icon' => $obj->classification->icon ?? null,
                'notes' => $task->notes,
                'due_date' => $task->due_date,
                'taxpayer_name' => $task->taxpayer->name ?? ($obj->taxpayer->name ?? 'Wajib Pajak'),
                'metadata' => $obj->metadata,
            ];
        });

        return response()->json($zones->concat($taxObjects)->concat($surveyTasks));
    }
}
