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

        if ($user && ($user->role === 'opd' || $user->role === 'kabid_pengawas' || $user->role === 'kasubid_pengawas' || $user->role === 'admin' || $user->role === 'pengawas')) {
            $query->where('opd_id', $user->opd_id);
        } elseif ($user && $user->role === 'petugas') {
            $query->where('opd_id', $user->opd_id);
            
            // Filter objects created by this Petugas (via Taxpayer relation or object relation)
            $query->whereHas('taxpayer', function($q) use ($user) {
                $q->where('created_by', $user->id);
            });

            $assignments = $user->assignments;
            if ($assignments && $assignments->count() > 0) {
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

        $perPage = $request->get('per_page', 50);
        if ($perPage == -1) {
            $objects = $query->latest()->get();
            return response()->json(['data' => $objects]);
        }

        $objects = $query->latest()->paginate($perPage);

        return response()->json($objects);
    }

    /**
     * Manually store a new tax object (useful for testing or direct Petugas API)
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Authorization: Only OPD admins or Petugas can create tax objects directly here.
        if (!in_array($user->role, ['opd', 'petugas'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'taxpayer_id' => 'required|exists:taxpayers,id',
            'retribution_type_id' => 'required|exists:retribution_types,id',
            'retribution_classification_id' => 'nullable|exists:retribution_classifications,id',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'nop' => 'nullable|string|max:50',
        ]);

        // Ensure the retribution type belongs to the user's OPD
        $type = \App\Models\RetributionType::where('id', $request->retribution_type_id)
            ->where('opd_id', $user->opd_id)
            ->firstOrFail();

        // Ensure taxpayer belongs to OPD
        $taxpayer = \App\Models\Taxpayer::where('id', $request->taxpayer_id)
            ->where('opd_id', $user->opd_id)
            ->firstOrFail();

        $taxObject = TaxObject::create([
            'opd_id' => $user->opd_id,
            'taxpayer_id' => $taxpayer->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $request->retribution_classification_id,
            'name' => $request->name,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'nop' => $request->nop,
            'status' => 'approved', // Manual creation -> assume approved for testing
            'is_active' => true,
            'metadata' => [],
        ]);

        return response()->json([
            'message' => 'Objek pajak berhasil ditambahkan.',
            'data' => $taxObject
        ], 201);
    }

    /**
     * Display the specified tax object
     */
    public function show(TaxObject $taxObject)
    {
        $taxObject->load(['taxpayer', 'retributionType', 'opd', 'classification']);
        return response()->json($taxObject);
    }

    /**
     * Update a pending tax object
     */
    public function update(Request $request, TaxObject $taxObject)
    {
        $user = $request->user();
        
        // Authorization: Only owner can edit (if user is a taxpayer)
        if ($user->role === 'citizen' || $user->role === 'wajib_pajak') {
            if ($taxObject->taxpayer_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } elseif ($user->role === 'opd') {
            if ($taxObject->opd_id !== $user->opd_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        // Only allow editing if status is pending
        if ($taxObject->status !== 'pending') {
            return response()->json(['message' => 'Hanya objek dengan status pending yang dapat diedit.'], 422);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'metadata' => 'nullable',
        ]);

        $metadata = $taxObject->metadata ?? [];
        $newMetadata = $request->input('metadata');
        if ($newMetadata) {
            if (is_string($newMetadata)) {
                $newMetadata = json_decode($newMetadata, true) ?: [];
            }
            $metadata = array_merge($metadata, $newMetadata);
        }

        // Handle dynamic document uploads
        $cloudinary = app(\App\Services\CloudinaryService::class);
        $classification = $taxObject->classification;
        $requirements = $classification->requirements ?? [];
        $processedKeys = [];

        foreach ($requirements as $req) {
            $key = $req['key'] ?? null;
            if ($key && $request->hasFile($key)) {
                $metadata[$key] = $cloudinary->upload(
                    $request->file($key), 
                    'citizen/documents/' . $taxObject->retribution_type_id
                );
                $processedKeys[] = $key;
            }
        }

        // Fallback files
        $fallbacks = ['foto_lokasi_open_kamera', 'formulir_data_dukung'];
        foreach ($fallbacks as $key) {
            if (!in_array($key, $processedKeys) && $request->hasFile($key)) {
                $metadata[$key] = $cloudinary->upload(
                    $request->file($key), 
                    'citizen/documents/' . $taxObject->retribution_type_id
                );
            }
        }

        $taxObject->update([
            'name' => $request->input('name', $taxObject->name),
            'address' => $request->input('address', $taxObject->address),
            'metadata' => $metadata,
        ]);

        return response()->json([
            'message' => 'Data objek berhasil diperbarui',
            'data' => $taxObject
        ]);
    }

    /**
     * Delete a pending tax object
     */
    public function destroy(Request $request, TaxObject $taxObject)
    {
        $user = $request->user();
        
        // Authorization: Only owner can delete (if user is a taxpayer)
        if (in_array($user->role, ['petugas', 'pengawas', 'kabid_pengawas', 'kasubid_pengawas'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        } elseif ($user->role === 'citizen' || $user->role === 'wajib_pajak') {
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
