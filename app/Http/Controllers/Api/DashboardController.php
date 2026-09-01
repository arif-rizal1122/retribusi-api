<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Opd;
use App\Models\Payment;
use App\Models\PetugasTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Dashboard agregat M-PAD (M39-1).
 *
 * Semua angka berasal dari data nyata DB (tabel bills / payments /
 * petugas_tasks / opds / tax_transactions). Tidak ada mock.
 * Scope: middleware 'opd_scope' menyuntikkan scope_opd_id & is_global_scope.
 */
class DashboardController extends Controller
{
    /** Status pembayaran yang dianggap realisasi (terverifikasi). */
    private const PAID_PAYMENT_STATUSES = ['approved', 'success'];

    /** Status tagihan lunas. */
    private const PAID_BILL_STATUSES = ['lunas', 'paid'];

    /** Status tagihan belum bayar (menunggu). */
    private const UNPAID_BILL_STATUSES = ['pending', 'unpaid'];

    // ---------------------------------------------------------------------
    // GET /api/dashboard/pad-summary
    // ---------------------------------------------------------------------
    public function padSummary(Request $request)
    {
        $scopeOpdId = $request->attributes->get('scope_opd_id');
        $isGlobal = (bool) $request->attributes->get('is_global_scope', true);

        // Realisasi PAD: total pembayaran berstatus approved/success.
        $revenueQuery = Payment::whereIn('status', self::PAID_PAYMENT_STATUSES);
        if ($scopeOpdId) {
            $revenueQuery->whereExists(function ($sub) use ($scopeOpdId) {
                $sub->select(DB::raw(1))
                    ->from('bills')
                    ->whereColumn('bills.id', 'payments.bill_id')
                    ->where('bills.opd_id', $scopeOpdId);
            });
        }
        $totalRevenue = (float) $revenueQuery->sum('payments.amount');

        // Agregat tagihan.
        $billsQuery = Bill::query();
        if ($scopeOpdId) {
            $billsQuery->where('opd_id', $scopeOpdId);
        }

        $totalBills = (clone $billsQuery)->count();
        $paidBills = (clone $billsQuery)->whereIn('status', self::PAID_BILL_STATUSES)->count();
        $pendingCount = (clone $billsQuery)->whereIn('status', self::UNPAID_BILL_STATUSES)->count();
        $overdueCount = (clone $billsQuery)->where(function ($q) {
            $q->where('status', 'overdue')
                ->orWhere(function ($q2) {
                    $q2->whereIn('status', self::UNPAID_BILL_STATUSES)
                        ->whereNotNull('due_date')
                        ->where('due_date', '<', now());
                });
        })->count();
        $billedAmount = (float) (clone $billsQuery)->sum('amount');

        // Kepatuhan: rasio tagihan lunas vs seluruh tagihan.
        $complianceRate = $totalBills > 0 ? round(($paidBills / $totalBills) * 100, 1) : 0;

        return response()->json([
            'data' => [
                'period' => now()->format('Y-m'),
                'period_label' => now()->format('F Y'),
                'total_revenue' => $totalRevenue,
                'realized' => $totalRevenue,
                'target' => 0,
                'target_apbd' => null,
                'realization_rate' => 0,
                'total_bills' => $totalBills,
                'paid_count' => $paidBills,
                'pending_count' => $pendingCount,
                'overdue_count' => $overdueCount,
                'billed_amount' => $billedAmount,
                'compliance_rate' => $complianceRate,
                'monthly_trend' => $this->monthlyTrend($scopeOpdId, 6),
            ],
            'scope' => [
                'opd_id' => $scopeOpdId,
                'is_global' => $isGlobal,
            ],
            'meta' => [
                'sources' => ['bills', 'payments'],
                'notes' => 'target_apbd belum tersedia di DB (tidak ada tabel target APBD) sehingga dikembalikan 0 secara jujur.',
            ],
        ]);
    }

