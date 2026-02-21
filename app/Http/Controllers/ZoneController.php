<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use Illuminate\Http\Request;

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
        foreach (['retribution_classification_id', 'amount', 'code', 'description'] as $field) {
            if (isset($input[$field]) && $input[$field] === '') {
                $input[$field] = null;
            }
        }
        $request->merge($input);

        $request->validate([
            'opd_id' => 'required|exists:opds,id',
            'retribution_type_id' => 'required|exists:retribution_types,id',
            'retribution_classification_id' => 'nullable|exists:retribution_classifications,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10|unique:zones',
            'multiplier' => 'required|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'geometry_type' => 'nullable|in:point,polygon',
            'coordinates' => 'nullable|array',
        ]);

        $zone = Zone::create($request->all());

        return response()->json($zone->load(['opd', 'retributionType', 'classification']), 201);
    }

    public function show(Zone $zone)
    {
        return response()->json($zone);
    }

    public function update(Request $request, Zone $zone)
    {
        // Sanitize empty strings to null for nullable fields
        $input = $request->all();
        foreach (['retribution_classification_id', 'amount', 'code', 'description'] as $field) {
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
            'code' => 'nullable|string|max:10|unique:zones,code,' . $zone->id,
            'multiplier' => 'sometimes|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'geometry_type' => 'nullable|in:point,polygon',
            'coordinates' => 'nullable|array',
        ]);

        $zone->update($request->all());

        return response()->json($zone->load(['opd', 'retributionType', 'classification']));
    }

    public function destroy(Zone $zone)
    {
        $zone->delete();
        return response()->json(['message' => 'Zona berhasil dihapus']);
    }
}
