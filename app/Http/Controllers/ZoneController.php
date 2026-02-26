<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ZoneController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Zone::with(['opd', 'retributionType', 'classification']);

        if ($user && in_array($user->role, ['opd', 'petugas'])) {
            $query->where('opd_id', $user->opd_id);
        } elseif ($request->has('opd_id')) {
            $query->where('opd_id', $request->opd_id);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        // Sanitize empty strings to null for nullable fields
        $input = $request->all();
        foreach (['retribution_classification_id', 'code', 'description', 'latitude', 'longitude'] as $field) {
            if (isset($input[$field]) && $input[$field] === '') {
                $input[$field] = null;
            }
        }
        $request->merge($input);

        $request->validate([
            'opd_id' => 'nullable|exists:opds,id',
            'retribution_type_id' => 'required|exists:retribution_types,id',
            'retribution_classification_id' => 'nullable|exists:retribution_classifications,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:zones',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'geometry_type' => 'nullable|in:point,polygon',
            'coordinates' => 'nullable|array',
        ]);

        $data = $request->only([
            'opd_id',
            'retribution_type_id',
            'retribution_classification_id',
            'name',
            'code',
            'description',
            'latitude',
            'longitude',
            'geometry_type',
            'coordinates',
        ]);

        try {
            // Infer opd_id from retribution type if not provided
            if (empty($data['opd_id'])) {
                $retributionType = \App\Models\RetributionType::find($data['retribution_type_id']);
                if ($retributionType) {
                    $data['opd_id'] = $retributionType->opd_id;
                }
            }

            // Final safety check for opd_id
            if (empty($data['opd_id'])) {
                return response()->json([
                    'message' => 'Gagal membuat zona: Kolom opd_id wajib diisi atau tidak dapat disimpulkan dari jenis retribusi.',
                    'error_detail' => 'opd_id_not_found_during_inference'
                ], 422);
            }

            // Generate code if not provided
            if (empty($data['code'])) {
                $baseCode = 'Z-' . strtoupper(Str::slug($data['name']));
                $data['code'] = $baseCode . '-' . strtoupper(Str::random(4));
                
                // Ensure uniqueness
                while (Zone::where('code', $data['code'])->exists()) {
                    $data['code'] = $baseCode . '-' . strtoupper(Str::random(4));
                }
            }

            $zone = Zone::create($data);

            return response()->json($zone->load(['opd', 'retributionType', 'classification']), 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('Zone Creation Failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal membuat zona: ' . $e->getMessage(),
                'error_detail' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function show(Zone $zone)
    {
        return response()->json($zone);
    }

    public function update(Request $request, Zone $zone)
    {
        // Sanitize empty strings to null for nullable fields
        $input = $request->all();
        foreach (['retribution_classification_id', 'code', 'description', 'latitude', 'longitude'] as $field) {
            if (isset($input[$field]) && $input[$field] === '') {
                $input[$field] = null;
            }
        }
        $request->merge($input);

        $request->validate([
            'opd_id' => 'sometimes|exists:opds,id',
            'retribution_type_id' => 'sometimes|exists:retribution_types,id',
            'retribution_classification_id' => 'nullable|exists:retribution_classifications,id',
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:50|unique:zones,code,' . $zone->id,
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'geometry_type' => 'nullable|in:point,polygon',
            'coordinates' => 'nullable|array',
        ]);

        try {
            $data = $request->only([
                'opd_id',
                'retribution_type_id',
                'retribution_classification_id',
                'name',
                'code',
                'description',
                'latitude',
                'longitude',
                'geometry_type',
                'coordinates',
            ]);

            // Infer opd_id if missing or empty
            if (empty($data['opd_id']) && !empty($data['retribution_type_id'])) {
                $retType = \App\Models\RetributionType::find($data['retribution_type_id']);
                if ($retType) {
                    $data['opd_id'] = $retType->opd_id;
                }
            }

            $zone->update($data);

            return response()->json($zone->load(['opd', 'retributionType', 'classification']));
        } catch (\Throwable $e) {
            \Log::error('Zone Update Failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal memperbarui zona: ' . $e->getMessage(),
                'error_detail' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function destroy(Zone $zone)
    {
        $zone->delete();
        return response()->json(['message' => 'Zona berhasil dihapus']);
    }
}