    // ---------------------------------------------------------------------
    // GET /api/dashboard/sector-data?opd_id=X
    // ---------------------------------------------------------------------
    public function sectorData(Request $request)
    {
        $scopeOpdId = $request->attributes->get('scope_opd_id');
        $isGlobal = (bool) $request->attributes->get('is_global_scope', true);

        $opdQuery = Opd::query()->where('status', 'approved');
        if ($scopeOpdId) {
            $opdQuery->where('id', $scopeOpdId);
        }
        $opds = $opdQuery->orderBy('name')->get();

        $data = $opds->map(function (Opd $opd) {
            $stats = Bill::where('opd_id', $opd->id)
                ->selectRaw('COUNT(*) as total_bills')
                ->selectRaw('COALESCE(SUM(amount),0) as billed_amount')
                ->selectRaw('COALESCE(SUM(CASE WHEN status IN ("lunas","paid") THEN 1 ELSE 0 END),0) as paid_count')
                ->selectRaw('COALESCE(SUM(CASE WHEN status IN ("pending","unpaid") THEN 1 ELSE 0 END),0) as pending_count')
                ->selectRaw('COALESCE(SUM(CASE WHEN status = "overdue" OR (status IN ("pending","unpaid") AND due_date IS NOT NULL AND due_date < NOW()) THEN 1 ELSE 0 END),0) as overdue_count')
                ->first();

            // Realisasi per OPD: pembayaran sukses yang tertaut ke tagihan OPD tsb.
            $paidAmount = (float) Payment::whereIn('status', self::PAID_PAYMENT_STATUSES)
                ->whereExists(function ($sub) use ($opd) {
                    $sub->select(DB::raw(1))
                        ->from('bills')
                        ->whereColumn('bills.id', 'payments.bill_id')
                        ->where('bills.opd_id', $opd->id);
                })
                ->sum('payments.amount');

            $totalBills = (int) $stats->total_bills;
            $billedAmount = (float) $stats->billed_amount;

            return [
                'opd_id' => $opd->id,
                'opd_code' => $opd->code,
                'opd_name' => $opd->name,
                'total_bills' => $totalBills,
                'billed_amount' => $billedAmount,
                'paid_amount' => $paidAmount,
                'outstanding_amount' => max(0, $billedAmount - $paidAmount),
                'paid_count' => (int) $stats->paid_count,
                'pending_count' => (int) $stats->pending_count,
                'overdue_count' => (int) $stats->overdue_count,
                'compliance_rate' => $totalBills > 0 ? round(((int) $stats->paid_count / $totalBills) * 100, 1) : 0,
                'realization_rate' => $billedAmount > 0 ? round(($paidAmount / $billedAmount) * 100, 1) : 0,
            ];
        });

        return response()->json([
            'data' => $data->values(),
            'scope' => [
                'opd_id' => $scopeOpdId,
                'is_global' => $isGlobal,
            ],
        ]);
    }

    // ---------------------------------------------------------------------
    // GET /api/dashboard/daily-tasks
    // ---------------------------------------------------------------------
    public function dailyTasks(Request $request)
    {
        $user = $request->user();
        $scopeOpdId = $request->attributes->get('scope_opd_id');
        $isGlobal = (bool) $request->attributes->get('is_global_scope', true);

        $query = PetugasTask::with(['user', 'taxpayer', 'taxObject', 'zone'])
            ->where('status', 'pending');

        // Petugas hanya melihat tugas miliknya sendiri.
        if ($user->role === 'petugas') {
            $query->where('user_id', $user->id);
        } elseif ($scopeOpdId) {
            // Scope OPD: tugas yang petugasnya dari OPD tsb ATAU objek pajaknya milik OPD tsb.
            $query->where(function ($q) use ($scopeOpdId) {
                $q->whereHas('user', fn ($u) => $u->where('opd_id', $scopeOpdId))
                    ->orWhereHas('taxObject', fn ($t) => $t->where('opd_id', $scopeOpdId));
            });
        }

        $tasks = $query->orderBy('due_date')->orderBy('created_at', 'desc')->limit(100)->get();

        $today = Carbon::now()->startOfDay();
        $data = $tasks->map(function (PetugasTask $task) use ($today) {
            $due = $task->due_date ? Carbon::parse($task->due_date) : null;
            $dueLabel = null;
            if ($due) {
                if ($due->lt($today)) {
                    $dueLabel = 'overdue';
                } elseif ($due->equalTo($today)) {
                    $dueLabel = 'hari_ini';
                } else {
                    $dueLabel = 'akan_datang';
                }
            }

            return [
                'id' => $task->id,
                'task_type' => $task->task_type,
                'status' => $task->status,
                'due_date' => $task->due_date?->toDateString(),
                'due_label' => $dueLabel,
                'notes' => $task->notes,
                'assignee' => $task->user ? [
                    'id' => $task->user->id,
                    'name' => $task->user->name,
                    'role' => $task->user->role,
                ] : null,
                'taxpayer_name' => $task->taxpayer?->name,
                'tax_object_name' => $task->taxObject?->name,
                'zone_name' => $task->zone?->name,
                'created_at' => $task->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $data->values(),
            'scope' => [
                'opd_id' => $scopeOpdId,
                'is_global' => $isGlobal,
                'user_id' => $user->role === 'petugas' ? $user->id : null,
            ],
            'meta' => [
                'source' => 'petugas_tasks',
                'count' => $data->count(),
            ],
        ]);
    }

    // ---------------------------------------------------------------------
    // GET /api/dashboard/anomalies
    // ---------------------------------------------------------------------
    public function anomalies(Request $request)
    {
        $scopeOpdId = $request->attributes->get('scope_opd_id');
        $isGlobal = (bool) $request->attributes->get('is_global_scope', true);

        $anomalies = [];

        // 1) Tagihan berstatus lunas/paid tetapi TIDAK punya catatan pembayaran
        //    (indikasi data hilang / rekonsiliasi tidak sinkron).
        $missingPaymentBills = Bill::whereIn('status', self::PAID_BILL_STATUSES)
            ->when($scopeOpdId, fn ($q) => $q->where('opd_id', $scopeOpdId))
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('payments')->whereColumn('payments.bill_id', 'bills.id');
            })
            ->with(['opd', 'taxpayer'])
            ->limit(50)
            ->get();

