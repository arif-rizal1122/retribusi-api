<?php

namespace App\Http\Controllers\Api\V1\Dlh;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Support\CitizenBillStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DlhReportController extends Controller
{
    /**
     * Ringkasan Eksekutif Penerimaan DLH (Door-to-Door, Karcis Pasar, dan UPTD Lab)
     */
    public function getExecutiveSummary(Request $request)
    {
        $year = $request->query('year', date('Y'));
        $month = $request->query('month');

        // 1. Realisasi Persampahan Bulanan (Door-to-Door / Bills)
        $labBillIds = DB::table('dlh_lab_orders')
            ->whereNotNull('bill_id')
            ->select('bill_id');
        $trashBillsQuery = Bill::where('opd_id', 4)
            ->whereNotIn('id', $labBillIds)
            ->whereYear('created_at', $year);
        if ($month) {
            $trashBillsQuery->whereMonth('created_at', $month);
        }
        $trashBills = $trashBillsQuery->get();

        $totalTrashBilled = $trashBills->sum('amount');
        $paidTrashBills = $trashBills->filter(fn (Bill $bill) => $this->isPaidBill($bill));
        $pendingTrashBills = $trashBills->filter(fn (Bill $bill) => $this->isPendingBill($bill));
        $totalTrashPaid = $paidTrashBills->sum('amount');
        $totalTrashPending = $pendingTrashBills->sum('amount');
        $totalTrashPenalties = $paidTrashBills->sum('penalty_amount');

        // 2. Realisasi Karcis Pasar Sampah Harian
        $ticketsQuery = DB::table('dlh_market_trash_tickets')->whereYear('issued_at', $year);
        if ($month) {
            $ticketsQuery->whereMonth('issued_at', $month);
        }
        $tickets = $ticketsQuery->get();
        $totalTicketsAmount = $tickets->sum('amount');
        $totalTicketsCount = $tickets->count();

        // 3. Realisasi Retribusi Uji Laboratorium Lingkungan
        $labOrdersQuery = DB::table('dlh_lab_orders')->whereYear('dlh_lab_orders.created_at', $year);
        if ($month) {
            $labOrdersQuery->whereMonth('dlh_lab_orders.created_at', $month);
        }
        $labOrders = $labOrdersQuery
            ->leftJoin('bills as linked_bills', 'linked_bills.id', '=', 'dlh_lab_orders.bill_id')
            ->select('dlh_lab_orders.*', 'linked_bills.status as linked_bill_status')
            ->get();
        $totalLabOrders = $labOrders->count();
        $totalLabRevenue = $labOrders
            ->filter(fn (object $order) => $this->isPaidLabOrder($order))
            ->sum('total_amount');

        $grandTotalRevenue = $totalTrashPaid + $totalTrashPenalties + $totalTicketsAmount + $totalLabRevenue;

        return response()->json([
            'status' => 'success',
            'data' => [
                'period' => [
                    'year' => (int) $year,
                    'month' => $month ? (int) $month : 'ALL',
                ],
                'grand_total_realization' => (float) $grandTotalRevenue,
                'breakdown' => [
                    'retribusi_persampahan_bulanan' => [
                        'total_billed' => (float) $totalTrashBilled,
                        'total_realized' => (float) $totalTrashPaid,
                        'total_pending' => (float) $totalTrashPending,
                        'total_penalties_collected' => (float) $totalTrashPenalties,
                        'compliance_rate' => $totalTrashBilled > 0 ? round(($totalTrashPaid / $totalTrashBilled) * 100, 2) : 0,
                    ],
                    'retribusi_karcis_pasar' => [
                        'total_tickets_issued' => $totalTicketsCount,
                        'total_realized' => (float) $totalTicketsAmount,
                        'cash_collection' => (float) $tickets->where('payment_method', 'TUNAI')->sum('amount'),
                        'qris_collection' => (float) $tickets->where('payment_method', 'QRIS_INSTANT')->sum('amount'),
                    ],
                    'retribusi_laboratorium_lingkungan' => [
                        'total_orders' => $totalLabOrders,
                        'total_realized' => (float) $totalLabRevenue,
                        'completed_orders' => $labOrders->where('status', 'COMPLETED')->count(),
                    ],
                ],
            ],
        ]);
    }

    private function isPaidBill(Bill $bill): bool
    {
        return CitizenBillStatus::normalize(
            $bill->getRawOriginal('status'),
            false,
            false,
            false,
        ) === 'paid';
    }

    private function isPendingBill(Bill $bill): bool
    {
        return in_array(
            CitizenBillStatus::normalize(
                $bill->getRawOriginal('status'),
                false,
                false,
                false,
            ),
            ['pending', 'overdue', 'pending_verification'],
            true,
        );
    }

    private function isPaidLabOrder(object $order): bool
    {
        // Once the bill is settled, the lab workflow may still be in sampling,
        // analysis, or completed. Keep the revenue in the paid ledger for all
        // of those operational stages, including the legacy linked bill path.
        if (in_array(strtoupper((string) $order->status), ['PAID', 'SAMPLING', 'ANALYZING', 'COMPLETED'], true)) {
            return true;
        }

        return CitizenBillStatus::normalize(
            $order->linked_bill_status,
            false,
            false,
            false,
        ) === 'paid';
    }

    /**
     * Rekapitulasi Realisasi Karcis Pasar per Juru Pungut untuk Audit BPK
     */
    public function getCollectorAuditReport(Request $request)
    {
        $month = $request->query('month', date('n'));
        $year = $request->query('year', date('Y'));

        $collectors = DB::table('dlh_collector_remittances')
            ->join('users', 'dlh_collector_remittances.collector_user_id', '=', 'users.id')
            ->where('dlh_collector_remittances.period_month', $month)
            ->where('dlh_collector_remittances.period_year', $year)
            ->select(
                'users.id as user_id',
                'users.name as collector_name',
                'dlh_collector_remittances.remittance_number',
                'dlh_collector_remittances.total_tickets_issued',
                'dlh_collector_remittances.total_amount_collected',
                'dlh_collector_remittances.remitted_amount',
                'dlh_collector_remittances.status',
                'dlh_collector_remittances.bank_reference_number',
                'dlh_collector_remittances.remittance_date',
                'dlh_collector_remittances.verified_at'
            )
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'period_month' => (int) $month,
                'period_year' => (int) $year,
                'total_remittances' => $collectors->count(),
                'total_remitted_amount' => (float) $collectors->sum('remitted_amount'),
                'records' => $collectors,
            ],
        ]);
    }
}
