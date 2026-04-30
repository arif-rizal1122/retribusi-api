<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\User;
use App\Support\SqlDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get summary reports based on date range
     */
    public function getSummary(Request $request)
    {
        $user = $request->user();
        $opdId = !$user->isSuperAdmin() ? $user->opd_id : $request->query('opd_id');

        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->toDateString());

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Revenue by Type
        $revenueByType = Payment::join('bills', 'payments.bill_id', '=', 'bills.id')
            ->join('retribution_types', 'bills.retribution_type_id', '=', 'retribution_types.id')
            ->when($opdId, fn($q) => $q->where('bills.opd_id', $opdId))
            ->whereBetween('payments.paid_at', [$start, $end])
            ->select(
                'retribution_types.name as type',
                DB::raw('SUM(payments.amount) as amount'),
                DB::raw('COUNT(payments.id) as count')
            )
            ->groupBy('retribution_types.name')
            ->get();

        // Calculate percentages
        $totalAmount = $revenueByType->sum('amount');
        $revenueByType = $revenueByType->map(function ($item) use ($totalAmount) {
            $item->percentage = $totalAmount > 0 ? round(($item->amount / $totalAmount) * 100, 1) : 0;
            // Target is a placeholder for now, can be linked to a targets table later
            $item->target = $item->amount * 1.2; 
            return $item;
        });

        return response()->json([
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'total_revenue' => $totalAmount,
            'revenue_by_type' => $revenueByType,
            'stats' => [
                'total_transactions' => $revenueByType->sum('count'),
                'avg_transaction' => $revenueByType->sum('count') > 0 ? round($totalAmount / $revenueByType->sum('count'), 0) : 0
            ]
        ]);
    }

    /**
     * Get recent payments list
     */
    public function getRecent(Request $request)
    {
        $user = $request->user();
        $opdId = !$user->isSuperAdmin() ? $user->opd_id : $request->query('opd_id');

        $payments = Payment::with(['bill.retributionType', 'bill.taxpayer'])
            ->whereHas('bill', function($q) use ($opdId, $user) {
                if ($opdId) $q->where('opd_id', $opdId);
                
                // If petugas, filter by their assignments if they exist
                if ($user->role === 'petugas') {
                    $assignments = $user->assignments;
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
                }
            })
            ->when($user->role === 'petugas', function($q) use ($user) {
                // Also show payments they personally approved even if outside assignment somehow
                $q->orWhere('approved_by', $user->id);
            })
            ->orderBy('paid_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($p) {
                return [
                    'id' => $p->id,
                    'taxpayer_name' => $p->bill->taxpayer->name ?? 'N/A',
                    'type' => $p->bill->retributionType->name ?? 'N/A',
                    'amount' => $p->amount,
                    'date' => $p->paid_at->toDateTimeString(),
                    'method' => $p->payment_method ?? 'CASH',
                    'status' => 'Verified' 
                ];
            });

        return response()->json($payments);
    }

    /**
     * Get performance stats for petugas (users with petugas role)
     */
    public function getPetugasPerformance(Request $request)
    {
        $admin = $request->user();
        $opdId = (!$admin->isSuperAdmin() && !$admin->isPengawas()) ? $admin->opd_id : $request->query('opd_id');

        $performance = User::where('role', 'petugas')
            ->when($opdId, fn($q) => $q->where('opd_id', $opdId))
            ->with(['opd'])
            ->withCount(['confirmedPayments as total_collections'])
            ->withSum(['confirmedPayments as total_amount'], 'amount')
            ->withCount(['createdTaxpayers as taxpayers_registered'])
            ->get()
            ->map(function($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'opd' => $u->opd->name ?? 'N/A',
                    'collections_count' => (int)($u->total_collections ?? 0),
                    'total_amount' => (float)($u->total_amount ?? 0),
                    'taxpayers_count' => (int)($u->taxpayers_registered ?? 0),
                    'status' => $u->status
                ];
            });

        return response()->json($performance);
    }

    /**
     * BPK-format monthly report: Revenue breakdown by type with realization vs target
     * Endpoint: GET /api/reports/monthly
     */
    public function getMonthlyReport(Request $request)
    {
        $user = $request->user();
        $opdId = !$user->isSuperAdmin() ? $user->opd_id : $request->query('opd_id');
        $year = $request->query('year', Carbon::now()->year);
        $monthSql = SqlDate::month('payments.paid_at');

        // Monthly breakdown per retribution type
        $monthlyData = Payment::join('bills', 'payments.bill_id', '=', 'bills.id')
            ->join('retribution_types', 'bills.retribution_type_id', '=', 'retribution_types.id')
            ->when($opdId, fn($q) => $q->where('bills.opd_id', $opdId))
            ->whereYear('payments.paid_at', $year)
            ->select(
                'retribution_types.id as type_id',
                'retribution_types.name as type_name',
                'retribution_types.category',
                DB::raw("$monthSql as month"),
                DB::raw('SUM(payments.amount) as realization'),
                DB::raw('COUNT(payments.id) as tx_count')
            )
            ->groupBy('retribution_types.id', 'retribution_types.name', 'retribution_types.category')
            ->groupByRaw($monthSql)
            ->get();

        // Aggregate target from bills (annual)
        $targets = Bill::join('retribution_types', 'bills.retribution_type_id', '=', 'retribution_types.id')
            ->when($opdId, fn($q) => $q->where('bills.opd_id', $opdId))
            ->whereYear('bills.created_at', $year)
            ->select(
                'retribution_types.id as type_id',
                DB::raw('SUM(bills.amount) as annual_target')
            )
            ->groupBy('retribution_types.id')
            ->pluck('annual_target', 'type_id');

        // Build structured report
        $report = [];
        foreach ($monthlyData->groupBy('type_id') as $typeId => $entries) {
            $first = $entries->first();
            $months = [];
            $ytdRealization = 0;

            for ($m = 1; $m <= 12; $m++) {
                $entry = $entries->firstWhere('month', $m);
                $realization = $entry ? (float) $entry->realization : 0;
                $ytdRealization += $realization;
                $months[] = [
                    'month' => $m,
                    'realization' => $realization,
                    'tx_count' => $entry ? (int) $entry->tx_count : 0,
                ];
            }

            $annualTarget = (float) ($targets[$typeId] ?? 0);
            $report[] = [
                'type_id' => $typeId,
                'type_name' => $first->type_name,
                'category' => $first->category,
                'annual_target' => $annualTarget,
                'ytd_realization' => $ytdRealization,
                'achievement_pct' => $annualTarget > 0 ? round(($ytdRealization / $annualTarget) * 100, 2) : 0,
                'months' => $months,
            ];
        }

        // Grand totals
        $grandTarget = $targets->sum();
        $grandRealization = collect($report)->sum('ytd_realization');

        return response()->json([
            'year' => (int) $year,
            'opd_id' => $opdId,
            'report' => $report,
            'grand_total' => [
                'target' => $grandTarget,
                'realization' => $grandRealization,
                'achievement_pct' => $grandTarget > 0 ? round(($grandRealization / $grandTarget) * 100, 2) : 0,
            ],
        ]);
    }

    /**
     * SIPD-Compliant Report: Laporan Realisasi Anggaran (LRA) & Neraca Piutang
     * Endpoint: GET /api/reports/sipd
     *
     * Structures data per SIPD Mendagri standard:
     * - LRA: Penerimaan PAD per klasifikasi (Pajak Daerah, Retribusi, Lain-lain PAD)
     * - Neraca Piutang: Outstanding receivables grouped by type
     *
     * @see reporting_billing/SKILL.md
     */
    public function getSipdReport(Request $request)
    {
        $user = $request->user();
        $opdId = !$user->isSuperAdmin() ? $user->opd_id : $request->query('opd_id');
        $year = $request->query('year', Carbon::now()->year);

        // ===================================================================
        // LRA: Laporan Realisasi Anggaran - Penerimaan PAD
        // ===================================================================
        $lraData = Payment::join('bills', 'payments.bill_id', '=', 'bills.id')
            ->join('retribution_types', 'bills.retribution_type_id', '=', 'retribution_types.id')
            ->when($opdId, fn($q) => $q->where('bills.opd_id', $opdId))
            ->whereYear('payments.paid_at', $year)
            ->select(
                'retribution_types.category',
                'retribution_types.name as type_name',
                DB::raw('SUM(payments.amount) as realisasi'),
                DB::raw('COUNT(payments.id) as jumlah_transaksi')
            )
            ->groupBy('retribution_types.category', 'retribution_types.name')
            ->get();

        // Group by SIPD classification
        $lra = [
            'pajak_daerah' => [
                'label' => '4.1.01 - Pajak Daerah',
                'items' => [],
                'subtotal' => 0,
            ],
            'retribusi_daerah' => [
                'label' => '4.1.02 - Retribusi Daerah',
                'items' => [],
                'subtotal' => 0,
            ],
            'lain_lain_pad' => [
                'label' => '4.1.04 - Lain-lain PAD yang Sah',
                'items' => [],
                'subtotal' => 0,
            ],
        ];

        $totalRealisasi = 0;
        foreach ($lraData as $item) {
            $cat = $item->category ?? 'lain_lain_pad';
            if (!isset($lra[$cat])) $cat = 'lain_lain_pad';

            $lra[$cat]['items'][] = [
                'uraian'           => $item->type_name,
                'realisasi'        => (float) $item->realisasi,
                'jumlah_transaksi' => (int) $item->jumlah_transaksi,
            ];
            $lra[$cat]['subtotal'] += (float) $item->realisasi;
            $totalRealisasi += (float) $item->realisasi;
        }

        // ===================================================================
        // NERACA: Daftar Piutang Daerah (Outstanding Receivables)
        // ===================================================================
        $piutangData = Bill::join('retribution_types', 'bills.retribution_type_id', '=', 'retribution_types.id')
            ->when($opdId, fn($q) => $q->where('bills.opd_id', $opdId))
            ->whereYear('bills.created_at', $year)
            ->whereIn('bills.status', ['pending', 'unpaid', 'overdue'])
            ->select(
                'retribution_types.category',
                'retribution_types.name as type_name',
                DB::raw('SUM(bills.amount) as total_piutang'),
                DB::raw('SUM(COALESCE(bills.penalty_amount, 0)) as total_denda'),
                DB::raw('COUNT(bills.id) as jumlah_tagihan')
            )
            ->groupBy('retribution_types.category', 'retribution_types.name')
            ->get();

        $piutang = [];
        $totalPiutang = 0;
        $totalDenda = 0;
        foreach ($piutangData as $item) {
            $piutang[] = [
                'uraian'          => $item->type_name,
                'kategori'        => $item->category,
                'piutang_pokok'   => (float) $item->total_piutang,
                'denda'           => (float) $item->total_denda,
                'total'           => (float) $item->total_piutang + (float) $item->total_denda,
                'jumlah_tagihan'  => (int) $item->jumlah_tagihan,
            ];
            $totalPiutang += (float) $item->total_piutang;
            $totalDenda += (float) $item->total_denda;
        }

        return response()->json([
            'format'  => 'SIPD_MENDAGRI',
            'tahun'   => (int) $year,
            'opd_id'  => $opdId,
            'lra'     => [
                'judul'          => 'Laporan Realisasi Anggaran - Pendapatan Asli Daerah',
                'klasifikasi'    => array_values($lra),
                'total_realisasi' => $totalRealisasi,
            ],
            'neraca_piutang' => [
                'judul'          => 'Daftar Piutang Pajak/Retribusi Daerah',
                'items'          => $piutang,
                'total_piutang'  => $totalPiutang,
                'total_denda'    => $totalDenda,
                'grand_total'    => $totalPiutang + $totalDenda,
            ],
            'generated_at' => now()->toDateTimeString(),
        ]);
    }
}