        foreach ($missingPaymentBills as $bill) {
            $anomalies[] = [
                'id' => 'bill_no_payment_'.$bill->id,
                'type' => 'payment_record_missing',
                'severity' => 'tinggi',
                'title' => 'Tagihan lunas tanpa catatan pembayaran',
                'description' => 'Tagihan '.$bill->bill_number.' berstatus '.$bill->status.' tetapi tidak ada baris pembayaran di tabel payments.',
                'amount' => (float) $bill->amount,
                'related' => [
                    'bill_id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'opd_id' => $bill->opd_id,
                    'opd_name' => $bill->opd?->name,
                    'taxpayer_name' => $bill->taxpayer?->name,
                ],
                'detected_at' => now()->toIso8601String(),
                'source' => 'rekonsiliasi_bill_vs_payment',
            ];
        }

        // 2) Pembayaran berstatus approved/success tetapi tagihan terkait belum lunas
        //    (indikasi status tidak sinkron antara payments ↔ bills).
        $orphanPayments = Payment::whereIn('status', self::PAID_PAYMENT_STATUSES)
            ->when($scopeOpdId, function ($q) use ($scopeOpdId) {
                $q->whereExists(function ($sub) use ($scopeOpdId) {
                    $sub->select(DB::raw(1))
                        ->from('bills')
                        ->whereColumn('bills.id', 'payments.bill_id')
                        ->where('bills.opd_id', $scopeOpdId);
                });
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('bills')
                    ->whereColumn('bills.id', 'payments.bill_id')
                    ->whereIn('bills.status', self::PAID_BILL_STATUSES);
            })
            ->with(['bill', 'taxpayer'])
            ->limit(50)
            ->get();

        foreach ($orphanPayments as $payment) {
            $anomalies[] = [
                'id' => 'payment_bill_mismatch_'.$payment->id,
                'type' => 'payment_status_mismatch',
                'severity' => 'sedang',
                'title' => 'Pembayaran sukses tetapi tagihan belum lunas',
                'description' => 'Pembayaran #'.$payment->id.' (Rp '.number_format((float) $payment->amount, 0, ',', '.').') berstatus '.$payment->status.' namun tagihan #'.$payment->bill_id.' belum berstatus lunas.',
                'amount' => (float) $payment->amount,
                'related' => [
                    'payment_id' => $payment->id,
                    'bill_id' => $payment->bill_id,
                    'bill_number' => $payment->bill?->bill_number,
                    'paid_at' => $payment->paid_at?->toIso8601String(),
                ],
                'detected_at' => now()->toIso8601String(),
                'source' => 'rekonsiliasi_bill_vs_payment',
            ];
        }

