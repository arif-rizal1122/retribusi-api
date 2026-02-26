<?php

namespace App\Http\Controllers;

use App\Models\RetributionRate;
use Illuminate\Http\Request;

class RetributionRateController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = RetributionRate::with(['opd', 'retributionType', 'classification', 'zone']);

        if ($user && in_array($user->role, ['opd', 'petugas'])) {
            $query->where('opd_id', $user->opd_id);
        } elseif ($request->has('opd_id')) {
            $query->where('opd_id', $request->opd_id);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'retribution_type_id' => 'required|exists:retribution_types,id',
            'retribution_classification_id' => 'required|exists:retribution_classifications,id',
            'zone_id' => 'nullable|exists:zones,id',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'is_active' => 'boolean',
        ]);

        $opdId = in_array($user->role, ['opd', 'petugas']) ? $user->opd_id : $request->opd_id;
        
        // Infer opd_id from retribution type if not provided (Super Admin)
        if (!$opdId && $user->role === 'super_admin') {
            $retributionType = \App\Models\RetributionType::find($request->retribution_type_id);
            if ($retributionType) {
                $opdId = $retributionType->opd_id;
            } else {
                $request->validate(['opd_id' => 'required|exists:opds,id']);
                $opdId = $request->opd_id;
            }
        }

        try {
            $rate = RetributionRate::create([
                'opd_id' => $opdId,
                'retribution_type_id' => $request->retribution_type_id,
                'retribution_classification_id' => $request->retribution_classification_id,
                'zone_id' => $request->zone_id,
                'name' => $request->name,
                'amount' => $request->amount,
                'unit' => $request->unit,
                'is_active' => $request->boolean('is_active', true),
            ]);

            return response()->json([
                'message' => 'Tarif berhasil ditambahkan',
                'data' => $rate->load(['opd', 'retributionType', 'classification', 'zone'])
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Rate Creation Failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal membuat tarif: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(RetributionRate $retributionRate)
    {
        return response()->json(['data' => $retributionRate->load(['opd', 'retributionType', 'classification', 'zone'])]);
    }

    public function update(Request $request, RetributionRate $retributionRate)
    {
        $user = $request->user();
        if ($user->role !== 'super_admin' && $retributionRate->opd_id !== $user->opd_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'retribution_type_id' => 'sometimes|exists:retribution_types,id',
            'retribution_classification_id' => 'sometimes|exists:retribution_classifications,id',
            'zone_id' => 'nullable|exists:zones,id',
            'name' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0',
            'unit' => 'sometimes|string|max:50',
            'is_active' => 'boolean',
        ]);

        try {
            $retributionRate->update($request->all());

            return response()->json([
                'message' => 'Tarif berhasil diupdate',
                'data' => $retributionRate->load(['opd', 'retributionType', 'classification', 'zone'])
            ]);
        } catch (\Exception $e) {
            \Log::error('Rate Update Failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal memperbarui tarif: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, RetributionRate $retributionRate)
    {
        try {
            $user = $request->user();
            if ($user->role !== 'super_admin' && $retributionRate->opd_id !== $user->opd_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $retributionRate->delete();
            return response()->json(['message' => 'Tarif berhasil dihapus']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('Rate Delete Failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal menghapus tarif: ' . $e->getMessage(),
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }
}
