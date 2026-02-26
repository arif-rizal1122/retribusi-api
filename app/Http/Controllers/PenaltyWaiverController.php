<?php

namespace App\Http\Controllers;

use App\Models\PenaltyWaiver;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PenaltyWaiverController extends Controller
{
    public function index(Request $request)
    {
        $query = PenaltyWaiver::with(['bill.taxpayer', 'requester', 'approver']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * Request a penalty waiver (usually by Petugas or Admin on behalf of WP)
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'bill_id' => 'required|exists:bills,id',
                'reason' => 'required|string',
                'reduction_type' => 'required|in:percentage,fixed_amount',
                'reduction_value' => 'required|numeric|min:0',
            ]);

            $bill = Bill::findOrFail($validated['bill_id']);

            // Check if there is already a pending waiver for this bill
            $existing = PenaltyWaiver::where('bill_id', $bill->id)
                ->where('status', 'pending')
                ->first();

            if ($existing) {
                return response()->json(['message' => 'A pending waiver request already exists for this bill.'], 422);
            }

            $waiver = PenaltyWaiver::create([
                'bill_id' => $bill->id,
                'requested_by' => Auth::id(),
                'reason' => $validated['reason'],
                'reduction_type' => $validated['reduction_type'],
                'reduction_value' => $validated['reduction_value'],
                'status' => 'pending',
            ]);

            return response()->json($waiver, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('Penalty Waiver Store Failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal mengajukan dispensasi denda: ' . $e->getMessage(),
                'error_detail' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Approve a penalty waiver (Kabid/SuperAdmin Only)
     */
    public function approve(Request $request, $id)
    {
        $waiver = PenaltyWaiver::with('bill')->findOrFail($id);

        if ($waiver->status !== 'pending') {
            return response()->json(['message' => 'This waiver request is already ' . $waiver->status], 422);
        }

        $validated = $request->validate([
            'approval_notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($waiver, $validated) {
            $bill = $waiver->bill;
            
            // Calculate waiver amount
            $basePenalty = (float) $bill->penalty_amount + (float) $bill->fixed_fine_amount + (float) $bill->surcharge_amount;
            
            $waivedAmount = 0;
            if ($waiver->reduction_type === 'percentage') {
                $waivedAmount = $basePenalty * ($waiver->reduction_value / 100);
            } else {
                $waivedAmount = min($basePenalty, $waiver->reduction_value);
            }

            // Update Bill
            $bill->waived_penalty_amount = round($waivedAmount, 2);
            $bill->save();

            // Update Waiver
            $waiver->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approval_notes' => $validated['approval_notes'] ?? null,
            ]);
        });

        return response()->json($waiver->fresh(['bill', 'approver']));
    }

    public function reject(Request $request, $id)
    {
        $waiver = PenaltyWaiver::findOrFail($id);

        if ($waiver->status !== 'pending') {
            return response()->json(['message' => 'This waiver request is already ' . $waiver->status], 422);
        }

        $validated = $request->validate([
            'approval_notes' => 'required|string',
        ]);

        $waiver->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approval_notes' => $validated['approval_notes'],
        ]);

        return response()->json($waiver);
    }
}