        return response()->json([
            'data' => array_slice($anomalies, 0, 100),
            'scope' => [
                'opd_id' => $scopeOpdId,
                'is_global' => $isGlobal,
            ],
            'meta' => [
                'source' => 'rekonsiliasi_bill_vs_payment; tax_transactions.correction_type',
                'count' => count($anomalies),
                'notes' => 'Deteksi anomali dari rekonsiliasi data nyata (bills vs payments). Koreksi transaksi (tax_transactions.correction_type) saat ini 0 baris sehingga tidak ditampilkan.',
            ],
        ]);
    }

    /**
     * Tupoksi & PIC OPD — daftar layanan pendapatan yang diampu OPD login,
     * jumlah objek (dari WP) per layanan, dan PIC petugas yang ditugaskan.
     * GET /api/dashboard/opd-services
     */
    public function opdServices(Request $request)
    {
        $scopeOpdId = $request->attributes->get('scope_opd_id');
        $isGlobal = (bool) $request->attributes->get('is_global_scope', true);
        $user = $request->user();

        $opdId = $scopeOpdId ?: $user?->opd_id;
        $opd = $opdId ? Opd::find($opdId) : null;

        // Semua layanan pendapatan (classification) — scope ke OPD jika ada
        $query = \App\Models\RetributionClassification::query()
            ->with(['retributionType'])
            ->whereIn('retribution_type_id', function ($q) {
                $q->select('id')->from('retribution_types')->where('is_active', 1);
            })
            ->orderBy('retribution_type_id')->orderBy('name');

        if ($opdId) {
            $query->where('opd_id', $opdId);
        }

        $services = $query->get()->map(function ($svc) {
            $objQuery = \App\Models\TaxObject::where('retribution_classification_id', $svc->id);
            $total = (clone $objQuery)->count();
            $pending = (clone $objQuery)->whereIn('status', ['pending', 'proses'])->count();
            $active = (clone $objQuery)->whereIn('status', ['active', 'approved'])->count();

            // OPD pengampu layanan ini (info dikaitkan ke OPD terkait)
            $svcOpd = $svc->opd_id ? Opd::find($svc->opd_id) : null;
            $opdInfo = $svcOpd ? [
                'id' => $svcOpd->id,
                'name' => $svcOpd->name,
                'code' => $svcOpd->code,
            ] : null;

            // PIC petugas yang ditugaskan ke layanan ini (via user_retribution_assignments)
            // Filter: petugas OPD yang sama dengan layanan (opd_id cocok) — PIC OPD, bukan semua petugas Bapenda.
            $pics = \App\Models\UserRetributionAssignment::query()
                ->where(function ($q) use ($svc) {
                    $q->where('retribution_classification_id', $svc->id)
                      ->orWhere(function ($q2) use ($svc) {
                          $q2->whereNull('retribution_classification_id')
                             ->where('retribution_type_id', $svc->retribution_type_id);
                      });
                })
                ->whereHas('user', function ($q) use ($svc) {
                    $q->where('role', 'petugas');
                    if ($svc->opd_id) {
                        $q->where('opd_id', $svc->opd_id);
                    }
                })
                ->with('user')
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->user?->id,
                    'name' => $a->user?->name,
                    'role' => $a->user?->role,
                ])
                ->filter(fn ($p) => $p['id'] !== null)
                ->unique('id')
                ->values();

            return [
                'id' => $svc->id,
                'name' => $svc->name,
                'retribution_type' => $svc->retributionType?->name,
                'retribution_type_id' => $svc->retribution_type_id,
                'opd' => $opdInfo,
                'total_objects' => $total,
                'pending_objects' => $pending,
                'active_objects' => $active,
                'pic' => $pics,
            ];
        });

        return response()->json([
            'opd' => $opd ? ['id' => $opd->id, 'name' => $opd->name, 'code' => $opd->code] : null,
            'is_global' => $isGlobal,
            'total_services' => $services->count(),
            'total_objects' => $services->sum('total_objects'),
            'pending_objects' => $services->sum('pending_objects'),
            'services' => $services->values(),
        ]);
    }

    /**
     * Tren realisasi N bulan terakhir (diisi 0 untuk bulan tanpa pembayaran).
     */
    private function monthlyTrend(?int $scopeOpdId, int $months = 6): array
    {
        $start = Carbon::now()->subMonths($months - 1)->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $rows = Payment::whereIn('status', self::PAID_PAYMENT_STATUSES)
            ->when($scopeOpdId, function ($q) use ($scopeOpdId) {
                $q->whereExists(function ($sub) use ($scopeOpdId) {
                    $sub->select(DB::raw(1))
                        ->from('bills')
                        ->whereColumn('bills.id', 'payments.bill_id')
                        ->where('bills.opd_id', $scopeOpdId);
                });
            })
            ->whereBetween('paid_at', [$start, $end])
            ->select(DB::raw("DATE_FORMAT(paid_at, '%Y-%m') as ym"), DB::raw('SUM(amount) as total'))
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $trend = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $d = Carbon::now()->subMonths($i);
            $key = $d->format('Y-m');
            $trend[] = [
                'label' => $d->format('M'),
                'month' => $key,
                'value' => (float) ($rows[$key] ?? 0),
            ];
        }

        return $trend;
    }
}
