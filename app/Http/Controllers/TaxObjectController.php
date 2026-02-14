<?php

namespace App\Http\Controllers;

use App\Models\TaxObject;
use Illuminate\Http\Request;

class TaxObjectController extends Controller
{
    /**
     * List tax objects (OPD-scoped)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = TaxObject::with(['taxpayer', 'retributionType', 'opd', 'classification']);

        if ($user && $user->role === 'opd') {
            $query->where('opd_id', $user->opd_id);
        } elseif ($user && $user->role === 'petugas') {
            $query->where('opd_id', $user->opd_id);
            
            $assignments = $user->assignments;
            if ($assignments) {
                $query->where(function($q) use ($assignments) {
                    foreach ($assignments as $assignment) {
                        $q->orWhere(function($sq) use ($assignment) {
                            $sq->where('retribution_type_id', $assignment->retribution_type_id);
                            if ($assignment->retribution_classification_id) {
                                $sq->where('retribution_classification_id', $assignment->retribution_classification_id);
                            }
                        });
                    }
                });
            }
        }

        if ($request->has('retribution_type_id')) {
            $query->where('retribution_type_id', $request->retribution_type_id);
        }

        if ($request->has('taxpayer_id')) {
            $query->where('taxpayer_id', $request->taxpayer_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nop', 'like', "%{$search}%")
                  ->orWhereHas('taxpayer', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $objects = $query->latest()->paginate($request->get('per_page', 50));

        return response()->json($objects);
    }

    /**
     * Delete a pending tax object
     */
    public function destroy(Request $request, TaxObject $taxObject)
    {
        $user = $request->user();
        
        // Authorization: Only owner can delete (if user is a taxpayer)
        // If it's an OPD admin, they might have different rules, but here we focus on Citizen/Taxpayer
        if ($user->role === 'citizen') {
            if ($taxObject->taxpayer_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } elseif ($user->role === 'opd') {
            if ($taxObject->opd_id !== $user->opd_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        // Only allow deletion if status is pending
        if ($taxObject->status !== 'pending') {
            return response()->json(['message' => 'Hanya objek dengan status pending yang dapat dihapus.'], 422);
        }

        // Cleanup: Delete associated verifications
        \App\Models\Verification::where('tax_object_id', $taxObject->id)->delete();
        
        // Delete the object
        $taxObject->delete();

        return response()->json(['message' => 'Pengajuan objek berhasil dibatalkan dan dihapus']);
    }
}
