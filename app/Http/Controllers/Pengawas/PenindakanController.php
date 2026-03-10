<?php

namespace App\Http\Controllers\Pengawas;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\EnforcementNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PenindakanController extends Controller
{
    /**
     * Generate SKPDKB (Surat Ketetapan Pajak Daerah Kurang Bayar)
     * Based on audit findings from an Enforcement Notice
     */
    public function generateSKPDKB(Request $request)
    {
        $request->validate([
            'enforcement_notice_id' => 'nullable|exists:enforcement_notices,id',
            'spot_check_id' => 'nullable|exists:spot_checks,id',
            'deficit_amount' => 'required_without:spot_check_id|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if (!Auth::user()->isKabid() && !Auth::user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Only Kabid can issue SKPDKB.'], 403);
        }

        return DB::transaction(function () use ($request) {
            $taxpayerId = null;
            $taxObjectId = null;
            $retributionTypeId = null;
            $notes = $request->notes;
            $amount = $request->deficit_amount;
            $period = 'Manual Audit Deficit';

            if ($request->enforcement_notice_id) {
                $notice = EnforcementNotice::findOrFail($request->enforcement_notice_id);
                $taxpayerId = $notice->taxObject->taxpayer_id;
                $taxObjectId = $notice->tax_object_id;
                $retributionTypeId = $notice->taxObject->retribution_type_id;
                $notes = 'Penindakan SKPDKB berdasarkan audit nomor ' . $notice->number . '. ' . $notes;
                $period = $notice->created_at->format('F Y') . ' (Audit Deficit)';
            } elseif ($request->spot_check_id) {
                $spotCheck = \App\Models\SpotCheck::with('taxObject')->findOrFail($request->spot_check_id);
                $taxpayerId = $spotCheck->taxpayer_id;
                $taxObjectId = $spotCheck->tax_object_id;
                $retributionTypeId = $spotCheck->taxObject->retribution_type_id;
                
                $service = new \App\Services\SpotCheckService();
                $estimation = $service->calculateEstimatedMonthlyRevenue($taxObjectId);
                $amount = $estimation['estimated_monthly_revenue'];
                
                $notes = 'Penindakan SKPDKB berdasarkan hasil Uji Petik nomor #' . $spotCheck->id . '. ' . $notes;
                $period = $spotCheck->created_at->format('F Y') . ' (Uji Petik Estimation)';
            }

            // Create a new bill specifically for the underpayment
            $bill = Bill::create([
                'taxpayer_id' => $taxpayerId,
                'tax_object_id' => $taxObjectId,
                'spot_check_id' => $request->spot_check_id,
                'retribution_type_id' => $retributionTypeId,
                'bill_number' => 'SKPDKB-' . strtoupper(uniqid()),
                'amount' => $amount,
                'period' => $period,
                'due_date' => now()->addDays(30),
                'status' => 'pending',
                'notes' => $notes,
                'created_by' => Auth::id(),
            ]);

            if ($request->enforcement_notice_id) {
                $notice->update([
                    'status' => 'penindakan_issued',
                    'notes' => $notice->notes . "\n[SKPDKB Issued: " . $bill->bill_number . "]",
                ]);
            }

            return response()->json([
                'message' => 'SKPDKB berhasil diterbitkan.',
                'bill' => $bill
            ], 201);
        });
    }

    /**
     * List all Penindakan (SKPDKB) bills
     */
    public function index()
    {
        $penindakan = Bill::where('bill_number', 'like', 'SKPDKB%')
            ->with(['taxpayer', 'taxObject', 'retributionType'])
            ->latest()
            ->get();

        return response()->json(['data' => $penindakan]);
    }
}
