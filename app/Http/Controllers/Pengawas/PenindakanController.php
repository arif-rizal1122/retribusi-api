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
            'enforcement_notice_id' => 'required|exists:enforcement_notices,id',
            'deficit_amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
        ]);

        $notice = EnforcementNotice::findOrFail($request->enforcement_notice_id);

        if (!Auth::user()->isKabid() && !Auth::user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Only Kabid can issue SKPDKB.'], 403);
        }

        return DB::transaction(function () use ($request, $notice) {
            // Create a new bill specifically for the underpayment
            $bill = Bill::create([
                'taxpayer_id' => $notice->taxObject->taxpayer_id,
                'tax_object_id' => $notice->tax_object_id,
                'retribution_type_id' => $notice->taxObject->retribution_type_id,
                'bill_number' => 'SKPDKB-' . strtoupper(uniqid()),
                'amount' => $request->deficit_amount,
                'period' => $notice->created_at->format('F Y') . ' (Audit Deficit)',
                'due_date' => now()->addDays(30),
                'status' => 'pending',
                'notes' => 'Penindakan SKPDKB berdasarkan audit nomor ' . $notice->number . '. ' . $request->notes,
                'created_by' => Auth::id(),
            ]);

            // Update notice status and link to the new bill
            $notice->update([
                'status' => 'penindakan_issued',
                'notes' => $notice->notes . "\n[SKPDKB Issued: " . $bill->bill_number . "]",
            ]);

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
