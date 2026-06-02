<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MutationController extends Controller
{
    /**
     * List recent mutations (audit trail)
     */
    public function index(Request $request)
    {
        $mutations = DB::table('tax_object_mutations')
            ->join('tax_objects', 'tax_object_mutations.tax_object_id', '=', 'tax_objects.id')
            ->join('taxpayers as old_tp', 'tax_object_mutations.old_taxpayer_id', '=', 'old_tp.id')
            ->join('taxpayers as new_tp', 'tax_object_mutations.new_taxpayer_id', '=', 'new_tp.id')
            ->select(
                'tax_object_mutations.*',
                'tax_objects.name as object_name',
                'tax_objects.nop',
                'old_tp.name as old_taxpayer_name',
                'new_tp.name as new_taxpayer_name'
            )
            ->orderByDesc('tax_object_mutations.created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json($mutations);
    }

    /**
     * Transfer a tax object from one taxpayer to another (Balik Nama)
     * 
     * Omni-Sync Protocol:
     * 1. Reassign tax_object.taxpayer_id
     * 2. Void all pending notices for old taxpayer on this object
     * 3. Reassign pending bills from old taxpayer to new taxpayer
     * 4. Log mutation for audit trail
     */
    public function store(Request $request)
    {
        $request->validate([
            'tax_object_id' => 'required|exists:tax_objects,id',
            'new_taxpayer_id' => 'required|exists:taxpayers,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $taxObject = TaxObject::with('taxpayer')->findOrFail($request->tax_object_id);
        $oldTaxpayerId = $taxObject->taxpayer_id;
        $newTaxpayerId = $request->new_taxpayer_id;

        if ($oldTaxpayerId === $newTaxpayerId) {
            return response()->json([
                'message' => 'Pemilik baru tidak boleh sama dengan pemilik lama.'
            ], 422);
        }

        $newTaxpayer = Taxpayer::findOrFail($newTaxpayerId);
        $user = $request->user();

        DB::beginTransaction();
        try {
            // 1. Reassign tax object
            $taxObject->update([
                'taxpayer_id' => $newTaxpayerId,
            ]);

            // 2. Void pending notices for old taxpayer on this object
            $voidedNotices = DB::table('notices')
                ->where('tax_object_id', $taxObject->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'voided',
                    'metadata' => DB::raw("JSON_SET(COALESCE(metadata, '{}'), '$.void_reason', 'mutation_transfer', '$.voided_at', '" . now()->toIso8601String() . "')"),
                    'updated_at' => now(),
                ]);

            // 3. Reassign pending bills to new taxpayer
            $reassignedBills = Bill::where('tax_object_id', $taxObject->id)
                ->where('status', 'pending')
                ->update([
                    'taxpayer_id' => $newTaxpayerId,
                ]);

            // 4. Log mutation for audit trail
            DB::table('tax_object_mutations')->insert([
                'tax_object_id' => $taxObject->id,
                'old_taxpayer_id' => $oldTaxpayerId,
                'new_taxpayer_id' => $newTaxpayerId,
                'reason' => $request->reason,
                'mutated_by' => $user->id,
                'voided_notices_count' => $voidedNotices,
                'reassigned_bills_count' => $reassignedBills,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            Log::info("Mutation: Tax Object #{$taxObject->id} transferred from Taxpayer #{$oldTaxpayerId} to #{$newTaxpayerId}", [
                'voided_notices' => $voidedNotices,
                'reassigned_bills' => $reassignedBills,
                'admin_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Mutasi berhasil. Objek pajak telah dialihkan.',
                'data' => [
                    'tax_object' => $taxObject->fresh()->load('taxpayer'),
                    'old_taxpayer_id' => $oldTaxpayerId,
                    'new_taxpayer_id' => $newTaxpayerId,
                    'voided_notices' => $voidedNotices,
                    'reassigned_bills' => $reassignedBills,
                ],
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Mutation failed: " . $e->getMessage(), [
                'tax_object_id' => $request->tax_object_id,
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'Gagal melakukan mutasi: ' . $e->getMessage()
            ], 500);
        }
    }
}
