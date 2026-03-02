<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TaxObject;
use App\Models\Billing;
use Illuminate\Support\Str;

class SptpdController extends Controller
{
    /**
     * Store a newly created SPTPD (self-assessment billing) in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'citizen') {
            return response()->json(['message' => 'Hanya Wajib Pajak yang dapat melaporkan SPTPD.'], 403);
        }

        $request->validate([
            'tax_object_id' => 'required|exists:tax_objects,id',
            'period' => 'required|date_format:Y-m',
            'omzet' => 'required|numeric|min:0',
        ]);

        $taxObject = TaxObject::with('retributionType')->findOrFail($request->tax_object_id);

        // Verify ownership
        if ($taxObject->user_id !== $user->id && optional($taxObject->taxpayer)->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized. Objek pajak ini bukan milik Anda.'], 403);
        }

        // Check if bill already exists for this period
        $existing = Billing::where('tax_object_id', $taxObject->id)
            ->where('period', $request->period)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Tagihan untuk periode ini sudah ada.'], 422);
        }

        // Calculate amount (Omzet * Tarif)
        // Ensure rate exists, fallback to 10% if not set
        $rate = $taxObject->retributionType->base_rate ?? 0.10; 
        
        // If the base rate is stored as a whole number percentage, divide by 100
        if ($rate > 1) {
            $rate = $rate / 100;
        }

        $amount = collect([(float)$request->omzet * $rate])->first();

        // Calculate due date (e.g., end of the next month)
        $dueDate = \Carbon\Carbon::parse($request->period . '-01')->addMonth()->endOfMonth();

        $billing = Billing::create([
            'tax_object_id' => $taxObject->id,
            'period' => $request->period,
            'amount' => $amount,
            'total_amount' => $amount, // without penalty initially
            'due_date' => $dueDate,
            'status' => 'pending',
            'bill_number' => 'SPTPD-' . date('Ymd') . '-' . Str::random(4),
            'omzet_reported' => $request->omzet // custom field via migration or dynamic
        ]);

        return response()->json([
            'message' => 'SPTPD berhasil dilaporkan. Tagihan telah dibuat.',
            'data' => $billing
        ], 201);
    }
}
